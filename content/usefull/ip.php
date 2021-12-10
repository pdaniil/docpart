﻿<?php
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
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	exit("Forbidden");
}




$ch = curl_init("https://2ip.ru/");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
curl_close($ch);
//var_dump($result);



if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $result, $ip_match)) 
{
	?>
	<p style="font-size:18px;">Некоторые поставщики автозапчастей ограничивают доступ к своей проценке по IP-адресу. Поэтому для корректной работы проценки, сообщайте своим поставщикам IP-адрес, с которого Ваш сайт будет посылать запросы.<br>Актуальный IP-адрес Вашего сайта:</p>
	<p style="font-weight:bold;font-size:35px;"><?php echo $ip_match[0]; ?></p>
	
	<p>Сообщите его Вашим поставщикам</p>
	<?php
}
else
{
	echo "Не удалось определить IP-адрес";
}
?>