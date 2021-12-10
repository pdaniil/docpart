<?php
/**
 * Скрипт для подготовки установки пакета
 * Вызывается через AJAX. Скрипт получает имя пакета, который нужно подготовить к установке
*/
header('Content-Type: application/json;charset=utf-8;');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/lib/PclZip/pclzip.lib.php");//Библиотека для работы с zip


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


//2. ПРОВЕРКА НАЛИЧИЯ ФАЙЛА
if(file_exists ( $_POST["pack_file"] ) == false)
{
    $ResultMessage->result_code = 2;
    $ResultMessage->message = "Файл пакета не найден";
	exit(json_encode($ResultMessage));
}

// - файл архива есть


//3. РАСПАКОВЫВАЕМ ФАЙЛ
$uploaddir = $_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/pack_setup/";//Директория для распаковки (где и сам архив)
$archive = new PclZip($_POST["pack_file"]);
if($archive->extract(PCLZIP_OPT_PATH, $uploaddir) == 0)
{
    $ResultMessage->result_code = 3;
    $ResultMessage->message = "Ошибка распаковки архива";
	exit(json_encode($ResultMessage));
}

// - архив распакован

//4. ПРОВЕРКА ПЕРВИЧНОЙ КОРРЕКТНОСТИ ПАКЕТА
//4.1 ПРОВЕРКА НАЛИЧИЯ ФАЙЛА pack.json
if(file_exists ( $uploaddir."pack.json" ) == false)
{
    $ResultMessage->result_code = 4;
    $ResultMessage->message = "Отсутствует файл описания пакета";
	exit(json_encode($ResultMessage));
}

// - файл описания есть

//4.2 ПОЛУЧАЕМ ОБЪЕКТ ОПИСАНИЯ
$pack_json_file = fopen($uploaddir."pack.json", "r");//Файл описания
$pack_json_string = fread($pack_json_file, filesize($uploaddir."pack.json"));//Получаем содержимое файла описания
fclose($pack_json_file);//Закрываем файл
$pack_json_ob = json_decode($pack_json_string, true);


//4.2.1 ПРОВЕРКА НА НАЛИЧИЕ УСТАНОВЛЕННОГО ПАКЕТА С ТАКИМ ЖЕ ТЕХ.ИМЕНЕМ
$same_pack_query = $db_link->prepare("SELECT * FROM `packs` WHERE `name`=? AND `removed` = 0;");
$same_pack_query->execute( array($pack_json_ob["name"]) );
$same_pack_record = $same_pack_query->fetch();
if( $same_pack_record != false )//Такой пакет уже есть.
{
    //Получаем версию и сравниваем - для уведомления администратора
    $current_pack_version = str_replace(array("."), "", $pack_json_ob["version"]);
    $same_pack_version = str_replace(array("."), "", $same_pack_record["version"]);
    
    //Дописываем нули к короткой строке
    if(strlen($current_pack_version) > strlen($same_pack_version))
    {
        $need_zero_count = strlen($current_pack_version) - strlen($same_pack_version);
        for($i=0; $i < $need_zero_count; $i++)
        {
            $same_pack_version = $same_pack_version."0";
        }
    }
    else if(strlen($same_pack_version) > strlen($current_pack_version))
    {
        $need_zero_count = strlen($same_pack_version) - strlen($current_pack_version);
        for($i=0; $i < $need_zero_count; $i++)
        {
            $current_pack_version = $current_pack_version."0";
        }
    }
    
    if((double)$same_pack_version > (double)$current_pack_version)
    {
        $ResultMessage->result_code = 51;
        $ResultMessage->message = "Уже установлен пакет более новой версии";
    	exit(json_encode($ResultMessage));
    }
    else if((double)$same_pack_version == (double)$current_pack_version)
    {
        $ResultMessage->result_code = 52;
        $ResultMessage->message = "Такой пакет уже установлен";
    	exit(json_encode($ResultMessage));
    }
    else
    {
        $ResultMessage->result_code = 53;
        $ResultMessage->message = "Сначала нужно удалить старый пакет";
    	exit(json_encode($ResultMessage));
    }
}

