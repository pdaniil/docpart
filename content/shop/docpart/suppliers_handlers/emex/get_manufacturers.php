<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class emex_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
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
			$params = array(
				"login"=>$login, 
				"password"=>$password, 
				"detailNum"=>$article,
				"substLevel"=>"OriginalOnly", 
				"substFilter"=>"None", 
				"deliveryRegionType"=>"PRI", 
				"minDeliveryPercent"=>null, 
				"maxADDays"=>null, 
				"minQuantity"=>null, 
				"maxResultPrice"=>null, 
				"maxOneDetailOffersCount"=>null, 
				"detailNumsToLoad"=>null
			);
			
			$soap_am_result=$objClient->FindDetailAdv5($params);//Запускаем SOAP-процедуру и получаем результат ее выполнения
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-метод FindDetailAdv5() с параметрами:<br>".print_r($params, true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
			
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-процедуры FindDetailAdv5()", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
	   
        //Получаем массив с объектами запчастей:
        $details_items = $soap_am_result->FindDetailAdv5Result->Details->DetailItem;

		$locale_hash = array();
		
		//Прогон по массиву объектов и вывод записей
		for ( $i=0; $i < count( $details_items ); $i++ ) {
			
			$this_partObject=$details_items[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов
			
			$manufacturer 				= $this_partObject->MakeName;
			$manufacturer_id 			= $this_partObject->MakeLogo;
			$name						= $this_partObject->DetailNameRus;
			$synonyms_single_query	= true;
			
			$hash = md5( $manufacturer );
			
			if ( ! isset( $locale_hash[$hash] ) ) {
				
				$DocpartManufacturer = new DocpartManufacturer($manufacturer,
					$manufacturer_id,
					$name,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$synonyms_single_query
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
				
				$locale_hash[$hash] = true;
				
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



try 
{
	
	$ob = new emex_enclosure($_POST["article"], $storage_options);
	$json = json_encode($ob);
	
	if ( ! $json ) 
	{
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
	}
	
} 
catch (Exception $e) 
{
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	} 
}

exit($json);
?>