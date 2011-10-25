<?php
/**
 * NoaaParseDwmlBehavior class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Parses DWML formated xml from the NWS into a php array. A small subset of
 * DWML is supported.
 */
class NoaaParseDwmlBehavior extends CBehavior {

	/**
	* @var array the elements to extract from the DWML
	*/
	public $dwmlElements = array();
	
	/**
	* Parses the dwml data and returns a formatted array
	*/
	public function parseDwml($dwmlRaw) {
		// Read the XML into a simpleXML object
		if(! $dwmlSimpleXml = @simplexml_load_string($dwmlRaw)) {
			throw new Exception('Error parsing dwml: ' . $dwmlRaw);
		}
		// Sanity check
		if(! @$dwmlSimpleXml->data->{'time-layout'} ) {
			throw new Exception('Error parsing dwml: ' . $dwmlRaw);
		}
		// Parse the simple xml object into a usable array
		// Read time layouts into array
		foreach( $dwmlSimpleXml->data->{'time-layout'} as $timeLayout) {
			foreach( $timeLayout->{'start-valid-time'} as $key => $start_valid_times ) {
				$timeLayouts[(string)$timeLayout->{'layout-key'}[0]][] = array( 
					'start-valid-time' =>  (string)$start_valid_times[0],
					'start-valid-time-calculated' => date( 'Ymd', strtotime((string)$start_valid_times[0])) .
						date( 'H', strtotime((string)$start_valid_times[0])) . '00',
				);
			}
			$i = 0;
			foreach( $timeLayout->{'end-valid-time'} as $key => $endValidTime ) {
				$timeLayouts[(string)$timeLayout->{'layout-key'}[0]][$i]['end-valid-time'] =  (string)$endValidTime[0];
				$timeLayouts[(string)$timeLayout->{'layout-key'}[0]][$i]['end-valid-time-calculated'] = date( 'Ymd', strtotime((string)$endValidTime[0])) .
						date( 'H', strtotime((string)$endValidTime[0])) . '00';
			}

		}
		// Loop through all the data/parameter elements
		$dwmlParsed = array();
		foreach( $dwmlSimpleXml->data->parameters->children() as $parameterName => $parameters ) {
			$key = $parameterName . ( $parameters->attributes()->type ? '-' . $parameters->attributes()->type : '' );
			$timeLayout = (string)$parameters->attributes()->{'time-layout'};
			if(in_array($key,array_keys($this->dwmlElements))) {
				if($key == 'conditions-icon-forecast-NWS') {
					$parameterName = 'icon-link';
					$attributeName = NULL;
				} elseif($key == 'weather') {
					$parameterName = 'weather-conditions';
					$attributeName = 'weather-summary';
				} elseif($key == 'wordedForecast') {
					$parameterName = 'text';
					$attributeName = NULL;
				} else {
					$parameterName = 'value';
					$attributeName = NULL;
				}
				$i = 0;
				foreach( $parameters->{$parameterName} as $values ) {
					$start_valid_time = $timeLayouts[$timeLayout][$i]['start-valid-time-calculated'];
					$dwmlParsed[$start_valid_time]['start_valid_time'] = $timeLayouts[$timeLayout][$i]['start-valid-time'];
					if($attributeName) {
						$dwmlParsed[$start_valid_time][$this->dwmlElements[$key]] = 
							(string)$values->attributes()->{$attributeName} ? (string)$values->attributes()->{$attributeName} : '';
					} else {
						$dwmlParsed[$start_valid_time][$this->dwmlElements[$key]] = 
							(string)$values[0] ? (string)$values[0] : '';
					}
					if(in_array('probability-of-precipitation-calculated',array_keys($this->dwmlElements))
					&& $key == 'conditions-icon-forecast-NWS' ) {
						$dwmlParsed[$start_valid_time][$this->dwmlElements['probability-of-precipitation-calculated']] = (int) preg_replace( "/[^0-9]/", '', $dwmlParsed[$start_valid_time][$this->dwmlElements[$key]] );
					}
					if(in_array('start-valid-time-calculated',array_keys($this->dwmlElements))) {
						$dwmlParsed[$start_valid_time][$this->dwmlElements['start-valid-time-calculated']] = $timeLayouts[$timeLayout][$i]['start-valid-time-calculated'];
					}
					$i++;
				}
			}
		}
		// Post process parsed data
		ksort( $dwmlParsed );
		$dwmlParsed = array_values($dwmlParsed);
		foreach($dwmlParsed as $rowKey => $row){
			foreach($this->dwmlElements as $column)
				if( ! isset( $row[$column] )) $dwmlParsed[$rowKey][$column] = null;
		}
		return $dwmlParsed;
	}
}
