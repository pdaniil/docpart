<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class emex_enclosure
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
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Информация для подключения к API. Для корректного подключения Вашего сайта к API поставщика нужно:<br>1. Попросить менеджера EMEX включить доступ к API для Вашего аккаунта. При этом Вам необходимо сообщить менеджеру IP-адрес Вашего сайта<br>2. Прописать в настройках подключения корректные ID пользователя и пароль от EMEX. Внимание! прописывается именно ID пользователя от сайта EMEX, а не логин");
		}
		
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient("http://ws.emex.ru/EmExService.asmx?wsdl", array('soap_version' => SOAP_1_2));//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP   FindDetailAdv3
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient. Внимание! У данного поставщика может действовать ограничение доступа к API по IP-адресу. Узнайте у менеджера поставщика, открыт ли доступ к API для Вашего сайта и для какого IP-адреса", print_r($e, true) , $e->getMessage() );
			}
			return;
		}

		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$params = array("login"=>$login, "password"=>$password, "detailNum"=>$article, "substLevel"=>"All", "substFilter"=>"None", "deliveryRegionType"=>"PRI", "minDeliveryPercent"=>null, "maxADDays"=>null, "minQuantity"=>null, "maxResultPrice"=>null, "maxOneDetailOffersCount"=>null, "detailNumsToLoad"=>null);
			
			
			$soap_am_result=$objClient->FindDetailAdv4($params);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-метод FindDetailAdv4() с параметрами:<br>".print_r($params, true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-процедуры FindDetailAdv4()", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
       
        //Получаем массив с объектами запчастей:
        $soap_am_result = $soap_am_result->FindDetailAdv4Result->Details->SoapDetailItem;
        

		//Прогон по массиву объектов и вывод записей
		for($i=0; $i<count($soap_am_result); $i++)
		{
			$this_partObject=$soap_am_result[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов
			
			$price = $this_partObject->ResultPrice;
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			//Параметры для SAO
			$paramsForSAO = array();
			$paramsForSAO["MakeLogo"] = $this_partObject->MakeLogo;
			$paramsForSAO["PriceLogo"] = $this_partObject->PriceLogo;
			$paramsForSAO["DestinationLogo"] = $this_partObject->DestinationLogo;
			
			$json = json_encode($paramsForSAO);
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($this_partObject->MakeName,
				$this_partObject->DetailNum,
				$this_partObject->DetailNameRus,
				$this_partObject->Quantity,
				$price + $price*$markup,
				$this_partObject->ADDays + $storage_options["additional_time"],
				$this_partObject->DeliverTimeGuaranteed + $storage_options["additional_time"],
				$this_partObject->PriceCountry,
				$this_partObject->LotQuantity,
				$this_partObject->DDPercent,
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price,
				$markup,
				2,0,0,'',$json,array("rate"=>$storage_options["rate"])
				);
			
			if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
        
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


$ob = new emex_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>