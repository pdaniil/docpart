<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class Customer
{
	public $UserName;
	public $Password;
	public $SubCustomerId;
	public $CustomerId;
}


class emexdwc_ae_enclosure
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
			$objClient = new SoapClient("http://emexonline.com:3000/MaximaWS/service.wsdl", array('soap_version' => SOAP_1_2));//Создаем SOAP-клиент
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
		
		
		$customer = new Customer();
		$customer->UserName = $login;
		$customer->Password = $password;
		$params = array("Customer"=>$customer, "DetailNum"=>$article, "ShowSubsts" => 1);
		
		try
		{
			$soap_am_result = $objClient->SearchPart($params);
			
			$soap_am_result_str = json_encode($soap_am_result);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода SearchPart() с параметрами:<br>".print_r($params,true), $soap_am_result_str, print_r(json_decode($soap_am_result_str, true), true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода SearchPart()", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		
		$soap_am_result = json_encode($soap_am_result);
		$soap_am_result = json_decode($soap_am_result, true);
		
		//var_dump($soap_am_result);
		
		$FindByNumber = $soap_am_result["SearchPartResult"]["FindByNumber"];
		
		for( $i=0 ; $i < count($FindByNumber) ; $i++ )
		{
			$price = $FindByNumber[$i]["Price"];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($FindByNumber[$i]["MakeName"],
				$FindByNumber[$i]["DetailNum"],
				$FindByNumber[$i]["MakeName"],
				$FindByNumber[$i]["Available"],
				$price + $price*$markup,
				$FindByNumber[$i]["GuaranteedDay"] + $storage_options["additional_time"],
				$FindByNumber[$i]["GuaranteedDay"] + $storage_options["additional_time"],
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
				2,0,0,'',null,array("rate"=>$storage_options["rate"])
				);
			
			if($DocpartProduct->valid == true || true)
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


$ob = new emexdwc_ae_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>