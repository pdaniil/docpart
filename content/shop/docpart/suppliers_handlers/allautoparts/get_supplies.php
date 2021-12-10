<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/allautoparts/soap_transport.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class allautoparts_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		

		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $passwd = $storage_options["password"];
		$session_id = $storage_options["session_id"];
		/*****Учетные данные*****/
		
		$data['session_id'] = $session_id;
		$data['session_guid']='';
		$data['session_login']=$login;
		$data['session_password']=$passwd;
		$data['search_code']= $manufacturers[0]['manufacturer_id'];
		$data['showcross']=1;
		$data['periodmin']=-1;
		$data['periodmax']=20;
		$data['instock']=1;
		
		
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
		
		$responceXML = $SOAP->query('SearchOfferStep2', array('SearchParametersXml' => $requestXMLstring), $errors);
		
		
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
    			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и ID производителя ".$manufacturers[0]['manufacturer_id']."(".$manufacturers[0]['manufacturer'].")", "Запрос через библиотеку soap_transport.php<br>Метод: SearchOfferStep2<br>Параметры: ".htmlentities($requestXMLstring), htmlentities($responceXML), print_r($result, true) );
    		}
		}

		for($i=0; $i < count($result); $i++)
		{
			$price = (float)$result[$i]["Price"];
			//Обработка времени доставки:
			$timeToExe = (int)$result[$i]["PeriodMin"];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			if($result[$i]["IsCross"] == '0')
			{
				$manufacturer = $result[$i]["ManufacturerName"];
				$article = $result[$i]["CodeAsIs"];
			}
			else
			{
				$manufacturer = $result[$i]["AnalogueManufacturerName"];
				$article = $result[$i]["AnalogueCode"];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($manufacturer,
				$article,
				$result[$i]["ProductName"],
				$result[$i]["Quantity"],
				$price + $price*$markup,
				(int)$result[$i]["PeriodMin"] + $storage_options["additional_time"],
				(int)$result[$i]["PeriodMax"] + $storage_options["additional_time"],
				$result[$i]["OfferName"],
				$result[$i]["LotBase"],
				$storage_options["probability"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price,
				$markup,
				2,0,0,'', json_encode(array("OfferName"=>$result[$i]["OfferName"], "LotBase"=>$result[$i]["LotBase"], "LotType"=>$result[$i]["LotType"], "PriceListDiscountCode"=>$result[$i]["PriceListDiscountCode"])),array("rate"=>$storage_options["rate"])
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
					 <ProductID>'.$data['search_code'].'</ProductID>
					 <StocksOnly>0</StocksOnly>
					 <InStock>'.$data['instock'].'</InStock>
					 <ShowCross>'.$data['showcross'].'</ShowCross>
					 <PeriodMin>'.$data['periodmin'].'</PeriodMin>
					 <PeriodMax>'.$data['periodmax'].'</PeriodMax>
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




$ob = new allautoparts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
exit(json_encode($ob));
?>