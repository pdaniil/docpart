<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class zzap_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login		= $storage_options["login"];
		$password	= $storage_options["password"];
		$api_key	= $storage_options["api_key"];
		$brand		= $manufacturers[0]["manufacturer"];
		
		//Запрос по арт.
		$ch = curl_init();
		$url = "https://www.zzap.ru/webservice/datasharing.asmx/GetSearchResult";
		
		$fields = array(
			"login"=>$login,
			"password"=>$password,
			"partnumber"=>$article,
			"class_man"=>$brand,
			"location"=>1,
			"row_count"=>500,
			"api_key"=>$api_key
		);
		
		$post_fields = http_build_query($fields);
		
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
		
		$exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($exec);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brand, $url."<br>Метод POST<br>Поля: ".print_r($fields, true), htmlspecialchars($exec, ENT_QUOTES, "UTF-8"), print_r($php_object, true) );
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
		
		
		$xml = new SimpleXMLElement($exec);
		
		$json = $xml[0]->__toString();
		
		if( $json == "" )
			return;
		
		$data = json_decode($json, true);
		
		
		if( ! $data )
			return;
		
		
		if(!empty($data['error'])){
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("API-ошибка", $data['error']);
			}
		}
		
		
		$items = $data["table"];
		
		
		foreach($items as $item)
		{
				
			preg_match("/[\d]+/", $item["qty"], $matches);
			
			$exist = $matches[0];
			
			//Цена
			preg_match("/\d+\s?\d+\.?\d+/", $item["price"], $matches);
			$price = str_replace(" ", "", $matches[0]);
			
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			$price_for_customer = $price+$price*$markup;

			//Ищем сначала с периодом
			$pattern = "/[\d]+-[\d]+/";
			preg_match($pattern, $item["descr_qty"], $matches);

			//Если нет периода, ищем одно значение
			if( ! $matches )
			{
				$pattern = "/[\d]+/";
				preg_match($pattern, $item["descr_qty"], $matches);
				
				//Скорее всего просто текст.
				if( ! $matches )
				{
					//Обработать текст
					if($item["descr_qty"] == "В наличии")
					{
						$time_to_exe =  $storage_options["additional_time"];
						$time_to_exe_guaranteed =  $storage_options["additional_time"];						
					}
				}
				else
				{
					$time_to_exe = $matches[0] + $storage_options["additional_time"];
					$time_to_exe_guaranteed = $matches[0] + $storage_options["additional_time"];
				}
			}
			else
			{
				$range = explode("-", $matches[0]);
				$time_to_exe = $range[0] + $storage_options["additional_time"];
				$time_to_exe_guaranteed = $range[1] + $storage_options["additional_time"];
			}


			$DocpartProduct = new DocpartProduct($item["class_man"],
				$item["partnumber"],
				$item["class_cat"],
				$exist,
				$price_for_customer,
				$time_to_exe,
				$time_to_exe_guaranteed,
				0,
				1,
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
				"",
				null,
				array("rate"=>$storage_options["rate"])
			);
		
			if($DocpartProduct->valid)
			{
				array_push($this->Products, $DocpartProduct);
			}
			
		}
		
		
		$this->result = 1;
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}

	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );


$ob =  new zzap_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>