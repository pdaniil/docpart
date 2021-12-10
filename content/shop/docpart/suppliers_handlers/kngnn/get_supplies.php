<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class kngnn
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
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

        // var_dump($result);

        $customer_code = $result['customer_code'];
        $contract_code = $result['contract_code'];

        if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Запрос авторизации", print_r($result, true) );
		}

        // var_dump($contract_code);

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

		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;

        // var_dump($result_brand);

        if(count($result_brand['result']) > 0) {

            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Ответ производителей", print_r($result_brand, true) );
            }

            // var_dump($result_brand['result']);

            foreach ($result_brand['result'] as $product) {
                // var_dump($product);
                $price = ceil($product["price"]);

                $markup = $storage_options["markups"][(int)$price];
                if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                {
                    $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                }

                //Создаем объек товара и добавляем его в список:
                    $DocpartProduct = new DocpartProduct(
                        $product['brand'],
                        $product['article'],
                        $product['name'],
                        $product["limited"],
                        $price + $price*$markup,
                        $storage_options["additional_time"],
                        $storage_options["additional_time"],
                        NULL,
                        $product["min_count"],
                        $storage_options["probability"],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $price,
                        $markup,
                        2,
                        0,
                        0,
                        '',
                        NULL,
                        array("rate"=>$storage_options["rate"])
                    );
                
                if($DocpartProduct->valid == true)
                {
                    array_push($this->Products, $DocpartProduct);
                }
            }

            //ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
            }
            
            $this->result = 1;
        }
	}//~function __construct($article)
};//~class shate_m_enclosure

// $_POST["article"] = 'C110';
// $_POST["manufacturers"] = '[{"manufacturer":"ABROINDUSTRIESINC","manufacturer_id":13584,"manufacturer_show":"ABROINDUSTRIESINC","name":"\u041e\u0447\u0438\u0441\u0442\u0438\u0442\u0435\u043b\u044c \u043a\u0430\u0440\u0431\u044e\u0440\u0430\u0442\u043e\u0440\u0430 480 \u043c\u043b +20% \u0430\u044d\u0440\u043e\u0437\u043e\u043b\u044c ABRO MASTERS","storage_id":"17","office_id":"1","synonyms_single_query":true,"params":null,"valid":true}]';
// $_POST["storage_options"] = '{"login":"potapov@don66.ru","password":"167901","probability":"90","color":"#ffffff","markups":[0],"office_id":"1","storage_id":"17","additional_time":"0","office_caption":"\u041e\u0441\u043d\u043e\u0432\u043d\u0430\u044f \u0442\u043e\u0447\u043a\u0430 \u043e\u0431\u0441\u043b\u0443\u0436\u0438\u0432\u0430\u043d\u0438\u044f","storage_caption":"","rate":"1","group_id":"2"}';

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new kngnn($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>