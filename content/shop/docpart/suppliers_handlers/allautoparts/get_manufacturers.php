<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/soap_transport.php");


class allautoparts_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $passwd = $storage_options["password"];
		$session_id = $storage_options["session_id"];
		/*****Учетные данные*****/
		
		
		$data['session_id'] = $session_id;
		$data['session_guid']='';
		$data['session_login']=$login;
		$data['session_password']=$passwd;
		$data['search_code']= $article;
		$data['showcross']=1;
		$data['periodmin']=0;
		$data['periodmax']=10;
		$data['instock']=0;
		
		
	   //Проверка загружены ли необходимые расширения
       $ext_soap = extension_loaded('soap');
       $ext_openssl = extension_loaded('openssl');
       $ext_SimpleXML = extension_loaded('SimpleXML');
       if (!($ext_soap && $ext_openssl && $ext_SimpleXML)) {
           
        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        {
            $DocpartSuppliersAPI_Debug->log_error("Отсутствуют необходимые расширения PHP (soap, openssl, SimpleXML)");
        }
       }
		
		$SOAP = new soap_transport();

		//Генерация запроса
		$requestXMLstring = $this->createSearchRequestXML($data);
		$errors = array();
		
		$responceXML = $SOAP->query('SearchOfferStep1', array('SearchParametersXml' => $requestXMLstring), $errors);
		
		//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug && count($errors) > 0 )
		{
			$errors[] = "ОШИБКА может возникать из-за ненадежного SSL-сертификата. Для устранения такой ошибки нужно отключить проверку SSL";
			
			$DocpartSuppliersAPI_Debug->log_error("Есть ошибка запроса", print_r($errors, true) );
		}
		
		if ($responceXML) 
		{
			$attr= $responceXML->rows->attributes();
			$data['session_guid'] = (string)$attr['SessionGUID'];
			$result = $this->parseSearchResponseXML($responceXML);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
    		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
    		{
    			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "Запрос через библиотеку soap_transport.php<br>Метод: SearchOfferStep1<br>Параметры: ".htmlentities($requestXMLstring), htmlentities($responceXML), print_r($result, true) );
    		}
		}
		
		if(is_array($result))
		{
			foreach ($result as &$value) 
			{
				$DocpartManufacturer = new DocpartManufacturer($value["ProducerName"],
					$value["ProductID"],
					$value["ProductName"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true
				);
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		
		$this->status = 1;
	}//~function __construct($article)
	
	
	//--------------------------------------------------------------------------------
	public function generateRandom($maxlen = 32) 
	{
		$code = '';
		while (strlen($code) < $maxlen) 
		{
			$code .= mt_rand(0, 9);
		}
		return $code;
	}
	//--------------------------------------------------------------------------------
	public function createSearchRequestXML($data) 
	{
		
		$session_info = $data['session_guid'] ? 
			'SessionGUID="'.$data['session_guid'].'"' : 
			'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';
		
		$xml = '<root>
				  <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.'/>
				  <Search>
					<Key>'.$data['search_code'].'</Key>
				  </Search>
				</root>';
		return $xml;
	}
	//-------------------------------------------------------------------------------- 
	public function parseSearchResponseXML($xml) 
	{
		$data = array();
		foreach($xml->rows->row as $row) 
		{
			$_row = array();
			foreach($row as $key => $field) 
			{
				$_row[(string)$key] = (string)$field;
			}
			$_row['Reference'] = $this->generateRandom(9);
			$data[] = $_row;
		}
		return $data;
	}
	//--------------------------------------------------------------------------------
};//~class allautoparts_enclosure

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new allautoparts_enclosure($_POST["article"], $storage_options );
exit(json_encode($ob));
?>