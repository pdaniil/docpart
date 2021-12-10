<?php
/**
Серверный скрипт для обработки загруженного файла
*/
header('Content-Type: application/json;charset=utf-8;');
/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST["import_options"]);
exit;
*/

function prepareString($string)
{
	$sweep=array("/", "#", "\r\n", "\r", "\n", "\t", "'", '"', "\\");
	$string = str_replace($sweep,"", $string);
	$string = trim($string);
	
	//Удаляем BOM из строки
	if( substr($string, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf) ) 
	{
        $string = substr($string, 3);
    }
	
	return $string;
}


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

$warnings = array();//Сообщения для справки

//ИСХОДНЫЕ ДАННЫЕ
$import_options = json_decode( $_POST["import_options"], true );//Параметры импорта
$category_id = $import_options["category_id"];//ID категории
$storage_id = $import_options["storage_id"];//ID склада


$time = time();


//ПОЛУЧАЕМ ОПИСАНИЕ СВОЙСТВ КАТЕГОРИИ
$properties_map = array();
$properties_map_query = $db_link->prepare("SELECT *, (SELECT `type` FROM `shop_line_lists` WHERE `id` = `shop_categories_properties_map`.`list_id`) AS 'list_type' FROM `shop_categories_properties_map` WHERE `category_id` = ?;");
$properties_map_query->execute( array($category_id) );
while( $property_record = $properties_map_query->fetch() )
{
	$property = array();
	
	$property["id"] = $property_record["id"];
	$property["type_id"] = $property_record["property_type_id"];
	$property["list_id"] = $property_record["list_id"];
	$property["list_type"] = $property_record["list_type"];
	$property["value"] = $property_record["value"];
	
	array_push($properties_map, $property);
}


//Счетчики для сообщений:
$count_records = 0;//Количество строк в файле
$count_created = 0;//Количество созданных товаров (новых)
$count_updates_storage_info = 0;//Количество существующих товаров (т.е. только обновление складской информации)
$count_continued = 0;//Количество пропущенных по причине неуказанного наименования


