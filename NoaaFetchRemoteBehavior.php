<?php
/**
 * NoaaFetchRemoteBehavior class file.
 *
 * @author Mark Scarbrough <markscarbrough@gmail.com>
 * @license http://www.yiiframework.com/license/
 */
 
 /**
 * Fetches data from the NOAA servers using the HTTP GET method.
 * Failed downloads are retried using an exponential backoff method.
 */
class NoaaFetchRemoteBehavior extends CBehavior {
	
	/**
	* @var the Url to fetch
	*/
	public $remoteUrl = null;

	/**
	* Fetches from the remote server and returns it
	*/
	public function fetchRemote($remoteUrl=null) {
		$return = null;
		if(!$remoteUrl) $remoteUrl = $this->remoteUrl;
		$i = 0;
		while( $i < 3 ) {
			$context = stream_context_create(array(
				'http' => array(
					'method'  => 'GET',
					'timeout' => round(10 / ($i + 2)),
					),
				));
			if( $return = @file_get_contents($remoteUrl, false, $context )) { 
				return $return;
			} else {
				$error = 'Error fetching remote url: ' . $remoteUrl . '(' . ($i + 1) . ' tries)';
				Yii::log($error, 'warning', 'ext.noaaWeather.noaaFectchRemoteBehavior');
				$i++;
			}
		}
		throw new Exception('Failed to fetch remote url: ' . $remoteUrl);		
	}
}
