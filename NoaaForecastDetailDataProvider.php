<?php
/**
 * NoaaForecastDetailDataProvider class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaForecastDetailDataProvider gathers data from the NWS National
 * Digital Forecast Database REST Service Single Point Unsummarized Data 
 * interface and returns data in terms of a Yii DataProvider.
 * More information: http://graphical.weather.gov/xml/rest.php
 */
class NoaaForecastDetailDataProvider extends CArrayDataProvider {

	protected $keyMap = array(
		'temperature-hourly' => 'temperature_hourly',
		'start-valid-time' => 'start_valid_time',
		'temperature-dew point' => 'temperature_dew_point',
		'temperature-apparent' => 'temperature_apparent',
		'wind-speed-sustained' => 'wind_speed_sustained',
		'wind-speed-gust' => 'wind_speed_gust',
		'direction-wind' => 'direction_wind',
		'cloud-amount-total' => 'cloud_amount_total',
		'humidity-relative' => 'humidity_relative',
		'conditions-icon-forecast-NWS' => 'conditions_icon_forecast_NWS',
		'probability-of-precipitation-calculated' => 'probability_of_precipitation_calculated',
		'start-valid-time-calculated' => 'start_valid_time_calculated',
	);
	
	/**
	 * Attach behaviors and set defaults 
	 */
	public function __construct($location,$config=array()) {
		$this->attachBehavior('cache','ext.noaaWeather.NoaaCacheBehavior');
		$this->attachBehavior('fetch','ext.noaaWeather.NoaaFetchRemoteBehavior');
		$this->attachBehavior('locate',array(
			'class' => 'ext.noaaWeather.NoaaLocateBehavior',
			'outputLocators' => 'coordinates',
			'location' => $location,
		));
		$this->attachBehavior('parseDwml',array(
			'class' => 'ext.noaaWeather.NoaaParseDwmlBehavior',
			'dwmlElements' => $this->keyMap,
			));
		$this->sort = false;
		$this->pagination = false;		
		foreach($config as $key=>$value) $this->$key=$value;
	}
	
	/**
	 * Fetch data from disk cache if possible, otherwise from the NOAA REST service 
	 */
	protected function fetchData() {
		$coordinates = $this->getLocation();
		$coordinates = $coordinates['coordinates'];
		$this->cacheId = 'NoaaForecastDetailDataProvider' . '_'. serialize($coordinates);
		if( ($rawData = $this->getCache()) === FALSE ) {
			$ndfdGenDwml = $this->fetchRemote('http://graphical.weather.gov/xml/sample_products/browser_interface/ndfdXMLclient.php?lat=' . $coordinates['latitude'] . '&lon=' . $coordinates['longitude'] . '&product=time-series&temp=temp&dew=dew&appt=appt&wspd=wspd&wgust=wgust&wdir=wdir&sky=sky&rh=rh&icons=icons&snow=snow&qpf=qpf');
			$rawData = $this->parseDwml($ndfdGenDwml);
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
		$id = 'detail';
		$periods = array( 
			'morning' => array('hours' => array(4,5,6,7,8,9)),
			'day' => array('hours' => array(10,11,12,13,14,15)),
			'evening' => array('hours' => array(16,17,18,19,20,21)),
			'night' => array('hours' => array(22,23,24,0,1,2,3)),
			);
		foreach( array_reverse($this->getData()) as $forecastRow ){
			$hour = date( 'G', strtotime($forecastRow['start_valid_time']));
			if($hour < 4) {
				$day = date("Y-m-d", mktime(0, 0, 0, date( 'm', strtotime($forecastRow['start_valid_time'])), date( 'd', strtotime($forecastRow['start_valid_time'])) - 1, date( 'Y', strtotime($forecastRow['start_valid_time']))));
			} else {
				$day = date( 'Y-m-d', strtotime($forecastRow['start_valid_time']));
			}
			
			foreach( $periods as $periodKey => $periodVal ) {
				if(in_array($hour,$periodVal['hours'])) {
					if( ! isset($periodVal['current']) ) {
						$periods[$periodKey]['current'] = $hour;
						$forecast[$day][$id][$periodKey] = $forecastRow;
					} elseif($hour == $periodVal['current']) {
						$forecast[$day][$id][$periodKey] = $forecastRow;
					}
					break;
				}
			}
		}
		return array('forecast'=>array_reverse($forecast,true));
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
