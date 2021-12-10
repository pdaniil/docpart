<?php

class TochkiApi 
{
	
	private $options;
	private $handler;
	
	public function __construct() {
		
		$this->options = array();
		
	}
	//Установка параметров для API
	public function setOptions( $option, $value = null  ) {
		
		if ( is_null( $value ) ) {
			
			$this->options = $option;
			
		} else {
			
			$this->options[$option] = $value;
			
		}
		
	}
	
	//Инициализаця Клиента
	private function init() {
		
		if ( ! isset( $this->handler ) ) {
			
			$wsdl = $this->options['wsdl'];
			
			$context = stream_context_create( array('ssl' => 
													array('verify_peer' => false, 
														'verify_peer_name'  => false) ));

			$client = new SoapClient( $wsdl );
			
			/* 												
			$client = new SoapClient(	$wsdl, 
										array( 'soap_version' => SOAP_1_2, 
												'stream_context' => $context,
												'exception' => true,
												'trace' => true ) );
			*/
			
			$this->handler = $client;
			
		}
		
		return $this->handler;
		
	}
	
	//Получение товаров по артикулу их наличия на складах
	public function getSupplierItems( $article ) {
		
		$goods_list = array();
		
		$client = $this->init();
		
		$login = $this->options['login'];
		$password = $this->options['password'];
		
		$code_list = array();
		$code_list[] = $article; 
		
		$params =  array( 'login' => $login,
						  'password' => $password,
						  'code_list' => $code_list );
		
		$answer = $client->GetGoodsInfo( $params )->GetGoodsInfoResult;
		
		if ( isset( $answer->error ) ) {
			
			$this->supplierErrorHandler( $answer->error );
			
		}
		
		
		//Получаем найденные товары 
		$groups_items = get_object_vars( $answer );
		
		foreach ( $groups_items as $group_item ) {
			
			if ( ! empty( $group_item ) ) {
				
				$objects = get_object_vars( $group_item ); //Получили массив св-в объекта
				
				
				if ( ! empty( $objects ) ) 
				{
					$group_keys = array_keys( $objects );
					
					
					$group_key = $group_keys[0]; //Получили название группы
					
					if ( is_array( $objects[$group_key] ) ) {
						
						//Обрабатываем как массив
						foreach ( $objects[$group_key] as $item_data ) {
							
							$this->getPriceRequestData( $item_data, $goods_list );
							
						}
						
					} else if ( $objects[$group_key] ) {
						
						//Обрабатываем как один елемент
						$this->getPriceRequestData( $objects[$group_key], $goods_list );
						
					}
					
				}
				
			}
			
		} // ~foreach ( $groups_items as $group_item )
		
		
		//Получили массив товаров.
		// var_dump( $goods_list );
		
		return $goods_list;
		
	}
	//Получение цен найденных товаров
	private function getPriceRequestData( $item, &$goods_list ) {
		
		$storages = array(); //Остатки на складах
		
		$brand = $item->brand;
		$name = $item->name;
		$article = $item->code;
		
		//
		$goods_list[$article]['article'] = $article;
		$goods_list[$article]['brand'] = $brand;
		$goods_list[$article]['name'] = $name;
		
		$client = $this->init();
		
		$login = $this->options['login'];
		$password = $this->options['password'];
		
		$code_list = array();
		$code_list[] = $article; 
		
		$filter = array();
		$filter['code_list'] = $code_list;
		
		$params =  array( 'login' => $login,
						  'password' => $password,
						  'filter' => $filter );
		
		$answer = $client->GetGoodsPriceRestByCode( $params )->GetGoodsPriceRestByCodeResult;
		
		if ( isset( $answer->error ) ) {
			
			$this->supplierErrorHandler( $answer->error );
			
		}
		
		$whpr = $answer->price_rest_list->price_rest->whpr; //Склады, где есть позиция
		
		//Получаем склады в виде массива
		$whpr = get_object_vars( $whpr );
		
		$whpr_keys = array_keys( $whpr );
		$whpr_key = $whpr_keys[0];
		
		if ( is_array( $whpr[$whpr_key] ) ) {
			
			//Обрабатываем массив складов
			foreach ( $whpr[$whpr_key] as $storage_data ) {
				
				$storage = get_object_vars( $storage_data );
				
				if ( $storage['rest'] > 0 ) {
					
					$storages[] = $storage;
										
				}
				
				
			}
			
		} else if ( is_object( $whpr[$whpr_key] ) ) {
			//Обрабатываем как один склад
			$storage = get_object_vars( $whpr[$whpr_key] );
				
			if ( $storage['rest'] > 0 ) {
				
				$storages[] = $storage;
									
			}
			
		}
		
		//Заполняем данными о наличии.
		$goods_list[$article]['storages'] = $storages;
	
	}
	//Получение информации о скаладах поставщика
	public function getWarehouseInfo() {
		
		$info = array();
		
		$client = $this->init();
		
		$login = $this->options['login'];
		$password = $this->options['password'];
		
		$params =  array( 'login' => $login,
						  'password' => $password );
						  
		$answer = $client->GetWarehouses( $params )->GetWarehousesResult;
		
		if ( isset( $answer->error ) ) {
			
			$this->supplierErrorHandler( $answer->error );
			
		}
		
		$WarehouseInfo = $answer->warehouses->WarehouseInfo;
		
		if ( is_array( $WarehouseInfo ) ) {
			
			foreach( $WarehouseInfo as $Info ) {
				
				$inf_arr = get_object_vars( $Info );
				
				$info[$inf_arr['id']] = $inf_arr;
				
			}
			
		} else if ( is_object( $WarehouseInfo ) ) {
			
			$inf_arr = get_object_vars( $WarehouseInfo );
			$info[$inf_arr['id']] = $inf_arr;
			
		}
		
		return $info;
		
	}
	
	private function supplierErrorHandler( $error_data ) {
		
		$code = $error_data->code;
		$comment = $error_data->comment;
			
		$message = "Ответ поставщика: {$comment}, Код: {$code}";
		throw new Exception( $message );
		
	}
	
}

?>