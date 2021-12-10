<?php
/**
Страничный срипт для VIN-запросов
*/

defined('_ASTEXE_') or die('No access');

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Для отправки уведомлений
require_once( $_SERVER["DOCUMENT_ROOT"]."/content/notifications/notify_helper.php" );

if( !empty($_POST["send_vin_zapros"]) )
{
	//0. Проверяем согласие с Пользовательским соглашением
	if( $_COOKIE["users_agreement"] != "yes" )
	{
		$error_message = "Запрос не отправлен. Необходимо принять Пользовательское соглашение";
        ?>
        <script>
            location="/vin-zapros?error_message=<?php echo $error_message;?>";
        </script>
        <?php
        exit;
	}
	
	
	//1. CAPTCHA
	//Проверям правильность ввода captcha, чтобы исключить вероятность обращения напрямую
	//Получаем значение от пользователя и сразу переводим его в md5:
	$user_captcha = md5($_POST['capcha_input']);
	//Правильная captcha из Куки, которая уже в md5:
	$cookie_captcha = $_COOKIE["captcha"];

	if($user_captcha != $cookie_captcha)
	{
		echo "Неправильная капча";
		exit;
	}



	$new_old_str = "Новая";
	if($_POST["new_old_flag"] == "new_old_flag_new")
	{
		$new_old_str = "Б/У";
	}
	
	
	$send_result = true;//Накопительный результат отправки
	
	//Формируем текст письма:
	$letter_text = "<table class=\"table\">
	<tr>
        <td>ФИО</td>
        <td>".htmlentities($_POST["client_fio"])."</td>
        <td>Город</td>
        <td>".htmlentities($_POST["client_city"])."</td>
    </tr>
     <tr>
        <td>Email</td>
        <td>".htmlentities($_POST["client_email"])."</td>
        
        <td>Телефон</td>
        <td>".htmlentities($_POST["client_phone"])."</td>
    </tr>
    <tr>
        <td>Необходимые запчасти</td>
        <td colspan='3' id='myInfoTableTdParameter'>".htmlentities($_POST["client_parts"])."</td>
    </tr>
	<tr>
        <td>Новая/Б/У:</td>
        <td>".htmlentities($new_old_str)."</td>
        <td></td>
        <td></td>   
    </tr>
    <tr>
        <td>Марка</td>
        <td>".htmlentities($_POST["client_mark"])."</td>
        <td>Модель</td>
        <td>".htmlentities($_POST["client_model"])."</td>
    </tr>
     <tr>
        <td>Год</td>
        <td>".htmlentities($_POST["client_year"])."</td>
        <td>*VIN/FRAME-код**</td>
        <td>".htmlentities($_POST["client_vin"])."</td>
    </tr>
    <tr>
        <td>Модель/объем двигателя</td>
        <td>".htmlentities($_POST["client_engine"])."</td>
        <td>Тип кпп</td>
        <td>".htmlentities($_POST["client_kpp"])."</td>
    </tr>
     <tr>
        <td>Тип кузова/число дверей</td>
        <td>".htmlentities($_POST["client_body"])."</td>
        <td>Привод</td>
        <td>".htmlentities($_POST["client_drive"])."</td>
    </tr>
	</table>";
	

	
	//Получаем список менеджеров (кому отправлять)
	$managers_list = array();//Список для контроля уникальности получателей
	$persons = array();
	$managers_query = $db_link->prepare('SELECT `users` FROM `shop_offices`;');
	$managers_query->execute();
	while( $managers = $managers_query->fetch() )
	{
		$managers = json_decode($managers["users"], true);
		for($i=0; $i < count($managers); $i++)
		{
			if( array_search((integer)$managers[$i], $managers_list) === false )
			{
				array_push($managers_list, (integer)$managers[$i]);
				
				$persons[] = array('type'=>'user_id', 'user_id' =>(integer)$managers[$i]);
			}
		}
	}
	//Значение переменных для уведомления
	$notify_vars = array();
	$notify_vars['vin_zapros_text'] = $letter_text;
	
	//Отправляем уведомление (БЕЗ обработки результата)
	$notify_result = send_notify('vin_zapros', $notify_vars, $persons);
	if( $notify_result['status'] != true )
	{
		$send_result = false;
	}
	else
	{
		//Будем считать, что отправка успешна, если хотя бы один продавец получил письмо на E-mail
		$minimum_one_success = false;
		for( $i=0 ; $i<count($notify_result["persons"]) ; $i++)
		{
			if( $notify_result["persons"][$i]['contacts']['email']['status'] == true )
			{
				$minimum_one_success = true;
			}
		}
		if( !$minimum_one_success )
		{
			$send_result = false;
		}
	}


	//Обработка результата отправки
	if($send_result)
	{
		$success_message = "Ваш запрос отправлен менеджеру! С Вами свяжутся";
        ?>
        <script>
            location="/zapros-prodavczu?success_message=<?php echo $success_message;?>";
        </script>
        <?php
        exit;
	}
	else
	{
		$error_message = "Ошибка отправки запроса!";
        ?>
        <script>
            location="/zapros-prodavczu?error_message=<?php echo $error_message;?>";
        </script>
        <?php
        exit;
	}

	
}
else//Действий нет - выводим страницу
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах выполнения действий
	
	
	$transmission = array("akpp"=>"АКПП", "mkpp"=>"МКПП", "robot"=>"Робот");
	
	
	
	$mark = "";
	$model = "";
	$year = "";
	$vin = "";
	$engine_value = "";
	$body_type = "";
	$kpp = "";
	
	
	
	if( !empty( $_COOKIE["seller_request"]) )
	{
		$user_id = DP_User::getUserId();//ID пользователя
		
		$stmt = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = :user_id AND `id` = :id;');
		$stmt->bindValue(':user_id', $user_id);
		$stmt->bindValue(':id', $_COOKIE["seller_request"]);
		$stmt->execute();
		$car_record = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$mark = $car_record["mark"];
		$model = $car_record["model"];
		$year = $car_record["year"];
		$vin = $car_record["vin"];
		if($vin == "")
		{
			$vin = $car_record["frame"];
		}
		$engine_value = $car_record["engine_value"];
		$body_type = $car_record["body_type"];
		$kpp = $transmission[$car_record["transmission"]];
	}
	
	?>

	<div align="center">
	<form method="post" onsubmit="return onSubmitCheck();">
	<input type="hidden" name="send_vin_zapros" value="send_vin_zapros" />
	<table class="table">
		<tr>
			<td><label for="client_fio">*ФИО</label></td>
			<td><input class="form-control" type="text" name="client_fio" id="client_fio" placeholder="Напишите ФИО" value=""/></td>
			
			<td><label for="client_city">Город</label></td>
			<td><input class="form-control" type="text" name="client_city" id="client_city" placeholder="Напишите город"  value=""/></td>
			
		</tr>
		
		 <tr>
			<td><label for="client_email">Email</label></td>
			<td><input class="form-control" type="text" name="client_email" id="client_email" placeholder="Напишите email" value=""/></td>
			
			<td><label for="client_phone">*Телефон</label></td>
			<td><input class="form-control" type="text" name="client_phone" id="client_phone" placeholder="Напишите телефон" value=""/></td>
		</tr>

		
		<tr>
			<td><label for="client_parts">*Необходимые запчасти</label></td>
			<td colspan="3" id='myInfoTableTdParameter'>
				<textarea class="form-control" name="client_parts" id="client_parts" placeholder="Какие нужны запчасти" style="height:100px; width:100%"></textarea>
			</td>
		</tr>
		
		
		
		<tr style="display:none;">
			<td><label for="new_old_flag_new">Нужны новые запчасти</label></td>
			<td><input class="form-control" type="radio" name="new_old_flag" id="new_old_flag_new" value="new" checked="checked"/></td>
			
			<td><label for="new_old_flag_old">Нужны Б/У запчасти</label></td>
			<td><input class="form-control" type="radio" name="new_old_flag" id="new_old_flag_old" value="old"/><br><br></td>
			
		</tr>
		
		
		
		
		<tr>
			<td><label for="client_mark">Марка</label></td>
			<td><input class="form-control" type="text" name="client_mark" id="client_mark" placeholder="Напишите марку" value="<?php echo $mark; ?>"/></td>
			
			<td><label for="client_model">Модель</label></td>
			<td><input class="form-control" type="text" name="client_model" id="client_model" placeholder="Напишите модель"  value="<?php echo $model; ?>"/></td>
			
		</tr>
		
		 <tr>
			<td><label for="client_year">Год</label></td>
			<td><input class="form-control" type="text" name="client_year" id="client_year" placeholder="Напишите год выпуска"  value="<?php echo $year; ?>"/></td>
			
			<td><label for="client_vin">*VIN/FRAME-код**</label></td>
			<td><input class="form-control" type="text" name="client_vin" id="client_vin" placeholder="Напишите VIN"  value="<?php echo $vin; ?>"/></td>
		</tr>
		
		<tr>
			<td><label for="client_engine">Модель/объем двигателя</label></td>
			<td><input class="form-control" type="text" name="client_engine" id="client_engine" placeholder="Двигатель" value="<?php echo $engine_value; ?>"/></td>
			
			<td><label for="client_kpp">Тип кпп</label></td>
			<td><input class="form-control" type="text" name="client_kpp" id="client_kpp" placeholder="МКПП/АКПП" value="<?php echo $kpp; ?>"/></td>
			
		</tr>
		
		 <tr>
			<td><label for="client_body">Тип кузова/число дверей</label></td>
			<td><input class="form-control" type="text" name="client_body" id="client_body" placeholder="Седан/Хетчбэк..." value="<?php echo $body_type; ?>"/></td>
			
			<td><label for="client_drive">Привод</label></td>
			<td><input class="form-control" type="text" name="client_drive" id="client_drive" placeholder="Передний/задний/полный" value=""/></td>
		</tr>
		

	</table>

	<br>
	
	
	<!--Captcha-->
	<div id="captcha">
		<img src="/lib/captcha/captcha.php" id="capcha-image">
		<a href="javascript:void(0);" onclick="document.getElementById('capcha-image').src='/lib/captcha/captcha.php?rid=' + Math.random();"><img src="/lib/captcha/refresh.png" border="0"/></a><br><br>
		Введите символы с картинки: <input type="text" name="capcha_input" id="capcha_input">
	</div>

	<br>
	
	
	
	
	<?php
	//Подключаем общий модуль принятия пользовательского соглашения
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/users_agreement_module.php");
	?>
	
	
	
	
	<button type="submit" class="btn btn-ar btn-primary">Отправить</button>
	</form>
	</div>
	
	<script>
	//Проверка формы при отправке
	var captcha_correct = false;
	function onSubmitCheck()
	{
		if( !check_user_agreement() )
		{
			return false;
		}
		
		
		//Проверка ввода полей:
		if(document.getElementById("client_fio").value == "")
		{
			alert("Заполните поле ФИО");
			return false;
		}
		if(document.getElementById("client_phone").value == "")
		{
			alert("Заполните поле Телефон");
			return false;
		}
		if(document.getElementById("client_parts").value == "")
		{
			alert("Заполните поле \"Необходимые запчасти\"");
			return false;
		}
	
	
	
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
	}
	</script>
	
	
	
	<br>
	Если не нашли нужную деталь в каталоге или ее нет в прайсах нашего интернет магазина, Вы можете оформить предварительную заявку на приобретение необходимых запчастей.<br>

	Для этого нужно заполнить запрос и отправить его нам. Специалисты нашей компании оперативно обработают запрос, ответят Вам и предоставят полную информацию по ценам, срокам поставки, способам оплаты нужной автозапчасти.<br> 

	Мы предложим Вам несколько вариантов запчастей (новые или б\у, оригинальные или аналоги). Для более быстрой обработки, запрос должен содержать максимум информации по автомобилю.<br> 
	<br>
	* - поля обязательные для заполнения.<br>

	** - FRAME - для автомобилей производства Японии.
	<?php
}
?>
