<?php
/**
 * Скрипт страницы для переадресации после успешной оплаты
*/
header('Content-Type: text/html; charset=utf-8');

// Подключаем класс работы с api банка
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/payment_systems/alfabank/SoapClient.php");


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

$query = $db_link->prepare('SELECT `id` FROM `shop_users_accounting` WHERE `tech_value_text` = ?;');
$query->execute( array($_GET['orderId']) );
$record = $query->fetch();
$operation_id = $record['id'];
//Общий скрипт получения настроек платежной системы.
require_once( $_SERVER['DOCUMENT_ROOT'].'/content/shop/finance/get_pay_system_parameters.php' );


// Авторизационные данные
$login = $paysystem_parameters["login"];
$password = $paysystem_parameters["password"];



if($paysystem_parameters["test_mode"] == 1)
{
    $wsdl = "https://web.rbsuat.com/ab/webservices/merchant-ws?wsdl";
}
else
{
    $wsdl = "https://pay.alfabank.ru/payment/webservices/merchant-ws?wsdl";
}



// Проверка совершения платежа
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['orderId']))
{
	$client = new Gateway($wsdl);
	$data = array('orderParams' => array('orderId' => $_GET['orderId']));
	
	$client->login = $login;
	$client->password = $password;
	
	$response = $client->__call('getOrderStatus', $data);
	
	if($response->errorCode != "0" )
	{
		$error_message = "Оплата отклонена. Ошибка проведения платежа на сайте платежной системы";
		$error_message = urlencode($error_message);
		?>
		<script>
			location="/shop/balans?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	else if($response->orderStatus != "2")
	{		
		$error_message = "Проверка платежа: счет не может быть создан - сообщите менеджеру код статуса платежа: ".$response->orderStatus;
		$error_message = urlencode($error_message);
		?>
		<script>
			location="/shop/balans?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	
	
	
	//Активируем операцию
	$result = $db_link->prepare('UPDATE `shop_users_accounting` SET `active`=1 WHERE `id` = ? AND `user_id` = ?;');
	if($result != $result->execute( array($response->orderNumber, $user_id) ) )
	{
		$error_message = "Платеж поступил на счет магазина, но возникла ошибка создания операции на сайте. Сообщите менеджеру id операции: ".$response->orderNumber;
		$error_message = urlencode($error_message);
		?>
		<script>
			location="/shop/balans?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	else
	{
		//Получаем сумму
		$amount_query = $db_link->prepare('SELECT `amount` FROM `shop_users_accounting` WHERE `id` = ?;');
		$amount_query->execute( array($response->orderNumber) );
		$amount_record = $amount_query->fetch();
		
		
		// -----
		//Уведомление менеджерам магазинов
		$operation_id = $response->orderNumber;
		$amount = $amount_record["amount"];
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_notify.php");
		// -----
		
		
		// -----
		//Вызов протокола оплаты заказа, если в операцию был вписан номер заказа
		$operation_id = $response->orderNumber;
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/finance/pay_for_order.php");
		// -----
		
		
		?>
		<script>
			location="/shop/balans?success_message=Платеж+зачислен";
		</script>
		<?php
		exit;
	}
}
?>