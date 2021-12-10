<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


class treelax_catalogue_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options, $searsch_str = '')
	{
		$this->result = 0;//По умолчанию

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
        
        $binding_values = array();
		
		// Список всех синонимов производителей запрошенного артикула
		$manufacturers_list = '';
		for($m=0; $m<count($manufacturers);$m++)//Цикл по массиву брэндов
		{
			$manufacturer = $manufacturers[$m]['manufacturer'];
			if(!empty($manufacturer))
			{
				array_push($binding_values, $manufacturer);
				
				if(!empty($manufacturers_list))
				{
					$manufacturers_list .= ',';
				}
				$manufacturers_list .= "?";
			}
		}
		
		// Запрошенный артикул
		if(empty($manufacturers_list))
		{
			$SQL_WHERE = " (`t2`.`value` = ?) ";
		}
		else
		{
			$SQL_WHERE = " (`t2`.`value` = ? AND `t3`.`value` IN(". $manufacturers_list .")) ";
		}
		array_unshift($binding_values, $article);
		
		
		
		// Аналоги
		$analogs = $storage_options["analogs"];
		for($i=0; $i < count($analogs); $i++)
		{
			//СИНОНИМЫ
			$manufacturer_synonyms = '';
			$manufacturer_synonyms_binding = array();
			if(!empty($analogs[$i]["manufacturer"]))
			{
				$manufacturer_synonyms = "?";
				array_push($manufacturer_synonyms_binding, $analogs[$i]["manufacturer"]);
				
				$shop_docpart_manufacturer_id = false;
				$shop_docpart_manufacturer_name = false;
				
				$synonym_query = $db_link->prepare('SELECT `id` FROM `shop_docpart_manufacturers` WHERE `name` = ?;');
				$synonym_query->execute( array($analogs[$i]["manufacturer"]) );
				$synonym_record = $synonym_query->fetch();
				if( $synonym_record != false )
				{
					$shop_docpart_manufacturer_id = $synonym_record["id"];
				}
				else
				{
					$synonym_query = $db_link->prepare('SELECT `manufacturer_id` FROM `shop_docpart_manufacturers_synonyms` WHERE `synonym` = ?;');
					$synonym_query->execute( array($analogs[$i]["manufacturer"]) );
					$synonym_record = $synonym_query->fetch();
					if( $synonym_record != false )
					{
						$shop_docpart_manufacturer_id = $synonym_record["manufacturer_id"];
					}
				}
				
				if(!empty($shop_docpart_manufacturer_id))
				{
					$synonym_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = ?;');
					$synonym_query->execute( array($shop_docpart_manufacturer_id) );
					
					if( $synonym_query->fetchColumn() > 0 )
					{
						$query = $db_link->prepare('SELECT `name` FROM `shop_docpart_manufacturers` WHERE `id` = ?;');
						$query->execute( array($shop_docpart_manufacturer_id) );
						$record = $query->fetch();
						if($record["name"] !== $analogs[$i]["manufacturer"])
						{
							array_push($manufacturer_synonyms_binding, $record["name"]);
							$manufacturer_synonyms .= ",?";
						}
						
						$synonym_query = $db_link->prepare('SELECT `synonym` FROM `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = ?;');
						$synonym_query->execute( array($shop_docpart_manufacturer_id) );
						while($synonym_record = $synonym_query->fetch() )
						{
							if($synonym_record["synonym"] !== $analogs[$i]["manufacturer"])
							{
								array_push($manufacturer_synonyms_binding, $synonym_record["synonym"]);
								$manufacturer_synonyms .= ",?";
							}
						}
					}
				}
			}
			
			if(empty($manufacturer_synonyms))
			{
				$SQL_WHERE .= " OR (`t2`.`value` = ?) ";
			}
			else
			{
				$SQL_WHERE .= " OR (`t2`.`value` = ? AND `t3`.`value` IN (".$manufacturer_synonyms.")) ";
			}
			array_unshift($manufacturer_synonyms_binding, $analogs[$i]["article"]);
			
			$binding_values = array_merge($binding_values, $manufacturer_synonyms_binding);
		}
		
		
		
        //Получаем ID товаров каталога, у которых совпал артикул и производитель или наименование
		$SQL_SELECT_PRODUCTS_IDS = '
			SELECT `t1`.`id`, `t1`.`category_id`, `t1`.`caption`, `t1`.`alias` AS ?, `t2`.`value` AS ?, `t3`.`value` AS ?, `t4`.`content`,
			
			(SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = `t1`.`category_id`) AS `category_url`

			FROM `shop_catalogue_products` AS `t1`
			
			INNER JOIN `shop_properties_values_text` AS `t2`
				ON `t1`.`id` = `t2`.`product_id` 
					AND `t2`.`property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `t1`.`category_id` AND `value` = ? AND `property_type_id` = 3)
					
			INNER JOIN `shop_line_lists_items` AS `t3`
				ON `t3`.`id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `t1`.`id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `t1`.`category_id` AND `value` = ? AND `property_type_id` = 5) limit 1)
			
			INNER JOIN `shop_products_text` AS `t4`
				ON `t1`.`id` = `t4`.`product_id` 
			
			WHERE '.$SQL_WHERE.';';
		
		$binding_values_add = array('product_alias', 'article', 'manufacturer', 'Артикул', 'Производитель');
		
		$binding_values = array_merge($binding_values_add, $binding_values);
		
		$products_ids_query = $db_link->prepare($SQL_SELECT_PRODUCTS_IDS);
        $products_ids_query->execute($binding_values);
        
		/*
		$f = fopen('log_SQL.txt', 'w');
		fwrite($f, $SQL_SELECT_PRODUCTS_IDS);
		*/
		
        
        

        //Техническая информация по интернте-магазину
        require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");
        
        //Получить список магазинов покупателя
        //require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
		
		$geo_id = (int)$storage_options["geo_id"];
		
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
		$offices_query->execute( array($geo_id) );
		while($office = $offices_query->fetch())
		{
			array_push($customer_offices, $office["office_id"]);
		}
        
        //ТЕПЕРЬ ПО КАЖДОМУ ТОВАРУ ИЗ СПИСКА
        while($treelax_product_record = $products_ids_query->fetch() )
        {
            //Получаем данные товара
            $product_id = $treelax_product_record["id"];
            
			$manufacturer = $treelax_product_record["manufacturer"];
            $caption = $treelax_product_record["caption"];
            $article = $treelax_product_record["article"];
            
            if($DP_Config->product_url == 'id'){
				$url = "/".$treelax_product_record["category_url"]."/".$treelax_product_record["id"];
			}else{
				$url = "/".$treelax_product_record["category_url"]."/".$treelax_product_record["product_alias"];
			}
			
            //Для каждого магазина получить список складов и опросить каждый склад
            for($i=0; $i < count($customer_offices); $i++)
            {
                $office_id = $customer_offices[$i];
                
				$storages_query = $db_link->prepare('SELECT DISTINCT(`storage_id`), `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ?;');
				$storages_query->execute( array($customer_offices[$i]) );
                while($storage = $storages_query->fetch())
                {
                    $storage_id = $storage["storage_id"];
                    $additional_time = $storage["additional_time"];
                    

					//Получаем id товаров по цене с данного склада
					$product_query = $db_link->prepare('SELECT *, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = ?) ) AS `rate` FROM `shop_storages_data` WHERE `product_id` = ? AND `exist` > 0 AND storage_id = ?;');
					$product_query->execute( array($storage_id, $product_id, $storage_id) );
					while($product = $product_query->fetch())
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
						

						
						$markup_query = $db_link->prepare("SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id`=? AND `min_point` <= ? AND `max_point` > ?");
						$markup_query->execute( array($office_id, $storage_id, $storage_options["group_id"], $price, $price) );
						$markup = $markup_query->fetchColumn();
						
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
							$price,
							$markup,
							1,
							$product_id,
							$storage_record_id,
							$url,null,array("rate"=>$product["rate"])
							);
						
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
						
					} 
                }
            }//for($i) - по магазинам
        }

		
