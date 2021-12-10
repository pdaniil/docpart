<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");//Класс продукта

class CurlException extends Exception{}

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class ivers_enclosure
{
	public $Products = array(); //Массив объектов DocpartProduct
	public $result = 0; //Результат работы
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("CURL-запросы посылаются из отдельного метода, перед вызовами которого выводим пояснение");
		}
		
		$login		= $storage_options["login"];
		$password	= $storage_options["password"];
		$session	= ""; //Ключ сессии
		
		$url_auth	= "https://order.ivers.ru/api/v1/login";
		$auth_array	= array("params" => array("login" => $login, "password" => $password));
		$headers 	= array("Content-Type: application/json"); 
	
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Посылаем CURL-запрос на авторизацию");
		}
	
		$execute_result	= $this->executeCurl($url_auth, null, $auth_array);//Выполняем CURL-запрос
		$session		= $execute_result["result"]["params"]["token"];
	
		$type_delivery = array('ivers-assortment', 'ivers-assortment-analog', 'inomarket', 'inomarket-analog', 'ivers-no-assortment');
		
		$url_search		= "https://order.ivers.ru/api/v1/product/search";
		//По всем вариантам поставщиков
		foreach($type_delivery as $delivery)
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Посылаем CURL-запрос для получения остатков для типа доставки: ".$delivery);
			}
			
			$search_array	= array("params" => array("text" => $article, "type" => $delivery));
			$execute_result = $this->executeCurl($url_search, $session, $search_array);
			
			
			if($execute_result["result"]["params"]["success"])
			{
				$originals_parts = $execute_result["result"]["params"]["objects"]["original"];//Оригинальные
				$analogs_parts = $execute_result["result"]["params"]["objects"]["replacement"];//Замены

				//По массиву оригинальных
				foreach($originals_parts as $part)
				{
					
					$exist_array = array(); //Наличие
					
					preg_match("/[0-9]+/", $part["status"], $exist_array);
					
					$exist = $exist_array[0];
					
					$price = $part["prices"][0]["price"]; //Цена
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$DocpartProduct = new DocpartProduct($part['brand'],
						$part['baseNumber'],
						$part['name'],
						$exist,
						$price + $price*$markup,
						$part["delivery"] + $storage_options["additional_time"],
						$part["delivery"] + $storage_options["additional_time"],
						NULL,
						1,
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
					else
					{
						//echo "Оригинал: DocpartProduct\n";
						//var_dump($DocpartProduct);
					}
				}
				//По массиву Замен
				foreach($analogs_parts as $part)
				{
					
					$exist_array = array(); //Наличие
				
					preg_match("/[0-9]+/", $part["status"], $exist_array);
					
					$exist = $exist_array[0];
					
					$price = $part["prices"][0]["price"]; //Цена
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$DocpartProduct = new DocpartProduct($part['brand'],
						$part['baseNumber'],
						$part['name'],
						$exist,
						$price + $price*$markup,
						$part["delivery"] + $storage_options["additional_time"],
						$part["delivery"] + $storage_options["additional_time"],
						NULL,
						1,
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
					else
					{
						//echo "Замена: DocpartProduct\n";
						//var_dump($DocpartProduct);
					}
				}
			}
			else
			{
				continue;
			}
		}
		
		
		$this->result = 1;
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Посылаем CURL-запрос для logout");
		}
		
		$url_out = "https://order.ivers.ru/api/v1/logout";
		$out_array = array();
		$execute_result = $this->executeCurl($url_out, $session, $out_array);
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
	}
	
	private function executeCurl($url, $cookie = null, $params)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$ch				= curl_init();
		$params_json	= json_encode($params);
		$headers 		= array("Content-Type: application/json");
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params_json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		if($cookie != null)
		{
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		
		$curl_result = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос", $url."<br>Метод POST<br>Поля: ".$params_json, $curl_result, print_r(json_decode($curl_result, true), true) );
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
		
		$result = json_decode($curl_result, true);
		
		if( ! $result["success"])
		{
			throw new CurlException("Ошибка запроса!");
		}
		
		return $result;
	}
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


try
{
	$ob	= new ivers_enclosure($_POST["article"], $storage_options);
}
catch(CurlException $e)
{
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit(json_encode($ob));
?>