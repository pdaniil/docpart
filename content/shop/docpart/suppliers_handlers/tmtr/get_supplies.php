<?php 
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tmtr {

    public $result;
	public $Products = array();//Список товаров

    public function __construct($article, $manufacturers, $storage_options) {
      //ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();

        $login = $storage_options["login"];
        $password = $storage_options["password"];
        $brand = $manufacturers[0]["manufacturer"];

        $url = "http://api.tmtr.ru/API.asmx/Proboy";
		
        $headers = array(
            "login: $login",
            "password:  $password",
			"Content-Type: application/json"
        );
		
		$postdata = array(
            "article" => $article,
            "brand" => $brand
        );
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

        $message = json_decode($execute, true);

        if($message['Message'] == "Предложение не найдено") {
            $this->result = 1;

                //ЛОГ [API-запрос] (вся информация о запросе)
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, $url, $execute, print_r("Предложение не найдено", true) );
            }
        }

        
       
        // var_dump($execute);
        
		$data = explode(']', $execute);
		$data = $data[0].']';
        $data = json_decode($data, true);
        
        if(count($data) != 0) {
            $this->result = 1;

            $products = $data;
            // var_dump($products);

            for($i = 0; $i < count($products); $i++) {

                $markup = $storage_options["markups"][count($storage_options["markups"])-1];

                if($markup == 0) {
                    $markup = 1;
                }

                // var_dump($products[$i]['DeliveryDate'] . " : " . $products[$i]['GuarantedDate']);

                // время доставки
                $day_delivery = explode('T', $products[$i]['DeliveryDate']); 
                $day_delivery_unix = strtotime($day_delivery[0]);
                if($day_delivery_unix > 0) {
                    $current_day = time();
                    $time_deliv = $day_delivery_unix - $current_day;
                    $time_d = ceil($time_deliv / 86400);
                    if($time_d < 10) {
                        $time_d = str_replace("0", "", $time_d);  
                    }
                } else {
                    // var_dump($products[$i]['DeliveryDate']);
                    // var_dump($products[$i]['StockName']);
                    continue;
                }

                // время гарантированно доставки
                $day_gurant_delivery = explode('T', $products[$i]['GuarantedDate']);                
                $day_gurant_delivery_unix = strtotime($day_gurant_delivery[0]);
                if($day_gurant_delivery_unix > 0) {
                    $current_day = time();
                    $time_deliveru = $day_gurant_delivery_unix - $current_day;
                    $d = new DateTime();
                    $d->setTimestamp($time_deliveru);

                    $time_gurant = ceil($time_deliveru / 86400);
                    if($time_gurant < 10) {
                        $time_gurant = str_replace("0", "", $time_gurant);  
                    }
                } else {
                    continue;
                }
                
                // var_dump($time_d . ":" . $time_gurant);
                
                //Создаем объек товара и добавляем его в список:
                $DocpartProduct = new DocpartProduct(
                    $products[$i]['Producer'],
                    $products[$i]['Article'],
                    $products[$i]['Nomenclature'],
                    $products[$i]['ShowedQuantity'],
                    $products[$i]['Price']*$markup,
                    $time_d + $storage_options["additional_time"],
                    $time_gurant + $storage_options["additional_time"],
                    $products[$i]['StockName'],
                    $products[$i]['MinPartyQuantity'],
                    $storage_options["probability"],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    $storage_options["office_caption"],
                    $storage_options["color"],
                    $storage_options["storage_caption"],
                    $products[$i]['Price'],
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
    }
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );

$ob = new tmtr($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));


?>