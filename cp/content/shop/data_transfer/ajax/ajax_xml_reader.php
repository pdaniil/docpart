<?php
/*
	Скрипт зачитывает xml файл каталогов и записывает данные в БД.

	xml файл должен быть в кодировке windows-1251
	
	// Ссылка для прямого вызова
	http://site.ru/cp/content/shop/data_transfer/ajax/ajax_xml_reader.php?storage=17&clear=0&file=1.xml

	Файл 1.xml ложить в /content/xml/

	Настройка CRON:

	sweb:
	/usr/bin/php5.4 /_ПУТЬ_/public_html/cp/content/shop/data_transfer/ajax/ajax_xml_reader.php?storage=17&clear=0&file=1.xml

	cPanel:
	wget -q -O- http://site.ru/cp/content/shop/data_transfer/ajax/ajax_xml_reader.php?storage=17&clear=0&file=1.xml > /dev/null 2>&1

	clear=0 - обновятся цены только тех товаров которые будут в файле
	clear=1 - Удалятся все цены склада и добавятся новые из файла
	clear=2 - Полная очистка каталога перед загрузкой, удаляются все таблицы.
	
*/
//ini_set('display_errors', 1);
set_time_limit(1000);
header('Content-Type: application/json;charset=utf-8;');

$start = date("Y-m-d H:i:s", time());

$f = fopen('error_log.txt', 'w');
fwrite($f, $str. "\n\n");
function my_log($str){
	global $f;
	$f = fopen('error_log.txt', 'a');
	fwrite($f, $str. "\n\n");
}

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




// -----------------------------------------------------------------------------------------------------
// 1. ПОДКЛЮЧЕНИЕ К БД
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

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = (int)DP_User::getAdminId();






// PDO -------------------------------------------------------------------------------------------------

function db_connect_pdo($param = false){
	global $DP_Config, $error_message;
	try
	{
		if(!empty($param))
		{
			$host = $param['host'];
			$user = $param['user'];
			$pswd = $param['password'];
			$db = $param['db'];
		}
		else
		{
			$host = $DP_Config->host;
			$user = $DP_Config->user;
			$pswd = $DP_Config->password;
			$db = $DP_Config->db;
		}
		
		$db = new PDO("mysql:host=$host;dbname=$db",$user,$pswd);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->exec("SET NAMES utf8");
		return $db;
	}
	catch(PDOException $e)
	{
		$error_message .= '<br/>EXCEPTION:<br/>'. $e .'<br/>';
	}
}

// -----------------------------------------------------------------------------------------------------



// Очистка таблиц -------------------------------------------------------------------------------------------------


function clear_table($type)
{
	global $DP_Config, $db, $error_message, $storages_id;
	try
	{
		switch($type){
			case 1 :
				// Удаление только складской информации. Когда нужно удалить цены на товары которых нет в файле. 
				// Удаляем складскую и нформацию по складу
				$sql = "DELETE FROM `shop_storages_data` WHERE `storage_id` = $storages_id;";
				$db->exec($sql);
			break;
			case 2 :
				// Полная очистка каталога
				$sql = "TRUNCATE TABLE `shop_catalogue_categories`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_catalogue_products`;"; 			$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_categories_properties_map`;"; 	$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_line_lists`;"; 					$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_line_lists_items`;"; 			$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_products_images`;"; 				$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_products_text`;"; 				$db->exec($sql);

				$sql = "TRUNCATE TABLE `shop_properties_values_bool`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_properties_values_float`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_properties_values_int`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_properties_values_list`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_properties_values_text`;"; 		$db->exec($sql);
				
				$sql = "TRUNCATE TABLE `shop_products_stickers`;"; 		$db->exec($sql);
				$sql = "TRUNCATE TABLE `shop_properties_values_tree_list`;"; 		$db->exec($sql);
				
				// Удаляем складскую и нформацию по складу
				$sql = "DELETE FROM `shop_storages_data` LIMIT 50;";
				$query = $db->prepare($sql);
				do{
					$query->execute();
				}while($query->rowCount() > 0);
			break;
		}
		
		return true;
	}
	catch(PDOException $e)
	{
		$db->rollback();// Отменяем транзакцию
		$error_message .= '<br/>EXCEPTION:<br/>'. $e .'<br/><br/><br/>';
	}
}

// -----------------------------------------------------------------------------------------------------





if(!empty($_GET['file'])){
	$file = $_SERVER["DOCUMENT_ROOT"] .'/content/xml/'. $_GET['file'];
}else{
	$file = $_SERVER["DOCUMENT_ROOT"] .'/'. $DP_Config->backend_dir .'/tmp/catalogue_import.xml';
}



if(!file_exists($file)){
	$result = array('error_message' => 'xml файл не найден. '.$file);
	exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}

$storages_id = (int)$_GET['storage'];// id склада
$clear_table = (int)$_GET['clear'];// флаг очистить таблицы перед загрузкой данных: 1 - удалить только складскую информацию по указанному складу. 2 - полная очистка каталога с пересозданием таблиц и индексов

if(empty($storages_id)){
	$result = array('error_message' => 'id склада не указан.');
	exit(json_encode($result, JSON_UNESCAPED_UNICODE));
}




$parent_id = 0;// id родительской категории в нашей базе.
$error_message = '';// Сообщения об ошибках.
$suggestion_sql = '';// Параметры инсерта для блока suggestion
$suggestion_sql_cnt = 0;// счетчик suggestion
$suggestion_sql_array = array();// массив Параметры инсерта для блока suggestion
$products_shop_storages_data_sql_id_delete = '';// id продуктов для которых нашлось новое наличие и цена в файле поэтому старые записи затем очистим
$arr_id_products_update_prices = array();// В массив добавляем id продуктов для которых добавляем новую цену, что бы не добавить несколько цен когда одинаковых товаров в категории несколько
$level = 1;// уровень вложенности категории
$url = '';


// Информация
$info_cnt_category_all = 0;
$info_cnt_category_new = 0;

$info_cnt_products_all = 0;
$info_cnt_products_new = 0;

$info_time = 0;


$reader = new XMLReader;
$reader_result = $reader->open($file);// Открываем файл на чтение

