<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class omega_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$base_url = "https://public.omega.page/public/api/v1.0";
        
        $hash_auth 	   = $storage_options["api_key"];	       // Учетные данные
        $url_request   = $base_url . "/product/searchBrand";   // Адрес для запроса


        if(!empty($manufacturers))
        
		{

			// инициализация сеанса
            $ch = curl_init();
            // установка URL и других необходимых параметров
            curl_setopt($ch, CURLOPT_URL, $url_request);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
			
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");
			
			foreach($manufacturers as $manufacturer_arr)
			{

                // Запрос позиций по бренду
                $data = array(
                    "Code"          =>  $article,
                    "Rest"          =>  0,
                    "Brand"         =>  $manufacturer_arr['manufacturer'],
                    "Key"           =>  $hash_auth,
                );
      
                $data_query = http_build_query($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_query);
        
                // загрузка страницы и выдача её браузеру
                $curl_result = curl_exec($ch);
        			
				//ЛОГ [API-запрос] (вся информация о запросе)
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_arr['manufacturer']." (ID ".$group_id.")", $url, $execute, print_r(json_decode($execute, true), true) );
				}
				
                $result_array = json_decode($curl_result, true);
                

                // echo "<pre>";
                //     print_r($result_array);
                // echo "</pre>";

				if($result_array['Success']){
					foreach($result_array as $product){

                        $price = $product['CustomerPrice'];
                        
						
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
                        }

						$exists = 0;
						$time_to_exe - 0;

                        if (!empty($product["Rests"])) {

							foreach($product["Rests"] as $stock){

								$is_stock = true;
								$abbr = '';

								switch($stock["Key"]) {
									case "Харьков":
										$exists = $stock["Value"];
										$time_to_exe = 1;
										$abbr = 'ХРК';
										break;
									case "Киев":
										$exists = $stock["Value"];
										$time_to_exe = 0;
										$abbr = 'КВ';
										break;
									case "Киев (Левый берег)":
										$exists = $stock["Value"];
										$time_to_exe = 1;
										$abbr = 'КВЛ';
										break;
									default: $is_stock = false;
								}

								if(!$is_stock) 	continue;


								$DocpartProduct = new DocpartProduct($product["BrandDescription"],
									$product["Number"],
									$product["Description"],
									$exists,
									$price + $price * $markup,
									$time_to_exe,
									0,
									0,
									0,
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
									NULL,
									array("rate"=>$storage_options["rate"])
								);
						
								if($DocpartProduct->valid)
								{
									array_push($this->Products, $DocpartProduct);
								}
							}
                        } 
					}
				}
			}
			curl_close($ch);
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}
}



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$ob =  new omega_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>