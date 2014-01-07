<?php
/**
 * NoaaCacheBehavior class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Caches downloaded data to improve application performance and reduce load 
 * on the NOAA servers. By default the extension will use the CFileCache
 * repository under protected/runtime/cache. This path must be writeable by the
 * webserver. 
 */
class NoaaCacheBehavior extends CBehavior {

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
			$this->cacheObject=new CFileCache();
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
