<?php
// Set the JSON header
header('Content-Type: application/json;charset=utf-8;');
/*
Серверный скрипт для получения массива запрошенных артикулов по определенным признакам
*/
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

// ----------------------------------------------------------------------------------------------------------

//Значения фильтра по умолчанию
list($y,$m,$d) = explode('-', date('Y-m-d', time()));
$time_from = mktime(0,0,0,$m,$d,$y);//1. Время с
$time_to = mktime(0,0,0,$m,$d+1,$y);//2. Время по
$customer = 0;//3. Покупатель


$binding_values = array();

//Получаем текущие значения фильтра:
$stat_article_queries_rating_filter = $_COOKIE["stat_article_queries_rating_filter"];
if($stat_article_queries_rating_filter != NULL)
{
	$stat_article_queries_rating_filter = json_decode($stat_article_queries_rating_filter, true);
	$time_from = $stat_article_queries_rating_filter["time_from"];
	$time_to = $stat_article_queries_rating_filter["time_to"];
	$customer = $stat_article_queries_rating_filter["customer"];
	
	//Если есть ID пользователя - учитываем. Если меньше нуля - значит нужно запросить для всех
	$customer_condition = "";
	if($customer >= 0)
	{
		$customer_condition = " AND `user_id` = ?";
		
		array_push($binding_values, $customer);
	}
}


// ----------------------------------------------------------------------------------------------------------

//ФОРМИРУЕМ ЗАПРОС

array_unshift($binding_values, $time_to);
array_unshift($binding_values, $time_from);

$SQL = "SELECT DISTINCT(`article`), COUNT(`article`) AS `y` FROM `shop_stat_article_queries` WHERE `time` > ? AND `time` < ? $customer_condition GROUP BY `article` ORDER BY `y` DESC LIMIT 50";

// ----------------------------------------------------------------------------------------------------------

$chart_data = array();//Массив со значениями



$chart_data_query = $db_link->prepare($SQL);
$chart_data_query->execute($binding_values);
while( $chart_data_record = $chart_data_query->fetch() )
{
	array_push($chart_data, array( "name"=>$chart_data_record["article"], "y"=>(int)$chart_data_record["y"] ) );
}

// ----------------------------------------------------------------------------------------------------------

echo json_encode($chart_data);
?>