<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class avdmotors_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Внимание! У API поставщика может действовать ограничение по IP-адресу");
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
		
		$supplier_items = array(); //Данные от поставщика
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient("http://ws1.avdmotors.ru/AvdUserService.svc?wsdl", array('soap_version' => SOAP_1_1));//Создаем SOAP-клиент
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
		
		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$GetPrice=$objClient->GetOriginalPrice(array("login"=>$login, "password"=>$password, "number"=>$article, "isOriginal"=>false));//Запускаем SOAP-процедуру и получаем результат ее выполнения
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-процедуры GetPrice", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-процедура GetPrice и параметрами: ".print_r(array("login"=>$login, "password"=>$password, "number"=>$article, "isOriginal"=>false), true), "См. далее ответ API после обработки", print_r($GetPrice, true) );
		}
		
		
		
		//Получаем массив с объектами запчастей:
        $PriceItem = $GetPrice->GetOriginalPriceResult->PriceItems->PriceItem3;
		
		
		if ( is_array( $PriceItem ) ) {
			
			$supplier_items = $PriceItem;
			
		} else {
			
			$supplier_items[] = $PriceItem;
			
		}
  
		//Прогон по массиву объектов и вывод записей
		if(!empty($supplier_items))
		{
			foreach($supplier_items as $item)
			{
				
				$price = trim($item->Price);
				$price = str_replace(' ', '', $price);
				$price = str_replace(',', '.', $price);
				$price = (float)$price;
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct(
					$item->CatalogName,
					$item->ItemNumber,
					$item->ItemName,
					$item->Quantity,
					$price + $price*$markup,
					$item->SupplierPeriod + $storage_options["additional_time"],
					$item->SupplierPeriod + $storage_options["additional_time"],
					$item->SupplierName,
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


$ob = new avdmotors_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>