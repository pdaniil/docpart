<?php
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autopiter_enclosure
{
	public $result;
	public $client = null;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		
		$this->connect($storage_options["user"], $storage_options["password"]);//Соединяемся с сервером SOAP
		$soap_result = $this->getPriceByNum ($article);//Выполняем процедуру получения товаров по артикулу
		
		if($soap_result == false)
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", '$soap_result после попытки соединения и запроса товаров равен false. Запрос не удался' );
			}
			
			$this->result = 0;
			return;
		}
		
		//Наполняем массивы запрошенного артикула и аналогов:
		for($i=0; $i < count($soap_result); $i++)
		{
		    //Наценка
		    $markup = $storage_options["markups"][(int)$soap_result[$i]->SalePrice];
		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		    {
		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
		    }
		    
			if($soap_result[$i]->NumberOfAvailable == NULL)
			{
				$soap_result[$i]->NumberOfAvailable = 100;
			}
		    
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($soap_result[$i]->NameOfCatalog,
                $soap_result[$i]->Number,
                $soap_result[$i]->NameRus,
                $soap_result[$i]->NumberOfAvailable,
                $soap_result[$i]->SalePrice + $soap_result[$i]->SalePrice*$markup,
                $soap_result[$i]->NumberOfDaysSupply + $storage_options["additional_time"],
                $soap_result[$i]->NumberOfDaysSupply + $storage_options["additional_time"],
                NULL,
                $soap_result[$i]->MinNumberOfSales,
                100 - (int)$soap_result[$i]->RealTimeInProc,
                $storage_options["office_id"],
                $storage_options["storage_id"],
                $storage_options["office_caption"],
                $storage_options["color"],
                $storage_options["storage_caption"],
                $soap_result[$i]->SalePrice,
                $markup,
                2,0,0,'',null,array("rate"=>$storage_options["rate"])
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
	
	
	
	//Метод соединения с SOAP - сервером
	public function connect ($user, $password) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SoapClient. Для авторизации используем<br>Логин: '$user'<br>Пароль: '$password'");
		
		
		$this->client = new SoapClient('http://service.autopiter.ru/price.asmx?WSDL', 
								 array('soap_version' => SOAP_1_2, 
									   'encoding'=>'UTF-8')); 
		$result = $this->client->IsAuthorization(); 
		
		// Авторизуемся 
		if (!$result->IsAuthorizationResult) 
		{ 
			$result = $this->client->Authorization(array( 
						   'UserID' => $user, 
						   'Password' => $password, 
						   'Save' => true 
						   )); 
		} 
	}//~public function connect() 
	
	
	//Выполнение процедуры SOAP
	public function getPriceByNum($detailNum) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед SOAP вызовом метода FindCatalog");
		
		try
		{
			// Загружаем каталоги с деталями 
			$catalogObj = $this->client->FindCatalog(array('ShortNumberDetail' => $detailNum));
		}
		catch(Exception $e)
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода FindCatalog", print_r($e, true) , $e->getMessage() );
			}
			
			return false;
		}
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$detailNum, "SOAP-вызов метода FindCatalog с указанием параметра 'ShortNumberDetail' = '$detailNum'", "См. ответ API после обработки", print_r($catalogObj, true) );
		}
		
		

		if (!$catalogObj->FindCatalogResult) 
		{ 
			return false; 
		} 

		$itemCatalog = $catalogObj->FindCatalogResult->SearchedTheCatalog; 
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Далее следуют вызовы SOAP-метода GetPriceId для каждого производителя");
		
		if (is_array($itemCatalog))
		{
			$result = array();
			for($i=0; $i < count($itemCatalog); $i++)
			{
				$item = $itemCatalog[$i];
				try
				{ 
					$details = $this->client->GetPriceId(array ('ID' => $item->id, 
													  'IdArticleDetail' => -1, 
													  'FormatCurrency' => 'РУБ', 
													  'SearchCross' => true)); 
				}
				catch (Exception $e)
				{
					//ЛОГ - [ИСКЛЮЧЕНИЕ]
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetPriceId", print_r($e, true) , $e->getMessage() );
					}
					
					return false; 
				}
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара: ".$item->id, "SOAP-вызов метода GetPriceId", "См. ответ API после обработки", print_r($details, true) );
				}
				
				if (!$details->GetPriceIdResult)
				{
					//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", 'Вызов GetPriceId выполнился корректно, но его результат $details->GetPriceIdResult равен false. Продолжаем цикл запросов по другим производителям' );
					}
					
					continue;
				} 
				
				if(is_array($details->GetPriceIdResult->BasePriceForClient))
				{
					$result = array_merge($result, $details->GetPriceIdResult->BasePriceForClient);
				}
			}
			return $result;
		}
		else
		{
			
			try 
			{
				$details = $this->client->GetPriceId(array ('ID' => $itemCatalog->id, 
												  'IdArticleDetail' => -1, 
												  'FormatCurrency' => 'РУБ', 
												  'SearchCross' => true)); 
			}
			catch (Exception $e)
			{ 
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetPriceId", print_r($e, true) , $e->getMessage() );
				}
			
				return false; 
			}
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара: ".$itemCatalog->id, "SOAP-вызов метода GetPriceId", "См. ответ API после обработки", print_r($details, true) );
			}
			
			
			if (!$details->GetPriceIdResult)
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", 'Вызов GetPriceId выполнился корректно, но его результат $details->GetPriceIdResult равен false' );
				}
				
				return false; 
			} 
			
			
			if(!is_array($details->GetPriceIdResult->BasePriceForClient))
			{
				$details->GetPriceIdResult->BasePriceForClient = array($details->GetPriceIdResult->BasePriceForClient);
			}
			return $details->GetPriceIdResult->BasePriceForClient;
		}
	}//~public function getPriceByNum($detailNum = '50610TA0A10')
};//~class autopiter_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autopiter_enclosure($_POST["article"], $storage_options);
$ob->client = 0;
exit(json_encode($ob));
?>