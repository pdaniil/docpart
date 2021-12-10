<?php
/*
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	exit( "No DB connect" );
}
$db_link->query("SET NAMES utf8;");
*/

class Initpro
{
	
	public $api_version = 'v1.2';//Версия API под которую написан скрипт
	public $token = '';//token авторизации
	
	public $kkt_options;//Настройки ККТ
	public $check_object;//Объект чека
	
	public $operation_id;//ID операции на стороне сервера ККТ
	public $status_sent;//Флаг отправки запроса
	public $status_approved;//Флаг печати чека
	public $message;
	
	
	
	public function __construct($kkt_handler_options_values, $check_object)
	{
		// Получаем настройки подключения к сервису ККТ и объект сформированного чека
		$this->kkt_options = $kkt_handler_options_values;
		$this->check_object = $check_object;
		
		// Формируем объект запроса к ККТ
		if((int)$check_object['is_correction_flag'] === 1){
			$request = $this->create_check_of_correction();
		}else{
			$request = $this->create_request();
		}
		
		/*
		echo '<pre>';
		var_dump($request);
		exit;
		*/
		
		// Отправляем запрос на ККТ
		$answer = $this->send_request($request);
		$this->operation_id = $answer['operation_id'];
		$this->status_sent = $answer['status_sent'];
		$this->status_approved = $answer['status_approved'];
		$this->message = $answer['message'];
		
		// Проверяем произошла ли фискализация чека
		$iterator = 0;
		if($this->status_approved === false && $this->status_sent === true){
			do{
				if($iterator > 0){
					usleep(10000000);// 10 сек. задержка
				}
				$iterator++;
				
				$answer = $this->get_task_status($this->operation_id);
				$this->operation_id = $answer['operation_id'];
				$this->status_sent = $answer['status_sent'];
				$this->status_approved = $answer['status_approved'];
				$this->message = $answer['message'];
				
			}while($this->status_approved === false && $iterator < 3);
		}
	}
	
	
	
	
	
	
	// Формирование запроса к ККТ
	private function create_request()
	{
		$DP_Config = new DP_Config;//Конфигурация CMS
		
		//Подключение к БД
		try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = 'Не удалось подключиться к локальной базе данных';//Текстовое сообщение
			return $answer;
		}
		$db_link->query("SET NAMES utf8;");
		
		// Настройки параметров чека
		$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 1 LIMIT 1;");
		$shop_kkt_default_setting_query->execute();
		$shop_kkt_default_setting = $shop_kkt_default_setting_query->fetch();
		
		$request = array();
		$request['external_id'] = (string) $this->check_object['id'];//Номер операции в вашей системе. Чек с повторяющимся external_id не будет принят системой.
		
		switch($this->check_object['type']){
			case '1' :
				// Приход
				$operation = 'sell';
				
				break;
			case '2' :
				// Возврат прихода
				$operation = 'sell_refund';
				
			break;
			case '3' :
				// Расход
				$operation = 'buy';
				
			break;
			case '4' :
				// Возврат расхода
				$operation = 'buy_refund';
				
			break;
		}
		
		//Адрес по которому будет отправлен запрос на печать чека
		
		
		$receipt = array();
		
		//______________________________________________________
		
