<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class rossko_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		$this->no_error = false;//По умолчанию
        
        
		/*****Учетные данные*****/
        $KEY1 = $storage_options["key1"];
        $KEY2 = $storage_options["key2"];
        $delivery_id = $storage_options["delivery_id"];
        $address_id = $storage_options["address_id"];
		/*****Учетные данные*****/


		$connect = array(
			'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetSearch',
			'options' => array(
				'connection_timeout' => 1,
				'trace' => true
			)
		);

		$param = array(
			'KEY1' => $KEY1,
			'KEY2' => $KEY2,
			'text' => $article,
			'delivery_id' => $delivery_id,
			'address_id'  => $address_id
		);
        
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient($connect['wsdl'], $connect['options']);//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient с параметрами ".print_r(array($KEY1, $KEY2, $article),true), print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		
        
		//Запускаем SOAP-процедуру
		try
		{
			$soap_am_result = $objClient->GetSearch($param);

			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-вызов метода GetSearch() с параметрами ".print_r(array('KEY1'=>$KEY1, 'KEY2'=>$KEY2, 'TEXT'=>$article),true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetSearch() с параметрами ".print_r(array('KEY1'=>$KEY1, 'KEY2'=>$KEY2, 'TEXT'=>$article),true), print_r($e, true) , $e->getMessage() );
			}
			return;
		}

        //Приводим в нормальный вид:
        $soap_am_result = json_encode($soap_am_result);
        $soap_am_result = json_decode($soap_am_result, true);
        
		
        //Получаем объект SearchResult
        $SearchResult = $soap_am_result["SearchResult"];

        if($SearchResult["success"] != true)
        {
            return;
        }
        
		
        $PartsList = $SearchResult["PartsList"];//Список объектов (элемент PartsList)
        
        $Part = $PartsList["Part"];//Список запчастей (элемент Part)
        
		
		
        if(isset($Part["guid"]))
        {
            $Part = array($Part);
        }
        
	

        //По списку объктов типа Part
        for($i=0; $i<count($Part); $i++)
        {
            //Запрошенная запчасть:
            if(isset($Part[$i]["stocks"]))
            {
                //Получаем конкретные товары
                $StocksList = $Part[$i]["stocks"];
                $Stock = $StocksList["stock"];

                if($Stock["price"] == false)
                {
                    for($s=0; $s < count($Stock); $s++)
                    {
						//ДЛЯ SAO
						
						$json = json_encode($Stock[$s]);
                       
						$price = $Stock[$s]["price"];

						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						
						$min_order = (int)$Stock[$s]["multiplicity"];
						if( $min_order < 1 )
						{
							$min_order = 1;
						}
						
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($Part[$i]["brand"],
							$Part[$i]["partnumber"],
							$Part[$i]["name"],
							$Stock[$s]["count"],
							$price + $price*$markup,
							$Stock[$s]["delivery"] + $storage_options["additional_time"],
							$Stock[$s]["delivery"] + $storage_options["additional_time"],
							NULL,
							$min_order,
							$storage_options["probability"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',$json,array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
                    }
                }
                else//Если в Stock один элемент
                {
					$price = $Stock["price"];
					
					//ДЛЯ SAO
						
					$json = json_encode($Stock);
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$min_order = (int)$Stock["multiplicity"];
					if( $min_order < 1 )
					{
						$min_order = 1;
					}
					
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($Part[$i]["brand"],
						$Part[$i]["partnumber"],
						$Part[$i]["name"],
						$Stock["count"],
						$price + $price*$markup,
						$Stock["delivery"] + $storage_options["additional_time"],
						$Stock["delivery"] + $storage_options["additional_time"],
						NULL,
						$min_order,
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2,0,0,'',$json,array("rate"=>$storage_options["rate"])
						);
					
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
                }
            }
            
			
				
            //Кроссы:
            if(isset($Part[$i]["crosses"]))
            {
                $CrossPart = $Part[$i]["crosses"]["Part"];//Список объектов Part
                
                if(isset($CrossPart["guid"]))
                {
                    $CrossPart = array($CrossPart);
                }
                
                for($c=0; $c < count($CrossPart); $c++)
                {
                    if(isset($CrossPart[$c]["stocks"]))
                    {
                        //Получаем конкретные товары
                        $StocksList = $CrossPart[$c]["stocks"];
                        $Stock = $StocksList["stock"];
                        
                        
                        if($Stock["price"] == false)
                        {
							
                            for($s=0; $s < count($Stock); $s++)
                            {
                               
								$price = $Stock[$s]["price"];
										
								//ДЛЯ SAO
						
								$json = json_encode($Stock[$s]);
								
								//Наценка
								$markup = $storage_options["markups"][(int)$price];
								if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
								{
									$markup = $storage_options["markups"][count($storage_options["markups"])-1];
								}
								
								$min_order = (int)$Stock[$s]["multiplicity"];
								if( $min_order < 1 )
								{
									$min_order = 1;
								}
								
								//Создаем объек товара и добавляем его в список:
								$DocpartProduct = new DocpartProduct($CrossPart[$c]["brand"],
									$CrossPart[$c]["partnumber"],
									$CrossPart[$c]["name"],
									$Stock[$s]["count"],
									$price + $price*$markup,
									$Stock[$s]["delivery"] + $storage_options["additional_time"],
									$Stock[$s]["delivery"] + $storage_options["additional_time"],
									NULL,
									$min_order,
									$storage_options["probability"],
									$storage_options["office_id"],
									$storage_options["storage_id"],
									$storage_options["office_caption"],
									$storage_options["color"],
									$storage_options["storage_caption"],
									$price,
									$markup,
									2,0,0,'',$json,array("rate"=>$storage_options["rate"])
									);
								
								if($DocpartProduct->valid == true)
								{
									array_push($this->Products, $DocpartProduct);
								}
                            }
                        }
                        else
                        {
                            $json = json_encode($Stock);
							
							$price = $Stock["price"];

							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							{
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}
							
							
							$min_order = (int)$Stock["multiplicity"];
							if( $min_order < 1 )
							{
								$min_order = 1;
							}
							
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($CrossPart[$c]["brand"],
								$CrossPart[$c]["partnumber"],
								$CrossPart[$c]["name"],
								$Stock["count"],
								$price + $price*$markup,
								$Stock["delivery"] + $storage_options["additional_time"],
								$Stock["delivery"] + $storage_options["additional_time"],
								NULL,
								$min_order,
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'',$json,array("rate"=>$storage_options["rate"])
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
                        }
                    }
                }
            }
		
		
        }//~for($i=0; $i<count($Part); $i++)
			
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class rossko_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new rossko_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>