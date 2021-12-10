<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class burjauto_enclosure
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
			$objClient = new SoapClient("https://burjauto.com/webservice/search.php?wsdl", array('trace'=>1, "connection_timeout" => 5));//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP   FindDetailAdv3
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient, возможно, поставщику нужно сообщить IP-адрес Вашего сайта", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		
		
		
		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$soap_am_result = $objClient->getFindByDetail(array("login"=>$login, "password"=>$password, "detail_num"=>$article));//Запускаем SOAP-процедуру и получаем результат ее выполнения
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода getFindByDetail", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода getFindByDetail()", "См. ответ API после обработки", print_r($soap_am_result, true) );
		}
		
		
        //Получаем массив с объектами запчастей:
        $soap_am_result = $soap_am_result->getFindByDetailResult->FindByDetail;
		
		//Прогон по массиву объектов и вывод записей
		if( is_array($soap_am_result))
		{
			for($i=0; $i<count($soap_am_result); $i++)
			{
				$this_partObject=$soap_am_result[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов
				
				$price = $this_partObject->price;
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($this_partObject->make_name,
					$this_partObject->detail_num,
					$this_partObject->description_rus,
					$this_partObject->quantity,
					$price + $price*$markup,
					$this_partObject->delivery_time + $storage_options["additional_time"],
					$this_partObject->delivery_time_guar + $storage_options["additional_time"],
					$this_partObject->supplier,
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
		else //Единственная позиция
		{
			$price = $soap_am_result->price;
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($soap_am_result->make_name,
				$soap_am_result->detail_num,
				$soap_am_result->description_rus,
				$soap_am_result->quantity,
				$price + $price*$markup,
				$soap_am_result->delivery_time + $storage_options["additional_time"],
				$soap_am_result->delivery_time_guar + $storage_options["additional_time"],
				$soap_am_result->supplier,
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



$ob = new burjauto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>