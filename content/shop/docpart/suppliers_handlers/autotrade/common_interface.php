<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);	// Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autotrade_enclosure 
{

	public $result;
	public $Products = array();							// Список товаров

	public function __construct($article, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result 	= 0;									// По умолчанию
		$hash_auth 		= $storage_options["api_key"];			// Учетные данные
		$address 		= "https://api2.autotrade.su/?json";	// Адрес для запроса

		// 1. Получаем артикулы с учетом аналогов:
		$query_string 	= 'data={"auth_key":"'.$hash_auth.'","method":"GetItemsByQuery","params":{"q":["'.$article.'"],"replace":"1","cross":"1","withSubs":"1"}}';
		$ch 			= curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);			// для остановки cURL от проверки сертификата узла сети
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед CURL-запросом на получение артикулов с аналогами. Метод GetItemsByQuery. Далее последует цикл do-while с выполнением curl_exec(), т.к. у поставщика могут возникать превышения лимитов на количество запросов");
		
		// UPD: Добавляем проверку на ошибку "Превышен лимит на число запросов за n секунд.".
		// В случаи необходимости, можно добавить timeout
		$result_string = "";
		do 
		{
			$result_string = curl_exec($ch);
			$result = $result_string;
			
			$result = json_decode($result, true);
			// DEBUG
			if ( $result['code'] == 7 ) 
			{
				//echo 'GetItemsByQuery: recurl' . '<br/>';
			}
		} while ( $result['code'] == 7 );
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Прошли цикл do-while. Далее лог запроса");
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка артикулов с аналогами по артикулу ".$article, "https://api2.autotrade.su/?json<br>Метод POST<br>Поля: ".$query_string, $result_string, print_r(json_decode($result_string, true), true) );
		}
		
		
		if ($result["message"] != "Ok") 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка в CURL-запросе на получение списка артикулов", $result["message"] );
			}
			
			return;
		}
		$articles_list = array();//Список артикулов - делаем сразу ассоциативным, как потребуется в запросе товаров
		$items = $result["items"];
		for ($i =0; $i < count($items); $i++) 
		{
			$articles_list[(string)$items[$i]["article"]] = 1000;//1000 - количество требуемых
		}


		// 2. Получаем список складов
		$store_list = array();
		//Строка запроса по артикулу и наименованию с неполным соответствием:
		$query_string 	= 'data={"auth_key":"'.$hash_auth.'","method":"GetStoragesList"}';
		$ch 			= curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 			// для остановки cURL от проверки сертификата узла сети
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
		// UPD: Добавляем проверку на ошибку "Превышен лимит на число запросов за n секунд.".
		// В случаи необходимости, можно добавить timeout
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед CURL-запросом на получение списка складов поставщика. Метод GetStoragesList. Далее последует цикл do-while с выполнением curl_exec(), т.к. у поставщика могут возникать превышения лимитов на количество запросов");
		$result_string = "";
		do 
		{
			$result_string = curl_exec($ch);
			$result = $result_string;

			$result	= json_decode($result, true);
			// DEBUG
			if ( $result['code'] == 7 )
			{
				//echo 'GetStoragesList: recurl' . '<br/>';
			}
		} while ( $result['code'] == 7 );
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Прошли цикл do-while. Далее лог запроса");
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка складов поставщика", "https://api2.autotrade.su/?json<br>Метод POST<br>Поля: ".$query_string, $result_string, print_r(json_decode($result_string, true), true) );
		}
		
		
		
		for($i=0; $i < count($result); $i++) 
		{
			//if($result[$i]["legend"] == "А" || $result[$i]["legend"] == "A(п.46)")continue;//Фильтр складов
			//И(Р) И(А) И(С) А A(п.46)
			array_push($store_list, (integer)$result[$i]["id"]);
		}

		// 3. Теперь получаем товары
		// Строка запроса по артикулу и наименованию с неполным соответствием:
		$query_string 	= 'data={"auth_key":"'.$hash_auth.'","method":"GetStocksAndPrices","params":{"storages":"'.json_encode($store_list).'","replace":"1","items":'.json_encode($articles_list).',"cross":"1","withDelivery":"1","withSubs":"1"}}';
		$ch 			= curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 			// для остановки cURL от проверки сертификата узла сети
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
		// UPD: Добавляем проверку на ошибку "Превышен лимит на число запросов за n секунд.".
		// В случаи необходимости, можно добавить timeout
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед CURL-запросом на получение остатков. Метод GetStocksAndPrices. Далее последует цикл do-while с выполнением curl_exec(), т.к. у поставщика могут возникать превышения лимитов на количество запросов");
		$result_string = "";
		do 
		{
			$result_string = curl_exec($ch);
			$result = $result_string;
			
			
			$result	= json_decode($result, true);
			// DEBUG
			if ( $result['code'] == 7 )
			{
				//echo 'GetStocksAndPrices: recurl' . '<br/>';
			}
		} while ( $result['code'] == 7 );
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Прошли цикл do-while. Далее лог запроса");
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков", "https://api2.autotrade.su/?json<br>Метод POST<br>Поля: ".$query_string, $result_string, print_r(json_decode($result_string, true), true) );
		}
		

		$result = $result["items"];
		$sweep 	= array(" ", "-", "/", ".", ",", "#", "\r\n", "\r", "\n", "\t");

		//Список товаров:
		$items = array();
		foreach( $result as $key=>$value ) 
		{
			array_push($items, $value);
		}

		for($i=0; $i < count($items); $i++) 
		{
			$stocks = $items[$i]["stocks"];

			foreach($stocks as $key => $value) 
			{
				$price 				= (integer)$items[$i]["price"];
				$delivery_period 	= (integer)$items[$i]["delivery_period"];
				$exist 				= (integer)$value["quantity_unpacked"];

				// Наценка
				$markup = $storage_options["markups"][(int)$price];
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				if ( $markup == NULL ) {
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}

				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($items[$i]["brand"],
				$items[$i]["article"],
				$items[$i]["name"],
				$exist,
				$price + $price*$markup,
				$delivery_period + $storage_options["additional_time"],
				$delivery_period + $storage_options["additional_time"],
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
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}	//~function __construct($article)

};	//~class autotrade_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new autotrade_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>
