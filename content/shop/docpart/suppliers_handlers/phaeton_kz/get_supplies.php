<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class phaeton_kz_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $user_guid = $storage_options["user_guid"];
        $api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
		//По каждому производителю
		for($i = 0; $i < count($manufacturers); $i++)
		{
			$brend_brend = $manufacturers[$i]['manufacturer'];
			
			//Запрос Товаров
			$url = "http://api.phaeton.kz/api/Search?Brand=$brend_brend&Article=$article&UserGuid=$user_guid&ApiKey=$api_key";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$json = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brend_brend, $url, $json, print_r(json_decode($json, true), true) );
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
			
			$response = json_decode($json, true);
			
			if($response['IsError'] == false){
				
				$products_items = $response['Items'];
				
				if(!empty($products_items)){

					foreach($products_items as $item){
						
						//////////////////////////////////////////////////////////////////////
						
						// Если нужно ограничить по городам. Указываем города которые допущены
						if(0){
							$Warehouse = $item['Warehouse'];
							switch($Warehouse){
								
								case 'Алматы':
								case 'Астана':
								case 'Караганда':
								
								break;
								default: continue 2;
							}
						}
						
						$price = $item['Price'];
	
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						//Количество:
						$exist = str_replace( array('больше','меньше','равно',' '), '', $item['Presence']);
						
						//Набор параметров для SAO
						$json_params = array();
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($item['Brand'],
							$item['Article'],
							$item['Name'],
							$exist,
							$price + $price*$markup,
							$item['ExpectedShipmentDays'] + $storage_options["additional_time"],
							$item['GuaranteedShipmentDays'] + $storage_options["additional_time"],
							NULL,
							1,
							$storage_options["probability"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',json_encode($json_params),array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
						
						//////////////////////////////////////////////////////////////////////
					}
					
				}
				
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class phaeton_kz_enclosure

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new phaeton_kz_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>