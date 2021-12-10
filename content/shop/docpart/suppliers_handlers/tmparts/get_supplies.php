<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tmparts_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $authorization = $storage_options["authorization"];
		/*****Учетные данные*****/
        
		$ch1 = curl_init();
		$fields = array("JSONparameter" => "{'Article': '".$article."'}");
        curl_setopt($ch1, CURLOPT_URL, "api.tmparts.ru/api/"); 
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1); 
		//Авторизация
		$headers = array(         
			'Authorization: Bearer '.$authorization
		); 
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);
		
		
		$curl_result = curl_exec($ch1);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "api.tmparts.ru/api/"."<br>Метод GET<br>Поля: ".print_r($fields,true)."<br>Заголовки: ".print_r($headers, true), $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($ch1))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch1), true) );
			}
		}

		$Art_List = json_decode($curl_result,true);  

		if ($Art_List[Message] != "")
		{
			//Не верный ключ
			return;
		}
		
		
		
		foreach($manufacturers as $brand)          
		{
			//Параметр запроса для проценки Номенклатуры с аналогами
			$fields = array("JSONparameter" => "{'Brand': '".$brand['manufacturer']."', 'Article': '".$article."', 'is_main_warehouse': 0 }" ); 
			curl_setopt($ch1, CURLOPT_URL, "api.tmparts.ru/api/StockByArticle?".http_build_query($fields)); 
			
			$curl_result = curl_exec($ch1);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brand['manufacturer'], "api.tmparts.ru/api/StockByArticle?".http_build_query($fields)."<br>Метод GET<br>Поля: ".print_r($fields,true)."<br>Заголовки: ".print_r($headers, true), $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			if(curl_errno($ch1))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch1), true) );
				}
			}
			
			
			//Получить проценку по Номенклатуре
			$Art_List_With_Prices = json_decode($curl_result,true);   

			//Вывести список товара с ценами и наличием на конкретных складах
			foreach($Art_List_With_Prices as $key2 => $value2)// По каждому найденному Бренду к искомому номеру выполнить проценку
			{
				foreach($value2[warehouse_offers] as $key3 => $value3)// По каждому найденному Бренду к искомому номеру выполнить проценку
				{
					$TimeToExe = $value3[delivery_period];
					$price = $value3[price];
					$quantity = $value3[quantity];
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($value2[brand],
						$value2[article],
						$value2[article_name],
						$quantity,
						$price + $price*$markup,
						$TimeToExe + $storage_options["additional_time"],
						$TimeToExe + $storage_options["additional_time"],
						NULL,
						$value3[min_part],
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
		}
		curl_close($ch1);
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
        $this->result = 1;
	}//~function __construct($article)
};//~class tmparts_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new tmparts_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>