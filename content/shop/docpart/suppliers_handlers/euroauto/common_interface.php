<?php
header("Content-Type: text/html; charset=utf-8");
require_once($_SERVER["DOCUMENT_ROOT"] . "/vendor/autoload.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

use Emarref\Jwt\Claim; 

class euroauto_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/**
			* Ключ API: 
			* ID пользователя API (user_id): 153
			* ID клиента для заказа в API (user_data_id): 89
			Пример: https://ecore-reseller.euroauto.ru/?id=1&user_id=1&request=данные в формате JWT
		*/
		
		$reseller_id = $storage_options["reseller_id"];
		$api_key     = $storage_options["api_key"];
		$sig_request = md5($reseller_id . $api_key);
		
		//Создаём токен
		$token = new Emarref\Jwt\Token(); 
		
		//Нужный алгоритм кодирования
		$algorithm = new Emarref\Jwt\Algorithm\Hs256($api_key);
		
		//Добавляем загловки (указываем тип токена)
		$token->addHeader(new Emarref\Jwt\HeaderParameter\Custom('typ', 'JWT'));
		
		//--Формируем тело запроса--//
		$token->addClaim(new Claim\PublicClaim('num', $article));
		
		//--флаг кроссов--//
		$token->addClaim(new Claim\PublicClaim('use_cross_references', true)); 
		
		//--Флаг обработки арикула как строки--//
		$token->addClaim(new Claim\PublicClaim('use_search_form', true)); 
		
		
		//Кодируем токен
		$encryption = Emarref\Jwt\Encryption\Factory::create($algorithm);

		$jwt = new Emarref\Jwt\Jwt();
		
		//Сериализуем токен
		$serializedToken = $jwt->serialize($token, $encryption);

		$ch = curl_init();
		$url_request = "https://ecore-reseller.euroauto.ru/";
		$url_request .= "goods/avail_by_num";
		$url_request .= "?id={$sig_request}";
		$url_request .= "&user_id={$reseller_id}";
		$url_request .= "&request={$serializedToken}";

		curl_setopt($ch, CURLOPT_URL, $url_request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$exec = curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url_request, $exec, print_r(json_decode($exec, true), true) );
		}
		
		if ( ! $exec ) 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка CURL", print_r(curl_error($ch), true) );
			}
			curl_close($ch);
			return;
		}
		
		curl_close($ch);
		
		$decode = json_decode($exec, true);
		
		if ( ! $decode ) 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка разбора json", print_r(json_last_error(), true) );
			}
			curl_close($ch);
			return;	
		}
		
		
		if ($decode["result"]==true) {
			
			//Товары в наличии
			$avail_fh 			= $decode["avail_fh"]; 
			//Массив наименований товаров
			$goods_name_list	= $decode["goods_name_list"];
			//Массив товаров
			$goods_list 		= $decode["goods_list"];
			
			
			foreach ($avail_fh as $goods_list_id => $avail_fh_value) {
				
				foreach ($avail_fh_value as $avail_fh_data) {
					
					$goods_name_id = $goods_list[$goods_list_id]["goods_name_id"];
					
					$article = $goods_list[$goods_list_id]["num"];
					$brand	= $goods_list[$goods_list_id]["company_name"];
					$name = $goods_name_list[$goods_name_id]["goods_name_short_ru"];
					$exist = $avail_fh_data["count_avail"];
					
					$price = $avail_fh_data["price_reseller"];
					//Наценка
					//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
					$markup = $storage_options["markups"][(int)$price];
					if($markup == NULL) {
						
						$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						
					}
					
					$price_for_customer = $price + $price * $markup;
					
					$time_to_exe = $avail_fh_data["expected_delivery_days"];
					 
					if ($time_to_exe == NULL) {
						
						$time_to_exe = 0;	
						 
					}
					
					$time_to_exe_max = $time_to_exe;
					
					// /*
					$DocpartProduct = new DocpartProduct(
						$brand, 
						$article,
						$name,
						$exist,
						$price_for_customer,
						$time_to_exe + $storage_options["additional_time"],
						$time_to_exe_guaranteed + $storage_options["additional_time"],
						0,
						1,
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
						'',
						array("rate"=>$storage_options["rate"])
					);
					
					if($DocpartProduct->valid)
					{
						array_push($this->Products, $DocpartProduct);
					}
					// */
				}
				
			}
			
			//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
			}
			
			$this->result = 1;
			
		}
		
	}
}


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON. Требуется Composer") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob =  new euroauto_enclosure(
	$_POST["article"], 
	json_decode($_POST["manufacturers"], true), 
	$storage_options
);
exit( json_encode($ob) );
?>