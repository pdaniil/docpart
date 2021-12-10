<?php
//Реализация класса онлайн-кассы
/*
Новые кассовые аппараты добавляются в базу данных вручную - разработчиками.
Изначально, в платформе создается одна касса - "Основная".

-----------------------------------------------------------------

При создании объекта DocpartKKT, ему передается id из таблицы кассовых аппаратов и чек будет пробиваться имено через этот кассовый аппарат.

Стандартная логика работы сайта с онлайн-кассами:
Когда пользователь пробивает чек вручную, он выбирает кассу, на которой нужно пробить чек.
Если чек пробивается автоматически при оплате через эквайринг, то, выбирается касса с флагом "Для платежей через интернет-эквайринг".


Любая другая логика работы - не стандартная и требует персонального подхода со стороны разработчиков: это могут быть разные кассы для разных магазинов и многое другое.

-----------------------------------------------------------------


Чтобы пробить чек, нужно:
1. Создать экземпляр DocpartKKT
2. Вызвать метод DocpartKKT->create_check(), передав в него параметры чека
После этого, в базу сайта будет записан новый чек. Метод вернет его id.

Но, чек не будет отправлен в ОФД.

Чтобы отправить чек в ОФД - нужно вызвать метод

3. Затем, чтобы отправить чек в ОФД, нужно передать его в РЕАЛЬНУЮ КАССУ, которую нужно подключить к сайту.
Для передачи чека в реальную кассу, нужно вызвать метод DocpartKKT->send_check($check_id), передав в него id чека из своей таблицы.

-----------------------------------------------------------------
В исходном варианте платформы никакие РЕАЛЬНЫЕ кассы не подключены. Т.е. весь модуль с онлайн-кассами - это заготовка (оболочка) для последующего персонального подключения кассовых аппаратов для каждого сайта отдельно. Это обусловлено тем, что кассы бывают разными и по-разному подключаются технически к сайту.
Изначально на сайте:
- есть полностью графическая часть (т.е. можно просматривать виртуальную кассу "Основная", создавать чеки, смотреть чеки)
- созданные чеки никуда не отправляются
Чтобы подключить РЕАЛЬНУЮ кассу, нужно:
- реализовать скрипт в switch в методе send_check() для конкретного сервиса из таблицы shop_kkt_interfaces_types (если он еще не реализован), т.е. CURL-взаимодействие с реальным кассовым аппаратом.
- в скрите обработки уведомления о платеже от эквайринга - реализовать создание и отправку чека (создать объект DocpartKKT, вызвать методы create_check() и send_check() ), если для конкретной платежной системы еще не реализовано
- в shop_kkt_devices - указать handler и его настройки в JSON-формате



//Протокол от OrangeData:
https://github.com/orangedata-official/PHP-OrangeData-official

//Протокол от komtet.ru:
https://kassa.komtet.ru/integration/api

*/


require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


class DocpartKKT
{
	public $kkt_ready;//Флаг - объект ККТ готов к работе (т.е. после создания объекта, его можно использовать)
	public $kkt_ready_error_message;//Текст сообщения об ошибке (если после создания объекта, kkt_ready==false, то в kkt_ready_error_message будет указано, почему объект нельзя использовать)
	
	
	public $kkt_id;//ID кассового аппарата (из таблицы shop_kkt_devices)
	public $kkt_name;//Наименование ККТ (из таблицы shop_kkt_devices)
	
	public $kkt_handler_id;//ID технического интерфейса для взаимодействия с реальной кассой (id из таблицы shop_kkt_interfaces_types)
	public $kkt_handler_name;//Символьное обозначение технического интерфейса для взаимодействия с реальной кассой (из таблицы shop_kkt_devices и shop_kkt_interfaces_types)
	public $kkt_handler_options_values;//Настройки в JSON-формате, применяемые для взаимодействия с реальной кассой (из таблицы shop_kkt_devices)
	
