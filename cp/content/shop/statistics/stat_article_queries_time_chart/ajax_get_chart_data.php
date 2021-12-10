<?php
// Set the JSON header
header('Content-Type: application/json;charset=utf-8;');
/*
Серверный скрипт для данных для графика запросов по артикулу
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
$articles = array();//Артикулы для поиска (по умолчанию)

//Получаем текущие значения фильтра:
$binding_values = array();
$stat_article_queries_time_chart = $_COOKIE["stat_article_queries_time_chart"];
if($stat_article_queries_time_chart != NULL)
{
	$stat_article_queries_time_chart = json_decode($stat_article_queries_time_chart, true);
	$time_from = $stat_article_queries_time_chart["time_from"];
	$time_to = $stat_article_queries_time_chart["time_to"];
	$customer = $stat_article_queries_time_chart["customer"];
	$articles = $stat_article_queries_time_chart["articles"];
	
	//Если есть ID пользователя - учитываем. Если меньше нуля - значит нужно запросить для всех
	$customer_condition = "";
	if($customer >= 0)
	{
		$customer_condition = " AND `user_id` = ?";
		
		array_push($binding_values, $customer);
	}
}
// ----------------------------------------------------------------------------------------------------------
//Формируем массив со структурой, адаптированной для шрафика
$chart_data = array();
for($i = 0; $i < count($articles); $i++)
{
	array_push($chart_data, array("name"=>$articles[$i], "data" => array()));
}
// ----------------------------------------------------------------------------------------------------------
//Нужно будет вернуть:
/*
- шкалу в текстовом виде xAxis
- chart_data - массив объектов, в каждом из которых два поля: артикул и массив со значениями для каждого деления шкалы
*/
// ----------------------------------------------------------------------------------------------------------
//Формируем массив со шкалой xAxis:
$xAxis = array();//Сам массив с делениями шкалы
$xAxis_range = $time_to - $time_from;//Время всего диапазона
$xAxis_division_count = 12;//Количество делений шкалы
$xAxis_division_range = (int)($xAxis_range / $xAxis_division_count);//Время одного деления шкалы

//Для каждого деления шкалы получаем значение yAxis
for($i = 0; $i <= $xAxis_division_count; $i++)
{
	$time_of_division = $time_from + $xAxis_division_range*$i;
	$division_str = date( "d.m.Y", $time_of_division)."<br>".date( "H:i:s", $time_of_division);
	
	//Диапазоны для SQL. Т.е. нужно получить данные по диапазону до текущего момента - от предыдущего (минус величину деления)
	$sql_time_from = $time_from + $xAxis_division_range*$i - $xAxis_division_range;
	$sql_time_to = $time_of_division;
	
	
	//1. Формируем заголовки делений шкалы
	array_push($xAxis, $division_str);
	
	//2. Получаем значение параметра Y для каждого артикула
	for($j = 0; $j < count($chart_data); $j++)
	{
		//Делаем SQL-запрос на получение количества запросов по данному артикулу в указанный период времени
		$SQL = "SELECT COUNT(`article`) AS `count` FROM `shop_stat_article_queries` WHERE `article` = ? AND `time` > ? AND `time` < ? $customer_condition";
		
		
		array_unshift($binding_values, $sql_time_to);
		array_unshift($binding_values, $sql_time_from);
		array_unshift($binding_values, $chart_data[$j]["name"]);
		
		
		$y_value_query = $db_link->prepare($SQL);
		$y_value_query->execute($binding_values);
		$y_value_record = $y_value_query->fetch();
		$y_value = $y_value_record["count"];
		
		array_push($chart_data[$j]["data"], (int)$y_value);
	}
}
// ----------------------------------------------------------------------------------------------------------
/*
//ФОРМИРУЕМ ЗАПРОС
$SQL = "SELECT DISTINCT(`article`), COUNT(`article`) AS `y` FROM `".$DP_Config->dbprefix."shop_stat_article_queries` WHERE `time` > $time_from AND `time` < $time_to $customer_condition GROUP BY `article`";

// ----------------------------------------------------------------------------------------------------------

$chart_data = array();//Массив со значениями
$chart_data_query = mysqli_query($db_link, $SQL);
while( $chart_data_record = mysqli_fetch_array($chart_data_query) )
{
	array_push($chart_data, array( "name"=>$chart_data_record["article"], "y"=>(int)$chart_data_record["y"] ) );
}

// ----------------------------------------------------------------------------------------------------------
*/


//Формируем ответ:
$answer = array();
$answer["status"] = true;
$answer["xAxis"] = $xAxis;
$answer["chart_data"] = $chart_data;
echo json_encode($answer);
?>