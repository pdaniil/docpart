<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set("memory_limit", "256M");

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class abs_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacurers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["user"];
        $password = $storage_options["password"];
		$ua_id = $storage_options["ua_id"];//ID договора
		/*****Учетные данные*****/
        
		// Получаем heah обязательный для каждого запроса.
		$hash = mb_strtolower(md5($login . mb_strtolower(md5($password), 'utf-8')), 'utf-8');
		
		//Получаем ID договора. Если его нет в storage_options - получаем по API и записываем в БД, чтобы больше не тратить на это время
		if( $ua_id == NULL ) {
			
			// Получаем контекст - необходиму информацию о профиле пользователя ABS
			$ch = curl_init();
			// установка URL и других необходимых параметров
			curl_setopt($ch, CURLOPT_URL, "https://api.absel.ru/api-get_user_context?auth=$hash&format=json");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			// загрузка страницы и выдача её браузеру
			$curl_result = curl_exec($ch);
			// завершение сеанса и освобождение ресурсов
			curl_close($ch);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получаем контекст - необходимую информацию о профиле пользователя ABS", "https://api.absel.ru/api-get_user_context?auth=$hash&format=json", $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			
			$curl_result = json_decode($curl_result, true);
			$ua_id = $curl_result['user_agreements'][0]['ua_id'];// ID договора
			
			
			
			//ЗАПИШЕМ ТЕПЕРЬ ЗНАЧЕНИЕ VKORG В БД, ЧТОБЫ В СЛЕДУЮЩИЙ РАЗ ЕГО НЕ ЗАПРАШИВАТЬ
			//Соединение с основной БД
			$DP_Config = new DP_Config;//Конфигурация CMS
			//Подключение к БД
			try
			{
				$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
			}
			catch (PDOException $e) 
			{
				exit("No DB connect");
			}
			$db_link->query("SET NAMES utf8;");
			//Получаем текущие настройки ABS
			$connection_options_query = $db_link->prepare('SELECT `connection_options` FROM `shop_storages` WHERE `id` = :id;');
			$connection_options_query->bindValue(':id', $storage_options["storage_id"]);
			$connection_options_query->execute();
			$connection_options_record = $connection_options_query->fetch();
			$connection_options = json_decode($connection_options_record["connection_options"], true);
			$connection_options["ua_id"] = $ua_id;
			
			
			$update_query = $db_link->prepare('UPDATE `shop_storages` SET `connection_options` = :connection_options WHERE `id` = :id;');
			$update_query->bindValue(':connection_options', json_encode($connection_options));
			$update_query->bindValue(':id', $storage_options["storage_id"]);
			$update_query->execute();
		}
		
		
		for($m=0; $m < count($manufacurers); $m++)
		{
			//ТЕПЕРЬ ПОЛУЧАЕМ СПИСОК ТОВАРОВ ПО ВСЕМ БРЭНДАМ:
			$ch = curl_init();
			// установка URL и других необходимых параметров
			curl_setopt($ch, CURLOPT_URL, "https://api.absel.ru/api-search?auth=$hash&article=$article&with_cross=1&agreement_id=$ua_id&format=json&brand=".urlencode($manufacurers[$m]["manufacturer"]));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			// загрузка страницы и выдача её браузеру
			$curl_result = curl_exec($ch);
			// завершение сеанса и освобождение ресурсов
			curl_close($ch);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacurers[$m]["manufacturer"], "https://api.absel.ru/api-search?auth=$hash&article=$article&with_cross=1&agreement_id=$ua_id&format=json&brand=".$manufacurers[$m]["manufacturer"], $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			
			$curl_result = json_decode($curl_result, true);
			
			if($curl_result['status'] == 'OK')
			{
				foreach($curl_result['data'] as $value)
				{
					// Цена
					$price = $value["price"] * 1;
					
					//Сначала проверяем корректность строки:
					if((string)$value["brand"]=="" || (string)$value["article"]=="" || (string)$value["product_name"]=="" || (float)$price==(float)0)
					{
						continue;
					}
					
					//Обработка времени доставки:
					$delivery_duration = $value["delivery_duration"];
					$delivery_duration = preg_replace('/\s/', '', $delivery_duration);
					$delivery_duration_arr = explode('-', $delivery_duration);
					
					$timeToExe = (int)$delivery_duration_arr[0];
					$timeToExeG = (int)$delivery_duration_arr[0];
					if(isset($delivery_duration_arr[1])) {
						$timeToExeG = (int)$delivery_duration_arr[1];
					}
					
					//Наценка
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					{
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
					}
					
					$probability = 100 - $value["fail_percent"];
					
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($value["brand"],
						$value["article"],
						$value["product_name"],
						$value["quantity"],
						$price + $price*$markup,
						$timeToExe + $storage_options["additional_time"],
						$timeToExeG + $storage_options["additional_time"],
						NULL,
						1,
						$probability,
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
		}
        
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
        
		$this->result = 1;
	}//~function __construct($article)
};//~class abs_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-https-JSON") );



$ob = new abs_enclosure(
	$_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);


exit(json_encode($ob));
?>