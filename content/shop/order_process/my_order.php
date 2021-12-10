<?php
/**
 * Страничный скрипт для отображения заказа покупателю
*/
defined('_ASTEXE_') or die('No access');

//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

//Общая информация по заказам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/orders_background.php");


if($user_id > 0)
{
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах выполнения действий
    
	
    $order_id = (int) $_GET["order_id"];
    
	
	//Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
	$WHERE_statuses_not_count = "";
	for($i=0; $i<count($orders_items_statuses_not_count); $i++)
	{
		$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
	}
	
	
	//Для подсчета суммы оплаты по заказу
	$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
	$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?),0)";
	
    //Получаем данные заказа
	$order_query = $db_link->prepare("SELECT *, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(8,2) ) AS `paid_sum`, CAST( ( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(8,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ? AND `user_id` = ?;");
	$order_query->execute( array($order_id, $order_id, $order_id, $order_id, $order_id, $user_id) );
    $order = $order_query->fetch();
    if( $offices_list[$order["office_id"]] == NULL )
    {
        echo("Заказ не найден");
    }else{
    
		$time = $order["time"];
		$office_id = $order["office_id"];
		$status = $order["status"];
		$paid = $order["paid"];
		$paid_sum = $order["paid_sum"];
		$paid_left = $order["paid_left"];
		$customer_id = $order["user_id"];
		$how_get = $order["how_get"];
		$how_get_json = json_decode($order["how_get_json"], true);
    ?>
    

    <table class="table">
        <tr> <td>Номер заказа</td> <td><?php echo $order_id; ?></td> </tr>
        <tr> <td>Создан</td> <td><?php echo date("d.m.Y", $time)." ".date("G:i", $time); ?></td> </tr>
        <tr> <td>Статус</td> <td><?php echo $orders_statuses[$status]["name"]; ?></td> </tr>
    </table>


	<p class="lead">Товарные позиции</p>
	
	<div style="overflow: hidden; overflow-x: auto;">
    <table class="table">
		<tr>
			<th class="hidden" style="vertical-align: middle; white-space: nowrap;"><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
			<th style="vertical-align: middle; white-space: nowrap;">ID</th>
			<th style="vertical-align: middle; white-space: nowrap;">Производитель</th>
			<th style="vertical-align: middle; white-space: nowrap;">Артикул</th>
			<th style="vertical-align: middle;">Наименование</th>
			<th style="vertical-align: middle; white-space: nowrap;">Цена</th>
			<th style="vertical-align: middle; white-space: nowrap; text-align:center;">Кол-во</th>
			<th style="vertical-align: middle; white-space: nowrap;">Сумма</th>
			<th style="vertical-align: middle; white-space: nowrap;">Срок</th>
			<th style="vertical-align: middle; white-space: nowrap;">Статус</th>
		</tr>

		<?php
		//ПОЛЯ ИТОГО ПО ЗАКАЗУ
		$count_need_total = 0;//Итого количество
		$price_sum_total = 0;//Итого сумма
		
		//ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
		
		//Запрос наименований
		$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
		$SELECT_type2_name = "CONCAT(`t2_name`)";
		$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name, ''), $SELECT_type2_name))";
		
		//Запрос артикула
		$SELECT_type1_article = "(SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`) AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = `shop_orders_items`.`product_id`)";
		$SELECT_type2_article = "CONCAT(`t2_article`)";
		$SELECT_product_article = "(CONCAT( IFNULL($SELECT_type1_article, ''), $SELECT_type2_article))";
		
		//Запрос производителя
		$SELECT_type1_manufacturer = "(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_orders_items`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = (SELECT `category_id` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`) AND `value` = 'Производитель' AND `property_type_id` = 5)))";
		$SELECT_type2_manufacturer = "CONCAT(`t2_manufacturer`)";
		$SELECT_product_manufacturer = "(CONCAT( IFNULL($SELECT_type1_manufacturer, ''), $SELECT_type2_manufacturer))";
		
		//Сумма позиции
		$SELECT_item_price_sum = "`price`*`count_need`";
		
		//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
		$SELECT_ORDER_ITEMS = "SELECT *, 
		$SELECT_product_name AS `product_name`, 
		$SELECT_item_price_sum AS `price_sum`, 
		$SELECT_product_article AS `article`, 
		$SELECT_product_manufacturer AS `manufacturer` 
		FROM `shop_orders_items` WHERE `order_id` = ?;";
		
		$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
		$order_items_query->execute( array($order_id) );
		
		//Массивы для JS с id элементов и с чекбоксами элементов
		$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
		$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов

		while( $order_item = $order_items_query->fetch() )
		{
			$item_id            = $order_item["id"];
			$item_status        = $order_item["status"];
			$item_count_need    = $order_item["count_need"];
			$item_price         = $order_item["price"];
			$item_price_sum     = $order_item["price_sum"];
			$item_product_type  = $order_item["product_type"];
			$item_product_id    = $order_item["product_id"];
			$item_product_name  = $order_item["product_name"];
			$item_product_manufacturer  = $order_item["manufacturer"];
			$item_product_article  = $order_item["article"];
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
			
			//Для Javascript
			$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$item_id."\";\n";//Добавляем элемент для JS
			$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$item_id.";\n";//Добавляем элемент для JS
			
			//Считаем поля ИТОГО ПО ЗАКАЗУ (если статус позиции позволяет)
			if( array_search($item_status, $orders_items_statuses_not_count) === false)
			{
				$count_need_total += $item_count_need;
				$price_sum_total += $item_price_sum;
			}
			
			?>
			
			<tr style="background:<?php echo $orders_items_statuses[$item_status]["color"]; ?>">
				<td class="hidden" style="vertical-align: middle;">
					<input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $item_id; ?>');" id="checked_<?php echo $item_id; ?>" name="checked_<?php echo $item_id; ?>"/>
				</td>
				<td style="vertical-align: middle;"><?php echo $item_id; ?></td>
				<td style="vertical-align: middle; white-space: nowrap;"><?php echo $item_product_manufacturer; ?></td>
				<td style="vertical-align: middle; white-space: nowrap;"><?php echo $item_product_article; ?></td>
				<td style="vertical-align: middle; width: 100%; min-width: 200px; max-width: 800px; word-wrap: break-word;"><?php echo $item_product_name; ?></td>
				<td style="vertical-align: middle; white-space: nowrap;"><?php echo number_format($item_price, 2, '.', ' '); ?></td>
				<td style="vertical-align: middle; white-space: nowrap; text-align:center;"><?php echo $item_count_need; ?></td>
				<td style="vertical-align: middle; white-space: nowrap;"><?php echo number_format($item_price_sum, 2, '.', ' '); ?></td>
				<td style="vertical-align: middle; white-space: nowrap;"><?php echo $item_t2_time_to_exe; ?></td>
				<td style="vertical-align: middle;"><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
			</tr>
			<?php
		}//while - по позициям заказа
		?>
		<tr style="font-weight:bold;">
			<td colspan="5" style="vertical-align: middle; white-space: nowrap; text-align:right;">Итого:</td>
			<td style="vertical-align: middle; white-space: nowrap; text-align:center;"><?php echo $count_need_total; ?></td>
			<td style="vertical-align: middle; white-space: nowrap;"><?php echo number_format($price_sum_total, 2, '.', ' '); ?></td>
			<td></td>
			<td></td>
		</tr>
	</table>
	</div>
	
	
	
	
	
	
	
	
	
	
	
	<div id="">
		<div class="panel panel-primary">
			<!--<div class="panel-heading">Платежи по заказу</div>-->
			<div class="panel-body">
				<div style="overflow: hidden; overflow-x: auto;">
					<p class="lead">Платежи по заказу</p>
				
				
					<table class="table">
						<tr>
							<td>Состояние оплаты:</td>
							<td>Сумма заказа:</td>
							<td>Оплачено:</td>
							<td>Осталось оплатить:</td>
						</tr>
						<tr>
							<td>
								<strong>
									<?php 
									switch( $paid )
									{
										case 0:
											echo '<div style="color:#FFF;background-color:#e74c3c;border-radius:3px;padding:6px 12px;font-weight:normal;">Не оплачен</div>';
											break;
										case 1:
											echo '<div style="color:#FFF;background-color:#62cb31;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен полностью</div>';
											break;
										case 2:
											echo '<div style="color:#FFF;background-color:#3498db;border-radius:3px;padding:6px 12px;font-weight:normal;">Оплачен частично</div>';
											break;
									}
									?>
								</strong>
							</td>
							<td><?php echo $price_sum_total; ?></td>
							<td><?php echo $paid_sum; ?></td>
							<td><?php echo $paid_left; ?></td>
						</tr>
					</table>
				</div>
				
				
				
				
				<?php
				//Блок добавления оплаты - выводим, если заказа еще оплачен не полностью
				if( $paid != 1 )
				{
					//Получаем баланс клиента
					$office_SQL = "";
					$balance_binging_values = array($user_id, $user_id, $user_id);
					if( isset( $DP_Config->wholesaler ) )
					{
						$office_SQL = " AND `office_id` = ? ";
						$balance_binging_values = array($user_id, $office_id, $user_id, $office_id, $user_id);
					}
					$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ? AND `income`=1 AND `active` = 1 ".$office_SQL."), 0)";
					$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = ? AND `income`=0 AND `active` = 1 ".$office_SQL."),0)";
					$balance_query = $db_link->prepare( "SELECT *, CAST( ($INCOME_SQL-$ISSUE_SQL) AS DECIMAL(8,2) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = ?;" );
					$balance_query->execute( $balance_binging_values );
					$balance_record = $balance_query->fetch();
					$balance = $balance_record["balance"];
					if($balance == ""){$balance = 0;}
					
					//Оплата с баланса доступна, если на балансе клиента достаточно денег для оплаты минимально-допустимого платежа с учетом настроек овердрафта
					$balance_pay_available = false;//Флаг доступности оплаты с баланса
					
					$sum_for_check_balance = $paid_left;//Сумма для проверки доступности оплаты с баланса. Если частичная оплата заказа выключена, то эта сумма равна $paid_left (т.е. с баланса должно хватить на оплату paid_left)
					if( $DP_Config->partial_payment )
					{
						//Включена частичная оплата заказа
						
						//Определяем сумму, менее которой клиент не сможет оплатить при неполной оплате
						$min_pay = $price_sum_total*($DP_Config->partial_payment_min_percent/100);
						
						$sum_for_check_balance = $min_pay;//Оплата с баланса будет доступна, если баланса достаточно для оплаты минимально-допустимого платежа
					}
					
					//Определяем максимальную сумму, которую клиент может потратить с баланса. Это нужно, если к примеру включена частичная оплата заказа, и денег на балансе достаточно, чтобы оплатить минимально-допустимый платеж, но НЕ достаточно, чтобы оплатить paid_left
					$balance_pay_limit = 0;
					
					
					if( $balance >= $sum_for_check_balance )
					{
						$balance_pay_available = true;//Можно использовать баланс для платежа. Далее определим лимит платежа с баланса.
						
						
						//Если частичная оплата заказа вЫключена, значит лимит равен остатку по заказу ($balance_pay_limit == $paid_left == $sum_for_check_balance).
						if( ! $DP_Config->partial_payment )
						{
							$balance_pay_limit = $paid_left;//Баланаса точно хватит
						}
						else
						{
							//Частичная предоплата включена
							
							if( $balance >= $paid_left )
							{
								$balance_pay_limit = $paid_left;//Баланса хватит, чтобы оплатить весь остаток по заказу
							}
							else
							{
								//Баланса не хватит, чтобы оплатить весь остаток. Определяем лимит с учетом настроек овердрафта
								
								if( $DP_Config->client_overdraft )
								{
									if( (int)$DP_Config->client_overdraft_value == 0 )
									{
										//Овердрафт не ограничен
										$balance_pay_limit = $paid_left;
									}
									else
									{
										//Можно будет потратить все, что есть на балансе, плюс доступный овердрафт
										$balance_pay_limit = $balance + (int)$DP_Config->client_overdraft_value;
									}
								}
								else//Овердрафт не допустим. Можно потратить только то, что есть на балансе.
								{
									$balance_pay_limit = $balance;
								}
							}
						}
					}
					else
					{
						//Денег на балансе не достаточно для оплаты минимально-допустимого платежа. Проверяем допустимость овердрафта.
						if( $DP_Config->client_overdraft )
						{
							if( (int)$DP_Config->client_overdraft_value == 0 )
							{
								//Овердрафт не ограничен
								$balance_pay_available = true;//Можно использовать баланс для платежа
								
								$balance_pay_limit = $paid_left;//Платежный лимит не ограничен - ставим равным долгу по заказу
							}
							else if( $sum_for_check_balance - $balance <= (int)$DP_Config->client_overdraft_value  )
							{
								//После оплаты минимально-допустимого платежа, овердрафт не будет превышен
								$balance_pay_available = true;//Оплата с баланса доступна
								
								$balance_pay_limit = (int)$DP_Config->client_overdraft_value + $balance;//Лимит - все, что есть на балансе, плюс доступный овердрафт
							}
						}
					}
					if( $balance_pay_limit == 0 )
					{
						$balance_pay_available = false;
					}
					
					?>
					<div class="form-horizontal">
						<?php
						//Если включена частичная оплата заказа
						if( $DP_Config->partial_payment )
						{
							?>
							<div class="col-md-6">
								<?php
								if($balance_pay_available)
								{
									//Показываем радио кнопки
									?>
									<label for="">Выберите способ оплаты:</label><br>
									<input type="radio" checked="checked" value="1" id="optionsRadios1" name="pay_source" /> <label for="optionsRadios1">Платеж через сайт</label>
									<br>
									<input type="radio" value="0" id="optionsRadios2" name="pay_source" /> <label for="optionsRadios2">Оплата с баланса (доступен платеж на сумму <?php echo $balance_pay_limit; ?>)</label>
									<?php
								}
								else
								{
									//Оплата с баланса не доступна. Радио-кнопок нет. Т.е. оплата будет только напрямую через сайт
								}
								?>
							</div>
							
							<div class="form-group col-md-6">
							<?php
							if( $paid_left <= $min_pay )
							{
								//Долг по заказу меньше, чем минимально-допустимый платеж.
								?>
								<input type="hidden" value="<?php echo $paid_left; ?>" id="pay_value" />
								<p>К оплате <?php echo $paid_left; ?></p>
								<button onclick="add_payment_to_order(2);" type="button" class="btn btn-ar btn-primary">Оплатить</button>
								<?php
							}
							else
							{
								//Долг по заказу больше, чем минимально-допустимый платеж. Клиент может сам определить желаемую сумму платежа
								?>
								<label>Укажите желаемую сумму оплаты:</label>
								<div class="header-search-box">
									<div class="input-group">
										<input style="padding-left:7px;!important;" type="number" class="form-control" placeholder="Введите сумму платежа" value="<?php echo $paid_left; ?>" id="pay_value" />
										<span class="input-group-btn">
											<button onclick="add_payment_to_order(2);" type="button" class="btn btn-ar btn-primary">Оплатить</button>
										</span>
									</div>
								</div>
								<?php
							}
							?>
							</div>
							<?php
						}
						else
						{
							//Частичная предоплата выключена. Платеж возможен только в сумме, равной paid_left (не больше и не меньше)
							?>
							<input type="hidden" value="<?php echo $paid_left; ?>" id="pay_value" />
							<?php
							//Кнопка "Оплатить online" доступна всегда
							?>
							<a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="add_payment_to_order(1);">Оплатить online</a>
							<?php
							//Кнопка "Оплатить с баланса"
							if( $balance_pay_available )
							{
								?>
								<a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="add_payment_to_order(0);">Оплатить с баланса</a>
								<?php
							}
						}
						?>
					</div>
					
					
					
					<script>
					//Обработка кнопки оплаты. direct_pay == 0 (оплата с баланса), direct_pay == 1 (прямая оплата), direct_pay == 2 (определить из радиокнопок)
					function add_payment_to_order(direct_pay)
					{
						//Сумма из поля ввода
						var pay_value = document.getElementById('pay_value').value;						
						pay_value = parseFloat(pay_value).toFixed(2);
						
						//Локальные проверки:
						
						//1. Должна быть указана сумма
						if( pay_value == '' || pay_value == 'NaN' )
						{
							alert('Укажите сумму платежа');
							return;
						}
						//2. Сумма не должна превышать остаток долга клиента по заказу, не должна быть отрицательной, не должна быть равна 0
						if( pay_value > <?php echo $paid_left; ?> || pay_value <= 0 )
						{
							alert('Сумма не должна превышать остаток долга по заказу, не должна быть отрицательной, не должна быть равна 0');
							return;
						}
						
						
						<?php
						//Если включена частичная оплата заказа - делаем проверки
						if( $DP_Config->partial_payment )
						{
							?>
							//Если желаемый платеж меньше оставшегося долга по заказу
							if( pay_value < <?php echo $paid_left; ?> )
							{
								//Проверяем, чтобы он был не менее минимально-допустимого платежа
								if( pay_value < <?php echo $min_pay; ?> )
								{
									alert('Минимальная платеж при частичной оплате заказа должен быть не менее <?php echo $DP_Config->partial_payment_min_percent; ?>% от общей суммы заказа. Минимально-допустимая сумма платежа: <?php echo $min_pay; ?>');
									return;
								}
							}
							<?php
						}
						?>
						
						
						
						
						
						//Если доступна частичная оплата и у клиента есть деньги на балансе - определяем способ оплаты из радиокнопок
						if( direct_pay == 2 )
						{
							//Берем значение из радио-кнопок
							direct_pay = $('input[name="pay_source"]:checked').val();
						}
						

						
						//Платеж с баланса
						if( direct_pay == 0 )
						{
							if( pay_value > <?php echo $balance_pay_limit; ?> )
							{
								alert('Доступны вам лимит при оплате с баланса: <?php echo $balance_pay_limit; ?>. Больше этой суммы потратить с баланса вы не можете');
								return;
							}
							
							
							<?php
							if( $balance < 0 )
							{
								?>
								if( !confirm('Внимание! На Вашем счете уже образовалась задолженность в сумме <?php echo $balance; ?>. При оплате этого заказа с баланса, задолженность перед магазином увеличится еще больше. Оплатить с баланса?') )
								{
									return;
								}
								<?php
							}
							else
							{
								?>
								if( pay_value > <?php echo $balance; ?> )
								{
									if( !confirm('Внимание! На вашем лицевом счету недостаточно средств для оплаты заказа. При оплате с баланса у Вас возникнет задолженность перед магазином. Оплатить с баланса?') )
									{
										return;
									}
								}
								<?php
							}
							?>

							
							jQuery.ajax({
								type: "GET",
								async: false, //Запрос синхронный
								url: "/content/shop/protocol/pay_for_order.php",
								dataType: "text",//Тип возвращаемого значения
								data: "initiator=2&order_id=<?php echo $order_id; ?>&direct_pay="+direct_pay+"&pay_sum="+pay_value,
								success: function(answer)
								{
									console.log(answer);
									var answer_ob = JSON.parse(answer);
					
									//Если некорректный парсинг ответа
									if( typeof answer_ob.status === "undefined" )
									{
										alert("Неизвестная ошибка");
									}
									else
									{
										//Корректный парсинг ответа
										if(answer_ob.status == true)
										{
											//Обновляем страницу
											location='/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Платеж успешно добавлен в заказ');
										}
										else
										{
											alert(answer_ob.message);
										}
									}
								}
							});
						}
						else
						{
							var request_object = new Object;
							request_object.order_id = <?php echo $order_id; ?>;
							request_object.amount = pay_value;
							
							jQuery.ajax({
								type: "POST",
								async: false, //Запрос синхронный
								url: "/content/shop/finance/ajax_create_operation.php",
								dataType: "text",//Тип возвращаемого значения
								data: "request_object="+encodeURI(JSON.stringify(request_object)),
								success: function(answer)
								{
									console.log(answer);
				
									var answer_ob = JSON.parse(answer);
									
									if( typeof answer_ob.result == 'undefined' )
									{
										alert("Ошибка парсинга результата");
									}
									else
									{
										if(answer_ob.result == true)
										{					
											if( answer_ob.pay_system == 0 )
											{
												alert("К сайту не подключена платежная система. Пополнение баланса возмжно через кассу");
												return;
											}
											else
											{
												location = "/content/shop/finance/payment_systems/"+answer_ob.pay_system+"/go_to_pay.php?operation="+answer_ob.operation;
											}
										}
										else
										{
											alert("Ошибка создания операции - сообщите продавцу");
										}
									}
								}
							});
						}
					}
					</script>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	
	

	
	
	<?php
	//Выводим кнопки для печати документов
	$print_docs_buttons = "";
	$print_docs_query = $db_link->prepare("SELECT * FROM `shop_print_docs` ORDER BY `id` ASC;");
	$print_docs_query->execute();
	while( $print_doc = $print_docs_query->fetch() )
	{
		$print_doc["parameters_values"] = json_decode($print_doc["parameters_values"], true);
		
		
		if( isset( $DP_Config->wholesaler ) )
		{
			$doc_query_2 = $db_link->prepare("SELECT * FROM `shop_print_docs_wholesaler` WHERE `doc_name` = ? AND `office_id` = ( SELECT `office_id` FROM `shop_orders` WHERE `id` = ? ) ;");
			$doc_query_2->execute( array( $print_doc["name"] , $order_id ) );
			$doc_record_2 = $doc_query_2->fetch();
			if( $doc_record_2 != false )
			{
				$print_doc["parameters_values"] = json_decode($doc_record_2["parameters_values"], true);
			}
		}
		
		
		if( (int)$print_doc["parameters_values"]["button_visible_for_customer"] != 1 )
		{
			continue;
		}
		
		if( $print_docs_buttons != "" )
		{
			$print_docs_buttons = $print_docs_buttons." ";
		}
		
		$print_docs_buttons = $print_docs_buttons."<a class=\"btn btn-ar btn-primary\" href=\"/content/shop/print_docs/service/print.php?doc_name=".$print_doc["name"]."&order_id=".$order_id."\" target=\"_blank\"><i class=\"fa fa-print\"></i> ".$print_doc["caption"]."</a>";
	}
	if( $print_docs_buttons != "" )
	{
		?>
		<div style="margin-top:50px;">
			<p class="lead">Печать документов</p>
			<?php echo $print_docs_buttons; ?>
		</div>
		<?php
	}
	?>
	
	
	<div style="overflow-x:auto; margin-top:50px;">
	<?php
	//2. ВЫВОДИМ СПОСОБ ПОЛУЧЕНИЯ
	//Получаем имя папки с обработчиком:
	$obtain_query = $db_link->prepare( 'SELECT * FROM `shop_obtaining_modes` WHERE `id` = ?;' );
	$obtain_query->execute( array($how_get) );
	$obtain_mode = $obtain_query->fetch();
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/".$obtain_mode["handler"]."/show_actual_info.php");
	?>
	</div>
	
	
	
    <script>
    // ----------------------------------------------------------------------------------------
    <?php
    echo $for_js;//Выводим массив с чекбоксами для элементов
    ?>
    //Обработка переключения Выделить все/Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
        for(var i=0; i<elements_array.length;i++)
        {
            document.getElementById(elements_array[i]).checked = state;
        }
    }//~function on_check_uncheck_all()
    // ----------------------------------------------------------------------------------------
    //Обработка переключения одного чекбокса
    function on_one_check_changed(id)
    {
        //Если хотя бы один чекбокс снят - снимаем общий чекбокс
        for(var i=0; i<elements_array.length;i++)
        {
            if(document.getElementById(elements_array[i]).checked == false)
            {
                document.getElementById("check_uncheck_all").checked = false;
                break;
            }
        }
    }//~function on_one_check_changed(id)
    // ----------------------------------------------------------------------------------------
    //Получение массива id отмеченых элементов
    function getCheckedElements()
    {
        var checked_ids = new Array();
        //По массиву чекбоксов
        for(var i=0; i<elements_array.length;i++)
        {
            if(document.getElementById(elements_array[i]).checked == true)
            {
                checked_ids.push(elements_id_array[i]);
            }
        }
        
        return checked_ids;
    }
    // ----------------------------------------------------------------------------------------
    </script>
	
	
	
	<!-- Переписка с покупателем -->
	<p class="lead">Переписка с продавцом</p>
	<div>
		<div class="chat_block" id="chat_block">
		</div>
		
		<br>
		Новое сообщение:
		<textarea id="new_message_area"></textarea>
		<button class="btn btn-ar btn-primary" onclick="sendMessage();">Отправить</button>
	</div>
	<script>
	// --------------------------------------------------------------------------
	//Получить сообщения по заказу
	function getOrderMessages()
	{
		jQuery.ajax({
			type: "GET",
			async: true,
			url: "/content/shop/messager/ajax_get_order_messages.php",
			dataType: "json",//Тип возвращаемого значения
			data: "order_id=<?php echo $order_id; ?>",
			success: function(answer)
			{
				var html = "";
				for(var i=0; i < answer.length; i++)
				{
					var class_str = "bubble";
					var sender = "Покупатель";
					if(answer[i].is_customer == false)
					{
						class_str += "2";
						sender = "Продавец";
					}
					html += "<div class=\""+class_str+"\">"+sender+" "+answer[i].time+"<br>"+answer[i].text+"</div>";	
				}
				if(html == "") html = "<div align=\"center\">Сообщений в данном заказе нет</div>";
				document.getElementById("chat_block").innerHTML = html;
				
				document.getElementById("chat_block").scrollTop = document.getElementById("chat_block").scrollHeight;
			}
		});
	}
	// --------------------------------------------------------------------------
	//Отправить сообщение
	function sendMessage()
	{
		var text = document.getElementById("new_message_area").value;
		if(text == "")
		{
			alert("Поле для сообщение пустое");
			return;
		}
		
		jQuery.ajax({
			type: "GET",
			async: true,
			url: "/content/shop/messager/ajax_send_message.php",
			dataType: "json",//Тип возвращаемого значения
			data: "order_id=<?php echo $order_id; ?>&text="+encodeURI(text),
			success: function(answer)
			{
				if(answer == true)
				{
					document.getElementById("new_message_area").value = "";
					getOrderMessages();
				}
				else
				{
					alert("Ошибка отправки сообщения");
				}
			}
		});
	}
	// --------------------------------------------------------------------------
	getOrderMessages();//Запрашиваем переписку по заказу
	
	setInterval(function(){
			getOrderMessages();
		}, 300000);
	</script>
<?php
	}
}
?>