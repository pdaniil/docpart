<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class rmsauto_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		$auth_data = $this->auth($login, $password);
		
		$url = "https://api.rmsauto.ru/api/Articles/{$article}/Brands?analogues=false";
		
		$headers = array("Accept: application/json",
			"Authorization: {$auth_data["token_type"]} {$auth_data["access_token"]}"
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$exec = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url."<br>Заголовки: ".print_r($headers, true), $exec, print_r(json_decode($exec, true), true) );
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
		
		foreach ($decode as $brand) 
		{	
			$DocpartManufacturer = new DocpartManufacturer($brand["Name"],
				0,
				$brand["Description"],
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
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


try 
{
	$ob = new rmsauto_enclosure($_POST["article"], $storage_options);	
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