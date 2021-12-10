<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class fwheel_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		// ----------------------------------------------------------------------------
		//Авторизуемся (получаем ID сессии)
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://trade.fwheel.com/portal/auth/login");
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "login=".$login."&pass=".$password);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение сессии", "http://trade.fwheel.com/portal/auth/login<br>Метод POST<br>Поля: "."login=".$login."&pass=".$password, $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка при получении сессии", print_r(curl_error($ch), true) );
			}
		}
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		$curl_result = json_decode($curl_result, true);
		if($curl_result["result"]["descr"] != "OK")
		{
			return;
		}
		//Получаем сессию:
		$sid = $curl_result["data"]["sid"];
		$uid = $curl_result["data"]["uid"];
		$conditions = $curl_result["data"]["conditions"];
		$condition_id = $conditions[0]["condition_id"];
		
		// ----------------------------------------------------------------------------
		
		
		//Запрос товаров по артикулу
		// инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://trade.fwheel.com/portal/goods/search_by_code");
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "code=".$article."&offer_type=1&analogs=1&sid=".$sid."&condition_id=".$condition_id);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://trade.fwheel.com/portal/goods/search_by_code<br>Метод POST<br>Поля: "."code=".$article."&offer_type=1&analogs=1&sid=".$sid."&condition_id=".$condition_id, $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка при получении остатков", print_r(curl_error($ch), true) );
			}
		}
		
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		$curl_result = json_decode($curl_result, true);
		if($curl_result["result"]["descr"] != "OK")
		{
			return;
		}
		
		//Получаем товары
		$products = $curl_result["data"]["goods"];
		//По наборам
		for($i=0; $i < count($products); $i++)
		{
			$set = $products[$i];
			for($s=0; $s < count($set); $s++)
			{
				//Товар:
				$product = $set[$s];
				$offers = $product["offers"];
				for($o=0; $o < count($offers); $o++)
				{
					$price = $offers[$o]["price"];

					//Получаем время доставки:
					$min_time = explode(" ", $offers[$o]["deliv_time_descr"]);
					$min_time = $min_time[0];
					$min_time = explode("-", $min_time);
					if(count($min_time) > 1)
					{
						$max_time = $min_time[1];
						$min_time = $min_time[0];
					}
					else
					{
						$min_time = $min_time[0];
						$max_time = explode(" ", $offers[$o]["deliv_time_max_descr"]);
						$max_time = $max_time[0];
					}
					
					//Количество
					$exist = str_replace(array(">", "<"), "", $offers[$o]["in_stock"]);

					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($product["brand_name"],
						$product["prod_code"],
						$product["prod_name"],
						$exist,
						$price + $price*$markup,
						$min_time + $storage_options["additional_time"],
						$max_time + $storage_options["additional_time"],
						NULL,
						$offers[$o]["min_order"],
						$offers[$o]["deliv_chance"],
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

		// ----------------------------------------------------------------------------
		//Завершаем сессию:
		// инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://trade.fwheel.com/portal/auth/logout");
		curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "sid=".$sid);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос на завершение сессии", "http://trade.fwheel.com/portal/auth/logout<br>Метод POST<br>Поля: "."sid=".$sid, $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка при завершении сессии", print_r(curl_error($ch), true) );
			}
		}
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		// ----------------------------------------------------------------------------
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class fwheel_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new fwheel_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>