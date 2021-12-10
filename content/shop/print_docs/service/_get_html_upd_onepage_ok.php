<?php
//Скрипт для формирования HTML для УПД по одному заказу
/*
В этом скрипте нет учета количества страниц. Если после продолжительного тестирования скрипта get_html_upd.php не возникнет ошибок, то, скрипт _get_html_upd_onepage_ok.php можно удалить
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
body { background: #ffffff; margin: 0; font-family: Arial; font-size: 8pt; font-style: normal;width:1000px; }
tr.R0{ font-family: Arial; font-size: 8pt; font-style: normal; height: 19px; }
tr.R0 td.R0C1{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: left; vertical-align: bottom; }
tr.R0 td.R0C10{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: right; vertical-align: top; overflow: hidden;}
tr.R0 td.R0C11{ text-align: center; }
tr.R0 td.R0C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R0 td.R0C5{ font-family: Arial; font-size: 9pt; font-style: normal; vertical-align: bottom; border-left: #ffffff 1px none; }
tr.R0 td.R0C6{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R0 td.R0C7{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; vertical-align: bottom; }
tr.R0 td.R0C9{ text-align: center; overflow: hidden;}
tr.R0 td.R1C5{ font-family: Arial; font-size: 9pt; font-style: normal; border-left: #ffffff 1px none; }
tr.R0 td.R1C6{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; border-bottom: #000000 1px solid; }
tr.R0 td.R1C7{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; }
tr.R0 td.R1C8{ font-family: Arial; font-size: 9pt; font-style: normal; text-align: center; border-bottom: #000000 1px solid; }
tr.R12{ font-family: Arial; font-size: 8pt; font-style: normal; height: 14px; }
tr.R12 td.R12C15{ text-align: center; vertical-align: bottom; overflow: hidden;}
tr.R12 td.R12C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R12 td.R12C5{ text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; }
tr.R12 td.R12C6{ text-align: left; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R14{ height: 5px; }
tr.R14 td.R14C3{ border-left: #000000 2px solid; }
tr.R14 td.R20C3{ border-left: #ffffff 1px none; }
tr.R14 td.R25C17{ text-align: center; vertical-align: middle; overflow: hidden;}
tr.R14 td.R25C5{ border-bottom: #000000 1px solid; }
tr.R14 td.R30C8{ text-align: right; overflow: hidden;}
tr.R15{ font-family: Arial; font-size: 8pt; font-style: normal; height: 43px; }
tr.R15 td.R15C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R15 td.R15C3{ text-align: center; vertical-align: middle; border-left: #000000 2px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R15 td.R15C8{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R15 td.R15C9{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R16{ font-family: Arial; font-size: 8pt; font-style: normal; height: 71px; }
tr.R16 td.R16C20{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R16 td.R16C9{ text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R18{ font-family: Arial; font-size: 8pt; font-style: normal; height: 29px; }
tr.R18 td.R18C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R18C11{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R18C16{ text-align: left; vertical-align: top; overflow: hidden;border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R18C2{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R18 td.R18C23{ font-family: Arial; font-size: 8pt; font-style: normal; vertical-align: top; }
tr.R18 td.R18C3
{ 
	text-align: left; 
	vertical-align: top; 
	border-left: #000000 2px solid; 
	border-top: #000000 1px solid; 
	border-bottom: #000000 1px solid; 
	border-right: #000000 1px solid;
	
	/*Стиль для колонки с наименованием - чтобы зафиксировать высоту для возможности точно посчитать количество страниц*/
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
tr.R18 td.R18C8{ text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R2{ font-family: Arial; font-size: 8pt; font-style: normal; height: 7px; }
tr.R2 td.R2C10{ text-align: center; }
tr.R2 td.R2C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R21{ font-family: Arial; font-size: 8pt; font-style: normal; height: 27px; }
tr.R21 td.R21C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; vertical-align: top; }
tr.R21 td.R21C2{ text-align: left; border-left: #000000 2px solid; }
tr.R21 td.R21C3{ text-align: left; overflow: hidden;border-left: #ffffff 1px none; }
tr.R21 td.R21C4{ border-bottom: #000000 1px solid; }
tr.R21 td.R21C8{ overflow: hidden;}
tr.R21 td.R23C9{ text-align: center; border-bottom: #000000 1px solid; }
tr.R22{ font-family: Arial; font-size: 8pt; font-style: normal; height: 10px; }
tr.R22 td.R22C2{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R22 td.R22C3{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #ffffff 1px none; }
tr.R22 td.R22C4{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; overflow: hidden;}
tr.R22 td.R24C13{ border-bottom: #ffffff 1px none; }
tr.R22 td.R24C2{ border-left: #000000 2px solid; border-bottom: #000000 2px solid; }
tr.R22 td.R24C3{ border-left: #ffffff 1px none; border-bottom: #000000 2px solid; }
tr.R22 td.R24C4{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; overflow: hidden;border-bottom: #000000 2px solid; }
tr.R22 td.R24C5{ border-bottom: #000000 2px solid; }
tr.R26{ height: 15px; }
tr.R26 td.R26C1{ font-family: Arial; font-size: 8pt; font-style: normal; overflow: hidden;}
tr.R26 td.R28C1{ overflow: hidden;border-bottom: #ffffff 1px none; }
tr.R26 td.R28C17{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; vertical-align: top; overflow: hidden;}
tr.R26 td.R28C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-bottom: #000000 1px solid; }
tr.R26 td.R31C1{ overflow: hidden;}
tr.R26 td.R31C10{ border-left: #000000 2px solid; }
tr.R26 td.R31C8{ text-align: right; overflow: hidden;}
tr.R26 td.R32C12{ font-family: Arial; font-size: 8pt; font-style: normal; }
tr.R26 td.R32C8{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: right; vertical-align: top; overflow: hidden;}
tr.R26 td.R34C13{ overflow: hidden;border-bottom: #000000 1px solid; }
tr.R26 td.R34C15{ border-bottom: #ffffff 1px none; }
tr.R26 td.R34C17{ text-align: right; overflow: hidden;border-bottom: #ffffff 1px none; }
tr.R26 td.R34C4{ font-family: Arial; font-size: 8pt; font-style: normal; overflow: hidden;border-bottom: #000000 1px solid; }
tr.R26 td.R39C17{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: right; overflow: hidden;}
tr.R26 td.R43C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: left; border-bottom: #000000 1px solid; }
tr.R26 td.R45C1{ text-align: center; overflow: hidden;}
tr.R26 td.R45C11{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; overflow: hidden;}
tr.R27{ height: 10px; }
tr.R27 td.R27C5{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: top; overflow: hidden;}
tr.R27 td.R33C10{ border-left: #000000 2px solid; }
tr.R27 td.R33C12{ font-family: Arial; font-size: 8pt; font-style: normal; }
tr.R3{ font-family: Arial; font-size: 8pt; font-style: normal; height: 15px; }
tr.R3 td.R10C6{ text-align: left; vertical-align: top; border-bottom: #000000 1px solid; }
tr.R3 td.R11C6{ text-align: left; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R3 td.R13C12{ font-family: Arial; font-size: 8pt; font-style: normal; border-bottom: #000000 1px solid; }
tr.R3 td.R13C15{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; }
tr.R3 td.R13C16{ text-align: left; vertical-align: bottom; }
tr.R3 td.R13C3{ border-right: #ffffff 1px none; }
tr.R3 td.R13C4{ border-left: #000000 2px solid; }
tr.R3 td.R17C1{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R17C19{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R3 td.R17C3{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 2px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R17C8{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R3 td.R17C9{ font-family: Arial; font-size: 6pt; font-style: normal; text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R19C1{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R19C15{ text-align: right; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R3 td.R19C16{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; text-align: center; overflow: hidden;border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R3 td.R19C2{ font-family: Arial; font-size: 8pt; font-style: normal; border-bottom: #000000 1px solid; }
tr.R3 td.R19C20{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R19C21{ font-family: Arial; font-size: 8pt; font-style: normal; border-top: #000000 1px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R19C22{ font-family: Arial; font-size: 8pt; font-style: normal; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }
tr.R3 td.R19C3{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; overflow: hidden;border-left: #000000 2px solid; border-bottom: #000000 1px solid; }
tr.R3 td.R19C8{ border-bottom: #000000 1px solid; }
tr.R3 td.R3C15{ text-align: center; overflow: hidden;}
tr.R3 td.R3C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R3 td.R3C5{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; overflow: hidden;border-left: #ffffff 1px none; }
tr.R3 td.R3C6{ text-align: left; border-left: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R3 td.R5C5{ text-align: left; vertical-align: top; border-left: #ffffff 1px none; }
tr.R3 td.R5C6{ text-align: left; border-bottom: #000000 1px solid; }
tr.R3 td.R9C15{ text-align: center; vertical-align: bottom; overflow: hidden;}
tr.R3 td.R9C5{ font-family: Arial; font-size: 8pt; font-style: normal; font-weight: bold; overflow: hidden;}
tr.R3 td.R9C6{ text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R4{ font-family: Arial; font-size: 8pt; font-style: normal; height: 17px; }
tr.R4 td.R4C1{ text-align: center; vertical-align: middle; overflow: hidden;}
tr.R4 td.R4C15{ text-align: center; overflow: hidden;}
tr.R4 td.R4C2{ text-align: center; vertical-align: middle; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 2px solid; border-bottom: #000000 2px solid; border-right: #000000 2px solid; }
tr.R4 td.R4C3{ font-family: Arial; font-size: 10pt; font-style: normal; text-align: left; vertical-align: top; }
tr.R4 td.R4C4{ font-family: Arial; font-size: 8pt; font-style: normal; border-left: #000000 2px solid; }
tr.R4 td.R4C5{ text-align: left; vertical-align: top; border-left: #ffffff 1px none; }
tr.R4 td.R4C6{ text-align: left; border-left: #ffffff 1px none; border-bottom: #000000 1px solid; }
tr.R4 td.R6C1{ font-family: Arial; font-size: 6pt; font-style: normal; vertical-align: top; }
tr.R4 td.R6C15{ text-align: center; vertical-align: bottom; overflow: hidden;}
tr.R4 td.R6C5{ text-align: left; vertical-align: bottom; border-left: #ffffff 1px none; }
tr.R4 td.R6C6{ text-align: left; vertical-align: bottom; border-bottom: #000000 1px solid; }
tr.R40{ height: 29px; }
tr.R40 td.R40C1{ font-family: Arial; font-size: 8pt; font-style: normal; border-bottom: #000000 1px solid; }
tr.R40 td.R40C10{ border-left: #000000 2px solid; }
tr.R40 td.R40C2{ font-family: Arial; font-size: 8pt; font-style: normal; }
tr.R40 td.R40C8{ font-family: Arial; font-size: 8pt; font-style: normal; text-align: right; vertical-align: top; overflow: hidden;}
table {table-layout: fixed; padding: 0px; padding-left: 2px; vertical-align:bottom; border-collapse:collapse;width: 100%; font-family: Arial; font-size: 8pt; font-style: normal; }
td { padding: 0px; padding-left: 2px; overflow:hidden; }
</STYLE>
</HEAD>
<BODY STYLE="background: #ffffff; margin: 0; font-family: Arial; font-size: 8pt; font-style: normal; ">
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=66>
<COL WIDTH=18>
<COL WIDTH=14>
<COL WIDTH=14>
<COL WIDTH=116>
<COL WIDTH=108>
<COL WIDTH=28>
<COL WIDTH=133>
<COL WIDTH=26>
<COL WIDTH=558>
<COL WIDTH=7>
<COL>
<TR CLASS=R0>
<TD><SPAN></SPAN></TD>
<TD CLASS="R0C1" COLSPAN=3 ROWSPAN=3>Универсальный передаточный<BR>документ</TD>
<TD CLASS="R0C4"><SPAN></SPAN></TD>
<TD CLASS="R0C5">Счет-фактура №</TD>
<TD CLASS="R0C6"><?php echo $order_id; ?></TD>
<TD CLASS="R0C7">от</TD>
<TD CLASS="R0C6"><?php echo date("d", $order_record["time"]); ?> <?php echo get_month($order_record["time"]); ?> <?php echo date("Y", $order_record["time"]); ?> г.</TD>
<TD CLASS="R0C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">(1)</SPAN></TD>
<TD CLASS="R0C10" ROWSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Приложение&nbsp;№&nbsp;1&nbsp;к&nbsp;постановлению&nbsp;Правительства&nbsp;Российской&nbsp;Федерации&nbsp;от&nbsp;26&nbsp;декабря&nbsp;2011&nbsp;г.&nbsp;№&nbsp;1137<BR>(в&nbsp;редакции&nbsp;постановления&nbsp;Правительства&nbsp;Российской&nbsp;Федерации&nbsp;от&nbsp;19&nbsp;августа&nbsp;2017&nbsp;г.&nbsp;№&nbsp;981)</SPAN></TD>
<TD CLASS="R0C11"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R0>
<TD><SPAN></SPAN></TD>
<TD CLASS="R0C4"><SPAN></SPAN></TD>
<TD CLASS="R1C5">Исправление №</TD>
<TD CLASS="R1C6">--</TD>
<TD CLASS="R1C7"><SPAN STYLE="white-space:nowrap;max-width:0px;">от</SPAN></TD>
<TD CLASS="R1C8"><SPAN STYLE="white-space:nowrap;max-width:0px;">--</SPAN></TD>
<TD CLASS="R0C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">(1а)</SPAN></TD>
<TD CLASS="R0C11"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R2>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R2C4"><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R2C10"><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:7px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:7px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=66>
<COL WIDTH=18>
<COL WIDTH=14>
<COL WIDTH=13>
<COL WIDTH=210>
<COL WIDTH=89>
<COL WIDTH=45>
<COL WIDTH=22>
<COL WIDTH=23>
<COL WIDTH=44>
<COL WIDTH=22>
<COL WIDTH=23>
<COL WIDTH=89>
<COL WIDTH=383>
<COL WIDTH=27>
<COL WIDTH=1>
<COL>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R3C4"><SPAN></SPAN></TD>
<TD CLASS="R3C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">Продавец:</SPAN></TD>
<TD CLASS="R3C6" COLSPAN=9><?php echo $parameters_values["seller_2"]; ?></TD>
<TD CLASS="R3C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(2)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><SPAN></SPAN></TD>
<TD CLASS="R4C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">&nbsp;&nbsp;&nbsp;&nbsp;Статус:</SPAN></TD>
<TD CLASS="R4C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">2</SPAN></TD>
<TD CLASS="R4C3"><SPAN></SPAN></TD>
<TD CLASS="R4C4"><SPAN></SPAN></TD>
<TD CLASS="R4C5">Адрес:</TD>
<TD CLASS="R4C6" COLSPAN=9><?php echo $parameters_values["address_2a"]; ?></TD>
<TD CLASS="R4C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(2а)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R3C4"><SPAN></SPAN></TD>
<TD CLASS="R5C5">ИНН/КПП продавца:</TD>
<TD CLASS="R5C6" COLSPAN=9><?php echo $parameters_values["seller_inn_kpp_2b"]; ?></TD>
<TD CLASS="R3C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(2б)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><SPAN></SPAN></TD>
<TD CLASS="R6C1" COLSPAN=3 ROWSPAN=3>1 – счет-фактура и передаточный документ (акт)<BR>2 – передаточный документ (акт)</TD>
<TD CLASS="R4C4"><SPAN></SPAN></TD>
<TD CLASS="R6C5">Грузоотправитель и его адрес:</TD>
<TD CLASS="R6C6" COLSPAN=9>--</TD>
<TD CLASS="R6C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(3)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><SPAN></SPAN></TD>
<TD CLASS="R4C4"><SPAN></SPAN></TD>
<TD CLASS="R6C5">Грузополучатель и его адрес:</TD>
<TD CLASS="R6C6" COLSPAN=9>--</TD>
<TD CLASS="R6C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(4)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R4>
<TD><SPAN></SPAN></TD>
<TD CLASS="R4C4"><SPAN></SPAN></TD>
<TD CLASS="R6C5">К платежно-расчетному документу №</TD>
<TD CLASS="R6C6" COLSPAN=9>-- от --</TD>
<TD CLASS="R6C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(5)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R3C4"><SPAN></SPAN></TD>
<TD CLASS="R9C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">Покупатель:</SPAN></TD>
<TD CLASS="R9C6" COLSPAN=9><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["customer_6"]); ?></TD>
<TD CLASS="R9C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(6)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R3C4"><SPAN></SPAN></TD>
<TD CLASS="R5C5">Адрес:</TD>
<TD CLASS="R10C6" COLSPAN=9><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["address_6a"]); ?></TD>
<TD CLASS="R9C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(6а)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R3C4"><SPAN></SPAN></TD>
<TD CLASS="R5C5">ИНН/КПП покупателя:</TD>
<TD CLASS="R11C6" COLSPAN=9><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["customer_inn_kpp_6b"]); ?></TD>
<TD CLASS="R9C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(6б)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R12>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R12C4"><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R12C5">Валюта: наименование, код</TD>
<TD CLASS="R12C6" COLSPAN=9>Российский рубль, 643</TD>
<TD CLASS="R12C15"><SPAN STYLE="white-space:nowrap;max-width:0px;">(7)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:14px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:14px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R13C3"><SPAN></SPAN></TD>
<TD CLASS="R13C4"><SPAN></SPAN></TD>
<TD CLASS="R5C5" COLSPAN=7>Идентификатор государственного контракта, договора (соглашения) (при наличии):</TD>
<TD CLASS="R13C12" COLSPAN=3><SPAN></SPAN></TD>
<TD CLASS="R13C15" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">(8)</SPAN></TD>
<TD></TD>
</TR>
</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=28>
<COL WIDTH=70>
<COL WIDTH=13>
<COL WIDTH=11>
<COL WIDTH=21>
<COL WIDTH=42>
<COL WIDTH=55>
<COL WIDTH=70>
<COL WIDTH=24>
<COL WIDTH=55>
<COL WIDTH=58>
<COL WIDTH=15>
<COL WIDTH=16>
<COL WIDTH=57>
<COL WIDTH=86>
<COL WIDTH=41>
<COL WIDTH=67>
<COL WIDTH=74>
<COL WIDTH=86>
<COL WIDTH=32>
<COL WIDTH=65>
<COL WIDTH=96>
<COL WIDTH=7>
<COL>
<TR CLASS=R14>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C3" COLSPAN=5><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD COLSPAN=3><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:5px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R15>
<TD><SPAN></SPAN></TD>
<TD CLASS="R15C1" ROWSPAN=2>№ п/п</TD>
<TD CLASS="R15C1" ROWSPAN=2>Код товара/ работ, услуг</TD>
<TD CLASS="R15C3" COLSPAN=5 ROWSPAN=2>Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</TD>
<TD CLASS="R15C8" ROWSPAN=2>Код вида<BR>товара</TD>
<TD CLASS="R15C9" COLSPAN=2>Единица<BR>измерения</TD>
<TD CLASS="R15C9" ROWSPAN=2>Коли-<BR>чество <BR>(объем)</TD>
<TD CLASS="R15C9" COLSPAN=3 ROWSPAN=2>Цена (тариф)<BR>за<BR>единицу измерения</TD>
<TD CLASS="R15C9" ROWSPAN=2>Стоимость товаров (работ, услуг), имущест-<BR>венных прав без налога - всего</TD>
<TD CLASS="R15C9" ROWSPAN=2>В том<BR>числе<BR>сумма <BR>акциза</TD>
<TD CLASS="R15C9" ROWSPAN=2>Налоговая ставка</TD>
<TD CLASS="R15C9" ROWSPAN=2>Сумма налога, предъяв-<BR>ляемая покупателю</TD>
<TD CLASS="R15C8" ROWSPAN=2>Стоимость товаров (работ, услуг), имущест-<BR>венных прав с налогом - всего</TD>
<TD CLASS="R15C8" COLSPAN=2>Страна<BR>происхождения товара</TD>
<TD CLASS="R15C8" ROWSPAN=2>Регистрационный номер<BR>таможенной<BR>декларации</TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R16>
<TD><SPAN></SPAN></TD>
<TD CLASS="R16C9">код</TD>
<TD CLASS="R16C9">условное обозна-<BR>чение (нацио-<BR>нальное)</TD>
<TD CLASS="R16C20">циф-<BR>ро-<BR>вой код</TD>
<TD CLASS="R16C20">краткое наиме-<BR>нование</TD>
<TD COLSPAN=2><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD CLASS="R17C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">А</SPAN></TD>
<TD CLASS="R17C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">Б</SPAN></TD>
<TD CLASS="R17C3" COLSPAN=5><SPAN STYLE="white-space:nowrap;max-width:0px;">1</SPAN></TD>
<TD CLASS="R17C8"><SPAN STYLE="white-space:nowrap;max-width:0px;">1а</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">2</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">2а</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">3</SPAN></TD>
<TD CLASS="R17C9" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">4</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">5</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">6</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">7</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">8</SPAN></TD>
<TD CLASS="R17C19"><SPAN STYLE="white-space:nowrap;max-width:0px;">9</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">10</SPAN></TD>
<TD CLASS="R17C9"><SPAN STYLE="white-space:nowrap;max-width:0px;">10а</SPAN></TD>
<TD CLASS="R17C19"><SPAN STYLE="white-space:nowrap;max-width:0px;">11</SPAN></TD>
<TD><SPAN></SPAN></TD>
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
$SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, $SELECT_product_name AS `product_name`, $SELECT_item_price_sum AS `price_sum` FROM `shop_orders_items` WHERE `order_id` = ? AND `status` IN (SELECT `id` FROM `shop_orders_items_statuses_ref` WHERE `count_flag` = ?);";


// --------------------------------------------------------------------
/*
БЛОК ДЛЯ ТЕСТИРОВАНИЯ:
- раскомментить
- указать нужное количество строк в $need_count_rows_for_test
*/
//Тестовый запрос
$need_count_rows_for_test = 1;//Какое количество строк вывести для теста
$SELECT_ORDER_ITEMS_SUB = " SELECT 'Название товара' AS `product_name`, '5' AS `count_need`, '5000' AS `price`, '25000' AS `price_sum` FROM `shop_orders_items` WHERE `id` = 5 ";
$SELECT_ORDER_ITEMS = "";
for($i=0; $i < $need_count_rows_for_test; $i++)
{
	if( $i > 0 )
	{
		$SELECT_ORDER_ITEMS = $SELECT_ORDER_ITEMS . " UNION ALL " . $SELECT_ORDER_ITEMS_SUB;
	}
	else
	{
		$SELECT_ORDER_ITEMS = $SELECT_ORDER_ITEMS . $SELECT_ORDER_ITEMS_SUB;
	}
}
$SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS * FROM (".$SELECT_ORDER_ITEMS.") AS t1";

// --------------------------------------------------------------------


$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute( array($order_id, 1) );


$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
$elements_count_rows_query->execute();
$elements_count_rows = $elements_count_rows_query->fetchColumn();


$count_items = 0;//Счетчик позиций
$price_sum_total_num = 0;//Сумма ИТОГО (цифра без форматирования)


//$itogo_page_price_sum_without_NDS = 0;//ИТОГО сумма без НДС (по таблице на текущей странице)
$itogo_all_price_sum_without_NDS = 0;//ИТОГО сумма без НДС (по всей УПД)

//$itogo_page_count_need = 0;//ИТОГО количество (по таблице на текущей странице)
$itogo_all_count_need = 0;//ИТОГО количество (по всей УПД)

//$itogo_page_NDS = 0;//ИТОГО сумма НДС (по таблице на текущей странице)
$itogo_all_NDS = 0;//ИТОГО сумма НДС (по всей УПД)

//$itogo_page_price_sum = 0;//ИТОГО сумма с НДС (по таблице на текущей странице)
$itogo_all_price_sum = 0;//ИТОГО сумма с НДС (по всей УПД)

/*
//Номера позиций для управления выводом страниц
$pos_start = 1;//На этой позиции начинаем новую страницу
$pos_end_table = 0;//На этой позиции заканчиваем страницу
$pos_end_all = 0;//На этой позиции заканчиваем накладную
*/

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
		$NDS_mark = "--";//Обозначение НДС
		
		$price_without_NDS = $order_item["price"];//Цена Без НДС
		$NDS = "";//Сумма НДС
		$price_sum_without_NDS = $price_without_NDS*$order_item["count_need"];//Сумма без НДС
	}
	
	//ИТОГО:
	//$itogo_page_price_sum_without_NDS = $itogo_page_price_sum_without_NDS + $price_sum_without_NDS;
	$itogo_all_price_sum_without_NDS = $itogo_all_price_sum_without_NDS + $price_sum_without_NDS;
	//$itogo_page_count_need = $itogo_page_count_need + $order_item["count_need"];
	$itogo_all_count_need = $itogo_all_count_need + $order_item["count_need"];
	if( $NDS == "" )
	{
		//$itogo_page_NDS = "";
		$itogo_all_NDS = "";
	}
	else
	{
		//$itogo_page_NDS = $itogo_page_NDS + $NDS;
		$itogo_all_NDS = $itogo_all_NDS + $NDS;
	}
	//$itogo_page_price_sum = $itogo_page_price_sum + $order_item["price_sum"];
	$itogo_all_price_sum = $itogo_all_price_sum + $order_item["price_sum"];
	
	?>
	<TR CLASS=R18>
	<TD><SPAN></SPAN></TD>
	<TD CLASS="R18C1"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $count_items; ?></SPAN></TD>
	<TD CLASS="R18C2">00-00000001</TD>
	<TD CLASS="R18C3" COLSPAN=5><SPAN><?php echo $order_item["product_name"]; ?></SPAN></TD>
	<TD CLASS="R18C8">--</TD>
	<TD CLASS="R18C8">796</TD>
	<TD CLASS="R18C8">шт</TD>
	<TD CLASS="R18C11"><?php echo $order_item["count_need"]; ?></TD>
	<TD CLASS="R18C11" COLSPAN=3><?php echo print_price($price_without_NDS); ?></TD>
	<TD CLASS="R18C11"><?php echo print_price($price_sum_without_NDS); ?></TD>
	<TD CLASS="R18C16"><SPAN STYLE="white-space:nowrap;max-width:0px;">без<BR>акциза</SPAN></TD>
	<TD CLASS="R18C8"><?php echo $NDS_mark; ?></TD>
	<TD CLASS="R18C11"><?php echo print_price($NDS); ?></TD>
	<TD CLASS="R18C11"><?php echo print_price($order_item["price_sum"]); ?></TD>
	<TD CLASS="R18C8">--</TD>
	<TD CLASS="R18C8">--</TD>
	<TD CLASS="R18C8">--</TD>
	<TD CLASS="R18C23"><SPAN></SPAN></TD>
	<TD><SPAN></SPAN></TD>
	<TD></TD>
	</TR>
	<?php
}
?>






<TR CLASS=R3>
<TD><SPAN></SPAN></TD>
<TD CLASS="R19C1"><SPAN></SPAN></TD>
<TD CLASS="R19C2"><SPAN></SPAN></TD>
<TD CLASS="R19C3" COLSPAN=5><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;к&nbsp;оплате</SPAN></TD>
<TD CLASS="R19C8"><SPAN></SPAN></TD>
<TD CLASS="R19C8"><SPAN></SPAN></TD>
<TD CLASS="R19C8"><SPAN></SPAN></TD>
<TD CLASS="R19C8"><SPAN></SPAN></TD>
<TD CLASS="R19C8" COLSPAN=3><SPAN></SPAN></TD>
<TD CLASS="R19C15"><?php echo print_price($itogo_all_price_sum_without_NDS); ?></TD>
<TD CLASS="R19C16" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Х</SPAN></TD>
<TD CLASS="R19C15"><?php echo print_price($itogo_all_NDS); ?></TD>
<TD CLASS="R19C15"><?php echo print_price($price_sum_total_num); ?></TD>
<TD CLASS="R19C20"><SPAN></SPAN></TD>
<TD CLASS="R19C21"><SPAN></SPAN></TD>
<TD CLASS="R19C22"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>



</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=7>
<COL WIDTH=98>
<COL WIDTH=16>
<COL WIDTH=189>
<COL WIDTH=122>
<COL WIDTH=11>
<COL WIDTH=161>
<COL WIDTH=9>
<COL WIDTH=35>
<COL WIDTH=130>
<COL WIDTH=122>
<COL WIDTH=11>
<COL WIDTH=179>
<COL WIDTH=6>
<COL>
<TR CLASS=R14>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C3"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R20C3"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:5px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R21>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C1" ROWSPAN=4>Документ составлен на<BR>1 листе</TD>
<TD CLASS="R21C2"><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">Руководитель&nbsp;организации<BR>или&nbsp;иное&nbsp;уполномоченное&nbsp;лицо</SPAN></TD>

<TD CLASS="R21C4">
	<DIV STYLE="height:27px;width: 100%; overflow:hidden; text-align:center;">
		<SPAN>
			<?php
			//Руководитель организации или иное уполномоченное лицо - Скан подписи
			if( $parameters_values["director_signature_scan"] != "" )
			{
				if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["director_signature_scan"]) )
				{
					?>
					<img src="/content/files/images/<?php echo $parameters_values["director_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
					<?php
				}
			}
			?>
		</SPAN>
	</DIV>
</TD>

<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>


<TD CLASS="R21C4">
	<DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;">
		<SPAN><?php echo $parameters_values["director_fio"]; ?></SPAN>
	</DIV>
</TD>

<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C8" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">Главный&nbsp;бухгалтер<BR>или&nbsp;иное&nbsp;уполномоченное&nbsp;лицо</SPAN></TD>


<TD CLASS="R21C4">
	<DIV STYLE="height:27px;width: 100%; overflow:hidden; text-align:center;">
		<SPAN>			
			<?php
			//Главный бухгалтер или иное уполномоченное лицо - Скан подписи
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
	</DIV>
</TD>


<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R21C4">
	<DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;">
		<SPAN>
			<?php echo $parameters_values["accountant_general_fio"]; ?>
		</SPAN>
	</DIV>
</TD>


<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:27px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R22>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C2"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C3"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R21>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C2"><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C3"><SPAN STYLE="white-space:nowrap;max-width:0px;">Индивидуальный&nbsp;предприниматель<BR>или&nbsp;иное&nbsp;уполномоченное&nbsp;лицо</SPAN></TD>

<TD CLASS="R21C4">
	<DIV STYLE="height:27px;width: 100%; overflow:hidden; text-align:center;">
		<SPAN>
			<?php
			//Индивидуальный предприниматель или иное уполномоченное лицо - Скан подписи
			if( $parameters_values["ip_signature_scan"] != "" )
			{
				if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["ip_signature_scan"]) )
				{
					?>
					<img src="/content/files/images/<?php echo $parameters_values["ip_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
					<?php
				}
			}
			?>
		</SPAN>
	</DIV>
</TD>

<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R21C4"><?php echo $parameters_values["ip_fio"]; ?></TD>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R23C9" COLSPAN=4><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:27px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:27px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R22>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C2"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C3"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD CLASS="R24C5"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C4"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD CLASS="R24C5"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C5"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R24C4" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;">(реквизиты&nbsp;свидетельства&nbsp;о&nbsp;государственной&nbsp;&nbsp;регистрации&nbsp;индивидуального&nbsp;предпринимателя)</SPAN></TD>
<TD CLASS="R24C13"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
</TABLE>
<TABLE style="width:100%; height:0px; " CELLSPACING=0>
<COL WIDTH=11>
<COL WIDTH=154>
<COL WIDTH=10>
<COL WIDTH=21>
<COL WIDTH=91>
<COL WIDTH=56>
<COL WIDTH=11>
<COL WIDTH=168>
<COL WIDTH=24>
<COL WIDTH=7>
<COL WIDTH=7>
<COL WIDTH=154>
<COL WIDTH=11>
<COL WIDTH=91>
<COL WIDTH=56>
<COL WIDTH=10>
<COL WIDTH=186>
<COL WIDTH=25>
<COL WIDTH=3>
<COL>
<TR CLASS=R14>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R25C5" COLSPAN=12 ROWSPAN=2><?php echo $parameters_values["basis_of_payment"]; ?></TD>
<TD CLASS="R25C17" ROWSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">[8]</SPAN></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:5px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R26C1" COLSPAN=4><SPAN STYLE="white-space:nowrap;max-width:0px;">Основание&nbsp;передачи&nbsp;(сдачи)&nbsp;/&nbsp;получения&nbsp;(приемки)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=12><SPAN STYLE="white-space:nowrap;max-width:0px;">(договор;&nbsp;доверенность&nbsp;и&nbsp;др.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R28C1" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Данные&nbsp;о&nbsp;транспортировке&nbsp;и&nbsp;грузе</SPAN></TD>
<TD CLASS="R28C4" COLSPAN=13><SPAN></SPAN></TD>
<TD CLASS="R28C17" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[9]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=13><SPAN STYLE="white-space:nowrap;max-width:0px;">(транспортная&nbsp;накладная,&nbsp;поручение&nbsp;экспедитору,&nbsp;экспедиторская&nbsp;/&nbsp;складская&nbsp;расписка&nbsp;и&nbsp;др.&nbsp;/&nbsp;масса&nbsp;нетто/&nbsp;брутто&nbsp;груза,&nbsp;если&nbsp;не&nbsp;приведены&nbsp;ссылки&nbsp;на&nbsp;транспортные&nbsp;документы,&nbsp;содержащие&nbsp;эти&nbsp;сведения)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R14>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R30C8"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R20C3"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R30C8"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:5px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Товар&nbsp;(груз)&nbsp;передал&nbsp;/&nbsp;услуги,&nbsp;результаты&nbsp;работ,&nbsp;права&nbsp;сдал</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">Товар&nbsp;(груз)&nbsp;получил&nbsp;/&nbsp;услуги,&nbsp;результаты&nbsp;работ,&nbsp;права&nbsp;принял</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>

<TD CLASS="R28C4">
	<SPAN>
		<?php
		//Товар (груз) передал / услуги, результаты работ, права сдал (10) - Должность
		?>
		<?php echo $parameters_values["goods_transferred_by_state"]; ?>
	</SPAN>
</TD>

<TD><SPAN></SPAN></TD>

<TD CLASS="R28C4" COLSPAN=3 style="text-align:center;">
	<SPAN>
		<?php
		//Товар (груз) передал / услуги, результаты работ, права сдал (10) - Скан подписи
		if( $parameters_values["goods_transferred_by_signature_scan"] != "" )
		{
			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["goods_transferred_by_signature_scan"]) )
			{
				?>
				<img src="/content/files/images/<?php echo $parameters_values["goods_transferred_by_signature_scan"]; ?>" style="max-height:30px;max-width:50px;" />
				<?php
			}
		}
		?>
	</SPAN>
</TD>

<TD><SPAN></SPAN></TD>
<TD CLASS="R28C4"><?php echo $parameters_values["goods_transferred_by_fio"]; ?></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[10]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R28C4"><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R28C4" COLSPAN=2><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R28C4"><SPAN></SPAN></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[15]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(должность)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R33C10"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(должность)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">Дата&nbsp;отгрузки,&nbsp;передачи&nbsp;(сдачи)</SPAN></TD>
<TD CLASS="R34C4" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">«&nbsp;<?php echo date("d", $order_record["time"]); ?>&nbsp;»&nbsp;&nbsp;&nbsp;&nbsp;<?php echo get_month($order_record["time"]); ?>&nbsp;&nbsp;&nbsp;&nbsp;<?php echo date("Y", $order_record["time"]); ?>&nbsp;&nbsp;года</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C8"><SPAN STYLE="white-space:nowrap;max-width:0px;">[11]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R31C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">Дата&nbsp;получения&nbsp;(приемки)</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R34C13" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">«&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;»&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;20&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;года</SPAN></TD>
<TD CLASS="R34C15"><SPAN></SPAN></TD>
<TD CLASS="R34C15"><SPAN></SPAN></TD>
<TD CLASS="R34C17"><SPAN STYLE="white-space:nowrap;max-width:0px;">[16]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R14>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R30C8"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R14C3"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R30C8"><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:5px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:5px;overflow:hidden;">&nbsp;</DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Иные&nbsp;сведения&nbsp;об&nbsp;отгрузке,&nbsp;передаче</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">Иные&nbsp;сведения&nbsp;о&nbsp;получении,&nbsp;приемке</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R28C4" COLSPAN=7><SPAN></SPAN></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[12]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R28C4" COLSPAN=6><SPAN></SPAN></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[17]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">(ссылки&nbsp;на&nbsp;неотъемлемые&nbsp;приложения,&nbsp;сопутствующие&nbsp;документы,&nbsp;иные&nbsp;документы&nbsp;и&nbsp;т.п.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R33C10"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">(информация&nbsp;о&nbsp;наличии/отсутствии&nbsp;претензии;&nbsp;ссылки&nbsp;на&nbsp;неотъемлемые&nbsp;приложения,&nbsp;и&nbsp;другие&nbsp;&nbsp;документы&nbsp;и&nbsp;т.п.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Ответственный&nbsp;за&nbsp;правильность&nbsp;оформления&nbsp;факта&nbsp;хозяйственной&nbsp;жизни</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R26C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">Ответственный&nbsp;за&nbsp;правильность&nbsp;оформления&nbsp;факта&nbsp;хозяйственной&nbsp;жизни</SPAN></TD>
<TD CLASS="R39C17"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R40>
<TD><SPAN></SPAN></TD>
<TD CLASS="R40C1"><?php echo $parameters_values["responsible_state"]; ?></TD>
<TD CLASS="R40C2"><SPAN></SPAN></TD>

<TD CLASS="R40C1" COLSPAN=3 style="text-align:center;">
	<SPAN>
		<?php
		//Ответственный за правильность оформления факта хозяйственной жизни (13) - Скан подписи
		if( $parameters_values["responsible_signature_scan"] != "" )
		{
			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["responsible_signature_scan"]) )
			{
				?>
				<img src="/content/files/images/<?php echo $parameters_values["responsible_signature_scan"]; ?>" style="max-height:50px;max-width:70px;" />
				<?php
			}
		}
		?>
	</SPAN>
</TD>

<TD CLASS="R40C2"><SPAN></SPAN></TD>
<TD CLASS="R40C1"><?php echo $parameters_values["responsible_fio"]; ?></TD>
<TD CLASS="R40C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[13]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R40C10"><SPAN></SPAN></TD>
<TD CLASS="R40C1"><SPAN></SPAN></TD>
<TD CLASS="R40C2"><SPAN></SPAN></TD>
<TD CLASS="R40C1" COLSPAN=2><SPAN></SPAN></TD>
<TD CLASS="R40C2"><SPAN></SPAN></TD>
<TD CLASS="R40C1"><SPAN></SPAN></TD>
<TD CLASS="R40C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[18]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(должность)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=3><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R33C10"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(должность)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">(подпись)</SPAN></TD>
<TD CLASS="R33C12"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5"><SPAN STYLE="white-space:nowrap;max-width:0px;">(ф.и.о.)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Наименование&nbsp;экономического&nbsp;субъекта&nbsp;–&nbsp;составителя&nbsp;документа&nbsp;(в&nbsp;т.ч.&nbsp;комиссионера&nbsp;/&nbsp;агента)</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R31C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">Наименование&nbsp;экономического&nbsp;субъекта&nbsp;–&nbsp;составителя&nbsp;документа</SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R43C1" COLSPAN=7><?php echo $parameters_values["econonomic_subject_14"]; ?></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[14]</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R43C1" COLSPAN=6><?php echo get_user_str_by_user_profile_json_builder($order_record["user_id"], $parameters_values["econonomic_subject_19"]); ?></TD>
<TD CLASS="R32C8" ROWSPAN=2><SPAN STYLE="white-space:nowrap;max-width:0px;">[19]</SPAN></TD>
<TD COLSPAN=2><SPAN></SPAN></TD>
<TD></TD>
</TR>
<TR CLASS=R27>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">(может&nbsp;не&nbsp;заполняться&nbsp;при&nbsp;проставлении&nbsp;печати&nbsp;в&nbsp;М.П.,&nbsp;может&nbsp;быть&nbsp;указан&nbsp;ИНН&nbsp;/&nbsp;КПП)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R33C10"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD CLASS="R27C5" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;">(может&nbsp;не&nbsp;заполняться&nbsp;при&nbsp;проставлении&nbsp;печати&nbsp;в&nbsp;М.П.,&nbsp;может&nbsp;быть&nbsp;указан&nbsp;ИНН&nbsp;/&nbsp;КПП)</SPAN></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>
<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;"></DIV></TD>
</TR>
<TR CLASS=R26>
<TD><SPAN></SPAN></TD>
<TD CLASS="R45C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">М.П.</SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C8"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD CLASS="R31C10"><SPAN></SPAN></TD>
<TD CLASS="R45C11"><SPAN STYLE="white-space:nowrap;max-width:0px;">М.П.</SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R32C12"><SPAN></SPAN></TD>
<TD CLASS="R39C17"><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD><SPAN></SPAN></TD>
<TD></TD>
</TR>
</TABLE>



<?php
//Скан печати - потом сделать по аналогии с ТОРГ-12
if( $parameters_values["stamp_scan"] != "" )
{
	if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["stamp_scan"]) )
	{
		?>
		<img src="/content/files/images/<?php echo $parameters_values["stamp_scan"]; ?>" style="max-height:150px;max-width:150px;" />
		<?php
	}
}
?>



</BODY>
</HTML>
<?php
$HTML = ob_get_contents();
ob_end_clean();
?>