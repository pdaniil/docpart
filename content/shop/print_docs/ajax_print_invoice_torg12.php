<?php
/**
 * Печать накладной ТОРГ-12
*/
require_once($_SERVER["DOCUMENT_ROOT"] ."/config.php");
$DP_Config = new DP_Config();


//------------------Работа с БД-----------------------------------//
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");
//---------------------------------------------------------------------//




//------------------Подключение необходимых библиотек--------------//
require_once($_SERVER["DOCUMENT_ROOT"].'/lib/PHPExcel/PHPExcel.php');
require_once($_SERVER["DOCUMENT_ROOT"].'/lib/PHPExcel/PHPExcel/IOFactory.php');
require_once($_SERVER["DOCUMENT_ROOT"] ."/content/users/dp_user.php");
//---------------------------------------------------------------------//




//-------------------Работа с заказом----------------------------------//
//Получаем номер заказа по которому следует вывести накладную
$order_id = (int)$_GET["order_id"];
$orders_items_json = $_GET["orders_items"];

$orders_items_SQL = '';
if( ! empty($orders_items_json) )
{
	$orders_items = json_decode($orders_items_json, true);
	
	
	//Приводим к целому числу, чтобы обезопаситься от SQL-инъекций
	for( $i=0 ; $i < count($orders_items) ; $i++ )
	{
		$orders_items[$i] = (int)$orders_items[$i];
	}
	
	
	$orders_items_SQL = " AND `id` IN ('" . implode ("','", $orders_items) . "') ";
}




$order_query = $db_link->prepare("SELECT * FROM `shop_orders` WHERE `id` = ?;");
$order_query->execute( array($order_id) );

$order = $order_query->fetch();
$user_id = $order['user_id'];

$user_profile = DP_User::getUserProfileById($user_id);

/*
$log = fopen("log.txt", "w");
fwrite($log, print_r($user_profile, true) );
fclose($log);
*/

