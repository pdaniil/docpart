<?php
/**
 * Шаблон скрипта для подбора товаров по текстовой строке
 * 
 * Данный скрипт заполняет массив $products_list номерами товаров, которые удовлетворяют поисковому запросу.
 * 
 * Этот скрипт нужно включать в соответствующие места скриптов, где должен использоваться этот массив
 * 
*/
$searsch_str = $search_string;
$like = '1 = 1';
$like_2 = '1 = 1';
$bind_array = array();
$iter = 0;
if(!empty($searsch_str))
{
	$searsch_str = trim($searsch_str);
	$searsch_str = explode(' ',$searsch_str);
	if(!empty($searsch_str))
	{
		$tmp_str = '';
		$tmp_str_2 = '';
		foreach($searsch_str as $item_str)
		{
			$item_str = trim($item_str);
			if(mb_strlen($item_str, 'utf-8') < 2)
			{
				continue;
			}
			
			$param_name = ':name'.$iter;
			$iter++;
			array_push($bind_array, array('value' => '%'.$item_str.'%', 'name'=>$param_name) );
			
			// Поиск по названию
			if($tmp_str != '')
			{
				$tmp_str .= ' AND ';
			}
			$tmp_str .= '((`caption` LIKE '.$param_name.')';
			//$tmp_str .= '((`caption` LIKE \'%OZ%\')';
			
			// Поиск по описанию
			if($tmp_str_2 != '')
			{
				$tmp_str_2 .= ' AND ';
			}
			$tmp_str_2 .= '((`content` LIKE '.$param_name.')';
			
			
			
			$tmp_str .= ')';
			$tmp_str_2 .= ')';
		}
		$like = '('.$tmp_str.')';
		$like_2 = '('.$tmp_str_2.')';
	}
}

$sql = 'SELECT `id` FROM `shop_catalogue_products` WHERE '.$like.' AND `published_flag` = 1;';
$sql_2 = 'SELECT `product_id` FROM `shop_products_text` WHERE '.$like_2.';';


$products_list = array();
$products_list_query = $db_link->prepare($sql);
for( $i=0; $i < count($bind_array); $i++ )
{
	$products_list_query->bindValue($bind_array[$i]['name'], $bind_array[$i]['value'], PDO::PARAM_STR);
}
$products_list_query->execute();
while( $product_record = $products_list_query->fetch() )
{
    array_push($products_list, (int)$product_record["id"]);
}

$products_list_query = $db_link->prepare($sql_2);
for( $i=0; $i < count($bind_array); $i++ )
{
	$products_list_query->bindValue($bind_array[$i]['name'], $bind_array[$i]['value'], PDO::PARAM_STR);
}
$products_list_query->execute();
while( $product_record = $products_list_query->fetch() )
{
    array_push($products_list, (int)$product_record["product_id"]);
}

//var_dump($products_list);
?>