<?php
header('Content-Type: application/json;charset=utf-8;');
/* Различные операции над таблицой синонимов */


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
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");

$ansver = array('status'=>false);

$request_object = json_decode($_POST['request_object'], true);


switch($request_object['action'])
{
	case 'get_manufacturers':
		$manufacturers = array();
		$sql = "SELECT * FROM  `shop_docpart_manufacturers` ORDER BY `name`;";
		$query = $db_link->prepare($sql);
		$query->execute();
		while($rov = $query->fetch() )
		{
			$manufacturers[] = array('id'=>$rov['id'], 'name'=>$rov['name']);
		}
		$ansver['manufacturers'] = $manufacturers;
		break;
	case 'get_synonyms':
		$id = (int)$request_object['id'];
		$manufacturers_synonyms = array();
		$sql = "SELECT * FROM  `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = ? ORDER BY `synonym`;";
		$query = $db_link->prepare($sql);
		$query->execute( array($id) );
		while($rov = $query->fetch() )
		{
			$manufacturers_synonyms[] = array('id'=>$rov['id'], 'manufacturer_id'=>$rov['manufacturer_id'], 'synonym'=>$rov['synonym']);
		}
		$ansver['synonyms'] = $manufacturers_synonyms;
		break;
	case 'add_synonym':
		$id = (int)$request_object['id'];
		$name = mb_strtoupper(trim(urldecode($request_object['name'])), 'UTF-8');
		if(!empty($name))
		{
			$sql = "INSERT INTO `shop_docpart_manufacturers_synonyms` (`manufacturer_id`, `synonym`) VALUES (?,?);";
			if( $db_link->prepare($sql)->execute( array($id, $name) ) )
			{
				$ansver['status'] = true;
			}
		}
		break;
	case 'add_manufacturer':
		$name = mb_strtoupper(trim(urldecode($request_object['name'])), 'UTF-8');
		if(!empty($name))
		{
			$sql = "INSERT INTO `shop_docpart_manufacturers` (`name`) VALUES (?);";
			if($db_link->prepare($sql)->execute( array($name) ))
			{
				$manufacturers = array();
				$sql = "SELECT * FROM  `shop_docpart_manufacturers` ORDER BY `name`;";
				$query = $db_link->prepare($sql);
				$query->execute();
				while($rov = $query->fetch() )
				{
					$manufacturers[] = array('id'=>$rov['id'], 'name'=>$rov['name']);
				}
				$ansver['status'] = true;
				$ansver['manufacturers'] = $manufacturers;
			}
		}
		break;
	case 'save_manufacturer':
		$id = (int)$request_object['id'];
		$name = mb_strtoupper(trim(urldecode($request_object['name'])), 'UTF-8');
		if(!empty($name))
		{
			$sql = "UPDATE `shop_docpart_manufacturers` SET `name` = ? WHERE `id` = ?;";
			if($db_link->prepare($sql)->execute( array($name, $id) ))
			{
				$ansver['status'] = true;
			}
		}
		break;
	case 'save_synonym':
		$id = (int)$request_object['id'];
		$name = mb_strtoupper(trim(urldecode($request_object['name'])), 'UTF-8');
		if(!empty($name))
		{
			$sql = "UPDATE `shop_docpart_manufacturers_synonyms` SET `synonym` = ? WHERE `id` = ?;";
			if($db_link->prepare($sql)->execute( array($name, $id) ))
			{
				$ansver['status'] = true;
			}
		}
		break;
	case 'del_manufacturer':
		$id = (int)$request_object['id'];
		$sql = "DELETE FROM `shop_docpart_manufacturers` WHERE `id` = ?;";
		if($db_link->prepare($sql)->execute( array($id) ))
		{
			$sql = "DELETE FROM `shop_docpart_manufacturers_synonyms` WHERE `manufacturer_id` = $id;";
			$db_link->prepare($sql)->execute( array($id) );
			$ansver['status'] = true;
		}
		break;
	case 'del_synonym':
		$id = (int)$request_object['id'];
		$sql = "DELETE FROM `shop_docpart_manufacturers_synonyms` WHERE `id` = $id;";
		if($db_link->prepare($sql)->execute( array($id) ))
		{
			$ansver['status'] = true;
		}
		break;
}

exit(json_encode($ansver));
?>