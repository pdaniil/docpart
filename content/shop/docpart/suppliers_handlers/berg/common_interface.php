<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class berg_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public $storage_options;//Для использования в других методах
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
	    $this->result = 0;//По умолчанию
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
		
		
		// завершение сеанса и освобождение ресурсов
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
                    $current_brand_id = $brands_result[$i]["brand"]["id"];
                    $ch = curl_init();
                    // установка URL и других необходимых параметров
                    curl_setopt($ch, CURLOPT_URL, "https://api.berg.ru/v0.9/ordering/get_stock.json?items[0][resource_article]=".$article."&items[0][brand_id]=".$current_brand_id."&analogs=1&key=".$key);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    // загрузка страницы и выдача её браузеру
                    $berg_result_improve = curl_exec($ch);
					
					//ЛОГ [API-запрос] (вся информация о запросе)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$brands_result[$i]["brand"]["name"]." (ID ".$brands_result[$i]["brand"]["id"].")", "https://api.berg.ru/v0.9/ordering/get_stock.json?items[0][resource_article]=".$article."&items[0][brand_id]=".$current_brand_id."&analogs=1&key=".$key, $berg_result_improve, print_r(json_decode($berg_result_improve, true), true) );
					}
					
					if(curl_errno($ch))
					{
						//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
						if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
						{
							$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
						}
					}
					
					// завершение сеанса и освобождение ресурсов
                    curl_close($ch);
					
                    
                    $berg_result_improve = json_decode($berg_result_improve, true);
                    //var_dump($berg_result_improve);
                    $berg_result = $berg_result_improve["resources"];
                    $this->add_to_object_arrays($berg_result);//Передаем в метод добавления к массивам
                }
            }
        }
        else//Если warnings нет, то результат можно сразу обрабатывать без детальных запросов
        {
            $berg_result = $berg_result["resources"];
            $this->add_to_object_arrays($berg_result);//Передаем в метод добавления к массивам
        }
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
        $this->result = 1;
	}//~function __construct($article)
	

	function add_to_object_arrays($resources)
	{
	    //Прогон по массиву объектов и вывод записей
		for($i=0; $i<count($resources); $i++)
		{
			$this_partArray=$resources[$i];//Получаем очередной массив полей одной запчасти из массива массивов
			
			for($offer=0; $offer < count($this_partArray["offers"]); $offer++)
			{
			    //echo $this_partArray["brand"]["name"]." ".$this_partArray["article"]." ".$this_partArray["name"]." ".$this_partArray["offers"][$offer]["quantity"]." ".$this_partArray["offers"][$offer]["assured_period"]." ".$this_partArray["offers"][$offer]["price"]."<br>";

    			$sweep=array(" ","-","/",">","<");
    			
    			//Обработка количества (если есть лишние знаки)
                $quantity = str_replace($sweep,"",  $this_partArray["offers"][$offer]["quantity"]);
                $quantity = (int)$quantity;
                
                
                $price = (float)$this_partArray["offers"][$offer]["price"];
                
                
                //Наценка
    		    $markup = $this->storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $this->storage_options["markups"][count($this->storage_options["markups"])-1];
    		    }
                

                //Создаем объек товара и добавляем его в список:
    			$DocpartProduct = new DocpartProduct($this_partArray["brand"]["name"],
                    $this_partArray["article"],
                    $this_partArray["name"],
                    $quantity,
                    $price + $price*$markup,
                    $this_partArray["offers"][$offer]["assured_period"] + $this->storage_options["additional_time"],
                    $this_partArray["offers"][$offer]["assured_period"] + $this->storage_options["additional_time"],
                    NULL,
                    1,
                    $this->storage_options["probability"],
                    $this->storage_options["office_id"],
                    $this->storage_options["storage_id"],
                    $this->storage_options["office_caption"],
                    $this->storage_options["color"],
                    $this->storage_options["storage_caption"],
                    $price,
                    $markup,
                    2,0,0,'',null,array("rate"=>$this->storage_options["rate"])
                    );
                    
                if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}//~for($offer
		}//~for($i=0;
	}//~public function add_to_object_arrays($resources)
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