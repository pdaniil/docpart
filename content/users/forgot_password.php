<?php
/**
 * Скрипт для страницы восстановления пароля
*/
defined('_ASTEXE_') or die('No access');


require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

//Почтовый обработчик
//require_once($_SERVER["DOCUMENT_ROOT"]."/lib/DocpartMailer/docpart_mailer.php");


//Пользователь авторизован
if($user_id != 0)
{
	?>
	<script>
		location="<?php echo $DP_Config->domain_path; ?>";
	</script>
	<?php
    exit;
}

//Если получен POST-параметр forgot_password_contact - значит идет запрос на восстановление пароля
if( isset( $_POST["forgot_password_contact"] ) )
{
	try
	{
		//Проверка поля "тип контакта"
		if( !isset( $_POST['forgot_password_contact_type'] ) )
		{
			throw new Exception("Получены не корректные данные");
		}
		$type = $_POST['forgot_password_contact_type'];
		//$type используется в SQL-запросах. Проверяем значение.
		if( $type != 'email' && $type != 'phone' )
		{
			throw new Exception("Получены не корректные данные");
		}
		
		
		//Теперь ищем пользователя по указанному контакту. Контакт должен быть подтвержденным
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `'.$type.'` = ? AND `'.$type.'_confirmed` = ?;');
		$user_query->execute( array($_POST["forgot_password_contact"], 1) );
		$user_record = $user_query->fetch();
		if( $user_record == false )
		{
			throw new Exception("Пользователь не найден");
		}
		//Есть $user_record['user_id']
		
		
		//Чтобы исключить хулиганские запросы (чтобы не израсходовать сразу все деньги с баланса у SMS-оператора), делаем ограничение по запросам на восстановление пароля - не чаще 1 раза в 5 минут
		if( $user_record[$type."_code_send_lock_expired"] > time() )
		{
			throw new Exception("Установлено ограничение на отправку запросов. Отправьте запрос через 5 минут");
		}
		
		
		
		//В зависимости от типа контакта: формируем код, определяем тип уведомления и формируем значения переменных для данного уведомления
		if( $type == "email" )
		{
			$forgot_password_code = md5(md5($DP_Config->secret_succession.$user_record['time_registered'].$user_record['time_last_visit']).time().rand(10000,99999));
			
			$notify_name = 'forgot_password_by_email';//Тип уведомления
			
			
			//Переменные для шаблонов уведомления
			$notify_vars = array();
			$notify_vars["site_name"] = $DP_Config->site_name;//Название сайта 
			$notify_vars["forgot_password_code_href"] = "<a target='_blank' href='".$DP_Config->domain_path."users/new_password?code=$forgot_password_code&type=$type&contact=".$_POST["forgot_password_contact"]."'>Нажмите на эту ссылку для получения нового пароля</a>";
		}
		else//Восставновление пароля по телефону
		{
			$forgot_password_code = rand(10000,99999);
			
			$notify_name = 'forgot_password_by_phone';//Тип уведомления
			
			
			//Переменные для шаблонов уведомления
			$notify_vars = array();
			$notify_vars["forgot_password_code"] = $forgot_password_code;
		}
		$persons = array();//Массив получателей
		$persons[] = array( 'type'=>'user_id', 'user_id'=>$user_record['user_id'] );
		
		
		//Ставим время в таблицу БД и код защиты восстановления пароля. Также ставим время, до которого новые запросы будут отбрасываться
		$code_send_lock_expired = time()+300;//Ставим на 5 минут блокировку отправки новых сообщений
		if( ! $db_link->prepare('UPDATE `users` SET `forgot_password_time` = ?, `forgot_password_code` = ?, `'.$type.'_code_send_lock_expired` = ? WHERE `user_id` = ?;')->execute( array(time(), $forgot_password_code, $code_send_lock_expired, $user_record['user_id']) ) )
		{
			throw new Exception("Ошибка");
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
			throw new Exception("Ошибка 1 отправки сообщения");
		}
		else
		{
			//Скрипт отправки выдал статус true, теперь НЕОБХОДИМО проверить статус отправки по конкретному контакту
			//Мы указывали единственный контакт, поэтому его и проверяем.
			if( ! $curl_result["persons"][0]['contacts'][$type]['status'] )
			{
				throw new Exception("Ошибка 2 отправки сообщения");
			}
		}
		
		
		if( $type == "email" )
		{
			echo "На указанный e-mail отправлена ссылка для активации нового пароля";
		}
		else
		{
			?>
			<div class="col-lg-12">
				На указанный телефон отправлено сообщение с кодом восстановления пароля. Введите этот код в форму восстановления пароля ниже
			</div>
			<form method="GET" action="/users/new_password">
				<input type="hidden" name="contact" value="<?php echo $_POST["forgot_password_contact"]; ?>" />
				<input type="hidden" name="type" value="phone" />
				<div class="form-group">
					<label for="" class="col-sm-2 control-label">Введите код из SMS</label>
					<div class="col-sm-6" style="padding:5px;">
						<input type="text" class="form-control" name="code" id="code" value="" placeholder="Введите код из SMS">
					</div>
					<div class="col-sm-4" style="padding:5px;">
						<button type="submit">Подтвердить</button>
					</div>
				</div>
			</form>
			<?php
		}
	}
	catch (Exception $e)
	{
		//Можно получить текст ошибки из throw: $e->getMessage()
		echo $e->getMessage();
	}
}
else//Действий нет - выводим страницу
{
    ?>
    <?php
        require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	<form method="POST" onsubmit="return onSubmitCheck();">
	
		<?php
		//Доступные способы связи
		$display_forgot_password_contact_select = ' style="display:none;" ';//Для видимости селектора - по-умолчанию не видимый
		$forgot_password_contact_select_options = '<option value="phone">Телефон</option> <option value="email">E-mail</option>';//Набор опций
		$available_communications = DP_User::available_communications();//Получаем доступные способы связи
		if( $available_communications["all"] )
		{
			$display_forgot_password_contact_select = "";//Селектор делаем видимым, чтобы клиент смог сам выбрать нужный вид контакта
		}
		else if( $available_communications["sms"] )
		{
			$forgot_password_contact_select_options = '<option value="phone">Телефон</option>';//Оставляем только телефон
		}
		else
		{
			$forgot_password_contact_select_options = '<option value="email">E-mail</option>';//Оставляем только E-mail
		}
		?>
		
		<!-- Селектор контакта для восстановления пароля -->
		<div class="input-group login-input" <?php echo $display_forgot_password_contact_select; ?>>
			<span class="input-group-addon">Восстановить через</span>
			
			<select name="forgot_password_contact_type" class="form-control" id="forgot_password_contact_select" onchange="on_forgot_password_contact_select_changed();" style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;">
					<?php echo $forgot_password_contact_select_options; ?>
			</select>
		</div>
		<?php
		//Добавляем перенос после видимого селектора
		if( $display_forgot_password_contact_select == '' )
		{
			?>
			<br/>
			<?php
		}
		?>
		<!-- Поле для контакта -->
		<div class="input-group login-input">
			<span class="input-group-addon"><i id="contact_type_icon_forgot_password" class=""></i></span>
			<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="text" class="form-control" placeholder="" name="forgot_password_contact" id="forgot_password_contact_input" />
		</div>
		<br/>
		<script>
		//Обработка выбора контакта
		function on_forgot_password_contact_select_changed()
		{
			if( document.getElementById("forgot_password_contact_select").value == "email" )
			{
				document.getElementById("contact_type_icon_forgot_password").setAttribute('class', 'fa fa-envelope');
				document.getElementById("forgot_password_contact_input").setAttribute("placeholder", "Ваш E-mail");
			}
			else
			{
				document.getElementById("contact_type_icon_forgot_password").setAttribute('class', 'fa fa-phone');
				document.getElementById("forgot_password_contact_input").setAttribute("placeholder", "Ваш телефон");
			}
		}
		on_forgot_password_contact_select_changed();
		</script>
	
	
        
        <!--Captcha-->
        <div id="captcha">
        	<img src="/lib/captcha/captcha.php" id="capcha-image">
            <a href="javascript:void(0);" onclick="document.getElementById('capcha-image').src='/lib/captcha/captcha.php?rid=' + Math.random();"><img src="/lib/captcha/refresh.png" border="0"/></a><br>
            Введите символы с картинки: <input type="text" name="capcha_input" id="capcha_input">
        </div>
        
        <button class="btn btn-ar btn-primary" type="submit">Восстановить</button>
    </form>
    
    <script>
        //Проверка формы перед отправкой
        function onSubmitCheck()
        {
            //Проверка Captcha синхронным запросом
        	var capcha_input = document.getElementById("capcha_input").value;
        	jQuery.ajax({
        	   type: "POST",
        	   async: false, //Запрос синхронный
        	   url: "/lib/captcha/check_captcha.php",
        	   dataType: "json",//Тип возвращаемого значения
        	   data: "captcha_check="+capcha_input,
        	   success: function(is_captcha_correct){
        		   captcha_correct = is_captcha_correct;
        	   }
        	 });
        	if(captcha_correct == false)
        	{
        		alert("Символы с изображения введены не верно");
        		document.getElementById('capcha-image').src='/lib/captcha/captcha.php?rid=' + Math.random();
        		return false;
        	}
        	
        	return true;
        }
    </script>
    <?php
}
?>