// - установленного пакета нет


//4.3 ПРОВЕРКА КОРРЕКТНОСТИ ИНФОРМАЦИОННЫХ ПОЛЕЙ В ФАЙЛЕ ОПИСАНИЯ
if($pack_json_ob["caption"] == "" || empty($pack_json_ob["caption"]))
{
    $ResultMessage->result_code = 61;
    $ResultMessage->message = "Не указано название пакета (caption)";
	exit(json_encode($ResultMessage));
}
if($pack_json_ob["name"] == "" || empty($pack_json_ob["name"]))
{
    $ResultMessage->result_code = 62;
    $ResultMessage->message = "Не указано техническое имя пакета (name)";
	exit(json_encode($ResultMessage));
}
if($pack_json_ob["version"] == "" || empty($pack_json_ob["version"]))
{
    $ResultMessage->result_code = 63;
    $ResultMessage->message = "Не указана версия пакета (version)";
	exit(json_encode($ResultMessage));
}

// - информационные поля корректны

//4.4 ПРОВЕРКА КОРРЕКТНОСТИ ФАЙЛОВ (УКАЗАННЫЕ В ПАКЕТЕ ФАЙЛЫ ДОЛЖНЫ БЫТЬ В СОСТАВЕ ПАКЕТА И ОНИ НЕ ДОЛЖНЫ ПЕРЕТИРАТЬ СУЩЕСТВУЮЩИЕ НА СЕРВЕРЕ ФАЙЛЫ)
if(!empty($pack_json_ob["files"]))
{
    if( ! is_array($pack_json_ob["files"]))
    {
        $ResultMessage->result_code = 71;
        $ResultMessage->message = "Атрибут files должен быть массивом";
    	exit(json_encode($ResultMessage));
    }
    // - files есть и является массивом
    
    //4.4.1 ПРОВЕРКА КАЖДОГО ФАЙЛА ПАКЕТА
    for($i=0; $i<count($pack_json_ob["files"]); $i++)
    {
        //1. Проверить наличие файла в составе пакета
        if(file_exists ( $uploaddir.$pack_json_ob["files"][$i]["pack_path"].$pack_json_ob["files"][$i]["file_name"] ) == false)
        {
            $ResultMessage->result_code = 72;
            $ResultMessage->message = "В составе пакета отсутствует файл, указанный в описании: ".$pack_json_ob["files"][$i]["file_name"];
        	exit(json_encode($ResultMessage));
        }
        
        //2. Проверить отсутствие файла в месте назначения (чтобы не перетирать)
        $destination_file = str_replace(array("<backend_dir>"), $DP_Config->backend_dir, $_SERVER["DOCUMENT_ROOT"].$pack_json_ob["files"][$i]["server_path"].$pack_json_ob["files"][$i]["file_name"]);
        if(file_exists ( $destination_file ) == true)
        {
            $ResultMessage->result_code = 73;
            $ResultMessage->message = "На сервере уже есть файл: ".$destination_file;
        	exit(json_encode($ResultMessage));
        }
    }
}

// - ошибки с файлами отсутствуют

