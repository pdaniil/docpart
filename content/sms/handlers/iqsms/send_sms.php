<?php
//Скрипт отправки SMS

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
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "Error";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");






//Проверка прав на запуск скрипта
if( $_POST["check"] != $DP_Config->secret_succession )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Forbidden";
	exit( json_encode($answer) );
}


//Получаем настройки SMS-оператора
$sms_api_query = $db_link->prepare('SELECT * FROM `sms_api` WHERE `handler` = ?;');
$sms_api_query->execute( array('iqsms') );
$sms_api = $sms_api_query->fetch();
$parameters_values = json_decode($sms_api["parameters_values"], true);



//Данные для отправки
//$subject = urlencode($_POST["subject"]);
$body = urlencode($_POST["body"]);
$main_field = urlencode($_POST['main_field']);
$login = urlencode($parameters_values["login"]);
$password = urlencode($parameters_values["password"]);

//Вызов API оператора
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "http://api.iqsms.ru/messages/v2/send/?phone=$main_field&text=$body&login=$login&password=$password");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
$curl_result_str = curl_exec($curl);


curl_close($curl);

$curl_result = explode(";", $curl_result_str);

$answer_code = array(
    "accepted"	=> "Сообщение принято сервисом",
    "invalid mobile phone" => "Неверно задан номер тефона (формат 71234567890)",
    "text is empty"	=> "Отсутствует текст",
    "sender address invalid" => "Неверная (незарегистрированная) подпись отправителя",
    "wapurl invalid" => "Неправильный формат wap-push ссылки",
    "invalid schedule time format" => "Неверный формат даты отложенной отправки сообщения",
    "invalid status queue name" => "Неверное название очереди статусов сообщений",
    "not enough credits" => "Баланс пуст (проверьте баланс)"
);

if( count($curl_result) != 2 )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Формат ответа SMS оператора не соответствует описанию протокола. ".$curl_result_str;
	exit( json_encode($answer) );
}
else
{
    $answer_1 = $curl_result[1];
    if(isset($answer_code[$answer_1])) {
        $answer = array();
    	$answer["status"] = true;
    	$answer["message"] = $answer_code[$answer_1];
    	exit( json_encode($answer) );
    }
    else
    {
        $answer = array();
    	$answer["status"] = true;
    	$answer["message"] = "Код ответа отсутствует. Успешно.";
    	exit( json_encode($answer) );
    }

}
?>