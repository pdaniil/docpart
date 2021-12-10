<?php
//Скрипт от способа доставки "Самовывоз" - для получения виджетов.
defined('DOCPART_MOBILE_API') or die('No access');


$offices = array();
$offices_query = $db_link->prepare('SELECT `id`,`caption`,`country`,`region`,`city`,`address`,`phone`,`email`,`coordinates`,`description`,`timetable` FROM `shop_offices`;');
$offices_query->execute();
while( $office = $offices_query->fetch() )
{
	array_push($offices, array("value"=>$office["id"], "caption"=>$office["caption"]." ".$office["city"].", ".$office["address"].", ".$office["phone"].", ".$office["description"].", ".$office["timetable"]) );	
}


array_push($object["widgets"], array("caption"=>"Выберите точку выдачи", "name"=>"office_id", "type"=>"radio", "items"=>$offices) );

?>