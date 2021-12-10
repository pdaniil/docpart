<?php
/*
Серверный скрипт для загрузки файла на сервер в директорию tmp
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;


//Проверям на расширение из трех знаков (txt, csv, xls)
$file_format = substr($_FILES['csv_file']['name'], strlen($_FILES['csv_file']['name'])-4, 4);
$file_format = strtolower($file_format);//К нижнему регистру
//Теперь полная проверка расширения
if(strtoupper($file_format) != ".TXT" &&
strtoupper($file_format) != ".CSV")
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Wrong file extension. Use csv or txt files";
	exit( json_encode($answer) );
}
//Загружаем файл
$uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/";
$uploadfile = $uploaddir . basename($_FILES['csv_file']['name']);
if (! move_uploaded_file($_FILES['csv_file']['tmp_name'], $uploadfile)) 
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Could not upload the file";
	exit( json_encode($answer) );
}
else
{
	$answer = array();
	$answer["status"] = true;
	$answer["file_full_path"] = $uploadfile;
	exit( json_encode($answer) );
}
?>