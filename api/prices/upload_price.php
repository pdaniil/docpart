<?php
	require_once($_SERVER["DOCUMENT_ROOT"].'/config.php');	
	$DP_Config = new DP_Config();	
	
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
	
	function clear_dir($dir, $clear_only) 
	{
		foreach(glob($dir . '/*') as $file) 
		{
			if(is_dir($file))
			{
				clear_dir($file, false);
			}
			else
			{
				$file_name = explode("/", $file);
				$file_name = $file_name[ count($file_name) - 1 ];
				if( $file_name != "index.html" )
				{
					unlink($file);
				}
			}
		}
		if(!$clear_only)
		{
			rmdir($dir);
		}
	}
	
	function import_csv_to_db($clean_before, $price_id)
	{	
		global $DP_Config;
		$url = $DP_Config->domain_path.$DP_Config->backend_dir.'/content/shop/prices_upload/ajax_5_import_csv_to_db.php?price_id='.$price_id.'&initiator=js&clean_before='.$clean_before;
		
		 if( $curl = curl_init() ) {
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($curl);
			curl_close($curl);
			
			$awnser = json_decode($result);
			if ($awnser->result == 1)
			{
				$url = $DP_Config->domain_path.$DP_Config->backend_dir.'/content/shop/prices_upload/ajax_6_complete_session.php?price_id='.$price_id;
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
				$result = curl_exec($curl);
				curl_close($curl);
				
				$awnser = json_decode($result);
				
				if ($awnser->result != 1)
				{
					$awnser = [
						'status' => false,
						'data' => 'Файл был загружен, но возникла ошибка при обновлении времени последнего изменения.'
					];
					exit(json_encode($awnser));
				}
			}
			else
			{
				$awnser = [
					'status' => false,
					'data' => 'Ошибка загрузки прайса в базу данных.'
				];
				exit(json_encode($awnser));
			}
		  }
	
	}
	///////////////////////////////////////////////////////////
	
	$SQL_select = "EXPLAIN SELECT * FROM `shop_docpart_prices` WHERE `id` =  ?;";
	$query = $db_link->prepare($SQL_select);
	$query->execute( array( $_POST["id"] ) );
	
	$result = $query->fetch();
	
	if ($result["rows"] > 0)
	{
		if ($_POST["tech_key"] != $DP_Config->tech_key)
		{
			$awnser = [
				'status' => false,
				'data' => 'Не правильно указан ключ.'
			];
			exit(json_encode($awnser));
		}
		else
		{
			//Проверяем наличие временного каталога для загрузки. ПРИ НЕОБХОДИМОСТИ 0 СОЗДАЕМ
			$treelax_tmp_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;//Путь к каталогу для загрузки файлов прайс-листов
			if(!is_dir($treelax_tmp_dir)) 
			{
				if(!mkdir($treelax_tmp_dir))
				{
					$awnser = [
						'status' => false,
						'data' => 'Не удалось создать временный каталог.'
					];
					exit(json_encode($awnser));
				}
			}
			else//Каталог есть - предварительно очищаем его
			{
				clear_dir($treelax_tmp_dir, true);//Функция очистки каталога (true - очистить, а сам каталог оставить)
			}
			$name = $treelax_tmp_dir.'/price.txt';
			move_uploaded_file($_FILES["document"]["tmp_name"], $name);
			import_csv_to_db(true, 1);
		}
	}
	else
	{
		$awnser = [
			'status' => false,
			'data' => 'Прайс лист с данным id не был обнаружен.'
		];
		exit(json_encode($awnser));
	}
	
	
	$awnser = [
			'status' => true,
			'data' => 'Файл успешно загружен.'
		];
	exit(json_encode($awnser));
?>