//4.5 ПРОВЕРКА КОРРЕКТНОСТИ ПРОТОТИПОВ МОДУЛЕЙ
if(!empty($pack_json_ob["modules_prototypes"]))
{
    if( ! is_array($pack_json_ob["modules_prototypes"]))
    {
        $ResultMessage->result_code = 81;
        $ResultMessage->message = "Атрибут modules_prototypes должен быть массивом";
    	exit(json_encode($ResultMessage));
    }
    // - modules_prototypes есть и является массивом
    
    //4.5.1 ПРОВЕРКА КАЖДОГО ОБЪЕКТА ПРОТОТИПА МОДУЛЯ
    for($i=0; $i<count($pack_json_ob["modules_prototypes"]); $i++)
    {
        //1. Наличие обязательного параметра is_frontend
        if( ! isset($pack_json_ob["modules_prototypes"][$i]["is_frontend"]))
        {
            $ResultMessage->result_code = 82;
            $ResultMessage->message = "Атрибут is_frontend обязателен для прототипа модуля";
        	exit(json_encode($ResultMessage));
        }
        else if((boolean)$pack_json_ob["modules_prototypes"][$i]["is_frontend"] != 1 && (boolean)$pack_json_ob["modules_prototypes"][$i]["is_frontend"] != 0)
        {
            $ResultMessage->result_code = 83;
            $ResultMessage->message = "Для атрибута прототипа модуля is_frontend необходимо использовать значения 0 или 1";
        	exit(json_encode($ResultMessage));
        }
        //2. Наличие обязательного параметра prototype_name
        if( ! isset($pack_json_ob["modules_prototypes"][$i]["prototype_name"]))
        {
            $ResultMessage->result_code = 84;
            $ResultMessage->message = "Атрибут prototype_name обязателен для прототипа модуля";
        	exit(json_encode($ResultMessage));
        }
        //3. Наличие обязательного параметра content_type
        if( ! isset($pack_json_ob["modules_prototypes"][$i]["content_type"]))
        {
            $ResultMessage->result_code = 85;
            $ResultMessage->message = "Атрибут content_type обязателен для прототипа модуля";
        	exit(json_encode($ResultMessage));
        }
        else if($pack_json_ob["modules_prototypes"][$i]["content_type"] != "php" && $pack_json_ob["modules_prototypes"][$i]["content_type"] != "text")
        {
            $ResultMessage->result_code = 86;
            $ResultMessage->message = "Неизвестный тип содержимого прототипа модуля ".$pack_json_ob["modules_prototypes"][$i]["prototype_name"].". Атрибут content_type может принимать параметры типа php или text";
        	exit(json_encode($ResultMessage));
        }
        //4. Наличие обязательного параметра content
        if( ! isset($pack_json_ob["modules_prototypes"][$i]["content"]))
        {
            $ResultMessage->result_code = 87;
            $ResultMessage->message = "Атрибут content обязателен для прототипа модуля";
        	exit(json_encode($ResultMessage));
        }
    }
}


// - проверка прототипов модулей пройдена


//4.6 ПРОВЕРКА КОРРЕКТНОСТИ ПЛАГИНОВ
if(!empty($pack_json_ob["plugins"]))
{
    if( ! is_array($pack_json_ob["plugins"]))
    {
        $ResultMessage->result_code = 91;
        $ResultMessage->message = "Атрибут plugins должен быть массивом";
    	exit(json_encode($ResultMessage));
    }
    // - plugins есть и является массивом
    
    //4.6.1 ПРОВЕРКА КАЖДОГО ОБЪЕКТА ПЛАГИНА
    for($i=0; $i<count($pack_json_ob["plugins"]); $i++)
    {
        //1. Наличие обязательного параметра is_frontend
        if( ! isset($pack_json_ob["plugins"][$i]["is_frontend"]))
        {
            $ResultMessage->result_code = 92;
            $ResultMessage->message = "Атрибут is_frontend обязателен для плагина";
        	exit(json_encode($ResultMessage));
        }
        else if((boolean)$pack_json_ob["plugins"][$i]["is_frontend"] != 1 && (boolean)$pack_json_ob["plugins"][$i]["is_frontend"] != 0)
        {
            $ResultMessage->result_code = 93;
            $ResultMessage->message = "Для атрибута плагина is_frontend необходимо использовать значения 0 или 1";
        	exit(json_encode($ResultMessage));
        }
        //2. Наличие обязательного параметра caption
        if( ! isset($pack_json_ob["plugins"][$i]["caption"]))
        {
            $ResultMessage->result_code = 94;
            $ResultMessage->message = "Атрибут caption обязателен для плагина";
        	exit(json_encode($ResultMessage));
        }
        //3. Наличие обязательного параметра source
        if( ! isset($pack_json_ob["plugins"][$i]["source"]))
        {
            $ResultMessage->result_code = 95;
            $ResultMessage->message = "Атрибут source обязателен для плагина";
        	exit(json_encode($ResultMessage));
        }
    }
}

