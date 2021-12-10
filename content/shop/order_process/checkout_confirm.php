<?php
/**
 * Страничный скрипт для подтверждения заказа
 * 
 * - выводим перечень товаров
 * - выводим способ получения
 * 
 * - также можно вывести ссылки для корректировки - на корзину и на способ получения
*/
defined('_ASTEXE_') or die('No access');


//Рекурвиная функция. Обрабатывает все значения древовидного массива через htmlentities
function prepare_json_htmlentities($how_get)
{
	foreach($how_get AS $key=>$value)
	{
		if( is_array($value) )
		{
			$how_get[$key] = prepare_json_htmlentities($value);
		}
		else
		{
			$how_get[$key] = htmlentities($value);
		}
	}
	
	return $how_get;
}


//Получаем данные по валюте магазина
$currency_query = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;');
$currency_query->execute( array($DP_Config->shop_currency) );
$currency_record = $currency_query->fetch();
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}




//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();
if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕавторизованного пользователя
	$session_record = DP_User::getUserSession();
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}


?>
<p class="lead">Проверьте данные заказа</p>
<p>В случае необходимости изменения - вернитесь на предыдущие шаги. Если данные верны, нажмите кнопку "Подтвердить"</p>
<?php


//1. ВЫВОДИМ ПЕРЕЧЕНЬ ТОВАРОВ

//Получаем содержимое корзины
$cart_records = array();//Список для id записей корзины
$cart_records_query = $db_link->prepare('SELECT `id` FROM `shop_carts` WHERE `user_id` = ? AND `session_id` = ?;');
$cart_records_query->execute( array($user_id, $session_id) );
while($cart_record = $cart_records_query->fetch() )
{
	array_push($cart_records, $cart_record["id"]);
}

//Отображаем:
?>
<div style="overflow: hidden; overflow-x: auto;">
    <table class="table">
		<tr>
			<th style="vertical-align: middle; white-space: nowrap;">Ваш заказ</th>
			<th style="vertical-align: middle; white-space: nowrap; text-align: right;">Цена</th>
			<th style="vertical-align: middle; white-space: nowrap; text-align: center;">Количество</th>
			<th style="vertical-align: middle; white-space: nowrap; text-align: right;">Сумма</th>
		</tr>
<?php
$price_total = 0;//Сумма заказа
for($i=0; $i < count($cart_records); $i++)
{
	$cart_record_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ? AND user_id = ? AND `checked_for_order` = 1;');
	$cart_record_query->execute( array($cart_records[$i], $user_id) );
	$cart_record = $cart_record_query->fetch();
	if( $cart_record == false )
	{
		continue;
	}
    
    
    //Получаем поля в зависимости от типа продукта
    switch($cart_record["product_type"])
    {
        case 1:
            $product_id = $cart_record["product_id"];
			$name_query = $db_link->prepare('SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = ?;');
			$name_query->execute( array($product_id) );
            $name_record = $name_query->fetch();
            $name = $name_record["caption"];
            break;
        case 2:
            $name = $cart_record["t2_manufacturer"]." ".$cart_record["t2_article"].". ".$cart_record["t2_name"];
            break;
    }
    
    
    //CSS подкласс для оформления
    $sub_css = "";
    if(count($cart_records) == 1)
    {
        $sub_css = " product_div_single";
    }
    else if($i == 0)
    {
        $sub_css = " product_div_first";
    }
    else if($i == count($cart_records) - 1)
    {
        $sub_css = " product_div_last";
    }
    
    
    //Считаем деньги:
	$price = $cart_record["price"];//Цена позиции
    $price_sum = $price*$cart_record["count_need"];//Сумма по позиции
    $price_total = $price_total + $price_sum;//Сумма заказа
    ?>
    
    
    
    <tr>
        <td style="vertical-align: middle; width: 100%; min-width: 200px; max-width: 800px; word-wrap: break-word;">
            <?php echo $name; ?>
        </td>
        
		<?php
		//Строки с ценами:
		$price = number_format($price, 2, '.', ' ');
		$price_sum = number_format($price_sum, 2, '.', ' ');
		//Индикатор валюты перед ценой
		if($DP_Config->currency_show_mode == "sign_before")
		{
			$price = "<font class=\"currency\">$currency_indicator</font> <font class=\"price\">".$price."</font>";
			$price_sum = "<b><font class=\"currency\">$currency_indicator</font> <font class=\"price\">".$price_sum."</font></b>";
		}
		//Индикатор валюты после цены
		else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
		{
			$price = "<font class=\"price\">".$price."</font> <font class=\"currency\">$currency_indicator</font>";
			$price_sum = "<b><font class=\"price\">".$price_sum."</font> <font class=\"currency\">$currency_indicator</font></b>";
		}
		?>
		
		
        <td style="vertical-align: middle; white-space: nowrap; text-align: right;">
            <?php echo $price; ?>
        </td>
        
        <td style="vertical-align: middle; white-space: nowrap; text-align: center;">
            <?php echo $cart_record["count_need"]; ?>
        </td>
        
        <td style="vertical-align: middle; white-space: nowrap; text-align: right;">
            <?php echo $price_sum; ?>
        </td>
    </tr>
    <?php
}
?>
</table>
</div>

