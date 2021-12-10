<?php
/**
 * Серверный скрипт для добавления товара в корзину
 * 
 * 
 * Если покупатель авторизован, то добавляем товар Базу данных
 * 
 * Если покупатель не авторизован - добавляем товар в куки
 * 
*/
header('Content-Type: application/json;charset=utf-8;');
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();
if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕавторизованного пользователя
	$session_record = DP_User::getUserSession();
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}



//Получаем массив продуктов для добавления в корзину
$product_objects = json_decode($_POST["product_objects"], true);

if($product_objects == NULL)
{
    $result = array();
    $result["status"] = false;
    $result["code"] = "incorrect_data";
    $result["message"] = "Некорретные данные от поставщика";
    exit(json_encode($result));
}

//Список записей корзины
$products_in_cart = array();

$no_error = true;//Флаг - выполнено без ошибок

//Добавляем записи:
for($i=0; $i < count($product_objects); $i++)
{
    $product_object = $product_objects[$i];
    $product_type = $product_object["product_type"];
    
    //В зависимости от типа продукта
    if( $product_type == 1 )//Каталожный
    {
        $product_id = (int)$product_object["product_id"];
        $office_id = (int)$product_object["office_id"];
        $storage_id = (int)$product_object["storage_id"];
        $storage_record_id = $product_object["storage_record_id"];
        $price = $product_object["price"];
        
		if(!empty($product_object["count_need"]))
		{
			$count_need = (int)$product_object["count_need"];
			if( $count_need <= 0 )
			{
				exit;
			}
		}
		else
		{
			$count_need = 1;//Изначально всегда добавляем одну запись
		}
		
        $time = time();//Время добавления записи
        
        
		//Проверяем хеш, защищающий от подмены данных злоумышлненниками через JavaScript
		$check_hash = md5($product_id.$office_id.$storage_id.$storage_record_id.$price.$DP_Config->tech_key);
		if( $check_hash != $product_object["check_hash"] )
		{
			$result = array();
			$result["status"] = false;
			$result["code"] = "35";
			$result["message"] = "Ошибка 35.1. Обратитесь к разработчику";
			exit(json_encode($result));
		}
		
        
        //Проверяем наличие такого же товара в корзине
        $check_already_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_carts` WHERE `product_id`=? AND `price`= ? AND `user_id`=? AND `session_id` = ?;');
		$check_already_query->execute( array($product_id, $price, $user_id, $session_id) );
		if( $check_already_query->fetchColumn() > 0)
		{
			$result = array();
			$result["status"] = false;
			$result["code"] = "already";
			exit(json_encode($result));
		}

        
		
        
        if( $db_link->prepare('INSERT INTO `shop_carts` (`product_type`, `product_id`, `price`, `count_need`, `user_id`, `time`, `session_id`) VALUES (?, ?, ?, ?, ?, ?, ?);')->execute( array($product_type, $product_id, $price, $count_need, $user_id, $time, $session_id) ) )
        {
            //1. Получаем ID последней всталенной записи
            $cart_record_id = $db_link->lastInsertId();

            //2. Добавляем в список записей корзины
            array_push($products_in_cart, $cart_record_id);
            
            //3. Резервируем товар на складе (И УЗНАЕМ ЗАКУПОЧНУЮ ЦЕНУ СКЛАДА)
            $price_purchase = 0;//Цена закупа склада

			
			//3.1.Резервируем товар на складе
			$db_link->prepare('UPDATE `shop_storages_data` SET `exist` = (`exist`-'.$count_need.'), `reserved` = (`reserved`+'.$count_need.') WHERE `id`= ?;')->execute( array($storage_record_id) );
			
			
			//Узнаем отпускную цену склада.
			//Подстрока для умножение цены на курс валюты склада
			$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";
			$price_purchase_query = $db_link->prepare('SELECT `price`*'.$SQL_currency_rate.' AS `price` FROM `shop_storages_data` WHERE `id`= ?;');
			$price_purchase_query->execute( array($storage_record_id) );
			$price_purchase_record = $price_purchase_query->fetch();
			$price_purchase = $price_purchase_record["price"];
            
			
			
            //3.2 Вносим детализированную запись корзины
            if($db_link->prepare('INSERT INTO `shop_carts_details` (`cart_record_id`, `office_id`, `storage_id`, `storage_record_id`, `count_reserved`, `price_purchase`) VALUES (?,?,?,?,?,?);')->execute( array($cart_record_id, $office_id, $storage_id, $storage_record_id, $count_need, $price_purchase) ) != true)
            {
				$db_link->prepare('DELETE FROM `shop_carts` WHERE `id` = ?;')->execute( array($cart_record_id) );
                $no_error = false;
            }
        }
        else
        {
            $no_error = false;
        }
    }//if( $product_type == 1 )//Каталожный
    else if( $product_type == 2 )//Автозапчасть Docpart
    {
        // **************************************************************************************************************
        // ***************************************************************************************************************
        // ****************************************************************************************************************
        // ***************************************************************************************************************
        // **************************************************************************************************************
        //Получаем поля продукта
        $t2_manufacturer = $product_object["manufacturer"];
        $t2_article = $product_object["article"];
        $t2_article_show = $product_object["article_show"];
        $t2_name = $product_object["name"];
        $t2_exist = $product_object["exist"];
        $t2_time_to_exe = $product_object["time_to_exe"];
        $t2_time_to_exe_guaranteed = $product_object["time_to_exe_guaranteed"];
        $t2_storage = $product_object["storage"].'';
        $t2_min_order = $product_object["min_order"];
        $t2_probability = $product_object["probability"];
        $price = $product_object["price"];
        $t2_price_purchase = $product_object["price_purchase"];
        $t2_markup = $product_object["markup"];
        $t2_office_id = $product_object["office_id"];
        $t2_storage_id = $product_object["storage_id"];
		$t2_json_params = $product_object["json_params"].'';
        
		if(!empty($product_object["count_need"])){
			$count_need = (int)$product_object["count_need"];
		}else{
			$count_need = 1;//Изначально всегда добавляем одну запись
		}
		
        $time = time();//Время добавления записи
        
		
		
		//Проверяем хеш, защищающий от подмены данных злоумышлненниками через JavaScript
		$check_hash = md5($t2_manufacturer.$t2_article.$t2_article_show.$t2_name.$t2_exist.$price.$t2_time_to_exe.$t2_time_to_exe_guaranteed.$t2_storage.$t2_min_order.$t2_probability.$t2_office_id.$t2_storage_id.$t2_price_purchase.$t2_markup.$t2_json_params."2".$DP_Config->tech_key);
		if( $check_hash != $product_object["check_hash"] )
		{
			$result = array();
			$result["status"] = false;
			$result["code"] = "35";
			$result["message"] = "Ошибка 35.2. Обратитесь к разработчику";
			exit(json_encode($result));
		}
        
        
        //Проверяем наличие такого же товара в корзине
        $check_already_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_carts` WHERE 
                `product_type`=2 AND 
                `user_id`=? AND 
				`session_id`=? AND 
                `t2_manufacturer` = ? AND 
                `t2_article` = ? AND 
                `t2_exist` = ? AND 
                `t2_time_to_exe` = ? AND 
                `t2_time_to_exe_guaranteed` = ? AND 
                `t2_probability` = ? AND 
                `t2_office_id` = ? AND 
                `t2_storage_id` = ? AND 
                CAST(`price` AS DECIMAL) = CAST(? AS DECIMAL);');
		$check_already_query->execute( array($user_id, $session_id, $t2_manufacturer, $t2_article, $t2_exist, $t2_time_to_exe, $t2_time_to_exe_guaranteed, $t2_probability, $t2_office_id, $t2_storage_id, $price) );
		if( $check_already_query->fetchColumn() > 0 )
		{
			$result = array();
			$result["status"] = false;
			$result["code"] = "already";
			exit(json_encode($result));
		}
        
        
        
        //Убедились, что такого товара еще нет в корзине - формируем запрос на добавление
        $SQL_INSERT = "INSERT INTO `shop_carts` (
            `product_type`,
            `price`,
            `count_need`,
            `time`,
            `user_id`,
			`session_id`,
            `t2_manufacturer`,
            `t2_article`,
            `t2_article_show`,
            `t2_name`,
            `t2_exist`,
            `t2_time_to_exe`,
            `t2_time_to_exe_guaranteed`,
            `t2_storage`,
            `t2_min_order`,
            `t2_probability`,
            `t2_markup`,
            `t2_price_purchase`,
            `t2_office_id`,
            `t2_storage_id`,
            `t2_product_json`,
			`t2_json_params`
                ) VALUES (";
				

		$binding_values = array(2,$price,$count_need,$time,$user_id,$session_id,$t2_manufacturer,$t2_article,$t2_article_show,$t2_name,$t2_exist,$t2_time_to_exe,$t2_time_to_exe_guaranteed,(string)$t2_storage,$t2_min_order,$t2_probability,$t2_markup,$t2_price_purchase,$t2_office_id,$t2_storage_id,json_encode($product_object), $t2_json_params );
		
		$SQL_INSERT = $SQL_INSERT.str_repeat('?,', count($binding_values) - 1).'?)';
		
		
		//var_dump($binding_values);
		
        //Добавляем запись в таблицу корзины
        if( $db_link->prepare($SQL_INSERT)->execute( $binding_values ) )
        {
            //1. Получаем ID новой записи
            $inserted_record_id = $db_link->lastInsertId();
            
            //2. Добавляем в список записей корзины
            array_push($products_in_cart, $inserted_record_id);
        }
        else
        {
			//var_dump($db_link->errorInfo());
            $no_error = false;
        }
        // ****************************************************************************************************************
        // ***************************************************************************************************************
        // **************************************************************************************************************
        // ***************************************************************************************************************
        // ****************************************************************************************************************
    }//~else if( $product_type == 2 )//Автозапчасть Docpart
    else
    {
        $result = array();
        $result["status"] = false;
        $result["code"] = "unknown_product_type";
        $result["message"] = "Неизвестный тип продукта";
        exit(json_encode($result));
    }
    
}//for(по каждому объекту)









//Объект ответа
$result = array();



if($no_error)
{
    $result["status"] = true;
}
else
{
    $result["status"] = false;
}


exit(json_encode($result));
?>