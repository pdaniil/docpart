<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class eezap_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturer, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login 			= $storage_options["login"];
		$password 		= $storage_options["password"];
		$article_search	= $article;
		
		try
		{
			$client = new SoapClient(
				"http://www.eezap.ru/webservice/search.php?WSDL",
				array('trace'=>1, "connection_timeout" => 10)
			);
			
			$params	= array();
			
			$params["login"]		= $login;
			$params["password"]	= $password;
			$params["detail_num"]	= $article_search;
			$params["find_subs"]	= '1';
			$params["sort"]		= '';
			$params["currency"]	= '';
			
			$result 		= $client->getFindByDetail($params);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода getFindByDetail() с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", print_r($result, true) );
			}
			
			$result_array	= $result->getFindByDetailResult->FindByDetail;
			
			foreach($result_array as $item)
			{
				$price	= $item->price;
				$markup = $storage_options["markups"][$price];
				
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$timeToExe			= $item->delivery_time + $storage_options["additional_time"];
				$timeToExeGuaranted	= $item->delivery_time_guar + $storage_options["additional_time"];
				$priceForCustomer	= $price + $price*$markup;
				
				
				
				$DocpartProduct = new DocpartProduct($item->make_name, 
					$item->detail_num,
					$item->description_rus,
					$item->quantity,
					$priceForCustomer,
					$timeToExe,
					$timeToExeGuaranted,
					$item->supplier,
					1,
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,
					0,
					0,
					"",
					"",
					array("rate"=>$storage_options["rate"])
				);
				
				
				if($DocpartProduct->valid)
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
			
		}//~try{}
		catch(SoapFault $e)
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение. Нужно сообщить менеджеру поставщика IP-адрес Вашего сайта для включения доступа к API. Кроме этого - проверьте корректность логина и пароля", print_r($e, true) , $e->getMessage() );
			}
		}
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


$ob =  new eezap_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>