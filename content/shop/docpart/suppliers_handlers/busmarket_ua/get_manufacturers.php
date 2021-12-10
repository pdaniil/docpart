<?php
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php" );


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class busmarket_ua_enclosure
{
	public $status = 0; 
	public $ProductsManufacturers = array();
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$flag = true;
		
		if($storage_options['is_admin']){
			if(empty($storage_options["storage_caption"])){
				$flag = false;
			}
		}
		
		if($flag){
			$DP_Config = new DP_Config;//Конфигурация CMS
			
			/*****Учетные данные*****/
			$article = urlencode($article);
			$key = $storage_options["key"];
			$user_agent = str_replace(array('http://','https://','/'),'',$DP_Config->domain_path);
			/*****Учетные данные*****/
			
			$headers = array("Authorization: $key", "User-Agent: $user_agent");
			
			// ПОЛУЧАЕМ СПИСОК БРЭНДОВ:
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://api.bm.parts/search/products/suggest?q=".$article);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);
			
			
			//ЛОГ API-запроса (вся информация о запросе)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$article,"https://api.bm.parts/search/products/suggest?q=".$article."<br>Заголовки:<br>".print_r($headers, true), $curl_result, print_r(json_decode($curl_result, true), true) );
			}
			
			
			$curl_result = json_decode($curl_result, true);
			
			$sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");

			if(!empty($curl_result['suggests'])){
				foreach($curl_result['suggests'] as $item){
					$article_2				= $item['article'];
					$article_2 = str_replace($sweep,"", $article_2);
					$article_2 = strtoupper($article_2);
					
					//echo $article_2 .'<br/>';
					
					if($article !== $article_2){
						continue;
					}
					
					$manufacturer 			= $item['brand'];
					$manufacturer_id 		= 0;
					$name					= $item['name'];
					$synonyms_single_query	= true;
					$params 				= "";
					
					$DocpartManufacturer = new DocpartManufacturer($manufacturer,
						$manufacturer_id,
						$name,
						$storage_options["office_id"],
						$storage_options["storage_id"],
						$synonyms_single_query,
						$params
					);
					
					array_push($this->ProductsManufacturers, $DocpartManufacturer);	
				}
			}
		}
		
		$this->status = 1;
		
		//ЛОГ результирующего объекта
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список брендов", print_r($this->ProductsManufacturers, true) );
		}
	}
}



$storage_options = json_decode( $_POST["storage_options"], true );
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-JSON") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();



$ob = new busmarket_ua_enclosure($_POST["article"], $storage_options);
exit( json_encode($ob) );
?>