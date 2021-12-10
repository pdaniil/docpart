<?php
/*
	Скрипт делает выгрузку таблицы кроссов в .csv файл
*/

//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
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

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$ansver = array('status'=>false);
	exit(json_encode($ansver));
}

$sql = "SELECT `article`, `manufacturer_article`, `analog`, `manufacturer_analog` FROM `shop_docpart_articles_analogs_list`;";
$query = $db_link->prepare($sql);
$query->execute();

// Выводим HTTP-заголовки
header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
header ( "Cache-Control: no-cache, must-revalidate" );
header ( "Pragma: no-cache" );
header ( "Content-type: application/vnd.ms-excel" );
header ( "Content-Disposition: attachment; filename=crosses.csv" );

//Выводим файл
echo iconv("UTF-8", "WINDOWS-1251","Артикул;Производитель;Аналог;Производитель аналога\r\n");

while($rov = $query->fetch() ){
	
	$article = $rov['article'];
	$manufacturer_article = $rov['manufacturer_article'];
	$analog = $rov['analog'];
	$manufacturer_analog = $rov['manufacturer_analog'];
	
	$article = iconv("UTF-8", "WINDOWS-1251", $article);
	$manufacturer_article = iconv("UTF-8", "WINDOWS-1251", $manufacturer_article);
	$analog = iconv("UTF-8", "WINDOWS-1251", $analog);
	$manufacturer_analog = iconv("UTF-8", "WINDOWS-1251", $manufacturer_analog);
	
	echo $article.';'.$manufacturer_article.';'.$analog.';'.$manufacturer_analog."\r\n";
}
?>