try{
	
	$db = db_connect_pdo();// Подключаемся к базе
	$r = $db->beginTransaction();// Стартуем транзакцию
	
	
	// -------------------------------------------
	// Полностью Очищаем таблицы:
	if($clear_table === 2)
	{
		clear_table(2);
	}
	// -------------------------------------------

	
	function readerCategories($parent_id, $level, $url)
	{
		global $reader, $db;
		while($reader->read())
		{
			
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "categories")
			{
				return true;
			}
			
			if($reader->name === "category" && $reader->nodeType == XMLReader::ELEMENT)
			{
				$res = readerCategory($parent_id, $level, $url);
			}
		
		}
	}

	function readerCategory($parent_id, $level, $url)
	{
		global $DP_Config, $db, $error_message, $reader, $clear_table, $info_cnt_category_all, $info_cnt_category_new;
		
		$doc = new DOMDocument;
		$parent_id = (int) $parent_id;// Родитель
		$category_id = null;// id созданой категории в нашей базе или id который должен быть у создаваемой категории если он указан в файле

		while($reader->read())
		{
			if($reader->name === "categories" && $reader->nodeType == XMLReader::ELEMENT)
			{
				$res = readerCategories($category_id, $level+1, $url);
			}
			
			
			
			if($reader->nodeType == XMLReader::ELEMENT)
			{
				switch($reader->name)
				{
					case 'id':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
						// Записываем id категории из файла только если была полная очистка талиц каталога
						if($clear_table === 2){
							$category_id = (int)$node;
						}
					break;
					case 'caption':
						
						$info_cnt_category_all++;
						
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));
						$alias = trim(translit($node));
						$url = $url .(($url != '')? '/' : ''). $alias;
						
						if(empty($category_id)){
							// Проверяем в базе наличие категории с таким же именем, если она есть получаем ее id
							$sql = "SELECT `id` FROM `shop_catalogue_categories` WHERE `alias` = ". $db->quote(trim($alias)) ." AND `parent` = $parent_id;";
							$result = $db->query($sql);
							$result = $result->fetch(PDO::FETCH_ASSOC);
							if( ! empty($result) )
							{
								// Категория есть, получаем ее id:
								$category_id = (int) $result['id'];// id категории в базе
							}
						}
						
						// Если категория есть удаляем всю информцию по ней что бы потом создать заново
						if( ! empty($category_id) ){
							$sql = "DELETE FROM `shop_categories_properties_map` WHERE `category_id` IN(SELECT `id` FROM `shop_catalogue_categories` WHERE `alias` = ". $db->quote(trim($alias)) .");";
							$result = $db->query($sql);
							
							$sql = "DELETE FROM `shop_catalogue_categories` WHERE `alias` = ".$db->quote(trim($alias));
							$result = $db->query($sql);
						}
						
						// Создаем категорию
						if( 1 )
						{
							// Такой категории нет, создаем новую:
							$alias 	 		 = $db->quote(trim($alias));
							$url_str 		 = $db->quote(trim($url));
							$count 	 		 = 0;
							$level 	 		 = (int) $level;
							$value 	 		 = $db->quote(trim($node));
							$parent  		 = (int) $parent_id;
							$title_tag 		 = $value;
							$description_tag = $value;
							$keywords_tag 	 = $value;
							
							$order = $db->query("SELECT MAX(`order`) AS 'max_order' FROM `shop_catalogue_categories`;");
							$order = $order->fetch(PDO::FETCH_ASSOC);
							$order = (int) $order['max_order'] + 1;
							
							$published_flag = 1;
							
							// Если есть $category_id то указываем его
							if($category_id){
								$sql = "INSERT INTO `shop_catalogue_categories` 
								(`id`, `alias`, `url`, `count`, `level`, `value`, `parent`, `title_tag`, `description_tag`, `keywords_tag`, `order`, `published_flag`) 
								VALUES ($category_id, $alias, $url_str, $count, $level, $value, $parent, $title_tag, $description_tag, $keywords_tag, $order, $published_flag);";
							}else{
								$sql = "INSERT INTO `shop_catalogue_categories` 
								(`alias`, `url`, `count`, `level`, `value`, `parent`, `title_tag`, `description_tag`, `keywords_tag`, `order`, `published_flag`) 
								VALUES ($alias, $url_str, $count, $level, $value, $parent, $title_tag, $description_tag, $keywords_tag, $order, $published_flag);";
							}
							
							if($db->exec($sql) !== false)
							{
								$category_id = (int) $db->lastInsertId();
								// увеличиваем число вложенных категорий у родительской котегории
								if($parent_id > 0)
								{
									$sql = "SELECT COUNT(*) AS 'cnt' FROM `shop_catalogue_categories` WHERE `parent` = $parent_id";
									$result = $db->query($sql);
									$result = $result->fetch(PDO::FETCH_ASSOC);
									
									$sql = "UPDATE `shop_catalogue_categories` SET `count` = ".$result['cnt']." WHERE `id` = ". $parent_id;
									$db->exec($sql);
								}
								
								$info_cnt_category_new++;
							}
							else
							{
								// Неудалось создать новую категорию в базе.
								$error_message .= '<br/>Ошибка:<br/>Неудалось добавить категорию '.$node.'<br/>';
								$category_id = null;
							}
						}
					break;
					case 'image':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if((!empty($node)) && $category_id > 0){
							// Обновляем данные категории
							$sql = "UPDATE `shop_catalogue_categories` SET `image` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							if($db -> exec($sql) === false){
								$error_message .= '<br/>Ошибка:<br/>Неудалось обновить картинку категории id'.$category_id.'<br/>Свойство: '. $reader->name .' Значение: '. $node .'<br/>';
							}
						}
					break;
					case 'order':
						$node = (int)simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
		
						
						if($node > 0 && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `order` = ". $node ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'title_tag':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if( (!empty($node)) && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `title_tag` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'alias':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if( (!empty($node)) && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `alias` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'url':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if( (!empty($node)) && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `url` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'description_tag':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if( (!empty($node)) && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `description_tag` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'keywords_tag':
						$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Значение
						$node = trim($node);
						if( (!empty($node)) && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `keywords_tag` = ". $db->quote($node) ." WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
					case 'properties':
						//echo '<br/>свойства:<br/>';
						$res = readerProperties($category_id);
					break;
					case 'products':
						//echo '<br/>Продукты:<br/>';
						$res = readerProducts($category_id);
					break;
					case 'published_flag':
						$published_flag = (int) trim(simplexml_import_dom($doc->importNode($reader->expand(), true)));// Значение
						$node = trim($node);
						if($published_flag === 0 && $category_id > 0){
							$sql = "UPDATE `shop_catalogue_categories` SET `published_flag` = 0 WHERE `id` = ". $category_id;
							$db -> exec($sql);
						}
					break;
				}
				
			}
			
			
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "category"){
				return true;
			}
			
		}
	}

	// Цикл по свойствам категории (<properties>).
	function readerProperties($category_id)
	{
		global $DP_Config, $db, $error_message, $reader, $clear_table;
		$doc = new DOMDocument;
		$category_id = (int)$category_id;
		while($reader->read())
		{
		
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "properties")
			{
				return true;// Все свойства категории опрошены.
			}
			
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "category")
			{
				return true;//Отработает в случае невалидного xml файла, если будет не найден закрывающий тег </properties>.
			}
			
			if($reader->name === "property" && $reader->nodeType == XMLReader::ELEMENT)
			{
				// Если у свойства есть родительская категория. Её может не быть если произошла ошибка при создании категории.
				if($category_id > 0)
				{
					// Проверяем наличие в базе свойства с таким же названием и в той же категории и если такого нет то записываем свойство в БД.
					$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Объект 'property'.
					
					$node_caption = trim($node->caption);// Название свойства
					$node_type_name = trim($node->type_name);// Название типа свойства
					$node_type = (int) trim($node->type);// id типа свойства
					if(empty($node_caption)){
						continue;
					}
					
					// Узнаем тип свойства
					$type = $db->query("SELECT `id` FROM `shop_properties_types` WHERE `caption` = ". $db->quote($node_type_name));
					
					$type = $type -> fetch(PDO::FETCH_ASSOC);
					$type = (int)$type['id'];
					
					// Если в файле не окажется названия типа свойства то попробуем выбрать id типа свойства
					if($type === 0){
						$type = $node_type;
					}
					
					if($type > 0)
					{
						// Есть ли такое свойство в базе:
						$sql = "SELECT `id`, `list_id` FROM `shop_categories_properties_map` WHERE `value` = ". $db->quote($node_caption) ." AND `category_id` = $category_id AND `property_type_id` = $type;";
						
						$result = $db -> query($sql);
						$result = $result -> fetch(PDO::FETCH_ASSOC);
						
						if( empty($result) )
						{
							// Такого свойства у категории в базе нет.
							// Создаем новое свойство категории в нашей базе.
							
							// Если в файле указано id свойства и была полная очистка таблиц
							$property_id = 'NULL';
							$node_id = (int) trim($node->id);
							if( (!empty($node_id)) && ($clear_table === 2) ){
								$property_id = $node_id;
							}
							
							// В зависимости от типа:
							switch((int)$type)
							{
								case 1:
								case 2:
								case 3:
								case 4:
									$list_id = 0;
									
									
									$sql = "INSERT INTO `shop_categories_properties_map` 
									(`id`, `category_id`, `property_type_id`, `value`, `list_id`, `order`) 
									SELECT $property_id, $category_id, $type, ". $db->quote(trim($node->caption)) .", $list_id, (MAX(`order`)+1) AS `max` FROM `shop_categories_properties_map`;";
									
									
								break;
								case 5:
									$plurality = (int) trim($node->plurality);// Тип списка (множественный / еденичный)
									if($plurality === 0){
										$plurality = 1;
									}
									
									$list_id = null;
									if(!empty($node->list_id)){
										$list_id = (int)$node->list_id;
									}
									
									// Так как свойство категори может называться не так как название списка то ищем по id если он указан
									if($list_id > 0){
										$sql = "SELECT `id` FROM `shop_line_lists` WHERE `id` = $list_id AND `type` = $plurality;";
									}else{
										$sql = "SELECT `id` FROM `shop_line_lists` WHERE `caption` = ". $db->quote(trim($node->caption)) ." AND `type` = $plurality;";
									}
										
									// Проверяем наличие такого списка в базе
									$result = $db -> query($sql);
									$result = $result -> fetch(PDO::FETCH_ASSOC);
									
									if(empty($result))
									{
										// Такого списка нет -> Создаем список
										$caption = $db->quote(trim($node->caption));
										$data_type = $db->quote('text');
										$auto_sort = $db->quote('asc');
										
										$list_id = 'NULL';
										if( (!empty($node->list_id)) && ($clear_table === 2) ){
											$list_id = (int)$node->list_id;
										}
										
										
										$sql = "INSERT INTO `shop_line_lists` 
											(`id`, `caption`, `type`, `data_type`, `auto_sort`) 
											VALUE ($list_id, $caption, $plurality, $data_type, $auto_sort);";
										
										
										$db->exec($sql);
										$list_id = (int) $db->lastInsertId();
										
									}
									else
									{
										// Такой список есть -> получаем его id
										$list_id = (int) $result['id'];
									}
									
									if($list_id > 0)
									{
										// Цикл по items
										foreach($node->items->item as $item)
										{
											$caption = $db->quote(trim($item->caption));
											
											$sql = "SELECT `id` FROM `shop_line_lists_items` WHERE `value` = $caption AND `line_list_id` = $list_id;";
											$result = $db -> query($sql);
											$result = $result -> fetch(PDO::FETCH_ASSOC);
											
											if(empty($result))
											{
												$item_id = 'NULL';
												if( (!empty($item->id)) && ($clear_table === 2) ){
													$item_id = (int) trim($item->id);
												}
												
												$sql = "INSERT INTO `shop_line_lists_items` 
													(`id`, `line_list_id`, `value`, `order`) 
													SELECT $item_id, $list_id, $caption, (MAX(`order`)+1) AS `max` FROM `shop_line_lists_items`;";
												
												$db->exec($sql);
											}
											
										}
									}
									// Добавляем свойство к категории
									$sql = "INSERT INTO `shop_categories_properties_map` 
									(`id`, `category_id`, `property_type_id`, `value`, `list_id`, `order`) 
									SELECT $property_id, $category_id, $type, ". $db->quote(trim($node->caption)) .", $list_id, (MAX(`order`)+1) AS `max` FROM `shop_categories_properties_map`;";
								break;
								default : $sql = '';
							}
							
							if($sql != ''){
								if($db->exec($sql) !== false)
								{
									// Свойство категории добавленно.
									//$property_id = (int) $db->lastInsertId();
									
									
								}
								else
								{
									// Неудалось создать свойство категории в базе.
									$error_message .= '<br/>Ошибка:<br/>Неудалось создать свойство категории '.$node->caption.'<br/>Для категории id'.$category_id.'<br/>';
								}
							}
						}
						else
						{
							// Такое свойства в базе есть. получаем его данные:
							if($type === 5)
							{
								$list_id = (int) $result['list_id'];
								
								if($list_id > 0)
								{
									// Цикл по items
									foreach($node->items->item as $item)
									{
										$caption = $db->quote(trim($item->caption));
										
										$sql = "SELECT `id` FROM `shop_line_lists_items` WHERE `value` = $caption AND `line_list_id` = $list_id;";
										$result = $db -> query($sql);
										$result = $result -> fetch(PDO::FETCH_ASSOC);
										
										if(empty($result))
										{
											
											$item_id = 'NULL';
											if( (!empty($item->id)) && ($clear_table === 2) ){
												$item_id = (int)$item->id;
											}
											
											$sql = "INSERT INTO `shop_line_lists_items` 
												(`id`, `line_list_id`, `value`, `order`) 
												SELECT $item_id, $list_id, $caption, (MAX(`order`)+1) AS `max` FROM `shop_line_lists_items`;";
											$db->exec($sql);
										}
										
									}
								}
							}
						}
					
					}
					else
					{
						// такого типа в базе нет - пропускаем свойство
					}// if($type > 0)
						
				}
			}
			
		}
	}

	// Цикл по продуктам категории.
	function readerProducts($parent_id)
	{
		global $DP_Config, $db, $error_message, $reader, $storages_id, $user_id, $suggestion_sql, $suggestion_sql_cnt, $suggestion_sql_array, $products_shop_storages_data_sql_id_delete, $clear_table, $arr_id_products_update_prices, $info_cnt_products_all, $info_cnt_products_new;
		$doc = new DOMDocument;
		$product_id = 0;
		$parent_id = (int)$parent_id;
		while($reader->read())
		{
			
			$product_id = 0;
			
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "products")
			{
				return true;// Все продукты категории опрошены.
			}
			
			if($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == "category")
			{
				return true;//Отработает в случае невалидного xml файла, если будет не найден закрывающий тег </products>.
			}
			
			if($reader->name === "product" && $reader->nodeType == XMLReader::ELEMENT)
			{
				
				$info_cnt_products_all++;
				
				// Если у продукта есть родитель. Его может не быть если произошла ошибка при создании категории.
				if($parent_id > 0)
				{
					// Проверяем наличие в базе продукта с таким же названием и в той же категории и если такого нет то записываем продукт в БД.
					$node = simplexml_import_dom($doc->importNode($reader->expand(), true));// Объект 'product'.
					
					if($clear_table === 2){
						$product_id = (int)$node->id;
					}
					
					$category_id 	 = $parent_id;
					$caption 		 = trim($node->caption);
					
					
					$alias = trim(translit($caption));
					// Если alias был указан в файле
					if(!empty($node->alias)){
						$alias = trim($node->alias);
					}
					
					// Если поиск был по id то нужно проверить нет ли товара в категории с таким же alias и если есть то нужно к alias добавить его id
					if($product_id > 0){
						$sql = "SELECT `id`, `category_id` FROM `shop_catalogue_products` WHERE `alias` = ". $db->quote($alias) ." AND `category_id` = $parent_id AND `id` != $product_id;";
						$result = $db -> query($sql);
						$result = $result -> fetch(PDO::FETCH_ASSOC);
						if( !empty($result) ){
							$alias  = $alias .'-'. $product_id;
						}
					}
					
					$alias = $db->quote($alias);
					
					// Находим все товары по alias
					if($product_id > 0){
						$sql = "SELECT `id`, `category_id` FROM `shop_catalogue_products` WHERE `id` = $product_id AND `category_id` = $parent_id;";
					}else{
						$sql = "SELECT `id`, `category_id` FROM `shop_catalogue_products` WHERE `alias` = ". $alias ." AND `category_id` = $parent_id;";
					}
					
					$products_to_delete = array();
					$result = $db -> query($sql);
					while($row = $result -> fetch(PDO::FETCH_ASSOC)){
						if( !empty($row) )
						{
							$products_to_delete[] = (int) $row['id'];// Для удаления товаров с одинаковым alias
							if(empty($product_id)){
								$product_id = (int) $row['id'];// Что бы у созданного товара остался прежний id
							}
						}
					}
					
					if( !empty($products_to_delete) )
					{
						//АЛГОРИТМ УДАЛЕНИЯ ПРОДУКТА ИЗ СПРАВОЧНИКА
						//1. Удаляем учетную запись продукта из таблицы shop_catalogue_products
						//2. Удаляем изображения продукта из таблицы shop_products_images
						//3. Удаляем текстовые описания продуктов из таблицы shop_products_texts
						//4. Удаляем значения свойств продуктов из 5 таблиц значений свойств


						//Составляем строку в формате '(ID1, ID2, ..., IDN)'
						$sub_SQL_PRODUCTS_LIST = "";
						$binding_values = array();
						for($i=0; $i < count($products_to_delete); $i++)
						{
							if($i > 0)
							{
								$sub_SQL_PRODUCTS_LIST .= ",";
							}
							$sub_SQL_PRODUCTS_LIST .= "?";
							
							array_push($binding_values, $products_to_delete[$i]);
						}
						$sub_SQL_PRODUCTS_LIST = "(".$sub_SQL_PRODUCTS_LIST.")";



						//Формируем SQL-запросы:
						//1. Удаляем учетную запись продукта из таблицы shop_catalogue_products
						$SQL_DELETE_PRODUCTS_RECORDS = "DELETE FROM `shop_catalogue_products` WHERE `id` IN $sub_SQL_PRODUCTS_LIST;";
						//2. Удаляем изображения продукта из таблицы shop_products_images
						$SQL_DELETE_PRODUCTS_IMAGES = "DELETE FROM `shop_products_images` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						//3. Удаляем текстовые описания продуктов из таблицы shop_products_text
						$SQL_DELETE_PRODUCTS_TEXTS = "DELETE FROM `shop_products_text` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						//4. Удаляем значения свойств продуктов из 5 таблиц значений свойств
						$SQL_DELETE_PRODUCTS_PROPERTIES_INT = "DELETE FROM `shop_properties_values_int` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						$SQL_DELETE_PRODUCTS_PROPERTIES_FLOAT = "DELETE FROM `shop_properties_values_float` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						$SQL_DELETE_PRODUCTS_PROPERTIES_TEXT = "DELETE FROM `shop_properties_values_text` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						$SQL_DELETE_PRODUCTS_PROPERTIES_BOOL = "DELETE FROM `shop_properties_values_bool` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						$SQL_DELETE_PRODUCTS_PROPERTIES_LIST = "DELETE FROM `shop_properties_values_list` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";


						//Удаляем стикеры
						$SQL_DELETE_PRODUCTS_PRODUCTS_STICKERS = "DELETE FROM `shop_products_stickers` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";

						//Удаляем древовидный список
						$SQL_DELETE_PRODUCTS_TREE_LIST = "DELETE FROM `shop_properties_values_tree_list` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST;";
						
						//Удаляем складские записи
						$SQL_DELETE_PRODUCTS_STORAGES_DATA = "DELETE FROM `shop_storages_data` WHERE `product_id` IN $sub_SQL_PRODUCTS_LIST AND `storage_id` = $storages_id;";
						
						//ВЫПОЛНЯЕМ ЗАПРОСЫ:
						$delete_products_error_messages = array();//Массив с сообщениями об ошибках
						if( $db->prepare($SQL_DELETE_PRODUCTS_RECORDS)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления учетных записей продуктов");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_IMAGES)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления изображений продуктов");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_TEXTS)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления текстовых описаний продуктов");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_INT)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления свойств продуктов типа INT");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_FLOAT)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления свойств продуктов типа FLOAT");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_TEXT)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления свойств продуктов типа TEXT");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_BOOL)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления свойств продуктов типа BOOL");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PROPERTIES_LIST)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления списковых свойств продуктов");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_PRODUCTS_STICKERS)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления стикеров продуктов");
						}
						if( $db->prepare($SQL_DELETE_PRODUCTS_TREE_LIST)->execute($binding_values) != true)
						{
							array_push($delete_products_error_messages, "Ошибка удаления привязки к древовидному списку");
						}
						if($clear_table != 0){
							if( $db->prepare($SQL_DELETE_PRODUCTS_STORAGES_DATA)->execute($binding_values) != true)
							{
								array_push($delete_products_error_messages, "Ошибка удаления складской информации");
							}
						}
					}
				
					// Создаем новый продукт в нашей базе.
					
					$caption = $db->quote($caption);
					
					if(!empty($node->title_tag)){
						$title_tag = $db->quote(trim($node->title_tag));
					}else{
						$title_tag = $caption;
					}
					
					if(!empty($node->description_tag)){
						$description_tag = $db->quote(trim($node->description_tag));
					}else{
						$description_tag = $caption;
					}
					
					if(!empty($node->keywords_tag)){
						$keywords_tag = $db->quote(trim($node->keywords_tag));
					}else{
						$keywords_tag = $caption;
					}
					
					
					$published_flag  = 1;
					if(isset($node->published_flag)){
						$published_flag = (int) $node->published_flag;
					}
					
					if(empty($product_id)){
						$product_id = 'NULL';
					}
					
					$sql = "INSERT INTO `shop_catalogue_products` (`id`, `category_id`, `caption`, `alias`, `title_tag`, `description_tag`, `keywords_tag`, `published_flag`) 
					VALUES ($product_id, $category_id, $caption, $alias, $title_tag, $description_tag, $keywords_tag, $published_flag);";
					
					if($db->exec($sql) !== false)
					{
						
						$product_id = (int) $db->lastInsertId();// id созданного продукта.
						$info_cnt_products_new++;
						// Добавляем свойства созданного продукта.
						
						// Добавляем описание продукта. Преобразуем в html
						$node_text = trim($node->text->asXML());
						$node_text = htmlspecialchars_decode($node_text);
						$sql = "INSERT INTO `shop_products_text` (`product_id`, `content`) 
							VALUES ($product_id, ". $db->quote($node_text) .");";
						$db->exec($sql);

						//Обрабатываем изображения
						$images_node = $node->images;

						$insert_values = array();
						$insert_binds = array();

						foreach ( $images_node->children() as $child ) {
							
							$image_name = trim( $child->__toString() );
							$domain = str_replace(array('http://','https://','/'),'',$DP_Config->domain_path);
							$image_name = str_replace(
														array(
															"http://".$domain."/content/files/images/products_images/",
															"https://".$domain."/content/files/images/products_images/"
														),"",$image_name
													 );
							//Добавляем данные
							$insert_values[] = "(?,?)";
							array_push( $insert_binds,
								$product_id,
								$image_name
							);

						}

						//Есть данные для вставки
						if ( ! empty( $insert_values ) ) { 
							
							$sql_insert = "INSERT INTO `shop_products_images` (`product_id`, `file_name`) VALUES " . implode( ',', $insert_values );
							$stmt = $db->prepare( $sql_insert );
							$stmt->execute( $insert_binds );
						}

						
						// Цикл по 'property' продукта.
						foreach($node->properties->property as $property)
						{
							
							////////////////////////////////////////////////////////
							
							// Проверяем наличие в базе свойства с таким же названием и если такого нет то записываем свойство в БД.
							$arr_value = array();// массив id items для списка

							// Узнаем тип свойства
							$type = $db->query("SELECT `id` FROM `shop_properties_types` WHERE `caption` = ". $db->quote(trim($property->type_name)));
							$type = $type -> fetch(PDO::FETCH_ASSOC);
							$type = (int)$type['id'];
							
							if($type === 0){
								$type = (int) trim($property->type);
							}
							
							if($type > 0)
							{
								
								$sql = "SELECT `id`, `list_id` FROM `shop_categories_properties_map` WHERE `value` = ". $db->quote(trim($property->caption)) ." AND `category_id` = $category_id AND `property_type_id` = $type;";
								
								$result = $db -> query($sql);
								$result = $result -> fetch(PDO::FETCH_ASSOC);
								
								if( empty($result) )
								{
									// Такого свойства в базе нет.
									// Создаем новое свойство продукта в нашей базе.
									
									$property_id = 'NULL';
									if( (!empty($property->id)) && ($clear_table === 2) ){
										$property_id = (int)$property->id;
									}
									
									switch($type)
									{
										case 1:
										case 2:
										case 3:
										case 4:
											$sql = "INSERT INTO `shop_categories_properties_map` 
											(`id`, `category_id`, `property_type_id`, `value`, `list_id`, `order`) 
											SELECT $property_id, $category_id, $type, ". $db->quote(trim($property->caption)) .", 0, (MAX(`order`)+1) AS `max` FROM `shop_categories_properties_map`;";
										break;
										case 5:
											
											// Тип списка
											if(count($property->values->value) > 1)
											{
												$plurality = 2;// множественный
											}
											else
											{
												$plurality = 1;// еденичный
											}
											
											$list_id = NULL;
											if(!empty($property->list_id)){
												$list_id = (int)$property->list_id;
											}
											
											// Так как свойство категори может называться не так как название списка то ищем по id если он указан
											if($list_id > 0){
												$sql = "SELECT `id` FROM `shop_line_lists` WHERE `id` = $list_id AND `type` = $plurality;";
											}else{
												$sql = "SELECT `id` FROM `shop_line_lists` WHERE `caption` = ". $db->quote(trim($property->caption)) ." AND `type` = $plurality;";
											}
											
											// Проверяем наличие такого списка в базе
											$result = $db -> query($sql);
											$result = $result -> fetch(PDO::FETCH_ASSOC);
											
											if(empty($result))
											{
												// Такого списка нет -> Создаем список
												$caption = $db->quote(trim($property->caption));
												
												$data_type = $db->quote('text');
												if(is_numeric(mb_substr($property->values->value->caption,0,1,'UTF-8'))){
													$data_type = $db->quote('number');
												}
												$auto_sort = $db->quote('asc');
												
												$sql = "INSERT INTO `shop_line_lists` 
														(`id`, `caption`, `type`, `data_type`, `auto_sort`) 
														VALUE (NULL, $caption, $plurality, $data_type, $auto_sort);";
												$db->exec($sql);
												$list_id = (int) $db->lastInsertId();
												
											}
											else
											{
												
												// Такой список есть -> получаем его id
												$list_id = $result['id'];
											}
											
											
											if($list_id > 0)
											{
												// Цикл по items
												foreach($property->values->value as $item)
												{
													$caption = $item->caption;
													$caption = str_replace(array('"',"'"), '', $caption);
													$caption = trim($caption);
													$caption = $db->quote($caption);
													
													$sql = "SELECT `id` FROM `shop_line_lists_items` WHERE `value` = $caption AND `line_list_id` = $list_id;";
													$result = $db -> query($sql);
													$result = $result -> fetch(PDO::FETCH_ASSOC);
													
													if(empty($result))
													{
														$item_id = 'NULL';
														if( (!empty($item->id)) && ($clear_table === 2) ){
															$item_id = (int)$item->id;
														}
														
														$sql = "INSERT INTO `shop_line_lists_items` 
														(`id`, `line_list_id`, `value`, `order`) 
														SELECT $item_id, $list_id, $caption, (MAX(`order`)+1) AS `max` FROM `shop_line_lists_items`;";
														$db->exec($sql);
														$arr_value[] = (int) $db->lastInsertId();
													}
													else
													{
														$arr_value[] = (int) $result['id'];
													}
												}
											}
											
											$sql = "INSERT INTO `shop_categories_properties_map` 
											(`id`, `category_id`, `property_type_id`, `value`, `list_id`, `order`) 
											SELECT $property_id, $category_id, $type, ". $db->quote(trim($property->caption)) .", $list_id, (MAX(`order`)+1) AS `max` FROM `shop_categories_properties_map`;";
										break;
										case 6:
											
											// Тип Древовидный список
											
											$list_id = NULL;
											
											if(!empty($property->list_id)){
												$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `id` = ".$db->quote(trim($property->list_id));
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												if(!empty($result))
												{
													$list_id = (int) $result['id'];
												}
											}
											
											if(empty($list_id)){
												
												// Вытаскиваем id списка
												if(!empty($property->list_caption)){
													$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `caption` = ".$db->quote(trim($property->list_caption));
												}else{
													$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `caption` = ".$db->quote(trim($property->caption));
												}
												
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												
												if(empty($result))
												{
													// Такого списка нет -> 
													if(!empty($property->list_caption)){
														$caption = $db->quote(trim($property->list_caption));
													}else{
														$caption = $db->quote(trim($property->caption));
													}
													
													$data_type = $db->quote('text');
													
													$sql = "INSERT INTO `shop_tree_lists` 
															(`caption`, `data_type`) 
															VALUE ($caption, $data_type);";
													$db->exec($sql);
													$list_id = (int) $db->lastInsertId();
												}
												else
												{
													
													// Такой список есть -> получаем его id
													$list_id = $result['id'];
												}
											}
											
											
											if($list_id > 0)
											{
												// Цикл по items
														
												foreach($property->values->value as $item)
												{
													$caption = $item->caption;// В $caption содержится вложенность применимости через ;
													$parent_tree_items = 0;
													$level_tree_items = 1;
													
													$primenimost = explode(';', $caption);
													
													foreach($primenimost as $item_tree_value){
														$item_tree_value = trim(str_replace(array('"',"'"), '', $item_tree_value));
														
														if($item_tree_value === ''){
															continue;
														}
														
														$sql = "SELECT `id` FROM `shop_tree_lists_items` WHERE `value` = ". $db->quote($item_tree_value) ." AND `parent` = $parent_tree_items AND `tree_list_id` = $list_id;";
														$result = $db -> query($sql);
														$result = $result -> fetch(PDO::FETCH_ASSOC);
														
														if(empty($result))
														{
															// Увеличиваем у предыдущего количество вложенных элементов
															$sql = "UPDATE `shop_tree_lists_items` SET `count` = `count`+1 WHERE `id` = $parent_tree_items;";
															$db->exec($sql);
															
															// Если в базе нет такого элемента
															$sql = "INSERT INTO `shop_tree_lists_items` 
															(`tree_list_id`, `value`, `count`, `level`, `parent`, `order`) 
															SELECT $list_id, ". $db->quote($item_tree_value) .", 0, $level_tree_items, $parent_tree_items, (MAX(`order`)+1) AS `max` FROM `shop_tree_lists_items`;";
															$db->exec($sql);
															$parent_tree_items = (int) $db->lastInsertId();
															$arr_value[] = $parent_tree_items;
															$level_tree_items++;
															my_log($sql);
															
														}
														else
														{
															$arr_value[] = (int) $result['id'];
															$parent_tree_items = (int) $result['id'];
															$level_tree_items++;
														}
													}
													
												}
											}
											
											$sql = "INSERT INTO `shop_categories_properties_map` 
											(`category_id`, `property_type_id`, `value`, `list_id`, `order`) 
											SELECT $category_id, $type, ". $db->quote(trim($property->caption)) .", $list_id, (MAX(`order`)+1) AS `max` FROM `shop_categories_properties_map`;";
											
										break;
										default : $sql = '';
									}
									
									if($db->exec($sql) !== false)
									{
										// Свойство категории добавленно.
										$property_id = (int) $db->lastInsertId();
										
									}
									else
									{
										// Неудалось создать свойство категории в базе.
										$error_message .= '<br/>Ошибка:<br/>Неудалось создать свойство продукта '.$node->caption.'<br/>Для категории id'.$category_id.'<br/>';
									}
								}
								else
								{
									// Такое свойства в базе есть.
									$property_id = $result['id'];
									$list_id = (int)$result['list_id'];
									
									if($type === 5)
									{
										// Цикл по items
										foreach($property->values->value as $item)
										{
											$caption = $item->caption;
											$caption = str_replace(array('"',"'"), '', $caption);
											$caption = trim($caption);
											$caption = $db->quote($caption);
											
											$sql = "SELECT `id` FROM `shop_line_lists_items` WHERE `value` = $caption AND `line_list_id` = $list_id;";
											$result = $db -> query($sql);
											$result = $result -> fetch(PDO::FETCH_ASSOC);
											
											if(empty($result))
											{
												$item_id = 'NULL';
												if( (!empty($item->id)) && ($clear_table === 2) ){
													$item_id = (int)$item->id;
												}
												
												$sql = "INSERT INTO `shop_line_lists_items` 
												(`id`, `line_list_id`, `value`, `order`) 
												SELECT $item_id, $list_id, $caption, (MAX(`order`)+1) AS `max` FROM `shop_line_lists_items`;";
												$db->exec($sql);
												$arr_value[] = (int) $db->lastInsertId();
											}
											else
											{
												$arr_value[] = (int) $result['id'];
											}
										}
									}
									if($type === 6)
									{
										
										if(empty($list_id)){
											if(!empty($property->list_id)){
												$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `id` = ".$db->quote(trim($property->list_id));
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												if(!empty($result))
												{
													$list_id = (int) $result['id'];
												}
											}
											
											if(empty($list_id)){
												
												// Вытаскиваем id списка
												if(!empty($property->list_caption)){
													$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `caption` = ".$db->quote(trim($property->list_caption));
												}else{
													$sql = "SELECT `id` FROM `shop_tree_lists` WHERE `caption` = ".$db->quote(trim($property->caption));
												}
												
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												
												if(empty($result))
												{
													// Такого списка нет -> 
													if(!empty($property->list_caption)){
														$caption = $db->quote(trim($property->list_caption));
													}else{
														$caption = $db->quote(trim($property->caption));
													}
													
													$data_type = $db->quote('text');
													
													$sql = "INSERT INTO `shop_tree_lists` 
															(`caption`, `data_type`) 
															VALUE ($caption, $data_type);";
													$db->exec($sql);
													$list_id = (int) $db->lastInsertId();
												}
												else
												{
													
													// Такой список есть -> получаем его id
													$list_id = $result['id'];
												}
											}
										}
										
										if($list_id > 0){
											// Цикл по items
													
											foreach($property->values->value as $item)
											{
												$caption = $item->caption;// В $caption содержится вложенность применимости через ;
												$parent_tree_items = 0;
												$level_tree_items = 1;
												
												$primenimost = explode(';', $caption);
												
												foreach($primenimost as $item_tree_value){
													$item_tree_value = trim(str_replace(array('"',"'"), '', $item_tree_value));
													
													if($item_tree_value === ''){
														continue;
													}
													
													$sql = "SELECT `id` FROM `shop_tree_lists_items` WHERE `value` = ". $db->quote($item_tree_value) ." AND `parent` = $parent_tree_items AND `tree_list_id` = $list_id;";
													$result = $db -> query($sql);
													$result = $result -> fetch(PDO::FETCH_ASSOC);
													
													if(empty($result))
													{
														// Увеличиваем у предыдущего количество вложенных элементов
														$sql = "UPDATE `shop_tree_lists_items` SET `count` = `count`+1 WHERE `id` = $parent_tree_items;";
														$db->exec($sql);
														
														// Если в базе нет такого элемента
														$sql = "INSERT INTO `shop_tree_lists_items` 
														(`tree_list_id`, `value`, `count`, `level`, `parent`, `order`) 
														SELECT $list_id, ". $db->quote($item_tree_value) .", 0, $level_tree_items, $parent_tree_items, (MAX(`order`)+1) AS `max` FROM `shop_tree_lists_items`;";
														$db->exec($sql);
														$parent_tree_items = (int) $db->lastInsertId();
														$arr_value[] = $parent_tree_items;
														$level_tree_items++;
														my_log($sql);
														
													}
													else
													{
														$arr_value[] = (int) $result['id'];
														$parent_tree_items = (int) $result['id'];
														$level_tree_items++;
													}
												}
												
											}
										}
										
									}
								}
								
								// добавляем value
								
								switch($type)
								{
									case 1:
										$value = (int) str_replace(array('"',"'"," "), '', $property->value);
										$sql = "INSERT INTO `shop_properties_values_int` 
										(`product_id`, `property_id`, `category_id`, `value`) 
										VALUE ($product_id, $property_id, $category_id, $value);";
										$db->exec($sql);
									break;
									case 2:
										$value = (float) str_replace(array('"',"'"," "), '', $property->value);
										$sql = "INSERT INTO `shop_properties_values_float` 
										(`product_id`, `property_id`, `category_id`, `value`) 
										VALUE ($product_id, $property_id, $category_id, $value);";
										$db->exec($sql);
									break;
									case 3:
										$value = trim(str_replace(array('"',"'"), '', $property->value));
										if($property->caption == 'Артикул'){
											$sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");
											$value = str_replace($sweep,"", $value);
											$value = strtoupper($value);
										}
										if(!empty($value)){
											$value = $db->quote($value);
											
											$sql = "INSERT INTO `shop_properties_values_text` 
											(`product_id`, `property_id`, `category_id`, `value`) 
											VALUE ($product_id, $property_id, $category_id, $value);";
											$db->exec($sql);
										}
									break;
									case 4:
										$value = (int) str_replace(array('"',"'"," "), '', $property->value);
										$sql = "INSERT INTO `shop_properties_values_bool` 
										(`product_id`, `property_id`, `category_id`, `value`) 
										VALUE ($product_id, $property_id, $category_id, $value);";
										$db->exec($sql);
									break;
									case 5:
										if(!empty($arr_value))
										{
											foreach($arr_value as $v)
											{
												$value = (int)$v;
												
												$sql = "SELECT `id` FROM `shop_properties_values_list` WHERE `product_id` = $product_id AND `property_id` = $property_id AND `category_id` = $category_id AND `value` = $value";
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												
												if(empty($result))
												{
													$sql = "INSERT INTO `shop_properties_values_list` 
													(`product_id`, `property_id`, `category_id`, `value`) 
													VALUE ($product_id, $property_id, $category_id, $value);";
													$db->exec($sql);
												}
											}
										}
									break;
									case 6:
										if(!empty($arr_value))
										{
											foreach($arr_value as $v)
											{
												$value = (int)$v;
												
												$sql = "SELECT `id` FROM `shop_properties_values_tree_list` WHERE `product_id` = $product_id AND `property_id` = $property_id AND `category_id` = $category_id AND `value` = $value";
												$result = $db -> query($sql);
												$result = $result -> fetch(PDO::FETCH_ASSOC);
												
												if(empty($result))
												{
													$sql = "INSERT INTO `shop_properties_values_tree_list` 
													(`product_id`, `property_id`, `category_id`, `value`) 
													VALUE ($product_id, $property_id, $category_id, $value);";
													$db->exec($sql);
												}
											}
										}
									break;
								}
							
							}// if($type > 0)
			
							
							
							////////////////////////////////////////////////////////
							
							
						}
					}
					else
					{
						// Неудалось создать новый продукт в базе.
						$error_message .= '<br/>Ошибка:<br/>Неудалось добавить продукт '.$node->caption.'<br/>Для категории id'.$parent_id.'<br/>';
						$product_id = 0;
					}
					
					
									
					// Если мы еще не добавляли информацию о наличии о данном товаре
					if(in_array($product_id, $arr_id_products_update_prices) === false){
						
						
						// Добавляем информацию о наличии товара
						// Если указан склад
						if($storages_id > 0)
						{
							
							// Получаем данные склада
							$sql = "SELECT * FROM `shop_storages` WHERE `id` = $storages_id;";
							$result = $db->query($sql);
							$result = $result->fetch(PDO::FETCH_ASSOC);
							if(!empty($result))
							{
								if((int)$result['interface_type'] === 1){
									
									// Является ли пользователь кладовщиком склада или запрос пришел от крона
									if( empty($_GET['file'])){
										$is_klad = false;
										$id_users = json_decode($result['users']);
										
										foreach($id_users as $id_user)
										{
											if((int)$id_user === (int)$user_id)
											{
												$is_klad = true;
												break;
											}
										}
									}else{
										$is_klad = true;// Значит крон
									}
									
									if($is_klad)
									{
										
										// Цикл по 'suggestions' продукта.
										if(!empty($node->suggestions)){
											foreach($node->suggestions->suggestion as $suggestion)
											{
												$price = (float)$suggestion->price;
												$price_crossed_out = (float)$suggestion->price_crossed_out;
												$price_purchase = (float)$suggestion->price_purchase;
												
												if(empty($suggestion->arrival_time)){
													$arrival_time = time();
												}else{
													$arrival_time = (int)$suggestion->arrival_time;
												}
												
												
												$exist = (int)$suggestion->exist;
												$reserved = (int)$suggestion->reserved;
												$issued = (int)$suggestion->issued;
												
												if($suggestion_sql != '')
												{
													$suggestion_sql .= ', ';
												}
												$suggestion_sql .= "($storages_id, $product_id, $category_id, $price, $price_crossed_out, $price_purchase, $arrival_time, $exist, $reserved, $issued)";
												
												//////////////////////////////////////////
												
												// Что бы не превысить максимальный размер инсерта
												$suggestion_sql_cnt++;
												if($suggestion_sql_cnt >= 10000){
													$suggestion_sql_array[] = $suggestion_sql;
													$suggestion_sql = '';
													$suggestion_sql_cnt = 0;
												}
												
												//////////////////////////////////////////
												
												if($products_shop_storages_data_sql_id_delete != '')
												{
													$products_shop_storages_data_sql_id_delete .= ', ';
												}
												$products_shop_storages_data_sql_id_delete .= "$product_id";
												
												
												// Информация что обновили цену у этого товара что бы не было дублей
												$arr_id_products_update_prices[] = $product_id;
												
											}
										}
										
									}
									else
									{
										$error_message .= '<br/>Ошибка:<br/>Вы не являетесь кладовщиком склада. ID склада '. $storages_id;
									}
								}
								else
								{
									$error_message .= '<br/>Ошибка:<br/>Недопустимый тип склада. ID склада '. $storages_id;
								}
							}
							else
							{
								$error_message .= '<br/>Ошибка:<br/>Склад не найден. ID склада '. $storages_id;
							}
						}
					
					}
					
				}
			}
			
		}
	}


	
	
	
	
	
	
	
	
	
	
	
	
	
	
	while($reader->read())
	{
		if($reader->name === "categories" && $reader->nodeType == XMLReader::ELEMENT)
		{
			$res = readerCategories($parent_id, $level, $url);
		}
	}
	
	
	
	

	
	
	
