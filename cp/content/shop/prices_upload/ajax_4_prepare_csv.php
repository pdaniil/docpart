<?php
/**
 * Очередной шаг общего алгоритма загрузки прайс-листов "Обработка файлов csv" (удаление кавычек и т.д.)
*/
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;


//Временный каталог
$work_dir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir.$DP_Config->tmp_dir_prices_upload;




$dh = opendir($work_dir);//Открываем временный каталог
//Пробегаем по содержимому временного каталога
while (false !== ($obj = readdir($dh)))
{
    continue;
	if($obj=='.' || $obj=='..') 
	{
		continue;
	}
	else
	{
		if(strripos(strtolower($obj), ".csv") != false || strripos(strtolower($obj), ".txt") != false)
		{
			if(strlen(strtolower($obj))-strlen(".csv") == strripos(strtolower($obj), ".csv") || strlen(strtolower($obj))-strlen(".txt") == strripos(strtolower($obj), ".txt"))
			{
			    //ОБРАБАТЫВАЕМ ФАЙЛ
			    /*
			    //Получаем содержимое файла
			    $file = fopen($work_dir."/".$obj, "r");
			    $file_content = fread($file, filesize($work_dir."/".$obj));
                fclose($file);
			      
			    //Перегоняем в UTF-8
			    $file_content = str_to_utf8($file_content);
			    
			    
			    //Можено выполнить еще действия, например, для очистки строки от кавыче и т.д.
			    
			    
			    //Записываем обратно в файл
			    $file = fopen($work_dir."/".$obj, "w");
			    fwrite($file, $file_content);
                fclose($file)*/
                
                $f = file_get_contents($work_dir."/".$obj);
                $f = iconv("WINDOWS-1251", "UTF-8", $f);
                file_put_contents($work_dir."/".$obj, $f);
			}
			else
			{
			    $answer = array();//Объект ответа
                $answer["result"] = 0;
                $answer["message"] = "Обнаружен не поддерживаемый файл";
                closedir($dh);//Закрываем каталог
                exit(json_encode($answer));
			}
		}
		else
		{
		    $answer = array();//Объект ответа
            $answer["result"] = 0;
            $answer["message"] = "Обнаружен не поддерживаемый файл";
            closedir($dh);//Закрываем каталог
            exit(json_encode($answer));
		}
	}//else 1
}//~while 1
closedir($dh);//Закрываем каталог



$answer = array();//Объект ответа
$answer["result"] = 1;
exit(json_encode($answer));







// Далее определения методов:
// ***************************************************************************************************************
// ---------------------------------------------------- 
//Функция для определения кодировки
if ( !function_exists('mb_detect_encoding') ) 
{
// ---------------------------------------------------------------- 
    function mb_detect_encoding ($string, $enc=null, $ret=null) 
    { 
        static $enclist = array( 
            'UTF-8', 'ASCII', 
            'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5', 
            'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10', 
            'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16', 
            'Windows-1251', 'Windows-1252', 'Windows-1254', 
            );
        
        $result = false; 
        
        foreach ($enclist as $item) 
        { 
            $sample = iconv($item, $item, $string); 
            if (md5($sample) == md5($string)) 
            { 
                if ($ret === NULL) 
                { 
                    $result = $item; 
                } 
                else 
                { 
                    $result = true; 
                } 
                break; 
            }
        }
            
        return $result; 
    } 
// ---------------------------------------------------------------- 
}
// *********************************************************************************
// ------------------------------------------------------ 
//Конвертирование в UTF-8
function str_to_utf8 ($str) 
{
    if (mb_detect_encoding($str, 'UTF-8', true) === false) 
    { 
        //Если кодировка не UTF-8, то перегоняем строку в UTF-8
        
        $str = iconv("CP1251", "UTF-8", $str);
        
        //$str = utf8_encode($str); 
    }

    return $str;
}
// ------------------------------------------------------ 
?>