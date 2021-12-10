<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class etsp_enclosure
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
		

		try
		{
			//Создание объекта клиента
			$client = new soapclient("http://ws.etsp.ru/Security.svc?singleWsdl", array("trace"=>1, "encoding" => "utf-8", "exceptions"=>0));
		}
		catch (SoapFault $e)
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для получения сессии", print_r($e, true) , $e->getMessage() );
			}
			return;
		}


		$params = array(array(
			'Login' => $login,
			'Password' => $password));

			try
			{
				//Получение сессии
				$response = $client->__soapCall('Logon', $params);
				

				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение сессии", "SOAP-вызов метода Logon с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", htmlentities(print_r($response, true)) );
				}
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода Logon()", print_r($e, true) , $e->getMessage() );
				}
				return;
			}



		$hashSession = $response->LogonResult;
		
		
		if (strpos($hashSession, 'error_message'))
		{
			$xml = simplexml_load_string($hashSession);
			$error_message = $xml->xpath("/root/error_message");

			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка при получении сессии", print_r($error_message,true) );
			}
		}
		else
		{
			// Поиск по артикулу
			try
			{
				//Создаем новый SOAP-клиент для поиска по артикулу
				$client = new soapclient("http://ws.etsp.ru/Search.svc?singleWsdl", array("trace"=>1, "encoding" => "utf-8", "exceptions"=>0));
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для поиска по артикулу", print_r($e, true) , $e->getMessage() );
				}
				return;
			}

			$params = array(array(
				'Text' => $article,
				'HashSession' => $hashSession));

			try
			{
				// Поиск по артикулу
				$response = $client->__soapCall('SearchBasic', $params);
				  
				  
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу", "SOAP-вызов метода SearchBasic с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", htmlentities(print_r($response, true)) );
				}
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода SearchBasic()", print_r($e, true) , $e->getMessage() );
				}
				return;
			}

			$searchResult = $response->SearchBasicResult;
			
			try
			{
				//Создаем новый SOAP-клиент для поиска по артикулу
				$client = new soapclient("http://ws.etsp.ru/PartsRemains.svc?singleWsdl", array("trace"=>1, "encoding" => "utf-8", "exceptions"=>0));
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для поиска PartsRemains", print_r($e, true) , $e->getMessage() );
				}
				return;
			}


			try
			{
				// Поиск по артикулу
				$response = $client->__soapCall('GetStoragesInfo', array(array('HashSession' => $hashSession)));

				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу", "SOAP-вызов метода GetStoragesInfo с параметрами:<br>".print_r($hashSession,true), "См. ответ API после обработки", htmlentities(print_r($response, true)) );
				}
			}
			catch (SoapFault $e)
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetStoragesInfo()", print_r($e, true) , $e->getMessage() );
				}
				return;
			}

      $storageResult = $response->GetStoragesInfoResult;

			
			if (strpos($searchResult, 'error_message'))
			{
				if($storage_options["is_debug"] == true)
				{
					echo $searchResult.'<br />';
				}
			}	
			else
			{
				$xml = simplexml_load_string($searchResult);
				
				// ищем остатки по складам
				foreach ($xml->xpath("/root/part") as $part)
				{
					$detal_article = (string)$part->unique_number;// Артикул 
					$detal_name = (string)$part->name;// Наименование
					$detal_text = (string)$part->note;// Описание
					$code_article = (string)$part->code;// Описание
					
					
					if(isset($code_article) && !empty($code_article))
					{

						try
						{
							//Создаем новый SOAP-клиент для поиска по артикулу
							$client = new soapclient("http://ws.etsp.ru/PartsRemains.svc?singleWsdl", array("trace"=>1, "encoding" => "utf-8", "exceptions"=>0));
						}
						catch (SoapFault $e)
						{
							//ЛОГ - [ИСКЛЮЧЕНИЕ]
							if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
							{
								$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании soapclient для поиска PartsRemains", print_r($e, true) , $e->getMessage() );
							}
							return;
						}

						$params = array(array(
							'Code' => $code_article,
							'ShowRetailRemains' => true,
							'ShowOutsideRemains' => true,
							'ShowPriceByQuantity' => true,
							'HashSession' => $hashSession));


						try
						{
							// Поиск по артикулу
							$response = $client->__soapCall('GetPartsRemainsByCode2', $params);

							//ЛОГ [API-запрос] (вся информация о запросе)
							if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
							{
								$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу", "SOAP-вызов метода GetPartsRemainsByCode2 с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", htmlentities(print_r($response, true)) );
							}
						}
						catch (SoapFault $e)
						{
							//ЛОГ - [ИСКЛЮЧЕНИЕ]
							if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
							{
								$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetPartsRemainsByCode2()", print_r($e, true) , $e->getMessage() );
							}
							return;
						}
						
						
						$responseResult = $response->GetPartsRemainsByCode2Result;
						
						$xml_sklad = simplexml_load_string($responseResult);

						foreach ($xml_sklad->xpath("sklad_remains/item") as $part_sklad)
						{
						    $storage_arr = simplexml_load_string($storageResult);
						    
                  // Для каждого склада создадим по объекту товара
                  if(!empty($storage_arr)) {
                      
                      foreach($storage_arr as $storage) {
                          
                          $storage = (array)$storage;

                          $detal_manufacturer = (string)$part_sklad->manufacturer_name;// Производитель
                          $detal_article = (string)$part_sklad->manufacturer_number;

                          // if(($detal_manufacturer !== $manufacturer) && ($article == (string)$part_sklad->manufacturer_number)) continue;
                                    
        							$detal_storage_id = (string)$part_sklad->storage_id;// id склада
        							$detal_storage_name = (string)$part_sklad->storage_name;// название склада
        							$detal_exist = $part_sklad->quantity;// наличие
        							
        							if($detal_storage_id !== $storage['storage_id']) continue;
        							
        							$detal_delivery = 0;
        							$storages_delivery = (array)$storage["deliveries"];
        							$storages_delivery = $storages_delivery["delivery"];
        							
        							if(isset($storages_delivery)) {
        							    foreach($storages_delivery as $storage_delivery) {
        							        $storage_delivery = (array)$storage_delivery;
        							        if (strpos($storage_delivery['name'], "Наша") !== false) {
                                                  $detal_delivery = $storage_delivery['estimated_delivery_datetime_unix'];
                                            }
                                            continue;
        							    }
        							}
        							
        							
        							//Срок доставки
            						$time_to_exe = 0;
            						$time_arrive = new DateTime();
            						
            						if($detal_delivery == 0) {
            						    $time_arrive->setTime( 0, 0 );
            						} else {
            						    $time_arrive = DateTime::createFromFormat('U', $detal_delivery);//Время поступления
            						}

            						$time_now = new DateTime();
            						$time_now->setTime( 0, 0 ); //Устанавливаем 00:00
            						$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
            						$time_to_exe = $time_interval->days; //Получаем дни (int)
        							
        							// Если наличие = ? то поставим 5
        							if($detal_exist == '?')
        							{
        								$detal_exist = 5;
        							}
        							
        							$price = $part_sklad->price;// цена
        							$price = (float)str_replace(',','.',$price);// Заменяем запятую на точку
        							
											
											
        							// Готовим объект docpart/DocpartProduct
        							//Наценка
        							$markup = $storage_options["markups"][(int)$price];
        							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
        							{
        								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
        							}
        							
        							//Создаем объек товара и добавляем его в список:
        							$DocpartProduct = new DocpartProduct(
        								$detal_manufacturer,
        								$detal_article,
        								$detal_article,
        								$detal_exist,
        								$price + $price*$markup,
        								$time_to_exe + $storage_options["additional_time"],
        								$time_to_exe + $storage_options["additional_time"],
        								$detal_storage_id,
        								1,
        								$storage_options["probability"],
        								$storage_options["office_id"],
        								$storage_options["storage_id"],
        								$storage_options["office_caption"],
        								$storage_options["color"],
        								$storage_options["storage_caption"],
        								$price,
        								$markup,
        								2,0,0,'',array("storage" => array("id" => $storage["storage_id"], "name" => $storage["storage_name"], "delivery" => $detal_delivery)),array("rate"=>$storage_options["rate"])
        							);
        							
        							if($DocpartProduct->valid == true)
        							{
        								array_push($this->Products, $DocpartProduct);
        							}

                    }
                  }
                  
                  // Здесь конец цикла по складу

						}
					}
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
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new etsp_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>