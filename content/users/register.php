<?php
/**
 * Страничный и технический скрип
 * 
 * Сюда пользователя попадает сразу после формы регистрации
 * 
 * Действия:
 * 1. Создание учетной записи пользователя
 * 2. Создание профиля пользователя
 * 3. Привязка пользователя к группе регистрации
 * 4. Отправка ссылки активации
 * 5. Вывод страницы с сообщением
*/
defined('_ASTEXE_') or die('No access');

//Класс пользователя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Все делаем через транзакцию
try
{
	// -------------------------------------------------------------------------------------------
	
	//Проверка, что пользователь не авторизован
	if(DP_User::getUserId() != 0)
	{
		throw new Exception("Вы уже зарегистрированы");
	}
	
	// -------------------------------------------------------------------------------------------
	
	//Старт транзакции
	if( ! $db_link->beginTransaction()  )
	{
		throw new Exception("Не удалось стартовать транзакцию");
	}
	
	// -------------------------------------------------------------------------------------------
	
	//1. CAPTCHA
	if( md5( $_POST['capcha_input'] ) != $_COOKIE["captcha"] )
	{
		throw new Exception("Неправильная капча");
	}
	
	// -------------------------------------------------------------------------------------------
	
	//2. Пользовательское соглашение
	if( $_COOKIE["users_agreement"] != "yes" )
	{
		throw new Exception("Регистрация отменена. Для регистрации необходимо принять Пользовательское соглашение");
	}
	
	// -------------------------------------------------------------------------------------------

	//3. ПРОВЕРКА reg_contact (уникальность и корректность)
	//Входные данные
	$reg_contact = $_POST["reg_contact"];
	$reg_contact_type = $_POST["reg_contact_type"];
	//Имя колонки, в которой ищем контакт:
	$col_name = 'email';//По-умолчанию - email
	if( $reg_contact_type == 'phone' )
	{
		$col_name = 'phone';//Будем искать в колонке "Телефон"
	}
	else if( $reg_contact_type != 'email' )
	{
		//Значит было передано некорректное значение reg_contact_type
		throw new Exception("Ошибка 1.1");
	}
	//Проверяем корректность контакта
	//Получаем регулярное выражение для контакта
	$regexp_query = $db_link->prepare("SELECT `regexp` FROM `reg_fields` WHERE `name` = ?;");
	$regexp_query->execute( array($reg_contact_type) );
	$regexp = $regexp_query->fetchColumn();
	preg_match("/".$regexp."/", $reg_contact, $matches);
	$regexp_ok = true;
	if($regexp != '') {
		if( count($matches) == 1 )
		{
			if( $matches[0] != $reg_contact )
			{
				$regexp_ok = false;
			}
		}
		else
		{
			$regexp_ok = false;
		}
	}
	if( !$regexp_ok )
	{
		throw new Exception("Ошибка 1.2");
	}
	//Проверяем уникальность контакта
	$contact_check_query = $db_link->prepare('SELECT COUNT(*) FROM `users` WHERE `'.$col_name.'`= ?;');//У col_name - безопасное значение
	$contact_check_query->execute( array(htmlentities($reg_contact)) );
	$contact_count_rows = $contact_check_query->fetchColumn();
	if( $contact_count_rows != 0)
	{
		throw new Exception("Ошибка 1.3");
	}

	// -------------------------------------------------------------------------------------------

	//4. Проверка IP-адреса - предотвращения регистраций роботами и хулиганами - блокируем ну сутки
	$ip = $_SERVER["REMOTE_ADDR"];
	if($ip == "" || $ip == NULL)
	{
		throw new Exception("Ошибка 2.1");
	}
	$time_day = time() - 86400;//Сутки назад

	$ip_query = $db_link->prepare('SELECT COUNT(*) FROM `users` WHERE `ip_address` = ? AND `time_registered` > ? AND `email_confirmed` = ? AND `phone_confirmed` = ?;');
	$ip_query->execute( array($ip, $time_day, 0, 0) );
	if( $ip_query->fetchColumn() > 0 )
	{
		throw new Exception("Попробуйте зарегистрироваться позже");
	}
	// -------------------------------------------------------------------------------------------

	//5. ДОБАВЛЕНИЕ ЗАПИСИ В ТАБЛИЦУ users
	if( $reg_contact_type == "email" )
	{
		$activation_code = md5(md5($reg_contact).md5($DP_Config->secret_succession));//Код активации
	}
	else
	{
		$activation_code = rand(100000,999999);
	}

	//Проверка подмены reg_variant
	$check_reg_variant = $db_link->prepare("SELECT COUNT(*) FROM `reg_variants` WHERE `id` = ?;");
	$check_reg_variant->execute( array($_POST["reg_variant"]) );
	if($check_reg_variant->fetchColumn() != 1)
	{
		throw new Exception("Ошибка 5.1");
	}

	if( $db_link->prepare('INSERT INTO `users` (`'.$col_name.'`, `reg_variant`, `password`, `'.$col_name.'_code`, `time_registered`, `'.$col_name.'_code_expired`, `unlocked`) VALUES (?, ?, ?, ?, ?, ?, ?);')->execute( array(htmlentities($reg_contact), $_POST["reg_variant"], md5($_POST['password'].$DP_Config->secret_succession), $activation_code, time(), time()+1800, 1 ) ) != true)
	{
		throw new Exception("Ошибка создания учетной записи пользователя");
	}
	else//Запись добавлена - узнаем user_id добавленного пользователя
	{
		$user_id = $db_link->lastInsertId();
	}
	
	// -------------------------------------------------------------------------------------------
	

	//6. ДОБАВЛЕНИЕ ЗАПИСЕЙ В ТАБЛИЦУ users_profiles
	//Получаем дополнительные регистрационные поля
	$reg_fields_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `main_flag` = 0;');
	$reg_fields_query->execute();
	while( $reg_field_record = $reg_fields_query->fetch() )
	{
		$show_for = json_decode($reg_field_record["show_for"], true);
		
		//Есть ли данное поле в этом Регистрационном Варианте показано
		if(array_search($_POST["reg_variant"], $show_for) !== false)
		{
			if( $db_link->prepare('INSERT INTO `users_profiles` (`user_id`, `data_key`, `data_value`) VALUES (?, ?, ?);')->execute( array($user_id, $reg_field_record["name"], htmlentities($_POST[$reg_field_record["name"]])) ) != true)
			{
				throw new Exception("Ошибка записи профиля пользователя");
			}
		}
	}
	
	// -------------------------------------------------------------------------------------------


	//7. ПРИВЯЗКА ПОЛЬЗОВАТЕЛЯ К ГРУППЕ РЕГИСТРАЦИИ
	$for_registrated_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_registrated` = 1;');
	$for_registrated_group_query->execute();
	$for_registrated_group_record = $for_registrated_group_query->fetch();
	if( $db_link->prepare('INSERT INTO `users_groups_bind` (`user_id`, `group_id`) VALUES (?, ?);')->execute( array($user_id, $for_registrated_group_record["id"]) ) != true)
	{
		throw new Exception("Ошибка добавления пользователя в группу");
	}

	// -------------------------------------------------------------------------------------------
	
	//8. ОТПРАВКА ССЫЛКИ/КОДА АКТИВАЦИИ КЛИЕНТУ

	if( $reg_contact_type == "email" )
	{
		$notify_name = 'reg_email_confirm';//Тип уведомления - "Подтверждение e-mail"
		
		//Массив получателей в соответствии с API скрипта уведомлений
		$persons = array(
			array(
				'type'=>'direct_contact',
				'contacts'=>array(
						'email'=>array('value'=>$reg_contact),
						'phone'=>array('value'=>'')
					)
				) 
		);
		
		//Переменные для шаблонов уведомления
		$notify_vars = array();
		$notify_vars["site_name"] = $DP_Config->site_name;//Название сайта 
		$notify_vars["email_confirm_href"] = "<a target='_blank' href='".$DP_Config->domain_path."users/confirm_contact?code=$activation_code&u_id=$user_id&type=email'>Нажмите на эту ссылку для подтверждения E-mail</a>";//Ссылка для подтверждения E-mail
	}
	else
	{
		$notify_name = 'reg_phone_confirm';//Тип уведомления - "Подтверждение телефона"
		
		//Массив получателей в соответствии с API скрипта уведомлений
		$persons = array(
			array(
				'type'=>'direct_contact',
				'contacts'=>array(
						'email'=>array('value'=>''),
						'phone'=>array('value'=>$reg_contact)
					)
				) 
		);
		
		//Переменные для шаблонов уведомления
		$notify_vars = array();
		$notify_vars["phone_confirm_code"] = $activation_code;
	}
	//Отправка уведомления по общий интерфейс
	$postdata = http_build_query(
		array(
			'check' => $DP_Config->secret_succession,
			'name' => $notify_name,
			'vars' => json_encode($notify_vars),
			'persons' => json_encode($persons)
		)
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/notifications/send_notify.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	$curl_result = json_decode($curl_result, true);
	if($curl_result["status"] == false)
	{
		//Причина - некорректные данные, нет прав на запуск и т.д.
		throw new Exception("Ошибка 1 отправки сообщения для подтверждения контакта");
	}
	else
	{
		//Скрипт отправки выдал статус true, теперь НЕОБХОДИМО проверить статус отправки по конкретному контакту
		//Мы указывали единственный контакт, поэтому его и проверяем.
		if( ! $curl_result["persons"][0]['contacts'][$reg_contact_type]['status'] )
		{
			throw new Exception("Ошибка 2 отправки сообщения для подтверждения контакта");
		}
	}
	
	// -------------------------------------------------------------------------------------------
	
	//9. ОТПРАВКА УВЕДОМЛЕНИЯ МЕНЕДЖЕРУ


	$persons = array();//Массив получателей
	$SQL_ids_admins = "SELECT `user_id`
	FROM `users` 
	WHERE `user_id` IN 
	(SELECT `user_id` FROM `users_groups_bind` WHERE `group_id` IN (SELECT `id` FROM `groups` WHERE `for_backend` = 1 OR `parent` = (SELECT `id` FROM `groups` WHERE `for_backend` = 1)) );";
	$ids_admins_query = $db_link->prepare($SQL_ids_admins);
	$ids_admins_query->execute();
	while( $id_admin_rec = $ids_admins_query->fetch() )
	{
		$persons[] = array( 'type'=>'user_id', 'user_id'=>$id_admin_rec["user_id"] );
	}
	//Массив с переменными для уведомления:
	$notify_vars = array();
	$notify_vars["user_id"] = $user_id;//ID зарегистрированного пользователя
	
	//Отправка уведомления по общий интерфейс
	$postdata = http_build_query(
		array(
			'check' => $DP_Config->secret_succession,
			'name' => 'reg_notify_admin',
			'vars' => json_encode($notify_vars),
			'persons' => json_encode($persons)
		)
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/notifications/send_notify.php");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	$curl_result = curl_exec($curl);
	curl_close($curl);
	$curl_result = json_decode($curl_result, true);
	if($curl_result["status"] == false)
	{
		//Ошибка отправки уведомления менеджеру
		throw new Exception("Ошибка 1 уведомления менеджера");
	}
	else
	{
		//Скрипт отправки выдал статус true. Теперь НЕОБХОДИМО проверить статус отправки по всем получателям.
		//Проверяем, всем ли менеджерам отправлено без ошибок. Счетитаем, что нет ошибки, если по одному получателю отправлено хотя бы либо на телефон, либо на email.
		for( $i=0 ; $i<count($curl_result["persons"]) ; $i++)
		{
			if( $curl_result["persons"][$i]['contacts']['email']['status'] == false && $curl_result["persons"][$i]['contacts']['phone']['status'] == false )
			{
				// throw new Exception("Ошибка 2 уведомления менеджера");
			}
		}
	}
}
catch (Exception $e)
{
	//Откатываем все изменения
	$db_link->rollBack();
	
	//Текст ошибки
	$error_message = $e->getMessage();
	?>
	<script>
		location="/?error_message=<?php echo urlencode($error_message); ?>";
	</script>
	<?php
	exit();
}

//Дошли до сюда, значит выполнено ОК
$db_link->commit();//Коммитим все изменения и закрываем транзакцию


//Сообщение для пользователя, после регистрации
if( $reg_contact_type == "email" )
{
	echo "На указанный E-mail отправлено письмо с ссылкой. Перейдите по ссылке в письме, чтобы подтвердить свой E-mail. После этого Вы сможете зайти на сайт, используя в качестве логина свой E-mail.";
}
else
{
	?>
	Код подтверждения телефона отправлен в SMS. Введите этот код в форму:
	<form method="GET" action="/users/confirm_contact">
		<input type="hidden" name="u_id" value="<?php echo $user_id; ?>" />
		<input type="hidden" name="type" value="phone" />
		<div class="form-group">
			<label for="" class="col-sm-2 control-label">Введите код подтверждения из SMS</label>
			<div class="col-sm-6" style="padding:5px;">
			  <input type="text" class="form-control" name="code" id="code" value="" placeholder="Введите код подтверждения из SMS">
			</div>
			<div class="col-sm-4" style="padding:5px;">
				<button type="submit">Подтвердить</button>
			</div>
		</div>
	</form>
	<?php
}
?>