		//Email или телефон клиента на который будет отправлен чек
		$email = '';
		$phone = '';
		$main_field = $this->check_object['customerContact'];
		if(strpos($main_field, '@') === false){
			$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $main_field);
			if(strlen($phone) == 11){
				$phone = substr($phone, 1);
			}
			$phone = '+7'.$phone;
		}else{
			$email = trim($main_field);
		}
		
		if($phone !== ''){
			$client = array("phone" => $phone);
		}else{
			$client = array("email" => $email);
		}
		
		$receipt['client'] = $client;
		
		//______________________________________________________
		
		//Атрибуты компании
		$company = array(
						"email" => $this->kkt_options['email'], // Электронная почта отправителя чека
						"sno" => $this->GET_sno($this->check_object['taxationSystem']), // Система налогообложения
						"inn" => $this->kkt_options['inn'], // ИНН организации
						"payment_address" => $DP_Config->domain_path // Место расчетов (домен сайта)
						);
		
		$receipt['company'] = $company;
		
		//______________________________________________________
		
		//Список позиций чека
		$positions = array();
		if(!empty($this->check_object['products'])){
			foreach($this->check_object['products'] as $product){
				
				$vat = array();
				$vat['type'] = $this->GET_vat($product['tax']);
				
				$positions[] = array(
									'name' => $product['text'],//Наименование позиции в чеке
									'price' => (float)$product['price'],//Цена позиции в чеке
									'quantity' => (int)$product['count'],//Количество единиц данного типа
									'sum' => (float)(number_format(($product['price'] * $product['count']),2,'.','')),//Общая стоимость позиции в чеке
									'measurement_unit' => 'шт.',//Единица измерения товара
									'payment_method' => $this->GET_calculation_method($product['paymentMethodType']),//Cпособ расчета
									'payment_object' => $this->GET_calculation_subject($product['paymentSubjectType']),//Предмет расчета
									'vat' => $vat//Ставка налога
									);
			}
		}
		
		$receipt['items'] = $positions;
		
		//______________________________________________________
		
		$total = 0;
		$payments = array();//Список платежей
		if(!empty($this->check_object['payments'])){
			foreach($this->check_object['payments'] as $payment){
				$payments[] = array(
									'sum' => (float)$payment['amount'],//Общая сумма по чеку
									'type' => $this->GET_payment_type($payment['type'])//Вид оплаты
									);
				$total += $payment['amount'];
			}
		}
		$receipt['payments'] = $payments;
		$receipt['total'] = $total;//Итоговая сумма чека в рублях
		//______________________________________________________
		
		$request['receipt'] = $receipt;
		
		//______________________________________________________
		
		$request['timestamp'] = date("d.m.Y H:i:s", $this->check_object['time_created']);//Дата и время dd.mm.yyyy HH:MM:SS
		
		$request['service'] = array(
									"callback_url" => $DP_Config->domain_path . $DP_Config->backend_dir .'/content/shop/kkt/handlers/initpro/notification.php'//URL, на который будет отправлен отчет после фискализации.
									);
		
		/*
		echo '<pre>';
		var_dump($request);
		echo '</pre><hr/>';
		*/
		
		return array("url"=>'https://kassa.initpro.ru/lk/api/'. $this->api_version .'/'. $this->kkt_options['GroupCode'] .'/'. $operation, "request"=>$request);
	}
	
	
	
	
	
	// Формирование запроса к ККТ
	private function create_check_of_correction()
	{
		$DP_Config = new DP_Config;//Конфигурация CMS
		
		//Подключение к БД
		try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = 'Не удалось подключиться к локальной базе данных';//Текстовое сообщение
			return $answer;
		}
		$db_link->query("SET NAMES utf8;");
		
		// Настройки параметров чека
		$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 1 LIMIT 1;");
		$shop_kkt_default_setting_query->execute();
		$shop_kkt_default_setting = $shop_kkt_default_setting_query->fetch();
		
		$request = array();
		$request['external_id'] = (string) $this->check_object['id'];//Номер операции в вашей системе. Чек с повторяющимся external_id не будет принят системой.
		
		
		
		
		
		// Коррекция.
		$correction = array();
		
		
		
		
		
		$company = array(
						"email" => $this->kkt_options['email'], // Электронная почта отправителя чека
						"sno" => $this->GET_sno($this->check_object['taxationSystem']), // Система налогообложения
						"inn" => $this->kkt_options['inn'], // ИНН организации
						"payment_address" => $DP_Config->domain_path // Место расчетов (домен сайта)
						);
						
		$correction['company'] = $company;
		
		
		
		
		
		// Тип коррекции
		if((int)$this->check_object['correction_type'] === 0){
			$correction_type = 'self';// самостоятельно
		}else{
			$correction_type = 'instruction';// по предписанию
		}
		
		// Объект описания коррекции
		$correction_info = array(
			"type" => $correction_type,
			"base_date" => date('d.m.Y', $this->check_object['correction_causeDocumentDate']),
			"base_number" => trim($this->check_object['correction_causeDocumentNumber']),
			"base_name" => trim($this->check_object['correction_description']),
		);
		
		$correction['correction_info'] = $correction_info;
		
		
	
	
		
		$payments = array();
		
		// Наличными
		if(!empty($this->check_object['correction_cashSum'])){
			$payments[] = array(
						'sum' => $this->check_object['correction_cashSum'],//Общая сумма по чеку
						'type' => $this->GET_payment_type('1')//Вид оплаты
						);
		}
		
		// безналичными
		if(!empty($this->check_object['correction_eCashSum'])){
			$payments[] = array(
						'sum' => $this->check_object['correction_eCashSum'],//Общая сумма по чеку
						'type' => $this->GET_payment_type('2')//Вид оплаты
						);
		}
		
		// предоплатой
		if(!empty($this->check_object['correction_prepaymentSum'])){
			$payments[] = array(
						'sum' => $this->check_object['correction_prepaymentSum'],//Общая сумма по чеку
						'type' => $this->GET_payment_type('14')//Вид оплаты
						);
		}
		
		// постоплатой
		if(!empty($this->check_object['correction_postpaymentSum'])){
			$payments[] = array(
						'sum' => $this->check_object['correction_postpaymentSum'],//Общая сумма по чеку
						'type' => $this->GET_payment_type('15')//Вид оплаты
						);
		}
		
		// встречным предоставлением
		if(!empty($this->check_object['correction_otherPaymentTypeSum'])){
			$payments[] = array(
						'sum' => $this->check_object['correction_otherPaymentTypeSum'],//Общая сумма по чеку
						'type' => $this->GET_payment_type('16')//Вид оплаты
						);
		}
		
		$correction['payments'] = $payments;
		
		
		
		
		
		$vats = array();
		
		//Сумма НДС в чеке по ставке 20%
		if($this->check_object['correction_tax1Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(1),
						'sum' => $this->check_object['correction_tax1Sum']
						);
		}
		
		//Сумма НДС в чеке по ставке 10%
		if($this->check_object['correction_tax2Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(2),
						'sum' => $this->check_object['correction_tax2Sum']
						);
		}
		
		//Сумма НДС в чеке по ставке 0%
		if($this->check_object['correction_tax3Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(5),
						'sum' => $this->check_object['correction_tax3Sum']
						);
		}
		
		//Сумма по чеку без НДС
		if($this->check_object['correction_tax4Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(6),
						'sum' => $this->check_object['correction_tax4Sum']
						);
		}
		
		//Сумма НДС в чеке по ставке 20/120
		if($this->check_object['correction_tax5Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(3),
						'sum' => $this->check_object['correction_tax5Sum']
						);
		}
		
		//Сумма НДС в чеке по ставке 10/110
		if($this->check_object['correction_tax6Sum']){
			$vats[] = array(
						'type' => $this->GET_vat(4),
						'sum' => $this->check_object['correction_tax6Sum']
						);
		}
		
		$correction['vats'] = $vats;
		
		$request['correction'] = $correction;
		
		$request['timestamp'] = date("d.m.Y H:i:s", $this->check_object['time_created']);//Дата и время dd.mm.yyyy HH:MM:SS
		
		$request['service'] = array(
									"callback_url" => $DP_Config->domain_path . $DP_Config->backend_dir .'/content/shop/kkt/handlers/initpro/notification.php'//URL, на который будет отправлен отчет после фискализации.
									);

		//______________________________________________________

		switch($this->check_object['type']){
			case '1' :
				// Приход
				$operation = 'sell_correction';
				
				break;
			case '3' :
				// Расход
				$operation = 'buy_correction';
				
			break;
		}
		
		return array("url"=>'https://kassa.initpro.ru/lk/api/'. $this->api_version .'/'. $this->kkt_options['GroupCode'] .'/'. $operation, "request"=>$request);
	}
	
	
	
	
	
	
	
	
	
	
	// Отправка запроса на сервер ККТ
	private function send_request($request)
	{
		$DP_Config = new DP_Config;//Конфигурация CMS
		
		/////////////////////////////////////////////////////////////////////////////////////////
		
		// Запрос авторизационного токена
		
		if(file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/kkt/handlers/initpro/token.txt')){
			$file_handle = @fopen($_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/kkt/handlers/initpro/token.txt', "r");
			if($file_handle) 
			{
				$string = fgets($file_handle);
				$data = json_decode($string, true);
				
				$time_success = (((int) $data['time']) + (3600 * 22));// 22 ч.
				if($time_success > time()){
					$this->token = $data['token'];
				}
			}
		}
		
		if(empty($this->token))
		{
			$path = 'https://kassa.initpro.ru/lk/api/'. $this->api_version .'/getToken';
			
			$request_data = array('login' => $this->kkt_options['ShopId'], 'pass' => $this->kkt_options['Secret']);
			
			$postdata = json_encode($request_data);
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-type: application/json; charset=utf-8'
			));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);

			$data = json_decode($curl_result, true);
			
			if( empty($data['error']) && !empty($data['token']) ){
				$this->token = $data['token'];
				
				$f_token = array('token' => $this->token, 'time' => time());
				$f = fopen($_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/kkt/handlers/initpro/token.txt', 'w');
				fwrite($f, json_encode($f_token));
			}else{
				$f = fopen($_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/kkt/handlers/initpro/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
				fwrite($f, date("d.m.Y H:i:s", time())."\n");
				fwrite($f, $path."\n");
				fwrite($f, $postdata."\n");
				fwrite($f, "Ответ:\n");
				fwrite($f, $curl_result."\n\n\n");
			}
		}
		/////////////////////////////////////////////////////////////////////////////////////////
		
		if( ! empty($this->token) )
		{
			$queue_id = trim($this->kkt_options['queue_id']);//Идентификатор очереди
			$path = $request['url'];
			
			$postdata = json_encode($request['request']);
			
			/*
			echo '<pre>';
			var_dump($request);
			echo '</pre><hr/>';
			exit;
			*/
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-type: application/json; charset=utf-8',
				'Token: '.$this->token
			));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$curl_result = curl_exec($ch);
			curl_close($ch);

			$data = json_decode($curl_result, true);
			
			$f = fopen('handlers/initpro/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
			fwrite($f, date("d.m.Y H:i:s", time())."\n");
			fwrite($f, json_encode($request)."\n");
			fwrite($f, "Ответ:\n");
			fwrite($f, $curl_result."\n\n\n");
			
			
			/*
			echo '<pre>';
			var_dump($data);
			echo '</pre><hr/>';
			*/
			
			switch($data['status']){
				case 'wait' :
					$message = 'Постановка задачи в очередь на фискализацию произведена. Уникальный идентификатор задачи '. $data['uuid'];
					$status_sent = true;
					$status_approved = false;
				break;
				case 'fail' :
					$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['uuid'];
					$status_sent = true;
					$status_approved = false;
				break;
				default :
					$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
					$status_sent = false;
					$status_approved = false;
				break;
			}
			
			$answer = array();
			$answer["operation_id"] = $data['uuid'];
			$answer["status_sent"] = $status_sent;
			$answer["status_approved"] = $status_approved;
			$answer["message"] = $message;//Текстовое сообщение
			return $answer;
		}else{
			$answer = array();
			$answer["message"] = 'Нет авторизации в ККТ';//Текстовое сообщение
			return $answer;
		}
	}
	
	
	
	
	
	
	// Получение информации о задаче на стороне ККТ
	private function get_task_status($operation_id)
	{
		$path = 'https://kassa.initpro.ru/lk/api/'. $this->api_version .'/'. $this->kkt_options['GroupCode'] .'/report/'. $operation_id;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Token: '.$this->token
			));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		$curl_result = curl_exec($ch);
		curl_close($ch);

		$data = json_decode($curl_result, true);
		
		/*
		echo '<pre>';
		var_dump($data);
		echo '</pre><hr/>';
		*/
		
		$f = fopen('handlers/initpro/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
		fwrite($f, date("d.m.Y H:i:s", time())."\n");
		fwrite($f, "Статус:\n");
		fwrite($f, $curl_result."\n\n\n");
		
		switch($data['status']){
			case 'done' :
				$message = 'Фискализация произведена. Уникальный идентификатор выполненной задачи '. $data['uuid'];
				$status_sent = true;
				$status_approved = true;
			break;
			case 'fail' :
				$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['uuid'] .'. Полученная ошибка: '. $data['error']['text'];
				$status_sent = true;
				$status_approved = false;
			break;
			case 'wait' :
				$message = 'Ожидание в очереди на фискализацию. Уникальный идентификатор задачи '. $data['uuid'];
				$status_sent = true;
				$status_approved = false;
			break;
			default :
				$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
				$status_sent = false;
				$status_approved = false;
			break;
		}
		
		$answer = array();
		$answer["operation_id"] = $data['uuid'];
		$answer["status_sent"] = $status_sent;
		$answer["status_approved"] = $status_approved;
		$answer["message"] = $message;//Текстовое сообщение
		return $answer;
	}
	
	
	
	
	
	
	// Получение параметра - Тип оплаты.
	private function GET_payment_type($type)
	{
		//Тип оплаты
		switch($type){
			case '1' :
				$vat = '1';//оплата наличными
			break;
			case '2' :
				$vat = '1';//оплата безналичными (по умолчанию)
			break;
			case '14' :
				$vat = '2';//сумма предоплатой (зачет аванса и/или предыдущих платежей)
			break;
			case '15' :
				$vat = '3';//сумма постоплатой (кредит)
			break;
			case '16' :
				$vat = '4';//сумма встречным предоставлением
			break;
			default :
				$vat = '1';//оплата безналичными (по умолчанию)
			break;
		}
		
		return $vat;
	}
	
	
	
	
	
	
	// Получение параметра - Ставка налога.
	private function GET_vat($tax)
	{
		//Cпособ расчета
		switch($tax){
			case '1' :
				$vat = 'vat20';//НДС 20%
			break;
			case '2' :
				$vat = 'vat10';//НДС 10%
			break;
			case '3' :
				$vat = 'vat120';//НДС 20/120
			break;
			case '4' :
				$vat = 'vat110';//НДС 10/110
			break;
			case '5' :
				$vat = 'vat0';//НДС 0%
			break;
			case '6' :
				$vat = 'none';//НДС НЕ ОБЛАГАЕТСЯ
			break;
			default :
				$vat = 'vat20';//НДС 20%
			break;
		}
		
		return $vat;
	}
	
	
	
	
	
	
	// Получение параметра - Cпособ расчета.
	private function GET_calculation_method($paymentMethodType)
	{
		//Cпособ расчета
		switch($paymentMethodType){
			case '1' :
				$calculation_method = 'full_prepayment';//ПРЕДОПЛАТА 100%
			break;
			case '2' :
				$calculation_method = 'prepayment';//ПРЕДОПЛАТА
			break;
			case '3' :
				$calculation_method = 'advance';//АВАНС
			break;
			case '4' :
				$calculation_method = 'full_payment';//ПОЛНЫЙ РАСЧЕТ
			break;
			case '5' :
				$calculation_method = 'partial_payment';//ЧАСТИЧНЫЙ РАСЧЕТ И КРЕДИТ
			break;
			case '6' :
				$calculation_method = 'credit';//ПЕРЕДАЧА В КРЕДИТ
			break;
			case '7' :
				$calculation_method = 'credit_payment';//ОПЛАТА КРЕДИТА
			break;
			default :
				$calculation_method = 'full_payment';//ПОЛНЫЙ РАСЧЕТ
			break;
		}
		
		return $calculation_method;
	}
	
	
	
	
	
	
	// Получение параметра - Предмет расчета.
	private function GET_calculation_subject($paymentSubjectType)
	{
		//Предмет расчета
		switch($paymentSubjectType){
			case '1' :
				$calculation_subject = 'commodity';//ТОВАР
			break;
			case '2' :
				$calculation_subject = 'excise';//ПОДАКЦИЗНЫЙ ТОВАР
			break;
			case '3' :
				$calculation_subject = 'job';//РАБОТА
			break;
			case '4' :
				$calculation_subject = 'service';//УСЛУГА
			break;
			case '5' :
				$calculation_subject = 'gambling_bet';//СТАВКА АЗАРТНОЙ ИГРЫ
			break;
			case '6' :
				$calculation_subject = 'gambling_prize';//ВЫИГРЫШ АЗАРТНОЙ ИГРЫ
			break;
			case '7' :
				$calculation_subject = 'lottery';//ЛОТЕРЕЙНЫЙ БИЛЕТ
			break;
			case '8' :
				$calculation_subject = 'lottery_prize';//ВЫИГРЫШ ЛОТЕРЕИ
			break;
			case '8' :
				$calculation_subject = 'intellectual_activity';//ПРЕДОСТАВЛЕНИЕ РИД
			break;
			case '8' :
				$calculation_subject = 'payment';//ПЛАТЕЖ
			break;
			case '8' :
				$calculation_subject = 'agent_commission';//АГЕНТСКОЕ ВОЗНАГРАЖДЕНИЕ
			break;
			case '8' :
				$calculation_subject = 'composite';//СОСТАВНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '8' :
				$calculation_subject = 'another';//ИНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '8' :
				$calculation_subject = 'property_right';//ИМУЩЕСТВЕННОЕ ПРАВО
			break;
			case '8' :
				$calculation_subject = 'non-operating_gain';//ВНЕРЕАЛИЗАЦИОННЫЙ ДОХОД
			break;
			case '8' :
				$calculation_subject = 'insurance_premium';//СТРАХОВЫЕ ВЗНОСЫ
			break;
			case '8' :
				$calculation_subject = 'sales_tax';//ТОРГОВЫЙ СБОР
			break;
			case '8' :
				$calculation_subject = 'resort_fee';//КУРОРТНЫЙ СБОР
			break;
			case '8' :
				$calculation_subject = 'commodity';//ЗАЛОГ - нет в системе поэтому поставим Товар
			break;
			default :
				$calculation_subject = 'commodity';//ТОВАР
			break;
		}
		
		return $calculation_subject;
	}
	
	
	
	
	
	
	// Получение параметра - Система налогообложения.
	private function GET_sno($sno)
	{
		//Предмет расчета
		switch($sno){
			case '0' :
				$sno = 'osn';//Общая - ОСН
			break;
			case '1' :
				$sno = 'usn_income';//Упрощенная доход - УСН доход
			break;
			case '2' :
				$sno = 'usn_income_outcome';//Упрощенная доход минус расход - УСН доход - расход
			break;
			case '3' :
				$sno = 'envd';//Единый налог на вмененный доход - ЕНВД
			break;
			case '4' :
				$sno = 'esn';//Единый сельскохозяйственный налог - ЕСХН
			break;
			case '5' :
				$sno = 'patent';//Патентная система налогообложения - Патент
			break;
		}
		
		return $sno;
	}
}

// Для тестирования при разработке и вызове скрипта напрямую


/*
//$f = fopen('log.txt', 'w');
//fwrite($f, json_encode($this->kkt_handler_options_values)."\n\n");
//fwrite($f, json_encode($check_object)."\n");
//exit;

$kkt_handler_options_values = '';
$check_object = '';


$kkt_handler_options_values = json_decode($kkt_handler_options_values, true);
$check_object = json_decode($check_object, true);

echo '<pre style="max-height: 400px; overflow-y: auto;">';
var_dump($check_object);
echo '</pre>';
echo '<hr>';

$Initpro = new Initpro($kkt_handler_options_values, $check_object);//Создаем объект работы с ККТ передавая параметры подключения

$status_sent = $Initpro->status_sent;
$status_approved = $Initpro->status_approved;
$message = $Initpro->message;


echo '$status_sent = '. $status_sent;
echo '<br>';
echo '$status_approved = '. $status_approved;
echo '<br>';
echo '$message = '. $message;
echo '<br>';
*/


?>