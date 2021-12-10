<?php
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class auto1_by_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
		
		
		//Делаем запрос списка организаций
        $ch = curl_init("https://auto1.by/WebApi/GetRequestParameters?login=$login&password=$password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        $curl_result = curl_exec($ch);
        curl_close($ch);
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$xml_result = simplexml_load_string($curl_result);
			
			$DocpartSuppliersAPI_Debug->log_api_request("Запрос списка организаций с указанием артикула ".$article, "http://auto1.by/Articles/GetRequestParameters?number=$article&login=$login&password=$password", htmlentities($curl_result), print_r($xml_result, true) );
		}
		
		if( empty($curl_result) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Поставщик выдал пустой результат.<br>Варианты решения проблемы:<br>1. Проверьте, корректно ли указан Вами логин: '$login'<br>2. Проверьте, корректно ли указан Вами пароль: '$password'<br>3. Узнайте у менеджера поставщика, включен ли для Вас доступ к API" );
			}
		}
		
        $Parameters = new SimpleXMLElement($curl_result);
        

		// цикл по найденным организациям
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Цикл по найденным организациям");
		
		$Organizations = (array)$Parameters->Organizations;
		
		
		if(is_array($Organizations))
		{
			foreach($Organizations as $Organization)
			{
			    
			    if(is_array($Organization)) {
			        
			        foreach($Organization as $Organization_item) {
			            
			            
                            $Organization_item = (array)$Organization_item;
    
            				$OrgName = $Organization_item['OrgName'];// название организации
            				$orgId = $Organization_item['OrgId'];// id организации для поиска
            				$orderType = $Organization_item['OrderType'];// 1 - основной заказ / 2 - дополнительный заказ
            				
            
            				//header("Content-type: text/xml"); //для вывода XML
                            $query = array(
            					'pattern' => $article,
            					'orgId' => $orgId,
            					'orderType' => $orderType,
            					'searchType' => '',
            					'point' => '',
            					'login' => $login,
            					'password' => $password,
            				);
            				$url = 'https://auto1.by/WebApi/Search?'.http_build_query($query);
            				
            				//$header = array("Accept: application/json", "User-Agent: Server");
            				//получить данные в JSON
            				$header = array("Accept: application/json", "User-Agent: Server");
            				
            				$ch = curl_init();
            				curl_setopt($ch, CURLOPT_URL, $url);
            				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            				$curl_result = curl_exec($ch);
            				curl_close($ch);
            				
            				
            				//ЛОГ [API-запрос] (вся информация о запросе)
            				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
            				{
            					$xml_result = json_decode($curl_result);
            					
            					$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу ".$article." в организации ".$OrgName." (ID ".$orgId.")", "https://auto1.by/WebApi/Search?pattern=$article&orgId=$orgId&orderType=$orderType&login=$login&password=$password", htmlentities($curl_result), print_r($xml_result, true) );
            				}
            				
            				
            				$Search = json_decode($curl_result);
            				
            				foreach($Search as $item)
            				{
                                
                                $items = array();
                                
            					if(!is_array($item)) {
            					    $item = (array)$item;
            					    $items[] = $item;
            					} else {
            					    $items = $item;
            					}
            					
            					
            					foreach($items as $brand_item)
            				    {
            					    $Stores = $brand_item['Stores'];
            					    
                                    if(is_array($Stores)) {
                                        
                                        foreach($Stores as $Store)
                						{
                						    
                							$Store = (array)$Store;
                							
                    						$price = (float)$Store["Price"];
                    						
            
                							$exist = $Store["Quantity"];
                							$exist = str_replace(array('<','>','=','+','-',' '),'',$exist);
                							
                							$Rating = (int)$Store["Rating"];
                							if($Rating < 40)
                							{
                								$Rating = 40;
                							}
                							
                							$time = $Store["DeliveryInfo"];
                							
                							$time_arrive = new DateTime($time);
                							
                							$time_now = new DateTime();
                                            $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
                							
                							
                							$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
            						        $time_to_exe = $time_interval->days; //Получаем дни (int)
            						        
            						        
                							//Наценка
                							$markup = $storage_options["markups"][(int)$price];
                							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
                							{
                								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
                							}
                							
                							
                							//Создаем объек товара и добавляем его в список:
                							$DocpartProduct = new DocpartProduct($brand_item["Brand"],
                								$brand_item["Article"],
                								$brand_item["Designation"],
                								$exist,
                								$price + $price*$markup,
                								$time_to_exe + $storage_options["additional_time"],
                								$time_to_exe + $storage_options["additional_time"],
                								$Store["StoreName"],
                								$Store["Multiplicity"],
                								$Rating,
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
			        
			    } else {
			    
    				$Organization = (array)$Organization;
    				
    
    
    
    				$OrgName = $Organization['OrgName'];// название организации
    				$orgId = $Organization['OrgId'];// id организации для поиска
    				$orderType = $Organization['OrderType'];// 1 - основной заказ / 2 - дополнительный заказ
    				
    
    				//header("Content-type: text/xml"); //для вывода XML
                    $query = array(
    					'pattern' => $article,
    					'orgId' => $orgId,
    					'orderType' => $orderType,
    					'searchType' => '',
    					'point' => '',
    					'login' => $login,
    					'password' => $password,
    				);
    				$url = 'https://auto1.by/WebApi/Search?'.http_build_query($query);
    				
    				//$header = array("Accept: application/json", "User-Agent: Server");
    				//получить данные в JSON
    				$header = array("Accept: application/json", "User-Agent: Server");
    				
    				$ch = curl_init();
    				curl_setopt($ch, CURLOPT_URL, $url);
    				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    				$curl_result = curl_exec($ch);
    				curl_close($ch);
    				
    				
    				//ЛОГ [API-запрос] (вся информация о запросе)
    				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
    				{
    					$xml_result = json_decode($curl_result);
    					
    					$DocpartSuppliersAPI_Debug->log_api_request("Запрос остатков по артикулу ".$article." в организации ".$OrgName." (ID ".$orgId.")", "https://auto1.by/WebApi/Search?pattern=$article&orgId=$orgId&orderType=$orderType&login=$login&password=$password", htmlentities($curl_result), print_r($xml_result, true) );
    				}
    				
    				
    				$Search = json_decode($curl_result);
    				
    				foreach($Search as $item)
    				{
                        
                        $items = array();
                        
    					if(!is_array($item)) {
    					    $item = (array)$item;
    					    $items[] = $item;
    					} else {
    					    $items = $item;
    					}
    					
    					
    					foreach($items as $brand_item)
    				    {
    					    $Stores = $brand_item['Stores'];
    					    
                            if(is_array($Stores)) {
                                
                                foreach($Stores as $Store)
        						{
        						    
        							$Store = (array)$Store;
        							
            						$price = (float)$Store["Price"];
            						
    
        							$exist = $Store["Quantity"];
        							$exist = str_replace(array('<','>','=','+','-',' '),'',$exist);
        							
        							$Rating = (int)$Store["Rating"];
        							if($Rating < 40)
        							{
        								$Rating = 40;
        							}
        							
        							$time = $Store["DeliveryInfo"];
        							
        							$time_arrive = new DateTime($time);
        							
        							$time_now = new DateTime();
                                    $time_now->setTime( 0, 0 ); //Устанавливаем 00:00
        							
        							
        							$time_interval = $time_arrive->diff( $time_now ); //Разница между датой поступления и текущей датой.
    						        $time_to_exe = $time_interval->days; //Получаем дни (int)
    						        
    						        
        							//Наценка
        							$markup = $storage_options["markups"][(int)$price];
        							if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
        							{
        								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
        							}
        							
        							
        							//Создаем объек товара и добавляем его в список:
        							$DocpartProduct = new DocpartProduct($brand_item["Brand"],
        								$brand_item["Article"],
        								$brand_item["Designation"],
        								$exist,
        								$price + $price*$markup,
        								$time_to_exe + $storage_options["additional_time"],
        								$time_to_exe + $storage_options["additional_time"],
        								$Store["StoreName"],
        								$Store["Multiplicity"],
        								$Rating,
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
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
        $this->result = 1;
	}//~function __construct($article)
};//~class auto1_by_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new auto1_by_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>             