<?php
/**
 * Скрипт проверки уникальности и корректности контакта (при регистрации или замене)
*/
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация Docpart
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
	$answer["result"] = "undefined";
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");



//Входящие данные:
$reg_contact = $_POST["reg_contact"];
$reg_contact_type = $_POST["reg_contact_type"];



//Имя колонки, в которой ищем контакт:
$col_name = 'email';//По-умолчанию - email
$col_caption = "E-mail";
if( $reg_contact_type == 'phone' )
{
	$col_name = 'phone';//Будем искать в колонке "Телефон"
	$col_caption = "Телефон";
}
else if( $reg_contact_type != 'email' )
{
	exit();//Значит было передано некорректное значение reg_contact_type
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
	//Значение контакта не соответствует регулярному выражению
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Указано некорректное значение для поля ".$col_caption;
	exit( json_encode($answer) );
}



//Проверяем уникальность контакта
$contact_check_query = $db_link->prepare('SELECT COUNT(*) FROM `users` WHERE `'.$col_name.'`= ?;');//У col_name - безопасное значение
$contact_check_query->execute( array(htmlentities($reg_contact)) );

$contact_count_rows = $contact_check_query->fetchColumn();

if( $contact_count_rows != 0)
{
	//Такое поле уже есть - использовать нельзя
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Указанный ".$col_caption." уже используется";
	exit( json_encode($answer) );
}
else if($contact_count_rows == 0)
{
	//Такого поля еще нет - можно использовать
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "Ok";
	exit( json_encode($answer) );
}
?>