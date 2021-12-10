<?php 
header('Content-type: text/html; charset=UTF-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

// ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class comtt {

    
    public $status;
    public $ProductsManufacturers = array();

    public function __construct($article, $storage_options) {

        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $login = $storage_options["login"];
        $password = $storage_options["password"];
 

        function getToken($login, $password) {
            //  проходит авторизацию и получаем токен

            $url = "http://catalogs.comtt.ru/api/login.php";

            $postdata = array(
                "login" => $login,
                "password" => $password
            );
            $postdata = json_encode($postdata);

            // var_dump($postdata);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_VERBOSE, 1 );
            $execute = curl_exec($ch);


            $result_authorization = json_decode($execute, true);
            
            $f = fopen("t.txt", 'w');
            fwrite($f, $result_authorization['token']);

            return $result_authorization['token'];
        }

        function checkToken($token) {
            // проверка токена
            $url_isToken = "http://catalogs.comtt.ru/api/validate_token.php";

            $postdata_isToken = array(
                "token" => $token
            );
            $postdata_isToken = json_encode($postdata_isToken);

            $ch_isToken = curl_init();
            curl_setopt($ch_isToken, CURLOPT_URL, $url_isToken );
            curl_setopt($ch_isToken, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch_isToken, CURLOPT_HEADER, 0);
            curl_setopt($ch_isToken, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
            curl_setopt($ch_isToken, CURLOPT_FOLLOWLOCATION, true );
            curl_setopt($ch_isToken, CURLOPT_POST, true);
            curl_setopt($ch_isToken, CURLOPT_POSTFIELDS, $postdata_isToken);
            curl_setopt($ch_isToken, CURLOPT_VERBOSE, 1 );
            $execute_isToken = curl_exec($ch_isToken);


            $result_isToken = json_decode($execute_isToken, true);

            // var_dump($result_isToken['message']);

            $delivery_code = "";

            if($result_isToken['message'] == "Доступ разрешен.") {
                return $result_isToken['data']['delivery_code'];
            }  else {
                return false;
            }
        }

        $current_token = "";

        // читаем токе из файла
        $token_file = file_get_contents('t.txt', false);
        $current_token = $token_file;

        // проверяем токен
        $delivery_code = checkToken($token_file);


        //если не прошел проверку запрашиваем новый
        if($delivery_code == false) { 
            $token = getToken($login, $password);
            $token_file = file_get_contents('t.txt', false);
            $current_token = $token_file;
        } 

        // var_dump($isToken);

        $url_product = "http://catalogs.comtt.ru/api/search.php";

        $post_product = array(
            "search" => $article,
            "token" => $current_token,
            "delivery_code" => $delivery_code
        );

        $post_product = json_encode($post_product);

        $ch_product = curl_init();
        curl_setopt($ch_product, CURLOPT_URL, $url_product );
        curl_setopt($ch_product, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch_product, CURLOPT_HEADER, 0);
		curl_setopt($ch_product, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
        curl_setopt($ch_product, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch_product, CURLOPT_POST, true);
		curl_setopt($ch_product, CURLOPT_POSTFIELDS, $post_product);
        curl_setopt($ch_product, CURLOPT_VERBOSE, 1 );
        $execute_product = curl_exec($ch_product);


        $result_product = json_decode($execute_product, true);

        // var_dump($result_product);

        $this->status = 1;

        // var_dump($result_product);

		if($result_product["message"] == "Успешный поиск.") {

            $products = $result_product["search_result"];

            // var_dump($products);

            $code_money = array_shift($products);

            // var_dump($products);

            $array_product = array();

            for($q = 0; $q < count($products); $q++) {
                if($q > 0) {
                    // var_dump(next($products));
                    array_push($array_product, (next($products)));
                    
                } else {
                    // var_dump(current($products));
                    array_push($array_product, (current($products)));
                }
            }
        }
		
        // var_dump($array_product);

        for($i = 0; $i < count($array_product); $i++) {

            //Создаем объек товара и добавляем его в список:
            $DocpartManufacturer = new DocpartManufacturer(
                $array_product[$i]["производитель"],
                0,
                $array_product[$i]["наименование"],
                $storage_options["office_id"],
                $storage_options["storage_id"],
                true,
                array()
            );
            
            array_push($this->ProductsManufacturers, $DocpartManufacturer);
        }

       //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
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


$ob = new comtt($_POST["article"], $storage_options);
exit(json_encode($ob));

?>