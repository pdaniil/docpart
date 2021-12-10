<?php
//Скрипт от способа доставки "Доставка по адресу" - для получения виджетов с деталями доставки.
defined('DOCPART_MOBILE_API') or die('No access');




array_push($order["obtaining_details"], array("caption"=>"Детали доставки", "type"=>"textview", "value"=>$how_get["city"].", ".$how_get["street"].", ".$how_get["house"].", ".$how_get["block"].", ".$how_get["flat_office"]));


?>