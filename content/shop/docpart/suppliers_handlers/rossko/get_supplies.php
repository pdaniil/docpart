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
	
	public function __construct( $article, $manufacturers, $storage_options )
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
        
		/*****Учетные данные*****/
        $KEY1 = $storage_options["key1"];
        $KEY2 = $storage_options["key2"];
        $delivery_id = $storage_options["delivery_id"];
        $address_id = $storage_options["address_id"];
		/*****Учетные данные*****/
		
		$all_supplier_items = array(); //Список позиций от поставщика
		// Создаем уточняющий запрос на основе данных о производителе
		foreach ( $manufacturers as $manufacturer ) {
			
			$article_req = $article . '+'. $manufacturer['manufacturer'];
			
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
                'text' => $article_req,
                'delivery_id' => $delivery_id,
                'address_id'  => $address_id
            );
			
			//Создание объекта клиента
			try 
			{
				$objClient = new SoapClient($connect['wsdl'], $connect['options']);//Создаем SOAP-клиент
				
				$SearchResult = $objClient->GetSearch($param)->SearchResult;
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer['manufacturer'], "SOAP-вызов метода GetSearch() с параметрами ".print_r(array('KEY1'=>$KEY1, 'KEY2'=>$KEY2, 'TEXT'=>$article_req, 'delivery_id' => $delivery_id, 'address_id'  => $address_id),true), "См. ответ API после обработки", print_r($SearchResult, true) );
				}
				
				if ( ! $SearchResult->success ) 
				{
					continue;
				}
				
				$PartList = $SearchResult->PartsList->Part;//Список объектов (элемент PartsList)
				
				if ( is_array( $PartList ) ) {
					
					foreach ( $PartList as $Part ) {
						
						$PartInfo = $this->getPartInfo( $Part );
						$all_supplier_items[] = $PartInfo;
						
						$Crosses = $Part->crosses->Part;
						
						if ( is_array( $Crosses ) ) {
							
							foreach ( $Crosses as $Cross ) {
								
								$PartInfo = $this->getPartInfo( $Cross );
								$all_supplier_items[] = $PartInfo;
								
							}
							
						} else if ( is_object( $Crosses ) ) {
							
							$PartInfo = $this->getPartInfo( $Crosses );
							$all_supplier_items[] = $PartInfo;
							
						}
						
					}
					
				} else if ( is_object( $PartList ) ) {
					
					$PartInfo = $this->getPartInfo( $PartList );
					$all_supplier_items[] = $PartInfo;
					
					$Crosses = $PartList->crosses->Part;
					
					if ( is_array( $Crosses ) ) {
						
						foreach ( $Crosses as $Cross ) {
						
							$PartInfo = $this->getPartInfo( $Cross );
							$all_supplier_items[] = $PartInfo;
							
						}
						
					} else if ( is_object( $Crosses ) ) {
						
						$PartInfo = $this->getPartInfo( $Crosses );
						$all_supplier_items[] = $PartInfo;
						
					}
					
				}
				
			}
			catch ( SoapFault $e ) 
			{
				//ЛОГ - [ИСКЛЮЧЕНИЕ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
				}
			}
		} // ~! foreach ( $manufacturers as $manufacturer )
		
		
		//Создаём DP
		foreach ( $all_supplier_items as $item ) {
			
			$this->getDocpartProduct( $item, $storage_options );
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
	}//~function __construct($article)
	
	private function getPartInfo( $supplier_item ) {
		
		$PartInfo = new stdClass();
		
		$PartInfo->guid = $supplier_item->guid;
		$PartInfo->brand = $supplier_item->brand;
		$PartInfo->partnumber = $supplier_item->partnumber;
		$PartInfo->name = $supplier_item->name;
		
		$PartInfo->stocks = array();
		
		$StocksList = $supplier_item->stocks->stock;
		
		if ( is_array( $StocksList ) ) {
			
			foreach ( $StocksList as $Stock ) {
				
				$PartInfo->stocks[] = $Stock;
				
			}
			
		} else if ( is_object( $StocksList ) ) {
			
			$PartInfo->stocks[] = $StocksList;
			
		}
		
		return $PartInfo;
		
	}
	
	//Создание DP
	private function getDocpartProduct( $part, $storage_options ) {
		
		foreach ( $part->stocks as $stock ) 
		{
			
			// var_dump( $part );
			
			$json = json_encode($stock);
			
			$price = $stock->price;

			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			if($markup == NULL) {
				
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				
			}
			
			$time_to_exe = $stock->delivery;
			
			$min_order = (int)$stock->multiplicity;
			if( $min_order < 1 )
			{
				$min_order = 1;
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($part->brand,
				$part->partnumber,
				$part->name,
				$stock->count,
				$price + $price*$markup,
				$time_to_exe + $storage_options["additional_time"],
				$time_to_exe + $storage_options["additional_time"],
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
				2,0,0,'',$json,array("rate"=>$storage_options["rate"] ) );
			
			if( $DocpartProduct->valid == true ) {
				
				$this->Products[] = $DocpartProduct;
				
			}
			
		}
		
	}
	
	
};//~class rossko_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );


$ob = new rossko_enclosure ( 
	$_POST["article"], 
	json_decode( $_POST["manufacturers"], true ), 
	$storage_options
);
exit(json_encode($ob));
?>