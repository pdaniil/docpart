<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


class treelax_catalogue_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		$this->result = 0;//По умолчанию
        
		$binding_values = array();
		
        //Формируем список артикулов
        $articles_list = array();
        array_push($articles_list, $article);
        //Здесь добавляем артикулы из списка аналогов:
        //...
        
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

			(SELECT `alias` FROM `shop_catalogue_products` WHERE `id` = `shop_properties_values_text`.`product_id`) AS `product_alias`,

			(SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = `shop_properties_values_text`.`category_id`) AS `category_url`,

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
        require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
        
        //ТЕПЕРЬ ПО КАЖДОМУ ТОВАРУ ИЗ СПИСКА
        while($treelax_product_record = $products_ids_query->fetch())
        {
            //Получаем данные товара
            $product_id = $treelax_product_record["product_id"];
            
			$manufacturer = $treelax_product_record["manufacturer"];
            $caption = $treelax_product_record["caption"];
            $article = $treelax_product_record["article"];
            $url = "/".$treelax_product_record["category_url"]."/".$treelax_product_record["product_alias"];
            
            //Для каждого магазина получить список складов и опросить каждый склад
            for($i=0; $i < count($customer_offices); $i++)
            {
                $office_id = $customer_offices[$i];
                
				$storages_query = $db_link->prepare('SELECT DISTINCT(`storage_id`), `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = :office_id;');
				$storages_query->bindValue(':office_id', $customer_offices[$i]);
				$storages_query->execute();
                while( $storage = $storages_query->fetch() )
                {
                    $storage_id = $storage["storage_id"];
                    $additional_time = $storage["additional_time"];
                    

					//Получаем id товаров по цене с данного склада
					$product_query = $db_link->prepare('SELECT *, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = ?) ) AS `rate` FROM `shop_storages_data` WHERE `product_id` = ? AND `exist` > 0 AND storage_id = ?;');
					$product_query->execute( array($storage_id, $product_id, $storage_id) );
					while( $product = $product_query->execute() )
					{
						$storage_record_id = $product["id"];
						$exist = $product["exist"];
						$price = $product["price"];
						
						//Считаем срок доставки
						if($product["arrival_time"] > time())
						{
							$time = $product["arrival_time"] + $additional_time*3600;
							
							$time_to_exe = (int)(($time - time())/86400);
						}
						else
						{
							$time_to_exe = (int)($additional_time/24);
						}
						

						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						
						
						//Создаем объект товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct($manufacturer,//Ok
							$article,//Ok
							$caption,//Ok
							$exist,//Ok
							$price + $price*$markup,//Ok
							$time_to_exe,//Ok
							$time_to_exe,//Ok
							"Каталог",//Название склада
							1,//Ok
							$storage_options["probability"],//Ok
							$office_id,//Ok
							$storage_id,//Ok
							$storage_options["office_caption"],// Название офиса
							$storage_options["color"],//Ok
							$storage_options["storage_caption"],// Название склада
							0,
							$markup,
							1,
							$product_id,
							$storage_record_id,
							$url,null,array("rate"=>$product["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							//Пересчитываем хеш для product_type = 1
							$DocpartProduct->check_hash = md5($product_id.$office_id.$storage_id.$storage_record_id.$DocpartProduct->price.$DP_Config->tech_key);
							
							
							array_push($this->Products, $DocpartProduct);
						}
						
					} 
                }
            }//for($i) - по магазинам
        }

		$this->result = 1;
	}//~function __construct($article)
};//~class treelax_catalogue_enclosure



$ob = new treelax_catalogue_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
//$ob = new treelax_catalogue_enclosure("FD24", NULL);
exit(json_encode($ob));
?>