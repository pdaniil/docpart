<?php
// API https://b2motor.ru/webservice/

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class b2motor_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/

		if (!empty($manufacturers)) {
			foreach($manufacturers as $manufacturer) {

				$brand = $manufacturer["manufacturer"];

				$query = http_build_query(
					array(
						'article' => $article,
						'brand' => $brand,
						'userlogin' => $login,
						'userpassw' => md5($password),
					)
				);//Аргументы =&=&=&=

				$url = "https://b2motor.ru/api/webservice/search/product/?".$query;

				// инициализация сеанса
				$ch = curl_init();
				// установка URL и других необходимых параметров
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		
				// загрузка страницы и выдача её браузеру
				$curl_result = curl_exec($ch);
		
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brand, $url, $curl_result, print_r(json_decode($curl_result, true), true) );
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
				

				$products = json_decode($curl_result);


				if(!isset($products->errorMessage)) 
				{
					foreach($products as $product)
					{

						$price = (int)$product->price;

						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}

						$min_order = $product->packing;
						if(empty($min_order)) {
							$min_order = 1;
						}

						$delivery_time = $product->deliveryPeriod;
						$delivery_time_guaranteed = $product->deliveryPeriodMax;


						if(empty($delivery_time)) {
							$delivery_time = 0;
							$delivery_time_guaranteed = 0;
						}

						$availability = (int)$product->availability;

						if ($availability < 0) {
							$availability = 0;
						}
						

						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct((string)$product->brand,
							(string)$product->article,
							(string)$product->description,
							$availability,
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
							2,0,0,'',null,array("rate"=>$storage_options["rate"], "SupplierName" => $product->SupplierName)
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
					}
				}
			}
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		
		$this->result = 1;
	}//~function __construct($article)
};




//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );




$ob = new b2motor_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>