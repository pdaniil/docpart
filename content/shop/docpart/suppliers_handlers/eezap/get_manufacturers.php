<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class eezap_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login			= $storage_options["login"];
		$password		= $storage_options["password"];
		$article_search	= $article;
		
		try
		{
			$client = new SoapClient(
				"http://eezap.ru/webservice/search.php?WSDL",
				array('soap_version'   => SOAP_1_2, 'trace'=>1, "connection_timeout" => 7)
			);			
			
			$params	= array();
			
			$params["login"]		= $login;
			$params["password"]	= $password;
			$params["make_logo"]	= '';
			$params["detail_num"]	= $article_search;
			$params["find_subs"]	= '1';
			$params["sort"]		= '';
			$params["currency"]	= '';
			
			$result			= $client->getFindByDetail($params);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-вызов метода getFindByDetail() с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", print_r($result, true) );
			}
		
		
			$result_array	= $result->getFindByDetailResult->FindByDetail;

			foreach($result_array as $item)
			{	
				$DocpartManufacturer = new DocpartManufacturer($item->make_name,
					$item->make_logo,
					$item->description_rus,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true,
					""
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);			
			}

			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
			}
			
			$this->status = 1;
		}
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


$ob = new eezap_enclosure( $_POST["article"], $storage_options );	
exit(json_encode($ob));
?>