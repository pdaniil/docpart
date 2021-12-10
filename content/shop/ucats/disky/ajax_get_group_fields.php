<?php
/*
Серверный скрипт для получения списка полей для указанной группы товаров
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

$group = $_POST["group"];//ПОЛУЧАЕМ ГРУППУ ТОВАРОВ


//Делаем запрос в веб-сервис Ucats
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $DP_Config->ucats_url."ucats/disky/get_group_fields.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&group=".urlencode($group));
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$curl_result = curl_exec($curl);
curl_close($curl);
$curl_result = json_decode($curl_result, true);
exit(json_encode($curl_result["fields"]));
?>