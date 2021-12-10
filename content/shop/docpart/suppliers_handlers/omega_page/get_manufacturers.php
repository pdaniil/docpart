<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class omega_enclosure
{
    public $status;
     
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$base_url = "https://public.omega.page/public/api/v1.0";
        
        $hash_auth 	   = $storage_options["api_key"];	// Учетные данные
        $url_request   = $base_url . "/product/search";	// Адрес для запроса

        $data = array(
            "SearchPhrase"  =>  $article,
            "Rest"          =>  0,
            "From"          =>  0,
            "Count"         =>  1000,
            "Key"           =>  $hash_auth,
        );

        $data_query = http_build_query($data);
		
		// инициализация сеанса
        $ch = curl_init();

        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, $url_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_query);

        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);

        // завершение сеанса и освобождение ресурсов
        curl_close($ch);

        //ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Ответ от поставщика", $curl_result, print_r( json_decode($curl_result, true), true ) );
        }
        
        $result_array = json_decode($curl_result, true);

        // echo "<pre>";
        // print_r($storage_options);
        // echo "</pre>";

		$brands_unique = array();
		if(!empty($result_array['Result'])){
			foreach($result_array['Result'] as $brand){
				if(!in_array($brand['BrandDescription'], $brands_unique))
				{
					$brands_unique[] = $brand['BrandDescription'];
					
					$DocpartManufacturer = new DocpartManufacturer($brand['BrandDescription'],
						0,
						$brand['Description'],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						true,
						null
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
				}
			}
        }
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
		$this->status = 1;
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



$ob = new omega_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>