<?php


error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class armtek_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacurers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
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
		$DocpartSuppliersAPI_Debug->log_simple_message("По каждой сбытовой организации клиента получаем KUNNR_RG (Покупатель)");
		
		foreach($vkorg_kunnr_rg_structure as $vkorg => $kunnr_rg_array)
		{
			
			//Делаем запрос KUNNR_RG и записываем его в базу данных
			if( count($kunnr_rg_array) == 0 )
			{
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_user/getUserInfo");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
				curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&STRUCTURE=1&VKORG=".$vkorg);
				$curl_result = curl_exec($ch);
				curl_close($ch);
				
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получаем KUNNR_RG по сбытовой организации VKORG ".$vkorg, "http://ws.armtek.ru/api/ws_user/getUserInfo<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод: POST<br>Поля: "."format=json&STRUCTURE=1&VKORG=".$vkorg, $curl_result, print_r(json_decode($curl_result, true), true) );
				}
				
				
				$curl_result = json_decode($curl_result, true);
				
				if($curl_result["STATUS"] != 200)
				{
					var_dump($curl_result["MESSAGES"]);
					continue;
				}
				
				for($k=0; $k < count($curl_result["RESP"]["STRUCTURE"]["RG_TAB"]); $k++)
				{
					array_push($kunnr_rg_array, $curl_result["RESP"]["STRUCTURE"]["RG_TAB"][$k]["KUNNR"]);
				}
				
				$vkorg_kunnr_rg_structure[$vkorg] = $kunnr_rg_array;
				
				
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
			
			
			//По каждому KUNNR_RG
			for($k=0; $k < count($kunnr_rg_array); $k++)
			{
				$KUNNR_RG = $kunnr_rg_array[$k];
				
				
				for($m=0; $m < count($manufacurers); $m++)
				{
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, "http://ws.armtek.ru/api/ws_search/search");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('header'  => "Authorization: Basic ".base64_encode("$login:$password") ) );
					curl_setopt($ch, CURLOPT_POSTFIELDS, "format=json&VKORG=".$vkorg."&KUNNR_RG=".$KUNNR_RG."&PIN=".$article."&QUERY_TYPE=2&BRAND=".$manufacurers[$m]["manufacturer"]);
					$curl_result_parts = curl_exec($ch);
					curl_close($ch);
					
					
					//ЛОГ [API-запрос] (вся информация о запросе)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacurers[$m]["manufacturer"], "http://ws.armtek.ru/api/ws_search/search<br>Авторизация через HTTPHEADER Authorization: Basic base64_encode(".$login.":".$password.")<br>Метод: POST<br>Поля: "."format=json&VKORG=".$vkorg."&KUNNR_RG=".$KUNNR_RG."&PIN=".$article."&QUERY_TYPE=2&BRAND=".$manufacurers[$m]["manufacturer"], $curl_result_parts, print_r(json_decode($curl_result_parts, true), true) );
					}
					
					
					$curl_result_parts = json_decode($curl_result_parts, true);
					
					if($curl_result_parts["STATUS"] != 200)
					{
						continue;
					}
					
					$products = $curl_result_parts["RESP"];
					
					
					for($p=0; $p < count($products); $p++)
					{
						$price = (float)$products[$p]["PRICE"];
					
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						//Срок доставки
						$time_to_exe = 0;
						$time_arrive = new DateTime( $products[$p]["DLVDT"] );//Время поступления
						$time_now = new DateTime();
						$time_now->setTime( 0, 0 ); //Устанавливаем 00:00
						$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
						$time_to_exe = $time_interval->days; //Получаем дни (int)
						
						
						//Набор параметров для SAO
						$json_params = array("VKORG"=>$vkorg, "KEYZAK"=>$products[$p]["KEYZAK"], "KUNNR_RG"=>$KUNNR_RG, "DLVDT"=>$products[$p]["DLVDT"]);
						
						
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($products[$p]["BRAND"],
							$products[$p]["PIN"],
							$products[$p]["NAME"],
							$products[$p]["RVALUE"],
							$price + $price*$markup,
							$time_to_exe + $storage_options["additional_time"],
							$time_to_exe + $storage_options["additional_time"],
							$products[$p]["KEYZAK"],
							$products[$p]["RDPRF"],
							$products[$p]["VENSL"],
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',json_encode($json_params),array("rate"=>$storage_options["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
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
};//~class armtek_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new armtek_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>