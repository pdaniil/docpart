<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class gpi24_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct( $article, $manufacturers, $storage_options )
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
        $ch = curl_init();
		
		$service = "https://api.gpi24.ru/v1/webservice/search/";
		$action = "positions";
		$params_action = array(
			"article" => $article,
		);
		
		foreach ( $manufacturers as $m ) 
		{
			
			$params_action['brand'] = $m['manufacturer'];
			
			$url_request = $service . $action . "?" . http_build_query( $params_action );
		
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $url_request);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$headers = array(     
				"accept: application/json",  
				"Authorization: Bearer $key",
				"X-CSRF-TOKEN: "
			); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			$exec = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$m['manufacturer'], $url_request, $exec, print_r(json_decode($exec, true), true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
				continue;
				curl_close($ch);
			}
			
			
			curl_close($ch);
			
			$decode = json_decode($exec, true);
			
			if ( json_last_error() ) {
				
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Ошибка парсинга JSON", print_r(json_last_error(), true) );
				}
				continue;
			}
			
			$status = $decode['code'];
			
			if ($status == '200') 
			{
				
				$answers = $decode['data'];
		
				foreach( $answers as $part ) {
					
					$dp = $this->getDocpartProduct( $part, $storage_options );
					
					if ( is_object( $dp ) ) {
						
						$this->Products[] = $dp;
						
					}
					
				}
							
			}
			
		} // ~! foreach ( $manufacturers as $m )
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
	}//~function __construct($article)
	
	private function getDocpartProduct( $detal, $storage_options ) {
		
		$storages = $detal['remains'];

		if(!empty($storages)) {
			foreach($storages as $storage) {

				$price = (float)$storage['price'];
				
				$markup = $storage_options["markups"][(int)$price];
				
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$exist = $storage['balance'];
				$time_to_exe = $detal['delivery_days'];

				$name = $detal['applicability'];
				$article = $detal['article'];
				$brend = $detal['brand'];
				$min_order = $detal['packing_amount'];
				
				$exist = str_replace('<','',$exist);
				$exist = str_replace('>','',$exist);
				$exist = str_replace('=','',$exist);
				$exist = str_replace(' ','',$exist);
				$exist = (int)$exist;
				
				$time_to_exe = str_replace('<','',$time_to_exe);
				$time_to_exe = str_replace('>','',$time_to_exe);
				$time_to_exe = str_replace('=','',$time_to_exe);
				$time_to_exe = str_replace(' ','',$time_to_exe);
				$time_to_exe = (int)$time_to_exe;
				
				//Создаем объек товара и добавляем его в список:
				$dp = new DocpartProduct (
					$brend,
					$article,
					$name,
					$exist,
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
					2,0,0,'',null,array("rate"=>$storage_options["rate"])
				);
				
				if ( $dp->valid == true ) {
					
					return $dp;
					
				}
				
				return false;
			}
		}
		
	} // ~! getDocpartProduct( $detal, $storage_options )
	
};//~class gpi24_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$ob =  new gpi24_enclosure($_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit(json_encode($ob));
?>