//...Процесс импорта
//ПОЛУЧАЕМ ФАЙЛ
$file_handle = @fopen($import_options["file_full_path"], "r");
if ($file_handle) 
{
	//Удаляем старые записи склада для выбранной категории
	if( $import_options["delete_storage_data"] == 1 )
	{
		$db_link->prepare("DELETE FROM `shop_storages_data` WHERE `storage_id` = ? AND `category_id` = ?;")->execute( array($storage_id, $category_id) );
	}
	
	//Удаляем старые товары для выбранной категории
	if( $import_options["delete_products_data"] == 1 )
	{
		$db_link->prepare("DELETE FROM `shop_storages_data` WHERE `storage_id` = ? AND `category_id` = ?;")->execute( array($storage_id, $category_id) );
		
		//Удаляем данные о товаре
		$db_link->prepare("DELETE FROM `shop_products_images` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_products_text` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_int` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_float` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_text` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_bool` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_list` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_products_stickers` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_properties_values_tree_list` WHERE `product_id` IN(SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ?);")->execute( array($category_id) );
		$db_link->prepare("DELETE FROM `shop_catalogue_products` WHERE `category_id` = ?;")->execute( array($category_id) );
	}
	
	// Зачитываем файл построчно
	$iii = 0;
    while (($string_data = fgetcsv($file_handle,0,";",'"')) !== false) 
	{
		$iii++;
		if($iii <= (int)$import_options["strings_to_left"]){
			continue;// Пропускаем нужное количество строк
		}
		
		//ID Товара
		$product_id = NULL;
		
		//Работаем с записью
		if($import_options["encoding"] === 'windows-1251'){
			foreach($string_data as $key => $item_str){
				$item_str = iconv('windows-1251', 'UTF-8', $item_str);
				$string_data[$key] = $item_str;
			}
		}
		
		//Основные поля
		$caption = prepareString(trim($string_data[$import_options["col_name"]-1]));//Наименование товара
		$price = str_replace(array(" "),'',$string_data[$import_options["col_price"]-1]);//Цена
		$price = str_replace(array(","),'.',$price);//Цена
		$exist = trim($string_data[$import_options["col_exist"]-1]);//Наличие
		
		//Текст
		$text = "";
		if( $import_options["col_text"] !=0 )
		{
			$text = trim($string_data[$import_options["col_text"]-1]);
		}
		
		//Изображение
		$img = "";
		if( $import_options["col_img"] !=0 )
		{
			$img = trim($string_data[$import_options["col_img"]-1]);
		}
		
		//Формируем URL-страницы товара
		$alias_tmp = "";
		if($import_options["col_name_url_check"] == true){
			$alias_tmp .= prepareString(trim($string_data[$import_options["col_name"]-1]));
		}
		for($p=0; $p < count($properties_map); $p++)
		{
			if( $import_options["col_".$properties_map[$p]["id"]."_url_check"] == true )
			{
				$tmp = prepareString(trim($string_data[$import_options["col_".$properties_map[$p]["id"]]-1]));
				if($tmp != ''){
					if($alias_tmp != ''){$alias_tmp .= '-';}
					$alias_tmp .= $tmp;
				}
			}
		}
		if($alias_tmp === ''){
			$tmp = prepareString(trim($string_data[$import_options["col_name"]-1]));
			if($tmp != ''){
				if($alias_tmp != ''){$alias_tmp .= '-';}
				$alias_tmp .= $tmp;
			}
		}
		
		//Alias товара
		$alias = translit($alias_tmp);
		if($alias === ''){
			$count_continued++;//Количество пропущенных по причине неуказанного наименования
			continue;
		}
		
		//Генерируем поля на основе наименования:
		$title_tag = $caption;
		$description_tag = $caption;
		$keywords_tag = $caption;
		
		//1. Определить, есть ли такой товар
		$check_product_query = $db_link->prepare("SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` = ? AND `alias` = ?;");
		$check_product_query->execute( array($category_id, $alias) );
		$product_record = $check_product_query->fetch();
		if( $product_record != false )//Товар существует - только добавляем данные по складу
		{
			$product_id = $product_record["id"];
			
			//1. Удаляем учетную запись продукта и его свойства что бы обновить данные о товаре
			$db_link->prepare("DELETE FROM `shop_catalogue_products` WHERE `id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_products_images` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_products_text` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_int` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_float` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_text` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_bool` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_list` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_properties_values_tree_list` WHERE `product_id` = ?;")->execute( array($product_id) );
			$db_link->prepare("DELETE FROM `shop_storages_data` WHERE `storage_id` = ? AND `product_id` = ?;")->execute( array($storage_id, $product_id) );
			
			$count_updates_storage_info++;//Счетчик обновляемого товара
		}else{
			$count_created++;//Счетчик созданного товара
		}
		
		//2.1. Добавляем запись в shop_catalogue_products и получаем ID товара
		$db_link->prepare("INSERT INTO `shop_catalogue_products` (`id`, `category_id`, `caption`, `alias`, `title_tag`, `description_tag`, `keywords_tag`, `published_flag`) VALUES (?,?,?,?,?,?,?,?);")->execute( array($product_id, $category_id, $caption, $alias, $title_tag, $description_tag, $keywords_tag, 1) );
		
		$product_id = $db_link->lastInsertId();
		
		//2.2. Добавляем запись в таблицу shop_products_text
		$db_link->prepare("INSERT INTO `shop_products_text` (`product_id`, `content`) VALUES (?,?);")->execute( array($product_id, $text) );
		
		//2.3. Добавить запись в таблицу shop_products_images
		if($img != "")
		{
			$img_arr = explode(',', $img);
			foreach($img_arr as $img){
				$img = trim($img);
				$db_link->prepare("INSERT INTO `shop_products_images` (`product_id`, `file_name`) VALUES (?,?);")->execute( array($product_id, $img) );
			}
		}
		
		//2.4. Добавление свойств
		for($p=0; $p < count($properties_map); $p++)
		{
			//Если задана колонка со свойством
			if( $import_options["col_".$properties_map[$p]["id"]] != 0 )
			{
				$value = trim($string_data[$import_options["col_".$properties_map[$p]["id"]]-1]);
				
				$property_id = $properties_map[$p]["id"];
				$property_value = trim($properties_map[$p]["value"]);
				$list_id = $properties_map[$p]["list_id"];
				$list_type = (int) $properties_map[$p]["list_type"];
				
				switch( $properties_map[$p]["type_id"] )
				{
					case 1:
						$table_postfix = "int";
						$value = (int)$value;
						break;
					case 2:
						$table_postfix = "float";
						$value = (float)$value;
						break;
					case 3:
						$table_postfix = "text";
						$value = prepareString($value);
						if($property_value == 'Артикул'){
							$sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");
							$value = str_replace($sweep,"", $value);
							$value = strtoupper($value);
						}
						break;
					case 4:
						$table_postfix = "bool";
						if( $value === "Да" || $value === "Есть" || $value === "On" || $value === "+" || $value === "1" || $value === "Y" || $value === 1 )
						{
							$value = 1;
						}
						else
						{
							$value = 0;
						}
						break;
					case 5:
						$table_postfix = "list";
						
						$value = prepareString($value);
						
						if($list_type == 2){
							// Множественный
							$values = explode(",", $value);
						}else{
							// Еденичный
							$values = array($value);
						}
						
						//Определяем, есть ли такое значение в списке
						
						if($value !== ''){
							foreach($values as $value){
								$value = trim($value);
								if($value !== ''){
									$list_value_query = $db_link->prepare("SELECT `id` FROM `shop_line_lists_items` WHERE `value` = ? AND `line_list_id` = ?;");
									$list_value_query->execute( array($value, $list_id) );
									$value_record = $list_value_query->fetch();
									if( $value_record != false )
									{
										$value = $value_record["id"];
									}
									else
									{
										$db_link->prepare("INSERT INTO `shop_line_lists_items` (`value`, `line_list_id`, `order`) VALUES (?,?,?);")->execute( array($value, $list_id, 0) );
										$value = $db_link->lastInsertId();
									}
									
									if($value !== ''){
										$db_link->prepare("INSERT INTO `shop_properties_values_$table_postfix` (`product_id`,`property_id`,`category_id`,`value`) VALUES (?,?,?,?);")->execute( array($product_id,$property_id,$category_id,$value) );
									}
									
								}
							}
						}
						break;
					case 6:
						$subvalue_array = array();//Массив значений для древовидного списка
						$table_postfix = "tree_list";
						$value = prepareString($value);//Получили значение из CSV
						$value_array_all = explode(":", $value);//Разделили на массив
						if(!empty($value_array_all)){
							foreach($value_array_all as $item_value_array_all){
								$value = prepareString($item_value_array_all);
								if($value === ''){
									continue;
								}
								$value_array = explode(",", $value);//Разделили на массив
								$last_tree_item_id = 0;
								for($tr=0; $tr < count($value_array); $tr++)
								{
									$subvalue = prepareString($value_array[$tr]);
									
									if($subvalue === ''){
										continue;
									}
									
									$level = $tr+1;
									
									if($tr==0)
									{
										$parent = 0;
									}
									else
									{
										$parent = $last_tree_item_id;
									}
									
									//Определяем, есть ли такое значение в древовидном списке
									$list_value_query = $db_link->prepare("SELECT `id` FROM `shop_tree_lists_items` WHERE `value` = ? AND `tree_list_id` = ? AND `level` = ? AND `parent` = ?;");
									$list_value_query->execute( array($subvalue, $list_id, $level, $parent) );
									$value_record = $list_value_query->fetch();
									if( $value_record != false )
									{
										$subvalue = $value_record["id"];
									}
									else
									{
										$db_link->prepare("INSERT INTO `shop_tree_lists_items` (`value`, `tree_list_id`, `order`, `level`, `parent`) VALUES (?,?,?,?,?);")->execute( array($subvalue, $list_id, 0, $level, $parent) );
										
										$subvalue = $db_link->lastInsertId();
										
										if($level > 1)
										{
											$db_link->prepare("UPDATE `shop_tree_lists_items` SET `count` = `count`+1 WHERE `id` = ?;")->execute( array($parent) );
										}
										
									}
									$last_tree_item_id = $subvalue;
									array_push($subvalue_array, $subvalue);
								}
							}
						}
						break;
				}
				
				//Запрос на запись значения свойства для товара
				if($table_postfix != "tree_list")
				{
					if($table_postfix != "list"){
						if($value !== ''){
							$db_link->prepare("INSERT INTO `shop_properties_values_$table_postfix` (`product_id`,`property_id`,`category_id`,`value`) VALUES (?,?,?,?);")->execute( array($product_id,$property_id,$category_id,$value) );
						}
					}
				}
				else
				{
					for($tr=0; $tr < count($subvalue_array); $tr++)
					{
						$subvalue = $subvalue_array[$tr];
						if($subvalue !== ''){
							$db_link->prepare("INSERT INTO `shop_properties_values_$table_postfix` (`product_id`,`property_id`,`category_id`,`value`) VALUES (?,?,?,?);")->execute( array($product_id,$property_id,$category_id,$subvalue) );
						}
					}
				}
			}
		}
		
		//2.5 Добавление складских записей
		$db_link->prepare("INSERT INTO `shop_storages_data` (`storage_id`, `product_id`, `category_id`, `price`, `arrival_time`, `exist`) VALUES (?,?,?,?,?,?);")->execute( array($storage_id, $product_id, $category_id, $price, $time, $exist) );
		
		//Счетчик общего количества строк в файле 
		$count_records++;
    }
    if (!feof($file_handle)) 
	{
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Error: unexpected fgets() fail";
		$answer["warnings"] = array();
		exit( json_encode($answer) );
    }
    fclose($file_handle);
	
	//Удаляем файл прайс-листа
	unlink($import_options["file_full_path"]);
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Не удалось открыть файл";
	$answer["warnings"] = array();
	exit( json_encode($answer) );
}




