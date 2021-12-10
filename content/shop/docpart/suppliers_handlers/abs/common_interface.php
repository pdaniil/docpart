<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class abs_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["user"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		// Получаем heah обязательный для каждого запроса.
		$hash = mb_strtolower(md5($login . mb_strtolower(md5($password), 'utf-8')), 'utf-8');
		
		// Получаем контекст - необходиму информацию о профиле пользователя ABS
		$ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://api.abs-auto.ru/api-get_user_context?auth=$hash&format=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получаем контекст - необходимую информацию о профиле пользователя ABS", "http://api.abs-auto.ru/api-get_user_context?auth=$hash&format=json", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
        
        $curl_result = json_decode($curl_result, true);
		$ua_id = $curl_result['user_agreements'][0]['ua_id'];// ID договора
		
		
        
        //ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
		$ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://api.abs-auto.ru/api-search?auth=$hash&article=$article&with_cross=1&agreement_id=$ua_id&format=json");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://api.abs-auto.ru/api-search?auth=$hash&article=$article&with_cross=1&agreement_id=$ua_id&format=json", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		
        $curl_result = json_decode($curl_result, true);

		
		if($curl_result['status'] == 'OK')
		{
			foreach($curl_result['data'] as $value){
				
				// Цена
				$price = $value["price"] * 1;
				
				//Сначала проверяем корректность строки:
        		if((string)$value["brand"]=="" || (string)$value["article"]=="" || (string)$value["product_name"]=="" || (float)$price==(float)0)
    			{
    				continue;
    			}
				
                //Обработка времени доставки:
                $timeToExe = (int)$value["delivery_duration"];
                
                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }
                
                
                //Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($value["brand"],
                    $value["article"],
                    $value["product_name"],
                    $value["quantity"],
                    $price + $price*$markup,
                    $timeToExe + $storage_options["additional_time"],
                    $timeToExe + $storage_options["additional_time"],
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
		}
		else
		{
			
		}
		$this->result = 1;
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		
	}//~function __construct($article)
};//~class abs_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new abs_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>