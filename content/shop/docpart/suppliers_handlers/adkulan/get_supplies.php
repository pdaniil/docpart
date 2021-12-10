<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class adkulan_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	
	
	
	//функция возврата данных по get-запросу (по URL)
	public function get_data_in_url($url)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//инифиализация CURL
		$ch = curl_init();

		//параметры curl
		$options = array(
		   CURLOPT_URL => $url,
		   CURLOPT_RETURNTRANSFER => true,
		   CURLOPT_HEADER => false,
		   CURLOPT_FOLLOWLOCATION => 1,

		   CURLOPT_SSL_VERIFYHOST => false,
		   CURLOPT_SSL_VERIFYPEER => false,
		   CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1,
		);

		curl_setopt_array($ch, $options);

		//запрос данных
		$res = curl_exec($ch);
		curl_close($ch);
		
		//ЛОГ [ПОСЛЕ API-запроса] (название запроса, ответ, обработанный ответ)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_after_api_request("Получение остатков", $res, print_r( json_decode($res, true), true ) );
		}
		
		//возврат результата в виде массива
		return json_decode($res, true);
	}
	
	
	
	public function __construct($article, $manufacturers, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
	    $this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $client_id = $storage_options["client_id"];
		/*****Учетные данные*****/
		
		
		
		$debug = 0;// Вывод ошибок
		
		
		$base_url = 'https://adkulan.kz/apiv2?';
		
		
		for($m=0; $m < count($manufacturers); $m++)
        {
			$brend = $manufacturers[$m]["manufacturer"];
			
			//формирование запроса остатков с учетом брендов
			$http_qr = array(
			   'client' => $client_id,
			   'mod' => 'rests',// Метод запроса товаров
			   'article' => $article,
			   'brand' => $brend
			);
			$url = $base_url . http_build_query($http_qr);
			
			//ЛОГ [ПЕРЕД API-запросом] (название запроса, запрос)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_before_api_request("Получение остатков по артикулу ".$article." и производителю ".$brend, $base_url."<br>Параметры: ".print_r($http_qr, true) );
			}
			
			
			//получение данных
			$data = $this->get_data_in_url($url);
			
			if($data['answer_code'] == 200)
			{
				// Цикл по найденным товарам
				if(is_array($data['rests']['items'])){
					foreach($data['rests']['items'] as $product){
						
						$this->add_to_object_arrays($product, $storage_options);
						
						// Аналоги
						if(is_array($product['replacements']) && !empty($product['replacements'])){
							foreach($product['replacements'] as $analog){
								$this->add_to_object_arrays($analog, $storage_options);
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
	
	
	
	public function add_to_object_arrays($product, $storage_options)
	{
		$sweep = array(" ","-","/",">","<");
		
		if(is_array($product['stocks'])){
			foreach($product['stocks'] as $stock){
				
				//Обработка количества (если есть лишние знаки)
				$quantity = str_replace($sweep, "", $stock['quantity']);
				$quantity = (int)abs($quantity);
				
				$price = (float)$stock['price'];
				
				//Наценка
				$markup = $storage_options["markups"][(int)$price];
				if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
				{
					$markup = $storage_options["markups"][count($storage_options["markups"])-1];
				}
				
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($stock['brand'],
					$stock['article'],
					$stock['name'],
					$quantity,
					$price + $price*$markup,
					abs((int)trim($stock['delivery_max'])) + $storage_options["additional_time"],
					abs((int)trim($stock['delivery_max'])) + $storage_options["additional_time"],
					NULL,
					$stock['moq'],
					$stock['quality'],
					$storage_options["office_id"],
					$storage_options["storage_id"],
					$storage_options["office_caption"],
					$storage_options["color"],
					$storage_options["storage_caption"],
					$price,
					$markup,
					2,0,0,'',null,array("rate"=>$this->storage_options["rate"])
					);
					
				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}
		}
	}
};//~class adkulan_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );


$ob = new adkulan_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));
?>