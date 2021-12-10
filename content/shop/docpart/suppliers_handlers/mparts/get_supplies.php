<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mparts_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//----------------Учётные данные для API поставщика---------------//
		$login 				= $storage_options["login"];
		$password			= md5($storage_options["password"]);
		$useOnlineStocks	= $storage_options["useOnlineStocks"];

		
		
		//-------------------------По массиву производителей------------------//
		foreach ($manufacturers as $m_data) {
			
			$brand_name = urlencode($m_data["manufacturer"]);
			
			//---------------Формируем запрос к поставщику-----------------//
			$url = "http://v01.ru/api/devinsight/search/articles/";
			$url .= "?userlogin={$login}"; 
			$url .= "&userpsw={$password}"; 
			$url .= "&number={$article}"; 
			$url .= "&brand={$brand_name}"; 
			$url .= "&useOnlineStocks={$useOnlineStocks}"; 
			
			//--------------------- Запрос в API Поставщика---------------//
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$execute = curl_exec($ch);
		
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brand_name, $url, $execute, print_r(json_decode($execute, true), true) );
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
		
			$array_data = json_decode($execute, true);
			
			
			//------------------По данным массива ответа от поставщика---------------//
			foreach ($array_data as $good) {
				

				//-------------------Обработка полей--------------------------------//
				$price = $good["price"];
				
				//Наценка
				//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL) {
					
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
					
    		    }
				
				$price_for_customer = $price + $price * $markup;
				
				$time_to_exe = ((int)$good["deliveryPeriod"] / 24) + $storage_options["additional_time"];
				$time_to_exe_max = ((int)$good["deliveryPeriodMax"] / 24) + $storage_options["additional_time"];
				
				$probability = $storage_options["probability"];
				
				if ($good["deliveryProbability"] != ""
					|| $good["deliveryProbability"] != 0 
				) {
					
					$probability = $good["deliveryProbability"];
					
				}
				
				//------------------Создание объекта-------------------------------//
				$DocpartProduct = new DocpartProduct(
					$good["brand"],
					$good["number"],
					$good["description"],
					(int)$good["availability"],
					$price_for_customer,
					$time_to_exe,
					$time_to_exe_max,
					$good["supplierCode"],
					$good["packing"],
					$probability,
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
					'',
					array(
						"rate"=>$storage_options["rate"]
					)
				);
				
				if($DocpartProduct->valid) {
					
					array_push($this->Products, $DocpartProduct);
					
				}			
								
			} // ~ foreach ($array_data as $good)
			
			
		} // ~foreach ($manufacturers as $m_data)
		
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


$ob =  new mparts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>