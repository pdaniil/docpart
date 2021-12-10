<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class x_autobody_enclosure
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
		if( $storage_options["subdomain"] == "ekb" )
		{
			$host_api = "http://ekbautobody.azurewebsites.net/Service.svc?singleWsdl";
		}
		else//Для spb
		{
			$host_api = "http://spbautobody.azurewebsites.net/Service.svc?singleWsdl";
		}
		/*****Учетные данные*****/
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("SOAP-сервер: ".$host_api);
		}
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient($host_api, array('soap_version' => SOAP_1_1));//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP   FindDetailAdv3
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		$params = array("login"=>$login, "password"=>$password, "article"=>$article);

		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$soap_am_result=$objClient->GetPrice($params);//Запускаем SOAP-процедуру и получаем результат ее выполнения
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода GetPrice() с параметрами ".print_r($params, true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetPrice() с параметрами ".print_r($params, true), print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		$soap_am_result = json_encode($soap_am_result);
		$soap_am_result = json_decode($soap_am_result, true);
	   
        //Получаем массив с объектами запчастей:
		if( isset($soap_am_result["GetPriceResult"]) )
		{
			$GetPriceResult = $soap_am_result["GetPriceResult"];
			foreach( $GetPriceResult AS $PriceItem )
			{
				$price = $PriceItem["Cost"];
			
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($PriceItem["Manufacturer"],
					$PriceItem["Article"],
					$PriceItem["Title"],
					$PriceItem["Qty"],
					$price + $price*$markup,
					$PriceItem["Delivery"] + $storage_options["additional_time"],
					$PriceItem["Delivery"] + $storage_options["additional_time"],
					"",
					1,
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',"",array("rate"=>$storage_options["rate"])
					);
				
				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}
		}
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
        
	}//~function __construct($article)
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new x_autobody_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>