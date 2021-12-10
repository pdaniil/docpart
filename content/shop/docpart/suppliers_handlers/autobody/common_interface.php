<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autobody_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$token = $storage_options["token"];
		/*****Учетные данные*****/
        
		
		
		//Запрос товаров по артикулу и производителю
        // инициализация сеанса
        $ch = curl_init();
        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, "http://www.autobody.ru/api/getProductInfo/?token=".$token."&item=".$article);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);
        // завершение сеанса и освобождение ресурсов
        curl_close($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://www.autobody.ru/api/getProductInfo/?token=".$token."&item=".$article, $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		
		$curl_result = json_decode($curl_result, true);
		
		//Если элемент единственный - приводим его к массиву
		if($curl_result["xml_id"] != NULL)
		{
			$curl_result = array($curl_result);
		}
		
		
		for($i=0; $i < count($curl_result); $i++)
		{
			$manufacturer = $curl_result[$i]["properties"]["firm"];
			$article = $curl_result[$i]["code"];
			$name = $curl_result[$i]["name"];
			$price = $curl_result[$i]["prices"][0]["price"];//0 - оптовая цена, 1 - розничная
			
			$amount = $curl_result[$i]["amount"];
			for($j=0; $j < count($amount); $j++)
			{
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($manufacturer,
					$article,
					$name,
					$amount[$j]["quantity"],
					$price + $price*$markup,
					$storage_options["additional_time"],
					$storage_options["additional_time"],
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
				
				if($DocpartProduct->valid == true)
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
};//~class autobody_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new autobody_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>