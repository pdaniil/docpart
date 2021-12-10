<?php

// http://shop.kais.ru/


header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class kais_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		$host = $storage_options["host"];
		/*****Учетные данные*****/


		if (!empty($manufacturers)) {
			foreach($manufacturers as $manufacturer) {


				$manufacturer_req = preg_replace("/[^a-zа-яё\d]+/iu", '', $manufacturer["manufacturer"]);


				// инициализация сеанса
				$ch = curl_init();
				// установка URL и других необходимых параметров
				curl_setopt($ch, CURLOPT_URL, "https://".$host."/?do=api&full_price=1&all_stores=1&with_analogs=1&article_and_vendor=".$manufacturer_req."_".$article);
				curl_setopt($ch, CURLOPT_USERPWD, "$login:$password"); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);

				$curl_result = curl_exec($ch);

				// echo "<pre>";
				// print_r($curl_result);
				// echo "</pre>";

				// ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$xml_result = simplexml_load_string($curl_result);

					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу и брэнду ".$article, "https://automaster.ru/?do=api&full_price=1&all_stores=1&no_prices=1&with_analogs=1&article_and_vendor=".$manufacturer_req."_".$article, $curl_result, print_r($xml_result, true) );
				}
				
				if(curl_errno($ch))
				{
					//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
					}
				}
				
				// завершение сеанса и освобождение ресурсов
				curl_close($ch);
				

				$xml_result = simplexml_load_string($curl_result);


				if(!empty($xml_result)) {
				
					foreach($xml_result as $item)
					{

						foreach($item->price_item as $product)
						{
							// echo "<pre>";
							// print_r($product);
							// echo "</pre>";


							$price = (int)$product->price_rur;
							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							{
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}

							$min_order = $product->min_qty;
							if(empty($min_order)) {
								$min_order = 1;
							}

							preg_match("/[\d]+/", $product->dlv_day, $delivery_array);

							$delivery_time = $delivery_array[0];
							if(empty($delivery_time)) {
								$delivery_time = 0;
							}
							

					
							// //Создаем объек товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct((string)$product->man_name,
								(string)$product->art,
								(string)$product->part_name,
								(int)$product->qty,
								$price + $price*$markup,
								$delivery_time + $storage_options["additional_time"],
								$delivery_time + $storage_options["additional_time"],
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
		}   

		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;


	}//~function __construct($article)
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new kais_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);


exit(json_encode($ob));
?>