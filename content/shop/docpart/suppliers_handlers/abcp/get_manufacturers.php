<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );



//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");



class abcp_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct ( $article_reqeust, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$login				= $storage_options['login'];
		$password			= md5( $storage_options['password'] );
		$subdomain			= $storage_options['subdomain'];
		// $use_online 		= $storage_options['useOnlineStocks'];
		$use_online 		= true;
		
		$params_query = http_build_query( array( 'userlogin' => $login, 
										'userpsw' => $password, 
										'number'  => $article_reqeust, 
										'useOnlineStocks' => $use_online ) );
	
		$url_request = "http://{$subdomain}.public.api.abcp.ru/search/brands?{$params_query}";
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_URL, $url_request );
		
		$exec = curl_exec($ch);
		
		
		//ЛОГ API-запроса (название запроса, запрос, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article_reqeust, $url_request, $exec, print_r(json_decode($exec, true), true) );
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
		
		$locale_hash = array();
		
		foreach ( $decode as $brand_data ) 
		{
			$manufacturer 				= $brand_data['brand'];
			$manufacturer_id			= 0;
			$name						= $brand_data['description'];
			$synonyms_single_query	= true;
			$params					= '';
			
			$hash = md5( $manufacturer . $manufacturer_id );
			
			if ( ! isset ( $locale_hash[$hash] ) ) {
				
				$DocpartManufacturer = new DocpartManufacturer ( $manufacturer,
																	$manufacturer_id,
																	$name,
																	$storage_options["office_id"],
																	$storage_options["storage_id"],
																	$synonyms_single_query,
																	$params);
				
				$this->ProductsManufacturers[] = $DocpartManufacturer;
				$locale_hash[$hash] = true;
				
			}
		}
		
		$this->status = 1;
		
		//ЛОГ результирующего объекта
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
	}
}






$storage_options = json_decode( $_POST["storage_options"], true );



//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();




try 
{
	$ob = new abcp_enclosure ( $_POST["article"], $storage_options );
	
	$json = json_encode($ob);
	
	if ( ! $json ) 
	{
		throw new Exception("Ошибка преобразования DocpartManufacturer в json: " . json_last_error());
	}
} 
catch (Exception $e) 
{
	//ЛОГ - ИСКЛЮЧЕНИЕ
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}


exit($json);
?>