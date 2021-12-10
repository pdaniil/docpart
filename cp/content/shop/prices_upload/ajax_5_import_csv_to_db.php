<?php
/**
 * Очередной шаг общего алгоритма загрузки прайс-листа "Загрузка файлов csv во таблицу в Базе данных"
 * 
 * Перед началом данного этапа во временном каталоге остаются только файлы формата csv(txt)
*/
header('Content-Type: application/json;charset=utf-8;');
set_time_limit(600);

function prepareString($string)
{
	$sweep=array("/", "#", "\r\n", "\r", "\n", "\t", "'", '"', "\\");
	$string = str_replace($sweep,"", $string);
	
	return $string;
}





//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;



//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
    $answer["result"] = 0;
    $answer["message"] = "Не ошибка подключния к основной БД";
    exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");




//Временный каталог
$work_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;


//Получаем конфигурацию прайс-листа
$price_id = $_GET["price_id"];

$price_configuration_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices` WHERE `id` = ?;");
$price_configuration_query->execute( array($price_id) );
$price_configuration = $price_configuration_query->fetch();
$strings_to_left = $price_configuration["strings_to_left"];//Сколько строк пропустить
//Составляем список задействованных колонок:
$operational_cols = array();
$all_cols_types_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices_cols_types`");
$all_cols_types_query->execute();
while($col_type = $all_cols_types_query->fetch() )
{
    //Составляем ассоциативый массив: "тип колонки" => "номер колонки в файле". Если такой колонки нет в файле, то значение равно 0
    $operational_cols[$col_type["name"]] = $price_configuration[$col_type["name"]."_col"];
}



//Инициатор обращения (js или cron)
$initiator = $_GET["initiator"];

//Определяем, нужно ли предварительной очищать таблицу
if($initiator == "js")//Если обращение из html страницы - флаг полностью обновить указан в аргументе
{
    $clean_before = false;
    if($_GET["clean_before"] != NULL)$clean_before = true;
}
else//обращение через cron - флаг "полностью обновить" указан в настройках конфигурации
{
    $clean_before = $price_configuration["clean_before"];
}


//Предварительно очищаем таблицу назначения
if($clean_before)
{
    if( $db_link->prepare("DELETE FROM `shop_docpart_prices_data` WHERE `price_id` = ?;")->execute( array($price_id) ) != true)
    {
        $answer = array();
        $answer["result"] = 0;
        $answer["message"] = "Ошибка предварительной очистки таблицы прайс-листа";
        exit(json_encode($answer));
    }
}

$SQL_sub = "";//Подстрока для SQL - cхема расположния колонок в файле



$dh = opendir($work_dir);//Открываем временный каталог
//Пробегаем по содержимому временного каталога
$first_file = true;//Флаг - работаем с первым файлом
while (false !== ($obj = readdir($dh)))
{
	if($obj=='.' || $obj=='..' || $obj=="index.html" ) 
	{
		continue;
	}
	else
	{
		//Открываем исходный файл
		$file = fopen($work_dir."/".$obj, "r");
		
		//Пропускаем требуемое количество строк
		for($i=0; $i < $strings_to_left; $i++)
		{
			$current_record = fgets($file, 4096);
		}
		
		//На время загрузки отключаем индексы
		if( $db_link->prepare("ALTER TABLE `shop_docpart_prices_data` DISABLE KEYS;")->execute() != true)
		{
			$answer = array();
			$answer["result"] = 0;
			$answer["message"] = "SQL ошибка отключения индексов";
			closedir($dh);//Закрываем каталог
			exit(json_encode($answer));
		}
		
		//Готовим запрос
		$SQL = "INSERT INTO `shop_docpart_prices_data` (`price_id`,`manufacturer`,`article`,`article_show`,`name`,`exist`,`price`,`time_to_exe`,`storage`,`min_order`) VALUES (?,?,?,?,?,?,?,?,?,?);";
		$INSERT_GENERAL_QUERY = $db_link->prepare($SQL);
		
		//Читаем файл построчно.
		while (($current_record = fgets($file, 4096)) !== false)
		{	
			$current_record = explode(";", $current_record);
			
			//Получаем данные из строки
			$manufacturer = "";
			if( $operational_cols["manufacturer"] > 0 )
			{
				$manufacturer = prepareString($current_record[$operational_cols["manufacturer"]-1]);
				$manufacturer = trim($manufacturer);
			}
			$article = "";
			$article_show = "";
			if( $operational_cols["article"] > 0 )
			{
				$article = $current_record[$operational_cols["article"]-1];
				$article_show = prepareString($current_record[$operational_cols["article"]-1]);
				
				$sweep = array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");
				$article = str_replace($sweep, "", $article);
				$article = strtoupper($article);
			}
			$name = "";
			if( $operational_cols["name"] > 0 )
			{
				$name = prepareString($current_record[$operational_cols["name"]-1]);
				$name = trim($name);
			}
			$exist = 0;
			if( $operational_cols["exist"] > 0 )
			{
				$exist = (int)$current_record[$operational_cols["exist"]-1];
				$exist = trim($exist);
			}
			$price = 0;
			if( $operational_cols["price"] > 0 )
			{
				$price = (float)$current_record[$operational_cols["price"]-1];
				$price = trim($price);
			}
			$time_to_exe = 0;
			if( $operational_cols["time_to_exe"] > 0 )
			{
				$time_to_exe = (int)$current_record[$operational_cols["time_to_exe"]-1];
				$time_to_exe = trim($time_to_exe);
			}
			$storage = "";
			if( $operational_cols["storage"] > 0 )
			{
				$storage = prepareString($current_record[$operational_cols["storage"]-1]);
				$storage = trim($storage);
			}
			$min_order = 0;
			if( $operational_cols["min_order"] > 0 )
			{
				$min_order = (int)$current_record[$operational_cols["min_order"]-1];
				$min_order = trim($min_order);
			}
			
			//Формируем строку
			$binding_values = array($price_id,$manufacturer,$article,$article_show,$name,$exist,$price,$time_to_exe,$storage,$min_order);
			
			if( $INSERT_GENERAL_QUERY->execute($binding_values) != true)
			{
				$answer = array();
				$answer["result"] = 0;
				$answer["message"] = "SQL ошибка (INSERT)";
				closedir($dh);//Закрываем каталог
				exit(json_encode($answer));
			}
		}
		
		//Снова включаем индексы
		if( $db_link->prepare("ALTER TABLE `shop_docpart_prices_data` ENABLE KEYS;")->execute() != true)
		{
			$answer = array();
			$answer["result"] = 0;
			$answer["message"] = "SQL ошибка отключения индексов";
			closedir($dh);//Закрываем каталог
			exit(json_encode($answer));
		}
		
		fclose($file);//Закрываем файл
		
	    //Удаляем файл csv
	    unlink($work_dir."/".$obj);//Удаляем файл
	}//else 1
}//~while 1
closedir($dh);//Закрываем каталог



$answer = array();
$answer["result"] = 1;
exit(json_encode($answer));
?>