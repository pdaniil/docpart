<?php
//Скрипт для формирования HTML накладной ТОРГ-12
/*
В этом скрипте нет учета количества страниц. Если после продолжительного тестирования скрипта get_html_torg_12.php не возникнет ошибок, то, скрипт _get_html_torg_12_onepage_ok.php можно удалить
*/
defined('_INTASK_') or die('No access');


$order_id = (int)$_GET["order_id"];


//Получаем запись заказа и проверяем права на печать
if(DP_User::isAdmin())
{
	//Вызов со стороны админа - поэтому, не проверяем принадлежность заказа пользователю
	$order_query = $db_link->prepare('SELECT * FROM `shop_orders` WHERE `id` = ?;');
	$order_query->execute( array($order_id) );
	$order_record = $order_query->fetch();
}
else
{
	//Вызов со стороны обычноного пользователя
	if($user_id == 0)
	{
		//Не авторизованные не могут печатать
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Not authorized";
		exit(json_encode($answer));
	}
	
	
	$order_query = $db_link->prepare('SELECT * FROM `shop_orders` WHERE `user_id` = ? AND `id` = ?;');
	$order_query->execute( array($user_id, $order_id) );
	$order_record = $order_query->fetch();
}
if($order_record == false)
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No such order";
    exit(json_encode($answer));
}






