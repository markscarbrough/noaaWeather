<?php
/**
 * NoaaCurrentObservationsDataProvider class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaCurrentObservationsDataProvider gathers data from the NWS XML feeds of
 * current weather conditions. It provides access to hourly observation data
 * from NWS METAR stations across the US. 
 * More information: http://www.weather.gov/xml/current_obs/ 
 */
class NoaaCurrentObservationsDataProvider extends CArrayDataProvider {

	/**
	 * Attach behaviors and set defaults 
	 */
	public function __construct($location,$config=array()) {
		$this->attachBehavior('cache','ext.noaaWeather.NoaaCacheBehavior');
		$this->attachBehavior('fetch','ext.noaaWeather.NoaaFetchRemoteBehavior');
		$this->attachBehavior('locate',array(
			'class' => 'ext.noaaWeather.NoaaLocateBehavior',
			'outputLocators' => 'weatherStation',
			'location' => $location,
		));
		$this->sort = false;
		$this->pagination = false;
		foreach($config as $key=>$value) $this->$key=$value;
	}
	
	/**
	 * Fetch data from disk cache if possible, otherwise from the NOAA METAR service 
	 */
	protected function fetchData() {
		$location = $this->getLocation();
		$weatherStation = $location['weatherStation'];
		$this->cacheId = 'NoaaCurrentObservationsDataProvider' . '_' . serialize($weatherStation);
		if(($rawData = $this->getCache()) === FALSE ) {
			$weatherStationDataProvider = Yii::createComponent(array(
				'class'=>'ext.noaaWeather.NoaaSqlDataProvider',
				'params'=>array(':station_id'=>$weatherStation)),
				'SELECT * FROM noaa_weather_stations where station_id=:station_id');
			$weatherStation = $weatherStationDataProvider->getData();
			$observationsRawXml = @$this->fetchRemote($weatherStation[0]['xml_url']);
			if(! $observationsSimpleXml = @simplexml_load_string($observationsRawXml)) {
				throw new Exception('Error parsing dwml.');
			}
			$keys = $this->fetchKeys();
			foreach($keys as $key){
				if( $observationsSimpleXml->$key ) {
					$rawData[$key] = (string) $observationsSimpleXml->$key;
				} else {
					$rawData[$key] = null;
				}
			}
			foreach($this->fetchKeys() as $key){
				if(!isset($rawData[$key])){
					$rawData[$key] = null;
				}
			}
			$rawData = array($rawData);
			$this->setCache($rawData);
		}
		$this->rawData = $rawData;
		return parent::fetchData();		
	}
	
	/**
	 * Returns data formatted in an associative array
	 */
	public function getDataFormatted() {
		$currentObservations = $this->getData();
		$forecast['currentObservations'] = $currentObservations[0];
		return $forecast;
	}
	
	/**
	* Returns a list of keys in the data array
	*/
	protected function fetchKeys() {
		return(array(
			'copyright_url',
			'dewpoint_c',
			'dewpoint_f',
			'dewpoint_string',
			'disclaimer_url',
			'heat_index_c',
			'heat_index_f',
			'heat_index_string',
			'icon_url_base',
			'icon_url_name',
			'latitude',
			'location',
			'longitude',
			'ob_url',
			'observation_time',
			'observation_time_rfc822',
			'pressure_in',
			'pressure_mb',
			'pressure_string',
			'privacy_policy_url',
			'relative_humidity',
			'station_id',
			'suggested_pickup',
			'suggested_pickup_period',
			'temp_c',
			'temp_f',
			'temperature_string',
			'two_day_history_url',
			'visibility_mi',
			'weather',
			'wind_degrees',
			'wind_dir',
			'wind_kt',
			'wind_mph',
			'wind_string',
		));
	}

	/**
	* Returns a count of items returned by the search criteria
	*/
	protected function calculateTotalItemCount() {
		return count($this->rawData);
	}
	
}

