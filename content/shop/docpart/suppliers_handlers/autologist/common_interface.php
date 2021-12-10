<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autologist_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $email = base64_encode($storage_options["email"]);
        $pass = base64_encode($storage_options["pass"]);
		/*****Учетные данные*****/

		// инициализация сеанса
		$ch = curl_init();
		// установка URL и других необходимых параметров
		curl_setopt($ch, CURLOPT_URL, "http://autologist.pro/webservice?in=url&out=json");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "email={$email}&password={$pass}&method=getPrice&data[art]={$article}&data[cross]=true");
		// загрузка страницы и выдача её браузеру
		$curl_result = curl_exec($ch);
		// завершение сеанса и освобождение ресурсов
		curl_close($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "http://autologist.pro/webservice?in=url&out=json<br>Метод POST<br>Поля: "."email={$email}&password={$pass}&method=getPrice&data[art]={$article}&data[cross]=true<br>E-mail и пароль преобразованы через base64_encode", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		
		$curl_result = json_decode($curl_result, true);

		if(!empty($curl_result['errors'])){
			return;
		}

		$products_arr = $curl_result['data'];

		foreach($products_arr as &$value)
		{
			$price = $value["price"];

			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			
			$max = (int) $d[0];
			$min = (int) $d[0];
			$d = explode('/', $value["delivery_period"]);
			if(count($d) == 1){
				$max = (int) $d[0];
				$min = (int) $d[0];
			}
			if(count($d) == 2){
				$max = (int) $d[0];
				$min = (int) $d[1];
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($value["brand"],
				$value["art"],
				$value["name"],
				$value["quantity"],
				$price + $price*$markup,
				$min + $storage_options["additional_time"],
				$max + $storage_options["additional_time"],
				null,
				$value["lot_base"],
				(int)$value["successPercent"],
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
		}//~foreach
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
};//~class autologist_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autologist_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>