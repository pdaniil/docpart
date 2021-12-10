<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

class prices_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $manufacturer, $storage_options)
	{
		$this->result = 0;//По умолчанию
		
		
        //Подключение к основной БД
        $DP_Config = new DP_Config;//Конфигурация CMS
        
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
        
		
		$manufacturer = NULL;
		$binding_values = array();
		//Формируем условие запроса для артикулов и производителей
		if( $manufacturer == "" || $manufacturer == NULL )
		{
			$SQL_WHERE = " `article` = ? ";
			
			array_push($binding_values, $article);
		}
		else
		{
			$SQL_WHERE = " (`article` = ? AND `manufacturer` = ?) ";
			
			array_push($binding_values, $article);
			array_push($binding_values, $manufacturer);
			
			$analogs = $storage_options["analogs"];
			for($i=0; $i < count($analogs); $i++)
			{
				$SQL_WHERE .= " OR (`article` = ? AND `manufacturer` = ?) ";
				
				array_push($binding_values, $analogs[$i]["article"]);
				array_push($binding_values, $analogs[$i]["manufacturer"]);
			}
		}
		
		
			
		$price_id = $storage_options["price_id"];
		$probability = $storage_options["probability"];
		$color = $storage_options["color"];
		
		$office_id = $storage_options["office_id"];
		$storage_id = $storage_options["storage_id"];
		
		$office_caption = $storage_options["office_caption"];
		$storage_caption = $storage_options["storage_caption"];
		$rate = $storage_options["rate"];
		
		
		//Получаем дополнительный срок доставки
		$additional_time_query = $db_link->prepare('SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = '.(int)$office_id.' AND `storage_id` = '.(int)$storage_id.' LIMIT 1;');
		$additional_time_query->execute();
		$additional_time_record = $additional_time_query->fetch();
		$additional_time = (int)($additional_time_record["additional_time"]/24);
		
		
		
		//Делаем запрос по артикулу
		$products_query = $db_link->prepare('SELECT * FROM `shop_docpart_prices_data` WHERE ('.$SQL_WHERE.') AND `price_id` = ?;');
		array_push($binding_values, $price_id);
		$products_query->execute($binding_values);
		while($product = $products_query->fetch() )
		{
			//Цена
			$price = $product["price"];
			
			//Наценка
			$markup = $storage_options["markups"][(int)$price];
			if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
			{
				$markup = $storage_options["markups"][count($storage_options["markups"])-1];
			}
			
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($product["manufacturer"],
				$product["article_show"],
				$product["name"],
				$product["exist"],
				$price + $price*$markup,
				$product["timeToExe"] + $storage_options["additional_time"],
				$product["timeToExe"] + $storage_options["additional_time"],
				$product["storage"],
				$product["min_order"],
				$storage_options["probability"],
				$storage_options["office_id"],
				$storage_options["storage_id"],
				$storage_options["office_caption"],
				$storage_options["color"],
				$storage_options["storage_caption"],
				$price,
				$markup,
				2,0,0,'',null,array("rate"=>$rate)
				);
			
			if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}
		}
			
			
		
		
		
		$this->result = 1;
	}//~function __construct($article)
};//~class prices_enclosure


$ob = new prices_enclosure($_POST["article"], $_POST["manufacturer"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>