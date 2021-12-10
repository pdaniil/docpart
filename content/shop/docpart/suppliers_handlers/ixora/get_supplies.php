<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class ixora_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$AuthCode = $storage_options["authcode"];
		/*****Учетные данные*****/
        
		//ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
        for($ind=0; $ind < count($manufacturers); $ind++)//Цикл по массиву брэндов
        {
		
			//Запрос товаров по артикулу и производителю
			// инициализация сеанса
			$ch = curl_init();
			$ch_url = "http://ws.ixora-auto.ru/soap/ApiService.asmx/FindXML?AuthCode=".$AuthCode."&Number=$article&StockOnly=false&SubstFilter=All&Maker=".urlencode($manufacturers[$ind]["manufacturer"]);
			
			// установка URL и других необходимых параметров
			curl_setopt( $ch, CURLOPT_URL, $ch_url );
			
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			// загрузка страницы и выдача её браузеру
			$curl_result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$php_object = simplexml_load_string($curl_result);
				
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[$ind]["manufacturer"], $ch_url, htmlspecialchars($curl_result, ENT_QUOTES, "UTF-8"), print_r($php_object, true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
			}
			
			// завершение сеанса и освобождение ресурсов
			curl_close($ch);

			$curl_result = simplexml_load_string($curl_result);
			
			
			$curl_result = json_encode($curl_result);
			$curl_result = json_decode($curl_result, true);
			
			$DetailInfo = $curl_result["DetailInfo"];
				
		
			for($i=0; $i < count($DetailInfo); $i++)
			{
				$current_record = $DetailInfo[$i];
				$price = $current_record['price'];
				
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Для SAO
				$sao_data = array("orderreference" =>$current_record["orderreference"]);
				$json = json_encode($sao_data);
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($current_record['maker'],
					$current_record['number'],
					$current_record['name'],
					$current_record['quantity'],
					$price + $price*$markup,
					$current_record['days'] + $storage_options["additional_time"],
					$current_record['days'] + $storage_options["additional_time"],
					NULL,
					$current_record['lotquantity'],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',$json,array("rate"=>$storage_options["rate"])
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
};//~class ixora_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );


$ob = new ixora_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>