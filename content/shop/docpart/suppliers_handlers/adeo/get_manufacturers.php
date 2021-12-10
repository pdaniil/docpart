<?php
/*
Скрипт для реализации первого шага протокола проценки
*/
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class adeo_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список
	
	public $client = null;
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		//XML-данные для запроса списка брэндов для артикула
		$xml='<?xml version="1.0" encoding="UTF-8" ?>
		 <message>
		   <param>
			 <action>price</action>
			 <login>'.$login.'</login>
			 <password>'.$password.'</password>
			 <code>'.$article.'</code>
			 <sm>1</sm>
		  </param>
		</message>';
		
		
		$data = array('xml' => $xml);
		$address="http://adeo.pro/pricedetals2.php";//Адрес для запроса
		$ch = curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$result=curl_exec($ch);//Получаем рузультат в виде xml
		
		//Формируем массив брэндов, у которых есть данный артикул:
		$brands = simplexml_load_string($result);//Объект xml ридера
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://adeo.pro/pricedetals2.php<br>Объект запроса (XML):<br>".htmlentities($xml), htmlentities($result), print_r($brands, true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
			}
		}
		
		
		if(!empty($brands)) {
		    
		    foreach($brands as $brand) {
		        
                $value = (array)$brand;
		        
		        $DocpartManufacturer = new DocpartManufacturer($value['producer'],
			        0,
				    $value['ident'],
				    $storage_options['office_id'],
			    	$storage_options['storage_id'],
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

};//~class adeo_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new adeo_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>