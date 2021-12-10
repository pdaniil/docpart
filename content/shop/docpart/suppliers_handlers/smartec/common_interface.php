<?php
// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class smartec_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$token == "";
		
		/*****Учетные данные*****/
		
    
		// -------------------------------------------------------------------------------------------------
		//Если нет токена, то получаем его
		//Получаем список сбытовых организаций клиента
		
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

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://api.smartec.ru/auth/login");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/plain"));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"username\":\"{$login}\",\"password\":\"{$password}\"}");
			
			$curl_result = curl_exec($ch);
			
			curl_close($ch);
	
			$result = json_decode($curl_result);
			
			if(isset($result->token)) {
				$token = $result->token;
			} else {
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Ошибка получения токена http://api.smartec.ru/auth/login, логин: $login, пароль: $password", $curl_result, print_r(json_decode($curl_result, true), true) );
				}
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
				$DocpartSuppliersAPI_Debug->log_simple_message("Токен есть.");
			}
		}
		

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://api.smartec.ru/price/source1/{$article}");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('header' => "Authorization: Bearer {$token}"));

		$curl_result = curl_exec($ch);
		
		// -------------------------------------------------------------------------------------------------

		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "http://api.smartec.ru/price/source1/{$article}", $curl_result, print_r(json_decode($curl_result, true), true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
			}
		}

		curl_close($ch);

		$result = json_decode($curl_result);
		$articles = array();
		$articles_ids = array();
		
		if(isset($result->result)) {
		    $articles = $result->result;
		}
		

		if(!empty($articles)) {

    		//--------------По данным ответа---------------//
    		foreach ($articles as $article) {
               $articles_ids[] = $article->articleId;
    		}
    		    		
    		    		
    		$a_ids = implode(",", $articles_ids);
    		
			$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, "http://api.smartec.ru/price/oem/{$a_ids}");
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    		curl_setopt($ch, CURLOPT_HTTPHEADER, array('header' => "Authorization: Bearer {$token}"));
    
    		$curl_result = curl_exec($ch);
    		
 
            // -------------------------------------------------------------------------------------------------

    		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
    		//ЛОГ [API-запрос] (вся информация о запросе)
    		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
    		{
    			$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу", "http://api.smartec.ru/price/source1/", $curl_result, print_r(json_decode($curl_result, true), true) );
    		}
    		
    		if(curl_errno($ch))
    		{
    			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
    			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
    			{
    				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
    			}
    		}
    
    		curl_close($ch);
    
            //Получили массив всех предложений
    		$result = json_decode($curl_result);
    		

    		$products = array();
    		
    		if(isset($result->articles)) {
    		    $products = $result->articles;
    		}
    		

    		if(!empty($products)) {
    		    foreach ($products as $product) {
    		        
                     if(isset($product->orders)) {
                         
                         foreach ($product->orders as $order) {
                             
                            $price = (float)$order->contractorPrice;

            				//Наценка
            				$markup = $storage_options["markups"][(int)$price];
            				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
            				{
            					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
            				}
            				
            				//Срок доставки
				            $time_to_exe = $order->delivery->totalDays;
                             
                             $DocpartProduct = new DocpartProduct($product->brand,
                					$product->brandNumber,
                					$product->description,
                					$order->availability,
                					$price + $price*$markup,
                					$time_to_exe + $storage_options["additional_time"],
                					$time_to_exe + $storage_options["additional_time"],
                					NULL,
                					$order->complect,
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
    		    }
    		}
    		
    		
    		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список товаров", print_r($this->Products, true) );
			}
			
			$this->result = 1;
    		
    		
		}

	}
};//~class smartec_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();

$ob = new smartec_enclosure($_POST["article"], $storage_options);


exit(json_encode($ob));
?>