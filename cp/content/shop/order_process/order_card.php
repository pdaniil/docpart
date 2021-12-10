<?php
/**
 * Страница одного заказа
*/
defined('_ASTEXE_') or die('No access');

//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");

//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу
?>

<?php
if(!empty($_POST["action"]))
{
    
}
else//Действий нет - выводим страницу
{
    ?>
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    <?php
    $order_id = $_GET["order_id"];
    
	//Ставим флаг "Просмотрен"
	$db_link->prepare("UPDATE `shop_orders_viewed` SET `viewed_flag` = 1 WHERE `user_id` = ? AND `order_id` = ?;")->execute( array($manager_id, $order_id) );
	
	
	
	
	//Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
	$WHERE_statuses_not_count = "";
	for($i=0; $i<count($orders_items_statuses_not_count); $i++)
	{
		$WHERE_statuses_not_count .= " AND `status` != ".(int)$orders_items_statuses_not_count[$i];
	}
	
	//Для подсчета суммы оплаты по заказу
	$INCOME_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `order_id` = ?), 0)";
	$ISSUE_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `order_id` = ?),0)";
	
	
	//Для определения текущего баланса клиента
	$sub_balance_SQL = "";
	if( isset( $DP_Config->wholesaler ) )
	{
		$sub_balance_SQL = " AND `office_id` = `shop_orders`.`office_id` ";
	}
	$INCOME_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 1 AND `user_id` = `shop_orders`.`user_id` ".$sub_balance_SQL." ), 0)";
	$ISSUE_USER_SQL = "IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `active` = 1 AND `income` = 0 AND `user_id` = `shop_orders`.`user_id` ".$sub_balance_SQL." ),0)";
	
	
    //Получаем данные заказа
	$order_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get`) AS `obtain_caption`, (SELECT `handler` FROM `shop_obtaining_modes` WHERE `id` = `shop_orders`.`how_get`) AS `obtain_handler`, CAST( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(8,2)) AS `price_sum`, CAST( ($ISSUE_SQL - $INCOME_SQL) AS DECIMAL(8,2) ) AS `paid_sum`, CAST( ($INCOME_USER_SQL - $ISSUE_USER_SQL) AS DECIMAL(8,2) ) AS `customer_balance`, CAST( ( (SELECT SUM(`price`*`count_need`) FROM `shop_orders_items` WHERE `order_id`= `shop_orders`.`id` $WHERE_statuses_not_count ) - ($ISSUE_SQL - $INCOME_SQL) ) AS DECIMAL(8,2) )  AS `paid_left` FROM `shop_orders` WHERE `id` = ?;");
	$order_query->execute( array($order_id, $order_id, $order_id, $order_id, $order_id) );
    $order = $order_query->fetch();
    if( $offices_list[$order["office_id"]] == NULL )
    {
        exit("Заказ не найден или у Вас нет прав для работы с этим заказом");
    }
    
    $time = $order["time"];
    $office_id = $order["office_id"];
    $status = $order["status"];
    $paid = $order["paid"];
    $customer_id = $order["user_id"];
    $how_get = $order["how_get"];
	$obtain_caption = $order["obtain_caption"];
	$obtain_handler = $order["obtain_handler"];
	$price_sum = $order["price_sum"];
	$paid_sum = $order["paid_sum"];
	$paid_left = $order["paid_left"];
	$customer_balance = $order["customer_balance"];
    $how_get_json = json_decode($order["how_get_json"], true);
    ?>
    
    
    <div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Данные заказа
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						ID заказа
					</label>
					<div class="col-lg-9">
						<?php echo $order_id; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Создан
					</label>
					<div class="col-lg-9">
						<?php echo date("d.m.Y", $time)." ".date("G:i", $time); ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Офис обслуживания
					</label>
					<div class="col-lg-9">
						<?php echo $offices_list[$office_id]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Способ получения
					</label>
					<div class="col-lg-9">
						<?php echo $obtain_caption; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Статус
					</label>
					<div class="col-lg-9">
						<div class="input-group">
							<select id="status_selector" onchange="document.getElementById('apply_order_status_button').disabled = 0;" class="form-control">
								<?php
								foreach($orders_statuses as $key => $value)
								{
									$selected = "";
									if($status == $key)$selected = "selected=\"selected\"";
									?>
									<option value="<?php echo $key; ?>" <?php echo $selected; ?>><?php echo $orders_statuses[$key]["name"]; ?></option>
									<?php
								}
								?>
							</select>
							<span class="input-group-btn">
								<button onclick="setOrderStatus();" disabled="disabled" id="apply_order_status_button" type="button" class="btn btn-success">Применить</button>
							</span>
						</div>
						<script>
							//Функция - изменить статус заказа
							function setOrderStatus()
							{
								var needStatus = document.getElementById("status_selector").value;
							
								jQuery.ajax({
									type: "GET",
									async: false, //Запрос синхронный
									url: "/content/shop/protocol/set_order_status.php",
									dataType: "json",//Тип возвращаемого значения
									data: "initiator=1&orders=[<?php echo $order_id; ?>]&status="+needStatus,
									success: function(answer)
									{
										console.log(answer);
										if(answer.status == true)
										{
											//Обновляем страницу
											location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Выполнено упешно');
										}
										else
										{
											console.log(answer);
											alert("Ошибка изменения статуса");
										}
									}
								});
							}
						</script>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Действия
					</label>
					<div class="col-lg-9">
						<button class="btn btn-danger" type="button" onclick="deleteOrder();"><i class="fas fa-trash"></i> <span class="bold">Удалить заказ</span></button>
					</div>
				</div>
				<script>
				// --------------------------------------------------------------------------------------------
				//Функция удаления заказа
				function deleteOrder()
				{
					if( !confirm("Заказ будет безвозвратно удален. Продолжить?") )
					{
						return;
					}
					
					
					var orders_list = new Array();
					orders_list.push(<?php echo $order_id; ?>);
					
					jQuery.ajax({
						type: "POST",
						async: true, //Запрос асинхронный
						url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_delete_orders.php",
						dataType: "json",//Тип возвращаемого значения
						data: "orders_list="+JSON.stringify(orders_list),
						success: function(answer)
						{	
							if(answer.status == true)
							{
								//Обновляем страницу
								location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
							}
							else
							{
								alert(answer.message);
							}
						}
					});
				}
				// --------------------------------------------------------------------------------------------
				</script>
				
			</div>
		</div>
	</div>
	
	
    
    
	
	
	
	
	<?php
	/*
	Выводим блок оплаты, если заказ "Не оплачен" или "Частично оплачен"
	Выводим блок возврата, если заказ "Оплачен" или "Частично оплачен"
	*/
	?>
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Учет платежей по заказу
			</div>
			<div class="panel-body">
				
				
				<div class="panel-footer contact-footer">
					<div class="row">
						<div class="col-md-3">
							<div class="contact-stat">
								<span>Состояние: </span> 
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
							</div>
						</div>
						
						<div class="col-md-3">
							<div class="contact-stat">
								<span>Сумма заказа: </span> 
								<strong><?php echo $price_sum; ?></strong>
							</div> 
						</div>
							
						<div class="col-md-3">
							<div class="contact-stat">
								<span>Оплачено клиентом: </span>
								<strong><?php echo $paid_sum; ?></strong>
							</div>
						</div>
							
						<div class="col-md-3">
							<div class="contact-stat">
								<span>Долг клиента: </span>
								<strong><?php echo $paid_left; ?></strong>
							</div>
						</div>
					</div>
				</div>
				
				
				
				<?php
				//Блок добавления оплаты - выводим, если заказа еще оплачен не полностью
				if( $paid != 1 )
				{
					?>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-horizontal">
						<div class="form-group col-md-12 text-center">
							<label>Добавление оплаты в заказ:</label>
						</div>
						<?php
						//Варианты - только для зарегистрированного покупателя
						if( $customer_id > 0 )
						{
							?>
							<div class="form-group col-md-6">
								<div class="col-sm-12">
									<div class="radio">
										<label>
											<input type="radio" checked="" value="1" id="optionsRadios1" name="pay_source" /> Прямой платеж от клиента
										</label>
									</div>
									<div class="radio">
										<label>
											<input type="radio" value="0" id="optionsRadios2" name="pay_source" /> Платеж с текущего баланса клиента
										</label>
									</div>
								</div>
							</div>
							<?php
						}
						else
						{
							?>
							<div class="form-group col-md-6">
								<div class="col-sm-12">
									Клиент не зарегистрирован, поэтому, оплата с баланса не возможна. Доступен только прямой платеж от клиента.
								</div>
							</div>
							<?php
						}
						?>
						<div class="form-group col-md-6">
							<label>К заказу будет добавлен платеж на сумму:</label>
							<div class="input-group">
								<input type="number" class="form-control" placeholder="Введите сумму платежа" value="<?php echo $price_sum - $paid_sum; ?>" id="pay_value" />
								<span class="input-group-btn">
									<button onclick="add_payment_to_order();" type="button" class="btn btn-success">Добавить платеж</button>
								</span>
							</div>
						</div>
					</div>
					<script>
					//Обработка кнопки "Добавить платеж"
					function add_payment_to_order()
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
						if( pay_value > <?php echo $price_sum - $paid_sum; ?> || pay_value <= 0 )
						{
							alert('Сумма не должна превышать остаток долга клиента по заказу, не должна быть отрицательной, не должна быть равна 0');
							return;
						}
						
						
						
						
						//Источник оплаты
						var direct_pay = 1;//Прямая оплата заказа от клиента (т.е. добавляется приход и той же расход)
						<?php
						//Если клиент зарегистрирован, то, доступна возможность списания денег с баланса клиента
						if( $customer_id > 0 )
						{
							?>
							//Берем значение из радио-кнопок
							direct_pay = $('input[name="pay_source"]:checked').val();
							
							//Если оплата с баланса клиента, нужно проверить баланс. Если на балансе недостаточно средств, нужно предупредить продавца об этом и далее уже - на его усмотрение.
							if( direct_pay == 0 )
							{
								if( pay_value > <?php echo $customer_balance; ?> )
								{
									if( !confirm("На балансе клиента недостаточно средств. Если продожить, то, на балансе клиента возникнет перерасход. Продолжить?") )
									{
										return;
									}
								}
							}
							<?php
						}
						?>
						//Если прямая оплата от клиента, то, другие проверки не делаем.
						
						jQuery.ajax({
							type: "GET",
							async: false, //Запрос синхронный
							url: "/content/shop/protocol/pay_for_order.php",
							dataType: "text",//Тип возвращаемого значения
							data: "initiator=1&order_id=<?php echo $order_id; ?>&direct_pay="+direct_pay+"&pay_sum="+pay_value,
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
										location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Платеж успешно добавлен в заказ');
									}
									else
									{
										alert(answer_ob.message);
									}
								}
							}
						});
						
					}
					</script>
					<?php
				}
				?>
				
				
				
				
				
				<?php
				//Блок возврата оплаты выводим, только если по заказу есть оплата или предоплата
				if( $paid != 0 )
				{
					?>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-horizontal">
						<div class="form-group col-md-12 text-center">
							<label><button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Данный блок предназначен для отражения в системе учета платежей возврата оплаты по заказу. Возврат по заказу осуществляется, в случае, если ранее был отражен платеж по заказу и при этом заказ по каким-либо причинам не может быть выполнен, либо, если клиент вернул товар в магазин.<br><br>Для возврата:<br>1. Примите товар от клиента<br>2. Верните клиенту деньги (если клиент не хочет оставить их у себя на балансе)<br>3. При необходимости сформируйте фискальный чек<br>4. Затем, отразите возврат, нажав соответствующую кнопку ниже');"><i class="fa fa-info"></i></button> Возврат оплаты по заказу:</label>
						</div>
						<?php
						if( $customer_id > 0 )
						{
							?>
							<button onclick="refund(0);" class="btn btn-primary " type="button"><i class="fas fa-money-check-alt"></i> <span class="bold">Возрат на баланс клиента</span></button>
							
							<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Возврат оплаты на баланс клиента означает, что клиент сможет затем использовать денежные средства с баласа для оплаты других заказов.');"><i class="fa fa-info"></i></button>
							<?php
						}
						else
						{
							?>
							Покупатель не зарегистрирован, поэтому, возврат оплаты на баланс не возможен. Можно сделать только прямой возврат оплаты покупателю.<br>
							<?php
						}
						?>
						<button onclick="refund(1);" class="btn btn-primary " type="button"><i class="fas fa-hand-holding-usd"></i> <span class="bold">Прямой возрат на клиенту</span></button> <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Прямой возврат клиенту означает, что вы вернули клиенту деньги наличными или на карту. Таким образом, после фактического возврата денег, нажмите эту кнопку, чтобы отразить возврат в системе учета платежей по заказам.');"><i class="fa fa-info"></i></button>
					</div>
					<script>
					//Обработка кнопок возврата
					function refund(direct_refund)
					{
						if( !confirm('Отразить возврат в системе учета платежей по заказу?') )
						{
							return;
						}
						
						
						jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_order_pay_refund.php",
							dataType: "text",//Тип возвращаемого значения
							data: "direct_refund="+direct_refund+"&order_id=<?php echo $order_id; ?>",
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
										location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Возврат успешно отражен в системе учета платежей');
									}
									else
									{
										alert(answer_ob.message);
									}
								}
							}
						});
						
					}
					</script>
					<?php
				}
				?>
				
				

			</div>
		</div>
	</div>
	
	

	
	
	<div class="col-lg-12"></div>

	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Покупатель
			</div>
			<div class="panel-body">
				<?php
				$customer_profile = DP_User::getUserProfileById($customer_id);//Получаем данные покупателя

				if($customer_id > 0)
				{
					?>
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">ID клиента</label>
						<div class="col-lg-9">
						<?php
							echo $customer_id;
						?>
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<?php
					
					//Регистрационный вариант
					$all_reg_variants_query = $db_link->prepare("SELECT COUNT(*) FROM `reg_variants`");//Для получения количества всех вариантов
					$all_reg_variants_query->execute();
					if($all_reg_variants_query->fetchColumn() > 1)
					{
						//Теперь запрос своего варианта
						$user_reg_variant_query = $db_link->prepare("SELECT * FROM `reg_variants` WHERE `id` = ?;");
						$user_reg_variant_query->execute( array($customer_profile["reg_variant"]) );
						$user_reg_variant_record = $user_reg_variant_query->fetch();

						echo "<div class=\"form-group\"><label for=\"\" class=\"col-lg-3 control-label\">Вариант регистрации</label><div class=\"col-lg-9\">".$user_reg_variant_record["caption"]."</div></div> <div class=\"hr-line-dashed col-lg-12\"></div>";
					}//в противном случае не выводим регистрационный вариант
					
					
					//Баланс клиента
					?>
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">Баланс клиента</label>
						<div class="col-lg-9">
						<?php
							echo $customer_balance;
						?>
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<?php
					
					
					
					//Основные контакты
					?>
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">Телефон</label>
						<div class="col-lg-9">
						<?php
						if( !empty( $customer_profile['phone'] ) )
						{
							echo $customer_profile['phone'];
							
							if( $customer_profile['phone_confirmed'] == 1 )
							{
								?>
								<i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>
								<?php
							}
							else
							{
								?>
								<i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>
								<?php
							}
						}
						else
						{
							?>
							Не указан
							<?php
						}
						?>
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">E-mail</label>
						<div class="col-lg-9">
						<?php
						if( !empty( $customer_profile['email'] ) )
						{
							echo $customer_profile['email'];
							
							if( $customer_profile['email_confirmed'] == 1 )
							{
								?>
								<i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>
								<?php
							}
							else
							{
								?>
								<i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>
								<?php
							}
						}
						else
						{
							?>
							Не указан
							<?php
						}
						?>
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					<?php
					
					
				}
				
				$need_hr = false;
				
				
				//Перед выводом профиля получаем имена колонок таблицы users, чтобы отфильтровать их при выводе профиля
				$users_table_columns_query = $db_link->prepare("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE TABLE_NAME = 'users' AND `TABLE_SCHEMA` = '".$DP_Config->db."';");
				$users_table_columns_query->execute();
				$users_table_columns = array();
				while( $col_record =  $users_table_columns_query->fetch() )
				{
					$users_table_columns[] = $col_record['COLUMN_NAME'];
				}
				
			   
				foreach($customer_profile as $key => $value)
				{
					//Фильтруем все, что не относится к users_profiles и что не нужно показывать пользователю
					if( array_search($key, $users_table_columns ) !== false )
					{
						continue;
					}
					
					//Получаем название поля
					$parameter = "";
					if($key == "user_id")
					{
						$parameter = "ID пользователя";
					}
					else if($key == "groups")
					{
						$parameter = "Группы пользователя";
						$groups_names = "";
						//Получаем названия групп
						for($i=0; $i < count($value); $i++)
						{
							$group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `id` = ?;');
							$group_query->execute( array($value[$i]) );
							$group_record = $group_query->fetch();
							if($groups_names != "")
							{
								$groups_names .= ";<br>";
							}
							$groups_names .= $group_record["value"];
						}
						$value = $groups_names;//Для вывода
					}
					else
					{
						//Название из таблицы регистрационны полей
						$field_caption_query = $db_link->prepare('SELECT * FROM `reg_fields` WHERE `name`=?;');
						$field_caption_query->execute( array($key) );
						$field_caption_record = $field_caption_query->fetch();
						$parameter = $field_caption_record["caption"];
					}
					
					if($need_hr)
					{
						echo "<div class=\"hr-line-dashed col-lg-12\"></div>";
					}
					else
					{
						$need_hr = true;
					}
					?>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							<?php echo $parameter; ?>
						</label>
						<div class="col-lg-9">
							<?php echo $value; ?>
						</div>
					</div>
					<?php
				}//foreach($customer_profile AS $key => $value)
				?>
				
				<?php
				//Если покупатель не авторизован - показываем его контакты
				if( $customer_id == 0)
				{
					?>
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Статус покупателя
						</label>
						<div class="col-lg-9">
							Не зарегистрирован (ID 0)
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Телефон
						</label>
						<div class="col-lg-9">
							<?php echo $order["phone_not_auth"]; ?>
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							E-mail
						</label>
						<div class="col-lg-9">
							<?php echo $order["email_not_auth"]; ?>
						</div>
					</div>
					<?php
				}
				
				?>
			</div>
		</div>
	</div>
	
	
	
	
	<!-- Интерфейс менеджера для способа получения -->
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Управление получением товара
			</div>
			<div class="panel-body">
				<?php
				require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/$obtain_handler/manager_interface.php");
				?>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	
	
	
    
    
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Товарные позиции
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<?php
					// ---------- Start SAO ----------
					//Предварительно получаем список возможных SAO-действий:
					$sao_actions = array();
					
					$sao_actions_query = $db_link->prepare("SELECT * FROM `shop_sao_actions`");
					$sao_actions_query->execute();
					while( $sao_action = $sao_actions_query->fetch() )
					{
						$sao_actions[$sao_action["id"]] = array();
						$sao_actions[$sao_action["id"]]["name"] = $sao_action["name"];
						$sao_actions[$sao_action["id"]]["script"] = $sao_action["script"];
						$sao_actions[$sao_action["id"]]["fontawesome"] = $sao_action["fontawesome"];
						$sao_actions[$sao_action["id"]]["btn_class"] = $sao_action["btn_class"];
					}
					
					//Подключаем протокол выполнения действий
					$sao_propocol_mode = 1;//Режим работы протокола - страница заказа
					require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/sao/actions_exec_propocol.php");
					// ---------- End SAO ----------
					
					$items_counter = 0;//Счетчик позиций
					
					//ПОЛЯ ИТОГО ПО ЗАКАЗУ
					$count_need_total = 0;//Итого количество
					$price_sum_total = 0;//Итого сумма
					$price_purchase_sum_total = 0;//Итого закуп
					$profit_total = 0;//Итого маржа
					
					//ПОЛУЧАЕМ ВСЕ ПОЗИЦИИ ЗАКАЗА
					//Запрос наименований
					$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
					$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
					$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";
					
					//Запрос закупа
					$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`)) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(8,2)))";

					//Сумма позиции
					$SELECT_item_price_sum = "CAST(`price`*`count_need` AS DECIMAL(8,2))";
					
					//Маржа позиции
					$SELECT_item_profit = "CAST(`price`*`count_need` - $SELECT_price_purchase_sum AS DECIMAL(8,2))";
					
					
					//SAO
					$SELECT_item_sao_state = "IFNULL( (SELECT `name` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_background = "IFNULL( (SELECT `color_background` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_text = "IFNULL( (SELECT `color_text` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					//Получаем через запятую возможные дейстия для SAO для данного состояния и данного поставщика
					$SELECT_item_sao_actions = " IFNULL(( SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `shop_sao_actions` WHERE id IN (SELECT `action_id` FROM `shop_sao_states_types_actions_link` WHERE `state_type_id` = (SELECT `id` FROM `shop_sao_states_types_link` WHERE `state_id` = `shop_orders_items`.`sao_state` AND `interface_type_id` =  (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_orders_items`.`t2_storage_id` ) )) ), '')";

					
					
					//СЛОЖНЫЙ ВЛОЖЕННЫЙ ЗАПРОС
					$SELECT_ORDER_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, $SELECT_product_name AS `product_name_type_1`, $SELECT_price_purchase_sum AS `price_purchase_sum`, $SELECT_item_price_sum AS `price_sum`, $SELECT_item_profit AS `profit`, $SELECT_item_sao_state AS `sao_state_name`, $SELECT_item_sao_color_background AS `sao_state_color_background`, $SELECT_item_sao_color_text AS `sao_state_color_text`, $SELECT_item_sao_actions AS `sao_actions`, (SELECT COUNT(`id`) FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` = `shop_orders_items`.`id`) AS `checks_count` FROM `shop_orders_items` WHERE `order_id` = ? ";
					
					/*$sql_log_file = fopen("sql_log_file.txt", "w");
					fwrite($sql_log_file, $SELECT_ORDER_ITEMS);
					fclose($sql_log_file);*/
					

					$order_items_query = $db_link->prepare($SELECT_ORDER_ITEMS);
					$order_items_query->execute( array($order_id) );
					
					
					$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
					$elements_count_rows_query->execute();
					$elements_count_rows = $elements_count_rows_query->fetchColumn();
					
					
					//Массивы для JS с id элементов и с чекбоксами элементов
					$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
					$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
					
					$for_js = $for_js."var orders_items_ids_to_orders_items_objects = new Array();";//Для связи ID позиций заказов с их объектами (требуется для пробивки чеков)
					
					?>
					<table id="order_items_table" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>">
						<thead>
							<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
							<th data-toggle="true"></th>
							<th>ID</th>
							<th>Наименование</th>
							<th data-hide="phone">Цена</th>
							<th data-hide="phone">Кол-во</th>
							<th data-hide="phone">Сумма</th>
							<th data-hide="phone,tablet">Закуп</th>
							<th data-hide="phone,tablet">Маржа</th>
							<th data-hide="phone,tablet">Статус</th>
							<th data-hide="phone,tablet">Срок</th>
							<th data-hide="phone,tablet">Чеки</th>
							<th data-hide="phone,tablet,default">Данные склада</th>
						</thead>
						<tbody>
							<?php
							while( $order_item = $order_items_query->fetch() )
							{
								//Для Javascript
								$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$order_item["id"]."\";\n";//Добавляем элемент для JS
								$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$order_item["id"].";\n";//Добавляем элемент для JS
								
								
								$for_js = $for_js."orders_items_ids_to_orders_items_objects[".$order_item["id"]."] = {\"product_name\":\"".$order_item["product_name_type_1"]."\",\"price\":".$order_item["price"].",\"count_need\":".$order_item["count_need"]."};\n";//Добавляем элемент для JS
								
								
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
								
								//SAO
								$item_sao_state_name = $order_item["sao_state_name"];
								$item_sao_state = $order_item["sao_state"];
								$item_sao_state_color_background = $order_item["sao_state_color_background"];
								$item_sao_state_color_text = $order_item["sao_state_color_text"];
								$item_sao_actions = $order_item["sao_actions"];
								$item_sao_message = $order_item["sao_message"];
								
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
								
								
								//Чеки
								$item_checks_count = $order_item["checks_count"];
								if( $item_checks_count == 0 )
								{
									$item_checks_count = "Нет";
								}
								else
								{
									$item_checks_count = "<span onclick=\"show_order_item_checks(".$item_id.");\">".$item_checks_count." <i class=\"fas fa-search\"></i></span>";
								}
								?>
								
								<tr style="background-color:<?php echo $orders_items_statuses[$item_status]["color"]; ?>" id="order_item_record_<?php echo $item_id; ?>">
									<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $order_item["id"];?>');" id="checked_<?php echo $order_item["id"];?>" name="checked_<?php echo $order_item["id"];?>"/></td>
									<td></td>
									<td><?php echo $item_id; ?></td>
									<td id="order_item_name_<?php echo $item_id; ?>"><?php echo $item_product_name; ?></td>
									<td><?php echo number_format($item_price, 2, '.', ''); ?></td>
									<td><?php echo $item_count_need; ?></td>
									<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
									<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
									<td><?php echo number_format($item_profit, 2, '.', ''); ?></td>
									<td id="order_item_status_<?php echo $item_id; ?>"><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
									<td><?php echo $item_t2_time_to_exe; ?></td>
									<td><?php echo $item_checks_count; ?></td>
									<td>
										<div class="row">
											<div class="col-lg-12">
											
											
												<?php
												if(!$item["paid"])
												{
												?>
													<div style="position:relative; left:-70px; top:60px;">
														<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items/edit?id=<?=$item_id;?>" title="Редактировать позицию"><i style="font-size: 4em;" class="far fa-edit"></i></a>
													</div>
												<?php
												}
												?>
											
											
												<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
													<thead>
														<tr>
															<th rowspan="2" style="vertical-align:middle;">Склад</th>
															<th rowspan="2" style="vertical-align:middle;">ID поставки</th>
															<th rowspan="2" style="vertical-align:middle;">Цена закупа</th>
															<th rowspan="2" style="vertical-align:middle;">Количество</th>
															<th rowspan="2" style="vertical-align:middle;">Сумма закупа</th>
															<th colspan="3" style="text-align:center;">
																SAO
															</th>
														</tr>
														<tr>
															<th style="text-align:center;">Состояние</th>
															<th style="text-align:center;">Инфо</th>
															<th style="text-align:center;">Действия</th>
														</tr>
													</thead>
													
													<tbody>
													<?php
													//Выводим данные по поставкам. Логика зависит от типа продукта
													if($item_product_type == 1)
													{
														$details_query = $db_link->prepare("SELECT *, (`count_reserved`+`count_issued`)*`price_purchase` AS `price_purchase_sum`, `count_reserved`+`count_issued` AS `count_reserved_issued` FROM `shop_orders_items_details` WHERE `order_item_id` = ?;");
														$details_query->execute( array($item_id) );
														while( $detail = $details_query->fetch() )
														{
															?>
															<tr>
																<td><?php echo $storages_list[$detail["storage_id"]]; ?></td>
																<td><?php echo $detail["storage_record_id"]; ?></td>
																<td><?php echo number_format($detail["price_purchase"], 2, '.', ''); ?></td>
																<td><?php echo $detail["count_reserved_issued"]; ?></td>
																<td><?php echo number_format($detail["price_purchase_sum"], 2, '.', ''); ?></td>
																<td colspan="3">В каталоге SAO не поддерживается</td>
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
															<?php
															if( $item_sao_state > 0 )
															{
																?>
																<td style="background-color:<?php echo $item_sao_state_color_background; ?>; color:<?php echo $item_sao_state_color_text; ?>;vertical-align:middle;">
																	<?php echo $item_sao_state_name; ?>
																</td>
																<td>
																	<?php
																	if($item_sao_message != "")
																	{
																		echo $item_sao_message;
																	}
																	else
																	{
																		echo "-";
																	}
																	?>
																</td>
																<td>
																	<?php
																	if($item_sao_actions != "")
																	{
																		$item_sao_actions = explode(",", $item_sao_actions);
																		for($ac=0; $ac < count($item_sao_actions); $ac++)
																		{
																			?>
																			<button onclick="exec_action(<?php echo $item_id; ?>, <?php echo $item_sao_actions[$ac]; ?>);" class="btn <?php echo $sao_actions[$item_sao_actions[$ac]]["btn_class"]; ?> " type="button"><i class="fa <?php echo $sao_actions[$item_sao_actions[$ac]]["fontawesome"]; ?>"></i> <span class="bold"><?php echo $sao_actions[$item_sao_actions[$ac]]["name"]; ?></span></button>
																			<?php
																		}
																	}
																	else
																	{
																		?>
																		Доступных действий нет
																		<?php
																	}
																	?>
																</td>
																<?php
															}
															else
															{
																?>
																<td colspan="3">Поставщик не поддерживает SAO</td>
																<?php
															}
															?>
														</tr>
														<?php
													}
													?>
													</tbody>
													<tfoot>
														<tr>
															<td colspan="2"></td>
															<td><strong>Итого</strong></td>
															<td><strong><?php echo $item_count_need; ?></strong></td>
															<td><strong><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></strong></td>
															<td colspan="3"></td>
														</tr>
													</tfoot>
												</table>
											</div>
										</div>
									</td>
								</tr>
								<?php
								$items_counter++;
							}//while - по позициям заказа
							?>
						</tbody>
						<tfoot>
							<tr>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td><strong>Итого</strong></td>
								<td><strong><?php echo $count_need_total; ?></strong></td>
								<td><strong><?php echo $price_sum_total; ?></strong></td>
								<td><strong><?php echo $price_purchase_sum_total; ?></strong></td>
								<td><strong><?php echo $profit_total; ?></strong></td>
								<td></td>
								<td></td>
								<td></td>
							</tr>
						</tfoot>
					</table>
				</div>
			
				
			</div>
			<div class="panel-footer">
				<div class="row float-e-margins">
					<div class="col-lg-8">
						<button class="btn btn-info " type="button" onclick="create_check_for_orders_items();"><i class="fas fa-receipt"></i> Оформить кассовый чек</button>
						
						
						<a class="btn btn-success " href="/content/shop/print_docs/service/print.php?doc_name=sales_receipt&order_id=<?php echo $order_id; ?>" target="_blank"><i class="fa fa-print"></i> <span class="bold">Товарный чек</span></a>
						
						
						<a class="btn btn-success " href="/content/shop/print_docs/service/print.php?doc_name=invoice_for_payment&order_id=<?php echo $order_id; ?>" target="_blank"><i class="fa fa-print"></i> <span class="bold">Счет на оплату</span></a>
						
						
						<a class="btn btn-success" href="/content/shop/print_docs/service/print.php?doc_name=torg_12&order_id=<?php echo $order_id; ?>" target="_blank"><i class="fa fa-print"></i> <span class="bold">Накладная ТОРГ-12</span></a>
						
						
						<a class="btn btn-success" href="/content/shop/print_docs/service/print.php?doc_name=upd&order_id=<?php echo $order_id; ?>" target="_blank"><i class="fa fa-print"></i> <span class="bold">УПД</span></a>
						
						
						<a class="btn btn-success" href="/content/shop/print_docs/service/print.php?doc_name=upd_2021&order_id=<?php echo $order_id; ?>" target="_blank"><i class="fa fa-print"></i> <span class="bold">УПД (c 01.07.2021)</span></a>

						
						<a class="btn btn-success " type="button" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items/add?id=<?php echo $order_id; ?>"><i class="fa fa-plus"></i> <span class="bold">Добавить позицию</span></a>
						
					</div>
					
					
					
					<div class="col-lg-4">	
						<div class="input-group">
							<select id="setOrderItemsStatusSelect" class="form-control">
								<?php
								foreach($orders_items_statuses as $status_id=>$status_data)
								{
									?>
									<option value="<?php echo $status_id; ?>"><?php echo $status_data["name"]; ?></option>
									<?php
								}
								?>
							</select>
							<span class="input-group-btn">
								<button onclick="setOrderItemsStatus();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Присвоить статус</span></button>
							</span>
						</div>
						
					</div>
				</div>
			</div>
		</div>
	</div>
    
    
	
	<script>
		jQuery( window ).load(function() {
			$('#order_items_table').footable();
		});
	</script>
    
	
	

	
	<script>
        //Выставить статус для позиций заказа
        function setOrderItemsStatus()
        {
            var orders_items = getCheckedElements();//Список отмеченных заказов
            if(orders_items.length == 0)
            {
                alert("Выберите товарные позиции из списка");
                return;
            }
            
            var needStatus = document.getElementById("setOrderItemsStatusSelect").value;
            
            jQuery.ajax({
                    type: "GET",
                    async: false, //Запрос синхронный
                    url: "/content/shop/protocol/set_order_item_status.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "initiator=1&orders_items="+JSON.stringify(orders_items)+"&status="+needStatus,
                    success: function(answer)
                    {
                        console.log(answer);
                        if(answer.status == true)
                        {
                            //Обновляем страницу
                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>&success_message='+encodeURI('Выполнено');
                        }
                        else
                        {
							if( typeof answer.message != undefined )
							{
								alert(answer.message);
							}
							else
							{
								alert("Ошибка изменения статуса позиций");
							}
                        }
                    }
            	});
        }
    </script>

    

    <script>
    // ------------------------------------------------------------------------------------------------------
    //Скрыть / Открыть  информацию по поставкам
    function show_hide_storage_info(item_id)
    {
        var block = document.getElementById("storage_info_"+item_id);
	    if(block == undefined)
	    {
	        return;
	    }
	    
	    var a = document.getElementById("storage_info_button_"+item_id);
	    var state = block.getAttribute("state");
	    if(state == "hidden")
	    {
	        block.setAttribute("state", "shown");
	        $("#storage_info_"+item_id).show("fast");
	        a.innerHTML = "Скрыть данные склада";
	    }
	    else
	    {
	        block.setAttribute("state", "hidden");
	        $("#storage_info_"+item_id).hide(150);
	        a.innerHTML = "Открыть данные склада";
	    }
    }
    // ------------------------------------------------------------------------------------------------------
    </script>
    
    
	
	
	
	
	
	
	<!-- Переписка с покупателем -->
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Переписка с покупателем
			</div>
			<div class="panel-body">
				<div class="chat_block" id="chat_block">
				</div>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-12">
						<div class="input-group">
							<input type="text" id="new_message_area" class="form-control" />
							<span class="input-group-btn">
								<button onclick="sendMessage();" class="btn btn-success " type="button"><i class="fa fa-pencil"></i> <span class="bold">Оставить сообщение</span></button>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
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
			data: "manager=1&order_id=<?php echo $order_id; ?>",
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
			data: "manager=1&order_id=<?php echo $order_id; ?>&text="+encodeURIComponent(text),
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
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				История заказа
			</div>
			<div class="panel-body">
				<div id="order_log" style="height:150px;border:1px solid #EEE;border-radius:7px;padding:7px;overflow-y:scroll;">
			
				<?php
				$log_records = "";
				$log_query = $db_link->prepare("SELECT * FROM `shop_orders_logs` WHERE `order_id` = ? ORDER BY `id`;");
				$log_query->execute( array($order_id) );
				while($log = $log_query->fetch() )
				{
					//Имя инициатора действия:
					if($log["is_robot"])
					{
						$initiator_name = "Робот";
					}
					else if($log["is_manager"])
					{
						$initiator_profile = DP_User::getUserProfileById($log["user_id"]);
						$initiator_name = "Менеджер ".$initiator_profile["surname"];
					}
					else
					{
						if($log["user_id"] == 0)
						{
							$initiator_name = "Покупатель (не авторизован)";
						}
						else
						{
							$initiator_profile = DP_User::getUserProfileById($log["user_id"]);
							$initiator_name = "Покупатель ".$initiator_profile["surname"];
						}
					}
					$log_records .= date("Y-m-d H:i:s", $log["time"])." [$initiator_name] ".$log["text"]."<br>";
				}
				echo $log_records;
				?>
				</div>
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-12">
						<div class="input-group">
							<input id="new_message_area_log" class="form-control" />
							<span class="input-group-btn">
								<button onclick="addCommentToLog();" type="button" class="btn btn-success">
									<i class="fa fa-pencil"></i>
									<span class="bold">Комментарий</span>
								</button>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
    <script>
	document.getElementById("order_log").scrollTop = document.getElementById("order_log").scrollHeight;
	// -----------------------------------------------------------------------
	//Добавление комментария в лог
	function addCommentToLog()
	{
		var text = document.getElementById("new_message_area_log").value;
		if(text == "")
		{
			alert("Поле пустое");
			return;
		}
		
		jQuery.ajax({
			type: "GET",
			async: true,
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_add_comment_to_log.php",
			dataType: "json",//Тип возвращаемого значения
			data: "order_id=<?php echo $order_id; ?>&text="+text,
			success: function(answer)
			{
				if(answer.status == true)
				{
					location = "/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $order_id; ?>";
				}
				else
				{
					alert("Ошибка добавление коментария");
					console.log(answer);
				}
			}
		});
	}
	// -----------------------------------------------------------------------
	</script>
	
	
	
    
    
    
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
    <?php
}
?>