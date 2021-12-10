<?php
/*Скрипт для страницы закладок*/
defined('_ASTEXE_') or die('No access');


//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");


//Техническая информация по интернет-магазину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");


//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя


//Функция добавления в корзину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/common_add_to_basket.php");


//Подключаем скрипт с общей функцией вывода блока товара ( printProductBlock(product) )
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/helper.php");
//ТИП БЛОКА (1,2,3,4,5,6)
$product_block_type = 6;//ТИП ДЛЯ БЛОКОВ ТОВАРОВ В ЗАКЛАДКАХ



//СТИЛЬ БЛОКА ДЛЯ ТОВАРА
$products_style = "";
if( isset($_COOKIE["products_style"]) )
{
	$products_style = $_COOKIE["products_style"];
}
switch($products_style)
{
	case 1:
		$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-4 col-lg-3";
		break;
	case 2:
		$main_class_of_block = "product_div_list_photo col-lg-12";//Список с фото
		break;
	case 3:
		$main_class_of_block = "product_div_list col-lg-12";//Список без фото
		break;
	default:
		$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-4 col-lg-3";
}
?>





<?php
//Получаем закладки
$bookmarks = NULL;
if(isset($_COOKIE["bookmarks"]))
{
	$bookmarks = $_COOKIE["bookmarks"];
}
if($bookmarks == NULL || $bookmarks == "[]")
{
	?>
	<p>Чтобы добавлять сюда закладки, нажимайте ссылку "В закладки" рядом с блоками товаров в каталоге</p>
	<p>Список Ваших закладок пока пуст</p>
	<?php
}
else//Есть закладки
{
	//ЗДЕСЬ ДЕЛАЕМ ЕДИНЫЙ SQL-ЗАПРОС НА ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ
	$SQL = "";//Единственный запрос для получения всех нужных товаров
	$SQL_LIGHT = "";//ТОЖЕ, ЧТО И $SQL, ТОЛЬКО БЕЗ ЛИШНИЙ ПОЛЕЙ. ДЛЯ ПОДЗАПРОСА С LIMIT. НУЖЕН, ЧТОБЫ НЕ ДЕЛАТЬ ВЫБОРКУ ЛИШНИЙ ПОЛЕЙ ПРИ ОТСЕИВАНИИ ПО LIMIT
	
	//Приводим значения к INT - чтобы исключить SQL-инъекцию
	$bookmarks = json_decode($bookmarks, true);
	for($b=0; $b < count($bookmarks); $b++)
	{
		$bookmarks[$b] = (int)$bookmarks[$b];
	}
	$bookmarks = json_encode($bookmarks);
	
	$bookmarks = str_replace( array("[", "]"), "", $bookmarks);

	//По всем доступным магазинам
	for($i = 0; $i < count($customer_offices); $i++)
	{
		if($i > 0)
		{
			$SQL = $SQL . " UNION ";
			$SQL_LIGHT = $SQL_LIGHT . " UNION ";
		}
		
		$SQL = $SQL . 
			"SELECT 
				* 
			FROM 
				(SELECT
					shop_catalogue_products.id AS id,
					shop_catalogue_products.caption AS caption,
					shop_catalogue_products.alias AS alias,
					shop_storages_data.id AS storage_record_id,
					shop_storages_data.storage_id AS storage_id,
					CAST(shop_storages_data.customer_price AS decimal(10,2)) AS customer_price,
					shop_storages_data.price AS price,
					shop_storages_data.price_crossed_out AS price_crossed_out,
					shop_storages_data.price_purchase AS price_purchase,
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
					".(int)$customer_offices[$i]." AS office_id,
					(SELECT additional_time FROM shop_offices_storages_map WHERE office_id = ".(int)$customer_offices[$i]." AND storage_id = shop_storages_data.storage_id LIMIT 1) AS additional_time,
					
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
				
				LEFT OUTER JOIN (SELECT *, `price` + `price` * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ".(int)$customer_offices[$i]." AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ".(int)$group_id." AND `shop_storages_data`.`price` >= `min_point` AND `shop_storages_data`.`price` < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ".(int)$customer_offices[$i].") ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
				
				LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
				
				LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
				
				WHERE
					shop_catalogue_products.id IN (".$bookmarks.") ) AS `all` ";
					
					
					
		$SQL_LIGHT = $SQL_LIGHT . 
			"SELECT 
				* 
			FROM 
				(SELECT
					shop_catalogue_products.id AS id,
					shop_catalogue_products.caption AS caption,
					CAST(shop_storages_data.customer_price AS decimal(10,2)) AS customer_price
				FROM
					shop_catalogue_products
				LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
				
				LEFT OUTER JOIN (SELECT *, `price` + `price` * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ".(int)$customer_offices[$i]." AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ".(int)$group_id." AND `shop_storages_data`.`price` >= `min_point` AND `shop_storages_data`.`price` < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ".(int)$customer_offices[$i].") ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
				
				WHERE
					shop_catalogue_products.id IN (".$bookmarks.") ) AS `all` ";
	}

	$SQL = "SELECT * FROM ( ".$SQL." ) AS all_offices";//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам
	

	//Подключаем скрипт генерации объектов товаров единого формата ( $products_objects )
	require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/generate_products_objects_by_sql.php");
	

	
	?>
	<div class="col-lg-12" id="products_area_turning">
		<div class="products_area_turning">
			<div class="showRestyle_name">Вид</div>
			<div class="showRestyle_wrap">
				<div class="showRestyle" id="showRestyle_1" onclick="showRestyle(1);"></div>
				<div class="showRestyle" id="showRestyle_2" onclick="showRestyle(2);"></div>
				<div class="showRestyle" id="showRestyle_3" onclick="showRestyle(3);"></div>
			</div>
		</div>
	</div>
	<script>
	<?php
	//Устновка стиля отображения товаров
    if(!empty($_COOKIE["products_style"]))
    {
        ?>
        var page_style = <?php echo (int)$_COOKIE["products_style"]; ?>;
        <?php
    }
    else
    {
        ?>
        var page_style = 1;
        <?php
    }
	?>
	document.getElementById("showRestyle_"+page_style).setAttribute("class", "showRestyle showRestyle_current");
	// -------------------------------------------------------------------------
	//Отобразить с другим стилем
	function showRestyle(style_code)
	{
		//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "products_style="+style_code+"; path=/; expires=" + date.toUTCString();
		
		//Перезагружаем страницу
		location.reload();
	}
	</script>
	
	
	
	<div class="col-lg-12">
	<?php
	foreach( $products_objects AS $id => $product )
	{
		echo printProductBlock($product);
	}
	?>
	</div>
	<?php
}
?>