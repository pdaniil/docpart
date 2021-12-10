<?php

class LekopartsApi
{
    private $options,
        $handler,
        $answer,
        $base_url;

    public function __construct( array $options ) {

        $this->options = $options;
        $this->base_url = 'http://lekoparts.ru:35861/';
 
    }
    
    public function getBrands( $params_action ) {
        
        $action = 'SearchMake.do';
        $params = array (
            'http' => $params_action,
            'post' => false
        );

        $this->buildRequest( $action, $params );
        $this->exec();
        
        return $this->getAnswer();

    }

    public function getSupplies( $params_action ) {

        $action = 'search.do';
        $params = array (
            'http' => $params_action,
            'post' => false
        );

        $this->buildRequest( $action, $params );
        $this->exec();

        return $this->getAnswer();

    }

    public function getAnswer() {
		
		$error_message = '';
		$answer  = json_decode( $this->answer, true );
		
		if ( json_last_error() ) {
			
			$error_message = "Ошибка получения массива из ответа поставщика : json_last_error - " . json_last_error();

		}
		else if ( $answer['result'] == 'error'  ) {
			
			$error_message = "Ошибка API: " . $answer['result'];
			
		}
		
		if ( $error_message != '' ) { throw new Exception( $error_message ); }
		
		return $answer;

    }

    private function buildRequest( $action, $params_action ) {

    
        $request_url = $this->base_url . $action;

        $params_action['http']['user'] = $this->options['user'];
        $params_action['http']['pass'] = $this->options['pass'];
        
        $build = http_build_query( $params_action['http'] );
		
        $ch = curl_init();
        $c_options = array (
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER, false
        );

        if ( $params_action['post'] == true ) {
          
            $c_options[CURLOPT_POST] = true;
            $c_options[CURLOPT_POSTFIELDS] = $build;
            
        }
        else {

            $request_url .= "?" . $build;

        }
		
		// Logger::addLog( '$params_action', $params_action );
		// Logger::addLog( '$request_url', $request_url );
		
        $c_options[CURLOPT_URL] = $request_url;
		
        curl_setopt_array( $ch, $c_options );
        $this->handler = $ch;

        return true;
    }

    private function exec() {

        $exec = curl_exec( $this->handler );
		
		// Logger::addLog( '$exec', $exec );
		
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