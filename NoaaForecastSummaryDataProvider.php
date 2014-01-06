<?php
/**
 * NoaaForecastSummaryDataProvider class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaForecastSummaryDataProvider gathers data from the NWS National Digital
 * Forecast Database REST Service Single Point Summarized Data interface and
 * returns data in terms of a Yii DataProvider.
 * It provides access to summarized forecast information.
 * More information: http://graphical.weather.gov/xml/rest.php
 */
class NoaaForecastSummaryDataProvider extends CArrayDataProvider {

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
		);

	/**
	 * Attach behaviors and set defaults 
	 */
	public function __construct($location,$config=array()) {
		$this->attachBehavior('cache','ext.noaaWeather.NoaaCacheBehavior');
		$this->attachBehavior('fetch','ext.noaaWeather.NoaaFetchRemoteBehavior');
		$this->attachBehavior('locate',array(
			'class' => 'ext.noaaWeather.NoaaLocateBehavior',
			'outputLocators' => array('coordinates'),
			'location' => $location,
		));
		$this->attachBehavior('parseDwml',array(
			'class' => 'ext.noaaWeather.NoaaParseDwmlBehavior',
			'dwmlElements' => $this->keyMap,
		));
		$this->sort = false;
		$this->pagination = false;
		foreach($config as $key=>$value)
			$this->$key=$value;
	}
	
	/**
	 * Fetch data from disk cache if possible, otherwise from the NOAA REST service 
	 */
	protected function fetchData() {
		$coordinates = $this->getLocation();
		$coordinates = $coordinates['coordinates'];
		$this->cacheId = 'NoaaForecastSummaryDataProvider' . '_' . serialize($coordinates);
		if( ($rawData = $this->getCache()) === FALSE ) {
			$dwml = $this->fetchRemote('http://graphical.weather.gov/xml/sample_products/browser_interface/ndfdBrowserClientByDay.php?lat=' . $coordinates['latitude'] . '&lon=' . $coordinates['longitude'] . '&format=12+hourly');
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
			$id = 'summary';
			$day = date( 'Y-m-d', strtotime($forecastRow['start_valid_time']));
			$meridian = date( 'a', strtotime($forecastRow['start_valid_time']));
			if(isset($forecastRow['temperature_minimum'])) {
				$forecast[$day][$id][$meridian]['temperature_minimum'] = $forecastRow['temperature_minimum'];
			} elseif(isset($forecastRow['temperature_maximum'])) {
				$forecast[$day][$id][$meridian]['temperature_maximum'] = $forecastRow['temperature_maximum'];
			}
			$forecast[$day][$id][$meridian]['weather'] = $forecastRow['weather'];
			$forecast[$day][$id][$meridian]['probability_of_precipitation_calculated'] = $forecastRow['probability_of_precipitation_calculated'];
			$forecast[$day][$id][$meridian]['conditions_icon_forecast_NWS'] = $forecastRow['conditions_icon_forecast_NWS'];
			$forecast[$day][$id][$meridian]['start_valid_time'] = $forecastRow['start_valid_time'];
		}
		return $forecast;
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
