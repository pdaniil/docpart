<?php
header('Content-Type: text/html; charset=utf-8');
/*
Скрипт для тестирования API поставщиков из панели управления
*/
//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
// ------------------------------------------------------------------------------------------------

//Получаем исходные данные
$file = $_GET["file"];
$handler_folder = $_GET["handler_folder"];

if( !file_exists( $_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/".$handler_folder."/".$file ) )
{
	exit("Указанный файл отсутствует в API поставщика. Используйте другой тип отладки");
}

// ------------------------------------------------------------------------------------------------

//Добавляем курс валюты - единицу, чтобы цены не обнулились при отладке
$storage_options = json_decode($_GET["connection_options"], true);
$storage_options["rate"] = 1;


$postdata = http_build_query(
		array(
			'article' => "OC247",//Артикул
			'manufacturers' => json_encode( array("KNECHT") ),
			'storage_options' => json_encode($storage_options)//Настройки подключения
		)
	);//Аргументы

// ------------------------------------------------------------------------------------------------


$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/shop/docpart/suppliers_handlers/".$handler_folder."/".$file);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
$curl_result = curl_exec($curl);
curl_close($curl);
echo $curl_result;
?>