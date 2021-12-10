<?php
//Скрипт от способа доставки "DPD" - для получения виджетов с деталями доставки.
defined('DOCPART_MOBILE_API') or die('No access');




array_push($order["obtaining_details"], array("caption"=>"Детали доставки", "type"=>"textview", "value"=>$how_get["name"].", ".$how_get["patronymic"].", ".$how_get["surname"].", ".$how_get["cellphone"].", ".$how_get["address"]));
?>