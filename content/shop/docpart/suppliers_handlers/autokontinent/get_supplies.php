<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autokontinent_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{	
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = trim($storage_options['login']);
		$password = trim($storage_options['password']);
		
		$anwers = array();
		
		foreach($manufacturers as $m) 
		{
			
			$part_id = $m['manufacturer_id'];
			
			$url_request = "http://api.autokontinent.ru/v1/search/price.json";
			
			$headers = array('Content-Type: application/x-www-form-urlencoded',
				"Authorization: Basic " . base64_encode($login . ":" . $password)
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url_request);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, true);
			
			$post_fields = http_build_query(
				array("part_id"=>$part_id,"show_cross" => true,"show_odds" => true)
			);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			$exec = curl_exec($ch);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по part_id ".$part_id, $url_request."<br>Авторизация через Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод POST<br>Поля: ".print_r(array("part_id"=>$part_id,"show_cross" => true,"show_odds" => true), true), $exec, print_r(json_decode($exec, true), true) );
			}
			
			
			curl_close($ch);
			
			if ($exec) {
				
				array_push($anwers, $exec);
				
			}
			
		}
		
		foreach ($anwers as $answer) {
			
			$decode  = json_decode($answer, true);
			
			if ($decode) {
				
				foreach ($decode as $item) {
					
					$manufacturer 	= $item['brand_name'];
					$article 		= $item['part_code'];
					$name 			= $item['part_name'];
					$exist 			= $item['quantity'];
					$price_purchase	= $item['price'];
					$storage 		= $item['warehouse_id'];
					$min_order 		= $item['package'];
					
					$dt_delivery 	= $item['dt_delivery'];//Срок доставки datetime
					
					$date_delivery = new DateTime($dt_delivery);
					$timestamp_delivery = $date_delivery->getTimestamp();
					$current_timestamp = time();
					
					$issue_timestamps = $timestamp_delivery - $current_timestamp;
					
					$delivery_days = (int) $issue_timestamps / 60 / 60 / 24; 
					
					$time_to_exe 	= $delivery_days + $storage_options['additional_time'];
					$time_to_exe_guaranteed = $delivery_days + $storage_options['additional_time'];
					
					//Наценка
					//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					$markup = $storage_options["markups"][(int)$price_purchase];
					if($markup == NULL) {
						
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						
					}
					
					$price_for_customer = $price_purchase + $price_purchase * $markup;
					
					$rest_params = array("rate"=>$storage_options["rate"]);
					
					$DocpartProduct = new DocpartProduct(
						$manufacturer,
						$article,
						$name,
						$exist,
						$price_for_customer,
						$time_to_exe,
						$time_to_exe_guaranteed,
						$storage,
						$min_order,
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price_purchase,
						$markup,
						2,
						0,
						0,
						'',
						'',
						$rest_params
					);
					
					if($DocpartProduct->valid) {
						
						array_push($this->Products, $DocpartProduct);
						
					}
					
				}
				
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
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



$ob =  new autokontinent_enclosure($_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit( json_encode($ob) );
?>