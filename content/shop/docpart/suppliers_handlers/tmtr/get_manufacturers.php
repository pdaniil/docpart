<?php 

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tmtr {

    
    public $status;
    public $ProductsManufacturers = array();

    public function __construct($article, $storage_options) {

        //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $login = $storage_options["login"];
        $password = $storage_options["password"];

        $url = "http://api.tmtr.ru/API.asmx/PreProboy";
		
        $headers = array(
            "login: $login",
            "password:  $password",
			"Content-Type: application/json"
        );
		
		$postdata = array("article" => $article);
		$postdata = json_encode($postdata);
        
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        $execute = curl_exec($ch);

        // var_dump($execute);
        
		$data = explode(']', $execute);
		$data = $data[0].']';
        $data = json_decode($data, true);
		
		// var_dump($data);
        $message = json_decode($execute, true);

        if($message['Message'] == "Предложение не найдено") {
            $this->result = 1;
        }
		
        //ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r(json_decode($data, true), true) );
        }
        
        if( curl_errno($ch) )
		{
            $this->status = 0;
			return;
		}
		
		curl_close( $ch );
        // var_dump($execute);
        // $data = json_decode( $execute, true );


		if(count($data) != 0) {
            $this->status = 1;

            $products = $data;

            for($i = 0; $i < count($products); $i++) {

                $DocpartManufacturer = new DocpartManufacturer(
                    $products[$i]['brand'],
                    0,
                    $products[$i]['name'],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    true,
                    null
                );

                array_push($this->ProductsManufacturers, $DocpartManufacturer);
            }
        }
		else {
            //ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_error("Есть ошибка", print_r($data["error"], true) );
            }

            $this->status = 0;
            return;
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



$ob = new tmtr($_POST["article"], $storage_options);
exit(json_encode($ob));

?>