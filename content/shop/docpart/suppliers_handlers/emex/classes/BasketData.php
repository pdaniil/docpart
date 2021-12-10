<?php
/**
	Объект корзины emex
	Из него можно получить различную информацию о позиции.

*/
class BasketData {
	
	public $data;
	
	public function __construct( $object ) {
		
		$this->data = $object;
		
	}
	
	/**
		
		* Получаем параметры для одновления статуса
		
		@return array
	
	*/
	
	public function getState() {
		
		// var_dump( $this );
		
		$status 	= array();
		
		$state		= $this->data->BasketPart;
		$state_id	= $this->data->StatusCode;
		$comment	= $this->data->StatusComments;
		
		//Кодировка может быть win-1251
		$encoding = mb_detect_encoding( $comment, "ASCII, WINDOWS-1251", true );
		
		if ( $encoding != 'UTF-8' ) {
			
			$comment = mb_convert_encoding( $comment, "UTF-8" );
			
		}
		
		$status['state_id']	= $state_id;
		$status['state']		= $state;
		$status['comment']		= $comment;
		
		return $status;
		
	}
	
	//Получение текущей цены в рублях
	public function getPricePotrOrderRUR() {
		
		return $this->data->PricePotrOrderRUR;
		
	}
	
	//Получение разницы цен
	public function getPricePotrDiffRUR() {
		
		return $this->data->PricePotrDiffRUR;
		
	}
	
	//Кол-во в наличии
	public function getDetailQuantity() {
		
		return $this->data->DetailQuantity;
		
	}
	
	//Время доставки
	public function getDeliverTimeAverage () {
		
		return $this->data->DeliverTimeAverage;
		
	}
	
	//Гарантированное время доставки
	public function getDeliverTimeGuaranteed() {
		
		return $this->data->DeliverTimeGuaranteed;
		
	}
}

?>