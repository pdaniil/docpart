<?php
/**
 * Скрипт шага 1 "Предварительное создание или очистка временного каталога"
*/

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;



$uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/pack_setup/";//Очищаемый каталог
clear_dir($uploaddir, true);//Функция очистки каталога (true - очистить, а сам каталог оставить)




//Функция очистки каталога ($clear_only: true - только очистить, false - удалить и сам каталог)
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
?>
