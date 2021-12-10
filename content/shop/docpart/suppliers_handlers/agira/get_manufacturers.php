<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class agira_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$key =  $storage_options["key"];
		
		$url = "http://92.53.97.97:80/api/searchBrand?key={$key}&oem={$article}";
	
		//$verbose = fopen( "curl.log", "w" );
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		
		curl_setopt($ch, CURLOPT_VERBOSE, 1 );
		//curl_setopt($ch, CURLOPT_STDERR, $verbose );
		
		$execute = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r(json_decode($execute, true), true) );
		}
		
		
		
		if( curl_errno($ch) )
		{
			return;
		}
		
		curl_close( $ch );

		$data = json_decode( $execute, true );
		
		
		if( $data["status"] == "success" )
		{
			
			$brands_list = $data["brands"];
			
			foreach( $brands_list as  $brand_data )
			{
				
				foreach ($brand_data as $brand_name => $v) 
				{
					
					$name = $v;
					if ( $name = "" || NULL )
					{
						$name = "Наименование не указано поставщиком";
					}
					if( is_array($v) )
					{
						$name = $v[0];
					}
					
					//$name = str_replace("\\", "", $name);
					//$brand_name = str_replace("\\", "", $brand_name);
					
					$DocpartManufacturer = new DocpartManufacturer(
						$brand_name,
						0,
						$name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						null
					);
			
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					
				}
				
			}
			
			$this->status = 1;
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new agira_enclosure($_POST["article"], $storage_options);
exit( json_encode($ob) );
?>