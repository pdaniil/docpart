<?php
	defined('_ASTEXE_') or die('No access');
	if (file_exists($_GET["name"])) 
	{
		$name = $_GET["name"];
		//Конфигурация CMS
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
		
		$xml = simplexml_load_file($name);
		print_r("Лог процесса: ");
		echo "<br>";
		echo "Версия ИБ: ".$xml->db_version;
		
		if ($xml->db_version == '1.0') //Новая версия платформы, просто идём по файлу и загружаем данные
		{	
			//Начало работы с файлом config_group
			require_once($_SERVER["DOCUMENT_ROOT"]."/cp/content/control/dp_configeditor.php");
			$config_parameters_query = $db_link->prepare("SELECT * FROM `config_items`;");
			$config_parameters_query->execute();
			while( $item = $config_parameters_query->fetch() )
			{
				
				//С некоторыми типами параметров необходимо работать особым образом:
				if ($xml->config->{$item["name"]} == '')
				{
					continue;
				}
				
				if($item["type"]=="password")//Для паролей: если передан пустой - оставляем как есть
				{
					if($xml->config->{$item["name"]} != "") DP_ConfigEditor::setParameter($item["name"], $xml->config->{$item["name"]});
				}
				else if($item["type"]=="checkbox")//Для чекбоксов приводим к булевому типу
				{
					DP_ConfigEditor::setParameter($item["name"], filter_var($xml->config->{$item["name"]}, FILTER_VALIDATE_BOOLEAN));
				}
				else//Для все остальных типов - как есть
				{
					DP_ConfigEditor::setParameter($item["name"], $xml->config->{$item["name"]});
				}
			}
			
			//Конец работы с файлом config
			
			
			//Начало работы с БД
			//Все изменения делаем через транзакции
			
		}
		unlink($name);
	} 
	else 
	{
		exit('Не удалось открыть файл.');
	}
	
?>