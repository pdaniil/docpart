<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class busmarket_ua_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$DP_Config = new DP_Config;//Конфигурация CMS
		
		/*****Учетные данные*****/
		$article = urlencode($article);
        $key = $storage_options["key"];
		$user_agent = str_replace(array('http://','https://','/'),'',$DP_Config->domain_path);
		/*****Учетные данные*****/
		
		$headers = array("Authorization: $key", "User-Agent: $user_agent");
		
		if(!empty($manufacturers)){
			foreach($manufacturers as $manufacturer){
				
				$brand = urlencode($manufacturer['manufacturer']);
				
				// ПОЛУЧАЕМ СПИСОК ПРЕДЛОЖЕНИЙ С УЧЕТОМ БРЕНДА:
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "https://api.bm.parts/search/products?q=".$article."&brands=".$brand."&new_product=0");
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result_items = curl_exec($ch);
				curl_close($ch);
				
				
				//ЛОГ API-запроса (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение цен и наличия по бренду ".$brand." и артикулу ".$article,"https://api.bm.parts/search/products?q=".$article."&brands=".$brand."&new_product=0"."<br>Заголовки:<br>".print_r($headers, true), $curl_result_items, print_r(json_decode($curl_result_items, true), true) );
				}
				
				
				$curl_result_items = json_decode($curl_result_items, true);
				
				if(!empty($curl_result_items['products'])){
					foreach($curl_result_items['products'] as $product){
						
						$stocks = array();
						if(!empty($product['in_stocks'])){
							$stocks[] = $product['in_stocks'];
						}
						if(!empty($product['in_others'])){
							$stocks[] = $product['in_others'];
						}
						if(!empty($product['in_waiting'])){
							$stocks[] = $product['in_waiting'];
						}
						
						if(!empty($stocks)){
							foreach($stocks as $stock_item){
								foreach($stock_item as $stock){
									$quantity = (int) trim(str_replace(array(' ', '<', '>', '-', '='), '', $stock['quantity']));
									
									if($quantity > 0){
										
										$price = $product['price'];
										
										//Наценка
										$markup = $storage_options["markups"][(int)$price];
										if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
										{
											$markup = $storage_options["markups"][count($storage_options["markups"])-1];
										}
										
										switch($stock['name']){
											case 'Киев' :
															$time_to_exe = $storage_options["to1"];
															$time_to_exe_garant = $storage_options["tg1"];
											break;
											case 'На других' :
															$time_to_exe = $storage_options["to2"];
															$time_to_exe_garant = $storage_options["tg2"];
											break;
											case 'Ожидается' :
															$time_to_exe = $storage_options["to3"];
															$time_to_exe_garant = $storage_options["tg3"];
											break;
											default :
															$time_to_exe = 3;
															$time_to_exe_garant = 10;
											break;
										}
										
										
										$bu = 0;
										if( ! $product['new_product'] ){
											$bu = 1;
										}
										
										//Создаем объек товара и добавляем его в список:
										$DocpartProduct = new DocpartProduct($product['brand'],
											$product['article'],
											$product['name'],
											$quantity,
											$price + $price*$markup,
											$time_to_exe + $storage_options["additional_time"],
											$time_to_exe_garant + $storage_options["additional_time"],
											$stock['name'],
											1,
											$storage_options["probability"],
											$storage_options["office_id"],
											$storage_options["storage_id"],
											$storage_options["office_caption"],
											$storage_options["color"],
											$storage_options["storage_caption"],
											$price,
											$markup,
											2,0,0,'',array('bu'=>$bu),array("rate"=>$storage_options["rate"])
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
				}
				
			}
		}
		
		$this->result = 1;
		
		//ЛОГ результирующего объекта
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
	}
}


$storage_options = json_decode( $_POST["storage_options"], true );
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob =  new busmarket_ua_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit( json_encode($ob) );
?>