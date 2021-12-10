<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autoleader1 {

    public $result;
	
	public $Products = array();//Список товаров

    public function __construct($article, $storage_options) {

        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $this->result = 0;//По умолчанию

        $access = $storage_options["access-token"];
        $city = $storage_options["city"];

        $url = "https://{$city}.autoleader1.ru/api/v1/search/?access-token={$access}&query={$article}";

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        
        $execute = curl_exec($ch);


        $data = json_decode( $execute, true );

        //ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$php_object = simplexml_load_string($curl_result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url, htmlentities($data), print_r($php_object, true) );
		}

        if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка при CURL-запросе остатков", print_r(curl_error($ch), true) );
			}
		}

        curl_close( $ch );

        if($data['error'] == NULL) {
            $this->result = 1;

            $products = $data['data'];
           // var_dump($products);

            for($i = 0; $i < count($products); $i++) {
                for($j = 0; $j < count($products[$i]['stock_list']); $j++) {

                    $markup = $storage_options["markups"][count($storage_options["markups"])-1];

                    if($markup == 0) {
                        $markup = 1;
                    }
                   //Создаем объек товара и добавляем его в список:
                    $DocpartProduct = new DocpartProduct(
                        $products[$i]['brand_name'],
                        $products[$i]['article'],
                        $products[$i]['name'],
                        $products[$i]['stock_list'][$j]['quantity'],
                        $products[$i]['stock_list'][$j]['price']*$markup,
                        $products[$i]['stock_list'][$j]['delivery_min'] + $storage_options["additional_time"],
                        $products[$i]['stock_list'][$j]['delivery_max'] + $storage_options["additional_time"],
                        $products[$i]['stock_list'][$j]['warehouse_id'],
                        0,
                        $storage_options["probability"],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $products[$i]['stock_list'][$j]['price'],
                        $markup,
                        2,
                        0,
                        0,
                        '',
                        NULL,
                        array("rate"=>$storage_options["rate"])
                    );

                    array_push($this->Products, $DocpartProduct);
                }
            }

            //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
            }
            
            $this->result = 1;

        } else {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($data["error"], true) );
            }

            $this->result = 0;
			return;
        }
    }
}

$_POST["article"] = 'C110';
$_POST["storage_options"] = '{"login":"zn@38zn.ru","password":"AvG1234567","access-token":"UM0cpfQvQvTNVbEP","city":"chita","probability":"95","office_id":"2","storage_id":"28"}';

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autoleader1($_POST["article"], $storage_options);
exit(json_encode($ob));

?>