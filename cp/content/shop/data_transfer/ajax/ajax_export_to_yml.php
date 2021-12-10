<?php
header('Content-Type: application/json;charset=utf-8;');

set_time_limit(0);
ini_set('display_errors', 0);
//ini_set('memory_limit', '800M');

/*
$f = fopen('log.txt', 'w');
fwrite($f, json_encode($_GET));
exit;
*/

/*
$_GET = json_decode('', true);
*/

//phpinfo();
//exit('d');





//Скрипт экспорта товаров на Яндекс.Маркет
// -----------------------------------------------------------------------------------------------------
// 0. НАСТРОЙКИ ВЫПОЛНЕНИЯ СКРИПТА
$export_options = array();
$export_options["offices"] = array(1);//Список магазинов, от которых выводить предложения
$export_options["data_output_mode"] = "create_file";//Способ вывода строки (оставить файл на сервере/скачать файл)
$export_options["group_id"] = 2;
if( !empty($_GET["export_options"]) )
{
	$export_options = json_decode($_GET["export_options"], true);
}
$category = json_encode($export_options['arr_category']);
$category_list = str_replace(array('[',']','{','}'),'',$category);
// -----------------------------------------------------------------------------------------------------
// 1. КОНСТАНТЫ
$file_name = "yml_dump.xml";//Имя формируемого xml файла
$property_types_tables = array("1"=>"int", "2"=>"float", "3"=>"text", "4"=>"bool", "5"=>"list");//Постфиксы таблиц значений свойств - зависят от типа свойства
$products_images_dir = "content/files/images/products_images/";//Директория к изображениям товаров
// -----------------------------------------------------------------------------------------------------
// 2. БИБЛИОТЕКА ДЛЯ XML
require_once('Array2XML.php');
// -----------------------------------------------------------------------------------------------------
// 3. ПОДКЛЮЧЕНИЕ К БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");
// -----------------------------------------------------------------------------------------------------
//Формируем массивы со свойствами товаров, используется при формировании свойств товаров
$shop_categories_properties_map = array();
$sql = "SELECT * FROM `shop_categories_properties_map` ORDER BY `order`";
$query = $db_link->prepare($sql);
$query->execute();
while($record = $query->fetch())
{
	$shop_categories_properties_map[$record['category_id']][] = $record;
}
// -----------------------------------------------------------------------------------------------------
//Для каждого магазина получить список складов
$offices_all = array();
for($o=0; $o < count($export_options["offices"]); $o++)
{
	$office_id = $export_options["offices"][$o];//ID точки выдачи
	
	$storages_query = $db_link->prepare("SELECT DISTINCT(`storage_id`) AS `storage_id`, `additional_time`, (SELECT `iso_name` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`)) AS `currency_id` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` IN(SELECT `id` FROM `shop_storages` WHERE `interface_type` = 1);");
	$storages_query->execute( array($office_id) );
	while($storage = $storages_query->fetch() )
	{
		$offices_all[$office_id][$storage['storage_id']] = array(
											"storage_id" => $storage['storage_id'],
											"additional_time" => $storage['additional_time'],
											"currency_id" => $storage['currency_id']
		);
	}
}


//Информация по первому магазину
$customer_office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
$customer_office_query->execute(array($export_options["offices"][0]));
$customer_office_info = $customer_office_query->fetch(PDO::FETCH_ASSOC);


//4. ФОРМИРОВАНИЕ ОБЪЕКТА SHOP
$shop = array(
	"#date"=>date("Y-m-d H:i", time() ),
	"shop"=>array(
		"name"=>$DP_Config->site_name,
		"company"=>$DP_Config->site_name,
		"url"=>$DP_Config->domain_path,
		"platform"=>"Docpart",
		"version"=>"1",
		"agency"=>$customer_office_info['email']
	)
);


//4.1 Заполняем категории товаров
$category_no_published = array();//Массив для добавления ID категорий которые не опубликованы вместе с их вложенными подкатегориями что бы отфильтровать их
$category_list_arr = array();//Массив для добавления ID категорий что бы ниже по ним отфильтровать товары, так как изначально список ID может содержать не опубликованные категории
$categories_names = array();//Массив для добавления названий категорий в товары
$shop["shop"]["categories"] = array();

