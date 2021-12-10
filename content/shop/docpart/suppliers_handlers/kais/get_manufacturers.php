<?php

// http://shop.kais.ru/

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class kais_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		$host = $storage_options["host"];
		/*****Учетные данные*****/


		//СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
		// установка URL и других необходимых параметров
		curl_setopt($ch, CURLOPT_URL, "https://".$host."/?do=api&full_price=1&all_stores=1&no_prices=0&with_analogs=1&article=".$article);
		curl_setopt($ch, CURLOPT_USERPWD, "$login:$password"); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

		// загрузка страницы и выдача её браузеру
		$curl_result = curl_exec($ch);


		// echo "<pre>";
		// print_r($curl_result);
		// echo "</pre>";
		// exit;

	
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$xml_result = simplexml_load_string($curl_result);

			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "https://automaster.ru/?do=api&full_price=1&all_stores=1&no_prices=1&with_analogs=1&article=".$article, $curl_result, print_r($xml_result, true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		

		$xml_result = simplexml_load_string($curl_result);

		$manufacturers = $xml_result->man;

		// echo "<pre>";
		// print_r($manufacturers);
		// echo "</pre>";
		// exit;


		if (isset($manufacturers->item)) {

			//--------------По данным ответа---------------//
			foreach ($manufacturers->item as $m) {

				$m_name = $m->man_name;
				$m_part = $m->part_name;

				$DocpartManufacturer = new DocpartManufacturer(
					$m_name,
					0,
					$m_part,
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
        
	}//~function __construct($article)
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

// $_POST["article"] = "C110";
// $storage_options = array("login" => "", "password" => "");

$ob = new kais_enclosure($_POST["article"], $storage_options);


// echo "<pre>";
// print_r($ob );
// echo "</pre>";
// exit;

exit(json_encode($ob));
?>