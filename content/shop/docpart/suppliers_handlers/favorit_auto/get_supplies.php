<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class favorit_auto_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $key = urlencode($storage_options["key"]);
		/*****Учетные данные*****/
        
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");
		}
		
		//По каждому производителю
		for($i = 0; $i < count($manufacturers); $i++)
		{
			$manufacturer = urlencode($manufacturers[$i]['manufacturer']);
			
			$ch = curl_init();
			// установка URL и других необходимых параметров
			curl_setopt($ch, CURLOPT_URL, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&brand=$manufacturer&showname=on&analogues=on");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			// загрузка страницы и выдача её браузеру
			$curl_result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer, "http://api.favorit-parts.ru/hs/hsprice/?key=$key&number=$article&brand=$manufacturer&showname=on&analogues=on", $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			if( curl_errno($ch) )
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Ошибка CURL", print_r(curl_error($ch), true) );
				}
			}
			
			
			
			// завершение сеанса и освобождение ресурсов
			curl_close($ch);
			
			$curl_result = json_decode($curl_result, true);
			$curl_result = $curl_result["goods"];
			
			for($i=0; $i < count($curl_result); $i++)
			{
				//Данные на весь блок
				$manufacturer = $curl_result[$i]["brand"];
				$number = $curl_result[$i]["number"];
				$name = $curl_result[$i]["name"];
				$min_order = $curl_result[$i]["rate"];
				
				// Цикл по складам
				if(!empty($curl_result[$i]["warehouses"]) && is_array($curl_result[$i]["warehouses"])){
					foreach($curl_result[$i]["warehouses"] as $item_stock){
						
						// Наличие на собственном складе поставщика
						if((int)$storage_options["own"] === 1){
							if((bool)$item_stock["own"] !== true){
								continue;
							}
						}
						
						$price = (float) $item_stock["price"];
						$exist = (int) str_replace(array('<', '>', '=', ' '),'',$item_stock["stock"]);
						
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						// Определение сроков поставки
						$time_to_exe = 0;
						$timestamp = strtotime($item_stock["shipmentDate"]);
						$current = time();
						if($timestamp > $current){
							$time_to_exe = $timestamp - $current;
							$time_to_exe = ceil($time_to_exe / (60 * 60 * 24));
						}
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($manufacturer,
							$number,
							$name,
							$exist,
							$price + $price*$markup,
							$time_to_exe + $storage_options["additional_time"],
							$time_to_exe + $storage_options["additional_time"],
							$item_stock["id"],
							$min_order,
							$storage_options["probability"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',array("warehouses"=>$item_stock),array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
						
					}
				}
				
				
				
				// Аналоги
				$analogues = $curl_result[$i]["analogues"];
				if(!empty($analogues)){
					foreach($analogues as $analog){
						
						//Данные на весь блок
						$manufacturer = $analog["brand"];
						$number = $analog["number"];
						$name = $analog["name"];
						$min_order = $analog["rate"];
						
						// Цикл по складам
						if(!empty($analog["warehouses"]) && is_array($analog["warehouses"])){
							foreach($analog["warehouses"] as $item_stock){
								
								// Наличие на собственном складе поставщика
								if((int)$storage_options["own"] === 1){
									if((bool)$item_stock["own"] !== true){
										continue;
									}
								}
								
								$price = (float) $item_stock["price"];
								$exist = (int) str_replace(array('<', '>', '=', ' '),'',$item_stock["stock"]);
								
								//Наценка
								$markup = $storage_options["markups"][(int)$price];
								if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
								{
									$markup = $storage_options["markups"][count($storage_options["markups"])-1];
								}
								
								// Определение сроков поставки
								$time_to_exe = 0;
								$timestamp = strtotime($item_stock["shipmentDate"]);
								$current = time();
								if($timestamp > $current){
									$time_to_exe = $timestamp - $current;
									$time_to_exe = ceil($time_to_exe / (60 * 60 * 24));
								}
								
								//Создаем объек товара и добавляем его в список:
								$DocpartProduct = new DocpartProduct($manufacturer,
									$number,
									$name,
									$exist,
									$price + $price*$markup,
									$time_to_exe + $storage_options["additional_time"],
									$time_to_exe + $storage_options["additional_time"],
									$item_stock["id"],
									$min_order,
									$storage_options["probability"],
									$storage_options["office_id"],
									$storage_options["storage_id"],
									$storage_options["office_caption"],
									$storage_options["color"],
									$storage_options["storage_caption"],
									$price,
									$markup,
									2,0,0,'',array("warehouses"=>$item_stock),array("rate"=>$storage_options["rate"])
									);
								
								if($DocpartProduct->valid == true)
								{
									array_push($this->Products, $DocpartProduct);
								}
								
							}
						}
						
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
};//~class favorit_auto_enclosure



/*
$f = fopen('log.txt', 'a');
fwrite($f, $_POST["article"] . "\n");
fwrite($f, $_POST["manufacturers"] . "\n");
fwrite($f, $_POST["storage_options"] . "\n");
*/


/*
$_POST["article"] = '';
$_POST["manufacturers"] = '';
$_POST["storage_options"] = '';
*/


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new favorit_auto_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>