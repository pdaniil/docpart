<?php

class PartSoftApi
{
	
	private $options; //Настройки API
	private $action; //Действие
	private $params_action; //Параметры для действия
	private $response; //Ответ API
	private $c_options; //Опции для curl
	
	public function __construct( array $options ) {
		
		$this->options = $options;
		$this->params_action = array();
		
	}
	
	public function setAction ( $action ) {
		
		$this->action = $action;
		
	}
	
	public function setParamsAction( array $params ) {
		
		$this->params_action = $params;
		
	}
	
	public function getResponse() {
		
		return $this->response;
		
	}
	
	public function exec( $post = false ) {
		
		$req_data = $this->buildRequest();
		
		$ch = curl_init(); 
		$c_options = array();
		
		$c_options[CURLOPT_URL] = $req_data['url'];
		$c_options[CURLOPT_HEADER] = false;
		$c_options[CURLOPT_RETURNTRANSFER] = true;

		$c_options[CURLOPT_SSL_VERIFYPEER] = false;
		$c_options[CURLOPT_SSL_VERIFYHOST] = false;
		$c_options[CURLOPT_FOLLOWLOCATION] = true;
		
		if ( $post == true ) {
			
			$c_options[CURLOPT_POST] = true;
			$c_options[CURLOPT_POSTFIELDS] = $req_data['params'];
			
		}
		else {
			
			$c_options[CURLOPT_URL] .= "?" . $req_data['params'];
			
		}

		if ( isset ( $req_data['custom_request'] ) ) {
			
			$c_options[CURLOPT_CUSTOMREQUEST] = $req_data['custom_request'];
			
		}
		
		curl_setopt_array( $ch, $c_options );
		
		$exec = curl_exec( $ch );
		
		if ( $exec == ''
			|| curl_errno( $ch ) > 0
		) {
			$error_message = "Ошибка: пустой ответ; curl_errno: " .  curl_errno( $ch );
			throw new Exception( $error_message );
			
		}
		
		curl_close( $ch );
		
		$this->response = $exec;
		
	}
	
	private function buildRequest() {
		
		$request_params = $this->params_action;
		$request = array();
		
		if ( ! isset( $request_params['api_key'] ) ) {
			
			$request_params['api_key'] = $this->options['api_key'];
			
		}
		
		if ( isset( $request_params['custom_request'] ) ) {
			
			$request['custom_request'] = $request_params['custom_request'];
			unset( $request_params['custom_request'] );
			
		}

		$build = http_build_query( $request_params );
		$req = $this->options['base'] . $this->action;
		
		$request['url'] = $req;
		$request['params'] = $build;
		
		return $request;

		
	} 
	
	
}
?>