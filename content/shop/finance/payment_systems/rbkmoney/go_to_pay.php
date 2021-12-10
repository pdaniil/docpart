<?php
/**
 * Скрипт перехода на страницу оплаты
*/
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

function get_email_user($user_id)
{
	global $db_link, $DP_Config;
	$query = $db_link->prepare('SELECT `email` FROM `users` WHERE `user_id` = ?;');
	$query->execute( array($user_id) );
	$record = $query->fetch();
	$email = $record["email"];
	return $email;
}

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["result"] = false;
	exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

$user_id = DP_User::getUserId();


//Получаем данные операции
$operation_id = (int)$_GET["operation"];
$operation_query = $db_link->prepare('SELECT * FROM `shop_users_accounting` WHERE `id` = ? AND `active` = 0 AND `user_id` = ?;');
$operation_query->execute( array($operation_id, $user_id) );
$operation = $operation_query->fetch();
if($operation == false)
{
    $answer = array();
	$answer["result"] = false;
	$answer["code"] = 2;
	exit(json_encode($answer));
}
$operation_description = "Пополнение баланса";
if($operation["pay_orders"] != "" && $operation["pay_orders"] != NULL)
{
	$operation_description = "Оплата заказа";
}


//Общий скрипт получения настроек платежной системы.
require_once( $_SERVER['DOCUMENT_ROOT'].'/content/shop/finance/get_pay_system_parameters.php' );



$eshopId = $paysystem_parameters["eshopId"];// ID магазина
$recipientCurrency = $paysystem_parameters["recipientCurrency"];// Валюта

$out_summ = $operation["amount"];// Сумма

$email = get_email_user($user_id);
// инициализация сеанса
?>
<!DOCTYPE html>
<html>
<head>

</head>
<body style="background:#eee;">
<div style=" display:block; width:300px; background:#fff; padding:50px; height:300px; margin:50px auto; " >
<form action="https://rbkmoney.ru/acceptpurchase.aspx" method="post">
	<div>
		<h2>Выберите способ оплаты:</h2>
	</div>
	<div>
		<input id="payment_cards" type="radio" name="preference" checked="" value="bankcard">
		<label for="payment_cards">Банковская карта Visa/MasterCard</label>
	</div>
	<div>
		<input id="payment_bank" type="radio" name="preference" value="sberbank">
		<label for="payment_bank">Банковский платеж</label>
	</div>
	<div>
		<input id="payment_internet" type="radio" name="preference" value="ibank">
		<label for="payment_internet">Интернет-банкинг</label>
	</div>
	<div>
		<input id="payment_terminal" type="radio" name="preference" value="terminals">
		<label for="payment_terminal">Платежные теминалы</label>
	</div>
	<div>
		<input id="payment_salon" type="radio" name="preference" value="mobilestores">
		<label for="payment_salon">Салоны связи</label>
	</div>
	<div>
		<input id="payment_wallet" type="radio" name="preference" value="inner">
		<label for="payment_wallet">Кошелек</label>
	</div>
	<div>
		<input id="payment_systems" type="radio" name="preference" value="transfers">
		<label for="payment_systems">Системы денежных переводов</label>
	</div>
	
	<div style="display:none;">
		<input type="text" name="eshopId" value="<?php echo $eshopId; ?>">
		<input type="text" name="orderId" value="<?php echo $operation_id; ?>">
		<input type="text" name="serviceName" value="<?php echo $operation_description; ?>">
		<input type="text" name="recipientAmount" value="<?php echo $out_summ; ?>">
		<input type="text" name="recipientCurrency" value="<?php echo $recipientCurrency; ?>">
		<input type="text" name="successUrl" value="<?php echo $DP_Config->domain_path .'shop/balans?success_message=Balance%20is%20replenished'; ?>">
		<input type="text" name="failUrl" value="<?php echo $DP_Config->domain_path .'shop/balans?error_message=An%20error%20occurred'; ?>">
		<input name="user_email" type="hidden" value="<?echo $email; ?>">
	</div>

	<div style="padding-top:20px;">
		<button type="submit"><span class="btn">Продолжить</span></button>
	</div>
</form>
</div>
</body>
</html>