<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

class prices_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturers, $storage_options, $searsch_str = '')
	{
		$this->result = 0;//По умолчанию
		
		/*
		$log = fopen("log1.txt", "w");
		fwrite($log, print_r($storage_options, true));
		fclose($log);
		*/
		
		/***** Настройки *****/
		$user_id = $storage_options["user_id"];//ID пользователя - для наценки
		$group_id = $storage_options["group_id"];
		$office_storage_bunches = $storage_options["office_storage_bunches"];
		/***** Настройки *****/
        
        
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



		// Аналоги
		//Из всех аналогов формируем несколько массиво, чтобы оптимизировать запрос
		//в базу данных
		$analogs_all = $storage_options["analogs"];
		$analogs_groups = array();
		$access_count = 300;
		$access_count_group = 1;
		
		for($k=0; $k < count($analogs_all); $k++)
		{
		    if($k < ($access_count * $access_count_group))
		    {
		        $analogs_groups[$access_count_group][] = $analogs_all[$k];
		    }
		    else
		    {
		        $access_count_group++;
		        $analogs_groups[$access_count_group][] = $analogs_all[$k];
		    }
		}

		if(!empty($analogs_groups)) {
		    foreach($analogs_groups as $analogs_group => $analogs) {

				$binding_values = array();
				
				//$log = fopen("log.txt", "w");
				
				//fwrite($log, print_r($manufacturers, true) . "\n\n");
				
				// Список всех синонимов производителей запрошенного артикула
				$manufacturers_list = '';
				for($m=0; $m<count($manufacturers);$m++)//Цикл по массиву брэндов
				{
					$manufacturer = $manufacturers[$m]['manufacturer'];
					if(!empty($manufacturer))
					{
						array_push($binding_values, $manufacturer);
					}
				}
				if( count($binding_values) > 0 )
				{
					$manufacturers_list  = str_repeat('?,', count($binding_values) - 1) . '?';
				}
				
				
				// Запрошенный артикул
				if(empty($manufacturers_list))
				{
					$SQL_WHERE = " (`article` = ?) ";
				}
				else
				{
					$SQL_WHERE = " (`article` = ? AND `manufacturer` IN(". $manufacturers_list .")) ";
				}
				array_unshift($binding_values, $article);
		
				for($i=0; $i < count($analogs); $i++)
				{
					$binding_manufacturer_synonyms = array();

					//СИНОНИМЫ
					//$manufacturer_synonyms = '';
					if(!empty($analogs[$i]["manufacturer"]))
					{				
						//$manufacturer_synonyms = "'". mysqli_real_escape_string($db_link, $analogs[$i]["manufacturer"]) ."'";
						array_push($binding_manufacturer_synonyms, $analogs[$i]["manufacturer"]);
						$shop_docpart_manufacturer_id = false;
						$shop_docpart_manufacturer_name = false;
						$synonym_query = $db_link->prepare('SELECT `id` FROM `shop_docpart_manufacturers` WHERE `name` =:name;');
						$synonym_query->bindValue(':name', $analogs[$i]["manufacturer"]);
						$synonym_query->execute();
						$synonym_record = $synonym_query->fetch();
						
						if( $synonym_record != false )
						{
							$shop_docpart_manufacturer_id = $synonym_record["id"];
						}
						else
						{
							$synonym_query = $db_link->prepare('SELECT `manufacturer_id` FROM `shop_docpart_manufacturers_synonyms` WHERE `synonym` = :synonym;');
							$synonym_query->bindValue(':synonym', $analogs[$i]["manufacturer"]);
							$synonym_query->execute();
							$synonym_record = $synonym_query->fetch();
							if( $synonym_record != false )
							{
								$shop_docpart_manufacturer_id = $synonym_record["manufacturer_id"];
							}
						}
						
						if(!empty($shop_docpart_manufacturer_id))
						{
							$synonym_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = :manufacturer_id;');
							$synonym_query->bindValue(':manufacturer_id', $shop_docpart_manufacturer_id);
							$synonym_query->execute();
							if( $synonym_query->fetchColumn() > 0 )
							{
								$query = $db_link->prepare('SELECT `name` FROM `shop_docpart_manufacturers` WHERE `id` = :id;');
								$query->bindValue(':id', $shop_docpart_manufacturer_id);
								$query->execute();
								$record = $query->fetch();
								if($record["name"] !== $analogs[$i]["manufacturer"])
								{
									//$manufacturer_synonyms .= ", '". mysqli_real_escape_string($db_link, $record["name"]) ."'";
									array_push($binding_manufacturer_synonyms, $record["name"]);
								}
								
								$synonym_query = $db_link->prepare('SELECT `synonym` FROM `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = :manufacturer_id;');
								$synonym_query->bindValue(':manufacturer_id', $shop_docpart_manufacturer_id);
								$synonym_query->execute();
								
								while($synonym_record = $synonym_query->fetch() )
								{
									if($synonym_record["synonym"] !== $analogs[$i]["manufacturer"])
									{
										//$manufacturer_synonyms .= ", '". mysqli_real_escape_string($db_link, $synonym_record["synonym"]) ."'";
										
										array_push($binding_manufacturer_synonyms, $synonym_record["synonym"]);
									}
								}
							}
						}
					}
					
					array_push($binding_values, $analogs[$i]["article"]);
					
					//fwrite($log, print_r($binding_manufacturer_synonyms, true) . "\n\n");
					
					if( count($binding_manufacturer_synonyms) == 0 )
					{
						$SQL_WHERE .= " OR (`article` = ?) ";
					}
					else
					{
						$manufacturer_synonyms  = str_repeat('?,', count($binding_manufacturer_synonyms) - 1) . '?';
						
						$SQL_WHERE .= " OR (`article` = ? AND `manufacturer` IN (".$manufacturer_synonyms.")) ";

						$binding_values = array_merge($binding_values, $binding_manufacturer_synonyms);
					}
				}
		
				$binding_values_main = $binding_values;
		
				//Формируем SQL-запрос
				$SQL = "";
				for($p=0; $p < count($office_storage_bunches); $p++)
				{
					$office_id = $office_storage_bunches[$p]["office_id"];
					$storage_id = $office_storage_bunches[$p]["storage_id"];
					
					$binding_office_storage_args = array();
					
					//Получаем данные склада
					$storage_query = $db_link->prepare('SELECT `connection_options`, `name`, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = `shop_storages`.`currency`) AS `rate` FROM `shop_storages` WHERE `id` = :id;');
					$storage_query->bindValue(':id', $storage_id);
					$storage_query->execute();
					$storage_record = $storage_query->fetch();
					$connection_options = json_decode($storage_record["connection_options"], true);
					$price_id = (int)$connection_options["price_id"];
					$probability = (int)$connection_options["probability"];
					$color = $connection_options["color"];
					$office_caption = "";
					$storage_caption = "";
					$rate = $storage_record["rate"];
			
			
					//Получаем название склада для менеджера
					$storage_caption_for_manager_query = $db_link->prepare('SELECT
						`shop_storages`.`name` AS `storage_caption`
						FROM
						`shop_offices`
						INNER JOIN `shop_offices_storages_map` ON `shop_offices`.`id` = `shop_offices_storages_map`.`office_id`
						INNER JOIN `shop_storages` ON `shop_storages`.`id` = `shop_offices_storages_map`.`storage_id`
						WHERE
						`shop_offices`.`users` LIKE ? AND
						`shop_storages`.`id` = ? AND
						`shop_offices`.`id` = ? LIMIT 1');
					//Вот тут изменили аргумент в запросе на корректный.
					// $storage_caption_for_manager_query->execute( array('%'.$user_id.'%', $storage_id, $office_id) );
					$storage_caption_for_manager_query->execute( array('%"'.$user_id.'"%', $storage_id, $office_id) );
					$storage_caption_record = $storage_caption_for_manager_query->fetch();
					if( $storage_caption_record != false )
					{
						$storage_caption = $storage_caption_record["storage_caption"];
					}
					else
					{
						$storage_caption = "";//Запрос не от менеджера офиса
					}
			
			
					//Получаем название магазина
					$office_caption_query = $db_link->prepare('SELECT `caption` FROM `shop_offices` WHERE `id` = '.(int)$office_id);
					$office_caption_query->execute();
					$office_caption_record = $office_caption_query->fetch();
					$office_caption = $office_caption_record["caption"];
					
					//Получаем дополнительный срок доставки
					$additional_time_query = $db_link->prepare('SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = '.(int)$office_id.' AND `storage_id` = '.(int)$storage_id.' LIMIT 1;');
					$additional_time_query->execute();
					$additional_time_record = $additional_time_query->fetch();
					$additional_time = (int)($additional_time_record["additional_time"]/24);
					
					//Формируем запрос
					if( $p > 0 )
					{
						$SQL .= ' UNION ALL ';
					}
					$SQL .= ' SELECT *, 
						(SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= `shop_docpart_prices_data`.`price` AND `max_point` > `shop_docpart_prices_data`.`price`) AS `markup`,
						'.$rate.' AS `rate`,
						? AS `color`,
						? AS `probability`,
						? AS `office_id`,
						? AS `storage_id`,
						? AS `storage_caption`,
						? AS `office_caption`,
						? AS `additional_time`
					FROM 
						`shop_docpart_prices_data` WHERE ('.$SQL_WHERE.') AND `price_id` = ?';
						
					array_push($binding_office_storage_args, $office_id);
					array_push($binding_office_storage_args, $storage_id);
					array_push($binding_office_storage_args, $group_id);
					array_push($binding_office_storage_args, $color);
					array_push($binding_office_storage_args, $probability);
					array_push($binding_office_storage_args, $office_id);
					array_push($binding_office_storage_args, $storage_id);
					array_push($binding_office_storage_args, $storage_caption);
					array_push($binding_office_storage_args, $office_caption);
					array_push($binding_office_storage_args, $additional_time);
			
			
					if( $p == 0 )
					{
						$binding_values = array_merge($binding_office_storage_args, $binding_values_main);
					}
					else
					{
						$binding_values = array_merge($binding_values, $binding_office_storage_args);
						$binding_values = array_merge($binding_values, $binding_values_main);
					}
					
					array_push($binding_values, $price_id);
				}
			
				//fwrite($log, $SQL . "\n\n");
				
				//fwrite($log, print_r($binding_values, true). "\n\n");

		
		
				//Делаем запрос по артикулу
				$products_query = $db_link->prepare($SQL);
				$products_query->execute($binding_values);
				//fwrite($log, print_r($products_query->fetch(), true). "\n\n");
				while( $product = $products_query->fetch() )
				{
					//Создаем объек товара и добавляем его в список:
					$DocpartProduct = new DocpartProduct($product["manufacturer"],//OK
						$product["article_show"],//OK
						$product["name"],//OK
						$product["exist"],//OK
						$product["price"] + $product["price"]*$product["markup"],//OK
						$product["time_to_exe"] + $product["additional_time"],
						$product["time_to_exe"] + $product["additional_time"],
						$product["storage"],//OK
						$product["min_order"],//OK
						$product["probability"],//OK
						$product["office_id"],//OK
						$product["storage_id"],//OK
						$product["office_caption"],
						$product["color"],//OK
						$product["storage_caption"],
						// $storage_caption,
						$product["price"],//OK
						$product["markup"],//OK
						2,0,0,'',null,array("rate"=>$product["rate"])//OK
						);
					
					if($DocpartProduct->valid == true)
					{
						array_push($this->Products, $DocpartProduct);
					}
				}
				//fclose($log);
			}
		} else {
			$binding_values = array();
				
			//$log = fopen("log.txt", "w");
			
			//fwrite($log, print_r($manufacturers, true) . "\n\n");
			
			// Список всех синонимов производителей запрошенного артикула
			$manufacturers_list = '';
			for($m=0; $m<count($manufacturers);$m++)//Цикл по массиву брэндов
			{
				$manufacturer = $manufacturers[$m]['manufacturer'];
				if(!empty($manufacturer))
				{
					array_push($binding_values, $manufacturer);
				}
			}
			if( count($binding_values) > 0 )
			{
				$manufacturers_list  = str_repeat('?,', count($binding_values) - 1) . '?';
			}
			
			
			// Запрошенный артикул
			if(empty($manufacturers_list))
			{
				$SQL_WHERE = " (`article` = ?) ";
			}
			else
			{
				$SQL_WHERE = " (`article` = ? AND `manufacturer` IN(". $manufacturers_list .")) ";
			}
			array_unshift($binding_values, $article);

			$binding_values_main = $binding_values;
		
			//Формируем SQL-запрос
			$SQL = "";
			for($p=0; $p < count($office_storage_bunches); $p++)
			{
				$office_id = $office_storage_bunches[$p]["office_id"];
				$storage_id = $office_storage_bunches[$p]["storage_id"];
				
				$binding_office_storage_args = array();
				
				//Получаем данные склада
				$storage_query = $db_link->prepare('SELECT `connection_options`, `name`, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = `shop_storages`.`currency`) AS `rate` FROM `shop_storages` WHERE `id` = :id;');
				$storage_query->bindValue(':id', $storage_id);
				$storage_query->execute();
				$storage_record = $storage_query->fetch();
				$connection_options = json_decode($storage_record["connection_options"], true);
				$price_id = (int)$connection_options["price_id"];
				$probability = (int)$connection_options["probability"];
				$color = $connection_options["color"];
				$office_caption = "";
				$storage_caption = "";
				$rate = $storage_record["rate"];
		
		
				//Получаем название склада для менеджера
				$storage_caption_for_manager_query = $db_link->prepare('SELECT
					`shop_storages`.`name` AS `storage_caption`
					FROM
					`shop_offices`
					INNER JOIN `shop_offices_storages_map` ON `shop_offices`.`id` = `shop_offices_storages_map`.`office_id`
					INNER JOIN `shop_storages` ON `shop_storages`.`id` = `shop_offices_storages_map`.`storage_id`
					WHERE
					`shop_offices`.`users` LIKE ? AND
					`shop_storages`.`id` = ? AND
					`shop_offices`.`id` = ? LIMIT 1');
				//Вот тут изменили аргумент в запросе на корректный.
				// $storage_caption_for_manager_query->execute( array('%'.$user_id.'%', $storage_id, $office_id) );
				$storage_caption_for_manager_query->execute( array('%"'.$user_id.'"%', $storage_id, $office_id) );
				$storage_caption_record = $storage_caption_for_manager_query->fetch();
				if( $storage_caption_record != false )
				{
					$storage_caption = $storage_caption_record["storage_caption"];
				}
				else
				{
					$storage_caption = "";//Запрос не от менеджера офиса
				}
		
		
				//Получаем название магазина
				$office_caption_query = $db_link->prepare('SELECT `caption` FROM `shop_offices` WHERE `id` = '.(int)$office_id);
				$office_caption_query->execute();
				$office_caption_record = $office_caption_query->fetch();
				$office_caption = $office_caption_record["caption"];
				
				//Получаем дополнительный срок доставки
				$additional_time_query = $db_link->prepare('SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = '.(int)$office_id.' AND `storage_id` = '.(int)$storage_id.' LIMIT 1;');
				$additional_time_query->execute();
				$additional_time_record = $additional_time_query->fetch();
				$additional_time = (int)($additional_time_record["additional_time"]/24);
				
				//Формируем запрос
				if( $p > 0 )
				{
					$SQL .= ' UNION ALL ';
				}
				$SQL .= ' SELECT *, 
					(SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= `shop_docpart_prices_data`.`price` AND `max_point` > `shop_docpart_prices_data`.`price`) AS `markup`,
					'.$rate.' AS `rate`,
					? AS `color`,
					? AS `probability`,
					? AS `office_id`,
					? AS `storage_id`,
					? AS `storage_caption`,
					? AS `office_caption`,
					? AS `additional_time`
				FROM 
					`shop_docpart_prices_data` WHERE ('.$SQL_WHERE.') AND `price_id` = ?';
					
				array_push($binding_office_storage_args, $office_id);
				array_push($binding_office_storage_args, $storage_id);
				array_push($binding_office_storage_args, $group_id);
				array_push($binding_office_storage_args, $color);
				array_push($binding_office_storage_args, $probability);
				array_push($binding_office_storage_args, $office_id);
				array_push($binding_office_storage_args, $storage_id);
				array_push($binding_office_storage_args, $storage_caption);
				array_push($binding_office_storage_args, $office_caption);
				array_push($binding_office_storage_args, $additional_time);
		
		
				if( $p == 0 )
				{
					$binding_values = array_merge($binding_office_storage_args, $binding_values_main);
				}
				else
				{
					$binding_values = array_merge($binding_values, $binding_office_storage_args);
					$binding_values = array_merge($binding_values, $binding_values_main);
				}
				
				array_push($binding_values, $price_id);
			}
		
			//fwrite($log, $SQL . "\n\n");
			
			//fwrite($log, print_r($binding_values, true). "\n\n");

	
	
			//Делаем запрос по артикулу
			$products_query = $db_link->prepare($SQL);
			$products_query->execute($binding_values);
			//fwrite($log, print_r($products_query->fetch(), true). "\n\n");
			while( $product = $products_query->fetch() )
			{
				//Создаем объек товара и добавляем его в список:
				$DocpartProduct = new DocpartProduct($product["manufacturer"],//OK
					$product["article_show"],//OK
					$product["name"],//OK
					$product["exist"],//OK
					$product["price"] + $product["price"]*$product["markup"],//OK
					$product["time_to_exe"] + $product["additional_time"],
					$product["time_to_exe"] + $product["additional_time"],
					$product["storage"],//OK
					$product["min_order"],//OK
					$product["probability"],//OK
					$product["office_id"],//OK
					$product["storage_id"],//OK
					$product["office_caption"],
					$product["color"],//OK
					$product["storage_caption"],
					// $storage_caption,
					$product["price"],//OK
					$product["markup"],//OK
					2,0,0,'',null,array("rate"=>$product["rate"])//OK
					);
				
				if($DocpartProduct->valid == true)
				{
					array_push($this->Products, $DocpartProduct);
				}
			}
			//fclose($log);


		}
		
		
/* ..................................................................................... */
/* ............................... ПОИСК ПО НАИМЕНОВАНИЮ ............................... */
/* ..................................................................................... */
		
	if(!empty($searsch_str))
	{
		$binding_values = array();
		// Формируем строку условия фильтрации для запроса
		$SQL_searsch_str = '';
		$searsch_str = trim(strip_tags($searsch_str));
		$searsch_str = explode(' ',$searsch_str);
		if(!empty($searsch_str))
		{
			foreach($searsch_str as $item_str)
			{
				$item_str = trim($item_str);
				if(mb_strlen($item_str, 'utf-8') < 3)
				{
					continue;// Короткие слова пропускаем
				}
				// Поиск по названию
				if($SQL_searsch_str != '')
				{
					$SQL_searsch_str .= " AND ";
				}
				$SQL_searsch_str .= "(`name` LIKE ?)";
				array_push($binding_values, '%'.$item_str.'%');
			}
		}
		
		
		//Формируем SQL-запрос
		$SQL = "";
		for($i=0; $i < count($office_storage_bunches); $i++)
		{
			$binding_office_storage_args = array();
			
			$office_id = $office_storage_bunches[$i]["office_id"];
			$storage_id = $office_storage_bunches[$i]["storage_id"];
			
			
			//Получаем данные склада
			$storage_query = $db_link->prepare('SELECT `connection_options`, `name`, (SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = `shop_storages`.`currency`) AS `rate` FROM `shop_storages` WHERE `id` = :id;');
			$storage_query->bindValue(':id', $storage_id);
			$storage_query->execute();
			$storage_record = $storage_query->fetch();
			$connection_options = json_decode($storage_record["connection_options"], true);
			$price_id = (int)$connection_options["price_id"];
			$probability = (int)$connection_options["probability"];
			$color = $connection_options["color"];
			$office_caption = "";
			$storage_caption = "";
			$rate = $storage_record["rate"];
			
			
			//Получаем название склада для менеджера
			$storage_caption_for_manager_query = $db_link->prepare('SELECT
				`shop_storages`.`name` AS `storage_caption`
				FROM
				`shop_offices`
				INNER JOIN `shop_offices_storages_map` ON `shop_offices`.`id` = `shop_offices_storages_map`.`office_id`
				INNER JOIN `shop_storages` ON `shop_storages`.`id` = `shop_offices_storages_map`.`storage_id`
				WHERE
				`shop_offices`.`users` LIKE ? AND
				`shop_storages`.`id` = ? AND
				`shop_offices`.`id` = ? LIMIT 1;');
			$storage_caption_for_manager_query->execute( array('%'.$user_id.'%', $storage_id, $office_id) );
			$storage_caption_record = $storage_caption_for_manager_query->fetch();
			if( $storage_caption_record != false )
			{
				$storage_caption = $storage_caption_record["storage_caption"];
			}
			else
			{
				$storage_caption = "";//Запрос не от менеджера офиса
			}
			
			//Получаем название магазина
			$office_caption_query = $db_link->prepare('SELECT `caption` FROM `shop_offices` WHERE `id` = '.(int)$office_id);
			$office_caption_query->execute();
			$office_caption_record = $office_caption_query->fetch();
			$office_caption = $office_caption_record["caption"];
			
			
			//Получаем дополнительный срок доставки
			$additional_time_query = $db_link->prepare('SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = '.(int)$office_id.' AND `storage_id` = '.(int)$storage_id.' LIMIT 1;');
			$additional_time_query->execute();
			$additional_time_record = $additional_time_query->fetch();
			$additional_time = (int)($additional_time_record["additional_time"]/24);
			
			
			//Формируем запрос
			if( $i > 0 )
			{
				$SQL .= ' UNION ALL ';
			}
			$SQL .= ' SELECT *, 
				(SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= `shop_docpart_prices_data`.`price` AND `max_point` > `shop_docpart_prices_data`.`price`) AS `markup`,
				'.$rate.' AS `rate`,
				? AS `color`,
				? AS `probability`,
				? AS `office_id`,
				? AS `storage_id`,
				? AS `storage_caption`,
				? AS `office_caption`,
				? AS `additional_time`
			FROM 
				`shop_docpart_prices_data` WHERE ('.$SQL_searsch_str.') AND `price_id` = ? ';
				
			array_push($binding_office_storage_args, $office_id);
			array_push($binding_office_storage_args, $storage_id);
			array_push($binding_office_storage_args, $group_id);
			array_push($binding_office_storage_args, $color);
			array_push($binding_office_storage_args, $probability);
			array_push($binding_office_storage_args, $office_id);
			array_push($binding_office_storage_args, $storage_id);
			array_push($binding_office_storage_args, $storage_caption);
			array_push($binding_office_storage_args, $office_caption);
			array_push($binding_office_storage_args, $additional_time);
			
			$binding_values = array_merge($binding_office_storage_args, $binding_values);
			array_push($binding_values, $price_id);
		}
		
		
		$products_query = $db_link->prepare($SQL);
		$products_query->execute($binding_values);
		while($product = $products_query->fetch())
		{
			// Если артикул равен запрошенному артикулу то пропускаем так как товар будет отображен в другом блоке
			if($product["article"] === $article){
				continue;
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($product["manufacturer"],
				$product["article_show"],
				$product["name"],
				$product["exist"],
				$product["price"] + $product["price"]*$product["markup"],
				$product["time_to_exe"] + $product["additional_time"],
				$product["time_to_exe"] + $product["additional_time"],
				$product["storage"],
				$product["min_order"],
				$product["probability"],
				$product["office_id"],
				$product["storage_id"],
				$product["office_caption"],
				$product["color"],
				$product["storage_caption"],
				$product["price"],
				$product["markup"],
				2,0,0,'',null,array('rate' => $product["rate"], 'search_name' => 1)
				);
			
			if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}
		}
	}
	
	
/* ..................................................................................... */
/* ........................................ END ........................................ */
/* ..................................................................................... */


		$this->result = 1;
	}//~function __construct($article)
};//~class prices_enclosure

//$f = fopen('log2.txt', 'w');
//fwrite($f, $_POST["manufacturers"] . "\n\n");

$ob = new prices_enclosure($_POST["article"], json_decode($_POST["manufacturers"], true), json_decode($_POST["storage_options"], true), $_POST["searsch_str"]);
exit(json_encode($ob));
?>