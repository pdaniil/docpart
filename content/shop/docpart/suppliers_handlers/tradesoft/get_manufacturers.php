<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tradesoft_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$user_tradesoft = $storage_options["user_tradesoft"];//Логин на сайте tradesoft.ru
		$password_tradesoft = $storage_options["password_tradesoft"];//Пароль  на сайте tradesoft.ru
		$provider = $storage_options["provider"];//Уникальный идентификатор поставщика (клиент tradesoft)
		$user_provider = $storage_options["user_provider"];//Логин и на сайте поставщика
		$password_provider = $storage_options["password_provider"];//Пароль и на сайте поставщика
		/*****Учетные данные*****/
        

		//Запрос производителей
		$request = array(
			'service'	=> 'provider',
			'action'	=> 'GetProducerList',
			'user'		=> $user_tradesoft,
			'password'	=> $password_tradesoft,
			'container'	=> array(
				array(
					'provider'	=> $provider,
					'login'		=> $user_provider,
					'password'	=> $password_provider,
					'code'		=> $article
				)
			)
		);
		$post = json_encode($request);

		$ch = curl_init('https://service.tradesoft.ru/3/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$data = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, 'https://service.tradesoft.ru/3/'."<br>Метод POST<br>Поля: ".print_r($request, true), $data, print_r(json_decode($data, true), true) );
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

		$responce = json_decode($data, true);
		$manufacturers = $responce["container"][0]["data"];
		
		
		//По каждому производителю
		for($i = 0; $i < count($manufacturers); $i++)
		{
			$DocpartManufacturer = new DocpartManufacturer($manufacturers[$i]['producer'],
			    $manufacturers[$i]['code'],
				$manufacturers[$i]['caption'],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}//~function __construct($article)
};//~class tradesoft_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new tradesoft_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>