<?php

header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class adavanta_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		$token = $storage_options["token"];
		/*****Учетные данные*****/
      
      
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

			
			$post_data = array("username" => $login, "password" => $password);
	
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://adavanta.ru/api/v1/login/");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	
			$curl_result_str = curl_exec($ch);

			curl_close($ch);
	
			$result = json_decode($curl_result_str);
	
			if(isset($result->token)) {
				$token = $result->token;
			} else {
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("API-запрос на получение токена", "https://adavanta.ru/api/v1/login/, логин: $login, пароль: $password", $curl_result_str, print_r(json_decode($curl_result_str, true), true) );
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
      
      
      
		foreach($manufacturers as $manufacturer) {

			$brand = preg_replace("/[^a-zа-яё\d]+/iu", '', $manufacturer["manufacturer"]);

			//Получаем список сбытовых организаций клиента
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://adavanta.ru/api/v1/estimate/?number={$article}&brand={$brand}&cross=1");
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('header' => "Authorization: {$token}"));

            $curl_result = curl_exec($ch);
          
			
			// -------------------------------------------------------------------------------------------------

			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "https://adavanta.ru/api/v1/estimate/?number={$article}&brand={$brand}&cross=1", $curl_result, print_r(json_decode($curl_result, true), true) );
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
          
			$products = json_decode($curl_result);
          
	
            if (isset($products) && !empty($products)) {

                //--------------По данным ответа---------------//
                foreach ($products as $product) {

                    $price = (int)$product->price;

                    //Наценка
                    $markup = $storage_options["markups"][(int)$price];
                    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                    {
                        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                    }

                    $min_order = $product->min_order;
                    if(empty($min_order)) {
                        $min_order = 1;
                    }

                    $delivery_time = $product->delivery_days;
                    $delivery_time_guaranteed = $product->delivery_days;


                    if(empty($delivery_time)) {
                        $delivery_time = 0;
                        $delivery_time_guaranteed = 0;
                    }



                    // //Создаем объек товара и добавляем его в список:
                    $DocpartProduct = new DocpartProduct((string)$product->brand,
                        (string)$product->partnumber,
                        (string)$product->description,
                        (int)$product->remain,
                        $price + $price*$markup,
                        $delivery_time + $storage_options["additional_time"],
                        $delivery_time_guaranteed + $storage_options["additional_time"],
                        NULL,
                        $min_order,
                        $storage_options["probability"],
                        $storage_options["office_id"],
                        $storage_options["storage_id"],
                        $storage_options["office_caption"],
                        $storage_options["color"],
                        $storage_options["storage_caption"],
                        $price,
                        $markup,
                        2,0,0,'',null,array("rate"=>$storage_options["rate"], "id_for_order" => $product->id_for_order)
                        );

                    if($DocpartProduct->valid == true)
                    {
                        array_push($this->Products, $DocpartProduct);
                    }

                }

            }

        }


		// -------------------------------------------------------------------------------------------------


			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
			
	}
};//~class armtek_enclosure




//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new adavanta_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);

exit(json_encode($ob));
?>