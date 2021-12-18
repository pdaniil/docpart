<?php 

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
		$answer["status"] = false;
		$answer["message"] = "No DB connect";
		exit( json_encode($answer) );
	}
	$db_link->query("SET NAMES utf8;");
	
	$xml = simplexml_load_file('tmp_db.xml');
	
	$dictionary_folder = $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/data_transfer/database_transfer/dictionary/'.
	$xml->db_version.'.php';
	
	if (file_exists($dictionary_folder))
		echo "Словарь версии ".$xml->db_version." был успешно подключен.";
	
	require_once($dictionary_folder);
	
	
	$base_file_name = $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/data_transfer/database_transfer/import_classes/base.php';
	require_once($base_file_name);
	$db_link->beginTransaction();
	try 
	{	
		foreach ($xml as $key => $table)
		{
			$file_name = $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/data_transfer/database_transfer/import_classes/'.$key.'.php';
			//echo $file_name;
			if (file_exists($file_name))
			{
				require_once($file_name);
				$obj = new $key($table, $dictionary[$key]);
				$obj->putDataIntoTable();
				$obj->destroy();
			}
		}	
	}
	catch (PDOException $e)
	{
		$db_link->rollBack();
		echo "<br>".$e->getMessage();
		exit();
	}
	catch (Exception $e)
	{
		$db_link->rollBack();
		echo "<br>".$e->getMessage();
		exit();
	}
	$db_link->commit();
	echo "<br>Успешно";

?>