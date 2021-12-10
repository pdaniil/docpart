<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/trinity_parts/TrinityPartsWS.php");

class trinity_parts_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("API-запросы логируются непосредственно в методе TrinityPartsWS::query()");
		}
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$ClientCode = $storage_options["сlient_сode"];
		/*****Учетные данные*****/
		
		$ws = new \TrinityPartsWS($ClientCode); 

		if( !empty($ws->error) )
		{
			return;
		}

		for($b=0; $b < count($manufacturers); $b++)
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Получаем остатки по артикулу ".$article." и производителю ".$manufacturers[$b]["manufacturer"]);
			}
			
			$items = $ws->searchItems($article, $manufacturers[$b]["manufacturer"]);
			
			if($items["count"] == 0)
			{
				continue;
			}
			
			
			for($i=0; $i< count($items["data"]); $i++)
			{
				$price = $items["data"][$i]["price"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				
				//Получаем время доставки
				$DeliveryDate = explode("-", $items["data"][$i]["deliverydays"]);
				if(count($DeliveryDate) == 2)
				{
					$time_to_exe = $DeliveryDate[0];
					$time_to_exe_guaranteed = $DeliveryDate[1];
				}
				else
				{
					$DeliveryDate = explode("/", $items["data"][$i]["deliverydays"]);
					if(count($DeliveryDate) == 2)
					{
						$time_to_exe = $DeliveryDate[0];
						$time_to_exe_guaranteed = $DeliveryDate[1];
					}
					else
					{
						$time_to_exe = $DeliveryDate[0];
						$time_to_exe_guaranteed = $DeliveryDate[0];
					}
				}
				
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($items["data"][$i]["producer"],
					$items["data"][$i]["code"],
					$items["data"][$i]["caption"],
					$items["data"][$i]["rest"],
					$price + $price*$markup,
					(int)$time_to_exe + $storage_options["additional_time"],
					(int)$time_to_exe_guaranteed + $storage_options["additional_time"],
					$items["data"][$i]["stock"],
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
};//~class trinity_parts_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"file_get_contents() в библиотеке поставщика TrinityPartsWS.php") );


$ob = new trinity_parts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>