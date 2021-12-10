<?php

class OptipartApi
{
	
	private $connection_options;
	private $answer;
	
	public function __construct( array $connection_options ) {
		
		$this->connection_options = $connection_options;
		$this->connection_options['service'] = "http://optipart.ru/";
		
	}
	
	public function getBrands( $article ) {
	// http://optipart.ru/clientapi/?apikey=29a435d9-bbba-4094-b931-67ab881c3702&action=lookup&number=701n&tecdoc=1
		
		$params_action = array (
			'action' => 'lookup',
			'number' => $article,
			'tecdoc' => $this->connection_options['tecdoc']
		);
		
		$action = 'clientapi/';
		
		$this->exec( $action, $params_action );
		
		return $this->getDom();
		
	}
	
	public function getItems( $article, $brand ) {	
	// http://optipart.ru/clientapi/?apikey=29a435d9-bbba-4094-b931-67ab881c3702&action=offers&number=701n&brand=ub
		
		$params_action = array (
			'action' => 'offers',
			'number' => $article,
			'brand' => $brand
		);
		
		$action = 'clientapi/';
		
		$this->exec( $action, $params_action );
		
		return $this->getDom();
		
	}
	
	public function getAnswer(){
		
		return $this->answer;
		
	}
	
	public function getDom() {
		
		$answer = $this->getAnswer();
		
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->loadXml( $answer );
		
		$result_el = $dom->getElementsByTagName( 'result' )->item(0);
		
		if ( is_null( $result_el ) )  {
			
			throw new Exception( 'Неизвестный результат' );
			
		}
		
		$attributes = $result_el->attributes;
		
		// Logger::addLog( '$result_el', $result_el );
		
		foreach ( $attributes as $attr ) {
			
			// Logger::addLog( '$attr', $attr );
			
			if ( $attr->name == 'type' ) {
				
				if ( $attr->value == 'ok' ) {
					
					return $dom;
					
				}
				
			}
		}
		
	}
	
	private function exec( $action, array $params_action, $post = false ) {
		
		
		$service = $this->connection_options['service'];
		$params_action['apikey'] = $this->connection_options['apikey'];
		
		$params_action_str = http_build_query( $params_action );
		
		$url_request = $service . $action;
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		if ( $post ) {
			
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params_action_str );
			
		}
		else {
			
			$url_request .= "?" . $params_action_str;
			
		}
		
		curl_setopt( $ch, CURLOPT_URL, $url_request );
		
		$exec = curl_exec( $ch );
		
		if ( curl_errno( $ch ) ) {
			
			throw new Exception( 'Ошибка curl: ' . curl_errno( $ch ) );
			
		}
		
		curl_close( $ch );
		$this->answer = $exec;
		// Logger::addLog( '$url_request', $url_request );
		// Logger::addLog( '$params_action', $params_action );
		// Logger::addLog( '$exec', $exec );
		
		
		
	}
}

?>