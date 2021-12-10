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
		
		$soap_result = $this->getManufacturers($article, $storage_options["login"], $storage_options["password"]);//Выполняем процедуру получения производителей
		
		if($soap_result == false)
		{
			$this->status = 0;
			return;
		}
		
		//Формируем массив брэндов:
        foreach ($soap_result as &$value) 
		{
			$DocpartManufacturer = new DocpartManufacturer($value["brand"],
			    $value["id"],
				$value["text"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);
			

			array_push($this->ProductsManufacturers, $DocpartManufacturer);
        }
        
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
	
	
	//Выполнение процедуры
	public function getManufacturers($article, $login, $password) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
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
		$address="https://dyadko.ru/pricedetals2.php";//Адрес для запроса
		$ch = curl_init($address);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$result=curl_exec($ch);//Получаем рузультат в виде xml
		

		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $address."<br>С параметрами:<br>".htmlentities($xml), htmlentities($result), print_r($php_object, true) );
		}
		
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Ошибка CURL-запроса при получении брендов:<br>".curl_error($ch) );
			}
		}
		
		
		
		//Формируем массив брэндов, у которых есть данный артикул:
		$res = array();
		$brands_result = new XMLReader;//Объект xml ридера
		$ok = $brands_result->xml($result);//Указываем полученный результат по брэндам
		
		if($ok)
		{
			$brands_result ->read();//Читаем полученный xml-результат
			if($brands_result->name == "result")//
			{

				while($brands_result ->read())//Читаем следующий узел
				{
					if($brands_result -> name == "detail")//Если этот узел "Деталь"
					{
						$thisPart_xml = "<part>".$brands_result -> readInnerXML()."</part>";//Читаем содержимое узла "Деталь" как строку (это тоже xml)
						
						$thisPart_reader = new XMLReader;//Объект xml ридера
						$thisPart_reader->xml($thisPart_xml);
						
						$brand = '';
						$article = '';
						$name = '';
						while($thisPart_reader -> read())//Бежим по узлам запчасти
						{
							if($thisPart_reader -> name == "producer")
							{
								if(empty($brand))
								{
									$brand = $thisPart_reader->readString();
								}
							}else if($thisPart_reader -> name == "article")
							{
								if(empty($article))
								{
									$article = $thisPart_reader->readString();
								}
							}else if($thisPart_reader -> name == "ident")
							{
								if(empty($name))
								{
									$name = $thisPart_reader->readString();
								}
							}
						}
						
						
						if($brand != "")
						{
							array_push($res, array('id' => $brand, 'brand' => $brand, 'text' => $name));//Добавляем брэнд в массив
						}
						
					}
				}
			}
		}//~if($ok)
		else
		{			
			$res = array();
		}
		
		return $res;
	}
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