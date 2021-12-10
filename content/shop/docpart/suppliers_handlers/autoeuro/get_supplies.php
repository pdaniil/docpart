<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

// подключаем класс клиента
include('ae_client/cli_main.php');


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


/*
Скрипт - оболочка для получения данных от Автомаркет по протоколу SOAP
В случае возникновения ошибки - результат работы данного скрипта просто не будет учтен
При этом последовательный вызов обработчиков других поставщиков не будет прерван
*/
class autoeuro_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$config = array (
			'server' => 'http://online.autoeuro.ru/ae_server/srv_main.php',
			'client_name' => $storage_options["client_name"],
			'client_pwd' => $storage_options["client_pwd"],
		);
		
		$manufacturer_req = $manufacturers[0]["manufacturer"];
	
	
		// создаем экземпляр класса
	    $aeClient = new AutoeuroClient($config);
		// вызов процедуры 'Get_Element_Details' с 3-мя параметрами: 'RUV',5413,1
		$data2 = $aeClient->getData( 'Get_Element_Details', array( $manufacturer_req ,$article, 1 ) );
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_req, "Метод Get_Element_Details", print_r($data2, true), "Преобразование результата не требуется" );
		}

		foreach($data2 as $value)
		{
			$item_name =  iconv( "windows-1251", "utf-8", $value["name"] );
			$item_maker = iconv( "windows-1251", "utf-8", $value["maker"] );
			$time = explode("-", $value["order_time"]);
			
			$price = $value["price"];
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			//Минимальный заказ:
			$min_order = (int)$value['packing'];
			if( $min_order < 1 )
			{
				$min_order = 1;
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($item_maker,
				$value["code"],
				$item_name,
				$value["amount"],
				$price + $price*$markup,
				$time[0] + $storage_options["additional_time"],
				$time[1] + $storage_options["additional_time"],
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
};//~class autoeuro_enclosure




//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"Библиотека от поставщика ae_client") );




$ob = new autoeuro_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
exit(json_encode($ob));
?>