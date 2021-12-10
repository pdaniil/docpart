<?php

class CrimeaApi 
{

	private $api_key = '';
	private $api_url = 'https://crimea-drive.ru/api/';


	function __construct($api_key) 
	{
		$this->api_key = $api_key;
	}


	public function getApiUrl ( $method ) {
		return $this->api_url.$method.'/';
	}

	protected function post($method,$data=array())
	{
		$url = $this->getApiUrl($method);
		$ch = curl_init($url);
		$data['api_key']=$this->api_key;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		
		if ( curl_errno($ch) ) {
			
			throw new Exception( 'Ошибка curl: ' . curl_errno($ch) );
			
		}
		
		curl_close($ch);
		return $response;
	}

	public function Search( $article, $brand='', $show_analog=1)
	{
		$data=array(
			'article' => $article,
			'brand' => $brand,
			'show_analog' => $show_analog
		);
		$ret = $this->post('search', $data);
		return $ret;
	}

}