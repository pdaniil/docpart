<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtogut_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$token = $storage_options["token"];
		
		if(!empty($manufacturers))
		{
			$headers = [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Bearer '.$token
			];
	

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");
			
			foreach($manufacturers as $manufacturer_arr)
			{
				$group_id = $manufacturer_arr['manufacturer_id'];
				// $brand = $manufacturer_arr['manufacturer'];
				
				// Запрос позиций по бренду
				$url = "https://ws.shop.avtogut.ru/v2/search/$article/$group_id/match";
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				$execute = curl_exec($ch);
				

				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_arr['manufacturer']." (ID ".$group_id.")", $url, $execute, print_r(json_decode($execute, true), true) );
				}
				
				$answer = json_decode($execute, true);

				// var_dump($answer);
				

				if(!empty($answer['data'])){
					foreach($answer['data'] as $product){
						$price = (int)$product["pr"];
						
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						$current_time = time();
						$time_delivey = strtotime($product["arrival"]);
						$time_delivey = $time_delivey - $current_time;
						$d = DateTime::createFromFormat('U', $time_delivey);
						$time_delivey = $d->format('d');
						if((int)$time_delivey < 10) {
							$time_delivey = substr($time_delivey, 1);
						}
						
						$time_to_exe = $time_delivey + $storage_options["additional_time"];
						$time_to_exe_guaranteed =  $time_delivey + $storage_options["additional_time"];
						
						$DocpartProduct = new DocpartProduct($product["prd"],
							$product["code"],
							$product["name"],
							$product["ex"],
							$price + $price * $markup,
							$time_to_exe,
							$time_to_exe_guaranteed,
							0,
							$product["mq"],
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
			curl_close($ch);
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



$ob =  new avtogut_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>