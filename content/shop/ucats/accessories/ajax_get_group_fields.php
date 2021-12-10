<?php
/*
Серверный скрипт для получения списка полей для указанной группы товаров
*/
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

$car_name = $_POST["car_name"];
$model = $_POST["model"];
$year = $_POST["year"];



//Делаем запрос в веб-сервис Ucats
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $DP_Config->ucats_url."ucats/accessories/get_group_fields.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&car_name=".urlencode($car_name)."&model=".urlencode($model)."&year=".urlencode($year));
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