<?php
//Строка с суммой:
$price_total = number_format($price_total, 2, '.', ' ');
//Индикатор валюты перед ценой
if($DP_Config->currency_show_mode == "sign_before")
{
	$price_total = "<font class=\"currency\">$currency_indicator</font> <font class=\"price\">".$price_total."</font>";
}
//Индикатор валюты после цены
else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
{
	$price_total = "<font class=\"price\">".$price_total."</font> <font class=\"currency\">$currency_indicator</font>";
}
?>

<div style="margin-bottom: 0px; text-align: right; font-size: 18px; font-weight: bold;"><span style="font-size: 14px; font-weight: normal;">Итого:</span> <?php echo $price_total; ?></div>

<div class="hidden-sm hidden-md hidden-lg" style="margin-bottom:40px;"></div>

<?php
//2. ВЫВОДИМ СПОСОБ ПОЛУЧЕНИЯ
$how_get_json = json_decode($_COOKIE["how_get"], true);
$how_get_json = prepare_json_htmlentities($how_get_json);
//Получаем имя папки с обработчиком:
$obtain_query = $db_link->prepare('SELECT * FROM `shop_obtaining_modes` WHERE `id` = ?;');
$obtain_query->execute( array($how_get_json["mode"]) );
$obtain_mode = $obtain_query->fetch();
echo '<div style="overflow-x: auto;">';
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/".$obtain_mode["handler"]."/show_details.php");
echo '</div>';
?>






<div class="row">
	<div class="col-lg-12">
		<p class="lead">Комментарий к заказу:</p>
		<textarea style="height: 36px;" class="form-control" id="message_textarea" rows="1" placeholder="Пожелания к заказу, если есть..."></textarea>
	</div>
</div>








<?php
//3. Для неавторизованного пользователя - нужно указать контакты
if($user_id == 0)
{
	?>
	<div class="row" style="margin-top:20px;">
		<div class="col-lg-6">
			<p class="lead">Телефон для связи*:</p>
			<input style="height: 36px;" class="form-control" type="text" id="phone_not_auth" value="" placeholder="Вы не авторизованы, поэтому, оставьте телефон для связи" />
		</div>

	
		<div class="col-lg-6">
			<p class="lead">E-mail:</p>
			<input style="height: 36px;" class="form-control" type="text" id="email_not_auth" value="" placeholder="Укажите e-mail, чтобы получать уведомления о состоянии заказа" />
		</div>

		<div class="col-xs-12 hidden-lg" style="margin-top: 20px;"></div>
	</div>
	<?php	
}
?>


<script>
//ОБРАБОТКА КНОПКИ ПОДТВЕРЖДЕНИЯ
function confirm()
{
	document.getElementById("confirm_btn").style.display = 'none';
	document.getElementById("confirm_loader").style.display = 'block';
	
	var result = confirm_order();
	
	if(result == false){
		document.getElementById("confirm_loader").style.display = 'none';
		document.getElementById("confirm_btn").style.display = 'inline';
	}
}



