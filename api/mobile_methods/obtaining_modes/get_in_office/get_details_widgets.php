<?php
//Скрипт от способа доставки "Самовывоз" - для получения виджетов с деталями доставки.
defined('DOCPART_MOBILE_API') or die('No access');


$office_query = $db_link->prepare('SELECT `id`,`caption`,`country`,`region`,`city`,`address`,`phone`,`email`,`coordinates`,`description`,`timetable` FROM `shop_offices` WHERE `id` = ?;');
$office_query->execute($how_get["office_id"]);
$office = $office_query->fetch();


array_push($order["obtaining_details"], array("caption"=>"Название магазина", "type"=>"textview", "value"=>$office["caption"]));

array_push($order["obtaining_details"], array("caption"=>"Адрес", "type"=>"textview", "value"=>$office["city"].", ".$office["address"]));


array_push($order["obtaining_details"], array("caption"=>"Телефон", "type"=>"textview", "value"=>$office["phone"]));

array_push($order["obtaining_details"], array("caption"=>"Описание", "type"=>"textview", "value"=>$office["description"]));


array_push($order["obtaining_details"], array("caption"=>"Режим работы", "type"=>"textview", "value"=>$office["timetable"]));
?>