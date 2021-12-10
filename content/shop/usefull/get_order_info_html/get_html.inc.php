<?php
/*
Вспомогательный скрипт для получения информации по заказу в виде простейшей html таблицы.
Используется для отправки уведомлений менеджеру
*/


$items_counter = 0;
$count_need_total = 0;
$price_sum_total = 0;
$price_purchase_sum_total = 0;
$profit_total = 0;


//1.
$orders_statuses = array();
$orders_statuses_query = $db_link->prepare('SELECT * FROM `shop_orders_statuses_ref` ORDER BY `order` ASC;');
$orders_statuses_query->execute();
while($status = $orders_statuses_query->fetch() )
{
    $orders_statuses[$status["id"]] = array("name"=>$status["name"], "color"=>$status["color"]);
}
//2
$orders_items_statuses = array();
$orders_items_statuses_not_count = array();
$orders_items_statuses_query = $db_link->prepare('SELECT * FROM `shop_orders_items_statuses_ref` ORDER BY `order` ASC;');
$orders_items_statuses_query->execute();
while($status = $orders_items_statuses_query->fetch() )
{
    //2.1
    $orders_items_statuses[$status["id"]] = array("name"=>$status["name"], "color"=>$status["color"]);
    
    //2.1
    if($status["count_flag"] == 0)
    {
        array_push($orders_items_statuses_not_count, $status["id"]);
    }
}



$storages_list = array();//ID=>Название
$storages_query = $db_link->prepare('SELECT `id`,`name` FROM `shop_storages`;');
$storages_query->execute();
while( $storage = $storages_query->fetch() )
{
    $storages_list[$storage["id"]] = $storage["name"];
}


//Запрос наименований
$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";


//Запрос закупа
$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*`count_reserved`) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(8,2)))";

//Сумма позиции
$SELECT_item_price_sum = "CAST(`price`*`count_need` AS DECIMAL(8,2))";

//Маржа позиции
$SELECT_item_profit = "CAST(`price`*`count_need` - $SELECT_price_purchase_sum AS DECIMAL(8,2))";

//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
$SELECT_ORDER_ITEMS = "SELECT *, $SELECT_product_name AS `product_name_type_1`, $SELECT_price_purchase_sum AS `price_purchase_sum`, $SELECT_item_price_sum AS `price_sum`, $SELECT_item_profit AS `profit` FROM `shop_orders_items` WHERE `order_id` = ?;";

$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
$order_items_query->execute( array($order_id) );
?>



<head>
	<style type="text/css">
		body
		{
			font-family:Calibri;
		}


		.main_table
		{
			border-collapse:collapse;
		}

		.head > td
		{
			background-color: #ffd181;
			font-weight:bold;
		}
		.in_total > td
		{
			background-color: #EEE;
			font-weight:bold;
		}
		.item_record > td
		{
			background-color: #b2e8b1;
			font-weight:bold;
		}

		td
		{
			padding:2px;
		}


		.table_inside
		{
			width:100%;
			background-color: #EEE;
		}
	</style>
</head>

<body>


<h3>Заказ № <?php echo $order_id; ?></h3>

<table class="main_table">
	<tr class="head">
		<td>ID</td>
		<td>Наименование</td>
		<td>Цена</td>
		<td>Кол-во</td>
		<td>Сумма</td>
		<td>Закуп</td>
		<td>Маржа</td>
		<td>Статус</td>
		<td>Срок</td>
	</tr>


<?php


