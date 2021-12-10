<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class avtoto_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
		//Создание объекта клиента
		try
		{
			$client = new SoapClient("http://www.avtoto.ru/services/search/soap.wsdl", array('soap_version' => SOAP_1_1));
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		//Учетные данные:
		$customer_id = $storage_options["customer_id"];//Целое число
		$login = $storage_options["login"];//Строка
		$password = $storage_options["password"];//Строка

		
		// Параметры запроса
        $params = array(
        	'user_id' => $customer_id,
        	'user_login' => $login,
        	'user_password' => $password,	
        	'search_code' => $article,
        	'search_cross' => 'on'
        );
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Параметры для запроса к API ".print_r($params,true));
		}
		
		
		//Запускаем SOAP-процедуру
		try
		{
			// Поиск
            $ProcessSearchId = $client->SearchStart($params);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("SOAP-вызов SearchStart для получения ID поиска", "Параметры для запроса указаны в сообщении выше", "См. ответ API после обработки", print_r($ProcessSearchId, true) );
			}
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{			
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-метода SearchStart()", print_r($e, true) , $e->getMessage() );
			}
			
			return;
		}
		
		
		$ProcessSearchId = $ProcessSearchId['ProcessSearchId'];
		

		$params = array(
        	'ProcessSearchId' => $ProcessSearchId,
        	'Limit' => 1000
        );
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Далее следует SOAP-вызов SearchGetParts2 в цикле do-while. Лог вызова не пишем, но, если будет исключение - оно будет записано в лог");
		}
		
		
		try
		{
			$start_time = time();
			do
			{
				$this_time = time();
				if( ($this_time - $start_time) > 30 )
				{
					break;
				}
				// Поиск
				$GetPriceResult = $client->SearchGetParts2($params);
			}
			while($GetPriceResult['Info']['SearchStatus'] == 2);
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-метода SearchGetParts2()", print_r($e, true) , $e->getMessage() );
			}
			
			return;
		}
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("SOAP-вызов SearchGetParts2 сработал без исключения.");
		}
		
		
		if( count($GetPriceResult["Info"]["Errors"]) > 0 )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($GetPriceResult["Info"]["Errors"], true) );
			}
		}
		
		
		// Обработка результата
    	foreach($GetPriceResult['Parts'] as $this_partRecord)
    	{
    	    if($this_partRecord["MaxCount"] < 1)continue;
			
			$price = (float)$this_partRecord["Price"];

			//Обработка времени доставки:
			$timeToExe = $this_partRecord["Delivery"];
			
			$timeToExe = str_replace('<','',$timeToExe);
			$timeToExe = str_replace('>','',$timeToExe);
			$timeToExe = str_replace('=','',$timeToExe);
			$timeToExe = str_replace('+','',$timeToExe);
			$timeToExe = trim($timeToExe);
            $tmp = explode('-', $timeToExe);
            $timeToExe = (int)trim($tmp[0]);
            $timeToExe_2 = (int)trim($tmp[1]);
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($this_partRecord["Manuf"],
				$this_partRecord["Code"],
				$this_partRecord["Name"],
				$this_partRecord["MaxCount"],
				$price + $price*$markup,
				$timeToExe + $storage_options["additional_time"],
				$timeToExe_2 + $storage_options["additional_time"],
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
    	}//~foreach
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class avtoto_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new avtoto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>