<?php 
header('Content-type: text/html; charset=UTF-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

// ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class kngnn {

    
    public $status;
    public $ProductsManufacturers = array();

    public function __construct($article, $storage_options) {

        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $login = $storage_options["login"];
        $password = $storage_options["password"];

        $url = "http://rest.kngnn.ru/auth/get";

        $basic = base64_encode($login . ":" . $password);

        $headers = array(
            "Authorization: Basic {$basic}"
        );

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        $execute = curl_exec($ch);


        $result = json_decode($execute, true);

        $customer_code = $result['customer_code'];
        $contract_code = $result['contract_code'];

        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Запрос авторизации", print_r($result, true) );
		}

        // var_dump($contract_code);


        $this->status = 1;
        
        $params = array(
            "customer" => $customer_code,
            "contract" => $contract_code,
            "mode" => "all",
            "category" => "",
            "keyword" => $article,
        );

        // var_dump($params);

        $query = http_build_query($params);

        $url = "http://rest.kngnn.ru/auth/products/0/1000?" . $query;

        // var_dump($url);

        $headers = array(
            "Authorization: Basic {$basic}",
        );

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        $execute = curl_exec($ch);


        $result_brand = json_decode($execute, true);

        // var_dump($result_product);

        if(count($result_brand['result']) > 0) {
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Ответ производителей", print_r($result_brand, true) );
            }

            // var_dump($result_brand['result']);

            foreach ($result_brand['result'] as $product) {

                $DocpartManufacturer = new DocpartManufacturer(
                    $product['brand'],
                    $product['id'],
                    $product["name"],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    true//Посылать только один запрос для одного синонима
                );
            
                array_push($this->ProductsManufacturers, $DocpartManufacturer);

            }

    //    ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
            }
        }
    }
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new kngnn($_POST["article"], $storage_options);
exit(json_encode($ob));

?>