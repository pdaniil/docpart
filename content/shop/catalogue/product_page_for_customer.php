<?php
/**
 * Скрипт страницы продукта
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
//Указатель валюты
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/general/get_currency_indicator.php");


//Выводим страницу товара:
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printProduct_Info.php");
?>



<?php
//Выводим блок со всеми предложениями по данному продукту

//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");


//Техническая информация по интернте-магазину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");


//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя
$user_id = DP_User::getUserId();


//Подключаем скрипт с общей функцией вывода блока товара ( printProductBlock(product) )
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/helper.php");
//ТИП БЛОКА (1,2,3,4,5)
$product_block_type = 5;//ТИП ДЛЯ БЛОКОВ ТОВАРОВ НА ГЛАВНОЙ, СОПУТСТВУЮЩИХ ТОВАРОВ
//СТИЛЬ БЛОКА ДЛЯ ТОВАРА
//$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-4 col-lg-3";//4 колонки
$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-3 col-lg-1-5";//5 колонок


$product_id_fix = $product_id;//$product_id может переинициализироваться. Поэтому делаем еще одну переменную









//Подстрока для умножение цены на курс валюты склада
$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";
?>


<div class="container"><div class="row">
<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

<div class="product_suggestions">
    <div class="price">
        Цена
    </div>
    <div class="exist">
        Наличие
    </div>
    <div class="reserved">
        В резерве
    </div>
</div>


<?php
$time_now = time();
//Для каждого магазина получить список складов и опросить каждый склад
for($i=0; $i < count($customer_offices); $i++)
{
    $office_id = $customer_offices[$i];
    ?>
    <div class="product_office">
        <?php echo $offices_list[$office_id]["caption"]; ?>
    </div>
    <?php
    
	$storages_query = $db_link->prepare('SELECT DISTINCT(`storage_id`), `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ?;');
	$storages_query->execute( array($customer_offices[$i]) );
    while($storage = $storages_query->fetch())
    {
        $storage_id = $storage["storage_id"];
        $additional_time = $storage["additional_time"];
        
		//Получаем id товаров по цене с данного склада
		$product_query = $db_link->prepare('SELECT *, CAST(`price`*'.$SQL_currency_rate.' AS decimal(10,2)) AS `price` FROM `shop_storages_data` WHERE `product_id` = ? AND (`exist`>0 OR `reserved` > 0) AND `storage_id` = ?;');
		$product_query->execute( array($product_id, $storage_id) );
		while($product = $product_query->fetch())
		{
			$price = $product["price"];
			$storage_record_id = $product["id"];
			$exist = $product["exist"];
			$main_action_html = "";
			$div_id = $office_id."_".$storage_id."_".$storage_record_id;
			
			
			//Получаем наценку:
			$markup_query = $db_link->prepare('SELECT `markup`/100 as `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= ? AND `max_point` > ?;');
			$markup_query_args = array($office_id, $storage_id, $group_id, $price, $price);
			$markup_query->execute($markup_query_args);
			$markup_record = $markup_query->fetch();
			$price = $price + $price*$markup_record["markup"];//Накидываем наценку
			
			
			
			//ОБРАБАТЫВАЕМ ОКРУГЛЕНИЕ ЦЕН
			if($DP_Config->price_rounding == '1')//Без копеечной части
			{
				if( $price != (int)$price )
				{
					$price = (int)$price + 1;
				}
				else
				{
					$price = (int)$price;
				}
			}
			else if($DP_Config->price_rounding == '2')//До 5 руб
			{
				$price = (integer)$price;
				$price_str = (string)$price;
				$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
				if($price_str_last_char > 0 && $price_str_last_char < 5)
				{
					$price = $price + (5 - $price_str_last_char);
				}
				else if($price_str_last_char > 5 && $price_str_last_char <= 9)
				{
					$price = $price + (10 - $price_str_last_char);
				}
			}
			else if($DP_Config->price_rounding == '3')//До 10 руб
			{
				$price = (integer)$price;
				$price_str = (string)$price;
				$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
				if($price_str_last_char != 0)
				{
					$price = $price + (10 - $price_str_last_char);
				}
			}
			//ЗДЕСЬ МОЖНО ПРИВЕСТИ К НУЖНОМУ ФОРМАТУ (точки, количество знаков после запятой и т.д.)
			//...
			
			
			
			
			//Указываем проверочный хеш для предотвращения подмены данных злоумышленниками через Javascript
			$check_hash = md5($product_id.$office_id.$storage_id.$storage_record_id.$price.$DP_Config->tech_key);
			?>
			<div
				id = "<?php echo $div_id; ?>"
				product_id = "<?php echo $product_id; ?>"
				office_id = "<?php echo $office_id; ?>"
				storage_id = "<?php echo $storage_id; ?>"
				storage_record_id = "<?php echo $storage_record_id; ?>"
				price = "<?php echo $price; ?>"
				check_hash = "<?php echo $check_hash; ?>"
			></div>
			
			
			<div class="product_suggestions">
				<div class="price">
					<?php echo $price; ?>
				</div>
				<div class="exist">
					<?php echo $product["exist"]; ?>
				</div>
				<div class="reserved">
					<?php echo $product["reserved"]; ?>
				</div>
				<div class="exist_details">
					<?php
					if($product["arrival_time"] < $time_now && $additional_time == 0 && $exist > 0)
					{
						?>
						В наличии
						<?php
						$main_action_html = "<a class=\"btn btn-ar btn-primary\" href=\"javascript:void(0);\" onclick=\"purchase_action('$div_id');\">Купить</a>";
					}
					else if($exist > 0)
					{
						$days = array("в воскресенье", "в понедельник", "во вторник", "в среду", "в четверг", "в пятницу", "в субботу");
						$months = array("", "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");
						
						if($product["arrival_time"] > $time_now)
						{
							$time = $product["arrival_time"] + $additional_time*3600;
						}
						else
						{
							$time = $time_now + $additional_time*3600;
						}

						$text_info = "Можем привезти ".$days[date("w", $time)]." ".date("j", $time)." ".$months[date("n", $time)]." после ".date("H:i", $time);
						echo $text_info;
						
						$main_action_html = "<a class=\"btn btn-ar btn-primary\" href=\"javascript:void(0);\" onclick=\"purchase_action('$div_id');\">Заказать</a>";
					}
					else
					{
						?>
						Возможно освободится
						<?php
						$main_action_html = "<a class=\"btn btn-ar btn-primary disabled\" href=\"javascript:void(0);\" >Купить</a>";
					}
					?>
				</div>
				<div class="purchase">
					<?php echo $main_action_html; ?>
				</div>
			</div>
			<?php
		}
    }
}
?>

</div></div></div>









<?php
// -----------------------------------------------------------------------------------
// START ВАРИАНТЫ ИСПОЛНЕНИЯ ТОВАРА

//Для отсутствующих значений:
$for_null_of_lists = "Значение отсутствует";


//Получаем перечень свойств данной категории
$properties_list = array();
$category_properties_query = $db_link->prepare('SELECT *, (SELECT `type` FROM `shop_line_lists` WHERE `id` = `shop_categories_properties_map`.`list_id`) AS `list_type` FROM `shop_categories_properties_map` WHERE `category_id` = ?;');
$category_properties_query->execute( array($category_id) );
while( $property = $category_properties_query->fetch() )
{
	array_push($properties_list, array("id"=>$property["id"], "type_id"=>$property["property_type_id"], "list_id"=>$property["list_id"], "list_type"=>$property["list_type"])  );
}


$head_line_shown = false;//Флаг - заголовок ВСЕГО БЛОКА показан

//Блок с выводом вариантов исполнения товара
$options_properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = ? AND `is_option`=1;');
$options_properties_query->execute( array($category_id) );
while( $option_property = $options_properties_query->fetch() )
{
	//Название свойства
	$option_property_caption = $option_property["value"];
	
	$head_line_property_shown = false;//Флаг - заголовок СВОЙСТВА показан
	
	
	//Формируем подстроку с условие: нужны товары этой же категории, у которых равны все свойства кроме данногоё
	$SQL_PROPERTIES_CONDITIONS = "";
	$binding_args_params = array();
	for($i=0; $i < count($properties_list); $i++)
	{
		$property_type_id = (int)$properties_list[$i]["type_id"];
		$property_id = (int)$properties_list[$i]["id"];
		
		
		//Знак равенства
		$equal = " = ";
		if( $property_id == $option_property["id"] )
		{
			$equal = " != ";
		}
		
		switch( $property_type_id )
		{
			case 1:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT `value` FROM shop_properties_values_int WHERE product_id = shop_catalogue_products.id AND `property_id` = ?), 0) '.$equal.' IFNULL((SELECT `value` FROM shop_properties_values_int WHERE product_id = ? AND `property_id` = ?),0)';
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $product_id);
				array_push($binding_args_params, $property_id);
				
				break;
			case 2:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT `value` FROM shop_properties_values_float WHERE product_id = shop_catalogue_products.id AND `property_id` = ?),0) '.$equal.' IFNULL((SELECT `value` FROM shop_properties_values_float WHERE product_id = ? AND `property_id` = ?),0)';
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $product_id);
				array_push($binding_args_params, $property_id);
				
				break;
			case 3:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT `value` FROM shop_properties_values_text WHERE product_id = shop_catalogue_products.id AND `property_id` = ?),\'\') '.$equal.' IFNULL((SELECT `value` FROM shop_properties_values_text WHERE product_id = ? AND `property_id` = ?),\'\')';
				
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $product_id);
				array_push($binding_args_params, $property_id);
				
				break;
			case 4:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT `value` FROM shop_properties_values_bool WHERE product_id = shop_catalogue_products.id AND `property_id` = ?),0) '.$equal.' IFNULL((SELECT `value` FROM shop_properties_values_bool WHERE product_id = ? AND `property_id` = ?),0)';
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $product_id);
				array_push($binding_args_params, $property_id);
				
				break;
			case 5:
				if((int)$properties_list[$i]["list_type"] == 1)
				{
					//Для списка с единичным выбором
					$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT `value` FROM shop_properties_values_list WHERE product_id = shop_catalogue_products.id AND `property_id` = ?), ?) '.$equal.' IFNULL((SELECT `value` FROM shop_properties_values_list WHERE product_id = ? AND `property_id` = ?), ?)';
					
					array_push($binding_args_params, $property_id);
					array_push($binding_args_params, $for_null_of_lists);
					array_push($binding_args_params, $product_id);
					array_push($binding_args_params, $property_id);
					array_push($binding_args_params, $for_null_of_lists);
				}
				else
				{
					//Для списка с множественным выбором
					$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT group_concat(`value`) FROM shop_properties_values_list WHERE product_id = shop_catalogue_products.id AND `property_id` = ?), ?) '.$equal.' IFNULL((SELECT group_concat(`value`) FROM shop_properties_values_list WHERE product_id = ? AND `property_id` = ?),?)';
					
					array_push($binding_args_params, $property_id);
					array_push($binding_args_params, $for_null_of_lists);
					array_push($binding_args_params, $product_id);
					array_push($binding_args_params, $property_id);
					array_push($binding_args_params, $for_null_of_lists);
				}
				break;
			case 6:
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND IFNULL((SELECT group_concat(`value`) FROM shop_properties_values_tree_list WHERE product_id = shop_catalogue_products.id AND `property_id` = ?), ?) '.$equal.' IFNULL((SELECT group_concat(`value`) FROM shop_properties_values_tree_list WHERE product_id = ? AND `property_id` = ?), ?)';
				
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $for_null_of_lists);
				array_push($binding_args_params, $product_id);
				array_push($binding_args_params, $property_id);
				array_push($binding_args_params, $for_null_of_lists);
				
				break;
		}
	}
	
	
	
	//Подстрока для получения значения свойства (варианта исполнения), которое отличается от данного товара
	$SQL_different_option_value = "";
	$binding_args_different_option_value = array();
	switch($option_property["property_type_id"])
	{
		case 1:
			$SQL_different_option_value = 'SELECT IFNULL(`value`, ?) FROM `shop_properties_values_int` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?';
			break;
		case 2:
			$SQL_different_option_value = 'SELECT IFNULL(`value`, ?) FROM `shop_properties_values_float` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?';
			break;
		case 3:
			$SQL_different_option_value = 'SELECT IFNULL(`value`, ?) FROM `shop_properties_values_text` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?';
			break;
		case 4:
			$SQL_different_option_value = 'SELECT IFNULL(`value`, ?) FROM `shop_properties_values_bool` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?';
			break;
		case 5:
			$SQL_different_option_value = 'SELECT IFNULL(group_concat(`value`), ?) FROM `shop_line_lists_items` WHERE `id` IN (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?)';
			break;
		case 6:
			$SQL_different_option_value = 'SELECT IFNULL(group_concat(`value`), ?) FROM `shop_tree_lists_items` WHERE `id` IN (SELECT `value` FROM `shop_properties_values_tree_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ?)';
			break;
	}
	array_push($binding_args_different_option_value, $for_null_of_lists);
	array_push($binding_args_different_option_value, $option_property["id"]);
	
	
	
	$SQL_options_products = "SELECT 
				* 
			FROM 
				(SELECT
					shop_catalogue_products.id AS id,
					shop_catalogue_products.caption AS caption,
					shop_catalogue_products.alias AS alias,
					shop_catalogue_categories.url AS category_url,
					(".$SQL_different_option_value.") AS `different_option_value`
				FROM
					shop_catalogue_products
				LEFT OUTER JOIN shop_catalogue_categories ON shop_catalogue_products.category_id = shop_catalogue_categories.id
				WHERE
					shop_catalogue_products.category_id = ? ".$SQL_PROPERTIES_CONDITIONS.") AS `all` ";
	$sql_args_array = array();
	array_push($sql_args_array, $category_id);
	
	$sql_args_array = array_merge($binding_args_different_option_value, $sql_args_array);
	$sql_args_array = array_merge($sql_args_array, $binding_args_params);
	
	
	//Получаем все товары, у которых совпали с данным товаром все свойства, кроме этого, т.е. получаем "варианты исполнения" данного товара
	$options_products_query = $db_link->prepare($SQL_options_products);
	$options_products_query->execute($sql_args_array);
	while( $option_product = $options_products_query->fetch() )
	{
		//Здесь выводим ссылки на страницы других товаров
		if( ! $head_line_shown  )
		{
			?>
			<div class="container"><div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<hr class="dotted">
				<h2>Другие варианты исполнения</h2>
			<?php
			$head_line_shown = true;
		}
		
		
		if( ! $head_line_property_shown  )
		{
			?>
			<p class="product-option-message">Есть такой же товар, но с другим значением свойства <b>"<?php echo $option_property_caption; ?>"</b>:</p>
			<?php
			$head_line_property_shown = true;
		}
		
		
		$product_url = $option_product["alias"];
		if( $DP_Config->product_url != "alias" )
		{
			$product_url = $option_product["id"];
		}
		?>
		<a class="product-option-variant" href="/<?php echo $option_product["category_url"]; ?>/<?php echo $product_url; ?>" title="Такой же товар, но с другим значением свойства <?php echo $option_property_caption; ?>: <?php echo $option_product["different_option_value"]; ?>"><?php echo $option_product["different_option_value"]; ?></a>
		<?php
	}
}

if( $head_line_shown  )
{
	?>
	</div></div></div>
	<?php
}

// END ВАРИАНТЫ ИСПОЛНЕНИЯ ТОВАРА
// -----------------------------------------------------------------------------------
?>
















<?php
//----------------------------------------------------------------------------------------------------------------
//START БЛОК СОПУТСТВУЮЩИХ ТОВАРОВ


//ЗДЕСЬ ДЕЛАЕМ ЕДИНЫЙ SQL-ЗАПРОС НА ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ
$SQL = "";//Единственный запрос для получения всех нужных товаров

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
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
			
			WHERE
				shop_catalogue_products.id IN (SELECT product_id_related FROM shop_related_products WHERE product_id = ? ORDER BY `order`) ) AS `all` ';
				
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $group_id);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $product_id);
}

$SQL = "SELECT * FROM ( ".$SQL." ) AS all_offices";//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам


//Подключаем скрипт генерации объектов товаров единого формата ( $products_objects )
require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/generate_products_objects_by_sql.php");





if( count($products_objects) != 0)//Если объекты товаров
{
	?>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<hr class="dotted">
		<h2>Сопутствующие товары</h2>
		<?php
		foreach( $products_objects AS $id => $product )
		{
			printProductBlock($product);
		}
		?>
	</div>
	<?php
}

//EDN БЛОК СОПУТСТВУЮЩИХ ТОВАРОВ
//----------------------------------------------------------------------------------------------------------------
?>













<?php
//----------------------------------------------------------------------------------------------------------------
//START БЛОК ПОХОЖИХ ТОВАРОВ
$product_id = 
//Получаем объект свойства Цена

//ФОРМИРУЕМ СТРОКИ УСЛОВИЙ ДЛЯ СВОЙСТВ
$SQL_PROPERTIES_CONDITIONS = "";
$binding_properties_array = array();

$for_similar_properties_query = $db_link->prepare('SELECT * FROM shop_categories_properties_map WHERE category_id = ? AND for_similar = 1;');
$for_similar_properties_query->execute( array($category_id) );
while( $for_similar_property = $for_similar_properties_query->fetch() )
{
	
	$property_type_id = $for_similar_property["property_type_id"];
	$property_id = $for_similar_property["id"];
	
	
	switch($property_type_id)
	{
		case 1:
		case 2:
		case 3:
			if( $property_type_id == 1 )$type_postfix = "int";
			if( $property_type_id == 2 )$type_postfix = "float";
			if( $property_type_id == 3 )$type_postfix = "text";
			
			
			$property_value_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_'.$type_postfix.'` WHERE `product_id` = :product_id_fix AND `property_id` = :property_id;');
			$property_value_query->bindValue(':product_id_fix', $product_id_fix);
			$property_value_query->bindValue(':property_id', $property_id);
			$property_value_query->execute();
			$property_value_record = $property_value_query->fetch();
			if( $property_value_record != false )
			{
				if( $property_type_id == 3 )$property_value_record["value"] = "'".$property_value_record["value"]."'";
				
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND (SELECT `value` FROM shop_properties_values_'.$type_postfix.' WHERE product_id = shop_catalogue_products.id AND `property_id` = ?) = ?';
				
				array_push($binding_properties_array, $property_id);
				array_push($binding_properties_array, $property_value_record["value"]);
			}
			break;
		case 4:
			//Получаем значение свойства
			$property_value_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_bool` WHERE `property_id` = :property_id AND `product_id` = :product_id_fix;');
			$property_value_query->bindValue(':property_id', $property_id);
			$property_value_query->bindValue(':product_id_fix', $product_id_fix);
			$property_value_query->execute();
			$property_value_record = $property_value_query->fetch();
			if( $property_value_record != false )
			{
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND (SELECT `value` FROM `shop_properties_values_bool` WHERE `product_id` = shop_catalogue_products.id AND `property_id` = ?) = ?';
				
				array_push($binding_properties_array, $property_id);
				array_push($binding_properties_array, $property_value_record["value"]);
			}
			break;
		case 5:
			$list_type = $for_similar_property["list_type"];//Тип списка
			$list_id = $for_similar_property["list_id"];//ID списка
			
			if($list_type == 1)
			{
				$OR_AND = "OR";
			}
			else if($list_type == 2)
			{
				$OR_AND = "AND";
			}
			
			$SQL_VALUES_COND = "";//Подстрока с условиями для поля value
			
			$list_options_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_list` WHERE `property_id` = :property_id AND `product_id` = :product_id_fix;');
			$list_options_query->bindValue(':property_id', $property_id);
			$list_options_query->bindValue(':product_id_fix', $product_id_fix);
			$list_options_query->execute();
			while( $list_option = $list_options_query->fetch() )
			{
				if($SQL_VALUES_COND != "")$SQL_VALUES_COND .= ' '.$OR_AND.' ';
				$SQL_VALUES_COND = $SQL_VALUES_COND . ' (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ? AND `value` = ?) ';
				
				array_push($binding_properties_array, $property_id);
				array_push($binding_properties_array, $list_option["value"]);
			}
			if($SQL_VALUES_COND != "")//Если строка заполнена, то по меньше мере одно значение отмечено, значит добавляем строку
			{
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ('.$SQL_VALUES_COND.')';
			}
			break;//case 5
		case 6:
			$list_id = $for_similar_property["list_id"];//ID списка
			
			$SQL_VALUES_COND = "";//Подстрока с условиями для поля value
			
			$list_options_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_tree_list` WHERE `property_id` = :property_id AND `product_id` = :product_id_fix;');
			$list_options_query->bindValue(':property_id', $property_id);
			$list_options_query->bindValue(':product_id_fix', $product_id_fix);
			$list_options_query->execute();
			while( $list_option = $list_options_query->fetch() )
			{
				if($SQL_VALUES_COND != "")$SQL_VALUES_COND .= ' OR ';
				$SQL_VALUES_COND = $SQL_VALUES_COND . ' (SELECT `value` FROM `shop_properties_values_tree_list` WHERE `product_id` = `shop_catalogue_products`.`id` AND `property_id` = ? AND `value` = ?) ';
				
				array_push($binding_properties_array, $property_id);
				array_push($binding_properties_array, $list_option["value"]);
			}
			if($SQL_VALUES_COND != "")//Если строка заполнена, то по меньше мере одно значение отмечено, значит добавляем строку
			{
				$SQL_PROPERTIES_CONDITIONS = $SQL_PROPERTIES_CONDITIONS . ' AND ('.$SQL_VALUES_COND.')';
			}
			
			break;
	}//switch($property_type_id)
}

$SQL = "";//Единственный запрос для получения всех нужных товаров
$SQL_LIGHT = "";//ТОЖЕ, ЧТО И $SQL, ТОЛЬКО БЕЗ ЛИШНИЙ ПОЛЕЙ. ДЛЯ ПОДЗАПРОСА С LIMIT. НУЖЕН, ЧТОБЫ НЕ ДЕЛАТЬ ВЫБОРКУ ЛИШНИЙ ПОЛЕЙ ПРИ ОТСЕИВАНИИ ПО LIMIT

$sql_args_array = array();
$sql_light_args_array = array();

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
			* 
		FROM 
			(SELECT
				shop_catalogue_products.id AS id,
				shop_catalogue_products.caption AS caption,
				shop_catalogue_products.alias AS alias,
				shop_storages_data.id AS storage_record_id,
				shop_storages_data.storage_id AS storage_id,
				CAST(shop_storages_data.customer_price AS decimal(10,2)) AS customer_price,
				CAST(shop_storages_data.price * '.$SQL_currency_rate.' AS decimal(10,2)) AS price,
				CAST(shop_storages_data.price_crossed_out * '.$SQL_currency_rate.' AS decimal(10,2)) AS price_crossed_out,
				CAST(shop_storages_data.price_purchase *'.$SQL_currency_rate.' AS decimal(10,2)) AS price_purchase,
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
			
			LEFT OUTER JOIN (SELECT *, `price`*'.$SQL_currency_rate.' + `price`*'.$SQL_currency_rate.' * (SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price`*'.$SQL_currency_rate.' < `max_point`) AS `customer_price` FROM `shop_storages_data` WHERE `category_id` = ? AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?) ) shop_storages_data ON shop_catalogue_products.id = shop_storages_data.product_id
			
			LEFT OUTER JOIN shop_products_text ON shop_catalogue_products.id = shop_products_text.product_id
			
			LEFT OUTER JOIN shop_products_stickers ON shop_catalogue_products.id = shop_products_stickers.product_id
			
			WHERE
				shop_catalogue_products.category_id = ? '.$SQL_PROPERTIES_CONDITIONS.' AND shop_catalogue_products.id != ?) AS `all` ';
				
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $group_id);
	array_push($sql_args_array, $category_id);
	array_push($sql_args_array, $customer_offices[$i]);
	array_push($sql_args_array, $category_id);
	$sql_args_array = array_merge($sql_args_array, $binding_properties_array);
	array_push($sql_args_array, $product_id_fix);
				
	$SQL_LIGHT = $SQL_LIGHT . 
		'SELECT 
			* 
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
				shop_catalogue_products.category_id = ? '.$SQL_PROPERTIES_CONDITIONS.' AND shop_catalogue_products.id != ?) AS `all` ';
	
	array_push($sql_light_args_array, $customer_offices[$i]);
	array_push($sql_light_args_array, $group_id);
	array_push($sql_light_args_array, $category_id);
	array_push($sql_light_args_array, $customer_offices[$i]);
	array_push($sql_light_args_array, $category_id);
	$sql_light_args_array = array_merge($sql_light_args_array, $binding_properties_array);
	array_push($sql_light_args_array, $product_id_fix);
}

$SQL = 'SELECT * FROM ( '.$SQL.' ) AS all_offices';//Объединяем выборку всех записей по всем магазинам. Т.е. это без лимита по страницам

//ЛИМИТ И СЛУЧАЙНЫЙ ПОРЯДОК
$SQL = $SQL . ' INNER JOIN (SELECT DISTINCT(`id`) FROM ( '.$SQL_LIGHT.' ORDER BY RAND()) AS limit_ids LIMIT 0, 5 ) limit_ids ON all_offices.id = limit_ids.id';


$sql_args_array = array_merge($sql_args_array, $sql_light_args_array);


//var_dump($sql_args_array);
//var_dump($SQL);


//Подключаем скрипт генерации объектов товаров единого формата ( $products_objects )
require($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/generate_products_objects_by_sql.php");





if( count($products_objects) != 0)//Если объекты товаров
{
	?>
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<hr class="dotted">
		<h2>Похожие товары</h2>
		<?php
		foreach( $products_objects AS $product_id => $product )
		{
			printProductBlock($product);
		}
		?>
	</div>
	<?php
}


//EDN БЛОК ПОХОЖИХ ТОВАРОВ
//----------------------------------------------------------------------------------------------------------------
?>


















<?php
//----------------------------------------------------------------------------------------------------------------
//START БЛОК ОТЗЫВОВ
?>

<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
	<hr class="dotted">
	<h2>Отзывы и оценки</h2>
</div>


<!-- Start Блок добавления отзыва -->
<div id="make_evaluation" class="col-xs-12 col-sm-12 col-md-12 col-lg-12">	
<?php
if($user_id > 0)
{
	//Проверяем наличие отзыва от данного пользователя
	$check_evaluation_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_products_evaluations` WHERE `product_id` = :product_id_fix AND `user_id` = :user_id;');
	$check_evaluation_query->bindValue(':product_id_fix', $product_id_fix);
	$check_evaluation_query->bindValue(':user_id', $user_id);
	$check_evaluation_query->execute();
	
	if( $check_evaluation_query->fetchColumn() == 0 )
	{
		//Выводим "Добавить отзыв"
		?>
		<div class="panel panel-primary" id="evaluations_form">
			<div class="panel-heading">Добавление отзыва о товаре</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="exampleInputPassword1">Ваша оценка</label>
					<div class="evaluations_mark">
						<i onclick="onStarPush(1);" class="fa fa-star-o em-primary star_evaluation"></i>
						<i onclick="onStarPush(2);" class="fa fa-star-o em-primary star_evaluation"></i>
						<i onclick="onStarPush(3);" class="fa fa-star-o em-primary star_evaluation"></i>
						<i onclick="onStarPush(4);" class="fa fa-star-o em-primary star_evaluation"></i>
						<i onclick="onStarPush(5);" class="fa fa-star-o em-primary star_evaluation"></i>
					</div>
				</div>
				<div class="form-group">
					<label for="exampleInputPassword1">Достоинства</label>
					<textarea class="form-control" rows="2" id="text_plus"></textarea>
				</div>
				<div class="form-group">
					<label for="exampleInputPassword1">Недостатки</label>
					<textarea class="form-control" rows="2" id="text_minus"></textarea>
				</div>
				<div class="form-group">
					<label for="exampleInputPassword1">Общие впечатления</label>
					<textarea class="form-control" rows="2" id="text"></textarea>
				</div>
				<div class="checkbox">
					<input type="checkbox" id="hide_user_data" />
					<label for="checkbox5">Скрыть мои данные</label>
				</div>
				<button id="sendEvaluation_Button" onclick="sendEvaluation();" class="btn btn-ar btn-primary">Опубликовать отзыв</button>
			</div>
		</div>
		<script>
		var evaluation_object = new Object;//Объект отзыва
		// ------------------------------------------------------
		//Обработка нажатия звезды
		function onStarPush(mark)
		{
			evaluation_object.mark = mark;
			
			evaluationReview();
		}
		// ------------------------------------------------------
		//Переотображение формы оценок
		function evaluationReview()
		{
			var fa_stars = document.getElementsByClassName("star_evaluation");
			for(var i=0; i < fa_stars.length; i++)
			{
				if(i+1 <= evaluation_object.mark)
				{
					fa_stars[i].setAttribute("class", "fa fa-star em-primary star_evaluation");
				}
				else
				{
					fa_stars[i].setAttribute("class", "fa fa-star-o em-primary star_evaluation");
				}
			}
		}
		// ------------------------------------------------------
		//Функция отправки отзыва
		function sendEvaluation()
		{
			if(evaluation_object.mark == undefined)
			{
				alert("Оцените товар по пятибальной шкале");
				return;
			}
			
			//Записываем достоинства, недостатки и общие впечатления
			evaluation_object.text_plus = document.getElementById("text_plus").value;
			evaluation_object.text_minus = document.getElementById("text_minus").value;
			evaluation_object.text = document.getElementById("text").value;
			
			if(evaluation_object.text_plus == "" && evaluation_object.text_minus == "" && evaluation_object.text == "")
			{
				alert("Необходимо заполнить хотя бы одно их текстовых полей: \"Достоинства\", \"Недостатки\" или \"Общие впечатления\"");
				return;
			}
			
			//Скрыть данные пользователя
			if(document.getElementById("hide_user_data").checked)
			{
				evaluation_object.hide_user_data = 1;
			}
			else
			{
				evaluation_object.hide_user_data = 0;
			}
			
			evaluation_object.product_id = <?php echo $product_id_fix; ?>;
			
			
			document.getElementById("sendEvaluation_Button").setAttribute("disabled", "disabled");
			
			console.log(evaluation_object);
			
			
			jQuery.ajax({
				type: "POST",
				async: true,
				url: "/content/shop/catalogue/evaluations/ajax_add_evaluation.php",
				dataType: "json",
				data: "evaluation_object="+encodeURI(JSON.stringify(evaluation_object)),
				success: function(answer)
				{
					if(answer.status == true)//Отзыв добавлен
					{
						//Выдаем сообщение, что отзыв добавлен
						alert("Спасибо! Ваш отзыв добавлен");
						
						//Убираем форму отправки отзыва
						var evaluations_form = document.getElementById("evaluations_form");
						evaluations_form.parentNode.removeChild(evaluations_form);
						
						//Обновляем список отзывов
						getProductEvaluations(0, "desc", 0);
						
						//Обновляем среднюю оценку
						getGeneralMark();
						
						//Показываем сообщение
						document.getElementById("make_evaluation").innerHTML = "<p>Вы уже опубликовали отзыв о данном товаре</p>";
					}
					else
					{
						alert("Серверная ошибка добавления отзыва");
					}
				}
			});
		}
		// ------------------------------------------------------
		</script>
		<?php
	}
	else
	{
		?>
		<p>Вы уже опубликовали отзыв о данном товаре</p>
		<?php
	}
}
else//Пользователь не авторизован
{
	?>
	<p>Добавлять отзывы могут только зарегистрированные покупатели</p>
	<?php
}
?>
</div>
<!-- End Блок добавления отзыва -->



<!-- Start Блок общей оценки -->
<div id="evaluations_general_mark" class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
</div>
<script>
//Функция обновления средней оценки
function getGeneralMark()
{
	jQuery.ajax({
		type: "POST",
		async: true,
		url: "/content/shop/catalogue/evaluations/ajax_get_product_general_mark.php",
		dataType: "json",
		data: "product_id=<?php echo $product_id_fix; ?>",
		success: function(answer)
		{
			console.log(answer);
			if(answer.status == true)
			{
				var general_mark_html = "";
				general_mark_html += "<h3 class=\"section-title\">Средняя оценка</h3> ";
				
				
				
				general_mark_html += "<div class=\"evaluations_mark\">";
					
					for(var i=0; i < 5; i++)
					{
						if( answer.general_mark < i+1 )
						{
							general_mark_html += "<i class=\"fa fa-star-o em-primary star_evaluation\"></i> ";
						}
						else
						{
							general_mark_html += "<i class=\"fa fa-star em-primary star_evaluation\"></i> ";
						}
					}
				general_mark_html += "Всего оценок: " + answer.marks_count + "</div>";
				
				
				
				general_mark_html += "<div><i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> " + answer.mark_1_count + "</div>";
				
				general_mark_html += "<div><i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> " + answer.mark_2_count + "</div>";
				
				general_mark_html += "<div><i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> " + answer.mark_3_count + "</div>";
				
				general_mark_html += "<div><i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star-o em-primary\"></i> " + answer.mark_4_count + "</div>";
				
				general_mark_html += "<div><i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> <i class=\"fa fa-star em-primary\"></i> " + answer.mark_5_count + "</div>";
			
				
				document.getElementById("evaluations_general_mark").innerHTML = general_mark_html;
			}
			else
			{
				alert("Серверная ошибка получения средней оценки");
			}
		}
	});
}
getGeneralMark();
</script>
<!-- End Блок общей оценки -->	

<!-- Start Блок списка отзывов покупателей -->
<div id="evaluations_area" class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
</div>
<script>
// ------------------------------------------------------------------------------
var CurrentPage = 0;//Текущая страница
//Функция получения всех отзывов о товаре
function getProductEvaluations(page, asc_desc, mark)
{
	var evaluation_query = new Object;
	
	evaluation_query.page = page;//Страница
	evaluation_query.product_id = <?php echo $product_id_fix; ?>;
	evaluation_query.asc_desc = asc_desc;//Сортировка
	evaluation_query.mark = mark;//Оценка. 0 - все
	
	jQuery.ajax({
		type: "POST",
		async: true,
		url: "/content/shop/catalogue/evaluations/ajax_get_product_evaluations.php",
		dataType: "json",
		data: "evaluation_query="+encodeURI(JSON.stringify(evaluation_query)),
		success: function(answer)
		{
			console.log(answer);
			if(answer.status == true)
			{
				var evaluations = answer.evaluations;
				var evaluations_html = "<h3 class=\"section-title\">Отзывы покупателей</h3>";
				
				for(var e = 0; e < evaluations.length; e++)
				{
					//Начало отзыва
					evaluations_html += "<div class=\"panel panel-default\">";
					
						//Заголовок
						evaluations_html += "<div class=\"panel-heading\">";
							//Пользовател и время
							evaluations_html += evaluations[e].time + ", " + evaluations[e].user_name + "<br>";
							//Оценка
							for(var i=0; i < 5; i++)
							{
								if( evaluations[e].mark < i+1 )
								{
									evaluations_html += "<i class=\"fa fa-star-o em-primary star_evaluation\"></i> ";
								}
								else
								{
									evaluations_html += "<i class=\"fa fa-star em-primary star_evaluation\"></i> ";
								}
							}
						evaluations_html +=  "</div>";
						
						
						evaluations_html += "<div class=\"panel-body\">";
							evaluations_html += "<strong>Достоинства:</strong> "+evaluations[e].text_plus + "<br>";
							evaluations_html += "<strong>Недостатки:</strong> "+evaluations[e].text_minus + "<br>";
							evaluations_html += "<strong>Общие впечатления:</strong> "+evaluations[e].text + "<br>";
						evaluations_html += "</div>";
					evaluations_html += "</div>";
				}
				
				
				//HTML-код переключателя страниц
				//Первая страница
				var first_page = "<li><a onclick=\"go_to_page(0);\" href=\"javascript:void(0);\">0</a></li>";
				if(CurrentPage == 0) first_page = "";
				//Последняя страница
				var pages_total = answer.pages_total;//Всего страниц
				var last_page = "<li><a onclick=\"go_to_page("+(pages_total-1)+");\" href=\"javascript:void(0);\">"+(pages_total-1)+"</a></li>";
				if(CurrentPage == (pages_total - 1)) last_page = "";
				//Текущая страница
				var current_page = "<li class=\"active\"><a onclick=\"\" href=\"javascript:void(0);\">"+CurrentPage+"</a></li>";
				//Пара от текущей справа
				var right_pages = "";
				for(var i = CurrentPage+1; i < (pages_total - 1) && i < CurrentPage + 4; i++)
				{
					if(i == CurrentPage+3)
					{
						right_pages += "<li><a onclick=\"go_to_page("+i+");\" href=\"javascript:void(0);\">...</a></li>";
					}
					else
					{
						right_pages += "<li><a onclick=\"go_to_page("+i+");\" href=\"javascript:void(0);\">"+i+"</a></li>";
					}
				}
				//Пара от текущей слева
				var left_pages = "";
				for(var i = CurrentPage-1; i > 0 && i > CurrentPage-4; i--)
				{
					if(i == CurrentPage-3)
					{
						left_pages = "<li><a onclick=\"go_to_page("+i+");\" href=\"javascript:void(0);\">...</a></li>" + left_pages;
					}
					else
					{
						left_pages = "<li><a onclick=\"go_to_page("+i+");\" href=\"javascript:void(0);\">"+i+"</a></li>" + left_pages;
					}
				}
				//Компонуем:
				var pages_selector_container = "<div class=\"col-lg-12 text-center\"><ul class=\"pagination pagination-sm\">"+first_page + left_pages + current_page + right_pages + last_page+"</ul></div>";
				
				if(pages_total == 1)
				{
					pages_selector_container = "";
				}
				
				if(evaluations.length == 0)
				{
					document.getElementById("evaluations_area").innerHTML = "<h3 class=\"section-title\">Отзывы покупателей</h3>Отзывы о товаре отсутствуют. Ваш отзыв может стать первым";
				}
				else
				{
					document.getElementById("evaluations_area").innerHTML = evaluations_html + pages_selector_container;
				}
			}
			else
			{
				alert("Серверная ошибка добавления отзыва");
			}
		}
	});
}
// ------------------------------------------------------------------------------
//Переход на требуемую страницу
function go_to_page(need_page)
{
	CurrentPage = need_page;
	getProductEvaluations(need_page, 'desc', 0);
}
// ------------------------------------------------------------------------------
getProductEvaluations(0, 'desc', 0);//После загрузки страницы получаем отзывы
</script>
<!-- End Блок списка отзывов покупателей -->
<?php
//END БЛОК ОТЗЫВОВ
//----------------------------------------------------------------------------------------------------------------
?>

















<?php
//Функция добавления в корзину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/common_add_to_basket.php");
?>