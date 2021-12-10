<?php
//Скрипт от способа доставки "Доставка по адресу" - для получения виджетов.
defined('DOCPART_MOBILE_API') or die('No access');


//Для данного способа - перечень простых полей ввода:
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Город", "name"=>"city", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Улица", "name"=>"street", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Дом", "name"=>"house", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Корпус", "name"=>"block", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Квартира/Офис", "name"=>"flat_office", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Телефон", "name"=>"phone", "required"=>true) );


?>