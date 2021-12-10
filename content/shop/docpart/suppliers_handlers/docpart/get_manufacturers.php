<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class docpart_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		$this->status = 0;//По умолчанию
		
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
		$domain 	= $storage_options["domain"];
		$off_id 	= $storage_options["off_id"];
		$no_api 	= $storage_options["no_api"];
		$login 		= $storage_options["login"];
		$password 	= $storage_options["password"];
		/*****Учетные данные*****/
		
		// Далее делаем запрос
		$postdata = array(
			'action' 	=> "get_brends",
			'login' 	=> $login,
			'password' 	=> $password,
			'article' 	=> $article
		);
		
		if(!empty($off_id)){
			$postdata['offices'] = json_encode(explode(',', $off_id));
		}
		
		if(!empty($no_api)){
			$postdata['no_api'] = 1;
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $domain."web_service/api.php");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		$curl_result = curl_exec($curl);
		if( curl_errno($curl) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true) );
			}
			curl_close($curl);
			return;
		}
		curl_close($curl);
		$result = json_decode($curl_result, true);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $domain."web_service/api.php", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
	
		if($result['result'] === true){
			foreach($result['brends'] as $item)
			{
				
				$DocpartManufacturer = new DocpartManufacturer($item["manufacturer"],
					0,
					$item["name"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					$item["manufacturers"]
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
				
			}
			$this->status = 1;
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	}//~function __construct($article)
};//~class docpart_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new docpart_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>