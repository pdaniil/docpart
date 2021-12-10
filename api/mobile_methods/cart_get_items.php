<?php
//Скрипт для метода запроса позиций корзины
defined('DOCPART_MOBILE_API') or die('No access');


//Получаем исходные данные
$params = $request["params"];
$login = $params["login"];
$session = $params["session"];

//Сначала проверяем наличие такого пользователя
$user_query = $db_link->prepare('SELECT `user_id` FROM `users` WHERE `main_field` = ?;');
$user_query->execute( array($login) );
$user_record = $user_query->fetch();
if( $user_record == false )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "User not found";
	exit(json_encode($answer));
}

$user_id = $user_record["user_id"];

//Теперь проверяем наличие сессии
$session_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `user_id` = ? AND `session` = ?;');
$session_query->execute( array($user_id, $session) );
if( $session_query->fetchColumn() > 0 )
{
	//Сессия есть - получаем корзину
	$cart_records = array();//Список записей корзины для этого пользователя
	
	$cart_records_query = $db_link->prepare('SELECT `id` FROM `shop_carts` WHERE `user_id` = ?;');
	$cart_records_query->execute( array($user_id) );
    while($cart_record = $cart_records_query->fetch() )
    {
        array_push($cart_records, $cart_record["id"]);
    }
	
	$cart_items = array();
	
	//Формируем объекты содержимого корзины
    for($i=0; $i < count($cart_records); $i++)
    {
        $cart_object = array();
		
		$cart_record_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
		$cart_record_query->execute( array($cart_records[$i]) );
        $cart_record = $cart_record_query->fetch();
        
        //Заполняем поля, которые не зависят от типа продукта:
        $cart_object["id"] = $cart_records[$i];
        $cart_object["price"] = $cart_record["price"];
        $cart_object["product_type"] = $cart_record["product_type"];
        $cart_object["count_need"] = $cart_record["count_need"];
        $cart_object["price_sum"] = $cart_object["count_need"]*$cart_object["price"];
		$cart_object["checked_for_order"] = $cart_record["checked_for_order"];
        
        //Заполняем поля объекта корзины в зависимости от типа продукта (1 - каталожный, 2 - docpart)
        switch($cart_record["product_type"])
        {
            case 1:
                $cart_object["product_id"] = $cart_record["product_id"];
                $product_id = $cart_record["product_id"];
            
                //Получаем каталожную информацию по продукту
                $product_query = $db_link->prepare('SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = ?;');
				$product_query->execute( array($product_id) );
                $product_record = $product_query->fetch();
                $cart_object["name"] = $product_record["caption"];
                break;
            case 2:
				$cart_object["name"] = $cart_record["t2_manufacturer"]." ".$cart_record["t2_article"]." ".$cart_record["t2_name"];
                break;
        }
		array_push($cart_items, $cart_object);
    }//for($i) - формируем объект корзины
	
	
	
	$answer = array();
	$answer["status"] = true;
	$answer["message"] = "Cart items";
	$answer["cart_items"] = $cart_items;
	exit(json_encode($answer));
}
else
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No session";
	exit(json_encode($answer));
}
?>