//Формируем HTML
ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 5.0 Transitional//EN">
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=utf-8">
<TITLE></TITLE>
<STYLE TYPE="text/css">
body { background: #ffffff; margin: 0; font-family: Arial; font-size: 8pt; font-style: normal; width:1000px; }
/*tr.R0{ height: 21px; }*/
tr.R0 td.R0C12{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: right; }
tr.R0 td.R0C6{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: left; vertical-align: top; }
tr.R0 td.R0C9{ text-align: left; }
tr.R0 td.R3C0{ text-align: right; }
tr.R0 td.R3C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R0 td.R3C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-right: #000000 2px solid; }
tr.R0 td.R3C9{ border-bottom: #000000 1px solid; }
/*tr.R1{ height: 17px; }*/
tr.R1 td.R1C0{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: top; }
tr.R1 td.R1C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; vertical-align: middle; }
tr.R1 td.R1C12{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
/*tr.R18{ height: 15px; }*/
tr.R18 td.R18C0{ vertical-align: top; }
tr.R18 td.R18C15{ font-family: Arial; font-size: 8pt; font-style: italic; text-align: right; vertical-align: top; }
tr.R18 td.R18C3{ text-align: center; vertical-align: top; }
tr.R18 td.R18C7{ text-align: right; vertical-align: top; }
tr.R18 td.R19C0{ vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R18 td.R19C1{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R19C2{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R21C0{ vertical-align: middle; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R18 td.R21C1{ font-family: Arial; font-size: 7pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R21C3{ font-family: Arial; font-size: 7pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 2px solid; border-right: #000000 1px solid; }
tr.R18 td.R23C0{ border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R18 td.R23C1{ border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R18 td.R23C11{ text-align: center; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R18 td.R23C12{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 1px solid; border-right: #ffffff 1px none; }
tr.R18 td.R23C13{ text-align: center; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R18 td.R23C14{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R18 td.R23C15{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R23C2{ border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R18 td.R23C3{ border-top: #000000 2px solid; }
tr.R18 td.R23C7{ text-align: right; border-top: #000000 2px solid; }
tr.R18 td.R23C8{ text-align: right; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R18 td.R24C11{ text-align: center; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R24C12{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R24C14{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R24C7{ text-align: right; }
tr.R18 td.R24C8{ text-align: right; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R26C3{ border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R18 td.R26C7{ border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R18 td.R26C8{ border-bottom: #000000 1px solid; }
tr.R18 td.R28C8{ border-left: #ffffff 0px none; border-top: #ffffff 0px none; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R18 td.R30C16{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; }
tr.R18 td.R30C9{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R18 td.R36C0{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; vertical-align: bottom; }
tr.R18 td.R36C1{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R18 td.R36C2{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; vertical-align: top; border-bottom: #000000 1px solid; }
tr.R18 td.R36C7{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; vertical-align: top; border-right: #000000 1px solid; }
tr.R18 td.R40C0{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; }
tr.R18 td.R40C6{ border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R41C17{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; overflow: hidden;border-top: #ffffff 1px none; }
tr.R18 td.R41C2{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-top: #ffffff 1px none; }
tr.R18 td.R41C6{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; border-right: #000000 1px solid; }
tr.R18 td.R42C2{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R18 td.R42C3{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-bottom: #ffffff 1px none; }
tr.R18 td.R45C10{ text-align: center; }
tr.R18 td.R45C7{ border-right: #000000 1px solid; }
/*tr.R2{ height: 22px; }*/
tr.R2 td.R2C0{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: top; }
tr.R2 td.R2C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R2 td.R2C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; vertical-align: middle; }
tr.R2 td.R2C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: middle; border-left: #000000 2px solid; border-top: #000000 2px solid; border-right: #000000 2px solid; }
/*tr.R20{ height: 56px; }*/
tr.R20 td.R20C0{ vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R20 td.R20C2{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R20 td.R20C3{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R32{ height: 7px; }
tr.R32 td.R44C7{ border-right: #000000 1px solid; }
tr.R33{ height: 13px; }
tr.R33 td.R33C10{ text-align: right; }
tr.R33 td.R33C14{ text-align: center; }
tr.R33 td.R33C4{ border-bottom: #000000 1px solid; }
tr.R33 td.R33C7{ border-right: #000000 1px solid; }
tr.R33 td.R35C0{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; }
tr.R33 td.R35C1{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; overflow: visible;}
tr.R33 td.R35C11{ border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R33 td.R35C2{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: left; }
tr.R33 td.R35C3{ text-align: left; }
tr.R33 td.R35C7{ text-align: left; border-right: #000000 1px solid; }
tr.R34{ height: 14px; }
tr.R34 td.R34C4{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; }
tr.R34 td.R34C7{ border-right: #000000 1px solid; }
tr.R34 td.R37C1{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R34 td.R37C11{ border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R34 td.R37C12{ border-bottom: #000000 1px solid; }
tr.R39{ height: 12px; }
tr.R39 td.R39C10{ border-left: #ffffff 0px none; border-top: #ffffff 1px none; }
tr.R39 td.R39C11{ border-top: #ffffff 1px none; }
tr.R39 td.R39C2{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; }
tr.R39 td.R39C6{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; border-right: #000000 1px solid; }
tr.R4{ height: 11px; }
tr.R4 td.R10C4{ font-family: Arial; font-size: 8pt; font-style: normal; vertical-align: bottom; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R4 td.R12C11{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; vertical-align: bottom; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R4 td.R27C17{ border-left: #000000 2px solid; border-top: #000000 2px solid; border-bottom: #000000 2px solid; border-right: #000000 2px solid; }
tr.R4 td.R29C10{ border-top: #ffffff 1px none; }
tr.R4 td.R29C9{ font-family: Arial; font-size: 6pt; font-style: normal; border-top: #ffffff 1px none; }
tr.R4 td.R4C0{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-top: #ffffff 1px none; }
tr.R4 td.R4C10{ font-family: Arial; font-size: 8pt; font-style: normal; }
tr.R4 td.R4C12{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: left; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 2px solid; }
tr.R4 td.R4C2{ font-family: Arial; font-size: 6pt; font-style: normal; }
tr.R4 td.R4C3{ text-align: center; }
tr.R4 td.R8C0{ text-align: right; }
tr.R4 td.R8C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: right; }
tr.R4 td.R8C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R4 td.R8C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-right: #000000 2px solid; }
tr.R4 td.R8C2{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R4 td.R8C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R5{ height: 16px; }
tr.R5 td.R13C1{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R5 td.R13C2{ text-align: left; vertical-align: bottom; overflow: visible;border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R5 td.R14C1{ font-family: Arial; font-size: 8pt; font-style: normal; }
tr.R5 td.R14C11{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; vertical-align: bottom; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R5 td.R14C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-right: #000000 2px solid; }
tr.R5 td.R14C2{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
tr.R5 td.R15C4{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R5 td.R16C2{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: right; vertical-align: middle; border-right: #000000 1px solid; }
tr.R5 td.R16C4{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 2px solid; border-top: #000000 2px solid; border-bottom: #000000 2px solid; border-right: #000000 1px solid; }
tr.R5 td.R16C5{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 2px solid; border-bottom: #000000 2px solid; border-right: #000000 2px solid; }
tr.R5 td.R17C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; vertical-align: bottom; }
tr.R5 td.R17C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 2px solid; border-right: #000000 2px solid; }
tr.R5 td.R17C4{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R5 td.R25C5{ border-bottom: #ffffff 1px none; }
tr.R5 td.R25C6{ border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R5 td.R25C7{ border-bottom: #000000 1px solid; }
tr.R5 td.R5C0{ text-align: right; }
tr.R5 td.R5C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R5 td.R5C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R6{ height: 19px; }
tr.R6 td.R6C0{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-left: #ffffff 0px none; border-top: #ffffff 1px none; }
tr.R6 td.R6C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R6 td.R6C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 2px solid; }
tr.R6 td.R6C2{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; }
/*tr.R7{ height: 29px; }*/
tr.R7 td.R38C2{ border-bottom: #000000 1px solid; }
tr.R7 td.R38C3{ border-bottom: #ffffff 1px none; }
tr.R7 td.R38C4{ border-bottom: #000000 1px solid; }
tr.R7 td.R38C6{ border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R7 td.R7C0{ text-align: right; }
tr.R7 td.R7C1{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R7 td.R7C10{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R7 td.R7C12{ font-family: Arial; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: bottom; overflow: hidden;border-left: #000000 2px solid; border-right: #000000 2px solid; }
tr.R7 td.R7C2{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
/*tr.R9{ height: 43px; }*/
tr.R9 td.R22C0{ vertical-align: top; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #ffffff 1px none; border-right: #ffffff 1px none; }
tr.R9 td.R22C1{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R9 td.R22C10{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R9 td.R22C12{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R9 td.R22C13{ text-align: left; vertical-align: top; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R9 td.R22C14{ text-align: right; vertical-align: top; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R9 td.R22C15{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 2px solid; }
tr.R9 td.R22C2{ vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R9 td.R22C3{ text-align: left; vertical-align: top; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R9 td.R22C4{ text-align: center; vertical-align: top; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R9 td.R22C5{ text-align: center; vertical-align: top; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #ffffff 0px none; }
tr.R9 td.R22C6{ text-align: center; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R9 td.R22C7{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R9 td.R9C0{ text-align: right; }
tr.R9 td.R9C1{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: right; }
tr.R9 td.R9C2{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; border-top: #ffffff 1px none; border-bottom: #000000 1px solid; }
table {table-layout: fixed; padding: 0px; padding-left: 2px; vertical-align:bottom; border-collapse:collapse;width: 100%; font-family: Arial; font-size: 8pt; font-style: normal; }
td { padding: 0px; padding-left: 2px; overflow:hidden; }
</STYLE>
</HEAD>
<BODY STYLE="background: #ffffff; margin: 0; font-family: Arial; font-size: 8pt; font-style: normal; ">
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=111>
<COL WIDTH=132>
<COL WIDTH=132>
<COL WIDTH=98>
<COL WIDTH=98>
<COL WIDTH=66>
<COL WIDTH=67>
<COL WIDTH=91>
<COL WIDTH=161>
<COL WIDTH=9>
<COL WIDTH=49>
<COL WIDTH=78>
<COL>
<TR CLASS=R0>
<TD><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=2><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R0C6" COLSPAN=3><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R0C12" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;">Унифицированная&nbsp;форма&nbsp;№&nbsp;ТОРГ-12<BR>Утверждена&nbsp;постановлением&nbsp;Госкомстата&nbsp;России&nbsp;от&nbsp;25.12.98&nbsp;№&nbsp;132</SPAN></TD>
<TD><DIV STYLE="position:relative; height:21px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:21px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R1>
<TD CLASS="R1C0"><SPAN></SPAN></TD>
<TD CLASS="R1C0"><SPAN></SPAN></TD>
<TD CLASS="R1C0" COLSPAN=2><SPAN></SPAN></TD>
<TD CLASS="R1C0"><SPAN></SPAN></TD>
<TD CLASS="R1C0"><SPAN></SPAN></TD>
<TD CLASS="R1C0" COLSPAN=3><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R1C10"><SPAN></SPAN></TD>
<TD CLASS="R1C10"><SPAN></SPAN></TD>
<TD CLASS="R1C12"><SPAN STYLE="white-space:nowrap;max-width:0px;">Коды</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R2>
<TD CLASS="R2C0"><SPAN></SPAN></TD>
<TD CLASS="R2C1" COLSPAN=8 ROWSPAN=2><?php echo $parameters_values["cargo_sender"]; ?></TD>
<TD CLASS="R2C10" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Форма&nbsp;по&nbsp;ОКУД&nbsp;</SPAN></TD>
<TD CLASS="R2C12"><SPAN STYLE="white-space:nowrap;max-width:0px;">0330212</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R0>
<TD CLASS="R3C0"><SPAN></SPAN></TD>
<TD CLASS="R3C9"><SPAN></SPAN></TD>
<TD CLASS="R3C10" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">по&nbsp;ОКПО</SPAN></TD>
<TD CLASS="R3C12"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD CLASS="R4C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C2" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;">организация-грузоотправитель,&nbsp;адрес,&nbsp;телефон,&nbsp;факс,&nbsp;банковские&nbsp;реквизиты</SPAN></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C12" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD CLASS="R5C0"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R5C1" COLSPAN=9><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN><?php echo $parameters_values["structure_division"]; ?></SPAN></DIV></TD>
<TD CLASS="R5C10"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R5C10"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R6>
<TD CLASS="R6C0" COLSPAN=2 STYLE="border-left: #ffffff 0px none; "><DIV STYLE="position:relative; height:19px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R6C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">структурное&nbsp;подразделение</SPAN></TD>
<TD CLASS="R6C2" COLSPAN=5><DIV STYLE="position:relative; height:19px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R6C10" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Вид&nbsp;деятельности&nbsp;по&nbsp;ОКДП</SPAN></TD>
<TD CLASS="R6C12"><DIV STYLE="position:relative; height:19px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:19px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:19px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R7>
<TD CLASS="R7C0"><SPAN></SPAN></TD>
<TD CLASS="R7C1">Грузополучатель</TD>
<TD CLASS="R7C2" COLSPAN=8><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["consignee"]); ?></TD>
<TD CLASS="R7C10" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">по&nbsp;ОКПО</SPAN></TD>
<TD CLASS="R7C12"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD CLASS="R8C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C1"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">организация,&nbsp;адрес,&nbsp;телефон,&nbsp;факс,&nbsp;банковские&nbsp;реквизиты</SPAN></TD>
<TD CLASS="R8C4" COLSPAN=5><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C12" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R9>
<TD CLASS="R9C1" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Поставщик</SPAN></TD>
<TD CLASS="R9C2" COLSPAN=8><?php echo $parameters_values["supplier"]; ?></TD>
<TD CLASS="R9C1" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">по&nbsp;ОКПО</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD CLASS="R8C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C1"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">организация,&nbsp;адрес,&nbsp;телефон,&nbsp;факс,&nbsp;банковские&nbsp;реквизиты</SPAN></TD>
<TD CLASS="R10C4" COLSPAN=5><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C10"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C12" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R7>
<TD CLASS="R7C10" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Плательщик</SPAN></TD>
<TD CLASS="R7C2" COLSPAN=8><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["payer"]); ?></TD>
<TD CLASS="R7C10" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">по&nbsp;ОКПО</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD CLASS="R8C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C1"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R8C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">организация,&nbsp;адрес,&nbsp;телефон,&nbsp;факс,&nbsp;банковские&nbsp;реквизиты</SPAN></TD>
<TD CLASS="R8C4" COLSPAN=5><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R12C11" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">номер</SPAN></TD>
<TD CLASS="R8C12" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD CLASS="R13C1" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Основание</SPAN></TD>
<TD CLASS="R13C2" COLSPAN=8><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $parameters_values["basis_of_payment"]; ?></SPAN></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C1"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">договор,&nbsp;заказ-наряд</SPAN></TD>
<TD CLASS="R14C2" COLSPAN=5><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">дата</SPAN></TD>
<TD CLASS="R14C12"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=2><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R15C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">Номер&nbsp;документа</SPAN></TD>
<TD CLASS="R15C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">Дата&nbsp;составления</SPAN></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R5C10"><SPAN STYLE="white-space:nowrap;max-width:0px;">Транспортная&nbsp;накладная</SPAN></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">номер</SPAN></TD>
<TD CLASS="R14C12"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R16C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">ТОВАРНАЯ&nbsp;НАКЛАДНАЯ&nbsp;&nbsp;</SPAN></TD>
<TD CLASS="R16C4"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $order_id; ?></SPAN></TD>
<TD CLASS="R16C5"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo date("d.m.Y", $order_record["time"]); ?></SPAN></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">дата</SPAN></TD>
<TD CLASS="R14C12"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R5>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R13C1" COLSPAN=2><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R17C4"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R17C4"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R17C10" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Вид&nbsp;операции</SPAN></TD>
<TD CLASS="R17C12"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=35>
<COL WIDTH=214>
<COL WIDTH=73>
<COL WIDTH=56>
<COL WIDTH=45>
<COL WIDTH=47>
<COL WIDTH=42>
<COL WIDTH=56>
<COL WIDTH=49>
<COL WIDTH=63>
<COL WIDTH=80>
<COL WIDTH=91>
<COL WIDTH=70>
<COL WIDTH=81>
<COL WIDTH=91>
<COL>
<TR CLASS=R18>
<TD CLASS="R18C15" COLSPAN=16><SPAN STYLE="white-space:nowrap;max-width:0px;">Страница&nbsp;1</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R18>
<TD CLASS="R19C0"><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R19C1" ROWSPAN=2>Но-<BR>мер<BR>по по-<BR>рядку </TD>
<TD CLASS="R19C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Товар</SPAN></TD>
<TD CLASS="R19C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Единица&nbsp;измерения</SPAN></TD>
<TD CLASS="R19C1" ROWSPAN=2>Вид упаков-<BR>ки</TD>
<TD CLASS="R19C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Количество</SPAN></TD>
<TD CLASS="R19C1" ROWSPAN=2>Масса брутто</TD>
<TD CLASS="R19C2" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Коли-<BR>чество&nbsp;<BR>(масса&nbsp;<BR>нетто)</SPAN></TD>
<TD CLASS="R19C2" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Цена,<BR>руб.&nbsp;коп.</SPAN></TD>
<TD CLASS="R19C2" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Сумма&nbsp;без<BR>учета&nbsp;НДС,<BR>руб.&nbsp;коп.</SPAN></TD>
<TD CLASS="R19C2" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">НДС</SPAN></TD>
<TD CLASS="R19C2" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Сумма&nbsp;с<BR>учетом&nbsp;<BR>НДС,&nbsp;<BR>руб.&nbsp;коп.</SPAN></TD>
<TD><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:15px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R20>
<TD CLASS="R20C0"><SPAN></SPAN></TD>
<TD CLASS="R20C2">наименование, характеристика, сорт, артикул товара</TD>
<TD CLASS="R20C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">код</SPAN></TD>
<TD CLASS="R20C2">наиме- нование</TD>
<TD CLASS="R20C2">код по ОКЕИ</TD>
<TD CLASS="R20C2">в одном месте</TD>
<TD CLASS="R20C2">мест,<BR>штук</TD>
<TD CLASS="R20C2">ставка, %</TD>
<TD CLASS="R20C2">сумма, <BR>руб. коп.</TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R18>
<TD CLASS="R21C0"><SPAN></SPAN></TD>
<TD CLASS="R21C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">1</SPAN></TD>
<TD CLASS="R21C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">2</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">3</SPAN></TD>
<TD CLASS="R21C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">4</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">5</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">6</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">7</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">8</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">9</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">10</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">11</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">12</SPAN></TD>
<TD CLASS="R21C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">13</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">14</SPAN></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">15</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>




<?php
//Получаем товарные позиции заказа, которые участвуют при ценовых расчетах (по статусу позиции)
//Наименования
$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
//Сумма позиции
$SELECT_item_price_sum = "`price`*`count_need`";
//ВЛОЖЕННЫЙ ЗАПРОС
$SELECT_ORDER_ITEMS = "SELECT *, $SELECT_product_name AS `product_name`, $SELECT_item_price_sum AS `price_sum` FROM `shop_orders_items` WHERE `order_id` = ? AND `status` IN (SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = ?);";

$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute( array($order_id, 1) );
$count_items = 0;//Счетчик позиций
$price_sum_total_num = 0;//Сумма ИТОГО (цифра без форматирования)

$itogo_page_price_sum_without_NDS = 0;//ИТОГО сумма без НДС (по таблице на текущей странице)
$itogo_all_price_sum_without_NDS = 0;//ИТОГО сумма без НДС (по всей накладной)

$itogo_page_count_need = 0;//ИТОГО количество (по таблице на текущей странице)
$itogo_all_count_need = 0;//ИТОГО количество (по всей накладной)

$itogo_page_NDS = 0;//ИТОГО сумма НДС (по таблице на текущей странице)
$itogo_all_NDS = 0;//ИТОГО сумма НДС (по всей накладной)

$itogo_page_price_sum = 0;//ИТОГО сумма с НДС (по таблице на текущей странице)
$itogo_all_price_sum = 0;//ИТОГО сумма с НДС (по всей накладной)

while( $order_item = $order_items_query->fetch() )
{
	$count_items++;//Счетчик позиций
	
	$price_sum_total_num = $price_sum_total_num + $order_item["price_sum"];//Сумма ИТОГО
	
	
	
	//В зависимости от НДС:
	if( $parameters_values["nds"] != "" )
	{
		$NDS_mark = $parameters_values["nds"];//Обозначение НДС
		
		
		$price_without_NDS = $order_item["price"]/(1 + $parameters_values["nds"]/100);//Цена Без НДС
		$NDS = ($order_item["price"] - $price_without_NDS)*$order_item["count_need"];//Сумма НДС
		$price_sum_without_NDS = $price_without_NDS * $order_item["count_need"];//Сумма без НДС
	}
	else
	{
		$NDS_mark = "Без НДС";//Обозначение НДС
		
		$price_without_NDS = $order_item["price"];//Цена Без НДС
		$NDS = "";//Сумма НДС
		$price_sum_without_NDS = $price_without_NDS*$order_item["count_need"];//Сумма без НДС
	}
	
	//ИТОГО:
	$itogo_page_price_sum_without_NDS = $itogo_page_price_sum_without_NDS + $price_sum_without_NDS;
	$itogo_all_price_sum_without_NDS = $itogo_all_price_sum_without_NDS + $price_sum_without_NDS;
	$itogo_page_count_need = $itogo_page_count_need + $order_item["count_need"];
	$itogo_all_count_need = $itogo_all_count_need + $order_item["count_need"];
	if( $NDS == "" )
	{
		$itogo_page_NDS = "";
		$itogo_all_NDS = "";
	}
	else
	{
		$itogo_page_NDS = $itogo_page_NDS + $NDS;
		$itogo_all_NDS = $itogo_all_NDS + $NDS;
	}
	$itogo_page_price_sum = $itogo_page_price_sum + $order_item["price_sum"];
	$itogo_all_price_sum = $itogo_all_price_sum + $order_item["price_sum"];
	
	
	?>
	<TR CLASS=R9>
	<TD CLASS="R22C0"><SPAN></SPAN></TD>
	<TD CLASS="R22C1"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $count_items; ?></SPAN></TD>
	<TD CLASS="R22C2"><?php echo $order_item["product_name"]; ?></TD>
	<TD CLASS="R22C3">00-00000001</TD>
	<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">шт</SPAN></TD>
	<TD CLASS="R22C5">796</TD>
	<TD CLASS="R22C6"><SPAN></SPAN></TD>
	<TD CLASS="R22C7"><SPAN></SPAN></TD>
	<TD CLASS="R22C7"><SPAN></SPAN></TD>
	<TD CLASS="R22C7"><SPAN></SPAN></TD>
	<TD CLASS="R22C10"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $order_item["count_need"]; ?>,000</SPAN></TD>
	<TD CLASS="R22C10"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($price_without_NDS); ?></SPAN></TD>
	<TD CLASS="R22C12"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($price_sum_without_NDS); ?></SPAN></TD>
	<TD CLASS="R22C13"><?php echo $NDS_mark; ?></TD>
	<TD CLASS="R22C14"><SPAN><?php if($NDS != "") echo print_price($NDS); ?></SPAN></TD>
	<TD CLASS="R22C15"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($order_item["price_sum"]); ?></SPAN></TD>
	<TD><SPAN></SPAN></TD>
	<TD></TD>
	</TR>
	<?php
}
?>



<TR CLASS=R18>
<TD CLASS="R23C0"><SPAN></SPAN></TD>
<TD CLASS="R23C1"><SPAN></SPAN></TD>
<TD CLASS="R23C2"><SPAN></SPAN></TD>
<TD CLASS="R23C3"><SPAN></SPAN></TD>
<TD CLASS="R23C0"><SPAN></SPAN></TD>
<TD CLASS="R23C7" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Итого&nbsp;</SPAN></TD>
<TD CLASS="R23C8"><SPAN></SPAN></TD>
<TD CLASS="R23C8"><SPAN></SPAN></TD>
<TD CLASS="R23C8"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $itogo_page_count_need; ?>,000</SPAN></TD>
<TD CLASS="R23C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">Х</SPAN></TD>
<TD CLASS="R23C12"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($itogo_page_price_sum_without_NDS); ?></SPAN></TD>
<TD CLASS="R23C13"><SPAN STYLE="white-space:nowrap;max-width:0px;">Х</SPAN></TD>
<TD CLASS="R23C14"><SPAN><?php if($itogo_page_NDS != "") echo print_price($itogo_page_NDS); ?></SPAN></TD>
<TD CLASS="R23C15"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($itogo_page_price_sum); ?></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R18>
<TD CLASS="R23C0"><SPAN></SPAN></TD>
<TD CLASS="R24C7" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;по&nbsp;накладной&nbsp;</SPAN></TD>
<TD CLASS="R24C8"><SPAN></SPAN></TD>
<TD CLASS="R24C8"><SPAN></SPAN></TD>
<TD CLASS="R24C8"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $itogo_all_count_need; ?>,000</SPAN></TD>
<TD CLASS="R24C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">Х</SPAN></TD>
<TD CLASS="R24C12"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($itogo_all_price_sum_without_NDS); ?></SPAN></TD>
<TD CLASS="R24C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">Х</SPAN></TD>
<TD CLASS="R24C14"><SPAN><?php if($itogo_all_NDS != "") echo print_price($itogo_all_NDS); ?></SPAN></TD>
<TD CLASS="R24C12"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo print_price($itogo_all_price_sum); ?></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=125>
<COL WIDTH=112>
<COL WIDTH=12>
<COL WIDTH=104>
<COL WIDTH=13>
<COL WIDTH=138>
<COL WIDTH=83>
<COL WIDTH=16>
<COL WIDTH=56>
<COL WIDTH=42>
<COL WIDTH=92>
<COL WIDTH=11>
<COL WIDTH=28>
<COL WIDTH=21>
<COL WIDTH=43>
<COL WIDTH=12>
<COL WIDTH=184>
<COL>
<TR CLASS=R5>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Товарная&nbsp;накладная&nbsp;имеет&nbsp;приложение&nbsp;на</SPAN></TD>
<TD CLASS="R25C5"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R25C6"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R25C7"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R25C7"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R25C7"><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:16px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:16px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R18>
<TD><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><SPAN STYLE="white-space:nowrap;max-width:0px;">и&nbsp;содержит</SPAN></TD>
<TD CLASS="R26C3" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo num2str_simple($count_items); ?>&nbsp;</SPAN></TD>
<TD CLASS="R26C7"><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R26C8"><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R26C8"><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R26C8"><DIV STYLE="position:relative; height:15px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=8><SPAN STYLE="white-space:nowrap;max-width:0px;">порядковых&nbsp;номеров&nbsp;записей</SPAN></TD>
<TD><DIV STYLE="width:100%;height:15px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R4>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C0" COLSPAN=8><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C17" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R18>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C3" COLSPAN=2 ROWSPAN=3><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Масса&nbsp;груза&nbsp;(нетто)</SPAN></TD>
<TD CLASS="R28C8"><SPAN></SPAN></TD>
<TD CLASS="R28C8"><SPAN></SPAN></TD>
<TD CLASS="R28C8"><SPAN></SPAN></TD>
<TD CLASS="R28C8"><SPAN></SPAN></TD>
<TD CLASS="R28C8"><SPAN></SPAN></TD>
<TD CLASS="R28C8" COLSPAN=3><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C0" COLSPAN=8><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C17" ROWSPAN=2><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R18>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;мест</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Масса&nbsp;груза&nbsp;(брутто)</SPAN></TD>
<TD CLASS="R26C3"><SPAN></SPAN></TD>
<TD CLASS="R30C9" COLSPAN=8><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C0" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD CLASS="R4C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R4C0" COLSPAN=8><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD CLASS="R4C0"><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:11px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:11px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R32>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:7px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R33>
<TD><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Приложение&nbsp;(паспорта,&nbsp;сертификаты&nbsp;и&nbsp;т.п.)&nbsp;на&nbsp;</SPAN></TD>
<TD CLASS="R33C4"><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=2 STYLE="border-right: #333333 1px solid; "><SPAN STYLE="white-space:nowrap;max-width:0px;">листах</SPAN></TD>
<TD CLASS="R33C10" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">По&nbsp;доверенности&nbsp;№</SPAN></TD>
<TD CLASS="R33C4" COLSPAN=3><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R33C14"><SPAN STYLE="white-space:nowrap;max-width:0px;">от</SPAN></TD>
<TD CLASS="R33C4" COLSPAN=4><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:13px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R34>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R34C4" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R34C7"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:14px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R33>
<TD CLASS="R35C0"><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R35C1" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;отпущено&nbsp;&nbsp;на&nbsp;сумму</SPAN></TD>
<TD CLASS="R35C3" COLSPAN=5 STYLE="border-right: #333333 1px solid; "><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">выданной</SPAN></TD>
<TD CLASS="R35C11" COLSPAN=8><DIV STYLE="position:relative; height:13px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:13px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R18>
<TD CLASS="R36C0"><SPAN></SPAN></TD>
<TD CLASS="R36C1" COLSPAN=7 STYLE="border-right: #333333 1px solid; "><?php echo num2str($itogo_all_price_sum); ?></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">кем,&nbsp;кому&nbsp;(организация,&nbsp;должность,&nbsp;фамилия,&nbsp;и.&nbsp;о.)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R34>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">прописью</SPAN></TD>
<TD CLASS="R34C7"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C11"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R37C12"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:14px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R7>
<TD><SPAN></SPAN></TD>
<TD><SPAN STYLE="white-space:nowrap;max-width:0px;">Отпуск&nbsp;груза&nbsp;разрешил</SPAN></TD>
<TD CLASS="R38C2"><?php echo $parameters_values["shipping_allowed_by"]; ?></TD>
<TD CLASS="R38C3"><SPAN></SPAN></TD>
<td CLASS="R38C4">
	<?php
	//Скан подписи "Отпуск груза разрешил"
	if( $parameters_values["shipping_allowed_by_signature_scan"] != "" )
	{
		if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["shipping_allowed_by_signature_scan"]) )
		{
			?>
			<img src="/content/files/images/<?php echo $parameters_values["shipping_allowed_by_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
			<?php
		}
	}
	?>
</td>
<TD><SPAN></SPAN></TD>

<TD CLASS="R38C6" COLSPAN=2><?php echo $parameters_values["shipping_allowed_by_fio"]; ?></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R38C4" COLSPAN=8><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R39>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">должность</SPAN></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">подпись</SPAN></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C6" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">расшифровка&nbsp;подписи</SPAN></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C10"><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C11"><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C11"><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C11" COLSPAN=3><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C11"><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C11"><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:12px;overflow:hidden;"></DIV></TD>
</TR>

<TR CLASS=R18>
<TD CLASS="R40C0"><SPAN></SPAN></TD>
<TD CLASS="R40C0" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Главный&nbsp;(старший)&nbsp;бухгалтер</SPAN></TD>
<TD CLASS="R26C8">
	<SPAN>
		<?php
		//Скан подписи главного бухгалтера
		if( $parameters_values["accountant_general_signature_scan"] != "" )
		{
			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["accountant_general_signature_scan"]) )
			{
				?>
				<img src="/content/files/images/<?php echo $parameters_values["accountant_general_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
				<?php
			}
		}
		?>
	</SPAN>
</TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R40C6" COLSPAN=2><?php echo $parameters_values["accountant_general_fio"]; ?><SPAN><td CLASS="R38C4"></td></SPAN></TD>

<TD COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Груз&nbsp;принял</SPAN></TD>
<TD CLASS="R26C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C8" COLSPAN=3><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>

<TR CLASS=R18>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R39C2"><SPAN STYLE="white-space:nowrap;max-width:0px;"></SPAN></TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>


<TD CLASS="R30C16" COLSPAN=1>подпись</TD>
<TD><DIV STYLE="position:relative; height:12px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R41C6" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">расшифровка&nbsp;подписи</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16"><SPAN STYLE="white-space:nowrap;max-width:0px;">должность</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">подпись</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R41C17"><SPAN STYLE="white-space:nowrap;max-width:0px;">расшифровка&nbsp;подписи</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>



<TR CLASS=R18>
<TD><SPAN></SPAN></TD>
<TD><SPAN STYLE="white-space:nowrap;max-width:0px;">Отпуск&nbsp;груза&nbsp;произвел</SPAN></TD>
<TD CLASS="R42C2"><SPAN><?php echo $parameters_values["shipping_executed_by"]; ?></SPAN></TD>
<TD CLASS="R42C3"><SPAN></SPAN></TD>
<TD CLASS="R26C8">
	<SPAN>
		<?php
		//Скан подписи "Отпуск груза произвел"
		if( $parameters_values["shipping_executed_by_signature_scan"] != "" )
		{
			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["shipping_executed_by_signature_scan"]) )
			{
				?>
				<img src="/content/files/images/<?php echo $parameters_values["shipping_executed_by_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
				<?php
			}
		}
		?>
	</SPAN>
</TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R40C6" COLSPAN=2><SPAN><?php echo $parameters_values["shipping_executed_by_fio"]; ?></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Груз&nbsp;получил&nbsp;</SPAN></TD>
<TD CLASS="R26C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C8" COLSPAN=3><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R18>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16"><SPAN STYLE="white-space:nowrap;max-width:0px;">должность</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16"><SPAN STYLE="white-space:nowrap;max-width:0px;">подпись</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R41C6" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">расшифровка&nbsp;подписи</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R18C0" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">грузополучатель</SPAN></TD>
<TD CLASS="R30C16"><SPAN STYLE="white-space:nowrap;max-width:0px;">должность</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R30C16" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">подпись</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R41C17"><SPAN STYLE="white-space:nowrap;max-width:0px;">расшифровка&nbsp;подписи</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R32>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R44C7"><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:7px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R18>
<TD CLASS="R24C7" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px; position:relative;" id="mp">М.П.</SPAN></TD>
<TD CLASS="R24C7" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">"<?php echo date("d",$order_record["time"]); ?>"</SPAN></TD>
<TD CLASS="R26C8"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo get_month($order_record["time"]); ?></SPAN></TD>
<TD COLSPAN=3 STYLE="border-right: #333333 1px solid; "><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo date("Y",$order_record["time"]); ?>&nbsp;года</SPAN></TD>
<TD CLASS="R45C10" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;">М.П.</SPAN></TD>
<TD COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"&nbsp;_____________&nbsp;20&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;года</SPAN></TD>
<TD><DIV STYLE="width:100%;height:15px;overflow:hidden;"></DIV></TD>
</TR>
</TABLE>
<div style="" id="stamp">
	<?php
	//Скан печати
	if( $parameters_values["stamp_scan"] != "" )
	{
		if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["stamp_scan"]) )
		{
			?>
			<img src="/content/files/images/<?php echo $parameters_values["stamp_scan"]; ?>" style="max-width:150px;max-height:150px;" />
			<?php
		}
	}
	?>
</div>
<script>
var stamp_top = parseInt(document.getElementById("mp").offsetTop)-200;
document.getElementById("stamp").setAttribute("style", "position:absolute;left:22px; top:"+stamp_top+"px;");
</script>
</BODY>
</HTML>
<?php
$HTML = ob_get_contents();
ob_end_clean();
?>