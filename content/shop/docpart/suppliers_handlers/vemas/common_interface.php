<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class vemas_enclosure
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
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient("http://api.vemas.ru:7777/VemasSearch.svc?wsdl", array('soap_version' => SOAP_1_1));//Создаем SOAP-клиент
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


		$params = array("Number"=>$article, "Manufacturer"=>'', "Login"=>$login, "Password"=>$password);
		
		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$soap_am_result=$objClient->PriceByNumber($params);//Запускаем SOAP-процедуру и получаем результат ее выполнения
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода PriceByNumber() с параметрами ".print_r($params, true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-метода PriceByNumber() с параметрами ".print_r($params, true), print_r($e, true) , $e->getMessage() );
			}
			return;
		}
       
        //Получаем массив с объектами запчастей:
        $soap_am_result = $soap_am_result->PriceByNumberResult->SearchResult;
		
		//Прогон по массиву объектов и вывод записей
		for($i=0; $i<count($soap_am_result); $i++)
		{
			$this_partObject=$soap_am_result[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов
			
			$price = $this_partObject->Price;
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($this_partObject->Manufacturer,
				$this_partObject->Number,
				$this_partObject->Name,
				$this_partObject->PartCount,
				$price + $price*$markup,
				$this_partObject->Duration + $storage_options["additional_time"],
				$this_partObject->Duration + $storage_options["additional_time"],
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


$ob = new vemas_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>