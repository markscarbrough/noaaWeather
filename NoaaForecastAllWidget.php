<?php
/**
 * NoaaForecastAllWidget class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaForecastAllWidget renders a widget based on the following data providers:
 * - NoaaForecastMapClickDataProvider
 * - NoaaForecastDetailDataProvider 
 * - NoaaCurrentObservationsDataProvider
 *
 * By default the widget uses the css, icons, and view file provided with the
 * noaaWeather extension to diplay detailed forecast data wrapped in a Yii 
 * CJuiTabs widget. All aspects of display can be overriden or customized.
 *
 * Example Usage:
 * 
 * $this->widget('ext.noaaWeather.NoaaForecastAllWidget',array(
 * 	'location' => array('coordinates'=>array(
 *	 		'latitude' => 44.27,
 *	 		'longitude' => -71.3,
 *	 		)
 *		)
 * ));
 *
 */
class NoaaForecastAllWidget extends CWidget
{
	/**
	* @var array the location array to pass to the noaaWeather DataProvider
	*/
	public $location = array();

	/**
	* @var string the name of the view file to render
	*/
	public $viewFile = 'forecastAll';

	/**
	* @var string the name of the css file to publish
	*/
	public $cssFile = 'noaaWeather.css';

	/**
	* @var string the asset URL to publish, defaults to ext/noaaWeather/assets
	*/
	public $assetUrl;

	/**
	* @var array the parameters to pass to the view file. The 'weather' parameter
	* is populated automatically with merged information from multiple 
	* data providers. Parameters supported by the default view are:
	* - $numDays: int the number of days to show forcast for (default 6)
	* - $dayOffset: int the first dayNum to display (default 0)
	* - $errorMsg: string the message to display if weather can't be fetched
	*/
	public $params=array();

	/**
	* Renders the widget
	*/
	public function run() {
		try {
			$weather=array();
			$detailDataProvider = Yii::createComponent('ext.noaaWeather.NoaaForecastDetailDataProvider',$this->location);
			$weatherDetail = $detailDataProvider->getDataFormatted();
			$mapClickDataProvider = Yii::createComponent('ext.noaaWeather.NoaaForecastMapClickDataProvider',$this->location);
			$weatherMapClick = $mapClickDataProvider->getDataFormatted();
			$currentDataProvider = Yii::createComponent('ext.noaaWeather.NoaaCurrentObservationsDataProvider',$this->location);
			$weatherCurrent = $currentDataProvider->getDataFormatted();
			$weather = array_merge_recursive($weatherMapClick, $weatherDetail, $weatherCurrent);
			$this->params['weather'] = $weather;
		} catch(Exception $e) {
			$this->params['weather'] = false;
			Yii::log($e, 'warning', 'ext.noaaWeather.noaaForecastDetailWidget');
		}
		Yii::app()->clientScript->registerCssFile($this->getAssetUrl() . '/' . $this->cssFile);
		$this->render($this->viewFile,$this->params);
	}
	
	/**
	* Returns the published assetUrl
	*/
	public function getAssetUrl() {
		if( ! $this->assetUrl ) {
			$dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
			$this->assetUrl = Yii::app()->getAssetManager()->publish($dir);
		}
		return $this->assetUrl;
	}		
}
