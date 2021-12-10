<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autoeuro_v2_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
		$api_key = $storage_options["api_key"];
		/*****Учетные данные*****/

		// -------------------------------------------------------------------------------------------------
		//Получаем список сбытовых организаций клиента
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.autoeuro.ru/api/v-1.0/shop/stock_items/json/{$api_key}?code={$article}&with_crosses=1");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$curl_result = curl_exec($ch);
			
		// -------------------------------------------------------------------------------------------------

		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://api.autoeuro.ru/api/v-1.0/shop/stock_items/json/$api_key?code=$article&with_crosses=1", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
			}
		}

		curl_close($ch);

		$result = json_decode($curl_result);

		if(isset($result->DATA->VARIANTS)) 
		{
			$manufacturers = $result->DATA->VARIANTS;
			if (isset($manufacturers)) 
			{
				//--------------По данным ответа---------------//
				foreach ($manufacturers as $manufacturer) 
				{
					$DocpartManufacturer = new DocpartManufacturer(
						$manufacturer->brand,
						0,
						$manufacturer->name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						array()
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
			}
		}
		
		
		if(isset($result->DATA->CODES)) 
		{
			$manufacturers = $result->DATA->CODES;
			if (isset($manufacturers)) 
			{
				//--------------По данным ответа---------------//
				foreach ($manufacturers as $manufacturer) 
				{
					$DocpartManufacturer = new DocpartManufacturer(
						$manufacturer->maker,
						0,
						$manufacturer->name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						array()
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
			}
		}

			
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
};//~class autoeuro_v2_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new autoeuro_v2_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>