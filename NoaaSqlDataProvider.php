<?php
/**
 * NoaaSqlDataProvider class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */

/**
 * NoaaSqlDataProvider provides the extension with access to the YII database
 * system. Database access is required to lookup location data. The extension
 * comes preconfigured to use an included sqlite database. If your server 
 * supports sqlite this will probably work with no further configuration. 
 *
 * If you need to use another database, import the appropriate sql files found
 * in the data subdirectory, then configure your database connection by adding
 * the 'noaaWeather.dbConfig' key to the application-level parameters array in 
 * Yii's main web application configuration file (main.php), for example:
 * 
 * // application-level parameters that can be accessed
 * // using Yii::app()->params['paramName']
 * // Change noaaWeather.dbConfig from the default below
 * // Set this parameter to null to use the application database connection 
 * 'params'=>array(
 *		'noaaWeather.dbConfig' => array(
 * 		'dsn' => 'sqlite:protected/extensions/noaaWeather/data/noaa_weather.db',
 * 		'username' => '',
 * 		'password' => '',
 * 	),
 * );
 */
class NoaaSqlDataProvider extends CSqlDataProvider {

	/**
	* @var array configuration for access to the noaa_weather database
	* Do not change this file, instead add the 'noaaWeather.dbConfig' key to the
	* application-level parameters array in Yii's main web application configuration
	* file (main.php)
	*/
	public $dbConfig = array(
		'dsn' => 'sqlite:protected/extensions/noaaWeather/data/noaa_weather.db',
		'username' => '',
		'password' => '',
		);
	
	/**
	 * Set $this->db from configuration or defaults
	 */
	public function __construct($sql,$config=array()) {
		if(isset(Yii::app()->params['noaaWeather.dbConfig'])) {
			$this->dbConfig = Yii::app()->params['noaaWeather.dbConfig'];
		}
		if($this->dbConfig){
			$this->db=new CDbConnection($this->dbConfig['dsn'],$this->dbConfig['username'],$this->dbConfig['password']);
		}
		$config = array_merge(array('pagination'=>false,'sort'=>false),$config);
		parent::__construct($sql,$config);
	}
}

