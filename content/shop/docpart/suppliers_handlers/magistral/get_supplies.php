<?php
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class Magistral_enclosure
{
	
	public $result = 0; 
	public $Products = array();
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
        if(!empty($manufacturers)) {
            
            $count = 0;
            foreach($manufacturers as $manufacturer) {
                
                // if ($count > 1) continue;
                
                $manufacturer_code = urlencode($manufacturer["manufacturer"]);
                
                $cid = $storage_options["cid"];
		
		        $url = "https://www.magistral-nn.ru/api/docpart/?cmd=GetPriceList&code={$article}&producer={$manufacturer_code}&cid={$cid}";
                
        		$ch = curl_init();
        		curl_setopt($ch, CURLOPT_URL, $url);
        		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        		curl_setopt($ch, CURLOPT_HEADER, 0);
        		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        		
        		$execute = curl_exec($ch);
        		
        		
        		//ЛОГ [API-запрос] (вся информация о запросе)
        		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        		{
        			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article." и производителю ".$manufacturer, $url, $execute, print_r(json_decode($execute, true), true) );
        		}
        		
        		if(curl_errno($ch))
        		{
        			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
        			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
        			{
        				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
        			}
        		}
        		
        		curl_close($ch);
        		
        		$decode = json_decode($execute);
        		if($decode->MESSAGE == "OK")
        		{
        			for($i = 0; $i < $decode->COUNT; $i++)
        			{
        				$part = $decode->PARTS[$i];
        				
        				// var_dump($part);
        				
        				$price = (float)$part->price;
        	
                        //Наценка
            		    $markup = $storage_options["markups"][(int)$price];
            		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
            		    {
            		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
            		    }
        				$DocpartProduct = new DocpartProduct($part->brand,
        					$part->article,
        					$part->name,
        					$part->quantity,
        					$price+$price*$markup,
        					$part->delivery + $storage_options["additional_time"],
        					$part->delivery + $storage_options["additional_time"],
        					$part->supplier,
        					1,
        					$storage_options["probability"],
        					$storage_options["office_id"],
        					$storage_options["storage_id"],
        					$storage_options["office_caption"],
        					$storage_options["color"],
        					$storage_options["storage_caption"],
        					$price,
        					$markup ,
        					2,
        					0,
        					0,
        					'',
        					null,
        					array("rate"=>$storage_options["rate"]) );
        					
        				// var_dump($DocpartProduct);
        				
        				if($DocpartProduct->valid)
        				{
        					array_push($this->Products, $DocpartProduct);
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
            
            $count++;
            
        }
        
	}
};


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );



$enclosure = new Magistral_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options );
exit(json_encode($enclosure));
?>