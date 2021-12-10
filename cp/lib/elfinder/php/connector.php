<?php
error_reporting(0); // Set E_ALL for debuging


// ----------- Start ПРОВЕРКА СЕССИИ -----------
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
    $ResultMessage->result_code = 1;
    $ResultMessage->message = "Нет соединения с БД";
	exit(json_encode($ResultMessage));
}
$db_link->query("SET NAMES utf8;");



//1. ПРОВЕРКА ПРАВ НА ЗАПУСК СКРИПТА
$user_id = 0;
$check_authentication_query = $db_link->prepare("SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `type`=1;");
$check_authentication_query->execute( array($_COOKIE["admin_session"]) );

$check_authentication_query = $check_authentication_query->fetchColumn();

if( $check_authentication_query == 0)
{
    exit("No access");
}
else if( $check_authentication_query != 1)
{
    exit("Session duplication");
}
// ----------- End ПРОВЕРКА СЕССИИ -----------




include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderConnector.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinder.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDriver.class.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeLocalFileSystem.class.php';
// Required for MySQL storage connector
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeMySQL.class.php';
// Required for FTP connector support
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeFTP.class.php';


/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from  '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
		:  null;                                    // else elFinder decide it itself
}

$opts = array(
	// 'debug' => true,
	'roots' => array(
		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => '../../../../content/files/',         // path to files (REQUIRED)
			'URL'           => '/../../../../content/files/', // URL to files (REQUIRED)
			'accessControl' => 'access'             // disable and hide dot starting files (OPTIONAL)
		)
	)
);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

