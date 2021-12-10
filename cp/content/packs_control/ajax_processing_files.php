<?php
/**
 * Скрипт для копирования файлов пакета из временного каталога в места назначения
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

//ОБЪЕКТ РЕЗУЛЬТАТА
$ResultMessage = new ResultMessage;


//0. ПОДКЛЮЧЕНИЕ К БД
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $ResultMessage->result_code = 1;
    $ResultMessage->message = "Нет соединения с БД";
	exit(json_encode($ResultMessage));
}
$db_link->query("SET NAMES utf8;");


// -------------------------------------------------------------------------------------------


//1. ПРОВЕРКА ПРАВ НА ЗАПУСК СКРИПТА
$user_id = 0;
$check_authentication_query = $db_link->prepare("SELECT * FROM `sessions` WHERE `session`= ? AND `type` = ?;");
$check_authentication_query->execute( array($_COOKIE["admin_session"], 1) );
$session_record = $check_authentication_query->fetch();
if( $session_record == false )
{
    exit("Forbidden");
}
$user_id = $session_record["user_id"];

// - проверка пройдена


//2. ПРОВЕРКА НАЛИЧИЯ УЧЕТНОЙ ЗАПИСИ ПАКЕТА
if(empty($_POST["pack_id"]))
{
    $ResultMessage->result_code = 2;
    $ResultMessage->message = "Не получен ID пакета";
    exit(json_encode($ResultMessage));
}

$pack_query = $db_link->prepare("SELECT * FROM `packs` WHERE `id` = ?;");
$pack_query->execute( array($_POST["pack_id"]) );
$pack_record = $pack_query->fetch();
if( $pack_record == false )
{
    $ResultMessage->result_code = 3;
    $ResultMessage->message = "Не найдена учетная запись пакета";
    exit(json_encode($ResultMessage));
}


//3. ДИРЕКТОРИЯ ВРЕМЕННОГО КАТАЛОГА ПАКЕТА
$uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/pack_setup/";//Директория для распаковки (где и сам архив)


//4. ПОЛУЧЕНИЕ ОБЪЕКТА ОПИСАНИЯ ПАКЕТА
$pack_info_ob = json_decode($pack_record["pack_json"], true);
if( !empty($pack_info_ob["files"]) )
{
    for($f=0; $f < count($pack_info_ob["files"]); $f++)
    {
        $tmp_dir = $uploaddir.$pack_info_ob["files"][$f]["pack_path"].$pack_info_ob["files"][$f]["file_name"];
        $tmp_dir = str_replace(array("//"), "/", $tmp_dir);
        
        
        $destination = str_replace(array("<backend_dir>"), $DP_Config->backend_dir, $_SERVER["DOCUMENT_ROOT"].$pack_info_ob["files"][$f]["server_path"]);
        if(!is_dir($destination))
        {
            mkdir($destination, 0755, true);//Создаем каталог назначения, если не было
        }
        
        
        if(!copy($tmp_dir, $destination.$pack_info_ob["files"][$f]["file_name"]))
        {
            $ResultMessage->result_code = 4;
            $ResultMessage->message = "Ошибка копирования файла";
            exit(json_encode($ResultMessage));
        }
    }
}




$ResultMessage->result_code = 0;
$ResultMessage->message = "Файлы успешно скопированы";
exit(json_encode($ResultMessage));
?>



<?php
//Класс ответа
class ResultMessage
{
    public $result_code;//Код результата (0 - все корректно)
    public $message;//Текстовое сообщение
}
?>