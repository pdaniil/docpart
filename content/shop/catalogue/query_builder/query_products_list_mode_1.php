<?php
//Построение запроса товаров для режима работы 1
//Получаем объект свойства Цена
//var_dump($properties_list);
for($i=0; $i < count($properties_list); $i++)
{
	$property_type_id = $properties_list[$i]["property_type_id"];
	$property_id = $properties_list[$i]["property_id"];
	
	if($property_id == 'price')
	{
		$price_object = $properties_list[$i];
		$min_price = $price_object["min_need"];
		$max_price = $price_object["max_need"];
		break;
	}
}


//Параметры сортировки
$ASC_DESC_FIELD = $propucts_request["products_sort_mode"]["field"];

switch($ASC_DESC_FIELD)
{
	case "price":
		$ASC_DESC_FIELD = "customer_price";
		break;
	case "name":
		$ASC_DESC_FIELD = "caption";
		break;
	default:
		$ASC_DESC_FIELD = "customer_price";
}


if( strtolower($propucts_request["products_sort_mode"]["asc_desc"]) == "asc" )
{
	$ASC_DESC_DIR = "ASC";
}
else
{
	$ASC_DESC_DIR = "DESC";
}


$binding_args_general = array();


//ФОРМИРУЕМ СТРОКИ УСЛОВИЙ ДЛЯ СВОЙСТВ
$SQL_PROPERTIES_CONDITIONS = "";
$binding_args_params = array();
for($i=0; $i < count($properties_list); $i++)
{
	$property_type_id = $properties_list[$i]["property_type_id"];
	$property_id = $properties_list[$i]["property_id"];
	
	if($property_id == 'price')//Цену пропускаем, т.е. ее уже учли
	{
		continue;
	}
	
	
	//Для свойств типа int и float
	if($property_type_id == 1 || $property_type_id == 2)
	{
		//Если указаны крайние значения, то это свойство не учитываем
		if($properties_list[$i]["min_need"] == $properties_list[$i]["min_value"] && $properties_list[$i]["max_need"] == $properties_list[$i]["max_value"])
		{
			continue;
		}
	}
	

	
	switch($property_type_id)
	{
		case 1:
			$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM shop_properties_values_int WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) >= ? AND (SELECT `value` FROM shop_properties_values_int WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) <= ? )';
			
			
			array_push($binding_args_params, $property_id);
			array_push($binding_args_params, $properties_list[$i]["min_need"]);
			array_push($binding_args_params, $property_id);
			array_push($binding_args_params, $properties_list[$i]["max_need"]);
			
			break;
		case 2:
			$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM shop_properties_values_float WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) >= ? AND (SELECT `value` FROM shop_properties_values_float WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) <= ? )';
			
			array_push($binding_args_params, $property_id);
			array_push($binding_args_params, $properties_list[$i]["min_need"]);
			array_push($binding_args_params, $property_id);
			array_push($binding_args_params, $properties_list[$i]["max_need"]);
			
			break;
		case 4:
			//Если оба варианта (ДА/НЕТ) не отмечены - означает, что данное свойство не учитывается
			//Если оба варианта (ДА/НЕТ) отмечны - это равносильно тому, что нужно будет высести все товары, поэтому, тоже это свойство не будет учтено
			if( !( ($properties_list[$i]["true_checked"] == false && $properties_list[$i]["false_checked"] == false) ||
				($properties_list[$i]["true_checked"] == true && $properties_list[$i]["false_checked"] == true) ) )
			{
				//if сработал, потому, что одно из них выставлено - выясняем, какое:
				if($properties_list[$i]["true_checked"] == true)
				{
					$need_value = 1;
				}
				else
				{
					$need_value = 0;
				}
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND (SELECT `value` FROM shop_properties_values_bool WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) = ?';
				
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $need_value);
			}
			break;
		case 5:
			
			$list_options = $properties_list[$i]["list_options"];
			$list_type = $properties_list[$i]["list_type"];//Тип списка
			
			if($list_type == 1)
			{
				$OR_AND = "OR";
			}
			else if($list_type == 2)
			{
				$OR_AND = "AND";
			}
			
			
			$SQL_VALUES_COND = "";//Подстрока с условиями для поля value
			for($o=0; $o < count($list_options); $o++)
			{
				if( isset($list_options[$o]["value"]) )
				{
					if($list_options[$o]["value"] == true)
					{
						if($SQL_VALUES_COND != "")$SQL_VALUES_COND .= ' '.$OR_AND.' ';
						$SQL_VALUES_COND = $SQL_VALUES_COND . ' (SELECT `value` FROM shop_properties_values_list WHERE product_id = shop_catalogue_products.id AND `property_id` = ? AND value = ?) ';
						
						
						array_push($binding_args_params, $property_id);
						array_push($binding_args_params, $list_options[$o]["id"]);
						
					}
				}
			}
			if($SQL_VALUES_COND != "")//Если строка заполнена, то по меньше мере одно значение отмечено, значит добавляем строку
			{
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ('.$SQL_VALUES_COND.')';
			}
			break;//case 5
		case 6:
			//Свойство данного типа НЕ учитывается если выбранно значение "Все" на первом уровне
			if( ! ( $properties_list[$i]["current_level"] == 1 && $properties_list[$i]["current_value"] == 0 ) )
			{
				$current_value = $properties_list[$i]["current_value"];
				
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( SELECT `value` FROM `shop_properties_values_tree_list` WHERE product_id = shop_catalogue_products.id AND `property_id` = ? AND value = ? ) ';
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $current_value);
			}
			break;
	}//switch($property_type_id)
}


