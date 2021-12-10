<?php
//Построение запроса для получения количества товаров для режима 1
//Получаем объект свойства Цена
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

$sql_args_array = array();


//ФОРМИРУЕМ СТРОКИ УСЛОВИЙ ДЛЯ СВОЙСТВ
$SQL_PROPERTIES_CONDITIONS = "";
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
			$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM `shop_properties_values_int` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?) >= ? AND (SELECT `value` FROM `shop_properties_values_int` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?) <= ? )';
			array_push($sql_args_array, $property_id);
			array_push($sql_args_array, $properties_list[$i]["min_need"]);
			array_push($sql_args_array, $property_id);
			array_push($sql_args_array, $properties_list[$i]["max_need"]);
			break;
		case 2:
			$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM `shop_properties_values_float` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?) >= ? AND (SELECT `value` FROM `shop_properties_values_float` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?) <= ? )';
			
			array_push($sql_args_array, $property_id);
			array_push($sql_args_array, $properties_list[$i]["min_need"]);
			array_push($sql_args_array, $property_id);
			array_push($sql_args_array, $properties_list[$i]["max_need"]);
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
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND (SELECT `value` FROM `shop_properties_values_bool` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?) = ?';
				
				array_push($sql_args_array, $property_id);
				array_push($sql_args_array, $need_value);
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
						$SQL_VALUES_COND = $SQL_VALUES_COND . ' (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ? AND `value` = ?) ';
						
						array_push($sql_args_array, $property_id);
						array_push($sql_args_array, $list_options[$o]["id"]);
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
				
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( SELECT `value` FROM `shop_properties_values_tree_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ? AND `value` = ? ) ';
				
				array_push($sql_args_array, $property_id);
				array_push($sql_args_array, $current_value);
			}
			break;
	}//switch($property_type_id)
}


$sql_args_array_constant = $sql_args_array;


$SQL = "";//Единственный запрос для получения всех нужных товаров

$sql_args_array_pre = array();

//По всем доступным магазинам
for($i = 0; $i < count($customer_offices); $i++)
{
	if($i > 0)
	{
		$SQL = $SQL . " UNION ";
	}
	
	$SQL = $SQL . 
		'SELECT 
			* 
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
				shop_products_stickers.`value` AS sticker_value,
				shop_products_stickers.id AS sticker_id,
				shop_products_stickers.color_text AS sticker_color_text,
				shop_products_stickers.color_background AS sticker_color_background,
				shop_products_stickers.href AS sticker_href,
				shop_products_stickers.class_css AS sticker_class_css,
				shop_products_stickers.description AS sticker_description,
				shop_catalogue_categories.url AS category_url,
				shop_catalogue_categories.id AS category_id,
				(SELECT file_name FROM shop_products_images WHERE product_id = shop_catalogue_products.id LIMIT 1) AS file_name,
				? AS office_id,
				(SELECT additional_time FROM shop_offices_storages_map WHERE office_id = ? AND storage_id = shop_storages_data.storage_id LIMIT 1) AS additional_time
			FROM
				shop_catalogue_products

			LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `category_id` = ? AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
			
			WHERE
				shop_catalogue_products.category_id = ? AND shop_storages_data.customer_price >= ? AND shop_storages_data.customer_price <= ? '.$SQL_PROPERTIES_CONDITIONS.') AS `all` ';
				
	array_push($sql_args_array_pre, $customer_offices[$i]);
	array_push($sql_args_array_pre, $customer_offices[$i]);
	array_push($sql_args_array_pre, $customer_offices[$i]);
	array_push($sql_args_array_pre, $group_id);
	array_push($sql_args_array_pre, $category_id);
	array_push($sql_args_array_pre, $customer_offices[$i]);
	array_push($sql_args_array_pre, $category_id);
	array_push($sql_args_array_pre, $min_price);
	array_push($sql_args_array_pre, $max_price);
	
	
	if( $i == 0 )
	{
		$sql_args_array = array_merge($sql_args_array_pre, $sql_args_array_constant);
		$sql_args_array_pre = array();
	}
	else
	{
		$sql_args_array = array_merge($sql_args_array, $sql_args_array_pre);
		$sql_args_array = array_merge($sql_args_array, $sql_args_array_constant);
		$sql_args_array_pre = array();
	}
}

//$general_sql_args_array = array_merge($sql_args_array_pre, $sql_args_array);
//$sql_args_array = $general_sql_args_array;

$SQL = 'SELECT * FROM ( '.$SQL.' ) AS all_offices';//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам
?>