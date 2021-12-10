<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tmparts_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $authorization = $storage_options["authorization"];
		/*****Учетные данные*****/
        
		if($article == "" || $article == NULL)
		{
			return;
		}
		
        
		//1. ЗАПРОС НА ПОЛУЧЕНИЕ СПИСКА БРЕНДОВ ПО АРТИКУЛУ
		$ch1 = curl_init(); 
		$fields = array("JSONparameter" => "{'Article': '".$article."'}");
        curl_setopt($ch1, CURLOPT_URL, "api.tmparts.ru/api/ArticleBrandList?".http_build_query($fields)); 
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1); 
		//Авторизация
		$headers = array(         
			'Authorization: Bearer '.$authorization
		); 
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
		
		$curl_result = curl_exec($ch1);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "api.tmparts.ru/api/ArticleBrandList?".http_build_query($fields)."<br>Метод GET<br>Поля: ".print_r($fields,true)."<br>Заголовки: ".print_r($headers, true), $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($ch1))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch1), true) );
			}
		}
		
		$Art_List = json_decode($curl_result,true);  

		if ($Art_List[Message] != "")
		{
			//Не верный ключ
			return;
		}
		
		
		if ($Art_List[BrandList] == null)
		{
			//Артикул не найден
			return;
		}
		
		foreach($Art_List[BrandList] as $key1 => $brand)          
		{
			//Параметр запроса для проценки Номенклатуры с аналогами
			$fields = array("JSONparameter" => "{'Brand': '".$brand[BrandName]."', 'Article': '".$Art_List[Article]."', 'is_main_warehouse': 0 }" ); 
			curl_setopt($ch1, CURLOPT_URL, "api.tmparts.ru/api/StockByArticle?".http_build_query($fields)); 
			
			$curl_result = curl_exec($ch1);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$Art_List[Article]." и производителю ".$brand[BrandName], "api.tmparts.ru/api/StockByArticle?".http_build_query($fields)."<br>Метод GET<br>Поля: ".print_r($fields,true)."<br>Заголовки: ".print_r($headers, true), $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			if(curl_errno($ch1))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch1), true) );
				}
			}
			
			
			//Получить проценку по Номенклатуре
			$Art_List_With_Prices = json_decode($curl_result,true);   
			
			foreach($Art_List_With_Prices as $key2 => $value2)// По каждому найденному Бренду к искомому номеру выполнить проценку
			{
				$DocpartManufacturer = new DocpartManufacturer($brand[BrandName],
					$brand[BrandID],
					$value2[article_name],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					null
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
				
				break;
			}
		}//2
		curl_close($ch1);
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class tmparts_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new tmparts_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>