$categories_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories` WHERE `id` IN($category_list) ORDER BY `order`, `level`");
$categories_query->execute();
while( $category_record = $categories_query->fetch() )
{
	if($category_record["published_flag"] != 1 || in_array($category_record["parent"], $category_no_published) !== false){
		$category_no_published[] = $category_record["id"];
		continue;
	}
	
	$categories_names[(int)$category_record["id"]] = $category_record["value"];
	
	$category = array(
		"category"=>array(
			"#id"=>$category_record["id"]
		)
	);
	if($category_record["parent"] > 0)
	{
		$category["category"]["#parentId"] = $category_record["parent"];
	}
	
	//Заполняем название категории - как индексный элемент массива
	$category["category"][] = array(trim($category_record["value"]));
	
	array_push($shop["shop"]["categories"], $category);
	array_push($category_list_arr, $category_record["id"]);//Добавляем в массив ID по которым будет фильтровать товары
}


//4.2 Заполняем предложения магазина
$shop["shop"]["offers"] = array();

$converter = new Array2XML();
$converter->rootName = "yml_catalog";
$export_str = $converter->convert($shop);
$export_str = str_replace('<offers/></shop></yml_catalog>','<offers>',$export_str);


//4.3 Начинаем формировать файл
$export_file = fopen($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/".$file_name, "w");
fwrite($export_file, trim($export_str));


//ПОЛУЧАЕМ ДАННЫЕ СКЛАДОВ
//Для каждого магазина опросить каждый склад
$group_id = $export_options["group_id"];//Группа для наценки
if(!empty($offices_all)){
	foreach($offices_all as $office_id => $storages_all){
		foreach($storages_all as $storage_id => $storage){
			$additional_time = $storage["additional_time"];
			
			$SQL = "
			SELECT
			`shop_catalogue_products`.`id` AS `product_id`,
			`shop_storages_data`.`category_id` AS `category_id`,
			`shop_catalogue_products`.`caption` AS `caption`,
			`shop_catalogue_products`.`alias` AS `alias`,
			
			(SELECT `shop_catalogue_categories`.`url`FROM `shop_catalogue_categories` WHERE `shop_catalogue_categories`.`id` = `shop_storages_data`.`category_id`) AS `category_url`,
			(SELECT `file_name` FROM `shop_products_images` WHERE `product_id` = `shop_storages_data`.`product_id` LIMIT 1) AS file_name,
			(SELECT `content` FROM `shop_products_text` WHERE `product_id` = `shop_storages_data`.`product_id` LIMIT 1) AS content,
			
			`shop_storages_data`.`id` AS `shop_storages_data_id`,

			(`shop_storages_data`.`price` + `shop_storages_data`.`price` * (SELECT (`shop_offices_storages_map`.`markup` / 100) AS markup FROM `shop_offices_storages_map` WHERE `shop_offices_storages_map`.`office_id` = $office_id AND `shop_offices_storages_map`.`storage_id` = $storage_id AND `shop_offices_storages_map`.`group_id` = $group_id AND `shop_offices_storages_map`.`min_point` <= `shop_storages_data`.`price` AND `shop_offices_storages_map`.`max_point` > `shop_storages_data`.`price`)) AS customer_price,

			`shop_catalogue_products`.`published_flag` AS `published_flag`

			FROM `shop_storages_data`

			LEFT OUTER JOIN `shop_catalogue_products` ON `shop_storages_data`.`product_id` = `shop_catalogue_products`.`id`

			WHERE `shop_storages_data`.`category_id` IN(".implode(',',$category_list_arr).") AND `shop_storages_data`.`storage_id` = $storage_id AND `shop_storages_data`.`price` > 0 AND `shop_storages_data`.`exist` > 0 AND `shop_catalogue_products`.`id` > 0
			";

			$products_query = $db_link->prepare($SQL);
			$products_query->execute();
			while( $product_record = $products_query->fetch() )
			{
				if($product_record["published_flag"] != 1){
					continue;
				}
				
				//Получаем изображения товара
				$picture = $product_record["file_name"];
				
				//Получаем текстовое описание товара
				$description = $product_record["content"];
				
				//Получаем значения свойств товара
				$vendor = "";//Поле Производитель - обязательный элемент для яндекса
				$model = "";//Модель - обязательный элемент для яндекса
				
				$params = array();
				
				//Свойства
				$category_properties = $shop_categories_properties_map[$product_record["category_id"]];
				if(!empty($category_properties)){
					foreach($category_properties as $property_record)
					{
						$param = array(
							"param"=>array(
								"#name"=>$property_record["value"]
							)
						);

						//Получаем значение данного свойства для товара:
						$table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
						
						$property_value_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS `id`, `value` FROM `shop_properties_values_$table_postfix` WHERE `product_id` = ? AND `property_id` = ?;");
						$property_value_query->execute( array($product_record["product_id"], $property_record["id"]) );
						
						$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
						$elements_count_rows_query->execute();
						$elements_count_rows = $elements_count_rows_query->fetchColumn();
						
						if( $elements_count_rows > 0)
						{
							//Задаем значение
							switch($property_record["property_type_id"])
							{
								case 1:
								case 2:
								case 3:
								case 4:
									$property_value_record = $property_value_query->fetch();
									$param["param"][] = array($property_value_record["value"]);
									
									//Обязательные поля для яндекса
									if($property_record["value"] == "Производитель")
									{
										$vendor = $property_value_record["value"];
									}
									if($property_record["value"] == "Модель")
									{
										$model = $property_value_record["value"];
									}
									break;
								case 5:
									$property["property"]["values"] = array();
								
									//Свойство списковое - значений может быть несколько. НО!!!!! В описании яндекса в поле param может быть только одно значение, поэтому, если у нас список с множественным выбором - пропускаем его.
									$list_property_items = array();
									while($property_value_record = $property_value_query->fetch())
									{
										$line_list_items_query = $db_link->prepare("SELECT `value` FROM `shop_line_lists_items` WHERE `id` = ? LIMIT 1;");
										$line_list_items_query->execute( array($property_value_record["value"]) );
										$list_item = $line_list_items_query->fetch();
										
										$param["param"][] = array($list_item["value"]);
										
										//Обязательные поля для яндекса
										if($property_record["value"] == "Производитель")
										{
											$vendor = $list_item["value"];
										}
										if($property_record["value"] == "Модель")
										{
											$model = $list_item["value"];
										}
									}
									break;
							}
						}//~if() - есть значение свойства
						
						//Добавляем свойство, еслли есть его значение
						if(count($param["param"]) == 2)
						{
							array_push($params, $param);
						}
					}
				}
				
				//Округление цены
				$work_price = $product_record["customer_price"];
				if($DP_Config->price_rounding == '1')//Без копеечной части
				{
					if($work_price > (int)$work_price)
					{
						$work_price = (int)$work_price+1;
					}
					else
					{
						$work_price = (int)$work_price;
					}
				}
				else if($DP_Config->price_rounding == '2')//До 5 руб
				{
					$work_price = (integer)$work_price;
					$price_str = (string)$work_price;
					$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
					if($price_str_last_char > 0 && $price_str_last_char < 5)
					{
						$work_price = $work_price + (5 - $price_str_last_char);
					}
					else if($price_str_last_char > 5 && $price_str_last_char <= 9)
					{
						$work_price = $work_price + (10 - $price_str_last_char);
					}
				}
				else if($DP_Config->price_rounding == '3')//До 10 руб
				{
					$work_price = (integer)$work_price;
					$price_str = (string)$work_price;
					$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
					if($price_str_last_char != 0)
					{
						$work_price = $work_price + (10 - $price_str_last_char);
					}
				}
				$work_price = (float)number_format($work_price,2,'.','');
				$product_record["customer_price"] = $work_price;
				
				if(empty($product_record["customer_price"])){
					continue;
				}
				
				//ЗДЕСЬ ФОРМИРУЕТСЯ ЭЛЕМЕНТ OFFER
				$offer = array(
					"offer"=>array(
						"#id"=>$product_record["shop_storages_data_id"],
						"#type"=>"vendor.model",
						"#available"=>"true",
						
						"url"=>$DP_Config->domain_path.$product_record["category_url"]."/".$product_record["alias"],
						"price"=>$product_record["customer_price"],
						"currencyId"=>$storage["currency_id"],
						"categoryId"=>$product_record["category_id"],
						"typePrefix"=>$categories_names[(int)$product_record["category_id"]],
						"model"=>$product_record["caption"]
					)
				);
				
				//Добавляем поле производитель
				if($vendor != "" && $vendor != NULL)
				{
					$offer["offer"]["vendor"] = $vendor;
				}
				
				//Добавляем изображение
				if(!empty($picture))
				{
					if(strpos($picture,'http') === 0){
						$offer["offer"]["picture"] = $picture;
					}else{
						$offer["offer"]["picture"] = $DP_Config->domain_path.$products_images_dir.$picture;
					}
				}
				
				//Добавляем текстовое описание
				if($description != "")
				{
					$offer["offer"]["description"] = $description;
				}
				
				//Параметры добавляем только если они есть
				if(count($params) > 0)
				{
					for($param = 0; $param < count($params); $param++)
					{
						array_push($offer["offer"], $params[$param]);
					}
				}
				
				//Преобразовываем массив с товаром в xml
				$converter = new Array2XML();
				$converter->rootName = "root";
				$export_str = $converter->convert($offer);
				$export_str = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$export_str);
				$export_str = str_replace('<root>','',$export_str);
				$export_str = str_replace('</root>','',$export_str);
				$file_extension = "xml";
				
				//Записываем в файл объект товара
				fwrite($export_file, $export_str);
				
			}//while( $product_record = $products_query->fetch() )
		}
	}
}

//Завершаем формирование файла
$export_str = '</offers></shop></yml_catalog>';
fwrite($export_file, $export_str);
fclose($export_file);

// -----------------------------------------------------------------------------------------------------

//Результат
$answer = array();
$answer["status"] = true;
$answer["filename"] = $file_name;
exit(json_encode($answer));
?>