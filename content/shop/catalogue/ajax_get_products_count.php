<?php
/**
 * Серверный скрипт для получения количества товаров без учета лимита по страницам
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


//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя


//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");


//Получаем объект запроса
$propucts_request = json_decode($_POST["propucts_request"], true);
$category_id = $propucts_request["category_id"];
$properties_list = $propucts_request["properties_list"];
$product_block_type = $propucts_request["product_block_type"];//Тип страницы (1 - отображения для покупателя; 2 - для администратора каталога; 3 - для кладовщика; 4 - для покупателя при поиске через текстовую строку)



//Для фильтрования товаров по цене (только, если требуется)
$need_price_filter = false;
$price_object = NULL;


//Подстрока для умножение цены на курс валюты склада
$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";


//ФОРМИРУЕМ ЗАПРОС НА ПОЛУЧЕНИЕ ОБЪЕКТОВ ТОВАРОВ
/*
В зависимости от типа блока формируем SQL-запрос
*/

//Страница категории товаров - вывод для покупателя с ценами
if($product_block_type == 1)
{
	//Подключение построение запроса
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_count_mode_1.php");
}
else if($product_block_type == 2 || $product_block_type == 3)
{
	// ------------------------------------------------------------------
	//Сначала получаем список товаров, которые подходят по запросу:
    $search_string = trim(htmlspecialchars(strip_tags($propucts_request["search_string"])));
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/text_search_algorithm.php");//ЕДИНЫЙ АЛГОРИТМ ПОИСКА ТОВАРА ПО ТЕКСТОВОЙ СТРОКЕ
    
    //Составим строку с id товаров вида (1,2,3). $products_list - массив с id товаров, который заполнен в скрипте единого алгоритма
    $products_ids_str = "";
    for($i=0; $i < count($products_list); $i++)
    {
		$products_list[$i] = (int)$products_list[$i];
		
        if($products_ids_str != "") $products_ids_str = $products_ids_str.",";
        $products_ids_str = $products_ids_str.$products_list[$i];
    }
	
	if($products_ids_str === "")
	{
		$products_ids_str = "0";
	}
	// ------------------------------------------------------------------
	
	
	
	
	
	//Параметры сортировки
	$ASC_DESC_FIELD = "shop_catalogue_products.caption";//В данном блоке можно сортировать только по имени	
	if( isset($propucts_request["products_sort_mode"]["asc_desc"]) )
	{
		if( strtolower($propucts_request["products_sort_mode"]["asc_desc"]) == "asc" )
		{
			$ASC_DESC_DIR = "ASC";
		}
		else
		{
			$ASC_DESC_DIR = "DESC";
		}
	}
	else
	{
		$ASC_DESC_DIR = "ASC";
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
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM shop_properties_values_int WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) >= ? AND (SELECT `value` FROM shop_properties_values_int WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) <= ? )';
				
				
				array_push($sql_args_array, $property_id);
				array_push($sql_args_array, $properties_list[$i]["min_need"]);
				array_push($sql_args_array, $property_id);
				array_push($sql_args_array, $properties_list[$i]["max_need"]);
				
				break;
			case 2:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ( (SELECT `value` FROM shop_properties_values_float WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) >= ? AND (SELECT `value` FROM shop_properties_values_float WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) <= ? )';
				
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
					$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND (SELECT `value` FROM shop_properties_values_bool WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) = '.$need_value;
					
					array_push($sql_args_array, $property_id);
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
					if($list_options[$o]["value"] == true)
					{
						if($SQL_VALUES_COND != "")$SQL_VALUES_COND .= ' '.$OR_AND.' ';
						$SQL_VALUES_COND = $SQL_VALUES_COND . ' (SELECT `value` FROM shop_properties_values_list WHERE product_id = shop_catalogue_products.id AND `property_id` = ? AND value = ?) ';
						
						array_push($sql_args_array, $property_id);
						array_push($sql_args_array, $list_options[$o]["id"]);
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
					
					array_push($sql_args_array, $property_id);
					array_push($sql_args_array, $current_value);
				}
				break;
		}//switch($property_type_id)
	}
	

	$SQL = "";//Единственный запрос для получения всех нужных товаров
	
	$SQL = '
		SELECT 
			* 
		FROM 
			(SELECT
				shop_catalogue_products.id AS id,
				shop_catalogue_products.caption AS caption,
				shop_catalogue_products.alias AS alias,
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
				(SELECT file_name FROM shop_products_images WHERE product_id = shop_catalogue_products.id LIMIT 1) AS file_name
			FROM
				shop_catalogue_products

			LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id

			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id

			WHERE
				shop_catalogue_products.id IN('.$products_ids_str.') AND shop_catalogue_products.category_id = ? '.$SQL_PROPERTIES_CONDITIONS.'

			ORDER BY '.$ASC_DESC_FIELD.' '.$ASC_DESC_DIR.') AS `all` ';
			
			
			array_unshift($sql_args_array, $category_id);
}
else if($product_block_type == 4)
{
	//Подключение построение запроса
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_count_mode_4.php");
}





$SQL = 'SELECT COUNT(DISTINCT(`id`)) AS `count_total` FROM ('.$SQL.') AS `all`;';

/*
$log = fopen("log.txt", "w");
fwrite($log, $SQL);
fwrite($log, print_r($sql_args_array, true));
fwrite($log, print_r($properties_list, true));
fclose($log);
*/

$count_total_query = $db_link->prepare($SQL);
$count_total_query->execute($sql_args_array);
$count_total_record = $count_total_query->fetch();
echo $count_total_record["count_total"];
?>