<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class adeo_enclosure
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
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$xml_result = simplexml_load_string($result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $address."<br>XML-параметры:<br>".htmlentities($xml), htmlentities($result), print_r($xml_result, true) );
		}
		
		
		//Формируем массив брэндов, у которых есть данный артикул:
		$brands_array = array();
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
						while($thisPart_reader -> read())//Бежим по узлам запчасти
						{
							if($thisPart_reader -> name == "producer")
							{
								$thisProduser = $thisPart_reader->readString();
								if($thisProduser != "")
								{
									array_push($brands_array, $thisProduser);//Добавляем брэнд в массив
								}
							}
						}
					}
				}
			}
		}//~if($ok)
		else
		{			
			$this->result = false;//Процесс выполнен с ошибкой
			return;
		}
		
		
		//Есть массив брэндов, теперь делаем запрос товаров:
		for($i=0; $i < count($brands_array); $i++)
		{
		
			//XML-данные для запроса списка товаров по Артикулу и Брэнду
			$xml='<?xml version="1.0" encoding="UTF-8" ?>
			 <message>
			   <param>
				 <action>price</action>
				 <login>'.$login.'</login>
				 <password>'.$password.'</password>
				 <code>'.$article.'</code>
				 <brand>'.$brands_array[$i].'</brand>
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
			
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$xml_result = simplexml_load_string($result);
				
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brands_array[$i], $address."<br>XML-параметры:<br>".htmlentities($xml), htmlentities($result), print_r($xml_result, true) );
			}
			
			
			
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
					
						if($parts_result -> name == "detail")//Если этот узел "Деталь"
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
							//Данные прочитали - создаем объект:
							//exit;
							//echo $manufacturer." ".$article_res." ".$name." ".$exist." ".$timeToExe." ".$price." ".$storage."<br>";
								
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
				
				
				//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
				$DocpartSuppliersAPI_Debug->log_simple_message("Ошибка инициализации XMLReader");
				
				return;
			}
			
		}//~for
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
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