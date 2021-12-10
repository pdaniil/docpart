<?php

class PlanetavtoApi
{
	private $service = "api.planetavto.ru";
	private $action = "";
	private $params_action = array();

	
	private $options = array();
	
	public function __construct( array $options ) {
		
		$this->options = $options;
		
	}
	
	//Получение списка брендов
	public function getBrands() {
		
		$action = $this->getAction();
		
		$params_action = $this->getParamsAction();
		
		return $this->execute( $action, $params_action, true );
		
	}
	//Получение проценки
	public function getSupplierItems() {
		
		$action = $this->getAction();
		
		$params_action = $this->getParamsAction();
		
		return $this->execute( $action, $params_action, true );
		
	}
	//Получение списка доступных поставщиков
	public function getSuppliers() {
		
		$action = $this->getAction();
		$params_action = $this->getParamsAction();
		
		return $this->execute( $action, $params_action );
		
	}
	//Установка действия API
	public function getAction( $action = null ) {
		
		if ( is_null( $action ) ) {
			
			return $this->action;
			
		} else {
			
			$this->action = $action;
			
		}
		
	}
	//Установка и получение параметров для выполения действия
	public function getParamsAction( $params_action = null, $v = null ) {
		
		if ( is_null( $params_action ) ) {
			
			return $this->params_action;
			
		} else {
			
			if ( is_null( $v ) ) {
				
				$this->params_action = $params_action;
				
			} else {
				
				$this->params_action[$params_action] = $v;
				
			}
			
		}
		
	}
	//Выполнение запроса к поставщику
	private function execute ( $action, $params_action, $post = false ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$base_64_str = base64_encode( $this->options['login'] . ":" . $this->options['password'] );
		
		$headers = array(
			"Authorization: Basic {$base_64_str}"
		);
		
		$url_request = $this->service . $action;
		
		$params_action_str = http_build_query( $params_action );
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
		if ( $post ) 
		{
			
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params_action_str );
			
		} 
		else 
		{	
			$url_request .= "?" . $params_action_str;
		}
		
		curl_setopt($ch, CURLOPT_URL, $url_request);
		
		
		$exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$method_name = "Метод GET";
			if($post)
			{
				$method_name = "Метод POST";
			}
			
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос из библиотеки PlanetavtoApi.php", $url_request."<br>".$method_name."<br>Поля ".print_r($params_action,true), $exec, print_r(json_decode($exec, true), true) );
		}
		
		
		if(curl_errno($ch)) 
		{	
			throw new Exception("Ошибка curl: " . curl_errno($ch));
			curl_close($ch);
		}

		
		curl_close($ch);

		$decode = json_decode($exec, true);
		
		if ( ! $decode ) 
		{	
			throw new Exception("Ошибка json: " . json_last_error());	
		}
		
		return $decode;
		
	}
	
}

?>