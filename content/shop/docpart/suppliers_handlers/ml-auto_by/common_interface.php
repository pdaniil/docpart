<?php
/*
https://www.ml-auto.by/webservice/info/
*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class ml_auto_by_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $pass = $storage_options["pass"];
		/*****Учетные данные*****/
       

	   
        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ: /webservice/ArticleSearch/
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "https://www.ml-auto.by/webservice/ArticleSearch/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "LOGIN=$login&PASSWORD=$pass&ARTICLE=$article");
        // загрузка страницы и выдача её браузеру
        $curl_result = trim(curl_exec($ch));
		
		// Вырезаем BOM
		if(substr($curl_result, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)){
			$curl_result = substr($curl_result, 3);
		}
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://www.ml-auto.by/webservice/ArticleSearch/<br>Метод POST<br>Поля "."LOGIN=$login&PASSWORD=$pass&ARTICLE=$article", $curl_result, print_r(json_decode($curl_result, true), true) );
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
        

        $curl_result = json_decode($curl_result, true);
		
		
		if($curl_result["STATUS"] == '200'){
			//Формируем массив брэндов:
			$brands_array = array();
			foreach ($curl_result['RESPONSE'] as $value) {
				array_push($brands_array, $value["BRAND"]);
			}
			
			
			//ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
			for($i=0; $i<count($brands_array);$i++)//Цикл по массиву брэндов
			{
				// инициализация сеанса
				$ch = curl_init();
				// установка URL и других необходимых параметров
				curl_setopt($ch, CURLOPT_URL, "https://www.ml-auto.by/webservice/Search/");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, "LOGIN=$login&PASSWORD=$pass&ARTICLE=$article&BRAND=".$brands_array[$i]."&SEARCH_TYPE=1");
				// загрузка страницы и выдача её браузеру
				$curl_result = trim(curl_exec($ch));
				
				// Вырезаем BOM
				if(substr($curl_result, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)){
					$curl_result = substr($curl_result, 3);
				}
				
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brands_array[$i], "https://www.ml-auto.by/webservice/Search/<br>Метод POST<br>Поля "."LOGIN=$login&PASSWORD=$pass&ARTICLE=$article&BRAND=".$brands_array[$i]."&SEARCH_TYPE=1", $curl_result, print_r(json_decode($curl_result, true), true) );
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

				$curl_result = json_decode($curl_result, true);
				
				
				if($curl_result["STATUS"] == '200'){
					foreach ($curl_result['RESPONSE'] as $value) {
						
						 //Сначала проверяем корректность строки:
						if($value["PIN"] == NULL)
						{
							continue;
						}
						
						
						$price = (float)$value["PRICE"];
						
						
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						if($value["MIN"] == 0){
							$value["MIN"] = 1;
						}
						
						$value["NAME"] = str_replace("\\",'/',$value["NAME"]);
						$value["NAME"] = trim(strip_tags($value["NAME"]));
						
						// Срок
						$value["DATE"] =  intval(abs( (strtotime($value["DATE"]) - time()) / (3600 * 24) ));
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($value["BRAND"],
							$value["PIN"],
							$value["NAME"],
							$value["QUANTITY"],
							$price + $price*$markup,
							(int)$value["DATE"] + $storage_options["additional_time"],
							(int)$value["DATE"] + $storage_options["additional_time"],
							NULL,
							$value["MIN"],
							$value["CHANCE"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',null,array("rate"=>$storage_options["rate"])
							);
						
						//var_dump($DocpartProduct);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
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
};//~class ml_auto_by_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new ml_auto_by_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>