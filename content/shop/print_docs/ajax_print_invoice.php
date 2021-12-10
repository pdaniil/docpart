<?php
/**
 * Печать накладной по заказу
*/

// Подключаем класс для работы с excel
require_once($_SERVER["DOCUMENT_ROOT"].'/lib/PHPExcel/PHPExcel.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/lib/PHPExcel/PHPExcel/IOFactory.php');


//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

//Общая информация по заказам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");

//Получаем номер заказа по которому следует вывести накладную
$order_id = $_GET["order_id"];


//Удостоверяемся, что заказ и пользователь соответствуют друг другу
$order_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = ? AND `id` = ?;');
$order_query->execute( array($user_id, $order_id) );
if($order_query->fetchColumn() == 0 && DP_User::isAdmin() != true)
{
    exit("No such order");
}


//Теперь работаем с заказом
//Копируем файл шаблона из шаблонов в рабочую папку:
copy($_SERVER["DOCUMENT_ROOT"]."//content/shop/print_docs/templates/check.xls", $_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/".$user_id.".xls");

//Загружаем файл в объект PHPExcel
$xls = PHPExcel_IOFactory::load($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/".$user_id.".xls");

// Получаем активный лист
$sheet = $xls->getActiveSheet();



$sheet->setCellValue('D4', "Товарный чек № $order_id от ".date("d.m.Y", time()));//Название документа




// *************************************************************************************************
//ПОЛЯ ИТОГО ПО ЗАКАЗУ
$count_need_total = 0;//Итого количество
$price_sum_total = 0;//Итого сумма

//ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
//Запрос наименования (для каталожного типа продукта)
//$SELEC_product_name = "(SELECT `caption` FROM `".$DP_Config->dbprefix."shop_catalogue_products` WHERE `id` = `".$DP_Config->dbprefix."shop_orders_items`.`product_id`)";
//Запрос наименований
$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";


//Сумма позиции
$SELECT_item_price_sum = "`price`*`count_need`";

//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
$SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, $SELECT_product_name AS `product_name`, $SELECT_item_price_sum AS `price_sum` FROM `shop_orders_items` WHERE `order_id` = ?;";

$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute( array($order_id) );

$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
$elements_count_rows_query->execute();
$elements_count_rows = $elements_count_rows_query->fetchColumn();

$row_before = 7;//Строка для добавления новой строки (ШАБЛОН EXCEL)
while( $order_item = $order_items_query->fetch() )
{
	$item_id            = $order_item["id"];
	$item_status        = $order_item["status"];
	$item_count_need    = $order_item["count_need"];//Кол-во
	$item_price         = $order_item["price"];//Цена
	$item_price_sum     = $order_item["price_sum"];//Сумма
	$item_product_type  = $order_item["product_type"];
	$item_product_id    = $order_item["product_id"];
	$item_product_name  = $order_item["product_name"];//Наименование
	
	//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
	if( array_search($item_status, $orders_items_statuses_not_count) === false)
	{
		$count_need_total += $item_count_need;//Всего количество
		$price_sum_total += $item_price_sum;//Сумма заказа
	}
	
	if($row_before > 7)//т.е. позиций в заказе больше 1
	{
	    $sheet->insertNewRowBefore($row_before,1);//Добавляем 1 строку перед строкой
	    
	    //Объединяем ячейки в рамках одного поля:
	    $sheet->mergeCells("A".$row_before.":B".$row_before);
	    $sheet->mergeCells("C".$row_before.":G".$row_before);
	    $sheet->mergeCells("H".$row_before.":I".$row_before);
	    $sheet->mergeCells("K".$row_before.":M".$row_before);
	    $sheet->mergeCells("N".$row_before.":S".$row_before);
	}
	
	
	//Работаем со строкой в файле Excel
	$sheet->setCellValue('A'.$row_before, $row_before-6);//Порядковый номер позиции
	$sheet->setCellValue('C'.$row_before, $item_product_name);//Наименование
	$sheet->setCellValue('H'.$row_before, "Шт.");//Шт.
	$sheet->setCellValue('J'.$row_before, $item_count_need);//Количество
	$sheet->setCellValue('K'.$row_before, $item_price);//Цена
	$sheet->setCellValue('N'.$row_before, $item_price_sum);//Сумма
	
	$row_before++;
}//while - по позициям заказа
// *************************************************************************************************

$sheet->setCellValue('N'.$row_before, $price_sum_total);//Сумма заказа


$sheet->setCellValue('B'.(9 + $elements_count_rows), "Всего наименований: ".$elements_count_rows);//Всего наименований

$sheet->setCellValue('I'.(11 + $elements_count_rows), date("d.m.Y", time()));//Дата выдачи товара


$sheet->setCellValue('C'.(18 + $elements_count_rows), "Корешок чека № $order_id от ".date("d.m.Y", time()));//Корешок чека

//Выволим файл
// Выводим HTTP-заголовки
 header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
 header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
 header ( "Cache-Control: no-cache, must-revalidate" );
 header ( "Pragma: no-cache" );
 header ( "Content-type: application/vnd.ms-excel" );
 header ( "Content-Disposition: attachment; filename=".$user_id.".xls" );

// Выводим содержимое файла
 $objWriter = new PHPExcel_Writer_Excel5($xls);
 $objWriter->save('php://output');


//Теперь удаляем файл
unlink($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/".$user_id.".xls");
?>