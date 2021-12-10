<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class tehnomir_enclosure
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
        /*****Учетные данные*****/
    
        // -------------------------------------------------------------------------------------------------
        
        foreach($manufacturers as $manufacturer)
        {

            $manufacturer_id = $manufacturer['manufacturer_id'];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=GetPriceWithCrosses&usr_login={$login}&usr_passwd={$password}&PartNumber={$article}&BrandId={$manufacturer_id}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $curl_result = curl_exec($ch);
            curl_close($ch);
    
            $xml = simplexml_load_string($curl_result, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $xml_result = json_decode($json,TRUE);

            
            //ЛОГ [API-запрос] (вся информация о запросе)
            if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            {
                $DocpartSuppliersAPI_Debug->log_api_request("Получение списка товаров", "https://tehnomir.com.ua/ws/xml.php?act=GetPriceWithCrosses&usr_login={$login}&usr_passwd={$password}&PartNumber={$article}&BrandId={$manufacturer_id}", $json, print_r($xml_result, true) );
            }
    
            if(isset($xml_result["Prices"])) {
    
                $prices = $xml_result["Prices"];
    
                if(isset($prices["Price"]) && !empty($prices["Price"])) {
    
                    if (isset($prices["Price"][0])) {
    
                        foreach($prices["Price"] as $product) {
    
                            $price = (float)$product["Price"];
					
                            //Наценка
                            $markup = $storage_options["markups"][(int)$price];
                            if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                            {
                                $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                            }
                            
                            //Срок доставки
                            $time_to_exe = (int)$product["DeliveryDays"];

                            if(empty($time_to_exe)) {
                                $time_to_exe = 0;
                            }

                            $json_data = array(
        						"is_tehnomir" => true,
        						"deliveryType" => $product["DeliveryType"],
        						"weight" => round($product["Weight"], 3),
        						"supplier" => $product["PriceLogo"],
        						"ProdId" => $product["BrandId"],
        						"SupCode" => $product["PriceLogo"],
        						"Code" => $product["PartNumber"]
        					);
        					
        					$json = json_encode($json_data);
                            

                           //Создаем объек товара и добавляем его в список:
                            $DocpartProduct = new DocpartProduct($product["Brand"],
                            $product["PartNumber"],
                            $product["PartDescriptionRus"],
                            $product["Quantity"],
                            $price + $price*$markup,
                            $time_to_exe + $storage_options["additional_time"],
                            $time_to_exe + $storage_options["additional_time"],
                            NULL,
                            $product["MinOrderQuantity"],
                            $storage_options["probability"],
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            $storage_options["office_caption"],
                            $storage_options["color"],
                            $storage_options["storage_caption"],
                            $price,
                            $markup,
                            2,0,0,'',
                            $json,
                            array("rate"=>$storage_options["rate"])
                            );

                            
                            if($DocpartProduct->valid == true)
                            {
                                array_push($this->Products, $DocpartProduct);
                            }
                        
    
                        }
    
                    } else {

                            $product = $prices["Price"];

                            $price = (float)$product["Price"];
					
                            //Наценка
                            $markup = $storage_options["markups"][(int)$price];
                            if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                            {
                                $markup = $storage_options["markups"][count($storage_options["markups"])-1];
                            }
                            
                            //Срок доставки
                            $time_to_exe = (int)$product["DeliveryDays"];

                            if(empty($time_to_exe)) {
                                $time_to_exe = 0;
                            }                           

                            $json_data = array(
        						"is_tehnomir" => true,
        						"deliveryType" => $product["DeliveryType"],
        						"weight" => round($product["Weight"], 3),
        						"supplier" => $product["PriceLogo"],
        						"ProdId" => $product["BrandId"],
        						"SupCode" => $product["PriceLogo"],
        						"Code" => $product["PartNumber"]
        					);
        					
        					$json = json_encode( $json_data);

                           //Создаем объек товара и добавляем его в список:
                            $DocpartProduct = new DocpartProduct($product["Brand"],
                            $product["PartNumber"],
                            $product["PartDescriptionRus"],
                            $product["Quantity"],
                            $price + $price*$markup,
                            $time_to_exe + $storage_options["additional_time"],
                            $time_to_exe + $storage_options["additional_time"],
                            NULL,
                            $product["MinOrderQuantity"],
                            $storage_options["probability"],
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            $storage_options["office_caption"],
                            $storage_options["color"],
                            $storage_options["storage_caption"],
                            $price,
                            $markup,
                            2,0,0,'',$json,array("rate"=>$storage_options["rate"])
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
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML-JSON") );




$ob = new tehnomir_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>