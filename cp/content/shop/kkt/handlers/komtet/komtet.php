<?php

class Komtet
{
	
	public $kkt_options;//Настройки ККТ
	public $check_object;//Объект чека
	
	public $operation_id;//ID операции на стороне сервера ККТ
	public $status_sent;//Флаг отправки запроса
	public $status_approved;//Флаг печати чека
	public $message;
	
	
	
	
	
	
	public function __construct($kkt_handler_options_values, $check_object)
	{
		
		$f = fopen('handlers/komtet/logs/log_check_object_'.date('Y_m', time()).'.txt', 'a');
		fwrite($f, date("d.m.Y H:i:s", time())."\n");
		fwrite($f, json_encode($check_object)."\n");
		fwrite($f, "\n\n\n");
		
		// Получаем настройки подключения к сервису ККТ и объект сформированного чека
		$this->kkt_options = $kkt_handler_options_values;
		$this->check_object = $check_object;
		
		// Формируем объект запроса к ККТ
		if((int)$check_object['is_correction_flag'] === 1){
			$request = $this->create_check_of_correction();
		}else{
			$request = $this->create_request();
		}
		
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
		if($this->check_object['initiator'] == 'php'){
			$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 1 LIMIT 1;");// Онлайн оплата
		}else{
			$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 2 LIMIT 1;");// Ручное формирование чека
		}
		$shop_kkt_default_setting_query->execute();
		$shop_kkt_default_setting = $shop_kkt_default_setting_query->fetch();
		
		$request = array();
		
		switch($this->check_object['type']){
			case '1' :
				// Приход
				$request['intent'] = 'sell';
				
				break;
			case '2' :
				// Возврат прихода
				$request['intent'] = 'sellReturn';
				
			break;
			case '3' :
				// Расход
				$request['intent'] = 'buy';
				
			break;
			case '4' :
				// Возврат расхода
				$request['intent'] = 'buyReturn';
				
			break;
		}
		
		$request['external_id'] = (int)$this->check_object['id'];//Номер операции в вашей системе. Чек с повторяющимся external_id не будет принят системой.
		$request['sno'] = (int)$this->check_object['taxationSystem'];//Система налогообложения.
		$request['print'] = (bool)$shop_kkt_default_setting['print'];//Печатать ли чек
		$request['user'] = $this->check_object['customerContact'];//Email или телефон
		
		$positions = array();//Список позиций чека
		if(!empty($this->check_object['products'])){
			foreach($this->check_object['products'] as $product){
				$positions[] = array(
									'id' => $product['id'],//Идентификатор товара или услуги в интернет-магазине
									'name' => $product['text'],//Наименование позиции в чеке
									'measure_name' => 'шт.',//Единица измерения товара
									'price' => (float)$product['price'],//Цена позиции в чеке
									'quantity' => (int)$product['count'],//Количество единиц данного типа
									'total' => (float)(number_format(($product['price'] * $product['count']),2,'.','')),//Общая стоимость позиции в чеке
									'calculation_method' => $this->GET_calculation_method($product['paymentMethodType']),//Cпособ расчета
									'calculation_subject' => $this->GET_calculation_subject($product['paymentSubjectType']),//Предмет расчета
									'vat' => $this->GET_vat($product['tax'])//Ставка налога
									);
			}
		}
		$request['positions'] = $positions;
		
		$payments = array();//Список платежей
		if(!empty($this->check_object['payments'])){
			foreach($this->check_object['payments'] as $payment){
				$payments[] = array(
									'sum' => (float)$payment['amount'],//Общая сумма по чеку
									'type' => $this->GET_payment_type($payment['type'])//Вид оплаты
									);
			}
		}
		$request['payments'] = $payments;
		
		//$request['client'] = array('name'=>'', 'inn'=>'');//Информация о покупателе
		//$request['cashier'] = array('name'=>'', 'inn'=>'');//Информация о кассире
		
		$request['payment_address'] = $DP_Config->domain_path;//Место расчетов (Адрес магазина, название сайта)
		$request['callback_url'] = $DP_Config->domain_path . $DP_Config->backend_dir .'/content/shop/kkt/handlers/komtet/notification.php';//URL, на который будет отправлен отчет после фискализации. Переопределит значения заданные в личном кабинете. Почему то не работает, нужно пропсывать в кабинете ККТ
		
		/*
		echo '<pre>';
		var_dump($request);
		echo '</pre><hr/>';
		*/
		
		return $request;
	}
	
	
	
	
	
	
	// Создание чека коррекции
	function create_check_of_correction(){
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
		if($this->check_object['initiator'] == 'php'){
			$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 1 LIMIT 1;");// Онлайн оплата
		}else{
			$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 2 LIMIT 1;");// Ручное формирование чека
		}
		$shop_kkt_default_setting_query->execute();
		$shop_kkt_default_setting = $shop_kkt_default_setting_query->fetch();
		
		$request = array();
		
		switch($this->check_object['type']){
			case '1' :
				// Коррекция прихода
				$request['intent'] = 'sellCorrection';
				break;
			case '3' :
				// Коррекция расхода
				$request['intent'] = 'buyCorrection';
			break;
		}
		
		$request['external_id'] = (int)$this->check_object['id'];//Номер операции в вашей системе. Чек с повторяющимся external_id не будет принят системой.
		$request['sno'] = (int)$this->check_object['taxationSystem'];//Система налогообложения.
		$request['vat'] = $this->GET_vat($this->check_object['correction_tax']);//Ставка налога НДС
		$request['printer_number'] = $this->check_object['correction_printer_number'];//Серийный номер принтера
		$request['payment_sum'] = (float)number_format(trim($this->check_object['correction_totalSum']),2,'.','');//Сумма коррекции
		$request['payment_type'] = $this->GET_payment_type($this->check_object['correction_payment_type']);//Вид оплаты
		
		// Тип коррекции
		if((int)$this->check_object['correction_type'] === 0){
			$correction_type = 'self';// самостоятельно
		}else{
			$correction_type = 'forced';// по предписанию
		}
		
		// Объект описания коррекции
		$request['correction'] = array(
			"type" => $correction_type,
			"date" => date('Y-m-d', $this->check_object['correction_causeDocumentDate']),
			"document" => trim($this->check_object['correction_causeDocumentNumber']),
			"description" => trim($this->check_object['correction_description']),
		);
		
		return $request;
	}
	
	
	
	
	
	
	// Отправка запроса на сервер ККТ
	private function send_request($request)
	{
		$queue_id = trim($this->kkt_options['queue_id']);//Идентификатор очереди
		$path = 'https://kassa.komtet.ru/api/shop/v1/queues/'. $queue_id .'/task';
		
		$postdata = json_encode($request);
		$signature = hash_hmac('md5', 'POST' . $path . $postdata, $this->kkt_options['shop_secret_key']);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: '.$this->kkt_options['shop_id'],
			'X-HMAC-Signature: '.$signature
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
		
		if(empty($data['state']) && !empty($data['task'])){
			$data = $data['task'];
		}
		
		
		$f = fopen('handlers/komtet/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
		fwrite($f, date("d.m.Y H:i:s", time())."\n");
		fwrite($f, json_encode($request)."\n");
		fwrite($f, "Ответ:\n");
		fwrite($f, $curl_result."\n\n\n");
		
		
		/*
		echo '<pre>';
		var_dump($data);
		echo '</pre><hr/>';
		*/
		
		switch($data['state']){
			case 'new' :
				$message = 'Постановка задачи в очередь на фискализацию произведена. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = true;
				$status_approved = false;
			break;
			case 'processing' :
				$message = 'Задача на фискализацию выполняется. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = true;
				$status_approved = false;
			break;
			case 'done' :
				$message = 'Фискализация произведена. Уникальный идентификатор выполненной задачи '. $data['id'];
				$status_sent = true;
				$status_approved = true;
			break;
			case 'error' :
				$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = false;
				$status_approved = false;
			break;
			default :
				$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
				$status_sent = false;
				$status_approved = false;
			break;
		}
		
		$answer = array();
		$answer["operation_id"] = $data['id'];
		$answer["status_sent"] = $status_sent;
		$answer["status_approved"] = $status_approved;
		$answer["message"] = $message;//Текстовое сообщение
		return $answer;
	}
	
	
	
	
	
	
	// Получение информации о задаче на стороне ККТ
	private function get_task_status($operation_id)
	{
		$path = "https://kassa.komtet.ru/api/shop/v1/tasks/". $operation_id;
		$signature = hash_hmac('md5', 'GET' . $path, $this->kkt_options['shop_secret_key']);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: '.$this->kkt_options['shop_id'],
			'X-HMAC-Signature: '.$signature
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
		
		$f = fopen('handlers/komtet/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
		fwrite($f, date("d.m.Y H:i:s", time())."\n");
		fwrite($f, "Статус:\n");
		fwrite($f, $curl_result."\n\n\n");
		
		switch($data['state']){
			case 'new' :
				$message = 'Постановка задачи в очередь на фискализацию произведена. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = true;
				$status_approved = false;
			break;
			case 'processing' :
				$message = 'Задача на фискализацию выполняется. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = true;
				$status_approved = false;
			break;
			case 'done' :
				$message = 'Фискализация произведена. Уникальный идентификатор выполненной задачи '. $data['id'];
				$status_sent = true;
				$status_approved = true;
			break;
			case 'error' :
				$message = 'Ошибка фискализации. Уникальный идентификатор задачи '. $data['id'];
				$status_sent = false;
				$status_approved = false;
			break;
			default :
				$message = 'Ошибка. Получен не корректный ответ от сервера. Проверьте наличие чека в личном кабинете ККТ.';
				$status_sent = false;
				$status_approved = false;
			break;
		}
		
		$answer = array();
		$answer["operation_id"] = $data['id'];
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
				$vat = 'cash';//оплата наличными
			break;
			case '2' :
				$vat = 'card';//оплата безналичными (по умолчанию)
			break;
			case '14' :
				$vat = 'prepayment';//сумма предоплатой (зачет аванса и/или предыдущих платежей)
			break;
			case '15' :
				$vat = 'credit';//сумма постоплатой (кредит)
			break;
			case '16' :
				$vat = 'counter_provisioning';//сумма встречным предоставлением
			break;
			default :
				$vat = 'card';//оплата безналичными (по умолчанию)
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
				$vat = '20';//НДС 20%
			break;
			case '2' :
				$vat = '10';//НДС 10%
			break;
			case '3' :
				$vat = '120';//НДС 20/120
			break;
			case '4' :
				$vat = '110';//НДС 10/110
			break;
			case '5' :
				$vat = '0';//НДС 0%
			break;
			case '6' :
				$vat = 'no';//НДС НЕ ОБЛАГАЕТСЯ
			break;
			default :
				$vat = '20';//НДС 20%
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
				$calculation_method = 'pre_payment_full';//ПРЕДОПЛАТА 100%
			break;
			case '2' :
				$calculation_method = 'pre_payment_part';//ПРЕДОПЛАТА
			break;
			case '3' :
				$calculation_method = 'advance';//АВАНС
			break;
			case '4' :
				$calculation_method = 'full_payment';//ПОЛНЫЙ РАСЧЕТ
			break;
			case '5' :
				$calculation_method = 'credit_part';//ЧАСТИЧНЫЙ РАСЧЕТ И КРЕДИТ
			break;
			case '6' :
				$calculation_method = 'credit';//ПЕРЕДАЧА В КРЕДИТ
			break;
			case '7' :
				$calculation_method = 'credit_pay';//ОПЛАТА КРЕДИТА
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
				$calculation_subject = 'product';//ТОВАР
			break;
			case '2' :
				$calculation_subject = 'product_practical';//ПОДАКЦИЗНЫЙ ТОВАР
			break;
			case '3' :
				$calculation_subject = 'work';//РАБОТА
			break;
			case '4' :
				$calculation_subject = 'service';//УСЛУГА
			break;
			case '5' :
				$calculation_subject = 'gambling_bet';//СТАВКА АЗАРТНОЙ ИГРЫ
			break;
			case '6' :
				$calculation_subject = 'gambling_win';//ВЫИГРЫШ АЗАРТНОЙ ИГРЫ
			break;
			case '7' :
				$calculation_subject = 'lottery_bet';//ЛОТЕРЕЙНЫЙ БИЛЕТ
			break;
			case '8' :
				$calculation_subject = 'lottery_win';//ВЫИГРЫШ ЛОТЕРЕИ
			break;
			case '8' :
				$calculation_subject = 'rid';//ПРЕДОСТАВЛЕНИЕ РИД
			break;
			case '8' :
				$calculation_subject = 'payment';//ПЛАТЕЖ
			break;
			case '8' :
				$calculation_subject = 'commission';//АГЕНТСКОЕ ВОЗНАГРАЖДЕНИЕ
			break;
			case '8' :
				$calculation_subject = 'composite';//СОСТАВНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '8' :
				$calculation_subject = 'other';//ИНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '8' :
				$calculation_subject = 'property_right';//ИМУЩЕСТВЕННОЕ ПРАВО
			break;
			case '8' :
				$calculation_subject = 'non_operating';//ВНЕРЕАЛИЗАЦИОННЫЙ ДОХОД
			break;
			case '8' :
				$calculation_subject = 'insurance';//СТРАХОВЫЕ ВЗНОСЫ
			break;
			case '8' :
				$calculation_subject = 'sales_tax';//ТОРГОВЫЙ СБОР
			break;
			case '8' :
				$calculation_subject = 'resort_fee';//КУРОРТНЫЙ СБОР
			break;
			case '8' :
				$calculation_subject = 'product';//ЗАЛОГ - нет в системе поэтому поставим Товар
			break;
			default :
				$calculation_subject = 'product';//ТОВАР
			break;
		}
		
		return $calculation_subject;
	}
}
?>