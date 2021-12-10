<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


class treelax_catalogue_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		$this->status = 0;//По умолчанию
        
		$binding_values = array();
		
        //Формируем список артикулов
        $articles_list = array();
        array_push($articles_list, $article);
        
        //Теперь готовим строку вида (article1, article2) для SQL запросов
        $articles_list_str = "";
        for($i=0; $i < count($articles_list); $i++)
        {
			array_push($binding_values, $articles_list[$i]);
			
            if($i > 0) $articles_list_str .= ",";
            $articles_list_str .= "?";
        }
        $articles_list_str = "(".$articles_list_str.")";
        
        
        //Подключение к основной БД
        $DP_Config = new DP_Config;//Конфигурация CMS
        //Подключение к БД
		try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			return;
		}
		$db_link->query("SET NAMES utf8;");
        
        
        
        //Получаем ID товаров каталога, у которых совпал артикул
        /**
         * В данном запросе получаем в каждой записи:
         * - product_id //Ok
         * - category_id //Ok
         * - manufacturer_list_id (id линейного спика для поля Производитель) //Ok
         * - manufacturer_item_id (id производителя в структуре линейного списка)
         * - manufacturer_propery_id (id свойства типа "список" для производителя) //Ok
         * - manufacturer_line_list_structure (JSON-структура линейного списка)
         * - caption продукта //Ok
         * - product_alias продукта //Ok
         * - category_url категории //Ok
        */
        $SQL_SELECT_PRODUCTS_IDS = 'SELECT *,
			`value` AS `article`,

			(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_properties_values_text`.`product_id`) AS `caption`,
			(SELECT
			`shop_line_lists_items`.`value`
			FROM
			`shop_line_lists`,
			`shop_line_lists_items`
			INNER JOIN `shop_categories_properties_map` ON `shop_categories_properties_map`.`list_id` = `shop_line_lists_items`.`line_list_id`
			INNER JOIN `shop_properties_values_list` ON `shop_properties_values_list`.`property_id` = `shop_categories_properties_map`.`id` AND `shop_properties_values_list`.`value` = `shop_line_lists_items`.`id`
			WHERE
			`shop_categories_properties_map`.`property_type_id` = 5 AND
			`shop_categories_properties_map`.`value` = ? AND
			`shop_properties_values_list`.`product_id` = `shop_properties_values_text`.`product_id`
			LIMIT 1) AS `manufacturer`

			FROM `shop_properties_values_text`

			WHERE `value` IN '.$articles_list_str.';';
        
		array_unshift($binding_values, 'Производитель');
		$products_ids_query = $db_link->prepare($SQL_SELECT_PRODUCTS_IDS);
		$products_ids_query->execute($binding_values);
		
        //Техническая информация по интернте-магазину
        require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");
        
        //Получить список магазинов покупателя
        //require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
		
		$geo_id = $storage_options["geo_id"];
		
		if($geo_id == NULL)
		{
			$min_geo_id_query = $db_link->prepare('SELECT MIN(`id`) AS `id` FROM `shop_geo`;');
			$min_geo_id_query->execute();
			$min_geo_id_record = $min_geo_id_query->fetch();
			$geo_id = $min_geo_id_record["id"];
		}
		
		//Получаем список магазинов для данного географического узла
		$customer_offices = array();
		$offices_query = $db_link->prepare('SELECT `office_id` FROM `shop_offices_geo_map` WHERE `geo_id` = ?;');
		$offices_query->execute(array($geo_id));
		while($office = $offices_query->fetch())
		{
			array_push($customer_offices, $office["office_id"]);
		}
        
		
		
        //ТЕПЕРЬ ПО КАЖДОМУ ТОВАРУ ИЗ СПИСКА
        while($treelax_product_record = $products_ids_query->fetch())
        {
            //Получаем данные товара
            $product_id = $treelax_product_record["product_id"];
            
			$manufacturer = $treelax_product_record["manufacturer"];
            $caption = $treelax_product_record["caption"];
            $article = $treelax_product_record["article"];
            
            //Для каждого магазина получить список складов и опросить каждый склад
            for($i=0; $i < count($customer_offices); $i++)
            {
                $office_id = $customer_offices[$i];
                
				$storages_query = $db_link->prepare('SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ?;');
				$storages_query->execute(array($customer_offices[$i]));
                while($storage = $storages_query->fetch())
                {
                    $storage_id = $storage["storage_id"];
                    
					//Получаем id товаров по цене с данного склада
					$product_query = $db_link->prepare('SELECT *, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = ?) ) AS `rate` FROM `shop_storages_data` WHERE `product_id` = ? AND `exist` > 0 AND storage_id = ?;');
					$product_query->execute( array($storage_id, $product_id, $storage_id) );
					// Выбираем производителей только если есть наличие на складе
					while($product = $product_query->fetch())
					{
						$DocpartManufacturer = new DocpartManufacturer($manufacturer,
							0,
							$caption,
							$storage_options["office_id"],
							$storage_options["storage_id"],
							true
						);
						
						array_push($this->ProductsManufacturers, $DocpartManufacturer);
						
					}
                }
            }//for($i) - по магазинам
        }

		$this->status = 1;
	}//~function __construct($article)
};//~class treelax_catalogue_enclosure

/*
$f = fopen('log.txt', 'a');
fwrite($f, $_POST["article"] ."\n");
fwrite($f, $_POST["storage_options"] ."\n\n");
*/

//$_POST["article"] = 'OC247';
//$_POST["storage_options"] = '{"color":"#cefec7","probability":"100","office_id":"2","storage_id":"18","geo_id":"30"}';

$ob = new treelax_catalogue_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>