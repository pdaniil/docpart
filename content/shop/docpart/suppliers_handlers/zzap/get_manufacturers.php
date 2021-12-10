<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class zzap_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$api_key = $storage_options["api_key"];
		
		// /*
		//Получаем "Регионы поиска" -Зачем? - ХЗ
		$ch = curl_init();
		$url = "https://www.zzap.ru/webservice/datasharing.asmx/GetSearchSuggestV2";
		
		$fields = array(
			"search_text"=>$article,
			"row_count"=>100,
			"type_request"=>1,
			"api_key"=>$api_key
		);
		
		$post_fields = http_build_query($fields);
			
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

		$exec = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($exec);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url."<br>Метод POST<br>Поля: ".print_r($fields, true), htmlspecialchars($exec, ENT_QUOTES, "UTF-8"), print_r($php_object, true) );
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
		
		$xml = new SimpleXMLElement($exec);
		
		$json = $xml[0]->__toString();
		
		if( $json == "" )
			return;
		
		$data = json_decode($json, true);
		
		if( ! $data )
			return;
		
		if(!empty($data['error'])){
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("API-ошибка", $data['error']);
			}
		}
		
		$manufacturers_list = $data["table"];
		
		foreach($manufacturers_list as $item)
		{
			$manufacturer 			= $item["class_man"];
			$manufacturer_id 		= 0;
			$name					= $item["class_cat"];
			$synonyms_single_query	= true;
			$params 				= "";
			
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
		
		$this->status = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new zzap_enclosure($_POST["article"], $storage_options);
exit( json_encode($ob) );
?>