$SQL = "";//Единственный запрос для получения всех нужных товаров
$binding_args_sql = array();
$SQL_LIGHT = "";//ТОЖЕ, ЧТО И $SQL, ТОЛЬКО БЕЗ ЛИШНИЙ ПОЛЕЙ. ДЛЯ ПОДЗАПРОСА С LIMIT. НУЖЕН, ЧТОБЫ НЕ ДЕЛАТЬ ВЫБОРКУ ЛИШНИЙ ПОЛЕЙ ПРИ ОТСЕИВАНИИ ПО LIMIT
$binding_args_sql_light = array();

//По всем доступным магазинам
for($i = 0; $i < count($customer_offices); $i++)
{
	if($i > 0)
	{
		$SQL = $SQL . ' UNION ';
		$SQL_LIGHT = $SQL_LIGHT . ' UNION ';
	}
	
	$SQL = $SQL . 
		'SELECT 
			`all`.id,
			`all`.caption,
			`all`.alias,
			`all`.storage_record_id,
			`all`.storage_id,
			`all`.customer_price,
			`all`.price,
			`all`.price_crossed_out,
			`all`.price_purchase,
			`all`.arrival_time,
			`all`.exist,
			`all`.reserved,
			`all`.issued,
			`all`.description,
			`all`.category_url,
			`all`.category_id,
			`all`.file_name,
			`all`.office_id,
			`all`.additional_time,
			`all`.mark,
			`all`.mark_1,
			`all`.mark_2,
			`all`.mark_3,
			`all`.mark_4,
			`all`.mark_5,
			`all`.marks_count,
			`all`.`id` AS `idstr` 
		FROM 
			(SELECT
				shop_catalogue_products.id AS id,
				shop_catalogue_products.caption AS caption,
				shop_catalogue_products.alias AS alias,
				shop_storages_data.id AS storage_record_id,
				shop_storages_data.storage_id AS storage_id,
				CAST(shop_storages_data.customer_price AS decimal(10,2)) AS customer_price,
				CAST(shop_storages_data.price*'.$SQL_currency_rate.' AS decimal(10,2)) AS price,
				CAST(shop_storages_data.price_crossed_out*'.$SQL_currency_rate.' AS decimal(10,2)) AS price_crossed_out,
				CAST(shop_storages_data.price_purchase*'.$SQL_currency_rate.' AS decimal(10,2)) AS price_purchase,
				shop_storages_data.arrival_time AS arrival_time,
				shop_storages_data.exist AS exist,
				shop_storages_data.reserved AS reserved,
				shop_storages_data.issued AS issued,
				shop_products_text.content AS description,
				shop_catalogue_categories.url AS category_url,
				shop_catalogue_categories.id AS category_id,
				(SELECT file_name FROM shop_products_images WHERE product_id = shop_catalogue_products.id LIMIT 1) AS file_name,
				? AS office_id,
				(SELECT additional_time FROM shop_offices_storages_map WHERE office_id = ? AND storage_id = shop_storages_data.storage_id LIMIT 1) AS additional_time,
				
				(SELECT ROUND(SUM(`mark`)/COUNT(`id`)) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id) AS mark,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id AND mark=1) AS mark_1,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id AND mark=2) AS mark_2,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id AND mark=3) AS mark_3,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id AND mark=4) AS mark_4,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id AND mark=5) AS mark_5,
				(SELECT COUNT(`id`) FROM shop_products_evaluations WHERE product_id = shop_catalogue_products.id) AS marks_count
				
			FROM
				shop_catalogue_products

			LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `category_id` = ? AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			WHERE
				shop_catalogue_products.category_id = ? AND shop_storages_data.customer_price >= ? AND shop_storages_data.customer_price <= ? '.$SQL_PROPERTIES_CONDITIONS.') AS `all` ';
				
	array_push($binding_args_sql, $customer_offices[$i]);
	array_push($binding_args_sql, $customer_offices[$i]);
	array_push($binding_args_sql, $customer_offices[$i]);
	array_push($binding_args_sql, $group_id);
	array_push($binding_args_sql, $category_id);
	array_push($binding_args_sql, $customer_offices[$i]);
	array_push($binding_args_sql, $category_id);
	array_push($binding_args_sql, $min_price);
	array_push($binding_args_sql, $max_price);
	
	
	$binding_args_sql = array_merge($binding_args_sql, $binding_args_params);
	
	
	$SQL_LIGHT = $SQL_LIGHT . 
		'SELECT 
			*, `id` AS `idstr`
		FROM 
			(SELECT
				shop_catalogue_products.id AS id,
				shop_catalogue_products.caption AS caption,
				CAST(shop_storages_data.customer_price AS decimal(10,2)) AS customer_price
			FROM
				shop_catalogue_products
			LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `category_id` = ? AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			WHERE
				shop_catalogue_products.category_id = ? AND shop_storages_data.customer_price >= ? AND shop_storages_data.customer_price <= ? '.$SQL_PROPERTIES_CONDITIONS.') AS `all` ';
	array_push($binding_args_sql_light, $customer_offices[$i]);
	array_push($binding_args_sql_light, $group_id);
	array_push($binding_args_sql_light, $category_id);
	array_push($binding_args_sql_light, $customer_offices[$i]);
	array_push($binding_args_sql_light, $category_id);
	array_push($binding_args_sql_light, $min_price);
	array_push($binding_args_sql_light, $max_price);
	
	$binding_args_sql_light = array_merge($binding_args_sql_light, $binding_args_params);
}

