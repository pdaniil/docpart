<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class adkulan_enclosure
{
	public $status;
	public $ProductsManufacturers = array();
	
	
	
	//функция возврата данных по get-запросу (по URL)
	public function get_data_in_url($url)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//инифиализация CURL
		$ch = curl_init();

		//параметры curl
		$options = array(
		   CURLOPT_URL => $url,
		   CURLOPT_RETURNTRANSFER => true,
		   CURLOPT_HEADER => false,
		   CURLOPT_FOLLOWLOCATION => 1,

		   CURLOPT_SSL_VERIFYHOST => false,
		   CURLOPT_SSL_VERIFYPEER => false,
		   CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1);

		curl_setopt_array($ch, $options);

		//запрос данных
		$res = curl_exec($ch);
		curl_close($ch);
		
		//ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Получение списка товаров по артикулу", $res, print_r( json_decode($res, true), true ) );
		}

		//возврат результата в виде массива
		return json_decode($res, true);
	}
	
	
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
	    $this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $client_id = $storage_options["client_id"];
		/*****Учетные данные*****/
		
		
		$debug = 0;// Вывод ошибок
		
		
		$base_url = 'https://adkulan.kz/apiv2?';
		

		//формирование запроса брендов
		$http_qr = array(
		   'client' => $client_id,
		   'mod' => 'rests',// Метод запроса товаров
		   'article' => $article,
		   'with_remotestores' => '1'// Показывать товары партнеров
		);
		
		
		//ЛОГ [ПЕРЕД API-запросом] (название запроса, запрос)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_before_api_request("Получение списка брэндов по артикулу ".$article, $base_url."<br>Параметры: ".print_r($http_qr, true) );
		}
		
		
		$url = $base_url . http_build_query($http_qr);
		
		//получение данных
		$data = $this->get_data_in_url($url);
		
		
		if($data['answer_code'] == 200){
			
			// Цикл по полученным брендам и запрос остатков с учетом брендам
			if(is_array($data['brands']['items'])){
				foreach($data['brands']['items'] as $item){
					$brend = $item['manufacturer'];
					
					if('(нет данных)' == $item["name"]){
						$item["name"] = '';
					}
					
					$DocpartManufacturer = new DocpartManufacturer($brend,
        			    0,
        				$item["name"],
        				$storage_options["office_id"],
        				$storage_options["storage_id"],
        				true
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
	}//~function __construct($article)
};//~class adkulan_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new adkulan_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>