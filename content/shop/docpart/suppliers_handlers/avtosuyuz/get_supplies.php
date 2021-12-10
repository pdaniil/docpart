<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
// require_once($_SERVER["DOCUMENT_ROOT"]."/Logger.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtosuyuz_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$withoutTransit = $storage_options["withoutTransit"] ? 'false' : 'true' ;
		
		$base_hash = base64_encode($login . ":" . $password);
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед циклом по производителям");
		
		$service = "http://xn----7sbgfs5baxh7jc.xn--p1ai/";
		$action = "SearchService/GetParts";
		
		for ($m=0; $m < count($manufacturers); $m++) 
		{
			
			$params_action = array();
			$params_action['article'] = $article;
			$params_action['brand'] = $manufacturers[$m]["manufacturer"];
			$params_action['withoutTransit'] = $withoutTransit;
			
			$build = http_build_query( $params_action );
			
			$url = $service . $action . "?" . $build;
			
			$headers = array(
				"Authorization:  Basic {$base_hash}",
				"Accept: application/json",
				"Content-type: application/json"
			);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

			$exec = curl_exec($ch);
			
			// Logger::addLog( 'url', $url );
			// Logger::addLog( 'exec', $exec );
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[$m]["manufacturer"], $url, $exec, print_r(json_decode($exec, true), true) );
			}
			
			
			if(curl_errno($ch)) {
				
				
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "При запросе остатков через CURL возникла ошибка.<br>".print_r(curl_error($ch), true) );
				}

				curl_close($ch);
				return;
				
			}
			curl_close($ch);
			
			$decode = json_decode($exec, true);
			
			// Logger::addLog( 'decode', $decode );
			
			foreach ($decode as $row) {
				
				/**
				
					*	article – Артикул
					*	brand – Бренд
					*	supplierName – имя поставщика (см. первую таблицу)
					*	costSale – цена товара (см. первую таблицу)
					*	quantity – количество единиц товара
					*	supplierTimeMin – минимальное время поставки (см. первую таблицу)
					*	supplierTimeMax – максимальное время поставки (см. первую таблицу)
					
				*/
				
				$json_data = array(
					"article"=>$row["Article"],
					"brand"=>$row["Brand"],
					"supplierName"=>$row["SupplierName"],
					"supplierTimeMin"=>$row["SupplierTimeMin"],
					"supplierTimeMax"=>$row["SupplierTimeMax"]
				);
				
				$price = $row["CostSale"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				if($markup == NULL) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer = $row["CostSale"] + $row["CostSale"]*$markup;
				
				$DocpartProduct = new DocpartProduct(
					$row["Brand"],
					$row["Article"],
					$row["Description"],
					$row["Count"],
					$price_for_customer,
					$row["SupplierTimeMin"]/24 + $storage_options["additional_time"],
					$row["SupplierTimeMax"]/24 + $storage_options["additional_time"],
					$row["SupplierName"],
					$row["MinCount"],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$row["CostSale"],
					$markup, 
					2,
					0,
					0,
					0,
					json_encode($json_data)
				);
				
				// Logger::addLog( '$DocpartProduct', $DocpartProduct );
				if($DocpartProduct->valid) {
					
					
					array_push($this->Products, $DocpartProduct);
					
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

$ob =  new avtosuyuz_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);

// Logger::writeLog( __DIR__, 'dump_s.log' );

exit( json_encode($ob) );
?>