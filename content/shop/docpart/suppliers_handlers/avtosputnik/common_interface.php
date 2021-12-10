<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtosputnik_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturer, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$article_search = $article;
		
		$data = array();
		
		$data['options'] = array(
			"login"=>$login,
			"pass"=>$password,
			"datatyp"=>"JSON",
			"storage"=>"as"
		);
		
		$data['data'] = array(
			"articul"=>$article_search,
			"brand"=>""
		);
		
		$data = serialize($data);
		
		$url = "https://api.auto-sputnik.ru/search_result.php?arr={$data}";
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$execute = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url, $execute, print_r(json_decode($execute, true), true) );
		}
		
		$answer = json_decode($execute, true);

		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Ошибка при получении остатков.<br>".print_r(curl_error($ch), true) );
			}
			return;
		}
		
		curl_close($ch);


		if($answer["requestInfo"]["Status"] == "ok" && $answer["requestInfo"]["Error"] == "no")
		{
			$info_parts = $answer["requestAnswer"];
			
			foreach($info_parts as $part)
			{
				$price = $part["NEW_COST"];
				
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$price_for_custumer = $price + $price*$markup;
				
				$time_to_exe = $part["DAYOFF"] + $storage_options["additional_time"];
				$time_to_exe_guaranteed =  $part["N_DELTA"] + $time_to_exe + $storage_options["additional_time"];
				
				
				$DocpartProduct = new DocpartProduct($part["BRA_BRAND"],
					$part["ARTICUL"],
					$part["NAME_TOVAR"],
					$part["STOCK"],
					$price_for_custumer,
					$time_to_exe,
					$time_to_exe_guaranteed,
					0,
					$part["MINIMAL"],
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




$ob =  new avtosputnik_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>