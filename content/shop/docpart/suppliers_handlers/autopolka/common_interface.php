<?php
set_time_limit(0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autopolka_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед CURL-запросом на получение брендов");
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://autopolka.ru/api/v1/search/get_brands_by_oem?api_key=$api_key&oem=$article");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 400);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		curl_close($ch);
       	
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://autopolka.ru/api/v1/search/get_brands_by_oem?api_key=$api_key&oem=$article", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if ($curl_errno > 0) 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка при запросе на полученик брендов", "Ошибка ".$curl_errno." - ".$curl_error );
			}
		}
		
		
        $curl_result = json_decode($curl_result, true);
		if(strtoupper($curl_result['result']) != 'OK'){
			return;
		}
		
        $brands_array = $curl_result['data'];
        
        //ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
        for($i=0; $i<count($brands_array);$i++)//Цикл по массиву брэндов
        {
            // инициализация сеанса
            $ch = curl_init();
            // установка URL и других необходимых параметров
            curl_setopt($ch, CURLOPT_URL, "http://autopolka.ru/api/v1/search/get_offers_by_oem_and_make_name?api_key=$api_key&oem=".$brands_array[$i]['number']."&make_name=".$brands_array[$i]['brand']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 400);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
            // загрузка страницы и выдача её браузеру
            $curl_result = curl_exec($ch);
            // завершение сеанса и освобождение ресурсов
            curl_close($ch);
            
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$brands_array[$i]['number']." и производителю ".$brands_array[$i]['brand'], "http://autopolka.ru/api/v1/search/get_offers_by_oem_and_make_name?api_key=$api_key&oem=".$brands_array[$i]['number']."&make_name=".$brands_array[$i]['brand'], $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			
            $curl_result = json_decode($curl_result, true);
           
			if(strtoupper($curl_result['result']) != 'OK'){
				continue;
			}
			
			$products_arr = $curl_result['data'];
			
            foreach($products_arr as &$value)
            {
                $price = $value["cost"];

                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
                
                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct($value["make_name"],
                    $value["oem"],
                    $value["detail_name"],
                    $value["qnt"],
                    $price + $price*$markup,
                    $value['min_delivery_day'] + $storage_options["additional_time"],
                    $value['max_delivery_day'] + $storage_options["additional_time"],
                    $value["sup_logo"],
                    $value["min_qnt"],
                    $value["stat_group"],
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
            }//~foreach
            
        }//~for $brands_array
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class autopolka_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autopolka_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>