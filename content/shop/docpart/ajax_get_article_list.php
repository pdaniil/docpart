<?php
header('Content-Type: application/json;charset=utf-8;');

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;


//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	$result = array();
	$result["status"] = false;
	$result["message"] = "No DB connect";
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

////////////////////////////////////////////

//Если в браузере есть информация о статистике запросов не авторизованного пользователя, а сейчас пользователь авторизован, тогда привяжим их к пользователю
if( isset($_COOKIE["shop_stat"]) && $user_id > 0)
{
	$where = '';
	$binding_values = array();
	//Формируем строку условий запроса из id записей статистики
	$shop_stat = explode('_', $_COOKIE["shop_stat"]);
	if(is_array($shop_stat)){
		$str = '';
		foreach($shop_stat as $item){
			if($str != ''){
				$str .= ',';
			}
			$str .= '?';
			array_push($binding_values, (int)$item);
		}
		if($str != ''){
			$where = '`id` IN('.$str.')';
		}
	}
	//Если строка условий сформирована
	if($where !== ''){
		array_unshift($binding_values, $user_id);//Добавляем user_id в запрос
		$sql = "UPDATE `shop_stat_article_queries` SET `user_id` = ? WHERE ".$where;
		//Привязываем записи статистики к user_id
		$query = $db_link->prepare($sql);
		if($query->execute($binding_values) == true)
		{
			//Удалим ненужную информацию
			setcookie('shop_stat', '', time()-86400, '/');
			unset($_COOKIE["shop_stat"]);
		}
	}
}

////////////////////////////////////////////
$request_object = json_decode($_POST['request_object'], true);
$value = $request_object['value'];
$cnt_list = 50;//Максимальное количество строк отображения
$list = array();
$dump_list = array();//Список дампов для проверки на уникальность
$standart_list = array( 
						array('article'=>'C110', 'manufacturer'=>'DOLZ', 'name'=>'Насос водяной'),
						array('article'=>'12345', 'manufacturer'=>'FEBI', 'name'=>'Подвески для двигателя и передачи'),
						array('article'=>'S56545', 'manufacturer'=>'BREMBO', 'name'=>'Тормозные колодки')
				 );



//Получаем последние запросы
$where = '';
$binding_values = array();
if($value == ''){
	//Если в браузере есть информация о статистике запросов не авторизованного пользователя
	if( isset($_COOKIE["shop_stat"]))
	{
		//Формируем строку условий запроса из id записей статистики
		$shop_stat = explode('_', $_COOKIE["shop_stat"]);
		if(is_array($shop_stat)){
			$str = '';
			foreach($shop_stat as $item){
				if($str != ''){
					$str .= ',';
				}
				$str .= '?';
				array_push($binding_values, (int)$item);
			}
			if($str != ''){
				$where = '`id` IN('.$str.')';
			}
		}
	}
	if($user_id > 0){
		if($where != ''){
			$where .= ' AND '; 
		}
		$where .= '`user_id` = ?';
		array_push($binding_values, $user_id);
	}
}
if($value != ''){
	if($where != ''){
		$where .= ' AND '; 
	}
	$where .= '(`article` LIKE ? OR `name` LIKE ?)';
	array_push($binding_values, $value.'%');
	array_push($binding_values, '%'.$value.'%');
}
if($where != ''){
	$sql = 'SELECT * FROM `shop_stat_article_queries` WHERE `id` IN(SELECT MAX(`id`) FROM `shop_stat_article_queries` WHERE '.$where.' GROUP BY `article`, `manufacturer`) ORDER BY `id` DESC LIMIT '.$cnt_list;
	$query = $db_link->prepare($sql);
	$query->execute($binding_values);
		while($record = $query->fetch()){
		$dump = md5($record['article'].$record['manufacturer']);
		if( array_search($dump, $dump_list) === false )
		{
			array_push($dump_list, $dump);//Вносим дамп в список уникальных дампов
			$list[] = array('article'=>$record['article'], 'manufacturer'=>$record['manufacturer'], 'name'=>$record['name']);
		}
	}
}

if($value == ''){
	//Добавляем стандартные строки если позволяет количество
	for($i=0; (count($list) < $cnt_list && count($standart_list) > $i); $i++){
		$dump = md5($standart_list[$i]['article'].$standart_list[$i]['manufacturer']);
		if( array_search($dump, $dump_list) === false )
		{
			array_push($dump_list, $dump);//Вносим дамп в список уникальных дампов
			$list[] = $standart_list[$i];
		}
	}
}else{
	for($i=0; count($standart_list) > $i; $i++){
		if(strpos($standart_list[$i]['article'], mb_strtoupper(trim($value), "UTF-8")) === 0){
			$dump = md5($standart_list[$i]['article'].$standart_list[$i]['manufacturer']);
			if( array_search($dump, $dump_list) === false )
			{
				array_push($dump_list, $dump);//Вносим дамп в список уникальных дампов
				$list[] = $standart_list[$i];
			}
		}
	}
}


/*
//ДАЛЕЕ ДЛЯ ОТЛАДКИ
//Функция замены первого вхождения строки
function str_replace_once($search, $replace, $text) 
{ 
   $pos = strpos($text, $search); 
   return $pos!==false ? substr_replace($text, "'".$replace."'", $pos, strlen($search)) : $text; 
}

//Боевой SQL-запрос присваем в $SQL_bebug, чтобы боевой остался без изменений, т.к. он будет далее использоваться в скрипте
$SQL_bebug = $sql;
//Цикл по массиву значений, которые нужно биндить
for( $i=0 ; $i < count($binding_values) ; $i++ )
{
	$SQL_bebug = str_replace_once('?', $binding_values[$i], $SQL_bebug);
}
*/


$result = array();
$result['list'] = $list;
//$result['sql'] = $SQL_bebug;
$json = json_encode($result, JSON_UNESCAPED_UNICODE);
exit($json);
?>