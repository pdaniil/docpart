<?php
// DOC : https://b2b.pwrs.ru/Help/Page?url=index.html
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );
include( 'TochkiApi.php' );


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class tochki_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		//ЛОГ - сообщение
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SOAP-клиента");
		}
		
		
		try 
		{
			
			$api = new TochkiApi();
			
			$api_options = array( 'wsdl' => 'http://api-b2b.pwrs.ru/WCF/ClientService.svc?wsdl',
									'login' => trim( $storage_options['login'] ),
									'password' => trim( $storage_options['password'] ) );
			
			$retail_price = $storage_options['retail_price']; //Розничная цена в проценке
			
			
			//ЛОГ ПЕРЕД API-запросом
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_before_api_request("Получение списка товаров по артикулу ".$article,'http://api-b2b.pwrs.ru/WCF/ClientService.svc?wsdl'."<br>Параметры: ".print_r($api_options, true) );
			}
			
			
			$api->setOptions( $api_options );
			
			//Получаем товары, наличие на складах и цены
			$suppliers_items = $api->getSupplierItems( $article );
			
			
			//ЛОГ ПОСЛЕ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение списка товаров по артикулу ".$article, print_r( $suppliers_items , true ), print_r( $suppliers_items, true ) );
			}
			
			
			
			if ( empty( $suppliers_items ) ) {
				
				$this->result = 1;
				return;
			}
			
			
			
			//ЛОГ ПЕРЕД API-запросом
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_before_api_request("Получение информации о складах",'http://api-b2b.pwrs.ru/WCF/ClientService.svc?wsdl<br>Дополнительные параметры не передаем');
			}
			
			//Получаем инфу о скаладах( сроки логистики и прочая инфа )
			$warehouse_info = $api->getWarehouseInfo();
			
			
			//ЛОГ ПОСЛЕ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_after_api_request("Получение информации о складах", print_r( $warehouse_info , true ), print_r( $warehouse_info, true ) );
			}
			
			
			
			
			//ЛОГ - сообщение
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Перед перекрестным циклом foreach (по товарам - по складам)");
			}
			
			
			
			// /* 
			foreach ( $suppliers_items as $item ) 
			{
				
				$article 				= $item['article'];
				$manufacturer 			= $item['brand'];
				$name 					= $item['name'];
				
				//Наличие на складах
				foreach ( $item['storages'] as $storage ) 
				{
					
					if ( $retail_price ) {
						
						$price_purchase		= $storage['price_rozn']; //Рекомендуемая цена из API
						
					} else {
						
						$price_purchase		= $storage['price']; //Цена поставщика( видимо )
												
					}
					
					// $price_purchase		= $storage['price_rozn']; //Возможно нужно будет использовать этот параметр
					$exist 				= $storage['rest'];
					
					$time_to_exe				= $warehouse_info[$storage['wrh']]['logisticDays'] + $storage_options['additional_time'];
					$time_to_exe_guaranteed	= $warehouse_info[$storage['wrh']]['logisticDays'] + $storage_options['additional_time'];
					
					$storage 					= 0;
					$min_order					= 1;
					$probability				= $storage_options['probability'];
					$product_type				= 2;
					$product_id				= 0;
					$storage_record_id		= 0;
					$url						= '';
					$json_params				= '';
					$rest_params				= array( "rate"=>$storage_options["rate"] );
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price_purchase];
					//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					if( $markup == NULL ) {
						
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						
					}
					
					$price_for_customer = $price_purchase + $price_purchase * $markup;
					
					$DocpartProduct = new DocpartProduct( $manufacturer, 
															$article,
															$name,
															$exist,
															$price_for_customer,
															$time_to_exe,
															$time_to_exe_guaranteed,
															$storage,
															$min_order,
															$probability,
															$storage_options["office_id"],
															$storage_options["storage_id"],
															$storage_options["office_caption"],
															$storage_options["color"],
															$storage_options["storage_caption"],
															$price_purchase,
															$markup,
															$product_type,
															$product_id,
															$storage_record_id,
															$url,
															$json_params,
															$rest_params );
					
					if($DocpartProduct->valid) {
						
						array_push($this->Products, $DocpartProduct);
						
					}
					
					
				}
				
				
			} // ~foreach ( $suppliers_items as $item )
			
			$this->result = 1;
			
			
			//ЛОГ результата запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список товаров", print_r($this->Products, true) );
			}
		}
		catch ( SoapFault $e )
		{
			//Отладка
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение SOAP-клиента", print_r($e, true) , $e->getMessage() );
			}
		}
		catch ( Exception $e )
		{
			//ЛОГ - ИСКЛЮЧЕНИЕ
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
			}
		}
		
	} //~__construct($article, $manufacturers, $storage_options)
	
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);


//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob =  new tochki_enclosure( $_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
$json = json_encode($ob);



exit($json);
?>