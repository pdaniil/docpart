<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class shate_m_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
		$login = $storage_options["login"];
		$password = $storage_options["password"];
        $api_key = $storage_options["api_key"];
		/*****Учетные данные*****/
        
		// -------------------------------------------------------------------------------------------------
		//Авторизуемся
		
		$api_path = "";
		switch($storage_options["country"])
		{
			case "ru":
				$api_path = "https://api.shate-m.ru/";
				break;
			case "by":
				$api_path = "https://api.shate-m.com/";
				break;
			case "kz":
				$api_path = "https://api.shate-m.kz:4443/";
				break;
			default: $api_path = "https://api.shate-m.ru/";
		}
		
		// Сперва на сервере используется HTTP Basic авторизация, выполняем ее: 
		$url = $api_path . 'login/'; 

		$ch = curl_init(); 
		
		$headers_answer = array();
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата

		curl_setopt($ch, CURLOPT_URL, $url); 
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5');
		curl_setopt($ch, CURLOPT_USERPWD, $login.":".$password); 

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "ApiKey=".$api_key);
		
		// this function is called by curl for each header received
		curl_setopt( $ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use ( & $headers_answer ) {
			
			$len = strlen($header);
			
			$header = explode(':', $header, 2);
			
			if ( count( $header ) < 2 ) // ignore invalid headers
			  return $len;

			$name = strtolower( trim( $header[0] ) );
			
			if ( ! array_key_exists( $name, $headers_answer ) )
				$headers_answer[$name] = array( trim( $header[1] ) );
			else
				$headers_answer[$name][] = trim( $header[1] );

			return $len;
			
		});
				
		$result = curl_exec($ch); 
		// Читаем token
		$token = $headers_answer['token'][0];
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос на авторизацию", $url."<br>CURLOPT_USERPWD: ".$login.":".$password."<br>Метод POST<br>Поля: "."ApiKey=".$api_key, $result, "Полученный токен: ".$token );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
		// -------------------------------------------------------------------------------------------------
		
		if ( ! isset( $token ) ) {
			
			return;
			
		}

		$headers = array( "Token: {$token}" );
		
		//По каждому производителю
		for($i = 0; $i < count($manufacturers); $i++)
		{
			// Запрашиваем товары по артикулу.
			
			$action = "api/search/GetPricesByArticle/";
			$params_action = array(
				"articleCode" => $article,
				"tradeMarkName" =>  $manufacturers[$i]['manufacturer'],
				"tradeMarkId" => $manufacturers[$i]['manufacturer_id'],
				"includeAnalogs" => "true"
			);
			
			$url_request = $api_path . $action . "?" . http_build_query( $params_action );
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // отключение сертификата
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // отключение сертификата
			curl_setopt($ch, CURLOPT_HEADER, 0);

			curl_setopt($ch, CURLOPT_URL, $url_request); 
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5');

			curl_setopt($ch, CURLOPT_POST, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
			
			$result = curl_exec($ch);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturers[$i]['manufacturer'], $url_request."<br>Метод GET<br>Заголовки: ".print_r($headers,true)."<br>Поля: ".print_r($params_action,true), $result, print_r(json_decode($result, true), true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
				}
			}
			
			$result_arr = (array)json_decode($result, true); 

			if(!empty($result_arr['PriceModels'])){
				//var_dump($result_arr['PriceModels']);
				foreach($result_arr['PriceModels'] as $item){
					
					$ArticleCode 	= $item['ArticleCode'];// Артикул
					$MarkName 		= $item['TradeMarkName'];// производитель
					$name 			= $item['Description'];// Описание детали
					
					if(!empty($item['ArticlePriceInfo'])){
						foreach($item['ArticlePriceInfo'] as $item_price){
							
							
							$price = (float)$item_price["Price"];
							
							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							{
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}
							
							//Набор параметров для SAO
							$json_params = array("OfferKey"=>$item_price["OfferKey"], "ArticleId"=>$item['ArticleId'], "Multiplicity"=>$item_price["Multiplicity"]);
							
							//Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($MarkName,
								$ArticleCode,
								$name,
								$item_price["Qty"],
								$price + $price*$markup,
								$item_price["DeliveryTerm"] + $storage_options["additional_time"],
								$item_price["DeliveryTerm"] + $storage_options["additional_time"],
								NULL,
								$item_price["Multiplicity"],
								$storage_options["probability"],
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],
								$price,
								$markup,
								2,0,0,'', json_encode($json_params), array("rate"=>$storage_options["rate"])
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
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
};//~class shate_m_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new shate_m_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>