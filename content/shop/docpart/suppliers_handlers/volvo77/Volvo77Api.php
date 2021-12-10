<?php

class Volvo77Api
{
    private $options,
        $handler,
        $answer;

    public function __construct( array $options ) {

        $this->options = $options;
 
    }
    
    public function getBrands( $params_action ) {
		
		$params_action['API_ID'] = $this->options['api_id'];
        $action = "auto/api_price/";
        $params = array (
            'post' => false,
            'http' => $params_action
        );

        $this->buildRequest( $action, $params );
        $this->exec();
        $answer = $this->getAnswer();
        return $answer;

    }

    public function getSupplies( $params_action ) {

        //Т.к методы отличаются только набором параметров...
        return $this->getBrands( $params_action );

    }

    public function getAnswer() {
		
		$error_message = '';
		$answer  = json_decode( $this->answer, true );
		
		if ( json_last_error() ) {
			
			$error_message = "Ошибка получения массива из ответа поставщика : json_last_error - " . json_last_error();

		}
		else if ( isset( $answer['error'] ) ) {
			
			$error_message = "Ошибка API: " . $answer['error'];
			
		}
		
		if ( $error_message != '' ) { throw new Exception( $error_message ); }
		
		return $answer;

    }

    private function buildRequest( $action, $params_action ) {

        $base_url = "https://77volvo.ru/";
        $request_url = $base_url . $action;
		
        $build = http_build_query( $params_action['http'] );
		
        $ch = curl_init();
        $c_options = array (
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER, false
        );
		
		$c_options[CURLOPT_SSL_VERIFYPEER] = false;
        $c_options[CURLOPT_SSL_VERIFYHOST] = false;
		$c_options[CURLOPT_FOLLOWLOCATION] = true;

        if ( $params_action['post'] == true ) {
          
            $c_options[CURLOPT_POST] = true;
            $c_options[CURLOPT_POSTFIELDS] = $build;
            
        }
        else {

            $request_url .= "?" . $build;

        }

        $c_options[CURLOPT_URL] = $request_url;
		
        curl_setopt_array( $ch, $c_options );
        $this->handler = $ch;

        return true;
    }

    private function exec() {

        $exec = curl_exec( $this->handler );

        if ( $exec == ''
             || curl_errno( $this->handler )
        ) {

            $error_message = "Ошибка curl : " . curl_errno( $this->handler ) ;
            throw new Exception( $error_message );
        }

        $this->answer = $exec;
        curl_close( $this->handler );

        return true;

    }
}
?>