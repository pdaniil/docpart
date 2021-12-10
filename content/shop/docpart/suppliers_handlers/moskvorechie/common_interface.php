<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class moskvorechie_enclosure
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
        $key = $storage_options["key"];
		/*****Учетные данные*****/
        
        
        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://portal.moskvorechie.ru/portal.api?l=$login&p=$key&act=brand_by_nr&nr=$article&alt&oe");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
		
		//ЛОГ API-запроса
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article,"http://portal.moskvorechie.ru/portal.api?l=$login&p=$key&act=brand_by_nr&nr=$article&alt&oe", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
        
        $curl_result = json_decode($curl_result, true);
        $curl_result = $curl_result["result"];
        
        
        //Формируем массив брэндов:
        $brands_array = array();
        foreach ($curl_result as &$value) {
            array_push($brands_array, $value["brand"]);
        }
        

        
        //ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
        for($i=0; $i<count($brands_array);$i++)//Цикл по массиву брэндов
        {
            // инициализация сеанса
            $ch = curl_init();
            // установка URL и других необходимых параметров
            curl_setopt($ch, CURLOPT_URL, "http://portal.moskvorechie.ru/portal.api?l=$login&p=$key&act=price_by_nr_firm&cs=utf8&nr=$article&f=".$brands_array[$i]."&alt&oe");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            // загрузка страницы и выдача её браузеру
            $curl_result = curl_exec($ch);
            // завершение сеанса и освобождение ресурсов
            curl_close($ch);
			
			
			//ЛОГ API-запроса
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка товаров по артикулу ".$article." и брэнду ".$brands_array[$i],"http://portal.moskvorechie.ru/portal.api?l=$login&p=$key&act=price_by_nr_firm&cs=utf8&nr=$article&f=".$brands_array[$i]."&alt&oe", $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
            
            $curl_result = json_decode($curl_result, true);
            $result_array = $curl_result["result"];
            
            
            foreach($result_array as &$value)
            {
                $price = (float)$value["price"];
                
                
                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
                
                
                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct($value["brand"],
                    $value["nr"],
                    $value["name"],
                    $value["stock"],
                    $price + $price*$markup,
                    (int)$value["delivery"] + $storage_options["additional_time"],
                    (int)$value["delivery"] + $storage_options["additional_time"],
                    NULL,
                    $value["minq"],
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
                
                //var_dump($DocpartProduct);
                
                if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
            }//~foreach
        }
        $this->result = 1;
        
		
		//ЛОГ результата запроса
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список товаров", print_r($this->Products, true) );
		}
        
	}//~function __construct($article)
};//~class moskvorechie_enclosure


$storage_options = json_decode($_POST["storage_options"], true);

//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new moskvorechie_enclosure($_POST["article"], $storage_options );

exit(json_encode($ob));
?>