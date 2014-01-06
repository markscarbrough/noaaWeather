<?php
/**
 * NoaaCurrentObservationsWidget class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaCurrentObservationsWidget renders a widget based on the 
 * NoaaCurrentObservationsDataProvider.
 *
 * By default the widget uses the css, icons, and view file provided with the
 * noaaWeather extension to diplay current observations wrapped in a Yii 
 * Portlet. All aspects of display can be overriden or customized.
 *
 * Example Usage:
 * 
 * $this->widget('ext.noaaWeather.NoaaCurrentObservationsWidget',array(
 * 	'location' => array('coordinates'=>array(
 *	 		'latitude' => 44.27,
 *	 		'longitude' => -71.3,
 *	 		)
 *		)
 * ));
 *
 */

class NoaaCurrentObservationsWidget extends CWidget
{
	/**
	* @var array the location array to pass to the noaaWeather DataProvider
	*/
	public $location = array();

	/**
	* @var string the name of the view file to render
	*/
	public $viewFile = 'currentObservations';

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
	* is populated automatically with data from the noaaCurrentObservationsDataProvider.
	* Parameters supported by the default view are:
	* - $title: string the title of the portlet widget
	* - $errorMsg: string the message to display if weather can't be fetched
	*/
	public $params=array();
	
	/**
	* Renders the widget
	*/
	public function run() {
		try{
			$dataProvider = Yii::createComponent('ext.noaaWeather.NoaaCurrentObservationsDataProvider',$this->location);
			$this->params['weather'] = $dataProvider->getData();
		} catch (Exception $e) {
			$this->params['weather'] = false;
			Yii::log($e, 'warning', 'ext.noaaWeather.noaaCurrentObservationsWidget');
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
