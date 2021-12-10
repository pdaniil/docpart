<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tradesoft_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
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
			$request = array(
				'service'	=> 'provider',
				'action'	=> 'GetPriceList',
				'user'		=> $user_tradesoft,
				'password'	=> $password_tradesoft,
				'container'	=> array(
					array(
						'provider'	=> $provider,
						'login'		=> $user_provider,
						'password'	=> $password_provider,
						'code'		=> $manufacturers[$i]["code"],
						'producer'	=> $manufacturers[$i]["producer"]
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
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[$i]["producer"], 'https://service.tradesoft.ru/3/'."<br>Метод POST<br>Поля: ".print_r($request, true), $data, print_r(json_decode($data, true), true) );
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
			
			$products = $responce["container"][0]["data"];
			
			for($j = 0; $j < count($products); $j++)
			{
				$current_record = $products[$j];
				$price = $current_record['price'];
			
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($current_record['producer'],
					$current_record['code'],
					$current_record['caption'],
					$current_record['rest'],
					$price + $price*$markup,
					$current_record['deliverydays_min'] + $storage_options["additional_time"],
					$current_record['deliverydays_max'] + $storage_options["additional_time"],
					$current_record['direction'],
					$current_record['minquantity'],
					$current_record['provider_rating'],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',json_encode($current_record),array("rate"=>$storage_options["rate"])
					);
				
				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
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