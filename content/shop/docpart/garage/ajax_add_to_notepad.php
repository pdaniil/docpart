<?php
// Скрипт добавления позиции в блокнот из проценки
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

$status = false;
$message = 'Неизвестная операция';

$garage_id = (int)$_POST['garage'];
$product = json_decode($_POST['product'], true);

if(!empty($user_id)){
	if($garage_id > 0){
		$query = $db_link->prepare('SELECT `id` FROM `shop_docpart_garage` WHERE `user_id` = ? AND `id` = ?;');
		$query->execute( array($user_id, $garage_id) );
		
		if($query->fetchColumn() > 0){
			$flag = true;
		}else{
			$message = 'Переданный автомобиль не найден в гараже';
			$flag = false;
		}
	}else{
		$flag = true;
	}
	
	if($flag == true){
		$brend = trim(htmlspecialchars(strip_tags($product['manufacturer'])));
		$article = trim(htmlspecialchars(strip_tags($product['article'])));
		$name = trim(htmlspecialchars(strip_tags($product['name'])));
		$exist = (int) trim($product['exist']);
		$price = (float) trim($product['price']);
		$comment = 'Добавлено '. date("d-m-Y H:i", time());
		
		if(!empty($article)){
			
			if($db_link->prepare('INSERT INTO `shop_docpart_garage_notepad` (`user_id`, `garage_id`, `brend`, `article`, `name`, `exist`, `price`, `comment`) VALUES (?,?,?,?,?,?,?,?);')->execute( array($user_id, $garage_id, $brend, $article, $name, $exist, $price, $comment) ))
			{
				$message = 'Позиция добавлена в блокнот';
				$status = true;
			}else{
				$message = 'Ошибка добавления позиции';
			}
		}else{
			$message = 'Поле Артикул не должно быть пустым';
		}
	}
}else{
	$message = 'Вы не авторизованы на сайте';
}

$result = array();
$result["status"] = $status;
$result["message"] = $message;
exit(json_encode($result));
?>