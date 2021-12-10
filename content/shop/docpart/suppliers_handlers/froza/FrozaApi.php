<?php
class FrozaApi
{
	
	private $service,
			$action;
			
	private $options = array();
	private	$params_action = array();
	
	
	public function __construct( array $options ) {
		
		$this->options = $options;
		
	}
	
	public function getAction( $action = null ) {
		
		if ( is_null( $action ) ) {
			
			return $this->action;
			
		} else {
			
			$this->action = $action;
			
		}
		
	}
	
	public function getParamsAction ( $params = null, $v = null ) {
		
		if ( is_null( $params ) ) {
			
			return $this->params_action;
			
		} else if ( is_array( $params ) &&
					is_null( $v )
		) {
			
			$this->params_action = $params;
			
		} else {
			
			$this->params_action[$params] = $v;
			
		}
		
	}
	
	public function searchOrderItemByOrderId( array $items, $global_id ) {
		
		foreach ( $items as $item ) {
			
			if ( $item->global_id == $global_id ) {
				
				return $item;
				
			}
			
		}
		
		return false;
		
	}
	
	public function execAction() {
		
		try {
			
			if ( is_null( $this->service ) ) {
				
				$wsdl = $this->options['wsdl'];
				$soap_options = array (
					"trace" => true,
					"exceptions" => true,
					"connection_timeout" => 10,
					'soap_version' => SOAP_1_2
				);
				
				$this->service = new SoapClient( $wsdl, $soap_options );
				
			}
			
			
			$action = $this->getAction();
			$this->getParamsAction( "login", $this->options['login'] );
			$this->getParamsAction( "password", $this->options['password'] );
			
			$params = $this->getParamsAction();
			
			/*
			
			$ch = curl_init( $this->options['wsdl'] );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			$exec = curl_exec( $ch );
			
			file_put_contents( "{$action}_service.wsdl", $exec );
			
			// */
			
			
			// return $this->service->$action( $params );
			$res =  $this->service->$action( $params );
			/* 
			Logger::addLog( 'Request', $this->service->__getLastRequest() );
			Logger::addLog( 'Response', $this->service->__getLastResponse() );
			 */
			return $res;
			
		} catch ( SoapFault $e ) {
		
			return array( 'error_message' => $e->getMessage() );
			
		}
		
	}
	
}

?>