<?php
/**
 * NoaaCacheBehavior class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Caches downloaded data to improve application performance and reduce load 
 * on the NOAA servers. By default the extension will create a new CFileCache
 * repository in its own cache subdirectory. This path must be writeable by the
 * webserver. The cache path can be changed by adding keys to the application-
 * level parameters array in the main web configuration file 
 * (main.php), for exmample:
 * 
 * // application-level parameters that can be accessed
 * // using Yii::app()->params['paramName']
 * // Change noaaWeather.cachePath from the default below
 * // Set to null to use the application file cache path (protected/runtime/cache)
 * 'params'=>array(
 * 	'noaaWeather.cachePath' => 'protected/extensions/noaaWeather/cache'
 * );
 *  
 */
class NoaaCacheBehavior extends CBehavior {

	/**
	* @var string path to store cache files
	* Do not change this file, instead add the 'noaaWeather.cachePath' key to the
	* application-level parameters array in Yii's main web application configuration
	* file (main.php)
	*/
	public $cachePath='protected/extensions/noaaWeather/cache';

	/**
	* @var object the CFileCache object
	*/
	public $cacheObject;

	/**
	* @var the cache id to use when getting and setting cache objects
	*/
	public $cacheId;
	
	/**
	* @var the timeout to use when caching data, by default one hour per noaa policy
	*/
	public $timeout = 3600;

	/**
	* Returns a Yii CFileCache obect 
	*/
	public function getCacheObject(){
		if(!is_object($this->cacheObject)){
			if(isset(Yii::app()->params['noaaWeather.cachePath'])){
				$this->cachePath = Yii::app()->params['noaaWeather.cachePath'];
			}
			$this->cacheObject=new CFileCache();
			$this->cacheObject->cachePath = $this->cachePath;
			$this->cacheObject->init();
		}
		return $this->cacheObject;
	}
	
	/**
	* Caches data
	*/
	public function setCache($data) {
		return $this->getCacheObject()->set($this->cacheId,$data,$this->timeout);
	}

	/**
	* Returns cached data
	*/
	public function getCache() {
		return $this->getCacheObject()->get($this->cacheId);
	}
}
