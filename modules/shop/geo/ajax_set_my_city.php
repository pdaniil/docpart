<?php
/**
 * Серверный скрипт для установки своего города
*/

$cookietime = time()+9999999;//на долго
setcookie("my_city", $_POST["geo_id"], $cookietime, "/");

echo 1;
?>