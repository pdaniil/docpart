<?php
ini_set('memory_limit', '256M');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class abcp_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct ( $article_reqeust, $manufacturers, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$login				= $storage_options['login'];
		$password			= md5( $storage_options['password'] );
		$subdomain			= $storage_options['subdomain'];
		// $use_online 		= $storage_options['useOnlineStocks'];
		$use_online 		= true;
		
		foreach ( $manufacturers as $m ) 
		{
			
			$params_query = http_build_query( array( 'userlogin' => $login, 
											'userpsw' => $password, 
											'number'  => $article_reqeust, 
											'brand' => $m['manufacturer'], 
											'useOnlineStocks' => $use_online ) );
		
			$url_request = "http://{$subdomain}.public.api.abcp.ru/search/articles?{$params_query}";
			
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_HEADER, false );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_URL, $url_request);
			
			$exec = curl_exec($ch);
			
			
			//ЛОГ API-запроса (название запроса, запрос, ответ, обработанный ответ)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article_reqeust." и производителю ".$m['manufacturer'], $url_request, $exec, print_r(json_decode($exec, true), true) );
			}
			
			
			if ( curl_errno( $ch ) ) 
			{	
				throw new Exception ( "Ошибка curl: " . curl_errno( $ch ) );
				curl_close($ch);
			}
			
			
			curl_close( $ch );

			$decode = json_decode ( $exec, true );
			
			if ( ! $decode ) 
			{	
				throw new Exception ( "Ошибка json: " . json_last_error() );	
			}
			
			if ( isset ( $decode['errorCode'] ) ) 
			{	
				throw new Exception ( "Ошибка от поставщика: " . $decode['errorMessage'] );	
			}
			
			foreach ( $decode as $value ) 
			{
                
                $exist		= $value["availability"];
                $price 	= $value["price"];
				$min_order	= $value["packing"];				   

                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
                
				$article = str_replace( "'", "", $value["number"] );
				
				$sao_data = array( 'brand' => $value["brand"],
									'number' => $article,
									'itemKey' => $value['itemKey'],
									'supplierCode' => $value['supplierCode'],
									'code' => $value['code'] );
				
				$sao_json = json_encode($sao_data);
				
                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct ( $value["brand"],
														$article,
														$value["description"],
														$exist,
														$price + $price*$markup,
														(int)($value["deliveryPeriod"]/24) + $storage_options["additional_time"],
														(int)($value["deliveryPeriod"]/24) + $storage_options["additional_time"],
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
														2,0,0,'',$sao_json,array("rate"=>$storage_options["rate"])
														);
				
				if ( $DocpartProduct->valid == true ) 
				{	
					array_push($this->Products, $DocpartProduct);
				}
			}
		} // ~ foreach ( $manufacturers as $m )
		
		$this->result = 1;
		
		//ЛОГ результирующего объекта
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	} // ~ __construct(){}	
}




$storage_options = json_decode( $_POST["storage_options"], true );


//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



try 
{

	$ob =  new abcp_enclosure ( $_POST["article"], json_decode( $_POST["manufacturers"], true ), $storage_options );

	$json = json_encode($ob);
		
	if ( ! $json ) 
	{
		throw new Exception("Ошибка преобразования abcp_enclosure в json: " . json_last_error());
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


exit( $json );
?>