<?php
header('Content-Type: text/html; charset=utf-8');
//ini_set('display_errors', 1);

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


class Dreamkas
{
	public $kkt_options;//Настройки ККТ
	public $check_object;//Объект чека
	
	public $operation_id;//ID операции на стороне сервера ККТ
	public $status_sent;//Флаг отправки запроса
	public $status_approved;//Флаг печати чека
	public $message;
	
	
	
	public function __construct($kkt_handler_options_values, $check_object)
	{
		/*
		$f = fopen('log.txt', 'w');
		fwrite($f, json_encode($kkt_handler_options_values)."\n\n\n");
		fwrite($f, json_encode($check_object)."\n\n\n");
		exit;
		*/
		
		/*
		echo '<pre>';
		var_dump($kkt_handler_options_values);
		echo '<hr>';
		var_dump($check_object);
		exit;
		*/
		
		// Получаем настройки подключения к сервису ККТ и объект сформированного чека
		$this->kkt_options = $kkt_handler_options_values;
		$this->check_object = $check_object;
		
		$flag_access = true;
		
		// Проверим если по данному чеку уже отправлен запрос в ККТ тогда завершим операцию
		//***************************************************************************************
		
		// Запишем id операции созданной на стороне KKT
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
		
		$operation_query = $db_link->prepare("SELECT `real_device_text_answer` FROM `shop_kkt_checks` WHERE `id` = ?;");
		$operation_query->execute(array($this->check_object['id']));
		$record = $operation_query->fetch();
		$response_id = $record['real_device_text_answer'];
		
		if(!empty($response_id)){
			$flag_access = false;//Запрещаем повторную отправку чека
			
			$f = fopen('handlers/dreamkas/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
			fwrite($f, date("d.m.Y H:i:s", time())."\n");
			fwrite($f, "Повтор чака id: ". $this->check_object['id'] ."\n");
			fwrite($f, "Операция в базе: ". $response_id ."\n");
			fwrite($f, json_encode($this->check_object)."\n");
			fwrite($f, "Повторная отправка. Прервано принудительно\n\n\n");
		}
		
		//***************************************************************************************
		
		if($flag_access){
			// Формируем объект запроса к ККТ
			if((int)$check_object['is_correction_flag'] === 1){
				$request = $this->create_check_of_correction();
			}else{
				$request = $this->create_request();
			}
			
			/*
			echo '<h3>Запрос:</h3>';
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
					
				}while($this->status_approved === false && $iterator < 1);
			}
		}
	}
	
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////	




	
	// Формирование запроса к ККТ
	private function create_request()
	{
		$request = array();
		
		// Идентификатор устройства, которое будет использовано для фискализации
		$request['deviceId'] = $this->kkt_options['deviceId'];
		
		// Тип операции
		switch($this->check_object['type']){
			case '1' :
				// Приход
				$request['type'] = 'SALE';
				
				break;
			case '2' :
				// Возврат прихода
				$request['type'] = 'REFUND';
				
			break;
			case '3' :
				// Расход
				$request['type'] = 'OUTFLOW';
				
			break;
			case '4' :
				// Возврат расхода
				$request['type'] = 'OUTFLOW_REFUND';
				
			break;
		}
		
		// Таймаут фискализации в минутах
		$request['timeout'] = 5;
		
		// Система налогообложения
		$request['taxMode'] = $this->GET_sno($this->check_object['taxationSystem']);
		
		//______________________________________________________
		
		//Email или телефон клиента на который будет отправлен чек
		$attributes = array();
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
		
		$attributes['email'] = $email;
		$attributes['phone'] = $phone;
		
		$request['attributes'] = $attributes;
		
		//______________________________________________________
		
		//Список позиций чека
		$positions = array();
		if(!empty($this->check_object['products'])){
			foreach($this->check_object['products'] as $product){
				$positions[] = array(
									'name' => $product['text'],//Наименование позиции в чеке
									'type' => "COUNTABLE",//Тип товара, Штучный
									'price' => (float)$product['price'] * 100,//Цена позиции в чеке в копейках
									'quantity' => (int)$product['count'],//Количество единиц данного типа
									'tax' => $this->GET_vat($product['tax']),//Тип НДС
									//Теги ОФД:
									'tags' => array(
													array('tag'=>1214, 'value'=>(int)$this->GET_calculation_method($product['paymentMethodType'])), 
													array('tag'=>1212, 'value'=>(int)$this->GET_calculation_subject($product['paymentSubjectType']))
													)
									);
			}
		}
		
		$request['positions'] = $positions;
		
		//______________________________________________________
		
		$total = 0;
		$payments = array();//Список платежей
		if(!empty($this->check_object['payments'])){
			foreach($this->check_object['payments'] as $payment){
				$payments[] = array(
									'sum' => (float)$payment['amount']*100,//Общая сумма по чеку
									'type' => $this->GET_payment_type($payment['type'])//Вид оплаты
									);
				$total += $payment['amount'];
			}
		}
		$request['payments'] = $payments;
		$request['total'] = array('priceSum'=>$total*100);//Итоговая сумма чека в копецках
		
		return array("url"=>'https://kabinet.dreamkas.ru/api/receipts', "request"=>$request);
	}
	
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	
	
	
	
	// Формирование запроса к ККТ
	private function create_check_of_correction()
	{
		// Не реализовано на строне KKT
		return false;
	}
	
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////	
	
	
	
	
	
	// Отправка запроса на сервер ККТ
	private function send_request($request)
	{
		if( ! empty($request) )
		{
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
			  "Content-Type: application/json",
			  "Authorization: Bearer ". $this->kkt_options['token']
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
			
			$f = fopen('handlers/dreamkas/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
			fwrite($f, date("d.m.Y H:i:s", time())."\n");
			fwrite($f, "Объект чека:\n");
			fwrite($f, json_encode($this->check_object)."\n");
			fwrite($f, "Запрос:\n");
			fwrite($f, json_encode($request)."\n");
			fwrite($f, "Ответ:\n");
			fwrite($f, $curl_result."\n\n\n");
			
			//***************************************************************************************
			
			// Запишем id операции созданной на стороне KKT
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
			
			if(!empty($data['id'])){
				$query = $db_link->prepare("UPDATE `shop_kkt_checks` SET `real_device_text_answer` = ? WHERE `id` = ?;");
				$query->execute(array('('.$data['id'].')', $this->check_object['id']));
			}
			
			//***************************************************************************************
			
			/*
			echo '<pre>';
			var_dump($data);
			echo '</pre><hr/>';
			*/
			
			switch($data['status']){
				case '401' :
					$message = 'Требуется авторизация на сервере dreamkas. Проверьте Токен.';
					$status_sent = false;
					$status_approved = false;
				break;
				case 'PENDING' :
					$message = 'Постановка задачи в очередь на фискализацию произведена. Уникальный идентификатор задачи ('. $data['id'] .')';
					$status_sent = true;
					$status_approved = false;
				break;
				case 'IN_PROGRESS' :
					$message = 'Процесс фискализации запущен. Уникальный идентификатор задачи ('. $data['id'] .')';
					$status_sent = true;
					$status_approved = false;
				break;
				case 'SUCCESS' :
					$message = 'Готово. Фискализация произведена. Уникальный идентификатор задачи ('. $data['id'] .')';
					$status_sent = true;
					$status_approved = false;
				break;
				case 'ERROR' :
					$message = 'Ошибка фискализации. Уникальный идентификатор задачи ('. $data['id'] .')';
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
			$answer["operation_id"] = $data['id'];
			$answer["status_sent"] = $status_sent;
			$answer["status_approved"] = $status_approved;
			$answer["message"] = $message;//Текстовое сообщение
			return $answer;
		}else{
			$answer = array();
			$answer["message"] = 'Нет данных для отпраки в ККТ';//Текстовое сообщение
			return $answer;
		}
	}
	
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////	




	
	// Получение информации о задаче на стороне ККТ
	private function get_task_status($operation_id)
	{
		$path = 'https://kabinet.dreamkas.ru/api/operations/'. $operation_id;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $path);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  "Content-Type: application/json",
		  "Authorization: Bearer ". $this->kkt_options['token']
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
		
		$f = fopen('handlers/dreamkas/logs/log_request_'.date('Y_m', time()).'.txt', 'a');
		fwrite($f, date("d.m.Y H:i:s", time())."\n");
		fwrite($f, "Статус:\n");
		fwrite($f, $curl_result."\n\n\n");
		
		switch($data['status']){
			case '401' :
					$message = 'Требуется авторизация на сервере dreamkas. Проверьте Токен.';
					$status_sent = false;
					$status_approved = false;
				break;
			case 'PENDING' :
				$message = 'Постановка задачи в очередь на фискализацию произведена. Уникальный идентификатор задачи ('. $data['id'] .')';
				$status_sent = true;
				$status_approved = false;
			break;
			case 'IN_PROGRESS' :
				$message = 'Процесс фискализации запущен. Уникальный идентификатор задачи ('. $data['id'] .')';
				$status_sent = true;
				$status_approved = false;
			break;
			case 'SUCCESS' :
				$message = 'Готово. Фискализация произведена. Уникальный идентификатор задачи ('. $data['id'] .')';
				$status_sent = true;
				$status_approved = false;
			break;
			case 'ERROR' :
				$message = 'Ошибка фискализации. Уникальный идентификатор задачи ('. $data['id'] .')';
				if(!empty($data['data']['error']['message'])){
					$message .= ' Код: '. $data['data']['error']['code'] .' \ Сообщение: '. $data['data']['error']['message'];
				}
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
		$answer["operation_id"] = $data['id'];
		$answer["status_sent"] = $status_sent;
		$answer["status_approved"] = $status_approved;
		$answer["message"] = $message;//Текстовое сообщение
		return $answer;
	}
	
	
	
	
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////	




	
	// Получение параметра - Тип оплаты.
	private function GET_payment_type($type)
	{
		//Тип оплаты
		switch($type){
			case '1' :
				$vat = 'CASH';//оплата наличными
			break;
			case '2' :
				$vat = 'CASHLESS';//оплата безналичными (по умолчанию)
			break;
			case '14' :
				$vat = 'PREPAID';//сумма предоплатой (зачет аванса и/или предыдущих платежей)
			break;
			case '15' :
				$vat = 'CREDIT';//сумма постоплатой (кредит)
			break;
			case '16' :
				$vat = 'CONSIDERATION';//сумма встречным предоставлением
			break;
			default :
				$vat = 'CASHLESS';//оплата безналичными (по умолчанию)
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
				$vat = 'NDS_20';//НДС 20%
			break;
			case '2' :
				$vat = 'NDS_10';//НДС 10%
			break;
			case '3' :
				$vat = 'NDS_20_CALCULATED';//НДС 20/120
			break;
			case '4' :
				$vat = 'NDS_10_CALCULATED';//НДС 10/110
			break;
			case '5' :
				$vat = 'NDS_0';//НДС 0%
			break;
			case '6' :
				$vat = 'NDS_NO_TAX';//НДС НЕ ОБЛАГАЕТСЯ
			break;
			default :
				$vat = 'NDS_20';//НДС 20%
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
		
		return $paymentMethodType;
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
			case '9' :
				$calculation_subject = 'intellectual_activity';//ПРЕДОСТАВЛЕНИЕ РИД
			break;
			case '10' :
				$calculation_subject = 'payment';//ПЛАТЕЖ
			break;
			case '11' :
				$calculation_subject = 'agent_commission';//АГЕНТСКОЕ ВОЗНАГРАЖДЕНИЕ
			break;
			case '12' :
				$calculation_subject = 'composite';//СОСТАВНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '13' :
				$calculation_subject = 'another';//ИНОЙ ПРЕДМЕТ РАСЧЕТА
			break;
			case '14' :
				$calculation_subject = 'property_right';//ИМУЩЕСТВЕННОЕ ПРАВО
			break;
			case '15' :
				$calculation_subject = 'non-operating_gain';//ВНЕРЕАЛИЗАЦИОННЫЙ ДОХОД
			break;
			case '16' :
				$calculation_subject = 'insurance_premium';//СТРАХОВЫЕ ВЗНОСЫ
			break;
			case '17' :
				$calculation_subject = 'sales_tax';//ТОРГОВЫЙ СБОР
			break;
			case '18' :
				$calculation_subject = 'resort_fee';//КУРОРТНЫЙ СБОР
			break;
			case '19' :
				$calculation_subject = 'commodity';//ЗАЛОГ - нет в системе поэтому поставим Товар
			break;
			default :
				$calculation_subject = 'commodity';//ТОВАР
			break;
		}
		
		return $paymentSubjectType;
	}
	
	
	
	
	
	
	// Получение параметра - Система налогообложения.
	private function GET_sno($sno)
	{
		//Предмет расчета
		switch($sno){
			case '0' :
				$sno = 'DEFAULT';//Общая - ОСН
			break;
			case '1' :
				$sno = 'SIMPLE';//Упрощенная доход - УСН доход
			break;
			case '2' :
				$sno = 'SIMPLE_WO';//Упрощенная доход минус расход - УСН доход - расход
			break;
			case '3' :
				$sno = 'ENVD';//Единый налог на вмененный доход - ЕНВД
			break;
			case '4' :
				$sno = 'AGRICULT';//Единый сельскохозяйственный налог - ЕСХН
			break;
			case '5' :
				$sno = 'PATENT';//Патентная система налогообложения - Патент
			break;
		}
		
		return $sno;
	}
}

// Для тестирования при разработке и вызове скрипта напрямую



//$f = fopen('log.txt', 'w');
//fwrite($f, json_encode($this->kkt_handler_options_values)."\n\n");
//fwrite($f, json_encode($check_object)."\n");
//exit;
/*
$kkt_handler_options_values = '';
$check_object = '';


$kkt_handler_options_values = json_decode($kkt_handler_options_values, true);
$check_object = json_decode($check_object, true);

echo '<pre style="max-height: 400px; overflow-y: auto;">';
var_dump($check_object);
echo '</pre>';
echo '<hr>';

$Dreamkas = new Dreamkas($kkt_handler_options_values, $check_object);//Создаем объект работы с ККТ передавая параметры подключения

$status_sent = $Dreamkas->status_sent;
$status_approved = $Dreamkas->status_approved;
$message = $Dreamkas->message;


echo '$status_sent = '. $status_sent;
echo '<br>';
echo '$status_approved = '. $status_approved;
echo '<br>';
echo '$message = '. $message;
echo '<br>';
*/


?>