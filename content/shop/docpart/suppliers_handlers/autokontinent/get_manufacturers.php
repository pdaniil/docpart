<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autokontinent_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = trim($storage_options['login']);
		$password = trim($storage_options['password']);
		
		$url_request = "http://api.autokontinent.ru/v1/search/part.json";
		
		$headers = array('Content-Type: application/x-www-form-urlencoded',
			"Authorization: Basic " . base64_encode($login . ":" . $password)
		);
		
		$post_fields = http_build_query(array("part_code"=>$article));
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url_request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		
		$exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url_request."<br>Авторизация через Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод POST<br>Поля: ".print_r(array("part_code"=>$article),true), $exec, print_r(json_decode($exec, true), true) );
		}
		
		
		$decode = json_decode($exec, true);
		
		foreach($decode as $item) {
			
			$manufacturer = $item['brand_name'];
			$manufacturer_id = $item['part_id'];
			$name = $item['part_descr'];
			$synonyms_single_query = true;
			
			$DocpartManufacturer = new DocpartManufacturer($manufacturer,
				$manufacturer_id,
				$name,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$synonyms_single_query,
				$params
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



$ob =  new autokontinent_enclosure($_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit( json_encode($ob) );
?>