//ПОДТВЕРЖДЕНИЕ ЗАКАЗА
function confirm_order()
{
	//Проверка согласия с обработкой персональных данных
	if( !check_user_agreement() )
	{
		return false;
	}
	
	
	
	var phone_not_auth = '';
	var email_not_auth = '';
	<?php
	//Для неавторизованного получаем контакты
	if( $user_id == 0 )
	{
		?>
		//Телефон - обязателен
		phone_not_auth = document.getElementById("phone_not_auth").value;
		if( String(phone_not_auth) == '' )
		{
			alert("Поле Телефон для связи - обязательно для заполнения");
			return false;
		}
		
		//E-mail - не обязателен
		email_not_auth = document.getElementById("email_not_auth").value;
		
		//var date = new Date(new Date().getTime() + 15552000 * 1000);
		//document.cookie = "phone_not_auth="+encodeURIComponent(phone_not_auth)+"; path=/; expires=" + date.toUTCString();
		<?php
		//Проверка контактов на соответствие регулярному выражению
		//Телефон
		$phone_field_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `name` = ?;');
		$phone_field_query->execute( array('phone') );
		$phone_field = $phone_field_query->fetch();
		$phone_field_regexp = $phone_field["regexp"];
		if( $phone_field_regexp != "" )
		{
			//Телефон проверяем в любом случае, т.к. он обязателен
			?>
			var current_value = String(phone_not_auth);//Заполненное значение
			var regex = new RegExp('<?php echo $phone_field_regexp; ?>');//Регулярное выражение для поля
			//Далее ищем подстроку по регулярному выражению
			var match = regex.exec(String(current_value));
			if(match == null)
			{
				alert("Телефон для уведомлений введен некорректно");
				return false;
			}
			else
			{
				var match_value = String(match[0]);//Подходящая подстрока
				if(match_value != current_value)
				{
					alert("Телефон для уведомлений содержит лишние знаки");
					return false;
				}
			}
			<?php
		}
		//E-mail
		$email_field_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `name` = ?;');
		$email_field_query->execute( array('email') );
		$email_field = $email_field_query->fetch();
		$email_field_regexp = $email_field["regexp"];
		if( $email_field_regexp != "" )
		{
			//E-mail проверяем только в случае его заполнения клиентом, т.к. email заполнять не обязательно для неавторизованного пользователя
			?>
			if( String( email_not_auth ) != "" )
			{
				var current_value = String(email_not_auth);//Заполненное значение
				var regex = new RegExp('<?php echo $email_field_regexp; ?>');//Регулярное выражение для поля
				//Далее ищем подстроку по регулярному выражению
				var match = regex.exec(String(current_value));
				if(match == null)
				{
					alert("E-mail введен некорректно");
					return false;
				}
				else
				{
					var match_value = String(match[0]);//Подходящая подстрока
					if(match_value != current_value)
					{
						alert("E-mail содержит лишние знаки");
						return false;
					}
				}
				//Заполнено правильно, если: есть подстрока по регулярному выражению и она полностью равна самой строке
			}
			<?php
		}
		
	}//~if - пользователь не авторизован
	?>

	
	// Комментарий заказа
	var message = document.getElementById("message_textarea").value;
	
	
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: "/content/shop/order_process/ajax_checkout_create.php",
        dataType: "text",//Тип возвращаемого значения
		data: "order_message="+encodeURIComponent(message)+"&phone_not_auth="+encodeURIComponent(phone_not_auth)+"&email_not_auth="+encodeURIComponent(email_not_auth),
        success: function(answer)
        {
			console.log(answer);
				
			var answer_ob = JSON.parse(answer);
			
			//Если некорректный парсинг ответа
			if( typeof answer_ob.status === "undefined" )
			{
				alert("Ошибка чтения ответа сервера");
			}
			else
			{
				//Корректный парсинг ответа
				if(answer_ob.status == true)
				{
					<?php
					//Заказ успешно создан - далее переадресация.
					//Для зарегистрированного клиента - страница заказа
					if($user_id != 0)
					{
						?>
						location = "/shop/orders/order?order_id="+answer_ob.order_id+"&success_message="+encodeURI("Заказ успешно оформлен");
						<?php
					}
					else//Для незарегистрированного - страница с информацией по заказу
					{
						?>
						location = "/shop/orders/zakaz-bez-registracii?order_id="+answer_ob.order_id+"&success_message="+encodeURI("Заказ успешно оформлен");
						<?php
					}
					?>
				}
				else
				{
					alert(answer_ob.message);
				
					document.getElementById("confirm_loader").style.display = 'none';
					document.getElementById("confirm_btn").style.display = 'inline';
					
					return false;
				}
			}
        }
    });
}
</script>


<?php
//Подключаем общий модуль принятия пользовательского соглашения
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/users_agreement_module.php");
?>


<div class="order_confirm_button_div text-center">
	
	<button id="confirm_btn" class="btn btn-ar btn-primary" onclick="confirm();">Подтвердить</button>
	
	<div id="confirm_loader" style="display:none;">
		<p>Отправка данных</p>
		<img src="/content/files/images/ajax-loader-transparent.gif" />
	</div>
	
</div>