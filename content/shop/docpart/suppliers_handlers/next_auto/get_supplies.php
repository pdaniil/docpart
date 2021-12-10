<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class next_auto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article,  $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		/*****Учетные данные*****/
		
		// var_dump($manufacturers);
		
		$ident = $manufacturers[0]["manufacturer_id"];
		
		
		$url = "http://next-auto.pro/xmlprice.php?login={$login}&password={$password}&ident={$ident}&json";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		
		$execute = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара ".$ident." (производитель ".$manufacturers[0]["manufacturer"].")", $url, $execute, print_r(json_decode($execute, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		curl_close($ch);
		
		$data = json_decode($execute, true);
		

		$partList = $data["pricelist"];
		
		if( ! $partList )
		{
			return;
		}
		
		foreach($partList as $part)
		{
			
			$price = (float)$part["price"];

			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			// наличие
			$exist = $part['rest'];
			if($exist === '+')
			{
				$exist = 5;
			}
			else
			{
				$exist = (int) $exist;
			}
			
			preg_match("/[0-9]+/", $part["deliverydays"], $deliveryDays);//Время доставки
			
			$DocpartProduct = new DocpartProduct($part["producer"], 
				$part["code"],
				$part["caption"],
				$exist,
				$price + $price*$markup,
				$deliveryDays[0] + $storage_options["additional_time"],
				$deliveryDays[0] + $storage_options["additional_time"],
				null,
				$part["amount"],
				100 - $part["stat_otkaz"],
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
				null,
				array("rate"=>$storage_options["rate"])
			);
			
			// var_dump($DocpartProduct);
			
			if($DocpartProduct->valid)
			{
				array_push($this->Products, $DocpartProduct);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob =  new next_auto_enclosure( $_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
exit(json_encode($ob));
?>