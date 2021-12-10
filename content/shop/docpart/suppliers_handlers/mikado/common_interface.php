<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
header('Content-Type: text/html; charset=utf-8');

ini_set("memory_limit", "512M");

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class mikado_enclosure 
{
	
	public $result;
	public $Products = array();//Список товаров
	private $storage_options = array();

	public function __construct( $article, $storage_options ) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->storage_options  = $storage_options;
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_simple_message("Внимание! Для API поставщика действует ограничение доступа по IP-адресу. Убедитесь, что IP-адрес Вашего сайта добавлен в список допущенных в личном кабинете на сайте поставщика.");
		}
		
		$this->result = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["user"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
        
		$items_order = $storage_options["items_order"]; //Отображать "под заказ"
		$spb_time = $storage_options["spb_time"]; 
		$vrn_time = $storage_options["vrn_time"];
		$kd_time = $storage_options["kd_time"];
		$unknow_time = $storage_options["unknow_time"];
		
        $ch = curl_init();
		$url_request = "http://www.mikado-parts.ru/ws1/service.asmx/Code_Search?Search_Code={$article}&ClientID={$login}&Password={$password}&FromStockOnly={$items_order}";
		
        curl_setopt( $ch, CURLOPT_URL, $url_request );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        $execute = curl_exec( $ch );
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$xml = simplexml_load_string( $execute );
			
			$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$article, $url_request, htmlentities($execute), print_r($xml, true) );
		}
		
		if(curl_errno($ch))
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("CURL-ошибка", print_r(curl_error($ch), true) );
			}
		}
		
        curl_close( $ch );
        
        $xml = simplexml_load_string( $execute );
        $rows = $xml->List;
		
        // Формирование массива товаров по запрошенному артикулу
        foreach ( $rows->Code_List_Row as $row ) {
				
			$srock = $row->Srock->__toString();
			
			// ob_start();
            // echo "<pre>";
            // echo date('Y-m-d H:i:s');
            // echo "</pre>";
            // echo "</br>";
            // echo "<pre>";
            // print_r($row);
            // echo "</pre>";
            // echo "</br>";
            // $page = ob_get_contents();
            // ob_end_clean();
            
            // $fw = fopen("result.txt", "a");
            // fwrite($fw, $page."\n\n");
            // fclose($fw);
			
			//Оригиналы запрашиваем отдельно
			if ( $row->CodeType->__toString() == 'OEM' || $row->CodeType->__toString() == 'AnalogOEM' ) {
				
				$one_service = "http://www.mikado-parts.ru/";
				
				$one_action = "ws1/service.asmx/Code_Info";
				
				$one_params_action = array (
					'ZakazCode' => $row->ZakazCode->__toString(),
					'ClientID' => $login,
					'Password' => $password,
					'FromStockOnly' => $items_order
				);
				
				$one_post_url_req = $one_service . $one_action . "?" . http_build_query( $one_params_action );
				
				$one_post_data = array (
					'url' => $one_post_url_req,
					'brand' => $row->ProducerBrand->__toString(), 
					'article' => $row->ProducerCode->__toString(), 
					'name' => str_replace( array("'", "\""), "", $row->Name->__toString() ), 
					'ZakazCode' => $row->ZakazCode->__toString()
				);
				
				$postdata[] = $one_post_data; //Мультизапрос
		
			}
			else
			{

				//Позиция из наличия
				if ($srock == "?") {
					
					continue;

				//Позиция в наличии
				}
				else
				{
					
					if(isset($row->OnStocks->StockLine)) {

						$this->createProduct( $row );

					}
					else
					{

						$one_service = "http://www.mikado-parts.ru/";
				
						$one_action = "ws1/service.asmx/Code_Info";
						
						$one_params_action = array (
							'ZakazCode' => $row->ZakazCode->__toString(),
							'ClientID' => $login,
							'Password' => $password,
							'FromStockOnly' => $items_order
						);
						
						$one_post_url_req = $one_service . $one_action . "?" . http_build_query( $one_params_action );
						
						$one_post_data = array (
							'url' => $one_post_url_req,
							'brand' => $row->ProducerBrand->__toString(), 
							'article' => $row->ProducerCode->__toString(), 
							'name' => str_replace( array("'", "\""), "", $row->Name->__toString() ), 
							'ZakazCode' => $row->ZakazCode->__toString()
						);
						
						$postdata[] = $one_post_data; //Мультизапрос

					}
					
				}
			}
			
		}//~foreach ( $rows as $row )

		//Выполняем дополнительные запросы
		if (!empty ( $postdata )) {
			
			$result = $this->multi_request($postdata);
			
			if (!empty($result)) {

			// ob_start();
            // echo "<pre>";
            // echo date('Y-m-d H:i:s');
            // echo "</pre>";
            // echo "</br>";
			// echo "<pre>";
            // print_r($postdata);
            // echo "</pre>";
            // echo "</br>";
			// echo "Результат";
            // echo "<pre>";
            // print_r($result);
            // echo "</pre>";
            // echo "</br>";
            // $page = ob_get_contents();
            // ob_end_clean();
            
            // $fw = fopen("result123.txt", "a");
            // fwrite($fw, $page."\n\n");
            // fclose($fw);
				
				
				foreach( $result as $item ) {
					
					// Logger::addLog( 'Позиция под заказ или от партнёров', $item );
		
					$brand		= $item['brand'];
					$article	= $item['article'];
					$name		= $item['name'];
					$ZakazCode = $item['ZakazCode'];
					
					$execute2 = $item['result'];
					
					$xml2 = simplexml_load_string( $execute2 );

					// ob_start();
					// echo "<pre>";
					// echo date('Y-m-d H:i:s');
					// echo "</pre>";
					// echo "</br>";
					// echo "<pre>";
					// print_r($xml2);
					// echo "</pre>";
					// $page = ob_get_contents();
					// ob_end_clean();
					
					// $fw = fopen("result123.txt", "a");
					// fwrite($fw, $page."\n\n");
					// fclose($fw);

					$rows2 = $xml2->Prices;
					
					foreach ( $rows2->Code_PriceInfo as $row2 ) {
						
						 if (isset($row2->OnStocks->StockLine) && !empty($row2->OnStocks->StockLine)) {						 						 

							foreach ($row2->OnStocks->StockLine as $stock) {
								
								$price = ( float ) $row2->PriceRUR->__toString();
							
								//Наценка
								$markup = $storage_options["markups"][(int)$price];
								//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
								if($markup == NULL) {
									$markup = $storage_options["markups"][count($storage_options["markups"])-1];
								}
								
								$price_for_customer = $price*$markup + $price;
								
								$time_to_exe		= $row2->Srock->__toString();
								$time_to_exe_g	    = $row2->SrockMax->__toString();
								$Rating 			= $row2->Rating->__toString();
								$Comment 			= $row2->Comment->__toString();
								
								if ( $time_to_exe > $time_to_exe_g ) {
									$time_to_exe_g = $time_to_exe;
								}
								
								$Comment 			= trim( str_replace( array( '"', "'" ), '', $Comment ) );
								
								$json_params = array ( 
									'ZakazCode' => $ZakazCode, 
									'StockID' => ( int ) $row2->StokID->__toString(), 
									'no_exist' => 1, 
									'comment' => $Comment,
									'DeliveryType' =>$row2->DeliveryType->__toString()
								);
								
								$exist = 4;
								

								//Создаем объект товара и добавляем его в список:
								$DocpartProduct = new DocpartProduct (
									$brand,
									$article,
									$name,
									$exist,
									$price_for_customer,
									$time_to_exe + $storage_options["additional_time"],
									$time_to_exe_g + $storage_options["additional_time"],
									'',
									1,
									$Rating,
									$storage_options["office_id"],
									$storage_options["storage_id"],
									$storage_options["office_caption"],
									$storage_options["color"],
									$storage_options["storage_caption"],// .' - '. $item['StokName'],
									$price,
									$markup,
									2,0,0,'',json_encode( $json_params ),array( "rate"=>$storage_options["rate"] )
								);
								
								if( $DocpartProduct->valid == true ) {
									array_push( $this->Products, $DocpartProduct );
								}
								
							}
							 
						 } //Есть на складе
						 else
						 { // Только заказ
							 
							$price = ( float ) $row2->PriceRUR->__toString();
							
							//Наценка
							$markup = $storage_options["markups"][(int)$price];
							//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
							if($markup == NULL) {
								$markup = $storage_options["markups"][count($storage_options["markups"])-1];
							}
							
							$price_for_customer = $price*$markup + $price;
							
							$time_to_exe		= $row2->Srock->__toString();
							$time_to_exe_g	    = $row2->SrockMax->__toString();
							$Rating 			= $row2->Rating->__toString();
							$Comment 			= $row2->Comment->__toString();
							
							if ( $time_to_exe > $time_to_exe_g ) {							
								$time_to_exe_g = $time_to_exe;
							}
							
							$Comment 			= trim( str_replace( array( '"', "'" ), '', $Comment ) );
							
							$json_params = array ( 
								'ZakazCode' => $ZakazCode, 
								'StockID' => '', 
								'no_exist' => 1, 
								'comment' => $Comment,
								'DeliveryType' =>$row2->DeliveryType->__toString()
							);
							
							$exist = 4;
							
							//Создаем объект товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct (
								$brand,
								$article,
								$name,
								$exist,
								$price_for_customer,
								$time_to_exe + $storage_options["additional_time"],
								$time_to_exe_g + $storage_options["additional_time"],
								'',
								1,
								$Rating,
								$storage_options["office_id"],
								$storage_options["storage_id"],
								$storage_options["office_caption"],
								$storage_options["color"],
								$storage_options["storage_caption"],// .' - '. $item['StokName'],
								$price,
								$markup,
								2,0,0,'',json_encode( $json_params ),array( "rate"=>$storage_options["rate"] )
							);

							if( $DocpartProduct->valid == true ) {
				
								array_push( $this->Products, $DocpartProduct );
								
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
	
	
	
	private function multi_request( $postdata ) {
		
		$curly = array();
		$result = array();
		$mh = curl_multi_init();

		foreach ( $postdata as $id => $postdata_item ) {
			
			$curly[$id] = curl_init();
			curl_setopt($curly[$id], CURLOPT_URL, $postdata_item['url']);
			curl_setopt($curly[$id], CURLOPT_HEADER, 0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_FOLLOWLOCATION, true);

			curl_multi_add_handle($mh, $curly[$id]);
			
		}

		$running = null;
		
		do {
			
			curl_multi_exec($mh, $running);
			
		} while ($running > 0);

		foreach ( $curly as $id => $c ) {

			$content = curl_multi_getcontent( $c );
			
			$item_data = array ( 
				'article' => $postdata[$id]['article'], 
				'brand' => $postdata[$id]['brand'], 
				'name' => $postdata[$id]['name'], 
				'ZakazCode' => $postdata[$id]['ZakazCode'], 
				'result'=>$content
			);
			
			$result[$id] = $item_data;
			
			curl_multi_remove_handle($mh, $c);

		}
		
		curl_multi_close($mh);
		
		return $result;
		
	}

	private function createProduct( $row ) {

		$stocks = $row->OnStocks->StockLine;

		$items_order = $this->storage_options["items_order"]; //Отображать "под заказ"
		$spb_time = $this->storage_options["spb_time"]; 
		$vrn_time = $this->storage_options["vrn_time"];
		$kd_time = $this->storage_options["kd_time"];
		$unknow_time = $this->storage_options["unknow_time"];

		$brand					= $row->ProducerBrand->__toString();
		$article				= $row->ProducerCode->__toString();
		$name					= str_replace( array("'", "\""), "", $row->Name->__toString() );
		$price 					= ( float ) $row->PriceRUR->__toString();
		$time_to_exe			= 0;
		$time_to_exe_guaranted	= 0;
		
		$storage_supp = ( int ) $row->StokID->__toString();
		$srock = $row->Srock->__toString();
			
		//Наценка
		$markup = $this->storage_options["markups"][(int)$price];
		//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		if($markup == NULL) {
			$markup = $this->storage_options["markups"][count($this->storage_options["markups"])-1];
		}
		
		$price_for_customer = $price*$markup + $price;
					
		foreach ( $stocks as $stock ) {
			//Обрабатываем количество товара
			$matches = array();
			$pattern = "/[\d]+/";
			preg_match( $pattern, $stock->StockQTY->__toString(), $matches );
			
			$exist = $matches[0];
			if ( $exist == 0 ) $exist = 10;
			
			//Время доставки с учётом склада
			switch( ( int ) $stock->StokID->__toString() ) {
				case 0:
					// Воронеж
					$time_to_exe = $vrn_time;
					$time_to_exe_guaranted = $vrn_time;
				break;
				case 1:
					// СПб
					$time_to_exe = $spb_time;
					$time_to_exe_guaranted = $spb_time;
				break;
				case 23:
					// Краснодар
					$time_to_exe = $kd_time;
					$time_to_exe_guaranted = $kd_time;
				break;
			}
			
			$DeliveryDelay = $stock->DeliveryDelay->__toString();
			
			if ( ! empty($DeliveryDelay)  ) {
				
				$time_to_exe = $DeliveryDelay;
				$time_to_exe_guaranted = $DeliveryDelay;
				
			}
			
			$json_params = array( "ZakazCode"=>$row->ZakazCode->__toString(), "StockID"=>( int ) $stock->StokID->__toString() );
			
			//Создаем объект товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($brand,
				$article,
				$name,
				$exist,
				$price_for_customer,
				$time_to_exe + $this->storage_options["additional_time"],
				$time_to_exe_guaranted + $this->storage_options["additional_time"],
				$storage_supp,
				1,
				$this->storage_options["probability"],
				$this->storage_options["office_id"],
				$this->storage_options["storage_id"],
				$this->storage_options["office_caption"],
				$this->storage_options["color"],
				$this->storage_options["storage_caption"],// .' - '. $item['StokName'],
				$price,
				$markup,
				2,0,0,'',json_encode( $json_params ),array( "rate"=>$this->storage_options["rate"] )
			);
			
			// var_dump( $DocpartProduct );
			
			if( $DocpartProduct->valid == true ) {
				
				array_push( $this->Products, $DocpartProduct );
				
			}

		}//~foreach ( $stocks as $stock )
	}
	
};//~class mikado_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-XML") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new mikado_enclosure($_POST["article"], $storage_options);
exit(json_encode($ob));
?>