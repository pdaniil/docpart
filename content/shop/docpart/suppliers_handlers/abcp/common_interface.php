<?php
//header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('memory_limit', '512M');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class abcp_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article_reqeust, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = urlencode($storage_options["login"]);
        $password = md5($storage_options["password"]);
		$subdomain = $storage_options["subdomain"];
		/*****Учетные данные*****/
        
		
		//СНАЧАЛА ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://".$subdomain.".public.api.abcp.ru/search/brands?userlogin=".$login."&userpsw=".$password."&number=".$article_reqeust);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $execute = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		
		
		//ЛОГ API-запроса (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article_reqeust,"http://".$subdomain.".public.api.abcp.ru/search/brands?userlogin=".$login."&userpsw=".$password."&number=".$article_reqeust, $execute, print_r(json_decode($execute, true), true) );
		}
		
		$result_array = json_decode($execute, true);
        
        //Формируем массив брэндов:
        $brands_array = array();
        foreach ($result_array as $value) 
		{	
            array_push($brands_array, $value["brand"]);
        }
        
		
		//ЛОГ - сообщение (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед циклом по брэндам");
		
        
        //ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
        for ( $i = 0; $i < count ( $brands_array ); $i++ ) 
		{
            // инициализация сеанса
            $ch = curl_init();
			
			$url_reqeust = "http://{$subdomain}.public.api.abcp.ru/search/articles/?userlogin={$login}&userpsw={$password}&number={$article_reqeust}&brand={$brands_array[$i]}";
			
            // установка URL и других необходимых параметров
            curl_setopt($ch, CURLOPT_URL, $url_reqeust);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
			
            // загрузка страницы и выдача её браузеру
            $exec = curl_exec($ch);
            // завершение сеанса и освобождение ресурсов
            curl_close($ch);
            
			
			//ЛОГ API-запроса (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article_reqeust." и производителю ".$brands_array[$i], $url_reqeust, $exec, print_r(json_decode($exec, true), true) );
			}
			
            $result_array = json_decode($exec, true);
			
			
            foreach($result_array as &$value) 
			{
                $exist=$value["availability"];
                $price = $value["price"];
				$min_order	= $value["packing"];				   

                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
                
				$article = str_replace( "'", "", $value["number"] );
				
				$sao_data = array( 'brand' => $value["brand"],
									'number' => $article,
									'itemKey' => $value['itemKey'],
									'supplierCode' => $value['supplierCode'],
									'code' => $value['code'] );
				
				$sao_json = json_encode( $sao_data );
				
                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct($value["brand"],
                    $article,
                    $value["description"],
                    $exist,
                    $price + $price*$markup,
                    (int)($value["deliveryPeriod"]/24) + $storage_options["additional_time"],
                    (int)($value["deliveryPeriod"]/24) + $storage_options["additional_time"],
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
                    2,
					0,
					0,
					'',
					$sao_json,
					array( "rate"=>$storage_options["rate"] ) );
				
				if ( $DocpartProduct->valid == true ) 
				{	
					array_push($this->Products, $DocpartProduct);	
				}
            }
        }//~for $brands_array
		$this->result = 1;
		
		
		//ЛОГ результирующего объекта
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	}//~function __construct($article)
};//~class abcp_enclosure



$storage_options = json_decode($_POST["storage_options"], true);


//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new abcp_enclosure($_POST["article"], $storage_options );

exit(json_encode($ob));
?>