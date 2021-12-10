<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class armtek_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		// -------------------------------------------------------------------------------------------------
		//Получаем список сбытовых организаций клиента
		$vkorg_kunnr_rg_structure = $storage_options["vkorg_kunnr_rg_structure"];
		if( count($vkorg_kunnr_rg_structure) == 0 )
		{
			$vkorg_kunnr_rg_structure = array();
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_user/getUserVkorgList?format=json");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка сбытовых организаций клиента", "http://ws.armtek.ru/api/ws_user/getUserVkorgList?format=json<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")", $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			
			$curl_result = json_decode($curl_result, true);

			
			if($curl_result["STATUS"] != 200)
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Статус ответа поставщика не равен 200. Подробнее - смотрите в ответе API выше" );
				}
				
				return;
			}
			$VKORG_list_RESP = $curl_result["RESP"];
			
			for($v=0; $v < count($VKORG_list_RESP); $v++)
			{
				$vkorg_kunnr_rg_structure["".$VKORG_list_RESP[$v]["VKORG"]] = array();
			}
			
			
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
			
			
			//Получаем текущие настройки Армтек
			$connection_options_query = $db_link->prepare('SELECT `connection_options` FROM `shop_storages` WHERE `id` = :id;');
			$connection_options_query->bindValue(':id', $storage_options["storage_id"]);
			$connection_options_query->execute();
			$connection_options_record = $connection_options_query->fetch();
			$connection_options = json_decode($connection_options_record["connection_options"], true);
			$connection_options["vkorg_kunnr_rg_structure"] = $vkorg_kunnr_rg_structure;


			$update_query = $db_link->prepare('UPDATE `shop_storages` SET `connection_options` = :connection_options WHERE `id` = :id;');
			$update_query->bindValue(':connection_options', json_encode($connection_options));
			$update_query->bindValue(':id', $storage_options["storage_id"]);
			$update_query->execute();
		}
		// -------------------------------------------------------------------------------------------------
		//По каждой сбытовой организации клиента получаем KUNNR_RG (Покупатель)
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("По каждой сбытовой организации клиента получаем KUNNR_RG (Покупатель) и бренды");
		
		foreach($vkorg_kunnr_rg_structure as $vkorg => $kunnr_rg_array)
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_search/assortment_search");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
			curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&STRUCTURE=1&VKORG=".$vkorg."&PIN=".$article);
			$execute = curl_exec($ch);
			curl_close($ch);
			
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получаем KUNNR_RG и бренды по сбытовой организации VKORG ".$vkorg." и артикулу ".$article, "http://ws.armtek.ru/api/ws_search/assortment_search<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод: POST<br>Поля: "."format=json&STRUCTURE=1&VKORG=".$vkorg."&PIN=".$article, $execute, print_r(json_decode($execute, true), true) );
			}
			
			
			
			$curl_result = json_decode( $execute, true );
			
			if($curl_result["STATUS"] != 200)
			{
				continue;
			}
			
			if ( ! empty( $curl_result["RESP"]["MSG"] ) )
				continue;
			
			
			for($k=0; $k < count($curl_result["RESP"]); $k++)
			{
				$DocpartManufacturer = new DocpartManufacturer($curl_result["RESP"][$k]["BRAND"],
				    0,
					$curl_result["RESP"][$k]["NAME"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true//Посылать только один запрос для одного синонима
				);
				
				
				
				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class armtek_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new armtek_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>