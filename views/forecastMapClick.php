<?php
// Set defaults
$weather = isset($weather) ? $weather : false;
$numDays = isset($numDays) ? $numDays : 5;
$dayOffset = isset($dayOffset) ? $dayOffset : 0;
$errorMsg = isset($errorMsg) ? $errorMsg : 'Error fetching forecast.';
$title = isset($title) ? $title : $numDays . ' Day Forecast';

// Start Portlet Widget
$this->beginWidget('zii.widgets.CPortlet',array('title'=> $title,'htmlOptions'=>array('class'=>'noaaForecastMapClickPortlet')));
// If weather array is not set there is an error
if(@is_array($weather)) {	
	echo '<center>';
	echo '<table class="noaaForecastMapClickWrapper">';
	// Prepare the forecast array to work with an offset
	$weather['forecast'] = array_values($weather['forecast']);
	$i=0;
	while($i<$numDays){
		$offset = $i + $dayOffset;
		// If pm is not set it is an incomplete day or not present in forecast
		if(isset($weather['forecast'][$offset]['mapClick']['pm'])) {
			// If am is not set pm must fill the row
			$colspan = ! isset($weather['forecast'][$offset]['mapClick']['am']) ? 1 : 2;
			echo '<td class="noaaForecastMapClickWrapper">';
			echo '<table class="noaaForecastMapClickInner" style="width:75px;">';
			echo '<tr><td colspan="' . $colspan . '" class="noaaForecastMapClickInnerHeader"> ';
			$title = date( 'l', strtotime($weather['forecast'][$offset]['mapClick']['pm']['start_valid_time']));
			echo '<b>' . $title . '</b></td></tr><tr>';
			// If am is not set do not show the am icon
			if(isset($weather['forecast'][$offset]['mapClick']['am']['temperature_maximum'])) {
				echo '<td class="noaaForecastMapClickInnerData">';
				echo 'Hi: ' . $weather['forecast'][$offset]['mapClick']['am']['temperature_maximum'] . '&deg;<br />';
				echo '<img src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['forecast'][$offset]['mapClick']['am']['conditions_icon_forecast_NWS'])  . '" />';
				echo '</td><td>';
			} else {
				echo '<td class="noaaForecastMapClickInnerData" colspan="' . $colspan . '">';
			}
			echo 'Lo: ' . $weather['forecast'][$offset]['mapClick']['pm']['temperature_minimum'] . '&deg;<br />';
			echo '<img src="' . $this->getAssetUrl() . '/nwsIcons/' . basename($weather['forecast'][$offset]['mapClick']['pm']['conditions_icon_forecast_NWS']) . '" />';
			echo '</td>';
			echo '</tr><tr><td colspan="' . $colspan . '" class="noaaForecastMapClickInnerFooter">';
			// If am is not set show the pm forecast instead
			echo isset($weather['forecast'][$offset]['mapClick']['am']['weather']) ? $weather['forecast'][$offset]['mapClick']['am']['weather'] : $weather['forecast'][$offset]['mapClick']['pm']['weather'];
			echo '</td>';
			echo '</tr></table>';
			echo '</center></td>';
		}
		$i++;
	}
	echo '</table>';
} else {
	// No weather array, display the error message
	echo $errorMsg;
}
$this->endWidget();