/*****************************************************************************************/
// Добавляем информацию о наличии
	
	if($error_message === '')
	{
		
		if( ((int) $clear_table) === 1)
		{
			// Удаляем складскую и нформацию по складу
			clear_table(1);
		}else{
			// удаляем старое наличие
			if($storages_id > 0 && $products_shop_storages_data_sql_id_delete != '')
			{
				try
				{
					$sql = "DELETE FROM `shop_storages_data` WHERE `product_id` IN($products_shop_storages_data_sql_id_delete) AND `storage_id` = $storages_id;";
					$db->exec($sql);
					//var_dump($sql);
				}
				catch(PDOException $e)
				{
					$db->rollback();// Отменяем транзакцию
					$error_message .= '<br/>Каталоги добавлены успешно.<br/>Однако не удалось удалить старое наличие.<br/>EXCEPTION:<br/>'. $e .'<br/><br/><br/>';
				}
				
			}
		}
		
		
		//////////////////////////////
		
		if($suggestion_sql != ''){
			$suggestion_sql_array[] = $suggestion_sql;
			$suggestion_sql = '';
			$suggestion_sql_cnt = 0;
		}
		
		//////////////////////////////

		
		// Добавляем продукты в наличии
		if($storages_id > 0 && !empty($suggestion_sql_array))
		{
			foreach($suggestion_sql_array as $suggestion_sql){
				try
				{
					$table = 'shop_storages_data';
					
					$sql = "INSERT INTO `$table` 
								(`storage_id`, `product_id`, `category_id`, `price`, `price_crossed_out`, `price_purchase`, `arrival_time`, `exist`, `reserved`, `issued`) 
								VALUE $suggestion_sql;";
								$db->exec($sql);
				}
				catch(PDOException $e)
				{
					$db->rollback();// Отменяем транзакцию
					$error_message .= '<br/>Каталоги добавлены успешно.<br/>Однако не удалось добавить товары в наличии.<br/>EXCEPTION:<br/>'. $e .'<br/><br/><br/>';
				}
			}
			$db->commit();// Всё прошло удачно, завершаем транзакцию.
		}
		else
		{
			$db->commit();// Всё прошло удачно, завершаем транзакцию.
		}
		
	}
	else
	{
		$db->rollback();
	}
	
}
catch(PDOException $e)
{
	$db->rollback();// Отменяем транзакцию
	$error_message .= '<br/>EXCEPTION:<br/>'. $e .'<br/><br/><br/>';
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$db = null;

$startTime = date_create($start);
$endTime   = date_create();
$diff = date_diff($endTime, $startTime);
$time = 'Прошло: '. $diff->format('%H') .' ч. '. $diff->format('%i') .' м. '. $diff->format('%s') .' с.';

$info = array(
	'category_all' => $info_cnt_category_all,
	'category_new' => $info_cnt_category_new,
	'products_all' => $info_cnt_products_all,
	'products_new' => $info_cnt_products_new,
	'time' => $time
);

$f = fopen($_SERVER["DOCUMENT_ROOT"] .'/cp/content/shop/data_transfer/ajax/log_reader.txt', 'w');
fwrite($f, "Запуск: ". $start ."\n");
fwrite($f, "Запрос: ". json_encode($_GET) ."\n");
fwrite($f, "Время выполнения: ". $time ."\n");
fwrite($f, "Категорий в файле: ". $info_cnt_category_all ."\n");
fwrite($f, "Новые категории: ". $info_cnt_category_new ."\n");
fwrite($f, "Товаров в файле: ". $info_cnt_products_all ."\n");
fwrite($f, "Новые товары: ". $info_cnt_products_new ."\n");
fwrite($f, "Ошибки: ". $error_message ."\n\n--------------------------------------------------\n\n");
fclose($f);

//exit($error_message);
$result = array('error_message' => $error_message, 'info' => $info);
exit(json_encode($result, JSON_UNESCAPED_UNICODE));
?>