/* ..................................................................................... */
/* ............................... ПОИСК ПО НАИМЕНОВАНИЮ ............................... */
/* ..................................................................................... */

		if(!empty($searsch_str))
		{
			$binding_values = array();
			
			//Загшулка. Там в начале добавляется артикул. А раз поиск по наименованию, что артикул не учитываем
			array_push($binding_values, '');
			
			
			// Поиск по наименованию и описанию
			$SQL_searsch_str = '';
			$SQL_searsch_str_2 = '';
			
			$searsch_str = trim(strip_tags($searsch_str));
			$searsch_str = explode(' ',$searsch_str);// Разбиваем на отдельные слова
			if(!empty($searsch_str))
			{
				foreach($searsch_str as $item_str)
				{
					$item_str = trim($item_str);
					if(mb_strlen($item_str, 'utf-8') < 2)
					{
						continue;// Пропускаем шумовые слова
					}
					// Поиск по названию
					if($SQL_searsch_str != '')
					{
						$SQL_searsch_str .= " AND ";
					}
					$SQL_searsch_str .= "(`caption` LIKE ?)";
					
					array_push($binding_values, '%'.$item_str.'%');
					
					// Поиск по названию
					if($SQL_searsch_str_2 != '')
					{
						$SQL_searsch_str_2 .= " AND ";
					}
					$SQL_searsch_str_2 .= "(`content` LIKE ?)";
					array_push($binding_values, '%'.$item_str.'%');
				}
			}
			
			if($SQL_searsch_str != '')
			{
				$SQL_WHERE .= ' OR ('. $SQL_searsch_str .')';
			}
			if($SQL_searsch_str_2 != '')
			{
				$SQL_WHERE .= ' OR ('. $SQL_searsch_str_2 .')';
			}
			

			
			//Получаем ID товаров каталога, у которых совпал артикул и производитель или наименование
			$SQL_SELECT_PRODUCTS_IDS = '
				SELECT `t1`.`id`, `t1`.`category_id`, `t1`.`caption`, `t1`.`alias` AS ?, `t2`.`value` AS ?, `t3`.`value` AS ?, `t4`.`content`,
				
				(SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = `t1`.`category_id`) AS `category_url`

				FROM `shop_catalogue_products` AS `t1`
				
				INNER JOIN `shop_properties_values_text` AS `t2`
					ON `t1`.`id` = `t2`.`product_id` 
						AND `t2`.`property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `t1`.`category_id` AND `value` = ? AND `property_type_id` = 3)
						
				INNER JOIN `shop_line_lists_items` AS `t3`
					ON `t3`.`id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `t1`.`id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `t1`.`category_id` AND `value` = ? AND `property_type_id` = 5) limit 1)
				
				INNER JOIN `shop_products_text` AS `t4`
					ON `t1`.`id` = `t4`.`product_id` 
				
				WHERE '.$SQL_WHERE.';
			';
			
			$binding_values_add = array('product_alias', 'article', 'manufacturer', 'Артикул', 'Производитель');
			
			$binding_values = array_merge($binding_values_add, $binding_values);
			$products_ids_query = $db_link->prepare($SQL_SELECT_PRODUCTS_IDS);
			$products_ids_query->execute($binding_values);

			
			
			 //ТЕПЕРЬ ПО КАЖДОМУ ТОВАРУ ИЗ СПИСКА
			while($treelax_product_record = $products_ids_query->fetch())
			{
				//Получаем данные товара
				$product_id = $treelax_product_record["id"];
				
				$manufacturer = $treelax_product_record["manufacturer"];
				$caption = $treelax_product_record["caption"];
				$article_show = $treelax_product_record["article"];
				
				if($DP_Config->product_url == 'id'){
					$url = "/".$treelax_product_record["category_url"]."/".$treelax_product_record["id"];
				}else{
					$url = "/".$treelax_product_record["category_url"]."/".$treelax_product_record["product_alias"];
				}
				
				
				// Если артикул равен запрошенному артикулу то пропускаем так как товар будет отображен в другом блоке
				if($article_show === $article){
					continue;
				}
				
				
				
				//Для каждого магазина получить список складов и опросить каждый склад
				for($i=0; $i < count($customer_offices); $i++)
				{
					$office_id = $customer_offices[$i];
					
					$storages_query = $db_link->prepare('SELECT DISTINCT(`storage_id`), `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ?;');
					$storages_query->execute( array($customer_offices[$i]) );
					while($storage = $storages_query->fetch())
					{
						$storage_id = $storage["storage_id"];
						$additional_time = $storage["additional_time"];
						

						//Получаем id товаров по цене с данного склада
						$product_query = $db_link->prepare('SELECT *, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = ?) ) AS `rate` FROM `shop_storages_data` WHERE `product_id` = ? AND `exist` > 0 AND storage_id = ?;');
						$product_query->execute( array($storage_id, $product_id, $storage_id) );
						while($product = $product_query->fetch() )
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
							

							$markup_query = $db_link->prepare("SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id`=? AND `min_point` <= ? AND `max_point` > ?");
							$markup_query->execute( array($office_id, $storage_id, $storage_options["group_id"], $price, $price) );
							$markup = $markup_query->fetchColumn();
							
							
							//Создаем объект товара и добавляем его в список:
							$DocpartProduct = new DocpartProduct($manufacturer,//Ok
								$article_show,//Ok
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
								$price,
								$markup,
								1,
								$product_id,
								$storage_record_id,
								$url,null,array("rate" => $product["rate"], 'search_name' => 1)
								);
							
							if($DocpartProduct->valid == true)
							{
								array_push($this->Products, $DocpartProduct);
							}
							
						} 
					}
				}//for($i) - по магазинам
			}
		}
	
	
