<?php
class ThePartsWS
{
	public $timeout = 30;
	private $login;
	private $pass;
	const URL = 'http://the-parts.ru/cabinet/ws/ws.json.php';
	private $useGZIP = true;
	private $lastRequest = '';
	private $lastResponse = '';

	public function __construct( $loginOrToken, $pass = '' )
	{
		$this->login = $loginOrToken;
		$this->pass = $pass;
		if( !function_exists( 'gzdecode' ) ) $this->useGZIP = false;
	}

	function __call( $method, $args = array() )
	{
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, static::URL . '?' . time() );
		curl_setopt( $curl, CURLOPT_TIMEOUT, $this->timeout );
		curl_setopt( $curl, CURLOPT_MAXREDIRS, 3 );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $this->lastRequest = array( 
			'user' => $this->login, 
			'password' => $this->pass, 
			'request' => json_encode( array( 
				'action' => $method, 
				'params' => $args 
			) ), 
			'compress' => $this->useGZIP ? 'gzip' : false 
		) );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		if( defined( CURLOPT_ENCODING ) ) curl_setopt( $curl, CURLOPT_ENCODING, 'identity' );
		curl_setopt( $curl, CURLOPT_USERAGENT, __CLASS__ . '/' . $this->login );
		$this->lastResponse = '';
		$httpData = curl_exec( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
		curl_close( $curl );
		if( 200 != $status ) throw new Exception( 'Remote server says HTTP ' . $status );
		if( !strlen( $httpData ) ) throw new Exception( 'Remote server says nothing' );
		if( $this->useGZIP )
		{
			$httpData = @gzdecode( $gz = $httpData );
			if( false === $httpData ) var_dump( $gz );
			if( false === $httpData ) throw new Exception( 'Remote server GZIP invalid: ' . var_export( $gz, true ) );
		}
		$result = json_decode( $httpData, true );
		if( function_exists( 'json_last_error' ) ) if( json_last_error() != JSON_ERROR_NONE ) throw new Exception( 'json_decode failed: ' . (function_exists( 'json_last_error_msg' ) ? json_last_error_msg() : json_last_error()) );
		return $this->lastResponse = $result;
	}

	function getLastRequest()
	{
		return $this->lastRequest;
	}

	function getLastResponse()
	{
		return $this->lastResponse;
	}
}
?>