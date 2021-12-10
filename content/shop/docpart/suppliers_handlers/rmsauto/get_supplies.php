<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

ini_set("memory_limit", "256M");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class rmsauto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$auth_data = $this->auth($login, $password);
		
		$headers = array("Accept: application/json",
			"Authorization: {$auth_data["token_type"]} {$auth_data["access_token"]}"
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		
		foreach($manufacturers as $m) 
		{	
			$url = "https://api.rmsauto.ru/api/articles/{$article}/brand/{$m["manufacturer"]}?analogues=true";
			curl_setopt($ch, CURLOPT_URL, $url);
			
			$exec = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$m["manufacturer"], $url."<br>Заголовки: ".print_r($headers, true), $exec, print_r(json_decode($exec, true), true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
				return;
			}
			
			$decode = json_decode($exec, true);
			
			if ( ! $decode ) 
			{
				throw new Exception("Ошибка json: " . json_last_error());	
			}
			
			if (isset($decode["Message"])) 
			{
				throw new Exception("Ошибка Web-сервиса : " . $decode["Message"]);	
			}
			
			// var_dump($decode);
			
			foreach($decode as $item) 
			{	
				$markup = $storage_options["markups"][(int)$item["Price"]];
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				if($markup == NULL)
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$price_for_customer = $item["Price"] + $markup * $item["Price"];
				
				$DocpartProduct = new DocpartProduct($item["Brand"],
					$item["Article"],
					$item["Name"],
					$item["Count"],
					$price_for_customer,
					$item["DeliveryDaysMin"] + $storage_options["additional_time"],
					$item["DeliveryDaysMax"]+ $storage_options["additional_time"],
					0,
					$item["MinOrderQty"],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$item["Price"],
					$markup,
					2,
					0,
					0,
					'',
					'',
					array("rate"=>$storage_options["rate"])
				);
				
				if($DocpartProduct->valid) 
				{
					array_push($this->Products, $DocpartProduct);	
				}
			}// ~foreach($decode as $items)
		}// ~foreach($manufacturers as $m)
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
		curl_close($ch);
	}
	
	public function auth($login, $password) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$url = "https://api.rmsauto.ru/api/auth/token";
		
		$params = array(
			"username"=>$login,
			"password"=>$password,
			"code"=>"",
			"grant_type"=>"password"
		);
		
		$post_fields = http_build_query($params);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
		
		$execute = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос на авторизацию", $url."<br>Метод POST<br>Поля ".print_r($params, true), $execute, print_r(json_decode($execute, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
			
			throw new Exception("Ошибка curl: " . curl_errno($ch));
		}
		
		
		$decode = json_decode($execute, true);
		
		if ( ! $decode ) 
		{
			throw new Exception("Ошибка json: " . json_last_error());	
		}
		
		curl_close($ch);
		
		return $decode;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


try 
{	
	$ob =  new rmsauto_enclosure($_POST["article"], 
		json_decode($_POST["manufacturers"], true), 
		$storage_options
	);	
} 
catch (Exception $e) 
{	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}	
}
exit( json_encode($ob) );
?>