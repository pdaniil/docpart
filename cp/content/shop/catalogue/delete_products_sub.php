<?php
/**
 * СКРИПТ ДЛЯ УДАЛЕНИЯ ПРОДУКТОВ ИЗ СПРАВОЧНИКА ТОВАРОВ
 * 
 * '_sub' в названии скрипта говорит о том, что этот скрипт работает не самостоятельно. Его нужно подключать через require_once
 * 
 * Параметры, которые требуются скрипту:
 * 
 * - $products_to_delete - список ID продуктов
*/
defined('_ASTEXE_') or die('No access');


//АЛГОРИТМ УДАЛЕНИЯ ПРОДУКТА ИЗ СПРАВОЧНИКА
//1. Удаляем учетную запись продукта из таблицы shop_catalogue_products
//2. Удаляем изображения продукта из таблицы shop_products_images
//3. Удаляем текстовые описания продуктов из таблицы shop_products_texts
//4. Удаляем значения свойств продуктов из 5 таблиц значений свойств


//Составляем строку в формате '(ID1, ID2, ..., IDN)'
$sub_SQL_PRODUCTS_LIST = "";
$binding_values = array();
for($i=0; $i < count($products_to_delete); $i++)
{
    if($i > 0)
    {
        $sub_SQL_PRODUCTS_LIST .= ",";
    }
    $sub_SQL_PRODUCTS_LIST .= "?";
	
	array_push($binding_values, $products_to_delete[$i]);
}
$sub_SQL_PRODUCTS_LIST = "(".$sub_SQL_PRODUCTS_LIST.")";



//Формируем SQL-запросы:
//1. Удаляем учетную запись продукта из таблицы shop_catalogue_products
$SQL_DELETE_PRODUCTS_RECORDS = "DELETE FROM `shop_catalogue_products` WHERE `id` IN $sub_SQL_PRODUCTS_LIST;";
//2. Удаляем изображения продукта из таблицы shop_products_images
$SQL_DELETE_PRODUCTS_IMAGES = "DELETE FROM `shop_products_images` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
//3. Удаляем текстовые описания продуктов из таблицы shop_products_text
$SQL_DELETE_PRODUCTS_TEXTS = "DELETE FROM `shop_products_text` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
//4. Удаляем значения свойств продуктов из 5 таблиц значений свойств
$SQL_DELETE_PRODUCTS_PROPERTIES_INT = "DELETE FROM `shop_properties_values_int` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
$SQL_DELETE_PRODUCTS_PROPERTIES_FLOAT = "DELETE FROM `shop_properties_values_float` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
$SQL_DELETE_PRODUCTS_PROPERTIES_TEXT = "DELETE FROM `shop_properties_values_text` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
$SQL_DELETE_PRODUCTS_PROPERTIES_BOOL = "DELETE FROM `shop_properties_values_bool` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
$SQL_DELETE_PRODUCTS_PROPERTIES_LIST = "DELETE FROM `shop_properties_values_list` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";



/*
//Удаляем файлы изображений
$images_query = $db_link->prepare("SELECT * FROM `shop_products_images` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;");
$images_query->execute($binding_values);
while($image = $images_query->fetch())
{
	//Проверяем, используется этот файл в учетных записях изображений тех товаров, которых нет в списке на удаение
	$check_file_use_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_products_images` WHERE `file_name` = '".$image["file_name"]."' AND `product_id` NOT IN $sub_SQL_PRODUCTS_LIST;");
	$check_file_use_query->execute($binding_values);
	//Файл удаляем только, если он не ипользуется учетных записях товаров, которых нет в списке на удаление
	if( $check_file_use_query->fetchColumn() == 0 )
	{
		unlink($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$image["file_name"]);
	}
}
*/


//Удаляем стикеры
$SQL_DELETE_PRODUCTS_PRODUCTS_STICKERS = "DELETE FROM `shop_products_stickers` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";

//Удаляем древовидный список
$SQL_DELETE_PRODUCTS_TREE_LIST = "DELETE FROM `shop_properties_values_tree_list` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";

//Удаляем складские записи
$SQL_DELETE_PRODUCTS_STORAGES_DATA = "DELETE FROM `shop_storages_data` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";


//ВЫПОЛНЯЕМ ЗАПРОСЫ:
$delete_products_error_messages = array();//Массив с сообщениями об ошибках
if( $db_link->prepare($SQL_DELETE_PRODUCTS_RECORDS)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления учетных записей продуктов");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_IMAGES)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления изображений продуктов");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_TEXTS)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления текстовых описаний продуктов");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_INT)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления свойств продуктов типа INT");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_FLOAT)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления свойств продуктов типа FLOAT");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_TEXT)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления свойств продуктов типа TEXT");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_BOOL)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления свойств продуктов типа BOOL");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_LIST)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления списковых свойств продуктов");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_PRODUCTS_STICKERS)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления стикеров продуктов");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_TREE_LIST)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления привязки к древовидному списку");
}
if( $db_link->prepare($SQL_DELETE_PRODUCTS_STORAGES_DATA)->execute($binding_values) != true)
{
	throw new Exception("Ошибка удаления складской информации");
}
?>