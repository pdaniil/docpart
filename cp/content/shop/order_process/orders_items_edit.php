<?php
/**
 * Страница для редактирования позиции заказа
*/
defined('_ASTEXE_') or die('No access');
ini_set("display_errors",0);


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");

$AdminId = (int) DP_User::getAdminId();

if(!empty($_POST["save_action"]))
{
	if($_POST["save_action"] != 'update')
	{
		$error_message = 'Неизвесная операция';
	}
	else
	{
		$item_id = (int)$_POST['item_id'];
		$order_id = (int)$_POST['order_id'];
		$item_product_type = (int)$_POST['item_product_type'];
		$article = $_POST['art'];
		$manufacturer = $_POST['man'];
		$name = $_POST['name'];
		//$t2_markup = (int)$_POST['t2_markup'];
		//$_POST['markup'] = $t2_markup;
		$storage_id = (int)$_POST['storage_id'];
		$user_id = (int)$_POST['user_id'];
		//$t2_price_purchase = (float)$_POST['t2_price_purchase'];
		$count_need = (int)$_POST['count_need'];
		$price = (float)$_POST['price'];//$t2_price_purchase + (($t2_price_purchase / 100) * ((float)$_POST['markup'])); 
		$price_zakup = (float)$_POST['price_zakup'];
		
		$time_to_exe = (int)trim($_POST['t2_time_to_exe']);
		$time_to_exe_guaranteed = (int)trim($_POST['t2_time_to_exe_guaranteed']);
		if($time_to_exe > $time_to_exe_guaranteed){
			$time_to_exe_guaranteed = $time_to_exe;
		}
		
		$error_message = "";
		$success_message = "";
		
		
		//Первым делом проверяем состояние оплаты. Нельзя редактировать позиции заказов, которые Оплачены или частично оплачены.
		$check_paid_query = $db_link->prepare('SELECT `paid` FROM `shop_orders` WHERE `id` = (SELECT `order_id` FROM `shop_orders_items` WHERE `id` = ?);');
		$check_paid_query->execute( array($item_id) );
		$check_paid = $check_paid_query->fetch();
		if( $check_paid['paid'] != 0 )
		{
			$location_url = '/'.$DP_Config->backend_dir.'/shop/orders/items/edit?id='.$item_id;
			?>
			<script>
				location="<?=$location_url?>&error_message=<?php echo urlencode('Редактирование позиции не выполнено, поскольку состояние оплаты заказа, к которому привязана данная позиция, не позволяет вносить изменения в заказ. Вносить изменения в заказ можно только том случае, если он не имеет состояние Оплачен или Частично оплачен'); ?>";
			</script>
			<?php
			exit;
		}
		
		
		
		if( empty($item_id) || empty($count_need) || empty($price) )
		{
			$error_message = 'Данные не могут быть меньше 1';
		}
		else
		{
			if($item_product_type === 2)
			{
				// Обновляем данные
				if( $db_link->prepare("UPDATE `shop_orders_items` SET `price` = ?, `t2_price_purchase` = ?, `t2_name` = ?, `t2_time_to_exe` = ?, `t2_time_to_exe_guaranteed` = ?, `count_need` = ?, `t2_storage_id` = ?, `t2_article` = ?, `t2_manufacturer` = ? WHERE `id` = ?;")->execute( array($price, $price_zakup, $name, $time_to_exe, $time_to_exe_guaranteed, $count_need, $storage_id, $article, $manufacturer, $item_id) ) != true)
				{
					$error_message = "Ошибка: <br/> Не удалось изменить данные.";
				}
				else
				{
					$success_message = "Данные сохранены";
				}
			}
			else
			{
				//Сначала проверяем был ли изменен склад и если склад изменен, то проверяем статус позиции
				$shop_orders_items_status_query = $db_link->prepare("SELECT `status` FROM `shop_orders_items` WHERE `id` = ?");
				$shop_orders_items_status_query->execute(array($item_id));
				$shop_orders_items_status = $shop_orders_items_status_query->fetchColumn();

				//Если статус выдана
				if($shop_orders_items_status == 5) {

					$error_message = "Ошибка: <br/> Текущая позиция уже Выдана. Изменение недопустимо.";

				} else {

					//Смотрим старую складскую запись и какой был склад
					$storage_data_id_query = $db_link->prepare("SELECT `storage_id` AS `prev_storage_id`, `product_id` FROM `shop_storages_data` WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `order_item_id` = ?)");
					$storage_data_id_query->execute(array($item_id));
					$storage_data_id = $storage_data_id_query->fetch();
			
					//Делаем перемещение товаров от одного склада к другому, если был выбран другой склад
					if($storage_data_id['prev_storage_id'] !== $storage_id) {
						
						//Сначала нужно проверить, есть ли этот товар на выбранном складе
						$storage_data_product_query = $db_link->prepare("SELECT COUNT(*) AS `storage_data_product_count`, `id`, `exist` FROM `shop_storages_data` WHERE `product_id` = ? AND `storage_id` = ?");
						$storage_data_product_query->execute(array($storage_data_id['product_id'], $storage_id));
						$storage_data_product = $storage_data_product_query->fetch();

						//Получаем количество товара который будем перемещать из детальных записей
						$storage_detail_items_reserved_query = $db_link->prepare("SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `order_item_id`=?");
						$storage_detail_items_reserved_query->execute(array($item_id));
						$storage_detail_items_reserved = $storage_detail_items_reserved_query->fetchColumn();

						//Переменная нужна для проверки - выполнен ли трансфер товара из одного склада на другой
						//От этого зависит нужно ли обновлять в таблице shop_orders_items_details id складской записи или нет
						$check_order_item_transfer = false;
						$storage_data_product_record_id = $storage_data_product['id'];

						if($storage_data_product['storage_data_product_count'] > 0 && $storage_data_product['exist'] >= $storage_detail_items_reserved) {

							//Такой товар есть на складе, начинаем его перемещать
							//Сначала восстанавливаем количество на изначальном складе
							$storage_data_update_query_1 = $db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` + (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `order_item_id`=?), `reserved` = `reserved` - (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `order_item_id`=?) WHERE `id` = (SELECT `storage_record_id` FROM `shop_orders_items_details` WHERE `order_item_id`=?)');
							if($storage_data_update_query_1->execute( array($item_id, $item_id, $item_id)) != true) {

								$error_message .= "Ошибка восстановления наличия на исходном складе. ";

							} else {

								//Теперь создаем резерв и списываем количество на выбранном складе
								$storage_data_update_query_2 = $db_link->prepare('UPDATE `shop_storages_data` SET `exist` = `exist` - (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `order_item_id`=?), `reserved` = `reserved` + (SELECT `count_reserved` FROM `shop_orders_items_details` WHERE `order_item_id`=?) WHERE `id` = ?');
								if($storage_data_update_query_2->execute( array($item_id, $item_id, $storage_data_product_record_id)) != true) {

									$error_message .= "Ошибка при перемещении товара в резерв на выбранный склад. <br/> На исходном складе наличие восстановлено успешно.  <br/> Измените наличие на складе вручную. ";

								} else {

									$check_order_item_transfer = true;
									$success_message .= "Выбранная позиция перемещан на другой склад успешно. ";

								}
							}

						}
						else
						{
							$error_message .= "Ошибка при добавлении товара в резерв. Текущая позиция отсутствует в нужном количестве на выбранном складе. ";
						}



						// Обновляем данные 1 тип продукта, после перемещения товара на другой склад
						if($check_order_item_transfer) {

							$SQL_order_items_detail = "UPDATE `shop_orders_items_details` SET `price_purchase` = ?, `count_reserved` = ?, `storage_id` = $storage_id, `storage_record_id` = $storage_data_product_record_id WHERE `order_item_id` = ?;";
						
						} else {

							$SQL_order_items_detail = "UPDATE `shop_orders_items_details` SET `price_purchase` = ?, `count_reserved` = ? WHERE `order_item_id` = ?;";
						
						}
						
						if( $db_link->prepare("UPDATE `shop_orders_items` SET `price` = ?, `count_need` = ? WHERE `id` = ?;")->execute( array($price, $count_need, $item_id) ) != true)
						{
							$error_message .= "Ошибка: <br/> Не удалось изменить данные. ";
						}
						else
						{
							if( $db_link->prepare($SQL_order_items_detail)->execute( array($price_zakup, $count_need, $item_id) ) != true)
							{
								$error_message .= "Ошибка: <br/> Не удалось изменить детальные данные. ";
							}
							else
							{  
								$success_message .= "Детальные данные позиции сохранены. ";
							}
						}
						

					}
					else
					{
						// Обновляем данные 1 тип продукта без перемещения товара на другой склад
						if( $db_link->prepare("UPDATE `shop_orders_items` SET `price` = ?, `count_need` = ? WHERE `id` = ?;")->execute( array($price, $count_need, $item_id) ) != true)
						{
							$error_message = "Ошибка: <br/> Не удалось изменить данные.";
						}
						else
						{
							if( $db_link->prepare("UPDATE `shop_orders_items_details` SET `price_purchase` = ?, `count_reserved` = ?, `storage_id` = ? WHERE `order_item_id` = ?;")->execute( array($price_zakup, $count_need, $storage_id, $item_id) ) != true)
							{
								$error_message = "Ошибка: <br/> Не удалось изменить детальные данные.";
							}
							else
							{  
								$success_message = "Данные сохранены.";
							}
						}
					}

				}

			}
			
		}
	}
	$location_url = '/'.$DP_Config->backend_dir.'/shop/orders/items/edit?id='.$item_id;
	?>
	<script>
		location="<?=$location_url?>&error_message=<?=$error_message;?>&success_message=<?=$success_message;?>";
	</script>
	<?php
	exit;
}
else
{
	$item_id = (int)$_GET['id'];
	
	//Первым делом проверяем состояние оплаты. Нельзя редактировать позиции заказов, которые Оплачены или частично оплачены.
	$check_paid_query = $db_link->prepare('SELECT `paid` FROM `shop_orders` WHERE `id` = (SELECT `order_id` FROM `shop_orders_items` WHERE `id` = ?);');
	$check_paid_query->execute( array($item_id) );
	$check_paid = $check_paid_query->fetch();
	if( $check_paid['paid'] != 0 )
	{
		$_GET["warning_message"] = 'Нельзя редактировать позиции заказов, которые Оплачены или Частично оплачены';
	}
	
	$available_storages_list = array();
	$query = $db_link->prepare('SELECT * FROM `shop_storages`;');
	$query->execute( array($item_id) );
	while($row = $query->fetch()){
		$available_storages_list[$row['id']] = $row['name'].' ('.$row['id'].')';
	}
	
	
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/actions_alert.php");//Вывод сообщений о результатах действий
	?>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/orders_items.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">К позициям заказов</div>
				</a>
				<a id="order_id_a" class="panel_a" href="">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/images/store.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">К заказу</div>
				</a>
			</div>
		</div>
	</div>
	<div class="col-lg-12">
	<div class="table-responsive">
	<div class="hpanel">
	<div class="panel-heading hbuilt">
		Данные позиции
	</div>
	<div class="panel-body">
	<?php
	//Формируем сложный SQL-запрос для получения всей информации по позиции
	//Запрос наименований
	$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
	$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
	$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
	//Запрос суммы позиции
	$SELECT_price_sum = "CAST(`price`*`count_need` AS DECIMAL(8,2))";
	//Запрос офисов обслуживания
	$SELECT_offices = "(SELECT `office_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
	//Запрос клиента
	$SELECT_clients = "(SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
	//Запрос закупа
	$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*`count_reserved`) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(8,2)))";
	$SELECT_price_purchase = "IFNULL((SELECT `price_purchase` FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase` AS DECIMAL(8,2)))";
	//Запрос маржы
	$SELECT_profit = "CAST(($SELECT_price_sum - $SELECT_price_purchase_sum) AS DECIMAL(8,2))";
	//Запрос статуса заказа
	$SELECT_order_status = "(SELECT `status` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
	//Запрос флаг "Заказ оплачен"
	$SELECT_paid = "(SELECT `paid` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
	//Запрос складов
	$SELECT_storages = "IFNULL((SELECT `storage_id` FROM `shop_orders_items_details` WHERE `order_id` = `shop_orders_items`.`order_id` AND `order_item_id` = ?), `t2_storage_id`)";
	//Запрос времени создания заказа
	$SELECT_time = "(SELECT `time` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";



	//ЗАПРОС
	$SQL_SELECT_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, 
		$SELECT_product_name AS `product_name`, 
		$SELECT_price_sum AS `price_sum`, 
		$SELECT_offices AS `office_id`, 
		$SELECT_clients AS `customer_id`, 
		$SELECT_price_purchase_sum AS `price_purchase_sum`,
		$SELECT_price_purchase AS `price_purchase`,
		$SELECT_profit AS `profit`,
		$SELECT_order_status AS `order_status`,
		$SELECT_paid AS `paid`,
		$SELECT_storages AS `storages`,
		$SELECT_time AS `time` 
		FROM `shop_orders_items` WHERE `id` = ?;";


	$elements_query = $db_link->prepare($SQL_SELECT_ITEMS);
	$elements_query->execute( array($item_id, $item_id) );
	//var_dump($elements_query->fetch());
	//var_dump($SQL_SELECT_ITEMS);
	//var_dump($SQL_SELECT_ITEMS);
	
	$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
	$elements_count_rows_query->execute();
	$elements_count_rows = $elements_count_rows_query->fetchColumn();
	
	//var_dump($elements_count_rows);
	
	?>
	<table style="font-size:11px;" id="orders_items_table" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>">
		<thead>
			<th><a href="javascript:void(0);" onclick="sortOrdersItems('id');" id="id_sorter">ID</a></th>
			<th><a href="javascript:void(0);" onclick="sortOrdersItems('t2_article');" id="t2_article_sorter">Артикул</a></th>
			<th><a href="javascript:void(0);" onclick="sortOrdersItems('t2_manufacturer');" id="t2_manufacturer_sorter">Производитель</a></th>
			<th><a href="javascript:void(0);" onclick="sortOrdersItems('t2_name');" id="t2_name_sorter">Наименование</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price');" id="price_sorter">Цена</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('count_need');" id="count_need_sorter">Кол-во</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('profit');" id="profit_sorter">Маржа</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price_sum');" id="price_sum_sorter">Сумма</a></th>
			<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('price_purchase_sum');" id="price_purchase_sum_sorter">Закуп</a></th>
			<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('profit2');" id="profit_sorter2">Маржа</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('status');" id="status_sorter">Статус</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('time');" id="time_sorter">Дата</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('order_id');" id="order_id_sorter">Заказ</a></th>
			<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('storages');" id="storages_sorter">Склад</a></th>
			<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('office_id');" id="office_id_sorter">Офис</a></th>
			<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('t2_time_to_exe');" id="t2_time_to_exe_sorter">Срок</a></th>
			<th><a href="javascript:void(0);" onclick="sortOrdersItems('customer_id');" id="customer_id_sorter">Клиент</a></th>
								
		</thead>
		<tbody>
		<?php
		$item_count_sum = 0;// количество
		$item_profit_sum = 0;// Маржа сумма
		$item_sum = 0;// сумма
		while( $item = $elements_query->fetch() )
		{
			//var_dump($item);
			
			$elements_counter++;
			
			$item_id = $item["id"];
			$order_id = $item["order_id"];
			$item_product_type = (int)$item["product_type"];
			$item_status = $item["status"];
			$item_order_id = $item["order_id"];
			$item_product_name = $item["product_name"];
			$item_price = $item["price"];
			$item_count_need = $item["count_need"];
			$item_price_sum = $item["price_sum"];
			$item_office_id = $item["office_id"];
			$item_customer_id = $item["customer_id"];
			$item_price_purchase_sum = $item["price_purchase_sum"];
			$item_profit = $item["profit"];
			$item_time = $item["time"];
			$item_storages = $item["storages"];
			
			if($item_product_type == 2){
				$item_t2_time_to_exe = $item["t2_time_to_exe"];
				$item_t2_time_to_exe_guaranteed = $item["t2_time_to_exe_guaranteed"];
				$item_t2_article = $item["t2_article"];
				$item_t2_manufacturer = $item["t2_manufacturer"];
				$item_t2_name = $item["t2_name"];
				$item_t2_markup = $item["t2_markup"];
			}
			
			// Переменные итого:
			$item_count_sum += $item_count_need;// количество
			$item_profit_sum += $item_profit;// Маржа
			$item_sum += $item_price_sum;// сумма
			
			//Теперь получаем ФИО
			$profile = DP_User::getUserProfileById($item_customer_id);
			$customer_name = $profile["surname"]." ".$profile["name"]."(".$item_customer_id.")";
			
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
			
			/// Получаем комментарии к позиции заказа
			$SELECT_POS_COMMENT = "SELECT `comment` FROM `shop_orders_pos_comment` WHERE `pos_id` = ?;";
			
			$item_query = $db_link->prepare($SELECT_POS_COMMENT);
			$item_query->execute( array($item_id) );
			$item_query = $item_query->fetch();
			$item_comment = $item_query['comment'];
			

			?>
			<tr id="order_item_record_<?php echo $item_id; ?>" style="background-color:<?php echo $orders_items_statuses[$item_status]["color"]; ?>">


				<td><?php echo $item_id; ?></td>
				<td><?php echo $item_t2_article; ?></td>
				<td><?php echo $item_t2_manufacturer; ?></td>
				<?php
				if(!empty($item_t2_name)){
					echo "<td style='max-width:150px;'>". $item_t2_name ."</td>";
					$item_name_edit = $item_t2_name;
				}else{
					echo "<td style='max-width:150px;'>". $item_product_name ."</td>";
					$item_name_edit = $item_product_name;
				}
				?>
				<td><?php echo number_format($item_price, 2, '.', ''). $old_price; ?></td>
				<td><?php echo $item_count_need . $old_count_need; ?></td>
				<td><?php echo $item_profit; ?></td>
				<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
				<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
				<td><?php echo number_format($item_profit, 2, '.', ''); ?><input type="hidden" id="inp_markup" value="<?=$item_profit;?>"/></td>
				<td><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
				<td><?php echo date("d.m.Y", $item_time)." ".date("G:i", $item_time); ?></td>
				<td><a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $item_order_id; ?>">Заказ <?php echo $item_order_id; ?></a><input type="hidden" id="inp_order_id" value="<?=$item_order_id;?>"/></td>
				
				<?php
				// Склад
				if($item_product_type == 1){
				?>
					<td><?php echo $available_storages_list[$item["storages"]]; ?></td>
				<?php
				}else{
				?>
					<td><?php echo $available_storages_list[$item["t2_storage_id"]]; ?></td>
				<?php
				}
				?>
				
				
				<td><?php echo $offices_list[$item_office_id]; ?></td>
				<td><?php echo $item_t2_time_to_exe; ?></td>
				<td><?php echo $customer_name; ?></td>
				
			</tr>
			

		</tbody>
		<tfoot style="display:none;"><tr><td><ul class="pagination"></ul></td></tr></tfoot>
	</table>


	<?php
	// Склад
	if($item_product_type == 1){
		$style = " display:none;";
	}
	?>
	<div style="padding:20px 0px;<?=$style;?>">
		<label>Артикул:</label><br/>
		<input class="form-control" style="width:500px;" id="art_item_inp" type="text" value="<?=$item_t2_article;?>" />
		<br/><label>Производитель:</label><br/>
		<input class="form-control" style="width:500px;" id="man_item_inp" type="text" value="<?=$item_t2_manufacturer;?>"/><br/><label>Наименование:</label><br/>
		<input class="form-control" style="width:500px;" id="name_item_inp" type="text" value="<?=$item_name_edit;?>"/>
		<br/><label>Ожидаемый срок:</label><br/>
		<input class="form-control" style="width:200px;" id="t2_time_to_exe_item_inp" type="text" value="<?=$item["t2_time_to_exe"];?>"/>
		<br/><label>Гарантированный срок:</label><br/>
		<input class="form-control" style="width:200px;" id="t2_time_to_exe_guaranteed_item_inp" type="text" value="<?=$item["t2_time_to_exe_guaranteed"];?>"/>
	</div>




	<table>
		<tr>
			<td>
			<div class="row">
				<div class="col-lg-12">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
						<thead>
							<th>Склад</th>
							<th>Цена</th>
							<th>Закупочная Цена</th>
							<th>Количество</th>
							<th>Сумма</th>
							<th>Сумма закупа</th>
						</thead>
						<tbody>
						<?php
						//Выводим данные по поставкам. Логика зависит от типа продукта
						if($item_product_type == 1)
						{
							$details_query = $db_link->prepare("SELECT *, `count_reserved`*`price_purchase` AS `price_purchase_sum` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;");
							$details_query->execute( array($item_id) );

							
							
							while( $detail = $details_query->fetch() )
							{
								?>
								<tr>
									<td>
										<select class="form-control" id="inp_storage_id">
											
									<?php 
										if($item_product_type !== 0)
										{
											foreach($storages_list as $k => $v){
												if($detail["storage_id"] == $k){
													echo '<option selected value="'.$k.'">'.$v.' ('.$k.')</option>';
												}else{
													echo '<option value="'.$k.'">'.$v.' ('.$k.')</option>';
												}
												 
											}
										}
									?>
										</select>
									</td>
									<td><input class="form-control" type="text" id="inp_price" value="<?php echo number_format($item["price"], 2, '.', ''); ?>" /> </td>
									<td><input class="form-control" type="text" id="inp_price_zakup" value="<?php echo number_format($detail["price_purchase"], 2, '.', ''); ?>" /> </td>
									<td><input class="form-control" type="text" id="inp_count_need" value="<?php echo $detail["count_reserved"]; ?>" /><?=$old_count_need;?></td>
									<td><?php echo number_format($item["price"]*$item["count_need"], 2, '.', ' '); ?></td>
									<td><?php echo number_format($detail["price_purchase_sum"], 2, '.', ''); ?></td>
								</tr>
								<?php
							}
						}
						else if($item_product_type == 2)
						{
							?>
							<tr>
								<td>
									<select id="inp_storage_id" class="form-control">
								<?php 
									foreach($storages_list as $k => $v){
										if($item["t2_storage_id"] == $k){
											echo '<option selected value="'.$k.'">'.$v.' ('.$k.')</option>';
										}else{
											echo '<option value="'.$k.'">'.$v.' ('.$k.')</option>';
										}
										 
									}
								?>
									</select>
								<td><input class="form-control" type="text" id="inp_price" value="<?php echo number_format($item["price"], 2, '.', ''); ?>" /> </td>
								<td><input class="form-control" type="text" id="inp_price_zakup" value="<?php echo number_format($item["price_purchase"], 2, '.', ''); ?>" /> </td>
								<td><input class="form-control" type="text" id="inp_count_need" value="<?php echo $item["count_need"]; ?>" /><?=$old_count_need;?></td>
								<td><?php echo number_format($item["price"]*$item["count_need"], 2, '.', ' '); ?></td>
								<td><?php echo number_format($item["price_purchase"]*$item["count_need"], 2, '.', ' '); ?></td>
							</tr>
							<?php
						}
						?>
							<tr>
								<td colspan="2"></td>
								<td><strong>Итого</strong></td>
								<td><strong><?php echo $item_count_need; ?></strong></td>
								<td><strong><?php echo number_format($item_price_sum, 2, '.', ' '); ?></strong></td>
								<td><strong><?php echo number_format($item_price_purchase_sum, 2, '.', ' '); ?></strong></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
			</td>
		</tr>
	</table>
						
	<?php
		$items_counter++;
	}//while() - по позициям
	?>
	<form id="save_form" name="save_form" method="POST" style="display:none;">
		<input type="hidden" name="save_action" id="save_action" value="update" />
		<input type="hidden" name="item_id" value="<?=$item_id;?>" />
		<input type="hidden" id="price" name="price" value="" />
		<input type="hidden" id="price_zakup" name="price_zakup" value="" />
		<input type="hidden" id="count_need" name="count_need" value="" />
		<input type="hidden" id="order_id" name="order_id" value="" />
		<input type="hidden" id="user_id" name="user_id" value="<?=$item_customer_id?>" />
		<input type="hidden" id="storage_id" name="storage_id" value="" />
		<input type="hidden" id="art" name="art" value="" />
		<input type="hidden" id="man" name="man" value="" />
		<input type="hidden" id="name" name="name" value="" />
		<input type="hidden" id="t2_time_to_exe" name="t2_time_to_exe" value="" />
		<input type="hidden" id="t2_time_to_exe_guaranteed" name="t2_time_to_exe_guaranteed" value="" />
		<input type="hidden" id="item_product_type" name="item_product_type" value="<?=$item_product_type;?>" />
	</form>
	<script>
	//Функция сохранения (отправка формы)

	document.getElementById('order_id_a').href = '/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?=$order_id;?>';
	function save_action(){
		document.getElementById('price').value = document.getElementById('inp_price').value;
		document.getElementById('price_zakup').value = document.getElementById('inp_price_zakup').value;
		document.getElementById('count_need').value = document.getElementById('inp_count_need').value;
		document.getElementById('order_id').value = document.getElementById('inp_order_id').value;
		document.getElementById('storage_id').value = document.getElementById('inp_storage_id').value;
		
		document.getElementById('art').value = document.getElementById('art_item_inp').value;
		document.getElementById('man').value = document.getElementById('man_item_inp').value;
		document.getElementById('name').value = document.getElementById('name_item_inp').value;
		
		document.getElementById('t2_time_to_exe').value = document.getElementById('t2_time_to_exe_item_inp').value;
		document.getElementById('t2_time_to_exe_guaranteed').value = document.getElementById('t2_time_to_exe_guaranteed_item_inp').value;
		
		document.forms["save_form"].submit();
	}
	</script>
	</div>
	</div>
	</div>
	</div>
<?php
}
?>