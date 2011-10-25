<?php
/**
 * NoaaForecastMapClickDataProvider class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaForecastMapClickDataProvider component gathers data from the NWS map
 * click forecast page and returns data in terms of a Yii DataProvider. 
 * It provides access to the same familiar forecast information displayed when
 * you search http://www.weather.gov/
 * More information: http://www.nws.noaa.gov/forecasts/xml/
 */
class NoaaForecastMapClickDataProvider extends CArrayDataProvider {

	/**
	* @var array the keyMap to pass to the parseDwml behavior
	*/
	protected $keyMap = array(
		'temperature-maximum' => 'temperature_maximum',
		'temperature-minimum' => 'temperature_minimum',
		'start-valid-time' => 'start_valid_time',
		'start-valid-time-calculated' => 'start_valid_time_calculated',
		'probability-of-precipitation-12 hour' => 'probability_of_precipitation_12_hour',
		'weather' => 'weather',
		'conditions-icon-forecast-NWS' => 'conditions_icon_forecast_NWS',
		'probability-of-precipitation-calculated' => 'probability_of_precipitation_calculated',
		'wordedForecast' => 'worded_forecast',   

		);

	/**
	 * Attach behaviors and set defaults 
	 */
	public function __construct($location,$config=array()) {
		$this->attachBehavior('cache','ext.noaaWeather.NoaaCacheBehavior');
		$this->attachBehavior('fetch','ext.noaaWeather.NoaaFetchRemoteBehavior');
		$this->attachBehavior('locate',array(
			'class' => 'NoaaLocateBehavior',
			'outputLocators' => array('coordinates'),
			'location' => $location,
		));
		$this->attachBehavior('parse',array(
			'class' => 'ext.noaaWeather.NoaaParseDwmlBehavior',
			'dwmlElements' => $this->keyMap,
		));
		$this->sort = false;
		$this->pagination = false;
		foreach($config as $key=>$value)
			$this->$key=$value;
	}
	
	/**
	 * Fetch data from disk cache if possible, otherwise from the NOAA MapClick.php service 
	 */
	protected function fetchData() {
		$location = $this->getLocation();
		$coordinates = $location['coordinates'];
		$this->cacheId = 'NoaaForecastMapClickDataProvider' . '_' . serialize($coordinates);
		if( ($rawData = $this->getCache()) === FALSE ) {
			$url='http://forecast.weather.gov/MapClick.php?&FcstType=dwml&lat=' . $coordinates['latitude'] . '&lon=' . $coordinates['longitude'];
			$dwml = $this->fetchRemote($url);
			$rawData = $this->parseDwml($dwml);
			$this->setCache($rawData);
		}
			
		$this->rawData = $rawData;
		return parent::fetchData();
	}
	
	/**
	 * Returns data formatted in an associative array
	 */
	public function getDataFormatted() {
		$forecast=array();
		foreach( $this->getData() as $forecastRow ){
			$id = 'mapClick';
			$day = date( 'Y-m-d', strtotime($forecastRow['start_valid_time']));
			$meridian = date( 'a', strtotime($forecastRow['start_valid_time']));
			if(isset($forecastRow['temperature_minimum'])) {
				$forecast[$day][$id][$meridian]['temperature_minimum'] = $forecastRow['temperature_minimum'];
			} elseif(isset($forecastRow['temperature_maximum'])) {
				$forecast[$day][$id][$meridian]['temperature_maximum'] = $forecastRow['temperature_maximum'];
			}
			$forecast[$day][$id][$meridian]['weather'] = $forecastRow['weather'];
			$forecast[$day][$id][$meridian]['worded_forecast'] = $forecastRow['worded_forecast'];
			$forecast[$day][$id][$meridian]['probability_of_precipitation_calculated'] = $forecastRow['probability_of_precipitation_calculated'];
			$forecast[$day][$id][$meridian]['conditions_icon_forecast_NWS'] = $forecastRow['conditions_icon_forecast_NWS'];
			$forecast[$day][$id][$meridian]['start_valid_time'] = $forecastRow['start_valid_time'];
		}
		return array('forecast'=>$forecast);
	}
	
	/**
	* Returns a list of keys in the data array
	*/
	protected function fetchKeys() {
		return array_values($this->keyMap);
	}

	/**
	* Counts the items in the data array
	*/
	protected function calculateTotalItemCount() {
		return count($this->rawData);
	}
}
?>
