<?php
// API https://b2motor.ru/webservice/

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class b2motor_enclosure
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
		/*****Учетные данные*****/

		$query = http_build_query(
			array(
				'article' => $article,
				'brand' => "",
				'userlogin' => $login,
				'userpassw' => md5($password),
			)
		);//Аргументы =&=&=&=

		//СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
		// установка URL и других необходимых параметров
		curl_setopt($ch, CURLOPT_URL, "https://b2motor.ru/api/webservice/search/product/?".$query);
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// загрузка страницы и выдача её браузеру
		$curl_result = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Запрос по артикулу ".$article, "https://b2motor.ru/api/webservice/search/product/?".$query, $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
			}
		}
		
		 // завершение сеанса и освобождение ресурсов
		curl_close($ch);

		$manufacturers = json_decode($curl_result);

		if (!isset($manufacturers->errorMessage)) {

			if(is_array($manufacturers)) {

				//--------------По данным ответа---------------//
				foreach ($manufacturers as $manufacturer) {

					$DocpartManufacturer = new DocpartManufacturer(
						$manufacturer->brand,
						0,
						$manufacturer->description,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						array()
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					
				}
			} else {

				$DocpartManufacturer = new DocpartManufacturer(
					$manufacturer->Brand,
					0,
					$manufacturer->description,
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



$ob = new b2motor_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>