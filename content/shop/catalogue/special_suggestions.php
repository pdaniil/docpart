<?php
defined('_ASTEXE_') or die('No access');


//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");

//Указатель валюты
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/general/get_currency_indicator.php");


//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя


//Подключаем скрипт с общей функцией вывода блока товара ( printProductBlock(product) )
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/helper.php");


//СТИЛЬ БЛОКА ДЛЯ ТОВАРА
$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-3 col-lg-1-5";


//ТИП БЛОКА (1,2,3,4,5)
$product_block_type = 5;//ТИП ДЛЯ БЛОКОВ ТОВАРОВ НА ГЛАВНОЙ


//Исходные данные:
$groups_query = $db_link->prepare('SELECT * FROM `shop_main_page_groups` WHERE `active` = 1 ORDER BY `order`;');
$groups_query->execute();
while( $group_record = $groups_query->fetch() )//Цикл по группам товаров
{
	//Получаем товары группы
	$products_list = array();
	$products_list_comma = "";
	
	$products_query = $db_link->prepare('SELECT `product_id` FROM `shop_main_page_products` WHERE `group_id` = :group_id ORDER BY `order`;');
	$products_query->bindValue(':group_id', $group_record["id"]);
	$products_query->execute();
	while( $product_record = $products_query->fetch())
	{
		$product_record["product_id"] = (int)$product_record["product_id"];
		
		array_push($products_list, $product_record["product_id"]);
		
		
		if($products_list_comma != "")
		{
			$products_list_comma = $products_list_comma . ",";
		}
		$products_list_comma = $products_list_comma . $product_record["product_id"];
	}
	
	//ЗДЕСЬ ДЕЛАЕМ ЕДИНЫЙ SQL-ЗАПРОС НА ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ
	$SQL = "";//Единственный запрос для получения всех нужных товаров
	
	//Подстрока для умножение цены на курс валюты склада
	$SQL_currency_rate = '(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )';
	
	$sql_args_array = array();
	
	//По всем доступным магазинам
	for($i = 0; $i < count($customer_offices); $i++)
	{
		if($i > 0)
		{
			$SQL = $SQL . ' UNION ';
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
				
				LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price` *'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
				
				LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
				
				LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
				
				WHERE
					shop_catalogue_products.id IN ('.$products_list_comma.') ) AS `all` ';
		
		array_push($sql_args_array, $customer_offices[$i]);
		array_push($sql_args_array, $customer_offices[$i]);
		array_push($sql_args_array, $customer_offices[$i]);
		array_push($sql_args_array, $group_id);
		array_push($sql_args_array, $customer_offices[$i]);
	}
	
	$SQL = 'SELECT * FROM ( '.$SQL.' ) AS all_offices';//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам
	
	
	//Подключаем скрипт генерации объектов товаров единого формата ( $products_objects )
	require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/generate_products_objects_by_sql.php");
	
	
	
	

	if( count($products_objects) != 0)//Если объекты товаров
	{
		?>
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<?php
		
		if( $group_record["show_caption"] == true )//Если нужно показать название блока
		{
			?>
			<h2 class="section-title"><?php echo $group_record["caption"]; ?></h2>
			<?php
		}
		
		foreach( $products_objects AS $product_id => $product )
		{
			printProductBlock($product);
		}
		?>
		</div>
		<?php
	}
}

//Функция добавления в корзину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/common_add_to_basket.php");
?>