$answer = array();
$answer["status"] = true;
$answer["message"] = "Выполнено успешно";
array_push($warnings, "Количество строк в файле: $count_records");
array_push($warnings, "Количество новых товаров: $count_created");
array_push($warnings, "Количество ранее созданных товаров: $count_updates_storage_info");
array_push($warnings, "Количество пропущенных по причине неуказанного наименования: $count_continued");
$answer["warnings"] = $warnings;
exit( json_encode($answer) );
?>







<?php
// Транслитерация
function translit($insert) 
{
    $insert = mb_strtolower($insert, 'UTF-8');
    $replase = array(
    // Буквы
    'а'=>'a',
    'б'=>'b',
    'в'=>'v',
    'г'=>'g',
    'д'=>'d',
    'е'=>'e',
    'ё'=>'yo',
    'ж'=>'zh',
    'з'=>'z',
    'и'=>'i',
    'й'=>'j',
    'к'=>'k',
    'л'=>'l',
    'м'=>'m',
    'н'=>'n',
    'о'=>'o',
    'п'=>'p',
    'р'=>'r',
    'с'=>'s',
    'т'=>'t',
    'у'=>'u',
    'ф'=>'f',
    'х'=>'h',
    'ц'=>'c',
    'ч'=>'ch',
    'ш'=>'sh',
    'щ'=>'shh',
    'ъ'=>'j',
    'ы'=>'y',
    'ь'=>'',
    'э'=>'e',
    'ю'=>'yu',
    'я'=>'ya',
    // Всякие знаки препинания и пробелы
    ' '=>'-',
    ' - '=>'-',
    '_'=>'-',
    //Удаляем
    '.'=>'',
    ':'=>'',
    ';'=>'',
    ','=>'',
    '!'=>'',
    '?'=>'',
    '>'=>'',
    '<'=>'',
    '&'=>'',
    '*'=>'',
    '%'=>'',
    '$'=>'',
    '@'=>'',
    '"'=>'',
    '\''=>'',
    '('=>'',
    ')'=>'',
    '`'=>'',
    '+'=>'',
    '/'=>'',
    '\\'=>'',
    );
    $insert=preg_replace("/  +/"," ",$insert); // Удаляем лишние пробелы
    $insert = strtr($insert,$replase);
    return $insert;
}
?>