<?php 
header('Content-type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class unitrade {

    public $result;
	public $Products = array();//Список товаров

    public function __construct($article, $manufacturers, $storage_options) {
        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $api = $storage_options["access-token"];
        $login = $storage_options["login"];
        $password = $storage_options["password"];
        $brand = $manufacturers[0]["manufacturer_show"];

        $url = "http://shop.unitrade.kz/backend/price_items/api/v1/search/get_offers_by_oem_and_make_name?api_key={$api}&oem={$article}&make_name={$brand}";

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        
        $execute = curl_exec($ch);
        
        //ЛОГ [API-запрос] (вся информация о запросе)
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

        if($data['result'] == "ok") {
            $this->result = 1;

            $products = $data['data'];
           // var_dump($products);

            for($i = 0; $i < count($products); $i++) {
                $markup = $storage_options["markups"][count($storage_options["markups"])-1];

                if($markup == 0) {
                    $markup = 1;
                }
                //Создаем объек товара и добавляем его в список:
                $DocpartProduct = new DocpartProduct(
                    $products[$i]['make_name'],
                    $products[$i]['oem'],
                    $products[$i]['detail_name'],
                    $products[$i]['qnt'],
                    $products[$i]['cost'] + $products[$i]['cost']*$markup,
                    $products[$i]['min_delivery_day'] + $storage_options["additional_time"],
                    $products[$i]['max_delivery_day'] + $storage_options["additional_time"],
                    0,
                    $products[$i]['min_qnt'],
                    $storage_options["probability"],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    $storage_options["office_caption"],
                    $storage_options["color"],
                    $storage_options["storage_caption"],
                    $products[$i]['cost'],
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

        } else {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($data["error"], true) );
            }

            $this->result = 0;
			return;
        }

        //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
        }
    }
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );

$ob = new unitrade($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));


?>