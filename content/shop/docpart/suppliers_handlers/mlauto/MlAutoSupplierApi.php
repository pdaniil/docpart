<?php

class MlAutoSupplierApi
{
	
	private $service,
			$action;
			
	private $options = array();
	private	$params_action = array();
	
	
	public function __construct( array $options ) {
		
		$this->service = "https://www.ml-auto.ru/";
		
		$this->options = $options;
		
	}
	
	public function getBrands() {
		
		$this->action = "webservice/ArticleSearch/";
		
		return $this->execAction( true );
		
	}
	
	public function getSupplierItems() {
		
		$this->action = "webservice/Search/";
		
		return $this->execAction( true );
		
	}
	
	public function getAction( $action = null ) {
		
		if ( is_null( $action ) ) {
			
			return $this->action;
			
		} else {
			
			$this->action = $action;
			
		}
		
	}
	
	public function getParamsAction ( $params = null, $v = null ) {
		
		if ( is_null( $v ) ) {
			
			$this->params_action = $params;
			
		} else if ( is_null( $params ) ) {
			
			return $this->params_action;
			
		} else {
			
			$this->params_action[$params] = $v;
			
		}
		
	}
	
	private function execAction( $post = false ) {
		
		$url_request = $this->service . $this->action;
		
		$params_action = $this->params_action;
		
		$params_action['LOGIN'] = $this->options['login'];
		$params_action['PASSWORD'] = $this->options['password'];
		
		$params_action_str = http_build_query( $params_action );
		
		$ch = curl_init();
		
		if ( $post == true ) {
			
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params_action_str );
			
		} else {
			
			$url_request .= "?" . $params_action_str;
			
		}
		
		curl_setopt( $ch, CURLOPT_URL, $url_request );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		
		$exec = $this->clearBOM( curl_exec( $ch ) );
				
		if ( ! $exec ) {
			
			throw new Exception( "Ошибка curl: " . curl_errno( $ch ) );
			
		}
		curl_close( $ch );
		
		$decode = json_decode( $exec, true );
		
		if ( json_last_error() ) {
			
			throw new Exception( "Ошибка json: " . json_last_error() );
			
		}
		
		return $decode;
		
	}
	
	private function clearBOM( $str ) {
		
		//Удаляем BOM из строки
		if ( substr($str, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf) ) {
			
			$str = substr( $str, 3 );
			
		}
		
		return $str;
	}
	
}

?>