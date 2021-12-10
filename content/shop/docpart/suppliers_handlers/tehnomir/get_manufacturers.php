<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class tehnomir_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
	
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
        /*****Учетные данные*****/

             
		// -------------------------------------------------------------------------------------------------
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://tehnomir.com.ua/ws/xml.php?act=GetPriceWithCrosses&usr_login={$login}&usr_passwd={$password}&PartNumber={$article}");
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
            $DocpartSuppliersAPI_Debug->log_api_request("Получение списка производителей", "https://tehnomir.com.ua/ws/xml.php?act=GetPriceWithCrosses&usr_login={$login}&usr_passwd={$password}&PartNumber={$article}", $json, print_r($xml_result, true));
        }

        if(isset($xml_result["Producers"])) {

            $producers = $xml_result["Producers"];

            if(isset($producers["Producer"]) && !empty($producers["Producer"])) {

                if (isset($producers["Producer"][0])) {

                    foreach($producers["Producer"] as $manufacturer) {

                        $DocpartManufacturer = new DocpartManufacturer($manufacturer["Brand"],
                            $manufacturer["BrandId"],
                            $manufacturer["PartDescriptionRus"],
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            true
                        );
                        

                        array_push($this->ProductsManufacturers, $DocpartManufacturer);

                    }

                } else {

                        $manufacturer = $producers["Producer"];

                        $DocpartManufacturer = new DocpartManufacturer($manufacturer["Brand"],
                            $manufacturer["BrandId"],
                            $manufacturer["PartDescriptionRus"],
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            true
                        );
                        

                        array_push($this->ProductsManufacturers, $DocpartManufacturer);

                }

            } else {

                if(isset($xml_result["Prices"])) {

                    $prices = $xml_result["Prices"];
    
                    if(isset($prices["Price"]) && !empty($prices["Price"])) {
        
                        if (isset($prices["Price"][0])) {
        
                            foreach($prices["Price"] as $product) {
        
                              
                                $DocpartManufacturer = new DocpartManufacturer($product["Brand"],
                                    $product["BrandId"],
                                    $product["PartDescriptionRus"],
                                    $storage_options["office_id"],
                                    $storage_options["storage_id"],
                                    true
                            );
                            
    
                            array_push($this->ProductsManufacturers, $DocpartManufacturer);
                            
                            }
        
                        } else {

                            $product = $prices["Price"];


                        $DocpartManufacturer = new DocpartManufacturer($product["Brand"],
                            $product["BrandId"],
                            $product["PartDescriptionRus"],
                            $storage_options["office_id"],
                            $storage_options["storage_id"],
                            true
                             );
                    

                            array_push($this->ProductsManufacturers, $DocpartManufacturer);

                        }
        
                    }

                }
            }

        }
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
		
        $this->status = 1;
	}//~function __construct($article)
};//~class armtek_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new tehnomir_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>