$SQL = "SELECT 
`all_offices`.id,
`all_offices`.caption,
`all_offices`.alias,
`all_offices`.storage_record_id,
`all_offices`.storage_id,
`all_offices`.customer_price,
`all_offices`.price,
`all_offices`.price_crossed_out,
`all_offices`.price_purchase,
`all_offices`.arrival_time,
`all_offices`.exist,
`all_offices`.reserved,
`all_offices`.issued,
`all_offices`.description,
`all_offices`.category_url,
`all_offices`.file_name,
`all_offices`.category_id,
`all_offices`.office_id,
`all_offices`.additional_time,
`all_offices`.mark,
`all_offices`.mark_1,
`all_offices`.mark_2,
`all_offices`.mark_3,
`all_offices`.mark_4,
`all_offices`.mark_5,
`all_offices`.marks_count
FROM ( ".$SQL." ) AS all_offices";//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам

//Далее делаем лимит по страницам
//$SQL = $SQL . ' INNER JOIN (SELECT DISTINCT(`id`) FROM ( '.$SQL_LIGHT.' ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR.') AS limit_ids ) limit_ids ON all_offices.id = limit_ids.id ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR.' LIMIT '.(int)$product_from.', '.(int)$product_max_count;
//$SQL = $SQL . ' INNER JOIN (SELECT DISTINCT(`id`) FROM ( '.$SQL_LIGHT.' ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR.') AS limit_ids LIMIT '.(int)$product_from.', '.(int)$product_max_count.' ) limit_ids ON all_offices.id = limit_ids.id ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR;
$SQL = $SQL . ' INNER JOIN (SELECT DISTINCT(`id`) FROM ( '.$SQL_LIGHT.' GROUP BY `idstr` ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR.', `idstr` '.$ASC_DESC_DIR.') AS limit_ids LIMIT '.(int)$product_from.', '.(int)$product_max_count.' ) limit_ids ON all_offices.id = limit_ids.id GROUP BY `idstr` ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR .', `idstr` '.$ASC_DESC_DIR;



//Стикеры (их может быть несколько для одного товара, поэтому, исключаем косяк с LIMIT для таких случаев)
$SQL = "
SELECT 

`a`.id,
`a`.caption,
`a`.alias,
`a`.storage_record_id,
`a`.storage_id,
`a`.customer_price,
`a`.price,
`a`.price_crossed_out,
`a`.price_purchase,
`a`.arrival_time,
`a`.exist,
`a`.reserved,
`a`.issued,
`a`.description,
`a`.category_url,
`a`.category_id,
`a`.file_name,
`a`.office_id,
`a`.additional_time,
`a`.mark,
`a`.mark_1,
`a`.mark_2,
`a`.mark_3,
`a`.mark_4,
`a`.mark_5,
`a`.marks_count,

stickers_t.`value` AS sticker_value,
stickers_t.id AS sticker_id,
stickers_t.color_text AS sticker_color_text,
stickers_t.color_background AS sticker_color_background,
stickers_t.href AS sticker_href,
stickers_t.class_css AS sticker_class_css,
stickers_t.description AS sticker_description
FROM
(".$SQL.") AS `a`
LEFT JOIN
  shop_products_stickers AS stickers_t
    ON a.id = stickers_t.product_id";




$sql_args_array = array_merge($binding_args_sql, $binding_args_sql_light);




/*
Пример - для отладки SQL-запроса. Здесь массив значений подставляется в строку запроса - чтобы можно было выполнить этот запрос вручную
*/

//ДАЛЕЕ ДЛЯ ОТЛАДКИ
//Функция замены первого вхождения строки
function str_replace_once($search, $replace, $text) 
{ 
   $pos = strpos($text, $search); 
   return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text; 
}

//Боевой SQL-запрос присваем в $SQL_bebug, чтобы боевой остался без изменений, т.к. он будет далее использоваться в скрипте
$SQL_bebug = $SQL;
//Цикл по массиву значений, которые нужно биндить
for( $i=0 ; $i < count($sql_args_array) ; $i++ )
{
	$SQL_bebug = str_replace_once('?', $sql_args_array[$i], $SQL_bebug);
}
/*
$log = fopen("sql.txt", "w");
fwrite($log, $SQL_bebug);
fclose($log);
*/
?>