<?php
header('Content-Type: application/json;charset=utf-8;');
/**
Серверный скрипт для получения необходимых настроек для загрузки прайс-листов:
- Подключение к БД
- Настройки почтового ящика для получения писем с прайс-листами
- Конфигурации прайс-листов
*/

//Проверка принятия ключа
if(empty($_GET["tech_key"]))
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Empty key";
	exit(json_encode($answer));
}


//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS


//Проверка корректности ключа
if($DP_Config->tech_key != $_GET["tech_key"])
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Wrong key";
	exit(json_encode($answer));
}

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");


//Формируем ответ с настройками
$answer = array();

//Настройки подключения к основной БД
$answer["db"] = array();
$answer["db"]["host"] = $DP_Config->host_external;
$answer["db"]["user"] = $DP_Config->user;
$answer["db"]["password"] = $DP_Config->password;
$answer["db"]["db"] = $DP_Config->db;


//Настройки почты для загрузки прайсов
$answer["prices_email"] = array();
$answer["prices_email"]["prices_email_server"] = $DP_Config->prices_email_server;
$answer["prices_email"]["prices_email_encryption"] = $DP_Config->prices_email_encryption;
$answer["prices_email"]["prices_email_port"] = $DP_Config->prices_email_port;
$answer["prices_email"]["prices_email_username"] = $DP_Config->prices_email_username;
$answer["prices_email"]["prices_email_password"] = $DP_Config->prices_email_password;


//Передаем ответ
$answer["status"] = true;
$answer["message"] = "Ok";
exit(json_encode($answer));
?>