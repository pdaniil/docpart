<?php 
/*****************************************
 * Сервис поиска предложений на сайте avtoto.ru
 * Редакция: 2017.05.31
*****************************************
*/

class avtoto_parts { 
	
	private $errors;
	private $params;
	private $start_result;
	private $soap_client = NULL;
	private $soap_version = 1;
	
	private $response_wait_first_periods = array(0.3, 0.3, 0.3, 0.3, 0.3); //seconds
	private $response_wait_period = 0.5; //seconds
	
	private $search_extension_time_limit = 10; //seconds
	
	
	private $avtoto_server = 'http://www.avtoto.ru';
	private $wsdl_file = '/services/search/soap.wsdl';
	
	private $progress_list = array(	
		'2'=>  'Ожидает оплаты',
		'1'=>  'Ожидает обработки',		    
		'3'=>  'Заказано',		 
		'4'=>  'Закуплено',		 
		'5'=>  'В пути',		 		
		'6'=>  'На складе',	    		   
		'7'=>  'Выдано',
		'8'=>  'Нет в наличии'	
	);
	
	
	//------------------------------------------------------------------------
	public function __construct($params = array(), $soap_version = NULL) { 		
		$this->errors = array();	
		$this->search_params = array();		
		$this->start_result = array();
		
		if($params) { 
			$this->set_params($params);
		}
		
		$this->set_soap_version($soap_version); 
		$this->create_soap_client();
	}
	
	
	//------------------------------------------------------------------------
	public function set_params($params) { 
		if(
			isset($params['user_id']) && (int)$params['user_id'] &&
			isset($params['user_login']) && $params['user_login'] &&
			isset($params['user_password']) && $params['user_password']
		) {
			$this->params = $params;	
		} else {
			$this->errors[] = $this->error('wrong params');	
			
		}
	}
	
	
	//------------------------------------------------------------------------
	public function set_soap_version($soap_version = NULL) { 
		if($soap_version == '2') { 
			$this->soap_version = 2;
			$this->wsdl_file = str_replace('soap.','soap2.',$this->wsdl_file);
		} else { 
			$this->soap_version = 1;		
		}
	}
	
	
	//------------------------------------------------------------------------
	public function set_search_extension_time_limit($time_secods) { 
		if((int)$time_secods) { 		
			$this->search_extension_time_limit = (int)$time_secods;
		}
	}
	
	//------------------------------------------------------------------------
	public function get_parts($code, $analogs = 'on', $limit = 0) {
		$this->reset_errors();
		
		if(trim($code)) {
			if($this->soap_client) {
				if($this->params) {

                    $params = $this->params;
                    $params['search_code'] = trim($code);
                    $params['search_cross'] = $analogs == 'on' || $analogs == 1 ? $analogs : 'off';

                    $result_for_listener = $this->soap_client->SearchStart($params);

                    if ($result_for_listener) {

                        if ((int)$limit) {
                            $result_for_listener['Limit'] = (int)$limit;
                        }

                        return $this->get_results_from_listener($result_for_listener);
                    }
                }
			} else { 
				$this->errors[] = $this->error('cannot create client');			
			}
		} else { 
			$this->errors[] = $this->error('error code');			
		}
	}
	
	//------------------------------------------------------------------------
	public function get_parts_brand($code, $brand, $limit = 0, $analogs = 'off') 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Работаем в методе get_parts_brand() в avtoto/classes/avtoto_parts.php");
		
		$this->reset_errors();
		
