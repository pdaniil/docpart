<?php
/**
 * Скрипт для создания записей расширений для указанного пакета
 * Создаются: Шаблоны, Прототипы модулей, Плагины
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");


//0. ПОДКЛЮЧЕНИЕ К БД
$DP_Config = new DP_Config;//Конфигурация CMS
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    echo json_encode("No DB connect");
	exit;
}
$db_link->query("SET NAMES utf8;");


// -------------------------------------------------------------------------------------------


//1. ПРОВЕРКА ПРАВ НА ЗАПУСК СКРИПТА
$check_authentication_query = $db_link->prepare("SELECT COUNT(*) FROM `sessions` WHERE `session`= ? AND `type` = ?;");
$check_authentication_query->execute( array($_COOKIE["admin_session"], 1) );
if($check_authentication_query->fetchColumn() == 0)
{
    exit("No access");
}
else if($check_authentication_query->fetchColumn() != 1)
{
    exit("Session duplication");
}

// - проверка пройдена


//2. ПОЛУЧЕНИЕ УЧЕТНОЙ ЗАПИСИ ПАКЕТА
$pack_query = $db_link->prepare("SELECT * FROM `packs` WHERE `id` = ?;");
$pack_query->execute( array($_POST["pack_id"]) );
$pack_record = $pack_query->fetch();
if($pack_record == false)
{
    $ResultMessage->result_code = 1;
    $ResultMessage->message = "Не найдена учетная запись пакета";
    exit(json_encode($ResultMessage));
}
$pack_info_ob = json_decode($pack_record["pack_json"], true);



//3. СОЗДАНИЕ РАСШИРЕНИЙ
//3.1 СОЗДАНИЕ ШАБЛОНОВ
for($t=0; $t < count($pack_info_ob["templates"]); $t++)
{
    $SQL_INSERT = "INSERT INTO `templates` (`is_frontend`, `name`, `caption`, `positions`, `phone_support`, `tablet_support`, `data_structure`, `data_value`, `current`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);";

    if( $db_link->prepare($SQL_INSERT)->execute( array($pack_info_ob["templates"][$t]["is_frontend"], $pack_info_ob["templates"][$t]["name"], $pack_info_ob["templates"][$t]["caption"], $pack_info_ob["templates"][$t]["positions"], $pack_info_ob["templates"][$t]["phone_support"], $pack_info_ob["templates"][$t]["tablet_support"], $pack_info_ob["templates"][$t]["data_structure"], $pack_info_ob["templates"][$t]["data_value"], 0) ) != true)
    {
        $ResultMessage->result_code = 31;
        $ResultMessage->message = "Ошибка создания учетной записи шаблона";
        exit(json_encode($ResultMessage));
    }
    
    
    //Учетная запись создана - получаем ID шаблона и записываем его в объект описания пакета
    $inserted_template_query = $db_link->prepare("SELECT * FROM `templates` ORDER BY `id` DESC LIMIT 1;");
	$inserted_template_query->execute();
    $inserted_template_record = $inserted_template_query->fetch();
    $pack_info_ob["templates"][$t]["id"] = $inserted_template_record["id"];
}

//3.2 СОЗДАНИЕ ПЛАГИНОВ
for($p=0; $p < count($pack_info_ob["plugins"]); $p++)
{
    $SQL_INSERT = "INSERT INTO `plugins` (`is_frontend`, `caption`, `source`, `description`, `activated`, `data_structure`, `data_value`, `control_lock`) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
    if( $db_link->prepare($SQL_INSERT)->execute( array($pack_info_ob["plugins"][$p]["is_frontend"], $pack_info_ob["plugins"][$p]["caption"], $pack_info_ob["plugins"][$p]["source"], $pack_info_ob["plugins"][$p]["description"], $pack_info_ob["plugins"][$p]["activated"], $pack_info_ob["plugins"][$p]["data_structure"], $pack_info_ob["plugins"][$p]["data_value"], $pack_info_ob["plugins"][$p]["control_lock"]) ) != true)
    {
        $ResultMessage->result_code = 32;
        $ResultMessage->message = "Ошибка создания учетной записи плагина";
        exit(json_encode($ResultMessage));
    }
    
    //Учетная запись создана - получаем ID плагина и записываем его в объект описания пакета
    $inserted_plugin_query = $db_link->prepare("SELECT * FROM `plugins` ORDER BY `id` DESC LIMIT 1;");
	$inserted_plugin_query->execute();
    $inserted_plugin_record = $inserted_plugin_query->fetch();
    $pack_info_ob["plugins"][$p]["id"] = $inserted_plugin_record["id"];
}

//3.3 СОЗДАНИЕ ПРОТОТИПОВ МОДУЛЕЙ
for($m=0; $m < count($pack_info_ob["modules_prototypes"]); $m++)
{
    $SQL_INSERT = "INSERT INTO `modules` (`is_frontend`, `is_prototype`, `prototype_name`, `content_type`, `content`, `data`, `css_js`) VALUES (?, ?, ?, ?, ?, ?, ?);";
    if( $db_link->prepare($SQL_INSERT)->execute( array($pack_info_ob["modules_prototypes"][$m]["is_frontend"], $pack_info_ob["modules_prototypes"][$m]["is_prototype"], $pack_info_ob["modules_prototypes"][$m]["prototype_name"], $pack_info_ob["modules_prototypes"][$m]["content_type"], $pack_info_ob["modules_prototypes"][$m]["content"], $pack_info_ob["modules_prototypes"][$m]["data"], $pack_info_ob["modules_prototypes"][$m]["css_js"]) ) != true)
    {
        $ResultMessage->result_code = 33;
        $ResultMessage->message = "Ошибка создания учетной записи прототипа модуля";
        exit(json_encode($ResultMessage));
    }
    
    //Учетная запись создана - получаем ID прототипа модуля и записываем его в объект описания пакета
    $inserted_module_prototype_query = $db_link->prepare("SELECT * FROM `modules` ORDER BY `id` DESC LIMIT 1;");
	$inserted_module_prototype_query->execute();
    $inserted_module_prototype_record = $inserted_module_prototype_query->fetch();
    $pack_info_ob["modules_prototypes"][$p]["id"] = $inserted_module_prototype_record["id"];
}


//4. ОБНОВЛЯЕМ ИНФОРМАЦИЮ ПО ПАКЕТУ
$db_link->prepare("UPDATE `packs` SET `pack_json` = ? WHERE `id` = ?;")->execute( array(str_replace(array("\\"), "\\\\", json_encode($pack_info_ob)), $_POST["pack_id"]) );


//5. УСПЕШНЫЙ РЕЗУЛЬТАТ
$ResultMessage->result_code = 0;
$ResultMessage->message = "Созданы расширения";
$ResultMessage->pack_info_ob = $pack_info_ob;
exit(json_encode($ResultMessage));
?>




<?php
//Класс ответа
class ResultMessage
{
    public $result_code;//Код результата (0 - все корректно)
    public $message;//Текстовое сообщение
    public $pack_info_ob;//Объект описания пакета
}
?>