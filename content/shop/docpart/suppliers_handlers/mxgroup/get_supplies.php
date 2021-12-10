<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mxgroup_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/

        $session = $manufacturers[0]["params"]["session"];
		
		
		for($i=0; $i<count($manufacturers);$i++)//Цикл по массиву брэндов
		{
			$price = (float)$manufacturers[$i]["params"]["discountprice"];
			
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($manufacturers[$i]["params"]["brand"],
				$manufacturers[$i]["params"]["articul"],
				$manufacturers[$i]["params"]["name"],
				$manufacturers[$i]["params"]["count"],
				$price + $price*$markup,
				$manufacturers[$i]["params"]["deliverytime"] + $storage_options["additional_time"],
				$manufacturers[$i]["params"]["deliverytime"] + $storage_options["additional_time"],
				$manufacturers[$i]["params"]["storename"],
				1,
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

			if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}

			
			//Делаем запрос аналогов
			//Делаем запрос товаров по артикулу
			$ch = curl_init("https://api.mxgroup.ru/mxapi/?session=$session&m=analog&zapros=".$manufacturers[$i]["params"]["code"]."&out=json");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			$curl_result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков аналогов", "https://api.mxgroup.ru/mxapi/?session=$session&m=analog&zapros=".$manufacturers[$i]["params"]["code"]."&out=json", $curl_result, print_r(json_decode($curl_result, true), true) );
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
			$curl_result = json_decode($curl_result, true);
			
			if($curl_result["result"] == "Out of stock")
			{
				continue;
			}
			
			$analogs = $curl_result["result"];
			
			for($a=0; $a < count($analogs); $a++)
			{
				$price = (float)$analogs[$a]["discountprice"];

				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($analogs[$a]["brand"],
					$analogs[$a]["articul"],
					$analogs[$a]["name"],
					$analogs[$a]["count"],
					$price + $price*$markup,
					$analogs[$a]["deliverytime"] + $storage_options["additional_time"],
					$analogs[$a]["deliverytime"] + $storage_options["additional_time"],
					$analogs[$a]["storename"],
					1,
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

				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
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
};//~class mxgroup_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$ob = new mxgroup_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>