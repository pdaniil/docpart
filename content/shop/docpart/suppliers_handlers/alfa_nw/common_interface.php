<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class alfa_nw_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
	
		$login = $storage_options['login'];
		$password = $storage_options['password'];
		
		$supplier_items = array();
		
		$base64_str = base64_encode($login . ":" . $password);
		
		$url_request = "http://alfa-nw.ru/api/v1/search/{$article}";
		
		$headers = array(
			"Authorization: Basic {$base64_str}"
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $url_request);
		
		$exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение ID товаров по артикулу ".$article, $url_request."<br>Авторизация через заголовок: base64_encode(".$login.":".$password.")", $exec, print_r(json_decode($exec, true), true) );
		}
		
		
		
		if ( ! $exec) {
			
			return false;
			
		}
		
		$search_result = json_decode($exec, true);
		
		foreach ($search_result as $product) 
		{
			
			$product_id = $product['product_id'];
			
			$url_request = "http://alfa-nw.ru/api/v1/spareparts/{$product_id}";
			curl_setopt($ch, CURLOPT_URL, $url_request);
			
			$exec = curl_exec($ch);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара: ".$product_id, $url_request."<br>CURL-сессия продолжается", $exec, print_r(json_decode($exec, true), true) );
			}
			
			
			if ( ! $exec) {
			
				return false;
			
			}
			
			$supplier_items = json_decode($exec, true);
			
			foreach ($supplier_items as $item) {
				
				$price_purchase = $item['price'];
				
				 //Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				 $markup = $storage_options["markups"][(int)$price_purchase];
				 
				if($markup == NULL) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer 		= $price_purchase + $price_purchase * $markup;
				
				$manufacturer 				= $item['supplier'];
				$article 					= $item['articul'];
				$name 						= $item['name'];
				$exist 					= $item['quantity'];
				$time_to_exe  				= $item['delaydays'];
				$time_to_exe_guaranteed 	= $item['gtddays'];
				$storage 					= 0;
				$min_order 				= $item['minbatch'];
				$probability 				= $item['delivery_percent'];
				$product_type 				= 2;
				$product_id 				= 0;
				$storage_record_id		= 0;
				$url 						= "";
				$json_params 				= '';
				$rest_params  				= array("rate"=>$storage_options["rate"]);
				
				if ($exist == -1) {
					
					$exist = 50;
					
				}
				
				$DocpartProduct = new DocpartProduct($manufacturer, 
					$article,
					$name,
					$exist,
					$price_for_customer,
					$time_to_exe,
					$time_to_exe_guaranteed,
					$storage,
					$min_order,
					$probability,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price_purchase,
					$markup,
					$product_type,
					$product_id,
					$storage_record_id,
					$url,
					$json_params,
					$rest_params
				);
				
				if($DocpartProduct->valid) {
					
					array_push($this->Products, $DocpartProduct);
					
				}
			
			}//~foreach ($supplier_items as $item)
			
		}//~foreach ($search_result as $product)
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		curl_close($ch);
		
	}
	
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();




$ob =  new alfa_nw_enclosure($_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit( json_encode($ob) );
?>