		if(trim($code)) 
		{
			if($this->soap_client) 
			{
				if($this->params) 
				{

                    $params = $this->params;
                    $params['search_code'] = trim($code);
                    $params['brand'] = trim($brand);
                    $params['search_cross'] = ($analogs == 'on' || (int)$analogs == 1 ? 'on' : 'off');

                    $result_for_listener = $this->soap_client->SearchStart($params);
					
					
					//ЛОГ [API-запрос] (вся информация о запросе)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков по артикулу ".$params['search_code']." и производителю ".$params['brand']." в avtoto/classes/avtoto_parts.php", "SOAP-вызов SearchStart()<br>Параметры вызова: ".print_r($params, true), "См. обработанный ответ - должен быть указан ID поиска, который затем передаем в avtoto_parts::get_results_from_listener() и там уже получаем результат", print_r($result_for_listener, true) );
					}
					
					
                    if ($result_for_listener) 
					{

                        if ((int)$limit) 
						{
                            $result_for_listener['Limit'] = (int)$limit;
                        }

                        return $this->get_results_from_listener($result_for_listener);
                    }
                }
				else
				{
					//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('wrong params') );
					}
					
					$this->errors[] = $this->error('wrong params');
				}
			} 
			else 
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('cannot create client') );
				}
				
				$this->errors[] = $this->error('cannot create client');			
			}
		} 
		else 
		{ 
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('error code') );
			}
			
			$this->errors[] = $this->error('error code');			
		}
	}
	
	//------------------------------------------------------------------------
	public function get_brands_by_code($code) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Работаем в методе get_brands_by_code() в avtoto/classes/avtoto_parts.php");
		
		$this->reset_errors();

		if (trim($code)) 
		{
			if ($this->soap_client) 
			{
				if ($this->params) 
				{
					$params = $this->params;
					$params['search_code'] = trim($code);
					
					$brands = $this->soap_client->GetBrandsByCode($params);
					
					//ЛОГ [API-запрос] (вся информация о запросе)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_api_request("Получение списка брендов по артикулу ".$params['search_code']." в avtoto/classes/avtoto_parts.php", "SOAP-вызов GetBrandsByCode()<br>Параметры вызова ".print_r($params, true), "См. обработанный ответ", print_r($brands, true) );
					}
					
					return $brands;
				}
				else
				{
					//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('wrong params') );
					}
					
					$this->errors[] = $this->error('wrong params');
				}
			} 
			else 
			{
				//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
				if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
				{
					$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('cannot create client') );
				}
				
				$this->errors[] = $this->error('cannot create client');
			}
		} 
		else 
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка в avtoto/classes/avtoto_parts.php", $this->error('error code') );
			}
			
			$this->errors[] = $this->error('error code');
		}
	}

	//------------------------------------------------------------------------
	public function add_to_basket($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$add_params['user'] = $this->params;
					$add_params['parts'] = $parts;					
					return $this->soap_client->AddToBasket($add_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	
	//------------------------------------------------------------------------
	public function delete_from_basket($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$delete_params['user'] = $this->params;
					$delete_params['parts'] = $parts;					
					
					return $this->soap_client->DeleteFromBasket($delete_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	//------------------------------------------------------------------------
	public function update_count_in_basket($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$update_params['user'] = $this->params;
					$update_params['parts'] = $parts;					
					
					return $this->soap_client->UpdateCountInBasket($update_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
    
    //------------------------------------------------------------------------
	public function check_availability_in_basket($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$check_params['user'] = $this->params;
					$check_params['parts'] = $parts;					
					
					return $this->soap_client->CheckAvailabilityInBasket($check_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	//------------------------------------------------------------------------
	public function add_to_orders_from_basket($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$add_params['user'] = $this->params;
					$add_params['parts'] = $parts;					
					
					return $this->soap_client->AddToOrdersFromBasket($add_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	
	//------------------------------------------------------------------------
	public function get_orders_status($parts) {
		$this->reset_errors();
		if($this->soap_client) {
			if($parts) {
				
				if($this->check_parts($parts, __FUNCTION__)) { 
				
					$get_params['user'] = $this->params;
					$get_params['parts'] = $parts;					
					
					return $this->soap_client->GetOrdersStatus($get_params);					
				}				
			}
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	
	//------------------------------------------------------------------------
	public function get_stat_search() {
		$this->reset_errors();
		if($this->soap_client) {		
			return $this->soap_client->GetStatSearch($this->params);
		} else { 
			$this->errors[] = $this->error('cannot create client');		
		}
	}
	
	
	//------------------------------------------------------------------------
	public function get_progress_text($status_int) {
		return isset($this->progress_list[$status_int]) ? $this->progress_list[$status_int] : '';	
	}
	
	//------------------------------------------------------------------------	
	public function get_errors() { 	
		return $this->errors;	
	}
	
	
	
	
	
	
	//************************************************************************
	//************************************************************************
	//------------------------------------------------------------------------
	private function create_soap_client() { 
		
		$wsdl = $this->avtoto_server . $this->wsdl_file;
		
		$this->soap_client = new SoapClient($wsdl."?".rand(), 
			array(
				'soap_version' => constant('SOAP_1_'.$this->soap_version),
                'trace'         => 1,
                'encoding'      =>'UTF-8',
                'compression'   => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
                'exceptions'    => true,
                'cache_wsdl'    => WSDL_CACHE_NONE,
			)
		);	
	}
	
	//------------------------------------------------------------------------
	private function error($key) {		
		switch($key) { 		
			case 'cannot create client': return 'Не получилось соединиться с сервером';			
			case 'no result':            return 'Сервер не ответил';
			case 'wrong params':         return 'Неверные параметры соединения';
			case 'wrong parts':          return 'Ошибка данных';
			case 'error code':           return 'Неверный артикул';			
		}
	}
	
	private function reset_errors() { 
		$this->errors = array();	
	}
	
	//------------------------------------------------------------------------
	private function get_results_from_listener($result_for_listener) 
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Работаем в методе get_results_from_listener");
		
		$start_time = microtime(1);
		$result['Info']['SearchStatus'] = 2; //В обработке
		
		$sleep_count = 0;
		
		while( microtime(1) - $start_time < $this->search_extension_time_limit && isset($result['Info']['SearchStatus']) && $result['Info']['SearchStatus'] == 2 )	
		{ 
			
			$sleep_ms = 1000000 * (float)$this->response_wait_period;			
			if(isset($this->response_wait_first_periods[$sleep_count])) 
			{ 
				$sleep_ms = 1000000 * (float)$this->response_wait_first_periods[$sleep_count];
			} 
			
			usleep( $sleep_ms );

			$sleep_count ++;		
			$result = $this->soap_client->SearchGetParts2($result_for_listener);
            
		}
		if( $result === array() ) 
		{ 
			$this->errors[] = $this->error('no result');		
		}
		
		//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
		$DocpartSuppliersAPI_Debug->log_simple_message("Результат get_results_from_listener: ".print_r($result, true) );
		
		return $result;		
	}
	
	
	//------------------------------------------------------------------------
	private function check_parts($parts, $mode) { 
		
		if(is_array($parts)) {
			if(!isset($parts[0])) { 
				$real_parts[0] = $parts;		
			} else { 
				$real_parts = $parts;		
			}
			
			unset($parts);
			$errors = array();
			
			switch($mode) { 
				
				case 'add_to_basket': {
				
					foreach($real_parts as $i => $part) { 
						
						if(
							isset($part['PartId']) && is_numeric((int)$part['PartId']) &&
							isset($part['SearchID']) && (int)$part['SearchID'] &&
							isset($part['RemoteID']) && $part['RemoteID'] &&
							isset($part['Count']) && (int)$part['Count']
						) { 
							//void			
						} else { 
							$errors[] = $i;
						}
					}
				} break;
				
				case 'delete_from_basket': 
                case 'update_count_in_basket':  
                case 'add_to_orders_from_basket': 
                case 'check_availability_in_basket':
                case 'get_orders_status': { 
                
				
					foreach($real_parts as $i => $part) { 
						
						if(
							isset($part['InnerID']) && (int)$part['InnerID'] &&							
							isset($part['RemoteID']) && $part['RemoteID'] 
						) { 
							//void			
						} else { 
							$errors[] = $i;
						}
					}
				
				} break;
				
				
			}
				
			if($errors) { 
				$this->errors[] = $this->error('wrong parts').': '.implode(', ',$errors);			
			} else { 
				return true;			
			}			
		} else { 
			$this->errors[] = $this->error('wrong parts');
		}	
	}

}

?>