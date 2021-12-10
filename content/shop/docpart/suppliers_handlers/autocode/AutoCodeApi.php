<?php

class AutoCodeApi
{
    private $options,
        $handler,
        $answer,
        $base_url;

    public function __construct( array $options ) {

        $this->options = $options;
        $this->base_url = 'http://api.autocode.ru/';
		$this->auth();
		
    }
    
	//Получение токена для последующих запросов
    public function auth() {

        $action = "token";

        $params = array();
        $params['http'][] = $this->options['login'];
        $params['http'][] = $this->options['password'];
        $params['post'] = false;

        $this->buildRequest( $action, $params );
        $this->exec();

        $answer = $this->getDecodeAnswer();
		
		if ( is_null( $answer ) ) {
			
			throw new Exception( 'Ошибка получения токена, логин или пароль указаны неправильно!' );
			
		}
		
		$auth_data = array();
		$auth_data['access_token'] = $answer['access_token'];
		$auth_data['token_type'] = $answer['token_type'];
		
		$this->options['auth_data'] = $auth_data;
		return true;
		
    }
	
	//Получение списка брендов
    public function getBrands( $params_action ) {
        
        $action = 'goods/GetBrandsByPhrases';
        $params = array (
            'http' => $params_action,
            'post' => false
        );

        $this->buildRequest( $action, $params );
        $this->exec();

        return $this->getDecodeAnswer();

    }
	//Получение позиций по артикулу и бренду
    public function getSupplies( $params_action ) {

        $action = 'goods/GetPriceByArtAndBrand';
        $params = array (
            'http' => $params_action,
            'post' => false
        );

        $this->buildRequest( $action, $params );
        $this->exec();

        return $this->getDecodeAnswer();

    }
	
	//Получение ответа поставщика
	public function getAnswer() {
		
		return $this->answer;
		
	}
	
	//Получение обработанного ответа поставщика, с перехватом возможных ошибок
    public function getDecodeAnswer() {
		
		$error_message = '';
		$answer  = json_decode( $this->getAnswer(), true );
		
		if ( json_last_error() ) {
			
			$error_message = "Ошибка получения массива из ответа поставщика : json_last_error - " . json_last_error();

		}
		else if ( isset( $answer['Message'] ) ) {
			
			$error_message = "Ошибка API: " . $answer['error'];
			
		}
		
		if ( $error_message != '' ) { throw new Exception( $error_message ); }
		
		return $answer;

    }
	
	//Инициализация обработчика запроса
    private function buildRequest( $action, $params_action ) {

        $request_url = $this->base_url . $action;

        $headers = array();
        $headers[] = 'Content-Type: application/json; charset=utf-8';
		
		//Если есть данные токена авторизации
        if ( isset( $this->options['auth_data'] ) ) {

            $headers[] = "Authorization: {$this->options['auth_data']['token_type']} {$this->options['auth_data']['access_token']}";

        }

        $ch = curl_init();

        $c_options = array (
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers
        );

        if ( $params_action['post'] == true ) {
            
            $build = $this->getBuild( $params_action['http'] );

            $c_options[CURLOPT_POST] = true;
            $c_options[CURLOPT_POSTFIELDS] = $build;
            
        }
        else {

            $build = $this->getBuild( $params_action['http'] );
            $request_url .= "/" . $build;

        }
		
        $c_options[CURLOPT_URL] = $request_url;
		
        curl_setopt_array( $ch, $c_options );
        $this->handler = $ch;

        return true;
    }
	
	//Создание набора параметров для POST-запроса
	private function postBuild( array $params ) {
		
		return http_build_query( $params );
		
	}
	//Создание набора параметров для GET-запроса
	private function getBuild( array $params ) {
		
		$build_params = array();
		foreach( $params as $p ) {
			
			$p = urldecode( $p );
			$build_params[] = $p;
			
		}
		
		$uri_params = implode( '/', $build_params );
		return $uri_params;
		
	}
	
	//Выполнение запроса
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