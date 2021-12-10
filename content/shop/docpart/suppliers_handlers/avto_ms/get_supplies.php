<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class auto_mc
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $api_key = $storage_options["key"];
		/*****Учетные данные*****/
		
		$url = "https://avto-ms.ru/api/v1/getPrice/{$api_key}";

        $ch = curl_init();
		// curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        
        $execute =  curl_exec($ch);

        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r(json_decode($execute, true), true) );
        }
        
        if( curl_errno($ch) )
		{
            $this->status = 0;
			return;
		}
		
		curl_close( $ch );

        $data = json_decode( $execute, true );
        // var_dump($data);
        $countItem = 0;
        if(count($data) > 0 ) { 
            foreach ($data as $product) 
            {
                $sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");
                $product["Артикул"] = str_replace($sweep,"", $product["Артикул"]);
                $product["Артикул"] = strtoupper($product["Артикул"]);

                // var_dump($product);
                if($countItem > 500) {
                    break;
                }
                $countItem++;

                if($product["Производитель"] == $manufacturers[0]["manufacturer"]) {



                    $price = (int)$product["с уч. скидки, если есть"];
                    
                    $markup = $storage_options["markups"][(int)$price];
                    // var_dump($markup);
                    if($markup == null || $markup == 0) {
                        $markup = 1;
                    }
                    // var_dump($product["Артикул"]);
                    // var_dump($article);

                        
                    //Создаем объек товара и добавляем его в список:
                    $DocpartProduct = new DocpartProduct(
                        $product['Производитель'],
                        $product['Артикул'],
                        $product['Название'],
                        $product['Кол-во'],
                        $product['с уч. скидки, если есть']*$markup,
                        $storage_options["additional_time"],
                        $storage_options["additional_time"],
                        0,
                        1,
                        $storage_options["probability"],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $product['с уч. скидки, если есть'],
                        $markup,
                        2,
                        0,
                        0,
                        '',
                        NULL,
                        array("rate"=>$storage_options["rate"])
                    );
                    // var_dump($DocpartProduct);

                    if($DocpartProduct->valid)
                    {
                        array_push($this->Products, $DocpartProduct);
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
};

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );


$ob = new auto_mc($_POST["article"],  json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>