	// ----------------------------------------------------------------------------
	public function __construct($kkt_id)
	{
		$this->kkt_ready = false;//По умолчанию
		
		$this->kkt_id = $kkt_id;//ID учетной записи, из которой нужно инициализировать объект
		
		$DP_Config = new DP_Config;//Конфигурация CMS
		//Подключение к БД
		try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			$this->kkt_ready_error_message = "Нет соединения с БД";
			return;
		}
		$db_link->query("SET NAMES utf8;");
		
		
		
		//Получаем все настройки для данной кассы из БД
		$kkt_config_query = $db_link->prepare("SELECT *, (SELECT `id` FROM `shop_kkt_interfaces_types` WHERE `handler` = `shop_kkt_devices`.`handler` LIMIT 1) AS `handler_id` FROM `shop_kkt_devices` WHERE `id` = ?;");
		$kkt_config_query->execute( array($this->kkt_id) );
		$kkt_config_record = $kkt_config_query->fetch();
		//Нет такой записи
		if($kkt_config_record == false)
		{
			$this->kkt_ready_error_message = "Учетная запись ККТ не найдена";
			return;
		}
		
		$this->kkt_name = $kkt_config_record["name"];
		$this->kkt_handler_id = $kkt_config_record["handler_id"];
		$this->kkt_handler_name = $kkt_config_record["handler"];
		$this->kkt_handler_options_values = json_decode($kkt_config_record["handler_options_values"], true);
		
		//Если к данной кассе не подключен реальный ККТ
		if( $this->kkt_handler_name == '' )
		{
			$this->kkt_ready_error_message = "Не подключен реальный кассовый аппарат";
			return;
		}
		
