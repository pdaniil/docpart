<?php
/**
 * Серверный скрипт для получения списка производителей по артикулу от прайс листов
*/
header('Content-Type: application/json;charset=utf-8;');

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

class prices_enclosure
{
	public $status;
	public $time;//Время запроса
	public $ProductsManufacturers = array();//Список брендов
	
	public function __construct($query, $office_storage_bunches, $DP_Config)
	{

		/*****Учетные данные*****/
		$article = $query["article"];
		/*****Учетные данные*****/
        
		
        // --------------------------------------------------------------------------------------
        //Подключение к БД
        try
		{
			$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
		}
		catch (PDOException $e) 
		{
			return;
		}
		$db_link->query("SET NAMES utf8;");
        // --------------------------------------------------------------------------------------
        
		
		//Формируем массив id прайс листов для опроса
		$prices_list = array();
		$cnt = count($office_storage_bunches);
		for($i=0; $i < $cnt; $i++)
		{
			$storage_id = $office_storage_bunches[$i]["storage_id"];
			
			//Получаем данные склада что бы выбрать id подключенных прайсов
			$storage_query = $db_link->prepare('SELECT `connection_options` FROM `shop_storages` WHERE `id` = ?;');
			$storage_query->execute( array($storage_id) );
			$storage_record = $storage_query->fetch();
			$connection_options = json_decode($storage_record["connection_options"], true);
			$price_id = $connection_options["price_id"];
			
			array_push($prices_list, (int)$price_id);
		}
		
		//Формируем SQL-запрос
		$SQL = "SELECT * FROM `shop_docpart_prices_data` WHERE `article` = ? AND `price_id` IN(" . implode( ',', array_fill( 0, count( $prices_list ), '?' ) ) .");";
		
		//Делаем запрос по артикулу
		$products_query = $db_link->prepare( $SQL );
		
		$binds = array();
		array_push($binds, $article);

		$binds = array_merge( $binds, $prices_list );
		
		$products_query->execute( $binds );
		
		//Фильтруем повторяющихся
		$hashes = array();
		
        while($product = $products_query->fetch() )
        {
			$DocpartManufacturer = new DocpartManufacturer($product["manufacturer"],
			    0,
				$product["name"],
				0,
				0,
				true,
				array('type'=>'prices')
			);
			
			if($DocpartManufacturer->valid === true){
				
				//Получаем хеш
				$hash = md5($DocpartManufacturer->manufacturer);
				
				//Поиск хеша
				if (!isset($hashes[$hash])){
					array_push($this->ProductsManufacturers, $DocpartManufacturer);
					$hashes[$hash] = true;
				}
			}
        }
		
		
        $this->status = true;
	}//~function __construct($article)
};//~class prices_enclosure


$time_start = microtime(true);


$ManufacturersList = new prices_enclosure( 
	json_decode($_POST["query"], true), 
	json_decode($_POST["office_storage_bunches"], true ), 
	$DP_Config
);



$time_end = microtime(true);
$ManufacturersList->time = number_format(($time_end - $time_start), 3, '.', '');
exit(json_encode($ManufacturersList));
?>