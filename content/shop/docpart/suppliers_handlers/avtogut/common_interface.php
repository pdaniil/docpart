<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtogut_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		// Запрос брендов
		$url = "http://ws.shop.avtogut.ru/getBrands/$login/$password/$article/";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$execute = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r(json_decode($execute, true), true) );
		}
		
		
		$answer = json_decode($execute, true);
		
		
		
		if(!empty($answer['result']))
		{
			$brands = $answer['result'];
			
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");
			
			foreach($brands as $brand)
			{
				
				$group_id = $brand['group_id'];
				
				// Запрос позиций по бренду
				$url = "http://ws.shop.avtogut.ru/getPrices/$login/$password/$article/$group_id/";
				curl_setopt($ch, CURLOPT_URL, $url);
				$execute = curl_exec($ch);
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ID ".$group_id, $url, $execute, print_r(json_decode($execute, true), true) );
				}
				
				$answer = json_decode($execute, true);
				
				if(!empty($answer['result'])){
					foreach($answer['result'] as $product){
						$price = $product["price"];
						
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						$term = trim($product["term"]);
						$term = explode('/',$term);
						
						$time_to_exe = (int)trim($term[0]);
						$time_to_exe_guaranteed = (int)trim($term[1]);
						
						if($time_to_exe_guaranteed < $time_to_exe){
							$time_to_exe_guaranteed = $time_to_exe;
						}
						
						$time_to_exe = $time_to_exe + $storage_options["additional_time"];
						$time_to_exe_guaranteed =  $time_to_exe_guaranteed + $time_to_exe + $storage_options["additional_time"];
						
						
						$DocpartProduct = new DocpartProduct($product["brand"],
							$product["article"],
							$product["title"],
							$product["remains"],
							$price + $price * $markup,
							$time_to_exe,
							$time_to_exe_guaranteed,
							0,
							$product["min_quantity"],
							$storage_options["probability"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,
							0,
							0,
							'',
							NULL,
							array("rate"=>$storage_options["rate"])
						);
				
						if($DocpartProduct->valid)
						{
							array_push($this->Products, $DocpartProduct);
						}
					}
				}
			}
		}
		
		curl_close($ch);
		
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
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob =  new avtogut_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>