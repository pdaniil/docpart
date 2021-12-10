<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class proliga_enclosure
{
	public $result = 0; 
	public $Products = array();
	public $order_params = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		$this->getOrderParams( $storage_options["api_key"], $storage_options["delivery_type"], $storage_options["payment_type"] );
		
		$this->getProducts( $article, $manufacturers, $storage_options );
	}
	
	public function getOrderParams( $secret, $delivery_type, $payment_type ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//Массив отправляемых данных.
		$post_data_get_params = array(
			"secret"=>$secret
		);
		
		$post_data = http_build_query( $post_data_get_params );
		
		$url_api = "https://pr-lg.ru";
		$uriGetParamsOrder = "/api/cart/params";
		
		$ch = curl_init();
		$url_request = $url_api . $uriGetParamsOrder;
			
		curl_setopt( $ch, CURLOPT_URL, $url_request );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		$execute = curl_exec( $ch );
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение параметров заказа", $url_request."<br>Метод POST<br>Поля ".print_r($post_data_get_params, true), htmlspecialchars($execute, ENT_QUOTES, "UTF-8"), htmlspecialchars(print_r(json_decode($execute, true), true), ENT_QUOTES, "UTF-8") );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		curl_close( $ch );
		
		if( ! $execute )
			throw new Exception( "Ошибка запроса!" . curl_error( $ch ) );
		
		$responce = json_decode( $execute, true );
		if( ! $responce )
			throw new Exception( "Ошибка разбора ответа от API! error: " . json_last_error() );
		
		$this->order_params["delivery_type"]	= $responce["methods"][$delivery_type]["id"];
		$this->order_params["payment_type"]		= $responce["payment"][$payment_type]["id"];
		$this->order_params["point_code"]		= $responce["points"][0]["code"];
		$this->order_params["adderss"]			= $responce["points"][0]["address"];
	}
	
	public function getProducts( $article, $manufacturers, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		$secret = $storage_options["api_key"];
		
		foreach ( $manufacturers as $m ) {
			
			$manufacturer = $m["manufacturer"];
			
			$ch = curl_init();
			
			$service = "https://pr-lg.ru/";
			$action = "api/search/crosses";
			
			$params_action = array();
			$params_action['secret'] = $secret;
			$params_action['article'] = $article;
			$params_action['brand'] = $manufacturer;
			$params_action['replaces'] = 1;
			
			$build = http_build_query( $params_action );
			
			$url = $service . $action . "?" . $build;
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			
			$execute = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer, $url, $execute, print_r(json_decode($execute, true), true) );
			}
			
			
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
				
				throw new Exception( "Ошибка CURL!" );
			}
			
			curl_close($ch);
			
			$decode = json_decode( $execute, true );
			
			if ( ! $decode ) {
				throw new Exception( "Ошибка JSON!" );
			}
			
			//Обрабатываем информацию по позиции
			foreach($decode as $part)
			{
				
				$art = $part["article"];
				$brand = $part["brand"];
				$storages_info = $part["products"];
				
				
				//По складским записям
				foreach($storages_info as $storage)
				{
					$price = $storage["price"];
					
					$delivery_time = $storage["delivery_time"];
					
					$delivery_time = $delivery_time/24; //Переводим в дни
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$json_data = array(
						"id" => $storage["article_id"],
						"warehouse" => $storage["warehouse_id"],
						"code" => $storage["product_code"],
						"comment" => "",
						"delivery_type" => $this->order_params["delivery_type"],
						"payment_type" => $this->order_params["payment_type"],
						"point_code" => $this->order_params["point_code"],
						"adderss" => $this->order_params["adderss"]
					);
					
					$json = json_encode( $json_data, JSON_UNESCAPED_UNICODE );
					
					
					$DocpartProduct = new DocpartProduct($brand,
						$art,
						$storage["description"],
						$storage["quantity"],
						$price + $price * $markup,
						$delivery_time + $storage_options["additional_time"],
						$delivery_time + $storage_options["additional_time"],
						0,
						$storage["multi"],
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2, //product_type
						0, //product_id
						0, //storage_record_id
						"", //url
						$json, //json_params
						array("rate"=>$storage_options["rate"])
					);
			
					if($DocpartProduct->valid)
					{
						array_push($this->Products, $DocpartProduct);
					}
				}
			}
			
			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
			}
			
		}
		
		$this->result = 1;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


try 
{
	$ob =  new proliga_enclosure( $_POST["article"], json_decode( $_POST["manufacturers"], true ), $storage_options );
	
	$ob->order_params = array();
}
catch( Exception $e ) 
{
	$ob->result = 0;
	$ob->Products = array();
	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit(json_encode($ob));
?>