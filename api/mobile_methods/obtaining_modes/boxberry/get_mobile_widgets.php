<?php
//Скрипт от способа доставки "DPD" - для получения виджетов.
defined('DOCPART_MOBILE_API') or die('No access');


array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Фамилия", "name"=>"surname", "required"=>false) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Имя", "name"=>"name", "required"=>false) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Отчество", "name"=>"patronymic", "required"=>false) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Телефон", "name"=>"cellphone", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Почтовый индекс", "name"=>"post_index", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Город", "name"=>"city", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"Адрес", "name"=>"address", "required"=>true) );
array_push($object["widgets"], array("type"=>"lineedit", "caption"=>"E-mail", "name"=>"email", "required"=>true) );

?>