<?php
/**
 * Скрипт для установки cookie режима редактирования фронтэнд / бэкэнд
*/
$cookietime = 0; // На время работы браузера
setcookie("edit_mode", $_GET["edit_mode"], $cookietime, "/");

echo $_GET['callback'].'('.json_encode($_GET["edit_mode"]).')';
?>