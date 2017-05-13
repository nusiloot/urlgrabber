<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class UrlGrabber
{
	const T_ASSETS_EXTENSIONS = [ 'js', 'css', 'woff', 'woff2', 'png', 'ico', 'gif', 'jpg', 'jpeg', 'txt', 'pdf', 'xml' ];
	
	const T_USER_AGENT = [
		'Mozilla/5.0 (X11; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0 Iceweasel/31.7.0',
		'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
		'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
		'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
		'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9',
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0',
	];
	const N_USER_AGENT = 7;

	private $target = '';
	
	private $tor = false;
	private $malicious = false;
	private $assets = true;
	
	private $t_urls = [];

	private $t_run = [];
	private $t_sources = [];
	
	
	public function getTarget() {
		return $this->target;
	}
	public function setTarget( $v ) {
		return $this->target = trim( $v );
	}

	
	public function enableTor() {
		$this->tor = true;
	}
	
	
	public function enableMaliciousSearch() {
		$this->malicious = true;
	}
	
	
	public function excludeAssets() {
		$this->assets = false;
	}
	
	
	private function testSource( $s ) {
		/*if( !is_subclass_of($s,'ThirdParty') ) {
			return false;
		}*/
		if( !method_exists($s,'run') ) {
			return false;
		}
		if( property_exists($s,'SOURCE_NAME') ) {
			return false;
		}
		return true;
	}
	public function registerSource( $index, $v ) {
		$v = trim( $v );
		$file = dirname(__FILE__).'/'.$v.'.php';
		if( !is_file($file) || !class_exists($v) ) {
			Utils::help( $v.' class not found' );
		}
		if( !$this->testSource($v) ) {
			Utils::help( $v.' class wrongly configured' );
		}
		$this->t_sources[ $index ] = $v;
		//ksort( $this->t_sources );
		$this->t_run[] = $v;
		return true;;
	}
	public function setSource( $v ) {
		$tmp = explode( ',', $v );
		$this->t_run = [];
		foreach( $tmp as $s ) {
			if( !isset($this->t_sources[$s]) ) {
				Utils::help( $s.' source not found' );
			}
			$this->t_run[] = $this->t_sources[ $s ];
		}
	}
	
	
	public function run()
	{
		foreach( $this->t_run as $s ) {
			$class = $s;
			echo "Testing ".$class::SOURCE_NAME."...\n";
			$t_urls = $class::run( $this->target, $this->tor, $this->malicious );
			$this->t_urls = array_merge( $this->t_urls, $t_urls );
			echo count( $t_urls )." urls found.\n";
			echo "\n";
		}
		
		$this->t_urls = array_unique( $this->t_urls );
		
		if( !$this->assets ) {
			$this->removeAssets();
		}
	}
	
	
	public function removeAssets()
	{
		foreach( $this->t_urls as $k=>$u ) {
			$parse = parse_url( $u );
			//var_dump( $parse['path'] );
			if( strstr($parse['path'],'.') ) {
				$ext = substr( $parse['path'], strrpos($parse['path'],'.')+1 );
				//var_dump($ext);
				if( in_array($ext,self::T_ASSETS_EXTENSIONS) ) {
					unset( $this->t_urls[$k] );
				}
			}
		}
	}
	
	
	public function printUrls()
	{
		echo implode( "\n", $this->t_urls )."\n";
	}
}
