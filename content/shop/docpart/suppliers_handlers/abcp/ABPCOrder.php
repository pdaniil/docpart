<?php

class ABCPOrder {
	
	public $order = array();
	
	public function __construct( ABCPSAO $abcp_sao ) {
		
		echo "payments: \n";
		$payments	= $abcp_sao->getPaymentMethods();
		
		echo "\n";
		
		echo "shipments: \n";
		$shipments	= $abcp_sao->getShipmentMethods();
		
		echo "\n";
		
		echo "offices: \n";
		$offices	= $abcp_sao->getShipmentOffices();
		
		echo "addresses: \n";
		$addresses	= $abcp_sao->getShipmentAddresses();
		
		echo "\n";
		
		echo "dates: \n";
		$dates		= $abcp_sao->getShipmentDates();
		echo print_r($dates, true)."\n";
		echo "\n";
		 
		 
		$payment_id 	= $payments[0]['id'];
		$shipment_id 	= $shipments[0]['id'];
		$office_id 	= $offices[0]['id'];
		$address_id	= $addresses[0]['id'];
		$date_id 		= $dates[0]['date'];
		
		$this->order = array ( 'userlogin' => $abcp_sao->login,
								'userpsw' => $abcp_sao->password,
								'positions' => array(),
								'paymentMethod' => $payment_id,
								'shipmentMethod' => $shipment_id,
								'shipmentOffice' => $office_id,
								'shipmentAddress' => $address_id,
								'shipmentDate' => $date_id,
								'comment' => '');
								
		
		
	}
	
	public function addPosition ( array $position ) {
		
		if ( ! empty ( $this->order ) ) {
			
			$this->order['positions'][] = $position;
			
		}
		
	}
	
	public function getOrderData () {
		
		return $this->order;
		
	}
	
}

?>