<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class avtobat_enclosure
{
	public $result;
	
    public $Products = array();//Список товаров
    
    public $client = null;
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
        $this->result = 0;//По умолчанию
        
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
        /*****Учетные данные*****/


        //ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Перед созданием SoapClient. Для авторизации используем<br>Логин: '$login', Пароль: '$password'. <br>Артикул: $article. <br>Поставщики: ".print_r($manufacturers, 1)."");

		$this->client = new SoapClient('https://avtobat.com.ua/price.php?wsdl', 
								 array('soap_version' => SOAP_1_2, 
                                       'encoding'=>'UTF-8'));


        try
		{
			// Загружаем каталоги с деталями 
			$client_id = $this->client->getUniqId($login, $password);
		}
		catch(Exception $e)
		{
            
   			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода getUniqId. Неверные логин или пароль.", print_r($e, true) , $e->getMessage() );
			}
			
			return false;
        }


        //ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Получили уникальный идентификатор пользователя: '$client_id'");
        
        // -------------------------------------------------------------------------------------------------
        
        foreach($manufacturers as $manufacturer)
        {
            $manufacturer_name = $manufacturer['manufacturer'];

            try
            {
                // Загружаем каталоги с деталями 
                $xml_products = $this->client->getPrice($client_id, $article, $manufacturer_name);
            }
            catch(Exception $e)
            {

                //ЛОГ - [ИСКЛЮЧЕНИЕ]
                if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                {
                    $DocpartSuppliersAPI_Debug->log_exception("Исключение при вызове SOAP-метода getPrice", print_r($e, true) , $e->getMessage() );
                }
                
                return false;
            }



            if (empty($xml_products)) {

                //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
                if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
                {
                    $DocpartSuppliersAPI_Debug->log_error("Пустой ответ", 'Запрос всех товаров вернул пустой результат. Артикул - '.$article. '. Производитель - '.$manufacturer_name. 'Результат - '.print_r($xml_products, true) );
                }
                
                $this->status = 0;
                return false;
    
            }

            //Формируем массив брэндов:
            foreach ($xml_products as $product) 
            {

                $price = (float)$product["price"];
        
                //Наценка
                $markup = $storage_options["markups"][(int)$price];
                if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                {
                    $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                }
                
                //Срок доставки
                $time_to_exe = $product["term_day"]; //Время поступления

                $exist_start = 0;
                $exist_end   = 0;

                if(empty($time_to_exe)) {
                    $exist_start = 0;
                    $exist_end   = 0;
                } else {
                    if (preg_match('/(-)/', $time_to_exe)) {

                        $exist_arr = explode("-", $time_to_exe);
                        $exist_start = trim($exist_arr[0]);
                        $exist_end   = trim($exist_arr[1]);

                        
                    } else {

                        $time_arrive = new DateTime($product["term_day"]);//Время поступления
                        $time_now = new DateTime();
                        $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
                        $time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
    
                        $exist_start = $time_interval->days;
                        $exist_end   = $time_interval->days;

                    }
                }
                

                $exist = $product["stock"];

                if ($exist == 'M') {
                    $exist = 100;
                }

                //Создаем объек товара и добавляем его в список:
                $DocpartProduct = new DocpartProduct($product["brand"],
                $product["code"],
                $product["name"],
                (int) $exist,
                $price + $price*$markup,
                $exist_start + $storage_options["additional_time"],
                $exist_end   + $storage_options["additional_time"],
                NULL,
                0,
                $storage_options["probability"],
                $storage_options["office_id"],
                $storage_options["storage_id"],
                $storage_options["office_caption"],
                $storage_options["color"],
                $storage_options["storage_caption"],
                $price,
                $markup,
                2,0,0,'',array("date" => $product["term_day"], "exist" => $product["stock"]),array("rate"=>$storage_options["rate"])
                );

                if($DocpartProduct->valid == true)
                {
                    array_push($this->Products, $DocpartProduct);
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
};//~class armtek_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );



$ob = new avtobat_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
$ob->client = 0;
exit(json_encode($ob));
?>