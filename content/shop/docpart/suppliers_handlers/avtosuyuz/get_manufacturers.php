<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtosuyuz_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$withoutTransit = $storage_options["withoutTransit"] ? 'false' : 'true' ;
		
		$base_hash = base64_encode($login . ":" . $password);
		
		$service = "http://xn----7sbgfs5baxh7jc.xn--p1ai/";
		$action = "SearchService/GetBrands";
		
		$params_action = array();
		$params_action['article'] = $article;
		$params_action['withoutTransit'] = $withoutTransit;
		
		$build = http_build_query( $params_action );
		
		$url = $service . $action . "?" . $build;
		
		$headers = array(
			"Authorization:  Basic ".$base_hash,
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
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $exec, print_r(json_decode($exec, true), true) );
		}
		
	
		if(curl_errno($ch)) 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "При запросе брендов через CURL возникла ошибка.<br>".print_r(curl_error($ch), true) );
			}
			
			curl_close($ch);
			return;
		}
		curl_close($ch);
		
		$decode = json_decode($exec, true);
		
		foreach ($decode as $row) 
		{
			
			$DocpartManufacturer = new DocpartManufacturer($row["Brand"],
				0,
				$row["Description"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true,
				null
			);
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
			
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
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

$ob = new avtosuyuz_enclosure($_POST["article"], $storage_options);

exit(json_encode($ob));
?>