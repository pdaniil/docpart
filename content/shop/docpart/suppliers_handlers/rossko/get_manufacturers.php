<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class rossko_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
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
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-вызов метода GetSearch() с параметрами ".print_r(array('KEY1'=>$KEY1, 'KEY2'=>$KEY2, 'TEXT'=>$article, 'delivery_id'=>$delivery_id, 'address_id'=>$address_id),true), "См. ответ API после обработки", print_r($soap_am_result, true) );
			}
			
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода GetSearch() с параметрами ".print_r(array('KEY1'=>$KEY1, 'KEY2'=>$KEY2, 'TEXT'=>$article, 'delivery_id'=>$delivery_id, 'address_id'=>$address_id),true), print_r($e, true) , $e->getMessage() );
			}
			return;
		}
		
		if ( isset( $soap_am_result->SearchResult ) ) 
		{
			
			if ( $soap_am_result->SearchResult->success == true) 
			{
				
				$PartsList = $soap_am_result->SearchResult->PartsList;
				
				if ( is_object( $PartsList->Part ) ) 
				{
					
					$manufacturer = $this->getDocpartManufacturer( $PartsList->Part, $storage_options );
					$this->ProductsManufacturers[] = $manufacturer;
					
					
				} 
				else if ( is_array( $PartsList->Part ) ) 
				{
					
					foreach ( $PartsList->Part as $Part ) 
					{
						
						$manufacturer = $this->getDocpartManufacturer( $Part, $storage_options );
						$this->ProductsManufacturers[] = $manufacturer;
						
					}
					
				} // if ( is_object( $PartsList->Part ) )
				
			} else {
			    
			    //ЛОГ - [ИСКЛЮЧЕНИЕ]
    			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
    			{
    				$DocpartSuppliersAPI_Debug->log_exception("Ошибка со стороны поставщика.", print_r($soap_am_result, true) , $soap_am_result->SearchResult->text );
    			}
			    
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
	
	private function getDocpartManufacturer( $supp_data, $storage_options ) {
		
		$brand = $supp_data->brand;
		$part_name = $supp_data->name;
		
		$dpm = new DocpartManufacturer(
			$brand,
			0,
			$part_name,
			$storage_options["office_id"],
			$storage_options["storage_id"],
			true,
			null
		);
		
		return $dpm;
		
	}
	
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