<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class major_auto_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient("https://parts.major-auto.ru:8066/PartsProcessing.asmx?WSDL", array('soap_version' => SOAP_1_2));
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{

			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SoapClient", print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		

         $params = array(
             'request' => array(
                 'Authority' => array(
                        'ConsumerID' => $storage_options["consumerId"],
                         'Identifier' => $storage_options["identifier"],
                     ),
                'Options' => array(
			            'AnalogueParts' => true,
			            'RepairParts' => false,
			    ),
			    'Rows' => array(
			        'Row' => array(
			                'PartNo' => $article,
			                'QTY' => 1000,
			                'PartsGroupID' => ''
			            ),
			    ),
            )
        );
        
        
		//Запускаем SOAP-процедуру
		try
		{
            $GetPriceResult = $objClient->GetAvailability($params);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода SearchParts() с параметрами:<br>".print_r($params,true), "См. ответ API после обработки", print_r($GetPriceResult, true) );
			}
		}
		catch (SoapFault $e)
		{	
		    
		    //print_r($e);
		    
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода SearchParts()", print_r($e, true) , $e->getMessage() );
			}
			return;
		}


		$GetPriceResult = json_encode($GetPriceResult);
		$GetPriceResult = json_decode($GetPriceResult, true);
		
		$SearchPartsResult = $GetPriceResult["GetAvailabilityResult"];
		
		$Rows = $SearchPartsResult["Rows"];
		
		if( $Rows["Rows"] != NULL )
		{
			$Rows = array($Rows);
		}
		

		foreach($Rows as $Row)
		{
		    
		    		// echo "<pre>";
        //     		print_r($Row);
        //     		echo "</pre>";
		    
            $Parts = $Row["Part"];
            
            if(!is_array($Parts[1])){
				$Parts = array();
				$Parts[] = $Row["Part"];
			}
            
            foreach ($Parts as $Part) {
                
            //     	echo "<pre>";
            // 		print_r($Part);
            // 		echo "</pre>";
                
                
        			$price = (float)$Part["Price"]["PricePurchase"];
        			
        			//Наценка
        			$markup = $storage_options["markups"][(int)$price];
        			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
        			{
        				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
        			}
        			
        			$Storages = $Part["Availabilities"]["Availability"];
        	
        			    if(!is_array($Storages[1])){
            				$Storages = array();
            				$Storages[] = $Part["Availabilities"]["Availability"];
            			}
        			
        
        			for($s=0; $s < count($Storages); $s++)
        			{
        			    
        			    $StockQTY = (int)$Storages[$s]["QTY"];
        			    $DeliveryStock = (int)$Storages[$s]["DeliveryTime"];
        		
        			    //Если позиция заказная то подставляем свои значения
        			    if($Storages[$s]["Stock"]["StockType"] === 3) {
        			        
        			        $StockQTY = 1;
        			        $DeliveryStock = 35; 
        			        
        			    }
        			    
        			    
        				//Создаем объек товара и добавляем его в список:
        				$DocpartProduct = new DocpartProduct($Part["PartInfo"]["PartsGroupName"],
        					$Part["PartInfo"]["PartNo"],
        					$Part["PartInfo"]["PartName"],
        					$StockQTY,
        					$price + $price*$markup,
        					$DeliveryStock,
        					$storage_options["additional_time"],
        					$Storages[$s]["Stock"]["StockName"],
        					null,
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
        			
            }
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class major_auto_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new major_auto_enclosure($_POST["article"], $storage_options);

exit(json_encode($ob));
?>