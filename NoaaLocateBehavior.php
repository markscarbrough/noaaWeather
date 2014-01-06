<?php
/**
 * NoaaLocateBehavior class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Translates location data between formats using locator methods. Currently
 * supports Latitude/Longitude and NWS Weather Station ID locators. Location
 * data is passed in arrays with the format locator=>location, for example:
 * // Coordinate location array
 * $location = array('coordinates'=>array(
 *			'latitude' => 44.27,
 *			'longitude' => -71.3,
 *			)
 *	 );
 * // Weather station location array
 * $location = array('weatherStation'=>'KMWN');
 *
 * To add a new locator method:
 * - add a key to the $_location['supportedLocators'] array
 * - create an appropriate validateLocator/sanitizeLocator methods
 * - create appropriate getLocationLocatorbyLocator translation methods
 */
class NoaaLocateBehavior extends CBehavior {
	
	/**
	* @var internal location state data
	*/
	protected $_location = array(
		'input' => null,
		'inputLocator' => null,
		'output' => null,
		'outputLocators' => null,
		'supportedLocators' => array(
			'coordinates',	
			'weatherStation',
			),
		);

	/**
	* Sets one or more output locators. 
	*/		
	public function setOutputLocators($outputLocators) {
		if( ! is_array($outputLocators)) $outputLocators=array($outputLocators);
		foreach( $outputLocators as $outputLocator ) {
			if(! in_array($outputLocator,$this->getSupportedLocators())) 
				throw new Exception('Unsupported outputLocator: ' . $outputLocator);
		}
		$this->_location['outputLocators'] = $outputLocators;
	}
	
	/**
	* Returns previously set output locators. 
	*/		
	public function getOutputLocators() {
		return $this->_location['outputLocators'];
	}

	/**
	* Returns supported output locators.
	*/		
	public function getSupportedLocators() {
		return $this->_location['supportedLocators'];
	}
	
	/**
	* Sets input location array with the format inputLocator=>location
	*/	
	public function setLocation($inputRaw) {
		foreach( $this->getSupportedLocators() as $inputLocator ) {
			if(isset($inputRaw[$inputLocator])) {
				
				$validateMethod = 'validate' . ucfirst($inputLocator);
				$sanitizeMethod = 'sanitize' . ucfirst($inputLocator);
				if( method_exists( $this, $validateMethod  )) {
					if( ! $this->$validateMethod($inputRaw[$inputLocator])) {
						throw new Exception('Tried to set invalid location: ' . serialize($inputRaw[$inputLocator]) );
					}
				}
				$inputData = method_exists( $this, $sanitizeMethod ) ? $this->$sanitizeMethod($inputRaw[$inputLocator]) : $inputRaw[$inputLocator]; 
				$this->_location['inputLocator'] = $inputLocator;
				$this->_location['input'][$inputLocator] = $inputData;
				return;
			}
		}
		throw new Exception('No supported locators for location: ' . serialize($this->_location));
	}
		
	/**
	* Returns location array with the format outputLocator=>location
	*/		
	public function getLocation($outputLocators=null) {
		if( ! $this->_location['input'] ) return null;
		if( $outputLocators ) $this->setOutputLocators($outputLocators);
		foreach( $this->getOutputLocators() as $outputLocator ) {
			if( $outputLocator == $this->_location['inputLocator'] ) {
				$this->_location['output'][$outputLocator] = $this->_location['input'][$outputLocator];
			} else {
				$locateMethod = 'getLocation' . ucfirst($outputLocator) . 'By' . ucfirst($this->_location['inputLocator']);
				if( method_exists( $this, $locateMethod  )) {
					$this->_location['output'][$outputLocator] = $this->$locateMethod();
				} else {
					throw new Exception('No locator method: ' . $locateMethod );
				}
			}
		}
		return $this->_location['output'];
	}
		
	/**
	* Validates coordinates input (currently sanity checking only)
	*/		
	public function validateCoordinates($coordinates) {
		if(isset($coordinates['latitude']) && isset($coordinates['longitude'])) {
			if(($coordinates['latitude'] >= -90 && $coordinates['latitude'] <= 90) 
			 && ($coordinates['longitude'] >= -180 && $coordinates['longitude'] <= 180))
				return true;	
		}
		return false;
	}

	/**
	* Sanitizes coordinates input (converts to rounded float as noaa barfs if with too high precision)
	*/		
	public function sanitizeCoordinates($coordinates) {
		$coordinates['latitude'] = round( $coordinates['latitude'], 2 ); 
		$coordinates['longitude'] = round( $coordinates['longitude'], 2 ); 
		return $coordinates;
	}

	/**
	* Validates weather station input (currently sanity checking only)
	*/		
	public function validateWeatherStation($weatherStation) {
		if(isset($weatherStation['weatherstation']))
			return true;	
		return false;
	}
	
	public function getNearestWeatherStations() 
	{
		if($this->_location['inputLocator'] != 'coordinates') 
		{
			$locateMethod = 'getLocationCoordinatesBy' . ucfirst($this->_location['inputLocator']);
			$this->_location['input']['coordinates'] = $this->$locateMethod();
		}
		return $this->getlocationWeatherStationByCoordinates(true);
	}

	/**
	* Translates weather stations into coordinates.
	*/		
	protected function getLocationCoordinatesByWeatherStation(){
		$return = array();
		
		$weatherStationDataProvider = Yii::createComponent(array(
			'class'=>'ext.noaaWeather.NoaaSqlDataProvider',
			'params'=>array(':station_id'=>$this->_location['input']['weatherStation'])),
			'SELECT * FROM noaa_weather_stations where station_id=:station_id');
		$return = $weatherStationDataProvider->getData();
		$return = array('latitude'=>$return[0]['latitude'] ,'longitude'=>$return[0]['longitude']);
		return $return;
	}
	
	/**
	* Translates coordinates into weather stations.
	*/		
	protected function getlocationWeatherStationByCoordinates($returnArray = false){
		$return = array();
		$weatherStationDataProvider = Yii::createComponent('ext.noaaWeather.NoaaSqlDataProvider',
			'SELECT * FROM noaa_weather_stations');
		foreach($weatherStationDataProvider->getData() as $weatherStation) {
			$distance = $this->calculateDistance($this->_location['input']['coordinates'],
				array(
					'latitude' => $weatherStation['latitude'],
					'longitude' => $weatherStation['longitude'])
				);
				
			$return[$distance]['station_id'] = $weatherStation['station_id'];	
			$return[$distance]['distance'] = $distance;
		}
		ksort($return);
		$return = array_values($return);
		return $returnArray ? $return : $return[0]['station_id'];
	}

	/**
	* Calculates the distance between two coordinate points using the spherical
	* law of cosines. Used to find the closest weather station.
	*/	
	protected function calculateDistance($point1,$point2) {
		$distance  = sin(deg2rad($point1['latitude'])) * sin(deg2rad($point2['latitude'])) + cos(deg2rad($point1['latitude'])) * cos(deg2rad($point2['latitude'])) * cos(deg2rad(($point2['longitude'] - $point1['longitude']))) ;
		$distance  = acos($distance);
		$distance  = rad2deg($distance);
		$distance  = $distance * 60 * 1.1515;
		$distance  = round($distance, 4);
		return $distance;
	}
}
