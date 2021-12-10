<?php
/**
 * Очередной шаг общего алгоритма загрузки прайс-листа "Конвертирование файлов Excel в CSV"
*/

//-------Подключаем библиотеку для работы с файлами Excel---------
//require_once($_SERVER["DOCUMENT_ROOT"]."/lib/PHPExcel/PHPExcel.php");
//-----------------------------------------------------------
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;


//Временный каталог
$work_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;




$dh = opendir($work_dir);//Открываем временный каталог
//Пробегаем по содержимому временного каталога
while (false !== ($obj = readdir($dh)))
{
	if($obj=='.' || $obj=='..') 
	{
		continue;
	}
	else
	{
		$excel_note = "Через панель управления сайта можно загружать файлы в формате *.CSV и *.TXT. А, для файлов *.xls и .xlsx используйте нашу бесплатную программу Docpart Price Manager, которую можно скачать с главной страницы нашего хелпдеска <a href=\"https://intask.pro/\" target=\"_blank\" style=\"text-decoration:underline;font-weight:bold;color:#33C;\">intask.pro</a> (необходима авторизация). По ней доступны видео-уроки.";
		
		if(strripos($obj, ".xls") != false)//Если содержит .xls
		{
			if(strlen($obj)-strlen(".xls") == strripos($obj, ".xls"))
			{
			    $answer = array();//Объект ответа
                $answer["result"] = 0;
                $answer["message"] = $excel_note;
                closedir($dh);//Закрываем каталог
                exit(json_encode($answer));
			}
		}
		if(strripos($obj, ".xlsx") != false)//Если содержит .xlsx
		{
			if(strlen($obj)-strlen(".xlsx") == strripos($obj, ".xlsx"))
			{
			    $answer = array();//Объект ответа
                $answer["result"] = 0;
                $answer["message"] = $excel_note;
                closedir($dh);//Закрываем каталог
                exit(json_encode($answer));
			}
		}
	}//else 1
}//~while 1
closedir($dh);//Закрываем каталог



$answer = array();//Объект ответа
$answer["result"] = 1;
exit(json_encode($answer));
?>