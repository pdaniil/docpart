<?php

class ABCPSAO {
	
	public $login;
	public $password;
	public $subdomain;
	private $ch;
	
	public function __construct ( $login, $password, $subdomain ) {
		
		$this->login = $login;
		$this->password = $password;
		$this->subdomain = $subdomain;
		
		$this->initHandler();
		
	}
	
	//Возвращает список доступных способов оплаты. Идентификатор способа оплаты необходим при отправке заказа
	public function getPaymentMethods () {
		
		$params_payment_methods = http_build_query( array( 'userlogin' => $this->login, 
																'userpsw' => $this->password ));

		$url_payment_methods = "http://{$this->subdomain}.public.api.abcp.ru/basket/paymentMethods?{$params_payment_methods}";
		
		$ch = $this->ch;
		
		curl_setopt ( $ch, CURLOPT_URL, $url_payment_methods );
		
		$data = $this->execute( $ch );
		
		return $data;
		
	}
	
	//Получение списка способов доставки
	public function getShipmentMethods () {
		
		$params_shipment_methods = http_build_query( array( 'userlogin' => $this->login, 
																'userpsw' => $this->password ));

		$url_payment_shipment_methods = "http://{$this->subdomain}.public.api.abcp.ru/basket/shipmentMethods?{$params_shipment_methods}";
		
		$ch = $this->ch;
		
		curl_setopt ( $ch, CURLOPT_URL, $url_payment_shipment_methods );
		
		$data = $this->execute( $ch );
		
		return $data;
		
	} 
	
	//Получение списка офисов самовывоза
	public function getShipmentOffices () {
		
		$params_shipment_offices = http_build_query( array( 'userlogin' => $this->login, 
																'userpsw' => $this->password ));

		$url_shipment_offices = "http://{$this->subdomain}.public.api.abcp.ru/basket/shipmentOffices?{$params_shipment_offices}";
		
		$ch = $this->ch;
		
		curl_setopt ( $ch, CURLOPT_URL, $url_shipment_offices );
		
		$data = $this->execute( $ch );

		return $data;
		
	} 
	
	//Получение списка адресов доставки
	public function getShipmentAddresses () {
		
		$params_shipment_offices = http_build_query( array( 'userlogin' => $this->login, 
																'userpsw' => $this->password ));

		$url_shipment_offices = "http://{$this->subdomain}.public.api.abcp.ru/basket/shipmentAddresses?{$params_shipment_offices}";
	
		$ch = $this->ch;
		
		curl_setopt ( $ch, CURLOPT_URL, $url_shipment_offices );
		
		$data = $this->execute( $ch );
		
		return $data;
		
		
		
	} 
	
	//Получение списка дат отгрузки
	public function getShipmentDates () {
		
		$params_shipment_dates = http_build_query( array( 'userlogin' => $this->login, 
																'userpsw' => $this->password ));

		$url_shipment_dates = "http://{$this->subdomain}.public.api.abcp.ru/basket/shipmentDates?{$params_shipment_dates}";
	
		$ch = $this->ch;
		
		curl_setopt ( $ch, CURLOPT_URL, $url_shipment_dates );
		
		$data = $this->execute( $ch );
		
		return $data;
		
	} 
	
	//Создание заказа
	public function createOrder ( ABCPOrder $abcp_order ) {
		
		$this->initHandler();
		$ch = $this->ch;
		
		$url_request = "http://{$this->subdomain}.public.api.abcp.ru/orders/instant";
		
		$order_data = $abcp_order->getOrderData();
		
		$positions = $order_data['positions'];
		
		//Удаляем массив позиций из объекта заказа, что бы они не формировались при build_query
		//Формируются они по особому ! далее
		unset( $order_data['positions'] );
		
		$postions_arr = array();
		
		$key_name = "positions";
		
		//Создаём массив позиций для POST
		foreach ( $positions as $i => $pos ) {
			
			$current_str = "";
			
			$number 		= $key_name . "[" . $i . "][number]";
			$brand 		= $key_name . "[" . $i . "][brand]";
			$itemKey 		= $key_name . "[" . $i . "][itemKey]";
			$supplierCode	= $key_name . "[" . $i . "][supplierCode]";
			$quantity 		= $key_name . "[" . $i . "][quantity]";
			
			$pos_data_arr = array ( $number => $pos['number'],
										$brand => $pos['brand'],
										$itemKey => $pos['itemKey'],
										$supplierCode => $pos['supplierCode'],
										$quantity => $pos['quantity']); 
			
			$postions_arr[] = $pos_data_arr;
			
		}

		$params_query = http_build_query($order_data);
		
		$positions_build = array();
		
		//Создаём строки с параметрами позиций для POST
		foreach ( $postions_arr as $pos_arr ) {
			
			$positions_build[] = http_build_query( $pos_arr );
			
		}
		
		//Склеиваем строки
		$positions_build_str = implode( "&", $positions_build );
		
		$params_query .= "&" . $positions_build_str;
		
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $params_query );
		curl_setopt ( $ch, CURLOPT_URL, $url_request );
		
		$data = $this->execute ( $ch ); //Создаём заказ
		
		return $data; 
		
	}
	
	//Инициализирует curl
	public function initHandler () {
		
		if ( isset ( $this->ch ) ) {
			
			curl_close( $this->ch );
			
		}
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		
		$this->ch = $ch;
		
	}
	
	//Выполняет crul-запрос
	private function execute ( $ch ) {
		
		$exec = curl_exec($ch);

		if ( curl_errno( $ch ) ) {
			
			throw new Exception ( "Ошибка curl: " . curl_errno( $ch ) );
			
		}

		$decode = json_decode ( $exec, true );
		//DEBUG
		/*
		
			var_dump($exec);
			var_dump($decode);

		// */

		if ( is_null( $decode ) ) {
			
			throw new Exception ( "Ошибка json: " . json_last_error() );
			
		}

		if ( isset ( $decode['errorCode'] ) ) {
			
			throw new Exception ( "Ошибка от поставщика: " . $decode['errorMessage'] );
			
		}
		
		return $decode;
		
	}
	
}

?>