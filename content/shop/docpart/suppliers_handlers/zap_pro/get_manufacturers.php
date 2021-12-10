<?php 
// header('Content-type: application/json');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
// require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class zap_pro {

    
    public $status;
    public $ProductsManufacturers = array();

    public function __construct($article, $storage_options) {


        $login = $storage_options["login"];
        $password = $storage_options["password"];

        $url = "https://zap-pro.ru/api/v1.0/getPrice?login={$login}&password={$password}&code={$article}";

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        
        $execute = curl_exec($ch);
        
        // var_dump($execute);

        $brands = simplexml_load_string($execute);

        $brands = json_decode(json_encode((array)$brands), TRUE);

        // var_dump($brands);

        if( $brands["Результат"] != "OK" )
		{
            $this->status = 0;
			return;
		} 
		
		curl_close( $ch );

		if($brands["Результат"] == "OK") {
            $this->status = 1;

            $products = $brands["СписокПозиций"]["Позиция"];

            // var_dump($products);

            for($i = 0; $i < count($products); $i++) {

                $DocpartManufacturer = new DocpartManufacturer(
                    $products[$i]['Производитель'],
                    0,
                    $products[$i]['Наименование'],
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


$ob = new zap_pro($_POST["article"], $storage_options);
exit(json_encode($ob));

?>