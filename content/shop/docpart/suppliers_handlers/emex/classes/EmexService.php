<?php

class EmexService {
	
	private $client;
	private $request;
	
	public function __construct( $wsld, $params ) {
		
		$pattern = "/(EmEx.*)\./";
		$matches = array();
		
		// $pathDump = "{$_SERVER['DOCUMENT_ROOT']}/content/shop/docpart/suppliers_handlers/emex/logs";
		
		preg_match( $pattern, $wsld, $matches );
		
		// file_put_contents( "{$pathDump}/{$matches[1]}_service.xml", file_get_contents( $wsld ) );
		
		$this->client	= new SoapClient( $wsld,  $params );//Создаем SOAP-клиент
		
	}
	
	public function createOrder( array $addParams = null ) {
		
		$request = $this->request;
		
		if ( ! is_null( $addParams ) ) {
			
			$request = $this->addParamsRequest( $addParams );
			
		}
		
		$res = $this->client->Basket_ChangeStatus_ById( $request );//Кладём товар в корзину emex'a
		
		if ( $res->Basket_ChangeStatus_ByIdResult->BasketChangingResult->res == 'res_OK' ) {
			
			return $res->Basket_ChangeStatus_ByIdResult->BasketChangingResult->Id;
						
		} else {
			
			return false;
			
		}
		
	}
	
	public function addToBacket( array $addParams = null ) {
		
		$request = $this->request;
		
		if ( ! is_null( $addParams ) ) {
			
			$request = $this->addParamsRequest( $addParams );
			
		}
		
		$sao_object = array();
		
		try {
			
			$res = $this->client->InsertToBasket3( $request );//Кладём товар в корзину emex'a
			
			if ( isset ( $res->InsertToBasket3Result->BasketReturnData ) &&
				 $res->InsertToBasket3Result->BasketReturnData->Comment == ''
			) {
				
				$sao_object['GlobalId'] = $res->InsertToBasket3Result->BasketReturnData->GlobalId;
				$sao_object['Num'] = $res->InsertToBasket3Result->BasketReturnData->Num;
				
			} else {

				$sao_object['error'] = $res->InsertToBasket3Result->BasketReturnData->Comment;
				
			}
			
		} catch ( SoapFault $e ) {
			
			$sao_object['error'] = $e->getMessage();
			
		}
		
		return $sao_object;
		
	}
	
	public function deleteOutOfBasket ( array $addParams = null  ) { 
		
		
		$request = $this->request;
		
		if ( ! is_null( $addParams ) ) {
			
			$request = $this->addParamsRequest( $addParams );
			
		}
		
		$res = $this->client->Basket_ChangeStatus_ById( $request );//Кладём товар в корзину emex'a
		
		if ( $res->Basket_ChangeStatus_ByIdResult->BasketChangingResult->res == 'res_OK' ) {
			
			return true;
						
		} else {
			
			return false;
			
		}
		

	}
	
	public function GetConsumerInmotion3 ( array $addParams = null ) {
		
		$request = $this->request;
		
		if ( ! is_null( $addParams ) ) {
			
			$request = $this->addParamsRequest( $addParams );
			
		}
		
		$res = $this->client->GetConsumerInmotion3( $request );//Получаем инфу движении заказа
		

		if ( isset ( $res->GetConsumerInmotion3Result->InmConsumer_v3 ) ) {
			
			return new Motion( $res->GetConsumerInmotion3Result->InmConsumer_v3 );
			
		} else {

			return false;
			
		}
		
	}
	
	public function GetBasket ( array $addParams = null ) {
		
		$request = $this->request;
		
		if ( ! is_null( $addParams ) ) {
			
			$request = $this->addParamsRequest( $addParams );
			
		}
		
		$res	= $this->client->GetBasket( $request );//Получаем инфу о корзине
		
		if ( isset ( $res->GetBasketResult->BasketData ) ) {
			
			
			return new BasketData( $res->GetBasketResult->BasketData );
			
		} else {

			return false;
			
		}
		
	}
	
	private function addParamsRequest( array $addParams ) {
		
		$request = $this->request;
		
		foreach ( $addParams as $k => $v ) {
			
			$request[$k] = $v;
			
		}
		
		return $request;
		
	}
	
	public function getRequest ( array $request = null ) {
		
		if ( ! is_null( $request ) ) {
			
			$this->request = $request;
			
		} else {
			
			return $this->request;
			
		}
		
	}
	
	
	
}

?>