<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class omega_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
        $this->result = 0;//По умолчанию

        $base_url = "https://public.omega.page/public/api/v1.0";
        
        $hash_auth 	   = $storage_options["api_key"];	// Учетные данные
        $url_request   = $base_url . "/product/search";	// Адрес для запроса

        $data = array(
            "SearchPhrase"  =>  $article,
            "Rest"          =>  0,
            "From"          =>  0,
            "Count"         =>  1000,
            "Key"           =>  $hash_auth,
        );

        $data_query = http_build_query($data);

        // инициализация сеанса
        $ch = curl_init();

        // установка URL и других необходимых параметров
        curl_setopt($ch, CURLOPT_URL, $url_request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_query);

        // загрузка страницы и выдача её браузеру
        $curl_result = curl_exec($ch);

        // завершение сеанса и освобождение ресурсов
        curl_close($ch);

        //ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Ответ от поставщика", $curl_result, print_r( json_decode($curl_result, true), true ) );
        }
        
        $result_array = json_decode($curl_result, true);

        // echo "<pre>";
        //     print_r($result_array);
        // echo "</pre>";


        if(!empty($result_array['Result']))
		{

            //ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
            $DocpartSuppliersAPI_Debug->log_simple_message("Цикл по производителям");

            foreach($result_array['Result'] as $product){

                // echo "<pre>";
                // print_r($product);
                // echo "</pre>";

                $price = $product["CustomerPrice"];
                
                $markup = $storage_options["markups"][(int)$price];
                if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                {
                    $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                }
                                    
                
                $exists = 0;
                $time_to_exe - 0;

                if (!empty($product["Rests"])) {

                    foreach($product["Rests"] as $stock){

                        switch($stock["Key"]) {
                            case "Харьков":
                                $exists = $stock["Value"];
                                $time_to_exe = 1;
                                break;
                            case "Киев":
                                $exists = $stock["Value"];
                                $time_to_exe = 0;
                                break;
                            case "Киев (Левый берег)":
                                $exists = $stock["Value"];
                                $time_to_exe = 1;
                                break;
                            default: continue;
                        }

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
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;

    }
}



$ob = new omega_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>