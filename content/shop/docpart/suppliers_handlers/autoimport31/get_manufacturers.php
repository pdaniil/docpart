<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class autoimport31_enclosure
{
	public $status;
	
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->status = 0;//По умолчанию
		
		$time_now = time();//Время сейчас
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		// -------------------------------------------------------------------------------------------------
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
		
		
		//Запускаем SOAP-процедуру (Получение брендов)
		try
		{
			$param = array(
				'user' => $login,
				'password' => $password,
				'search_string' => $article
			);
			$soap_am_result = $objClient->__soapCall('get_brands', $param);
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article, "SOAP-вызов get_brands с параметрами ".print_r($param, true), htmlentities(print_r($soap_am_result, true)), "Преобразование результата не требуется" );
			}
		}
		catch (SoapFault $e)//Не можем создать клиент SOAP 
		{
            //ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение при выполнении SOAP-метода get_brands", htmlentities( print_r($e, true) ), $e->getMessage()."<br>Ошибка может возникать, если не указаны логин и пароль, либо, если логин и пароль указаны не правильно. Проверьте<br>Логин: '".$login."'<br>Пароль: '".$password."'");
			}
			return;
		}
		

		if(is_array($soap_am_result))
		{
			foreach($soap_am_result as $std)
			{
				$item = (array) $std;
				
				$DocpartManufacturer = new DocpartManufacturer(
					$item["brand"],
					$item["brand_code"],
					$item["title"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					true//Посылать только один запрос для одного синонима
				);

				array_push($this->ProductsManufacturers, $DocpartManufacturer);
			}
		}
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - БРЭНДЫ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}

        $this->status = 1;
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