<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class v_avto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
		$api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
		
		foreach($manufacturers as $manufacturer) 
		{

			$brand = preg_replace("/[^a-zа-яё\d]+/iu", '', $manufacturer["manufacturer"]);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.v-avto.ru/v1/search/name.json?key={$api_key}&q={$article}&b={$brand}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			
			$curl_result = curl_exec($ch);
			
			// -------------------------------------------------------------------------------------------------

			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и бренду ".$brand, "https://api.v-avto.ru/v1/search/name.json?key={$api_key}&q={$article}&b={$brand}", $curl_result, print_r(json_decode($curl_result, true), true) );
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

			$result = json_decode($curl_result, true);

			//Сначала по запрошенному артикулу
			if(isset($result['response']['items'])) 
			{
				$products = $result['response']['items'];
	
				if (isset($products)) 
				{
					//--------------По данным ответа---------------//
					foreach ($products as $product) 
					{
						$price = (int)$product['price'];

						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}

						$min_order = $product['shipment'];
						if(empty($min_order)) {
							$min_order = 1;
						}

						$delivery_time = $product['delivery'];
						$delivery_time_guaranteed = $product['delivery'];


						if(empty($delivery_time)) {
							$delivery_time = 0;
							$delivery_time_guaranteed = 0;
						}
						

						// //Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct((string)$product['oem_brand'],
							(string)$product['oem_num'],
							(string)$product['name'],
							(int)$product['count'],
							$price + $price*$markup,
							$delivery_time + $storage_options["additional_time"],
							$delivery_time_guaranteed + $storage_options["additional_time"],
							NULL,
							$min_order,
							$storage_options["probability"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',null,array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
					}
				}
			}

		}
		// -------------------------------------------------------------------------------------------------

		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}
};//~class autoeuro_v2_enclosure




//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new v_avto_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>