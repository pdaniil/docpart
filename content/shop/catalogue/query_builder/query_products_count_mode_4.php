<?php
//Построение запроса для получения количества товаров для режима 4
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


$products_ids_str = $propucts_request["products_ids_str"];
$products_ids = explode(',', $products_ids_str);
for($i=0; $i < count($products_ids); $i++)
{
	$products_ids[$i] = (int)$products_ids[$i];
}
$products_ids_str = json_encode($products_ids);
$products_ids_str = str_replace( array("[", "]"), "", $products_ids_str);

$sql_args_array = array();
$SQL = "";//Единственный запрос для получения всех нужных товаров

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
				(SELECT additional_time FROM shop_offices_storages_map WHERE `office_id` = ? AND storage_id = shop_storages_data.storage_id LIMIT 1) AS `additional_time`
			FROM
				shop_catalogue_products

			LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
			
			WHERE
				shop_catalogue_products.id IN('.$products_ids_str.') AND shop_storages_data.customer_price >= ? AND shop_storages_data.customer_price <= ?) AS `all` ';
				
	
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $group_id);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $min_price);
	array_push($sql_args_array, $max_price);
}

$SQL = "SELECT * FROM ( ".$SQL." ) AS all_offices";//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам
?>