		//Объект ККТ успешно создан
		$this->kkt_ready = true;//Можно использовать
	}
	
	// ----------------------------------------------------------------------------
	
	//Метод создания ОБЫЧНОГО чека - запись его БД
	function create_check($check_object)
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
		
		
		
		
		//ДАЛЕЕ СОЗДАЕМ ЧЕК - ЧЕРЕЗ ТРАНЗАКЦИЮ
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			//Выполняем действия
			//1. Создаем учетную запись чека
			if( ! $db_link->prepare("INSERT INTO `shop_kkt_checks` (`kkt_device_id`, `type`, `customerContact`, `taxationSystem`, `time_created`, `sent_to_real_device_flag`, `real_device_approved_flag`, `is_correction_flag`) VALUES (?,?,?,?,?,?,?,?);")->execute( array((int)$this->kkt_id, (int)$check_object["tag_1054"]["value"], (string)$check_object["customer_contact"], (int)$check_object["tag_1055"]["value"], time(), 0, 0, 0) ) )
			{
				throw new Exception("Ошибка создания учетной записи чека");
			}
			$check_id = $db_link->lastInsertId();
			if( (int)$check_id <= 0 )
			{
				throw new Exception("ID чека не получен");
			}
			
			
			//2. Добавляем товарные позиции
			if( is_array($check_object["products"]) )
			{
				if( count($check_object["products"]) > 0 )
				{
					for( $i=0 ; $i < count($check_object["products"]) ; $i++ )
					{
						$product = $check_object["products"][$i];
						
						if( ! $db_link->prepare("INSERT INTO `shop_kkt_checks_products` (`check_id`, `count`, `price`, `tax`, `text`, `paymentMethodType`, `paymentSubjectType`) VALUES (?,?,?,?,?,?,?);")->execute( array($check_id, $product["count"], $product["price"], $product["tag_1199"], $product["name"], $product["tag_1214"], $product["tag_1212"]) ) )
						{
							throw new Exception("Ошибка добавления товарной позиции чека");
						}
						else//Позиция чека записана в БД
						{
							//Проверяем, была ли она взята из заказа. Если да, то делаем привязку позиции чека с позицией заказа
							if( $product["order_item_id"] > 0 )
							{
								$order_item_id = $product["order_item_id"];
								$check_product_id = $db_link->lastInsertId();
								if( (int)$check_product_id <= 0 )
								{
									throw new Exception("ID позиции чека не получен");
								}
								
								if( ! $db_link->prepare("INSERT INTO `shop_kkt_checks_products_to_orders_items_map` (`check_product_id`,`order_item_id`) VALUES (?,?);")->execute( array($check_product_id, $order_item_id) ) )
								{
									throw new Exception("Ошибка добавления связи позиции чека с позицией заказа");
								}
							}
						}
					}
				}
				else
				{
					throw new Exception("В объекте массив с товарными позициями пуст");
				}
			}
			else
			{
				throw new Exception("В объекте чека не найден массив с товарными позициями");
			}
			
			
			
			//3. Добавляем платежи по чеку
			if( is_array($check_object["payments"]) )
			{
				if( count($check_object["payments"]) > 0 )
				{
					for( $i=0 ; $i < count($check_object["payments"]) ; $i++ )
					{
						$payment = $check_object["payments"][$i];
						
						if( ! $db_link->prepare("INSERT INTO `shop_kkt_checks_payments` (`check_id`, `type`, `amount`) VALUES (?,?,?);")->execute( array($check_id, $payment["type_tag"], $payment["amount"]) ) )
						{
							throw new Exception("Ошибка добавления платежа для чека");
						}
					}
				}
				else
				{
					throw new Exception("В объекте массив с платежами пуст");
				}
			}
			else
			{
				throw new Exception("В объекте чека не найден массив с платежами");
			}
			
		}
		catch (Exception $e)
		{
			//Откатываем все изменения
			$db_link->rollBack();
			
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = "Причина: ". $e->getMessage();//Текстовое сообщение
			return $answer;
		}

		//Дошли до сюда, значит выполнено ОК
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		
		
		$answer = array();
		$answer["status"] = true;
		$answer["check_id"] = $check_id;
		return $answer;
		
		
		/*
		//Здесь создаем чек, т.е. записываем его в базу данных
		$check_object["type"];//(ЕСТЬ ТАБЛИЦА 1054) Тип операциия (1 - Приход, 2 - Возврат прихода, 3 - Расход, 4 - Возврат расхода)
		$check_object["customerContact"];//Контакт покупателя (email)
		$check_object["taxationSystem"];//(ЕСТЬ ТАБЛИЦА 1055) Система налогообложения (0 - Общая, ОСН, 1 - Упрощенная доход, УСН доход, 2 - Упрощенная доход минус расход, УСН доход - расход, 3 - Единый налог на вмененный доход, ЕНВД, 4 - Единый сельскохозяйственный налог, ЕСН, 5 - Патентная система налогообложения, Патент)
		
		
		$check_object["products"];//Массив с позициями товаров
		$check_object["products"][0]["count"]//Количество предмета расчета
		
		$check_object["products"][0]["price"]//Цена за единицу предмета расчета
		
		$check_object["products"][0]["tax"]//(ЕСТЬ ТАБЛИЦА 1199) Ставка НДС для указанной позиции (1 - ставка НДС 20%, 2 - ставка НДС 10%, 3 - ставка НДС расч. 20/120, 4 - ставка НДС расч. 10/110, 5 - ставка НДС 0%, 6 - НДС не облагается)
		
		$check_object["products"][0]["text"]//Наименование предмета расчета
		
		$check_object["products"][0]["paymentMethodType"]//(ЕСТЬ ТАБЛИЦА 1214) Признак способа расчета (1 - Предоплата 100%, 2 - Частичная предоплата, 3 - Аванс, 4 - Полный расчет, 5 - Частичный расчет и кредит, 6 - Передача в кредит, 7 - оплата кредита)
		
		$check_object["products"][0]["paymentSubjectType"]//(ЕСТЬ ТАБЛИЦА 1212) Признак предмета расчета (1 - Товар, 2 - Подакцизный товар, 3 - Работа, 4 - Услуга)
		
		
		//Добавление оплаты в чек:
		$check_object["payment"][0]["type"]//(ЕСТЬ ТАБЛИЦА shop_kkt_ref_payment_types_tags) Тип оплаты (1 - сумма по чеку наличными, 1031; 2 - сумма по чеку безналичными, 1081; 14 - сумма по чеку предоплатой (зачетом аванса и (или) предыдущих платежей), 1215; 15 - сумма по чеку постоплатой (в кредит), 1216; 16 - сумма по чеку (БСО) встречным предоставлением, 1217)
		$check_object["payment"][0]["amount"]//Сумма
		*/
	}
	
	// ----------------------------------------------------------------------------
	
	//Создание чека коррекции
	function create_check_of_correction($check_object)
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
		
		
		
		
		
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			//Выполняем действия
			//Создаем учетную запись чека КОРРЕКЦИИ
			if( ! $db_link->prepare("INSERT INTO `shop_kkt_checks` (`kkt_device_id`, `type`, `taxationSystem`, `time_created`, `sent_to_real_device_flag`, `real_device_approved_flag`, `is_correction_flag`, `correction_type`, `correction_description`, `correction_causeDocumentDate`, `correction_causeDocumentNumber`, `correction_payment_type`, `correction_totalSum`, `correction_cashSum`, `correction_eCashSum`, `correction_prepaymentSum`, `correction_postpaymentSum`, `correction_otherPaymentTypeSum`, `correction_tax`, `correction_tax1Sum`, `correction_tax2Sum`, `correction_tax3Sum`, `correction_tax4Sum`, `correction_tax5Sum`, `correction_tax6Sum`, `correction_printer_number`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);")->execute( array((int)$this->kkt_id, (int)$check_object["tag_1054"]["value"], (int)$check_object["tag_1055"]["value"], time(), 0, 0, 1, (int)$check_object["correction_type"]["value"], $check_object["correction_description"], (int)$check_object["correction_causeDocumentDate"]["value"],$check_object["correction_causeDocumentNumber"], $check_object["payment"]['id'], $check_object["correction_totalSum"], $check_object["correction_cashSum"], $check_object["correction_eCashSum"], $check_object["correction_prepaymentSum"], $check_object["correction_postpaymentSum"], $check_object["correction_otherPaymentTypeSum"], $check_object["tag_1199"]['id'], $check_object["correction_tax1Sum"], $check_object["correction_tax2Sum"], $check_object["correction_tax3Sum"], $check_object["correction_tax4Sum"], $check_object["correction_tax5Sum"], $check_object["correction_tax6Sum"], $check_object["printer_number"]) ) )
			{
				throw new Exception("Ошибка создания учетной записи чека КОРРЕКЦИИ");
			}
			$check_id = $db_link->lastInsertId();
			if( (int)$check_id <= 0 )
			{
				throw new Exception("ID чека КОРРЕКЦИИ не получен");
			}
		}
		catch (Exception $e)
		{
			//Откатываем все изменения
			$db_link->rollBack();
			
			$answer = array();
			$answer["status"] = false;
			$answer["message"] = "Причина: ". $e->getMessage();//Текстовое сообщение
			return $answer;
		}
		
		
		
		//Дошли до сюда, значит выполнено ОК
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		
		
		$answer = array();
		$answer["status"] = true;
		$answer["check_id"] = $check_id;
		return $answer;
		
		
		
		
		/*
		$correction = [
		  'correctionType' => 0,//(ЕСТЬ ТАБЛИЦА 1173) Тип коррекции 1173 (0. Самостоятельно, 1. По предписанию)
		  
		  'type' => 1,//(ЕСТЬ ТАБЛИЦА 1054) Признак расчета, 1054 (1. Приход, 3. Расход)
		  
		  'description' => 'cashier error',//Описание коррекции (cтрока от 1 до 243 символов. )
		  
		  'causeDocumentDate' => new \DateTime(),//Дата документа основания для коррекции (время в виде строки в формате ISO8601)
		  
		  'causeDocumentNumber' => '56ce',//Номер документа основания для коррекции (строка от 1 до 32 символов)
		  
		  'totalSum' => 567.9,//Сумма расчета, указанного в чеке (десятичное число)
		  
		  'cashSum' => 567,//Сумма по чеку (БСО) наличными (десятичное число)
		  
		  'eCashSum' => 0.9,//Сумма по чеку (БСО) безналичными (десятичное число)
		  
		  'prepaymentSum' => 0,//Сумма по чеку (БСО) предоплатой (зачетом аванса и (или) предыдущих платежей) (десятичное число)
		  
		  'postpaymentSum' => 0,//Сумма по чеку (БСО) постоплатой (в кредит) (десятичное число)
		  
		  'otherPaymentTypeSum' => 0,//Сумма по чеку (БСО) встречным предоставлением (десятичное число)
		  
		  'tax1Sum' => 0,//Сумма НДС чека по ставке 20% (десятичное число)
		  'tax2Sum' => 0,//Сумма НДС чека по ставке 10% (десятичное число)
		  'tax3Sum' => 0,//Сумма расчета по чеку с НДС по ставке 0% (десятичное число)
		  'tax4Sum' => 0,//Сумма расчета по чеку без НДС (десятичное число)
		  'tax5Sum' => 0,//Сумма НДС чека по расч. ставке 20/120 (десятичное число)
		  'tax6Sum' => 0,//Сумма НДС чека по расч. ставке 10/110 (десятичное число)
		  
		  'taxationSystem' => 2,//СНО
		];
		*/
	}
	
	// ----------------------------------------------------------------------------
	
	//Метод отправки запроса в реальную кассу
	function send_check($check_id, $initiator)
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
			$answer["status_sent"] = false;//Флаг - чек отправлен в реальную ККТ
			$answer["status_approved"] = false;//Флаг - от реальной ККТ получено подтверждение (принят)
			$answer["message"] = 'Не удалось подключиться к локальной базе данных';//Текстовое сообщение
			return $answer;
		}
		$db_link->query("SET NAMES utf8;");
		
		
		
		
		//Получаем данные чека
		$check_object = array();
		$check_query = $db_link->prepare("SELECT * FROM `shop_kkt_checks` WHERE `id` = ?;");
		$check_query->execute( array($check_id) );
		$check_record = $check_query->fetch();
		if( $check_record == false )
		{
			$answer = array();
			$answer["status_sent"] = false;//Флаг - чек отправлен в реальную ККТ
			$answer["status_approved"] = false;//Флаг - от реальной ККТ получено подтверждение (принят)
			$answer["message"] = 'Учетная запись чека не найдена в локальной БД';//Текстовое сообщение
			return $answer;
		}
		$check_object = $check_record;
		$check_object["initiator"] = $initiator;
		$check_object["products"] = array();
		$check_object["payments"] = array();
		
		//Получаем товарные позиции
		$products_query = $db_link->prepare("SELECT * FROM `shop_kkt_checks_products` WHERE `check_id` = ?;");
		$products_query->execute( array($check_id) );
		while( $product = $products_query->fetch() )
		{
			$check_object["products"][] = $product;
		}
		
		//Получаем платежи
		$payments_query = $db_link->prepare("SELECT * FROM `shop_kkt_checks_payments` WHERE `check_id` = ?;");
		$payments_query->execute( array($check_id) );
		while( $payment = $payments_query->fetch() )
		{
			$check_object["payments"][] = $payment;
		}
		
		//Все данные чека получены. Далее уже их нужно будет адаптировать под протокол конкретной реальной ККТ
		
		
		//1. отправка curl-запроса
		//здесь идет реализация для конкретного сервиса или конкретной ККТ, которая есть у пользователя (orangedata, Дримкас-Ф и т.д.)
		//В зависимости от типа технического интерфейса, выбираем конкретный скрипт для взаимодействия с реальной кассой.
		//Результат отправки запроса необходимо записать в универсальную структуру - так, чтобы ее обработка следовала сразу после этого switch
		
		
		
		//$status_sent - "API реальной ККТ был вызван и от него получен структурированный ответ. В этом ответе можеть указано, например, что 'чек принят', 'нет связи с кассой', 'переполнен фискальный накопитель' и т.д. - Это вся зависит от протокола конкретной реальной ККТ. Если этот ответ явно означает, что чек не принят, то в колонку shop_kkt_checks.real_device_text_answer записывается текст причины и тогда $status_approved=0. Если чек принят, то, real_device_text_answer = '', а $status_approved=1". Если по какой-то причине не удалось вызвать API реальной ККТ (нет соединения или ответ не удалось распарсить), то, оба флага $status_sent и $status_approved будут равны 0.
		//$status_approved - "От API реальной кассы получено структурированное подтверждение того, что чек принят. Т.е. можно с гарантией указать пользователю, что чек отправден в ОФД."
		
		
		switch($this->kkt_handler_name)
		{
			case 'orangedata':
				$status_sent = false;
				$status_approved = false;
				$message = 'API orangedata не реализовано';
				break;
			case 'komtet':
				
				//Подключаем класс ККТ
				require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/handlers/komtet/komtet.php");
				$Komtet = new Komtet($this->kkt_handler_options_values, $check_object);//Создаем объект работы с ККТ передавая параметры подключения
				
				$status_sent = $Komtet->status_sent;
				$status_approved = $Komtet->status_approved;
				$message = $Komtet->message;
				
				break;
			case 'initpro':
				
				//Подключаем класс ККТ
				require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/handlers/initpro/initpro.php");
				$Initpro = new Initpro($this->kkt_handler_options_values, $check_object);//Создаем объект работы с ККТ передавая параметры подключения
				
				$status_sent = $Initpro->status_sent;
				$status_approved = $Initpro->status_approved;
				$message = $Initpro->message;
				
				break;
			case 'dreamkas':
				
				//Подключаем класс ККТ
				require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/handlers/dreamkas/dreamkas.php");
				$Dreamkas = new Dreamkas($this->kkt_handler_options_values, $check_object);//Создаем объект работы с ККТ передавая параметры подключения
				
				$status_sent = $Dreamkas->status_sent;
				$status_approved = $Dreamkas->status_approved;
				$message = $Dreamkas->message;
				break;
			case 'docpart_emulator':
				$status_sent = true;
				$status_approved = true;
				$message = '';
				break;
		}
		
		
		
		//2. Запись в БД результата оправки
		if( $db_link->prepare("UPDATE `shop_kkt_checks` SET `sent_to_real_device_flag` = ?, `real_device_approved_flag` = ?, `real_device_text_answer` = ? WHERE `id` = ?;")->execute( array($status_sent, $status_approved, $message, $check_id) ) )
		{
			//Результат отправки чека в реальную ККТ
			$answer = array();
			$answer["status_sent"] = $status_sent;//Флаг - чек отправлен в реальную ККТ
			$answer["status_approved"] = $status_approved;//Флаг - от реальной ККТ получено подтверждение (принят)
			$answer["message"] = $message;//Текстовое сообщение
			return $answer;
		}
		else
		{
			//Результат отправки чека в реальную ККТ
			$answer = array();
			$answer["status_sent"] = $status_sent;//Флаг - чек отправлен в реальную ККТ
			$answer["status_approved"] = $status_approved;//Флаг - от реальной ККТ получено подтверждение (принят)
			
			$status_sent_text = " Отправлено в реальную ККТ - НЕТ";
			if( $status_sent == true )
			{
				$status_sent_text = " Отправлено в реальную ККТ - ДА";
			}
			$status_approved_text = " Получено подтверждение о приеме от реальной ККТ - НЕТ";
			if( $status_approved == true )
			{
				$status_approved_text = " Получено подтверждение о приеме от реальной ККТ - ДА";
			}
			
			$answer["message"] = 'Не удалось записать результат отправки в локальную БД. '.$status_sent_text.$status_approved_text;//Текстовое сообщение
			return $answer;
		}
	}
	
	// ----------------------------------------------------------------------------
}

?>