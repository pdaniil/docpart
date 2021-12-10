<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class agira_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$key =  $storage_options["key"];
		
		for ($m = 0; $m < count($manufacturers); $m++) 
		{
			
			$url = "http://92.53.97.97:80/api/search?key={$key}&oem={$article}&brand={$manufacturers[$m]["manufacturer"]}";
	
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
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[$m]["manufacturer"], $url, $execute, print_r(json_decode($execute, true), true) );
			}
			
			
			if( curl_errno($ch) ) 
			{	
				return;	
			}
			
			curl_close( $ch );

			$data = json_decode( $execute, true );
			
			
			if ($data["status"] == "success") {
				
				$full_data = array();
				
				array_push($full_data, $data["products"]["originals"]);
				array_push($full_data, $data["products"]["analogs"]);
			
				$this->addProduct($full_data, $storage_options);
				
			} //~if ($data["status"] == "success")
		
		} //~for ($m = 0; $m < count($manufacturers); $m++)
			
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
	}
	
	function addProduct($products_arr, $storage_options) {
		
		for ($p = 0; $p<count($products_arr); $p++) {
			
			foreach ($products_arr[$p] as $part) {
					
				$price = $part["price"];
				
				//Наценка
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				$markup = $storage_options["markups"][(int)$price];
				
				if($markup == NULL) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer = $price + $price * $markup;
				
				$delivery_time_full = strtotime($part["delivery_time"]);
				$current_time = time();
				
				$delivery_time = $delivery_time_full - $current_time;
				
				//Время доставки в днях
				$time_to_exe = $delivery_time/60/60/24 + $storage_options["additional_time"];
				
				$DocpartProduct = new DocpartProduct(
					$part["manufacturer"],
					$part["oem"],
					$part["name"],
					$part["quantity"],
					$price_for_customer,
					$time_to_exe,
					$time_to_exe,
					$part["stock"],
					$part["minimum"],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,
					0,
					0,
					'',
					$json_params = '',
					array("rate"=>$storage_options["rate"])
				);
			
				if($DocpartProduct->valid) {
					
					array_push($this->Products, $DocpartProduct);
					
				}
					
			} //~foreach ($analogs as $part)
		
		}
		
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$ob =  new agira_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>