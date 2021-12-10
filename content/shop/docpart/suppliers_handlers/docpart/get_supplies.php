<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class docpart_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public $storage_options;//Для использования в других методах
	
	public function __construct($article, $manufacturers, $storage_options)
	{
	    $this->result = 0;//По умолчанию
		
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
        $domain 	= $storage_options["domain"];
		$off_id 	= $storage_options["off_id"];
		$no_api 	= $storage_options["no_api"];
		$login 		= $storage_options["login"];
		$password 	= $storage_options["password"];
		/*****Учетные данные*****/
		
		$manufacturers = json_decode($manufacturers, true);
		$manufacturers_Debug = $manufacturers;
		$manufacturers = json_encode($manufacturers[0]['params']);
		
		$postdata = array(
			'action' 	=> "get_products",
			'login' 	=> $login,
			'password' 	=> $password,
			'article' 	=> $article,
			'manufacturers' => $manufacturers,
		);
		
		if(!empty($off_id)){
			$postdata['offices'] = json_encode(explode(',', $off_id));
		}
		
		if(!empty($no_api)){
			$postdata['no_api'] = 1;
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $domain."web_service/api.php");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30); 
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		$curl_result = curl_exec($curl);
		if( curl_errno($curl) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($curl), true) );
			}
			curl_close($curl);
			return;
		}
		curl_close($curl);
		$result = json_decode($curl_result, true);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers_Debug[0]["manufacturer"]." (ID ".$manufacturers_Debug[0]["manufacturer_id"].")", $domain."web_service/api.php", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if($result['result'] === true){
			foreach($result['products'] as $item)
			{
				$price	= $item['price'];
				$markup = $storage_options["markups"][$price];
				
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$time_to_exe = $item['time_to_exe'] + $storage_options["additional_time"];

				$DocpartProduct = new DocpartProduct($item['manufacturer'], 
					$item['article'],
					$item['name'],
					$item['exist'],
					$price + $price * $markup,
					$time_to_exe,
					$time_to_exe,
					$item['office_id'].'_'.$item['storage_id'].'_'.$item['storage'],
					1,
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
					"",
					"",
					array("rate"=>$storage_options["rate"])
				);
				
				if($DocpartProduct->valid)
				{
					array_push($this->Products, $DocpartProduct);
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
};//~class docpart_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new docpart_enclosure($_POST["article"], $_POST["manufacturers"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>