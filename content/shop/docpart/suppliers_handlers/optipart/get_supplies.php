<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class optipar_enclosure
{
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$apikey = $storage_options["apikey"];
		$tecdoc = $storage_options["tecdoc"];
		$brand = $manufacturers[0]["manufacturer_show"];

		
		$url = "http://optipart.ru/clientapi/?apikey={$apikey}&action=offers&number={$article}&brand={$brand}";

		// var_dump($url);

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$curl_result = curl_exec($ch);

        
		curl_close( $ch );

		$xml_result = simplexml_load_string($curl_result);
		
		// var_dump($xml_result);

		foreach($xml_result as $item) {
			foreach($item->e as $product) {

				$array = json_decode(json_encode((array)$product), TRUE);
				$data_product = $array["@attributes"];
				// var_dump($data_product);
				
				// var_dump($array["@attributes"]);

				$price = (int)$product->pri;
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = 1;
				}

				$DocpartProduct = new DocpartProduct(
					$data_product["bra"],
					$data_product["cod"],
					$data_product["nam"],
					$data_product["qty"],
					$data_product["pri"]*$markup,
					$data_product["day"] + $storage_options["additional_time"],
					$data_product["day"] + $storage_options["additional_time"],
					0,
					1,
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$data_product["pri"],
					$markup,
					2,
					0,
					0,
					'',
					NULL,
					array("rate"=>$storage_options["rate"])
				);

				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
				$this->result = 1;
			}
		}

	}
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$ob =  new optipar_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>