while( $order_item = $order_items_query->fetch() )
{
	$item_id            = $order_item["id"];
	$item_status        = $order_item["status"];
	$item_count_need    = $order_item["count_need"];
	$item_price         = $order_item["price"];
	$item_price_sum     = $order_item["price_sum"];
	$item_product_type  = $order_item["product_type"];
	$item_product_id    = $order_item["product_id"];
	$item_price_purchase_sum = $order_item["price_purchase_sum"];
	$item_product_name  = $order_item["product_name_type_1"];
	$item_profit        = $order_item["profit"];
	
	$item_t2_time_to_exe = $order_item["t2_time_to_exe"];
	$item_t2_time_to_exe_guaranteed = $order_item["t2_time_to_exe_guaranteed"];
	
	//Срок доставки для продуктов типа 2
	if($item_t2_time_to_exe < $item_t2_time_to_exe_guaranteed)
	{
		$item_t2_time_to_exe = $item_t2_time_to_exe." - ".$item_t2_time_to_exe_guaranteed;
	}
	$item_t2_time_to_exe = $item_t2_time_to_exe." дн.";
	if($item_product_type == 1)
	{
		$item_t2_time_to_exe = "";
	}
		
	//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
	if( array_search($item_status, $orders_items_statuses_not_count) === false)
	{
		$count_need_total += $item_count_need;
		$price_sum_total += $item_price_sum;
		$price_purchase_sum_total += $item_price_purchase_sum;
		$profit_total += $item_profit;
	}
	
	?>
	
	
	<tr class="item_record">
		<td><?php echo $item_id; ?></td>
		<td><?php echo $item_product_name; ?></td>
		<td><?php echo number_format($item_price, 2, '.', ''); ?></td>
		<td><?php echo $item_count_need; ?></td>
		<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
		<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
		<td><?php echo number_format($item_profit, 2, '.', ''); ?></td>
		<td><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
		<td><?php echo $item_t2_time_to_exe; ?></td>
	</tr>
	
	
	
	<tr>
		<td colspan="10">
				<table class="table_inside">
					
					<tr>
						<td colspan="5">
							<b>Данные склада</b>
						</td>
					</tr>
				
					<tr>
						<td>Склад</td>
						<td>Поставка</td>
						<td>Цена закупа</td>
						<td>Количество</td>
						<td>Сумма закупа</td>
					</tr>
					<tbody>
					<?php
					//Выводим данные по поставкам. Логика зависит от типа продукта
					if($item_product_type == 1)
					{
						$details_query = $db_link->prepare('SELECT *, `count_reserved`*`price_purchase` AS `price_purchase_sum` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;');
						$details_query->execute( array($item_id) );
						while( $detail = $details_query->fetch() )
						{
							?>
							<tr>
								<td><?php echo $storages_list[$detail["storage_id"]]; ?></td>
								<td><?php echo $detail["storage_record_id"]; ?></td>
								<td><?php echo number_format($detail["price_purchase"], 2, '.', ''); ?></td>
								<td><?php echo $detail["count_reserved"]; ?></td>
								<td><?php echo number_format($detail["price_purchase_sum"], 2, '.', ''); ?></td>
							</tr>
							<?php
						}
					}
					else if($item_product_type == 2)
					{
						?>
						<tr>
							<td><?php echo $storages_list[$order_item["t2_storage_id"]]; ?></td>
							<td><?php echo "-"; ?></td>
							<td><?php echo number_format($order_item["t2_price_purchase"], 2, '.', ''); ?></td>
							<td><?php echo $order_item["count_need"]; ?></td>
							<td><?php echo number_format($order_item["t2_price_purchase"]*$order_item["count_need"], 2, '.', ''); ?></td>
						</tr>
						<?php
					}
					?>
						<tr>
							<td colspan="2"></td>
							<td>Итого</td>
							<td><?php echo $item_count_need; ?></td>
							<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
						</tr>
					</tbody>
				</table>
		</td>
	</tr>
	
	
	
	
	
	
	<?php
	$items_counter++;
}//while - по позициям заказа
?>


		<tr class="in_total">
			<td></td>
			<td></td>
			<td>Итого</td>
			<td><?php echo $count_need_total; ?></td>
			<td><?php echo $price_sum_total; ?></td>
			<td><?php echo $price_purchase_sum_total; ?></td>
			<td><?php echo $profit_total; ?></td>
			<td></td>
			<td></td>
		</tr>

	</table>
</body>