/* ..................................................................................... */
/* ........................................ END ........................................ */
/* ..................................................................................... */


		
		$this->result = 1;
	}//~function __construct($article)
};//~class treelax_catalogue_enclosure

/*
$f = fopen('log.txt', 'a');
fwrite($f, $_POST["article"] . "\n");
fwrite($f, $_POST["manufacturers"] . "\n");
fwrite($f, $_POST["storage_options"] . "\n\n");
*/

/*
$_POST["article"] = 'OC247';
$_POST["manufacturers"] = '[{"manufacturer":"Bridgestone","manufacturer_id":0,"manufacturer_show":"BRIDGESTONE","name":"Bridgestone Blizzak VRX","storage_id":"18","office_id":"2","synonyms_single_query":true,"params":null}]';
$_POST["storage_options"] = '{"color":"#cefec7","probability":"100","markups":["0.0000"],"office_id":"2","storage_id":"18","additional_time":"0","office_caption":"\u041e\u0441\u043d\u043e\u0432\u043d\u0430\u044f \u0442\u043e\u0447\u043a\u0430 \u043e\u0431\u0441\u043b\u0443\u0436\u0438\u0432\u0430\u043d\u0438\u044f","storage_caption":"","rate":"1","analogs":[{"alt":"alt","manufacturer":"CHRYSLER","article":"05003558AA"},{"alt":"alt","manufacturer":"CONTINENTAL","article":"OC247"},{"alt":"alt","manufacturer":"Bridgestone","article":"OC247"}],"geo_id":"30"}';
*/

$ob = new treelax_catalogue_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), json_decode($_POST["storage_options"], true), $_POST["searsch_str"]);
exit(json_encode($ob));
?>