// - проверка плагинов пройдена



//4.7 ПРОВЕРКА КОРРЕКТНОСТИ ШАБЛОНОВ
if(!empty($pack_json_ob["templates"]))
{
    if( ! is_array($pack_json_ob["templates"]))
    {
        $ResultMessage->result_code = 101;
        $ResultMessage->message = "Атрибут templates должен быть массивом";
    	exit(json_encode($ResultMessage));
    }
    // - templates есть и является массивом
    
    //4.7.1 ПРОВЕРКА КАЖДОГО ОБЪЕКТА ШАБЛОНА
    for($i=0; $i<count($pack_json_ob["templates"]); $i++)
    {
        //1. Наличие обязательного параметра is_frontend
        if( ! isset($pack_json_ob["templates"][$i]["is_frontend"]))
        {
            $ResultMessage->result_code = 102;
            $ResultMessage->message = "Атрибут is_frontend обязателен для шаблона";
        	exit(json_encode($ResultMessage));
        }
        else if((boolean)$pack_json_ob["templates"][$i]["is_frontend"] != 1 && (boolean)$pack_json_ob["templates"][$i]["is_frontend"] != 0)
        {
            $ResultMessage->result_code = 103;
            $ResultMessage->message = "Для атрибута шаблона is_frontend необходимо использовать значения 0 или 1";
        	exit(json_encode($ResultMessage));
        }
        //2. Наличие обязательного параметра name
        if( ! isset($pack_json_ob["templates"][$i]["name"]))
        {
            $ResultMessage->result_code = 104;
            $ResultMessage->message = "Атрибут name обязателен для шаблона";
        	exit(json_encode($ResultMessage));
        }
        //3. Наличие обязательного параметра caption
        if( ! isset($pack_json_ob["templates"][$i]["caption"]))
        {
            $ResultMessage->result_code = 105;
            $ResultMessage->message = "Атрибут caption обязателен для шаблона";
        	exit(json_encode($ResultMessage));
        }
        //4. Наличие обязательного параметра positions
        if( ! isset($pack_json_ob["templates"][$i]["positions"]))
        {
            $ResultMessage->result_code = 106;
            $ResultMessage->message = "Атрибут positions обязателен для шаблона";
        	exit(json_encode($ResultMessage));
        }
    }
}


// - проверка шаблонов пройдена


//5. СОЗДАЕМ УЧЕТНУЮ ЗАПИСЬ ПАКЕТА
$SQL_INSERT_PACK = "INSERT INTO `packs` (`name`, `caption`, `author`, `version`, `time_setup`, `admin_id`, `pack_json`) VALUES (?, ?, ?, ?, ?, ?, ?);";
if( $db_link->prepare($SQL_INSERT_PACK)->execute( array($pack_json_ob["name"], $pack_json_ob["caption"], $pack_json_ob["author"], $pack_json_ob["version"], time(), $user_id, $pack_json_string) ) == false)
{
    $ResultMessage->result_code = 201;
    $ResultMessage->message = "Не удалось создать учетную запись пакета";
	exit(json_encode($ResultMessage));
}

//6. ПОЛУЧАЕМ УЧЕТНУЮ ЗАПИСЬ ПАКЕТА
$created_pack_query = $db_link->prepare("SELECT * FROM `packs` ORDER BY `id` DESC LIMIT 1;");
$created_pack_query->execute();
$created_pack_record = $created_pack_query->fetch();
$created_pack_id = $created_pack_record["id"];//ID созданного пакета


//7. УПЕШНЫЙ РЕЗУЛЬТАТ
$ResultMessage->result_code = 0;
$ResultMessage->message = "Проверка пакета пройдена. Учетная запись создана";
$ResultMessage->pack_id = $created_pack_id;
exit(json_encode($ResultMessage));
?>


<?php
//Класс ответа
class ResultMessage
{
    public $result_code;//Код результата (0 - все корректно)
    public $message;//Текстовое сообщение
    public $pack_id;//ID устанавливаемого пакета
}
?>