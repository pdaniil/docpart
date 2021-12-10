<?php
/**
 * Третий шаг общего алгоритма сессии загрузки файлов "Извлечение архивов"
 * 
 * Скрипт пробегает по всем файлам во временно каталоге и распаковывает найденные архивы. Распакованные архивы удаляются
*/
header('Content-Type: application/json;charset=utf-8;');
//-------Подключаем библиотеку для работы с архивами---------
require_once($_SERVER["DOCUMENT_ROOT"]."/lib/PclZip/pclzip.lib.php");
//-----------------------------------------------------------

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Временный каталог
$work_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;



$dh = opendir($work_dir);//Открываем временный каталог
$packs_count = 0;//Всего найдено архивов
$packs_successfully_extracted = 0;//Архивов успешно распаковано
$packs_error = 0;//Архивов не распаковано (ошибки)
//Пробегаем по содержимому временного каталога
while (false !== ($obj = readdir($dh)))
{
	if($obj=='.' || $obj=='..') 
	{
		continue;
	}
	else
	{
		if(strripos($obj, ".zip") != false)//Если содержит .zip
		{
			if(strlen($obj)-strlen(".zip") == strripos($obj, ".zip"))//и при этом .zip - конец имени файла, то это zip-архив
			{
			    $packs_count++;//Счетчик архивов
			    
				$arhive_name_zip = $work_dir."/".$obj;//Полный путь к архиву, в т.ч. имя файла 
				$path_dir_zip = $work_dir."/";//Куда распаковывать - в сам временный каталог
				
				$archive = new PclZip($arhive_name_zip);//Объект PclZip
                $result = $archive->extract(PCLZIP_OPT_PATH, $path_dir_zip);//Распаковка архива
                if($result == 0) 
                {
                    //Обработать ошибку
                    $packs_error++;
                }
                else
                {
                    //Обработать успех
                    $packs_successfully_extracted++;
                }
                unlink($arhive_name_zip);//Удаляем архив
			}
		}
		if(strripos($obj, ".rar") != false)//Если содержит .rar
		{
			if(strlen($obj)-strlen(".rar") == strripos($obj, ".rar"))//и при этом .rar - конец имени файла, то это rar-архив
			{
			    $packs_count++;//Счетчик архивов
			    
				$arhive_name_rar = $work_dir."/".$obj;//Полный путь к архиву, в т.ч. имя файла 
				$path_dir_rar = $work_dir."/";//Куда распаковывать - в сам временный каталог
				
				$rar_file = rar_open($arhive_name_rar);
                $list = $rar_file->getEntries();
                foreach($list as $file) 
                {
                    $result = $file->extract($work_dir);//Извлекаем этот файл
                    if($result == 0) 
                    {
                        //Обработать ошибку
                        $packs_error++;
                    }
                    else
                    {
                        //Обработать успех
                        $packs_successfully_extracted++;
                    }
                }
                rar_close($rar_file);

                unlink($arhive_name_rar);//Удаляем архив
			}
		}
	}//else 1
}//~while 1
closedir($dh);//Закрываем каталог



$answer = array();//Объект ответа
$answer["packs_count"] = $packs_count;
$answer["packs_error"] = $packs_error;
$answer["packs_successfully_extracted"] = $packs_successfully_extracted;

exit(json_encode($answer));
?>