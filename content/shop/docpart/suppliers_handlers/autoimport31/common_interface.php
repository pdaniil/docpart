<?php
/*

Документация: http://autoimport31.ru/ws/ 

*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");


class autoimport31_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		//-------------------------------------------------------------------------------------------------------
		
		//Создание объекта клиента
		try
		{
			$objClient = new SoapClient('http://www.autoimport31.ru/ws/service.php?wsdl', 
                    array(
                        'soap_version'	=> SOAP_1_1,
                        'compression' 	=> SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                        'encoding'      => 'UTF-8',
                        'timeout' 		=> 10
                    )
            );//Создаем SOAP-клиент
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при создании SOAP-клиента", htmlentities( print_r($e, true) ), $e->getMessage() );
			}
			return;
		}
		
		
		
		//Запускаем SOAP-процедуру (Получение запчастей)
		try
		{
			$param = array(
				'user' => $login,
				'password' => $password,
				'search_string' => $article
			);
			$soap_am_result = $objClient->__soapCall('search', $param);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, "SOAP-вызов search с параметрами ".print_r($param, true), htmlentities(print_r($soap_am_result, true)), "Преобразование результата не требуется" );
			}
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-метода search", htmlentities( print_r($e, true) ), $e->getMessage()."<br>Ошибка может возникать, если не указаны логин и пароль, либо, если логин и пароль указаны не правильно. Проверьте<br>Логин: '".$login."'<br>Пароль: '".$password."'");
			}
			return;
		}
		
		if(!is_array($soap_am_result))
		{
			if(!empty($soap_am_result->partcode))
			{
				$soap_am_result = array($soap_am_result);
			}
		}
		
		if(is_array($soap_am_result))
		{
			foreach($soap_am_result as $std)
			{
				$product = (array) $std;
				
				//Срок доставки
				$time_to_exe = (int)$product["delivery_min"];
				$time_to_exe_garant = (int)$product["delivery_max"];
				
				if($time_to_exe > 20)
				{
					//continue;
				}
				
				$price = (float)$product["price"];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($product["brand"],
					$product["partcode"],
					$product["partname"],
					$product["avail"],
					$price + $price*$markup,
					$time_to_exe + $storage_options["additional_time"],
					$time_to_exe_garant + $storage_options["additional_time"],
					$product["supplier"],
					$product["quantity"],
					$product["delivery_percent"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,''
					);
				
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
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
};//~class autoimport31_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new autoimport31_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>