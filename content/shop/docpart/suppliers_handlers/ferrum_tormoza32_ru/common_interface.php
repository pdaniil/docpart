<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class tormoza32_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		if($article == "" || $article == NULL)
		{
			return;
		}
		
		
		/*****Учетные данные*****/
        $consumer = $storage_options["consumer"];
        $token = $storage_options["token"];
		/*****Учетные данные*****/
		
		
		
		//1. ЗАПРОС НА ПОЛУЧЕНИЕ СПИСКА БРЕНДОВ ПО АРТИКУЛУ
		$ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, "http://ferrum.tormoza32.ru/api/product/export?consumer=$consumer&token=$token&mode=list&article=$article"); 
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
		$curl_result = curl_exec($ch1);
		$Art_List = json_decode($curl_result,true);  
		
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://ferrum.tormoza32.ru/api/product/export?consumer=$consumer&token=$token&mode=list&article=$article", $curl_result, print_r($Art_List, true) );
		}
		
		
		
		
		if ($Art_List[error] != "")
		{
			//Не верный ключ
			
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не верный ключ" );
			}
			
			return;
		}

		
		if(!empty($Art_List['products'])){
			foreach($Art_List['products'] as $item){
				
					$price = $item['purchase_price'];
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($item['manufacturer'],
						$item['article'],
						$item['name'],
						$item['quantity'],
						$price + $price*$markup,
						$item['delivery_time'] + $storage_options["additional_time"],
						$item['delivery_time'] + $storage_options["additional_time"],
						NULL,
						$item['min_quantity'],
						$storage_options["probability"],
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$storage_options["office_caption"],
						$storage_options["color"],
						$storage_options["storage_caption"],
						$price,
						$markup,
						2,0,0,'',array("product_id"=>$item['product_id']),array("rate"=>$storage_options["rate"])
						);
					
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
			}
		}
		
		curl_close($ch1); 
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	}//~function __construct($article)
};//~class tormoza32_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new tormoza32_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>