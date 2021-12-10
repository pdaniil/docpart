<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class berg_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
	    $this->status = 0;//По умолчанию
		$this->storage_options = $storage_options;
		
		/*****Учетные данные*****/
        $key = $this->storage_options["key"];
		/*****Учетные данные*****/
		
		
		//Запрос по артикулу:
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "https://api.berg.ru/v0.9/ordering/get_stock.json?items[0][resource_article]=".$article."&analogs=1&key=".$key);
        curl_setopt($ch, CURLOPT_HEADER, 0);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // загрузка страницы и выдача её браузеру
        $berg_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "https://api.berg.ru/v0.9/ordering/get_stock.json?items[0][resource_article]=".$article."&analogs=1&key=".$key, $berg_result, print_r(json_decode($berg_result, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
			}
		}
        
		curl_close($ch);
        
        
        $berg_result = json_decode($berg_result, true);
		
        
        //Если по данному артикулу есть несколько производителей:
        if(!empty($berg_result["warnings"]))
        {
            $warnings_array = $berg_result["warnings"];
            if($warnings_array[0]["code"] = "WARN_ARTICLE_IS_AMBIGUOUS")
            {
                //Выдергиваем список брэндов:
                $brands_result = $berg_result["resources"];
                //Делаем теперь запрос по артикулу и брэнду:
                for($i=0; $i<count($brands_result); $i++)
                {
                    $DocpartManufacturer = new DocpartManufacturer($brands_result[$i]["brand"]["name"],
        			    $brands_result[$i]["brand"]["id"],
        				$brands_result[$i]["name"],
        				$storage_options["office_id"],
        				$storage_options["storage_id"],
        				true
        			);
        			
        			array_push($this->ProductsManufacturers, $DocpartManufacturer);
                }
            }
        }
        else//Если warnings нет, то результат можно сразу обрабатывать без детальных запросов
        {
            $berg_result = $berg_result["resources"];
            
            
            $DocpartManufacturer = new DocpartManufacturer($berg_result[0]["brand"]["name"],
			    $berg_result[0]["brand"]["id"],
				$berg_result[0]["name"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true
			);
			
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
        }
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class berg_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new berg_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>