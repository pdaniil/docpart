<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class adeo_enclosure
{
	public $result;
	public $client = null;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		
		$login = $storage_options["login"];
		$password = $storage_options["password"];
		
		foreach($manufacturers as $manufacturer)
		{
		    
		    $manufacturer_name = $manufacturer["manufacturer"];
		    
		    
		    //XML-данные для запроса списка товаров по Артикулу и Брэнду
    		$xml='<?xml version="1.0" encoding="UTF-8" ?>
    		 <message>
    		   <param>
    			 <action>price</action>
    			 <login>'.$login.'</login>
    			 <password>'.$password.'</password>
    			 <code>'.$article.'</code>
    			 <brand>'.$manufacturer_name.'</brand>
    			 <sm>1</sm>
    		  </param>
    		</message>';
    		
    		
    		$data = array('xml' => $xml);
    		$address="http://adeo.pro/pricedetals2.php";//Адрес для запроса
    		$ch = curl_init($address);
    		curl_setopt($ch, CURLOPT_HEADER, 0);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    		curl_setopt($ch, CURLOPT_POST,1);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    		$result=curl_exec($ch);//Получаем рузультат в виде xml
		    
		    //Формируем массив брэндов, у которых есть данный артикул:
		    $items = simplexml_load_string($result);//Объект xml ридера
		    
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer_name, "http://adeo.pro/pricedetals2.php<br>Объект запроса (XML):<br>".htmlentities($xml), htmlentities($result), print_r($items, true) );
			}
			
			if(curl_errno($ch))
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", curl_error($ch) );
				}
			}
			
			
			
		    if(!empty($items)) 
			{
		    
		    foreach($items as $item) {
		        

                    $value = (array)$item;
                    
                    
                    //Наценка
    				$markup = $storage_options["markups"][(int)$value['price']];
    				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    				{
    					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
    				}
    				
    				

    				//Создаем объек товара и добавляем его в список:
    				$DocpartProduct = new DocpartProduct($value['producer'],
    					$value['code'],
    					$value['caption'],
    					$value['rest'],
    					$value['price'] + $value['price']*$markup,
    					$value['deliverydays'] + $storage_options["additional_time"],
    					$value['delivery'] + $storage_options["additional_time"],
    					$value['stock'],
    					1,
    					$storage_options["probability"],
    					$storage_options["office_id"],
    					$storage_options["storage_id"],
    					$storage_options["office_caption"],
    					$storage_options["color"],
    					$storage_options["storage_caption"],
    					$value['price'],
    					$markup,
    					2,0,0,'',json_encode(array("code"=>$value['code'], "b_id"=>$value['b_id'])),array("rate"=>$storage_options["rate"])
    					);
    				
    				//var_dump($DocpartProduct);
    				if($DocpartProduct->valid == true)
    				{
    					array_push($this->Products, $DocpartProduct);
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
};//~class adeo_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );


$ob = new adeo_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>