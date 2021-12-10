<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class leopart_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$user_id = $storage_options["user_id"];
		$password = $storage_options["password"];
		$api_key = $storage_options["api_key"];
		
		$wsdl = "https://www.leopart.kz/ext_soap/search_service/ext_search.wsdl";
		
		$options = array('soap_version' => SOAP_1_2,
			'exception' => true,
			'trace' => true		
		);
		
		$request = array("user_id" => $user_id,
			"password" => $password,
			"api_key" => $api_key,
			"detail_number" => $article,
			"only_number" => false
		);
		
		$client = new SoapClient($wsdl, $options);
		
		$serch_parts_res = $client->searchParts($request);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов метода searchParts() с параметрами:<br>".print_r($request,true), "См. ответ API после обработки", print_r($serch_parts_res, true) );
		}
		
		$status = $serch_parts_res->detailSearchResult->status;
		
		$goods_array = array();
		
		if ($status == "ok") 
		{
			$goods = $serch_parts_res->detailSearchResult->detail_data->detailList;
			
			if (count($goods) == 1) 
			{
				array_push($goods_array, $goods);
			} 
			else 
			{	
				$goods_array = $goods;
			}
			
			foreach ($goods_array as $goods_item) 
			{
				//Наценка
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL) {
					
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
				}
				
				$price_for_customer = $goods_item->price + $goods_item->price * $markup;
				
				$DocpartProduct = new DocpartProduct($goods_item->make, 
					$goods_item->detail_number,
					$goods_item->detail_name,
					$goods_item->quantity,
					$price_for_customer,
					$goods_item->day_min + $storage_options["additional_time"],
					$goods_item->day_max + $storage_options["additional_time"],
					$goods_item->stock,
					$goods_item->cmpl,
					$goods_item->possibility,
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$goods_item->price,
					$markup,
					2,
					0,
					0,
					'',
					'',
					array("rate"=>$storage_options["rate"])
				);
				
				if($DocpartProduct->valid)
				{	
					array_push($this->Products, $DocpartProduct);	
				}
			}
			$this->result = 1;
		} 
		else 
		{	
			throw new Exception($status);
		}
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


try 
{
	$ob =  new leopart_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
	$json = json_encode($ob, true);
	
	if( ! $json ) 
	{	
		throw new Exception(json_last_error());	
	}
	
}
catch (SoapFault $e) 
{	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
catch (Exception $e)
{	
	//ЛОГ - [ИСКЛЮЧЕНИЕ]
	if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
	{
		$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
	}
}
exit($json);
?>