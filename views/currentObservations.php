<?php
// Set Defaults
$weather = isset($weather) ? $weather : false;
$errorMsg = isset($errorMsg) ? $errorMsg : 'Error fetching current conditions.';
$title = isset($title) ? $title : 'Current Conditions';

// Start Portlet Widget
$this->beginWidget('zii.widgets.CPortlet',array('title'=>$title,'htmlOptions'=>array('class'=>'noaaCurrentObservationsPortlet')));
// If weather array is not set there is an error
if(@is_array($weather[0])) {
	echo isset($weather[0]['icon_url_name']) ? '<img class="noaaCurrentObservationsIcon" align="left" src="' . $this->getAssetUrl() . '/nwsIcons/' . $weather[0]['icon_url_name'] . '" />' : '';
	echo isset($weather[0]['icon_url_name']) ? '<div class="noaaCurrentObservationsBody">' : '<div>';
	echo isset($weather[0]['weather']) ? '<b>' . $weather[0]['weather'] . '</b><br />' : '';
	echo isset($weather[0]['temp_f']) ? $weather[0]['temp_f'] . '&deg;<br />' : '';
	echo isset($weather[0]['wind_mph']) ? 'Wind: ' . $weather[0]['wind_mph'] . ' MPH ' : '';
	echo (isset($weather[0]['wind_dir']) ? $weather[0]['wind_dir'] : '' ) . '<br />';
	echo isset($weather[0]['relative_humidity']) ? 'Relative Humidity: ' . $weather[0]['relative_humidity'] . '%<br />' : '';
	echo '</div><br />';
	echo isset($weather[0]['location']) ? '<div class="noaaCurrentObservationsFooter">Location: ' . $weather[0]['location'] . '<br />' : '';
	echo (isset($weather[0]['observation_time']) ? $weather[0]['observation_time'] : '') . '</div>';
} else {
	// No weather array, display the error message
	echo $errorMsg;
}
$this->endWidget();
