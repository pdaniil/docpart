<?php
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
header("Location: ".$DP_Config->domain_path."shop/balans?error_message=Ошибка");
?>