//Проверяем наличие у пользователя заказа. 
$order_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_orders` WHERE `user_id` = ? AND `id` = ?;");
$order_query->execute( array($user_id, $order_id) );

if( $order_query->fetchColumn() == 0 && DP_User::isAdmin() != true)
{
    exit("No such order");
}



//ПОЛЯ ИТОГО ПО ЗАКАЗУ
$count_need_total = 0;//Итого количество
$price_sum_total = 0;//Итого сумма

//ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
//Запрос наименования (для каталожного типа продукта)
//$SELEC_product_name = "(SELECT `caption` FROM `".$DP_Config->dbprefix."shop_catalogue_products` WHERE `id` = `".$DP_Config->dbprefix."shop_orders_items`.`product_id`)";
//Запрос наименований
$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
$SELECT_type2_name = "`t2_name`";//Для типа продукта = 2
$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";


//Сумма позиции
$SELECT_item_price_sum = "`price`*`count_need`";

$WHERE_COUNT_STATUS = "";
for($i=0; $i < count($orders_items_statuses_not_count); $i++)
{
    $WHERE_COUNT_STATUS .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
}


//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
$SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, $SELECT_product_name AS `product_name`, ($SELECT_item_price_sum) AS `price_sum` FROM `shop_orders_items` WHERE `order_id` = $order_id $WHERE_COUNT_STATUS $orders_items_SQL";

$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute();



$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
$elements_count_rows_query->execute();
$elements_count_rows = $elements_count_rows_query->fetchColumn();




file_put_contents("SQL.log", $SELECT_ORDER_ITEMS);

// exit();

//-----------------Модули подключения для Denwer------------------------//

//require_once($_SERVER["DOCUMENT_ROOT"].'/PHPExcel/PHPExcel.php');
//require_once($_SERVER["DOCUMENT_ROOT"].'/PHPExcel/PHPExcel/IOFactory.php');

//---------------------------------------------------------------------//

//Теперь работаем с заказом
//Копируем файл шаблона из шаблонов в рабочую папку:
copy($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/templates/tovarnaya-nakladnaya_torg12.xls", $_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");

//-----------------Модули подключения для Denwer------------------------//

//copy($_SERVER["DOCUMENT_ROOT"]."//print_docs/templates/tovarnaya-nakladnaya_torg12.xls", $_SERVER["DOCUMENT_ROOT"]."/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");

//---------------------------------------------------------------------//

//Загружаем файл в объект PHPExcel
$xls = PHPExcel_IOFactory::load($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");

//-----------------Модули подключения для Denwer------------------------//

//$xls = PHPExcel_IOFactory::load($_SERVER["DOCUMENT_ROOT"]."/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");

//----------------------------------------------------------------------//

// Получаем активный лист
$sheet = $xls->getActiveSheet();

$sheet->getPageSetup()->setFitToPage(false);
$sheet->getPageSetup()->setScale(90);




//--------------Реквизиты----------------//
// Информация о фирме
$office_id = (int)$order['office_id'];
$office_info_query = $db_link->prepare("SELECT * FROM `shop_offices` WHERE `id` = ?;");
$office_info_query->execute( array($office_id) );
$office_info = $office_info_query->fetch();


$office_description = $office_info["description"];
$office_city = $office_info["city"];
$office_address = $office_info["address"];
$office_phone = $office_info["phone"];


$office_for_output = $office_city." ".$office_address." ".$office_phone." ".$office_description;

//$office_for_output = "Сюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавцаСюда можно заполнить реквизиты продавца";//Сюда можно заполнить реквизиты продавца


/*
$addres_yr 		= $office_info["addres_yr"];
$addres_fiz 	= $office_info["addres_fiz"];
$inn 			= $office_info["inn"];
$kpp 			= $office_info["kpp"];
$ogrn 			= $office_info["ogrn"];
$okpo 			= $office_info["okpo"];
$bank_name 		= $office_info["bank_name"];
$addres_bank 	= $office_info["addres_bank"];
$rs 			= $office_info["rs"];
$ks 			= $office_info["ks"];
$bik 			= $office_info["bik"];
$director 		= $office_info["director"];
*/

//Информация о грузополучателе.
$info_user = "";

if($user_profile["reg_variant"] == 1)//Физ. лицо
{
	$info_user = $user_profile["surname"]." ".$user_profile["name"]." ".$user_profile["patronymic"]." ".$user_profile["fon"];
}
else if($user_profile["reg_variant"] == 3)// Юр.лицо
{
	$info_user = "{$user_profile["company_name"]}; {$user_profile["uradres"]}; ИНН {$user_profile["inn"]}; КПП {$user_profile["kpp"]}; р/с {$user_profile["rs"]}; {$user_profile["bank"]}; кор. счёт {$user_profile["ks"]}; БИК {$user_profile["bik"]}";
}

//$info_user = "тест";

//---------------------Информация о магазине-----------------------------------//
$INFO_POINT = "SELECT * FROM `shop_offices` WHERE `id` = ?";

$point_result = $db_link->prepare($INFO_POINT);
$point_result->execute( array($office_id) );
$point_fetch = $point_result->fetch();

$phone = $point_fetch["phone"];

//$B7 = $office_info_str."{$firma}; {$addres_yr}; ИНН {$inn}; КПП {$kpp}; р/с {$rs}; {$bank_name}; кор. счёт {$ks}; БИК {$bik}; т. {$phone}";	// Реквизиты организации-грузополучатель (B7==B15=>графу поставщик заполнять не нужно, данные продублируются)
$B7 = $office_for_output;	// Реквизиты организации-грузополучатель (B7==B15=>графу поставщик заполнять не нужно, данные продублируются)
$AY7 = $okpo;		// организации-грузополучатель по ОКПО (AY7==AY16)
$B10 = "";		// Наименование структурногго подразделения
$AY9 = "";		// Вид деятельности по ОКДП
$I12 = $info_user;		// Реквизиты грузополучателя
$AY13 = $user_profile["okpo"];		// Грузополучатель по ОКПО (AY13==AY19)
$I18 = $info_user;		// Реквизиты плательщика
$I21 = "";		// Основание
$AY21 = "";		// Номер основания
$AY22 = "";		// Дата основания
$AY23 = "";		// номер
$AY24 = "";		// дата
$AY25 = "";		// Вид операции
$AG28 = "123";		// Номер документа
$AL28 = "29.11.2017";		// Дата составления документа

$sheet->setCellValue('B7', $B7);
$sheet->setCellValue('AY7', $AY7);
$sheet->setCellValue('B10', $B10);
$sheet->setCellValue('AY9', $AY9);
$sheet->setCellValue('I12', $I12);
$sheet->setCellValue('AY13', $AY13);
$sheet->setCellValue('I18', $I18);
$sheet->setCellValue('I21', $I21);
$sheet->setCellValue('AY21', $AY21);
$sheet->setCellValue('AY22', $AY22);
$sheet->setCellValue('AY23', $AY23);
$sheet->setCellValue('AY24', $AY24);
$sheet->setCellValue('AY25', $AY25);
$sheet->setCellValue('AG28', $AG28);
$sheet->setCellValue('AL28', $AL28);

//---------------------------------------//

//-------Массивы для полей позиций товаров----------//
$B = array();		$AB = array();		$AP = array();
$E = array();		$AE = array();		$AS = array();
$S = array();		$AH = array();		$AV = array();
$V = array();		$AJ = array();		$AY = array();
$Y = array();		$AM = array();		$BC = array();
//--------------------------------------------------//

$coll_prod = $elements_count_rows;//максимум 54 позиции (3 полных листа), но это не предел. Количество будет зависить от скорости работы хостинга. На генерацию отчета отведено максимум 30 сек. Если за это время отчет не сформируется, будет ошибка.
$n_poz_prod = 34;		// Номер позиции товара
$col_start = 34;
$n = 1;	// номер п/п

$date1 = date("d-m-Y");

$sheet->setCellValue("AV33", "Без НДС");
$sheet->setCellValue("AL28", $date1);

ob_start();

while($order_item = $order_items_query->fetch() )
{

	if($n_poz_prod > 34)//т.е. позиций в заказе больше 1
	{
	    $sheet->insertNewRowBefore($n_poz_prod, 1);
		$sheet->mergeCells("B".$n_poz_prod.":D".$n_poz_prod."");
		$sheet->mergeCells("E".$n_poz_prod.":R".$n_poz_prod."");
		$sheet->mergeCells("S".$n_poz_prod.":U".$n_poz_prod."");
		$sheet->mergeCells("V".$n_poz_prod.":X".$n_poz_prod."");
		$sheet->mergeCells("Y".$n_poz_prod.":AA".$n_poz_prod."");
		$sheet->mergeCells("AB".$n_poz_prod.":AD".$n_poz_prod."");
		$sheet->mergeCells("AE".$n_poz_prod.":AG".$n_poz_prod."");
		$sheet->mergeCells("AH".$n_poz_prod.":AI".$n_poz_prod."");
		$sheet->mergeCells("AJ".$n_poz_prod.":AL".$n_poz_prod."");
		$sheet->mergeCells("AM".$n_poz_prod.":AO".$n_poz_prod."");
		$sheet->mergeCells("AP".$n_poz_prod.":AR".$n_poz_prod."");
		$sheet->mergeCells("AS".$n_poz_prod.":AU".$n_poz_prod."");
		$sheet->mergeCells("AV".$n_poz_prod.":AX".$n_poz_prod."");
		$sheet->mergeCells("AY".$n_poz_prod.":BB".$n_poz_prod."");
		$sheet->mergeCells("BC".$n_poz_prod.":BF".$n_poz_prod."");		
	}
	
	
	$item_id            = $order_item["id"];
	$item_status        = $order_item["status"];
	$item_count_need    = $order_item["count_need"];//Кол-во
	$item_price         = $order_item["price"];//Цена
	$item_price_sum     = $order_item["price_sum"];//Сумма
	$item_product_type  = $order_item["product_type"];
	$item_product_id    = $order_item["product_id"];
	$item_product_name  = $order_item["product_name"];//Наименование
	$item_time_to_exe   = $order_item["t2_time_to_exe"];//срок
	$item_brend         = $order_item["t2_manufacturer"];
	$item_article       = $order_item["t2_article"];
	
	if($item_product_name == "")
	{
		$item_product_name = "Наименование не указано";
	}

	//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
	// if( array_search($item_status, $orders_items_statuses_not_count) !== false)
	// {
		// continue;
	// }
	
	var_dump($order_item);
	
	$B[$i] = $n; //Порядковый номер
	
	$AB[$i]="";		
	$AE[$i]=""; 		//кол-во заказанных			
	$AH[$i]=$order_item["count_need"];				
	$AJ[$i]="";				
	$AP[$i]=$item_price; 			//Цена за штуку
	$AS[$i]=$item_price_sum;		//Цена за кол-во
	$AM[$i]="";				
	$AV[$i]="x";					//Ставка НДС %
	$AY[$i]="0.00"; 				//Сумма НДС
	
	$E[$i] = $item_product_name;	
	
	
	$S[$i] = $item_article;
	$V[$i] = "шт.";																	
	$Y[$i] = "";																
	$BC[$i]= $item_price_sum;
	
	$sheet->setCellValue("B".$n_poz_prod."",$B[$i]);
	$sheet->setCellValue("E".$n_poz_prod."", $E[$i]);
	$sheet->setCellValue("S".$n_poz_prod."", $S[$i]);
	$sheet->setCellValue("V".$n_poz_prod."", $V[$i]);
	$sheet->setCellValue("Y".$n_poz_prod."", $Y[$i]);
	$sheet->setCellValue("AB".$n_poz_prod."", $AB[$i]);
	$sheet->setCellValue("AE".$n_poz_prod."", $AE[$i]);
	$sheet->setCellValue("AH".$n_poz_prod."", $AH[$i]);
	$sheet->setCellValue("AJ".$n_poz_prod."", $AJ[$i]);
	$sheet->setCellValue("AM".$n_poz_prod."", $AM[$i]);
	$sheet->setCellValue("AP".$n_poz_prod."", $AP[$i]);
	$sheet->setCellValue("AS".$n_poz_prod."", $AS[$i]);
	$sheet->setCellValue("AV".$n_poz_prod."", $AV[$i]);
	$sheet->setCellValue("AY".$n_poz_prod."", $AY[$i]);
	$sheet->setCellValue("BC".$n_poz_prod."", $BC[$i]);
	
	$count_need_total += $item_count_need;//Всего количество
	$price_sum_total += $item_price_sum;//Сумма заказа
	$n+=1; //Порядковый номер
	$n_poz_prod +=1;
}

//Вставляем пустую строку
$sheet->insertNewRowBefore($n_poz_prod, 1);
$sheet->mergeCells("B".$n_poz_prod.":D".$n_poz_prod."");
$sheet->mergeCells("E".$n_poz_prod.":R".$n_poz_prod."");
$sheet->mergeCells("S".$n_poz_prod.":U".$n_poz_prod."");
$sheet->mergeCells("V".$n_poz_prod.":X".$n_poz_prod."");
$sheet->mergeCells("Y".$n_poz_prod.":AA".$n_poz_prod."");
$sheet->mergeCells("AB".$n_poz_prod.":AD".$n_poz_prod."");
$sheet->mergeCells("AE".$n_poz_prod.":AG".$n_poz_prod."");
$sheet->mergeCells("AH".$n_poz_prod.":AI".$n_poz_prod."");
$sheet->mergeCells("AJ".$n_poz_prod.":AL".$n_poz_prod."");
$sheet->mergeCells("AM".$n_poz_prod.":AO".$n_poz_prod."");
$sheet->mergeCells("AP".$n_poz_prod.":AR".$n_poz_prod."");
$sheet->mergeCells("AS".$n_poz_prod.":AU".$n_poz_prod."");
$sheet->mergeCells("AV".$n_poz_prod.":AX".$n_poz_prod."");
$sheet->mergeCells("AY".$n_poz_prod.":BB".$n_poz_prod."");
$sheet->mergeCells("BC".$n_poz_prod.":BF".$n_poz_prod."");
//---------------------------------------------------------------------------//

file_put_contents("dump.log", ob_get_clean());

$n_poz_prod +=1;

//Всего по накладной
$sheet->setCellValue("AS{$n_poz_prod}", $price_sum_total);  	//Колонка "Без учёта НДС"
$sheet->setCellValue("AY{$n_poz_prod}", "0.00");				//Колонка "Сумма руб,коп"
$sheet->setCellValue("BC{$n_poz_prod}", $price_sum_total);		//Колонка "Сумма с учётом НДС"
$sheet->setCellValue("AH{$n_poz_prod}", $count_need_total);	//Колонка "Мест,штук"

$n_poz_list1=33;

if($coll_prod > 2 and $coll_prod < 14)
{
	$n_poz_list1=$n_poz_list1+$coll_prod;
	$sheet->setBreak( "A".$n_poz_list1."" , PHPExcel_Worksheet::BREAK_ROW);
		
	$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(30, 33);
		
}

$n_poz_list_x = 33;

if($coll_prod > 14 and $coll_prod < 29)
{
	
	$sheet->setBreak( 'A46' , PHPExcel_Worksheet::BREAK_ROW);
			
	$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(30, 33);
}
if($coll_prod > 29 and $coll_prod < 55)
{
	$sheet->setBreak( 'A46' , PHPExcel_Worksheet::BREAK_ROW);
			
	$sheet->setBreak( 'A72' , PHPExcel_Worksheet::BREAK_ROW);
	
	$sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(30, 33);
}


//Выволим файл
// Выводим HTTP-заголовки
 header ( "Expires: Mon, 1 Apr 1974 05:00:00 GMT" );
 header ( "Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT" );
 header ( "Cache-Control: no-cache, must-revalidate" );
 header ( "Pragma: no-cache" );
 header ( "Content-type: application/vnd.ms-excel" );
 header ( "Content-Disposition: attachment; filename=tovarnaya-nakladnaya_torg12_new.xls" );

// Выводим содержимое файла
 $objWriter = new PHPExcel_Writer_Excel5($xls);
 $objWriter->save('php://output');


//Теперь удаляем файл

//-----------------Модули подключения для Denwer------------------------//

//unlink($_SERVER["DOCUMENT_ROOT"]."/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");

//----------------------------------------------------------------------//

unlink($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/work/tovarnaya-nakladnaya_torg12_new.xls");
?>