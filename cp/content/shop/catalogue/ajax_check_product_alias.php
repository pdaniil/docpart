<?php
/**
 * Скрипт проверки уникальности url продукта в пределах одной категории товаров
*/
//Конфигурация Docpart
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    echo "Ошибка подключения к БД: ".mysqli_connect_error();
    exit;
}
$db_link->query("SET NAMES utf8;");


$product_id = $_POST['product_id'];
$category_id = $_POST['category_id'];
$alias = $_POST['alias'];


$alias_check_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_catalogue_products` WHERE `id` != ? AND `category_id`=? AND `alias` = ?;");
$alias_check_query->execute( array($product_id, $category_id, $alias) );
if( $alias_check_query->fetchColumn() > 0)
{
	echo "false";
}
else if( $alias_check_query->fetchColumn() == 0)
{
	echo "true";
}
?>