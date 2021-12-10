<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class onlinezakaz_enclosure
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
        $password = $storage_options["password"];
		/*****Учетные данные*****/

        
        //СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://onlinezakaz.ru/xmlprice.php?login=$login&password=$password&code=$article&sm=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$xml = simplexml_load_string($curl_result);
			$xml = json_encode($xml);
			$xml = json_decode($xml, true);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://onlinezakaz.ru/xmlprice.php?login=$login&password=$password&code=$article&sm=1", htmlentities($curl_result), print_r($xml, true) );
		}
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
        $xml = simplexml_load_string($curl_result);
        $xml = json_encode($xml);
        $xml = json_decode($xml, true);
        
        $detail_array = $xml["detail"];
		//Приводим к массиву
		if($detail_array["ident"] != NULL)
		{
			$detail_array = array($detail_array);
		}
        
        //Получаем список идентификаторов запчастей по заданному артикулу
        $ident_array = array();
        for($i=0; $i<count($detail_array); $i++)
        {
            array_push($ident_array, $detail_array[$i]["ident"]);
        }
        
        
        //Теперь делаем запросы по каждой найденной запчасти:
        for($i=0; $i<count($ident_array); $i++)
        {
            // инициализация сеанса
            $ch = curl_init();
            // установка URL и других необходимых параметров
            curl_setopt($ch, CURLOPT_URL, "http://onlinezakaz.ru/xmlprice.php?login=$login&password=$password&ident=".$ident_array[$i]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            // загрузка страницы и выдача её браузеру
            $curl_result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$xml = simplexml_load_string($curl_result);
				$xml = json_encode($xml);
				$xml = json_decode($xml, true);
				
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по ID товара ".$ident_array[$i], "http://onlinezakaz.ru/xmlprice.php?login=$login&password=$password&ident=".$ident_array[$i], htmlentities($curl_result), print_r($xml, true) );
			}
			
			
            // завершение сеанса и освобождение ресурсов
            curl_close($ch);
            
            //Сначал нужно проверить тип возвращенного значения: если это объект - значит позиция только одна; если это массив - позиций несколько
            $type_check = simplexml_load_string($curl_result, NULL, LIBXML_NOCDATA);
            $type_check = json_encode($type_check);
            $type_check = json_decode($type_check);
            $result_type = gettype($type_check->detail);
            
            $products_array = array();//Массив с продуктами
            if($result_type == "object")
            {
                $one_product = simplexml_load_string($curl_result, NULL, LIBXML_NOCDATA);
                $one_product = json_encode($one_product);
                $one_product = json_decode($one_product, true);
                $one_product = $one_product["detail"];
                array_push($products_array, $one_product);
            }
            else if($result_type == "array")
            {
                $products_array = simplexml_load_string($curl_result, NULL, LIBXML_NOCDATA);
                $products_array = json_encode($products_array);
                $products_array = json_decode($products_array, true);
                $products_array = $products_array["detail"];
            }
            
            
            for($j=0; $j<count($products_array); $j++)
            {
                $price = $products_array[$j]["price"];
			
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($products_array[$j]["producer"],
					$products_array[$j]["code"],
					$products_array[$j]["caption"],
					$products_array[$j]["rest"],
					$price + $price*$markup,
					$products_array[$j]["deliverydays"] + $storage_options["additional_time"],
					$products_array[$j]["deliverydays"] + $storage_options["additional_time"],
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
        }//По всем типам запчастей
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class onlinezakaz_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new onlinezakaz_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>