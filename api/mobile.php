<?php
define("DOCPART_MOBILE_API", "DOCPART_MOBILE_API");
header('Content-Type: application/json;charset=utf-8;');
//Скрипт API взаимодействия сайта и мобильного приложения

// -------------------------------------------------------------------------
//Проверяем наличие объекта запроса
if( empty( $_POST["request"] ) )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Пустой запрос.".$_POST["request"];
	exit(json_encode($answer));
}
// -------------------------------------------------------------------------
/*
$log = fopen("log.txt", "w");
fwrite($log, $_POST["request"]);
fclose($log);
*/
//Получаем запрос
$request = json_decode($_POST["request"], true);
// -------------------------------------------------------------------------
//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
// -------------------------------------------------------------------------
//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB Connect";
	exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");
// -------------------------------------------------------------------------





//Обрабатываем метод
switch($request["method"])
{
	//Метод обновления текущих данных
	case "update_data":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/update_data.php");
		break;
	//Метод аутентификации
	case "authentication":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/authentication.php");
		break;
	//Метод проверки сессии
	case "check_session":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/check_session.php");
		break;
	//Выход из сессии
	case "close_session":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/close_session.php");
		break;
	//Запрос позиций корзины
	case "cart_get_items":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/cart_get_items.php");
		break;
	//Команда изменения требуемого количества корзины
	case "cart_change_count_need":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/cart_change_count_need.php");
		break;
	//Команда изменения выставления флага - помечен на заказ
	case "cart_check_for_order":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/cart_check_for_order.php");
		break;
	//Команда удаления позиции корзины
	case "cart_delete_item":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/cart_delete_item.php");
		break;
	//Команда получения доступных способов доставки
	case "get_obtaining_modes":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_obtaining_modes.php");
		break;
	//Команда создания заказа
	case "checkout_create":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/checkout_create.php");
		break;
	//Команда получения списка статусов заказов
	case "get_orders_statuses_list":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_orders_statuses_list.php");
		break;
	//Команда получения списка заказов
	case "get_my_orders":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_my_orders.php");
		break;
	//Команда получения данных по одному заказу
	case "get_my_order":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_my_order.php");
		break;
	//Команда получения баланса
	case "get_balance":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_balance.php");
		break;
	//Команда получения списка возможных наименований финансовых операций
	case "get_balance_operations_names_n_codes":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/get_balance_operations_names_n_codes.php");
		break;
	
	//ПОИСК ПО АРТИКУЛУ. Получение связок офис-склад
	case "article_search_get_offices_storages_bunches":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/article_search_get_offices_storages_bunches.php");
		break;
	//ПОИСК ПО АРТИКУЛУ. Получение списка производителей по артикулу
	case "article_search_get_manufacturers":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/article_search_get_manufacturers.php");
		break;
	//ПОИСК ПО АРТИКУЛУ. Получение аналогов
	case "article_search_get_analogs":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/article_search_get_analogs.php");
		break;
	//ПОИСК ПО АРТИКУЛУ. Получение товаров
	case "article_search_get_products_of_bunch":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/article_search_get_products_of_bunch.php");
		break;
	//Добавление в корзину
	case "add_to_basket":
		require_once($_SERVER["DOCUMENT_ROOT"]."/api/mobile_methods/add_to_basket.php");
		break;
	default:
		$answer = array();
		$answer["status"] = false;
		$answer["message"] = "Не известный метод. ".$request["method"];
		$answer["request"] = json_encode($request);
		exit(json_encode($answer));
}
?>