<?php
// Set defaults
$weather = isset($weather) ? $weather : false;
$numDays = isset($numDays) ? $numDays : 6;
$dayOffset = isset($dayOffset) ? $dayOffset : 0;
$errorMsg = isset($errorMsg) ? $errorMsg : 'Error fetching forecast.';

// If weather array is not set there is an error
if(@is_array($weather['forecast'])) {
	// Prepare the forecast array to work with an offset
	$weather['forecast'] = array_values($weather['forecast']);
	$i=0;
	while($i<$numDays){
		$offset = $i + $dayOffset;
		// If pm is not set it is an incomplete day or not present in forecast
		if(isset($weather['forecast'][$offset]['mapClick']['pm']['start_valid_time'])) {
			// populate $tabKey which is displayed in the tab 
			$tabKey = '<div class="noaaForecastAllTabWrapper">';
			$day = date('l', strtotime($weather['forecast'][$offset]['mapClick']['pm']['start_valid_time']));
			$today = $day == date('l') ? true : false;
			$tabKey .= '<div class="noaaForecastAllTabHeader">'.$day.'</div>'; 
			if( isset($weather['forecast'][$offset]['mapClick']['am']['start_valid_time']) ) {
				$tabKey .= '<img class="noaaForecastAllTabIcon" src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['forecast'][$offset]['mapClick']['am']['conditions_icon_forecast_NWS']) . '" /><br />';
				$tabKey .= '<div class="noaaForecastAllTabFooter">' . $weather['forecast'][$offset]['mapClick']['am']['temperature_maximum']  . '&deg; | ';
				$tabKey .= $weather['forecast'][$offset]['mapClick']['pm']['temperature_minimum']  . '&deg;</div>';
			} else {
				$tabKey .= '<img class="noaaForecastAllTabIcon" src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['forecast'][$offset]['mapClick']['pm']['conditions_icon_forecast_NWS']) . '" /><br />';
				$tabKey .= '<div class="noaaForecastAllTabFooter">Low: ' . $weather['forecast'][$offset]['mapClick']['pm']['temperature_minimum']  . '&deg;</div>';
			}
			$tabKey .= '</div>';
			// populate $content which is displayed as the content
			$content =  '<table>';
			$content .=  '<tr><td colspan="2" class="noaaForecastAllContentHeader">';
			$content .=  $day;
			$content .=  '</td></tr><tr>';
			// populate the summary section from the current observations and mapclick data
			foreach( array('am','pm') as $meridian ) {
				// The am forecast meridian offset is not set in the evening, in this case
				// current observation data is used
				if(isset($weather['forecast'][$offset]['mapClick'][$meridian])){
					$content .= '<td class="noaaForecastAllSummaryWrapper">';
					$content .= '<table><tr><td collspan="2" class="noaaForecastAllSummaryHeader">';
					$content .= ($meridian=='am' ? 'Day' : ($today?'Tonight':'Night'));
					$content .= '</td></tr><tr><td class="noaaForecastAllSummaryIcon">';
					$content .= '<img src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['forecast'][$offset]['mapClick'][$meridian]['conditions_icon_forecast_NWS']) . '" />';
					$content .= '</td><td class="noaaForecastAllSummaryText">';
					$content .= $weather['forecast'][$offset]['mapClick'][$meridian]['worded_forecast'];
					$content .= '</td></tr></table></td>';
				// No am mapclick data, current observation data is used instead
				} elseif($meridian=='am' && $today) {
					$content .= '<td class="noaaForecastAllSummaryWrapper">';
					$content .= '<table><tr><td collspan="2" class="noaaForecastAllSummaryHeader">';
					$content .= 'Current';
					$content .= '</td></tr><tr>';
					// Sometimes the current observations icon is not set
					if(isset($weather['currentObservations']['icon_url_name'])){
						$content .= '<td class="noaaForecastAllSummaryIcon">';
						$content .= '<img src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['currentObservations']['icon_url_name']) . '" />';
						$content .= '</td>';
						$content .= '<td class="noaaForecastAllSummaryText">';
					} else {
						$content .= '<td class="noaaForecastAllSummaryText" colspan="2">';
					}
					$content .= isset($weather['currentObservations']['weather']) ? $weather['currentObservations']['weather'] . '<br />' : '';
					$content .= isset($weather['currentObservations']['temp_f']) ? $weather['currentObservations']['temp_f'] . '&deg;<br />' : '';
					$content .= $weather['currentObservations']['wind_mph'] > 0 ? $weather['currentObservations']['wind_dir'] . ' wind ' 
						. $weather['currentObservations']['wind_mph'] . ' mph.' . '<br />' : 'No Wind';
					$content .= '</td></tr></table></td>';
				}
			}
			// populate the detail table from the detailed forecast data
			$content .=  '</tr><tr><td class="noaaForecastAllDetailWrapper" colspan="2">';
			$content .= '<table>';
			$content .= '<tr ><th class="noaaForecastAllDetailHeader">&nbsp;</th><th class="noaaForecastAllDetailHeader">Morning</th><th class="noaaForecastAllDetailHeader">Day</th><th class="noaaForecastAllDetailHeader">Evening</th><th class="noaaForecastAllDetailHeader">Night</th></tr>';
			foreach( array('temperature_hourly' => 'Temp',
				'probability_of_precipitation_calculated' => 'Precip',
				'cloud_amount_total' => 'Cloud',
				'wind_speed_sustained' => 'Wind',
				) as $tableKey => $tableValue ) {
				$content .= '<tr><th class="noaaForecastAllDetailHeader">' . $tableValue . '</th>';
				foreach(array('morning','day','evening','night') as  $period) {
					$content .= '<td class="noaaForecastAllDetailBody">';
					if(isset($weather['forecast'][$offset]['detail'][$period][$tableKey])) {
						if($tableKey == 'wind_speed_sustained') {
							$content .= $weather['forecast'][$offset]['detail'][$period][$tableKey] ? $weather['forecast'][$offset]['detail'][$period][$tableKey] : '0';
							if( $weather['forecast'][$offset]['detail'][$period]['wind_speed_gust'] > 10 || 
								$weather['forecast'][$offset]['detail'][$period]['wind_speed_gust'] 
									> ($weather['forecast'][$offset]['detail'][$period]['wind_speed_sustained'] + 5)) {
								$content .= '-' . $weather['forecast'][$offset]['detail'][$period]['wind_speed_gust'];
							}
						} elseif($tableKey == 'temperature_hourly') {
							$content .= $weather['forecast'][$offset]['detail'][$period][$tableKey] . '&deg;';
						} else {
							$content .= $weather['forecast'][$offset]['detail'][$period][$tableKey] ?  $weather['forecast'][$offset]['detail'][$period][$tableKey]. '%' : '0%';
						}
					} else {
						$content .= '-';
					}
					$content .= '</td>';
				}
				$content .= '</tr>';
			}
			$content .= '</table>';
			$content .=  '</td></tr></table>';
			$tabs[$tabKey] = array('content' => $content,'id'=>$day);
		}
		$i++;
	}
	// Display the tab widget
	$this->widget('zii.widgets.jui.CJuiTabs', array('tabs'=>$tabs,'htmlOptions'=>array('class'=>'noaaForecastAllJuiTabs'),'headerTemplate'=>'<li><a href="{url}" title="">{title}</a></li>'));
} else {
	// No weather array, display the error message
	echo $errorMsg;
}
