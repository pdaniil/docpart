<?php
header('Content-Type: application/json;charset=utf-8;');
//API для создания файла лицензии

$license = (int)$_GET["license"];
$key = $_GET["key"];
$expired = (int)$_GET["expired"];



//Если файл лицензии еще не создан - создаем
if( ! file_exists($_SERVER["DOCUMENT_ROOT"]."/license/license.lic") )
{
	$file = fopen($_SERVER["DOCUMENT_ROOT"]."/license/license.lic", "w");
	fwrite($file, "license:".$license."\nkey:".$key."\nexpired:".$expired);
	fclose($file);
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "License created";
	exit(json_encode($answer));
}
else//Если файл уже существует - ничего не делаем
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "License already exists";
	exit(json_encode($answer));
}
?>