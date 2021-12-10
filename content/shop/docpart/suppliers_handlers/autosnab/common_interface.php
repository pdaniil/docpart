<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autosnab_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $key = urlencode($storage_options["key"]);
		/*****Учетные данные*****/
        

        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://www.autosnab-k.ru/webservices/getxmlprice.php?key=$key&number=$article&cross=on");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($curl_result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://www.autosnab-k.ru/webservices/getxmlprice.php?key=$key&number=$article&cross=on", htmlentities($curl_result), print_r($php_object, true) );
		}
		
		
		$curl_result = simplexml_load_string($curl_result);
		$curl_result = json_encode($curl_result);
		$curl_result = json_decode($curl_result, true);
		
		
		$searchnumber = $curl_result["searchnumber"];// - блок результатов поиска по совпадению номера
		$crossnumber = $curl_result["crossnumber"];// - блок результатов поиска кроссовых замен
		$discountnumber = $curl_result["discountnumber"];// - блок результатов поиска дисконтных товаров
		
		$arr_list = array('searchnumber', 'crossnumber', 'discountnumber');
		
		
		foreach($arr_list as $k => $v){
			
			$DetailInfo = $$v;
			$DetailInfo = $DetailInfo['pricelist'];
			
			
			for($i=0; $i < count($DetailInfo); $i++)
			{
				$price = $DetailInfo[$i]["price"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$period = explode('-', $DetailInfo[$i]["period"]);
				$time_to_exe = (int)trim($period[0]);
				$time_to_exe_garanted = (int)trim($period[1]);
				if($time_to_exe > $time_to_exe_garanted){
					$time_to_exe_garanted = $time_to_exe;
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($DetailInfo[$i]["brand"],
					$DetailInfo[$i]["number"],
					$DetailInfo[$i]["name"],
					(int)$DetailInfo[$i]["quantity"],
					$price + $price*$markup,
					$time_to_exe + $storage_options["additional_time"],
					$time_to_exe_garanted + $storage_options["additional_time"],
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
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class autosnab_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autosnab_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>