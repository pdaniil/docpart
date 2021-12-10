<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");



class adeo_enclosure
{
	public $result;
	public $client = null;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		foreach($manufacturers as $manufacturer){
			$soap_result = $this->getPriceByNum ($article, $manufacturer['manufacturer_id'], $storage_options["login"], $storage_options["password"]);//Выполняем процедуру получения товаров по артикулу
			
			if($soap_result == false)
			{
				$this->result = 0;
				return;
			}
			$result=$soap_result;
			//Формируем массив товаров:
			$parts_result = new XMLReader;//Объект xml ридера
			$ok = $parts_result->xml($result);//Указываем полученный результат по товарам
			if($ok)
			{
				$parts_result ->read();//Читаем полученный xml-результат
				if($parts_result->name == "result")//
				{
					while($parts_result ->read())//Читаем следующий узел
					{
						if($parts_result ->name == "detail")//Если этот узел "Деталь"
						{
							
							if($parts_result -> readInnerXML() == "") continue;
							$thisPart_xml = "<part>".$parts_result -> readInnerXML()."</part>";//Читаем содержимое узла "Деталь" как строку (это тоже xml)
							
							$thisPart_reader = new XMLReader;//Объект xml ридера
							$thisPart_reader->xml($thisPart_xml);
							
							
							//ПОЛЯ ДЛЯ ОБЪЕКТА ТОВАРА
							$manufacturer = "";
							$article_res = "";
							$name = "";
							$exist = 0;
							$price = 0;
							$timeToExe = 0;
							$timeToExe_min = 0;
							$storage = "";
							
							$sao_code = "";
							$b_id = "";
							
							while($thisPart_reader -> read())//Бежим по узлам запчасти
							{
								//echo $thisPart_reader -> name."<br>";
								if($thisPart_reader -> name == "producer" && $manufacturer == "")
								{
									$manufacturer = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "code" && $article_res == "")
								{
									$article_res = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "caption" && $name == "")
								{
									$name = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "rest" && $exist == 0)
								{
									$exist = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "delivery" && $timeToExe == 0)
								{
									$timeToExe = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "deliverydays" && $timeToExe_min == 0)
								{
									$timeToExe_min = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "price" && $price == 0)
								{
									$price = (float)$thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "stock" && $storage == "")
								{
									$storage = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "code" && $sao_code == "")
								{
									$sao_code = $thisPart_reader->readString();
								}
								if($thisPart_reader -> name == "b_id" && $b_id == "")
								{
									$b_id = $thisPart_reader->readString();
								}
							}
								
							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							{
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}
							
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($manufacturer,
								$article_res,
								$name,
								$exist,
								$price + $price*$markup,
								$timeToExe_min + $storage_options["additional_time"],
								$timeToExe + $storage_options["additional_time"],
								$storage,
								1,
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'',json_encode(array("code"=>$sao_code, "b_id"=>$b_id)),array("rate"=>$storage_options["rate"])
								);
							
							//var_dump($DocpartProduct);
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
						}
					}
				}
			}//~if($ok)
			else
			{
				$this->result = 0;//Процесс выполнен с ошибкой
				return;
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
	
	
	
	//Выполнение процедуры SOAP
	public function getPriceByNum($article, $manufacturer_id, $login, $password) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//XML-данные для запроса списка товаров по Артикулу и Брэнду
		$xml='<?xml version="1.0" encoding="UTF-8" ?>
		 <message>
		   <param>
			 <action>price</action>
			 <login>'.$login.'</login>
			 <password>'.$password.'</password>
			 <code>'.$article.'</code>
			 <brand>'.$manufacturer_id.'</brand>
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
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и ID производителя ".$manufacturer_id, $address."<br>С параметрами:<br>".htmlentities($xml), htmlentities($result), print_r($php_object, true) );
		}
		
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Ошибка CURL-запроса при получении остатков:<br>".curl_error($ch) );
			}
		}
		
		
		
		return $result;
		
	}//~public function getPriceByNum($detailNum = '50610TA0A10')
};//~class adeo_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );



$ob = new adeo_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>