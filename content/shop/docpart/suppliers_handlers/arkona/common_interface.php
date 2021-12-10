<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class arkona_enclosure
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
        $login = $storage_options["user"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		//-------------------------------------------------------------------------------------------------------
		try {
			$IP='http://188.235.19.133:8096/ws_online5/services/easkis5_ws_online5';
			$client_main = new SoapClient(null, array('location' => $IP, 'uri' => "http://easkis5_ws_online5/"));

			$nameMethod 	= 'rc_findtovout';
			$login_name 	= $login; // логин для авторизации на сайте eOrder
			$login_password = md5($password); // пароль для авторизации на сайте eOrder
			$oem 			= $article;
			$cross 			= 1;

$xml_input = 
<<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no"?><findparm><row><userlogin>$login_name</userlogin><userpass>$login_password</userpass><number>$oem</number><fcross>$cross</fcross></row></findparm>
XML;

		
			$xml_output=$client_main->$nameMethod($xml_input);
			
			
			//ЛОГ [API-запрос] (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$xml_result = simplexml_load_string($xml_output);
				
				$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $IP."<br>Метод: ".$nameMethod."<br>Параметры: ".htmlentities($xml_input), htmlentities($xml_output), print_r($xml_result, true) );
			}
			
			
			$objXML = new SimpleXMLElement($xml_output);
			

			$result = (array) $objXML->row->typemessage;
			if($result === 'error')
			{
				return;
			}
			
			$products = (array)$objXML;
			$products = $products['row'];
			
			if(is_object($products)){
				$products = array($products);
			}
			
			for($p=0; $p < count($products); $p++)
			{
				$products[$p] = (array)$products[$p];
				
				$price = (float)$products[$p]["price"];

			
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Срок доставки
				$time_to_exe = $products[$p]["daydeliverysupplier"];


				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($products[$p]["nbrand"],
					$products[$p]["idtovoem"],
					$products[$p]["ntov"],
					$products[$p]["kolrest"],
					$price + $price*$markup,
					$time_to_exe + $storage_options["additional_time"],
					$time_to_exe + $storage_options["additional_time"],
					NULL,
					$products[$p]["minpart"],
					$storage_options["probability"],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',
					json_encode(
						array(
							"idtovoemshort"=> $products[$p]["idtovoemshort"],
							"idtov"        => $products[$p]["idtov"],
							"price"        => $products[$p]["price"],
							"daydeliverysupplier"   =>  $products[$p]["daydeliverysupplier"]
							)
					),
					array("rate"=>$storage_options["rate"])
					);
				
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
			}
		}
		catch (SoapFault $fault)
		{ 
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($fault, true) , $fault->getMessage() );
			}
		}
		catch (Exception $e) 
		{ 
			//ЛОГ - [ИСКЛЮЧЕНИЕ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_exception("Исключение", print_r($e, true) , $e->getMessage() );
			}
		}
		
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}

        $this->result = 1;
	}//~function __construct($article)
};//~class arkona_enclosure



//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"SOAP") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new arkona_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>