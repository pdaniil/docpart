<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php" );
require_once( $_SERVER["DOCUMENT_ROOT"]."/config.php" );

require_once("ThePartsWS.class.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class the_parts_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturer, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$login		= $storage_options["login"];
		$password	= $storage_options["password"];
		$token		= $storage_options["token"];
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Проверяем, есть ли токен клиента");
		}
		
		if( $token == "" || NULL)
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Токена нет. Получаем токен через API.");
			}
			
			//-----------------------------Сохраняем токен------------------------------------//
			$DP_Config = new DP_Config();
			try
			{
				$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
			}
			catch (PDOException $e) 
			{
				exit("No DB connect");
			}
			$db_link->query("SET NAMES utf8;");

			$OPTIONS = "SELECT `connection_options` FROM `shop_storages` WHERE `id` = ?";
			
			$result_options_query = $db_link->prepare($OPTIONS);
			$result_options_query->execute( array($storage_options["storage_id"]) );
			$mysql_fetch = $result_options_query->fetch();
			
			if( ! $mysql_fetch )
				return;
					
			$connection_options = json_decode($mysql_fetch["connection_options"], true);

			$PartsWS = new ThePartsWS($login, $password); //Получаем токен
			
			$token = $PartsWS->authorize();
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение токена через API", "Создание библиотечного объекта ThePartsWS с указанием логина ".$login." и пароля ".$password.". Вызов метода ThePartsWS->authorize()", "Результат ThePartsWS->authorize() - токен: ".$token, "Дополнительная обработка не требуется" );
			}
			
			
			$connection_options["token"] = $token;
			
			$connection_options_json = json_encode($connection_options);
			
			$OPTION_UPDATE = "
				UPDATE `shop_storages` 
				SET `connection_options` = ? 
				WHERE `id` = ?
			";
			
			$db_link->prepare($OPTION_UPDATE)->execute( array($connection_options_json, $storage_options["storage_id"]) );
			
			//----------------------------------------------------------------------------------//
			
		}
		else
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("Токена есть.");
			}
		}
		
		$pw = new ThePartsWS($login, $password);

		$searchResult = $pw->searchDo2($article, array('present' => true, "original"=>false, "noreplace"=>false));
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков от поставщика", "Создание библиотечного объекта ThePartsWS с указанием токена ".$token.". Вызов метода ThePartsWS->searchDo2(".$article.", ".print_r( array('present' => true, "original"=>false, "noreplace"=>false),true).")", "Результат ThePartsWS->searchDo2()".print_r($searchResult, true), "Дополнительная обработка не требуется" );
		}
		
		
		
		// var_dump($searchResult);
		
		foreach($searchResult as $group) //Идём по брендовым группам
		{
			$items = $group["items"]; //Получаем позиции в текущей группе
			
			foreach($items as $item) //По позициям
			{
				
				$price = $item["price"]; //Цена

				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				$price_for_customer = $price + $price*$markup;
				
				
				$DocpartProduct = new DocpartProduct($item["chname"], 
					$item["code"],
					$item["name"],
					$item["stock"],
					$price_for_customer,
					$item["days_avg"] + $storage_options["additional_time"],
					$item["days_max"]+ $storage_options["additional_time"],
					0,
					$item["quant"],
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
					'',
					null,
					array("rate"=>$storage_options["rate"])
				);
				
				var_dump($DocpartProduct);
				
				if($DocpartProduct->valid)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}
		}
		
		$this->result = 1;
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
	}
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON (библиотека поставщика)") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



ob_start();

$ob = new the_parts_enclosure( $_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );

file_put_contents("dump_comm.log", ob_get_clean());

exit( json_encode($ob) );
?>