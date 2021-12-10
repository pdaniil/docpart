<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class burjauto_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		$ch = curl_init("https://www.impex-jp.com/api/parts/search.html?part_no=".$article."&key=".$storage_options["api_key"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $curl_result = curl_exec($ch);
        
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "https://www.impex-jp.com/api/parts/search.html?part_no=".$article."&key=".$storage_options["api_key"], $curl_result, print_r(json_decode($curl_result, true), true) );
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
       
        //Получаем массив с объектами запчастей:
        $curl_result = json_decode($curl_result, true);
		
		if(empty($storage_options["exist"]))
		{
			$storage_options["exist"] = 1;
		}
		
		$arr_result = array();
		if(!empty($curl_result['original_parts']))
		{
			foreach($curl_result['original_parts'] as $v)
			{
				array_push($arr_result, $v);
			}
		}
		if(!empty($curl_result['replacement_parts']))
		{
			foreach($curl_result['replacement_parts'] as $v)
			{
				array_push($arr_result, $v);
			}
		}

		//Прогон по массиву объектов и вывод записей
		for($i=0; $i<count($arr_result); $i++)
		{
			$this_partObject=$arr_result[$i];//Получаем очередной Объект с полями одной запчасти из массива объектов
			
			$price = $this_partObject['price_rub'];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($this_partObject['mark'],
				$this_partObject['part_no_raw'],
				$this_partObject['name_rus'],
				$storage_options["exist"],
				$price + $price*$markup,
				$storage_options["time_to_exe"] + $storage_options["additional_time"],
				$storage_options["time_to_exe_guaranteed"] + $storage_options["additional_time"],
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
				2,0,0,'',null,array("rate"=>$storage_options["rate"])
				);
			
			if($DocpartProduct->valid == true)
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
        
	}//~function __construct($article)
};

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new burjauto_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>