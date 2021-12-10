<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class gpi24_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
		//Запрос товаров по артикулу и производителю
        // инициализация сеанса
        $ch = curl_init();
		
		$service = "http://api.gpi24.ru/";
		$action = "v1.0/details/";
		$params_action = array(
			"article" => $article,
			"key" => $key
		);
		
		$url_request = $service . $action . "?" . http_build_query( $params_action );
	
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $exec = curl_exec($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url_request, $exec, print_r(json_decode($exec, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
			curl_close($ch);
			return;
		}
		
		
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
        
		$decode = json_decode($exec, true);

		$status = $decode['status'];
		
		if ($status == '200') {
			
			$answer = $decode['answer'];
			
			$parts = $answer['details'];
			$cross_parts = $answer['cross'];
	
			foreach( $parts as $part ) {
				
				$dp = $this->getDocpartProduct( $part, $storage_options );
				
				if ( is_object( $dp ) ) {
					
					$this->Products[] = $dp;
					
				}
				
			}
			
			foreach( $cross_parts as $part ) {
				
				$dp = $this->getDocpartProduct( $part, $storage_options );
				
				if ( is_object( $dp ) ) {
					
					$this->Products[] = $dp;
					
				}
				
			}
			
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
		
	}//~function __construct($article)
	
	private function getDocpartProduct( $detal, $storage_options ) {
		
		// Logger::addLog( '$storage_options', $storage_options );
		
		$price = (float)$detal['PRICE'];
		
		$markup = $storage_options["markups"][(int)$price];
		
		if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		{
			$markup = $storage_options["markups"][count($storage_options["markups"])-1];
		}
		
		$exist = $detal['BOX'];
		$time_to_exe = $detal['DAYsDELIVERY'];
		$name = $detal['DESCR'];
		$article = $detal['ARTICLE'];
		$brend = $detal['BRAND'];
		
		$exist = str_replace('<','',$exist);
		$exist = str_replace('>','',$exist);
		$exist = str_replace('=','',$exist);
		$exist = str_replace(' ','',$exist);
		$exist = (int)$exist;
		
		$time_to_exe = str_replace('<','',$time_to_exe);
		$time_to_exe = str_replace('>','',$time_to_exe);
		$time_to_exe = str_replace('=','',$time_to_exe);
		$time_to_exe = str_replace(' ','',$time_to_exe);
		$time_to_exe = (int)$time_to_exe;
		
		//Создаем объек товара и добавляем его в список:
		$dp = new DocpartProduct(
			$brend,
			$article,
			$name,
			$exist,
			$price + $price*$markup,
			$time_to_exe + $storage_options["additional_time"],
			$time_to_exe + $storage_options["additional_time"],
			NULL,
			1,
			$storage_options["probability"],
			$storage_options["office_id"],
			$storage_options["storage_id"],
			$storage_options["office_caption"],
			$storage_options["color"],
			$storage_options["storage_caption"],
			$price,
			$markup,
			2,0,0,'',null,array("rate"=>$storage_options["rate"])
		);
		
		if ( $dp->valid == true ) {
			
			return $dp;
			
		}
		
		return false;
		
	}
	
};//~class gpi24_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new gpi24_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>