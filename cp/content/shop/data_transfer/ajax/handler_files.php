<?php
// скрипт загружает прикрепленные файлы во временную папку

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

// Получим расширение файла
function getex($filename){
	$s = explode(".", $filename);
	return end($s);
}

function d($d){
	ob_start();
	echo '<pre>';	
	var_dump($d); // выведем на экран массив полученных вайлов
	echo '</pre>';
	$req = ob_get_contents();
	ob_end_clean();
	echo json_encode($req); // вернем полученное в ответе
	exit;
}

if(isset($_FILES['myfile'])){
	
	//Список разрешенных файлов
	$whitelist = array('xml');// Разрешенные расширения файлов
	$msg = '';
	$folder = $_SERVER['DOCUMENT_ROOT'] .'/'. $DP_Config->backend_dir .'/tmp/';//директория в которую будет загружен файл
	$bytes = 1073741824;// 1ГБ - максимальный размер разрешенный для загрузки файлов
	$error = true;

	//Проверяем разрешение файла
	foreach($whitelist as  $item){
		if(preg_match("/$item\$/i",$_FILES['myfile']['name'])){
			$error = false;
		}
    }
	
    //если нет ошибок, грузим файл
	if(!$error){
        // Был ли файл загружен при помощи HTTP POST
		if(is_uploaded_file($_FILES['myfile']["tmp_name"])){
			
			// Проверяем размер файла
			if($_FILES['myfile']["size"] == 0 OR $_FILES['myfile']["size"] > $bytes){// 1ГБ
				// Ошибка размера файла
				$msg = 'Файл не должен привышать 1ГБ';
			}else{
				// Все впорядке - загружаем файл
				$name = 'catalogue_import.xml';// имя загруженого файла

				if(move_uploaded_file($_FILES['myfile']['tmp_name'], $folder . $name)){
					$file = $name;
					$msg = '';
				}else{
					$msg = 'Во время загрузки файла произошла ошибка. Путь = ' . $folder . $name . 'tmp = '. $_FILES['myfile']['tmp_name'];
				}
			}
		}else{
			$msg = 'Файл не загружен';
		}
	}else{
		$msg = 'Вы загружаете запрещенный тип файла. Разрешенные форматы: xml';
	}
	$file_name = trim(htmlspecialchars(strip_tags($_FILES['myfile']['name'])));
	$data = array('file'=>$name, 'name'=>$file_name, 'msg'=>$msg);
	exit(json_encode($data));
}else{
	exit("ERROR");
}
?>