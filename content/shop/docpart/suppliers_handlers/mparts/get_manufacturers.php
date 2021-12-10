<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mparts_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//----------------Учётные данные для API поставщика---------------//
		$login 				= $storage_options["login"];
		$password			= md5($storage_options["password"]);
		$useOnlineStocks	= $storage_options["useOnlineStocks"];
		
		//---------------Формируем запрос к поставщику-----------------//
		$url = "http://v01.ru/api/devinsight/search/brands/?userlogin={$login}&userpsw={$password}&number={$article}&useOnlineStocks={$useOnlineStocks}";
		
		//--------------------- Запрос в API Поставщика---------------//
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		
		$execute = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r(json_decode($execute, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		curl_close($ch);
		
		$array_data = json_decode($execute, true);
		
		if (empty($array_data["error"])) {
			
			//--------------По данным ответа---------------//
			foreach ($array_data as $brand_data) {
				
				$brand_name = $brand_data["brand"];
				$description = $brand_data["description"];
				
				$DocpartManufacturer = new DocpartManufacturer(
					$brand_name,
					0,
					$description,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					array()
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
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new mparts_enclosure($_POST["article"], $storage_options);
exit( json_encode($ob) );
?>