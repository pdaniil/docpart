<?php
/**
 * Страница для вывода всех позиций всех заказов
*/
defined('_ASTEXE_') or die('No access');


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу



//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php")
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
	
	
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Фильтр позиций
			</div>
			<div class="panel-body">
				<?php
				$time_from = "";//1. Время с
				$time_to = "";//2. Время по
				$order_id = "";//3. ID заказа
				$order_status = "0";//4. Статус заказа
				$paid = -1;//5. Флаг - Заказ оплачен
				$customer = "";//6. Покупатель
				$order_item_status = "0";//7. Статус позиции
				$office_id = "0";//8. Офис обслуживания
				$product_name = "";//Подстрока наименования товара
				$viewed = -1;//Флаг "Заказ просмотрен"
				$storage_id = -1;//ID склада
				
				//Получаем текущие значения фильтра:
				$orders_items_filter = NULL;
				if( isset($_COOKIE["orders_items_filter"]) )
				{
					$orders_items_filter = $_COOKIE["orders_items_filter"];
				}
				if($orders_items_filter != NULL)
				{
					$orders_items_filter = json_decode($orders_items_filter, true);
					$time_from = $orders_items_filter["time_from"];
					$time_to = $orders_items_filter["time_to"];
					$order_id = $orders_items_filter["order_id"];
					$order_status = $orders_items_filter["order_status"];
					$paid = $orders_items_filter["paid"];
					$customer = $orders_items_filter["customer"];
					$order_item_status = $orders_items_filter["order_item_status"];
					$office_id = $orders_items_filter["office_id"];
					$product_name = $orders_items_filter["product_name"];
					$viewed = $orders_items_filter["viewed"];
					$storage_id = $orders_items_filter["storage_id"];
				}
				?>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата с
						</label>
						<div class="col-lg-6">
							<div style="position:relative;height:34px;">
								<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_from" value="<?php echo $time_from; ?>" class="form-control" />
								<input style="position:absolute; z-index:1;" type="text" id="time_from_show" class="form-control" />
								<script>
								//Инициализируем datetimepicker
								jQuery("#time_from").datetimepicker({
									lang:"ru",
									closeOnDateSelect:true,
									closeOnTimeSelect:false,
									dayOfWeekStart:1,
									format:'unixtime',
									onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
									if($time_from != "")
									{
										?>
										,
										onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
										{
											var time_string = "";
											var date_ob = new Date(current_time);
											time_string += date_ob.getDate()+".";
											time_string += (date_ob.getMonth() + 1)+".";
											time_string += date_ob.getFullYear()+" ";
											time_string += date_ob.getHours()+":"+date_ob.getMinutes();
											document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
										}
										<?php
									}
									?>
								});
								</script>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Дата по
						</label>
						<div class="col-lg-6">
							<div style="position:relative;height:34px;">
								<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_to" value="<?php echo $time_to; ?>" class="form-control" />
								<input style="position:absolute; z-index:1;" type="text" id="time_to_show" class="form-control" />
								<script>
								//Инициализируем datetimepicker
								jQuery("#time_to").datetimepicker({
									lang:"ru",
									closeOnDateSelect:true,
									closeOnTimeSelect:false,
									dayOfWeekStart:1,
									format:'unixtime',
									onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
									if($time_to != "")
									{
										?>
										,
										onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
										{
											var time_string = "";
											var date_ob = new Date(current_time);
											time_string += date_ob.getDate()+".";
											time_string += (date_ob.getMonth() + 1)+".";
											time_string += date_ob.getFullYear()+" ";
											time_string += date_ob.getHours()+":"+date_ob.getMinutes();
											document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
										}
										<?php
									}
									?>
								});
								</script>
							</div>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер заказа <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В одном заказе может быть несколько позиций. Номер позиции указан в таблице слева, а номер заказа справа. Этот фильтр Вы можете использовать, чтобы отобразить позиции нужного заказа.');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input type="text"  id="order_id" value="<?php echo $order_id; ?>" class="form-control" placeholder="Поиск по номеру заказа" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Оплачен
						</label>
						<div class="col-lg-6">
							<select id="paid" class="form-control">
								<option value="-1">Все</option>
								<option value="1">Оплачен полностью</option>
								<option value="2">Оплачен частично</option>
								<option value="0">Не оплачен</option>
							</select>
							<script>
								document.getElementById("paid").value = <?php echo $paid; ?>;
							</script>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Статус заказа
						</label>
						<div class="col-lg-6">
							<select id="order_status" class="form-control">
								<option value="0">Все</option>
								<?php
								foreach($orders_statuses as $status_id=>$status_data)
								{
									$selected = "";
									if($order_status == $status_id)
									{
										$selected = "selected=\"selected\"";
									}
									?>
									<option value="<?php echo $status_id; ?>" <?php echo $selected; ?>><?php echo $status_data["name"]; ?></option>
									<?php
								}
								?>
							<select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							
							<?php
							//Для подсказки для поля Клиент
							$fields_for_customer_search = "ID, E-mail, Телефон";
							//Дополнительно сюда выводим перечень полей профиля пользователя:
							$users_profile_fields_query = $db_link->prepare("SELECT `caption` FROM `reg_fields` WHERE `to_users_table` = 1;");
							$users_profile_fields_query->execute();
							while( $users_profile_field = $users_profile_fields_query->fetch() )
							{
								$fields_for_customer_search = $fields_for_customer_search.", ".$users_profile_field["caption"];
							}
							?>
						
							Клиент <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В это поле можно ввести одно из полей данных покупателя: <?php echo $fields_for_customer_search; ?>. Допустим поиск по частичному совпадению');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input type="text"  id="customer" value="<?php echo $customer; ?>" class="form-control" placeholder="Любое из полей покупателя" />
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Офис
						</label>
						<div class="col-lg-6">
							<select id="office_id" class="form-control">
								 <option value="0">Все</option>
								 <?php
								 foreach($offices_list as $office_id_key => $office_name)
								 {
									$selected = "";
									if($office_id == $office_id_key)
									{
										$selected = "selected=\"selected\"";
									}
									?>
									 <option value="<?php echo $office_id_key; ?>" <?php echo $selected; ?>><?php echo $office_name; ?></option>
									 <?php
								 }
								 ?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Статус позиции
						</label>
						<div class="col-lg-6">
							<select id="order_item_status" class="form-control">
								<option value="0">Все</option>
								<?php
								foreach($orders_items_statuses as $status_id=>$status_data)
								{
									$selected = "";
									if($order_item_status == $status_id)
									{
										$selected = "selected=\"selected\"";
									}
									?>
									<option value="<?php echo $status_id; ?>" <?php echo $selected; ?>><?php echo $status_data["name"]; ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Наименование товара <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Поиск по полям Артикул, Производитель, Наименование товара. Допустимо вводить часть строки');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<input type="text"  id="product_name" value="<?php echo $product_name; ?>" class="form-control" placeholder="Наименование товара" />
						</div>
					</div>
				</div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Заказ просмотрен
						</label>
						<div class="col-lg-6">
							<select id="viewed" class="form-control">
								<option value="-1">Все</option>
								<option value="1">Просмотрен</option>
								<option value="0">Не просмотрен</option>
							</select>
							<script>
								document.getElementById("viewed").value = <?php echo $viewed; ?>;
							</script>
						</div>
					</div>
				</div>
				
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Поставщик <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Поиск позиций по складам (или поставщикам). Для выбора доступны только те склады (поставщики), которые не были удалены. Если поставщик какой-либо позиции был удален, то найти ее можно только с выставленным значением &#34;Все&#34;');"><i class="fa fa-info"></i></button>
						</label>
						<div class="col-lg-6">
							<select id="storage_id" class="form-control">
								<option value="-1">Все</option>
								<?php
								$storages_query = $db_link->prepare("SELECT *, (SELECT `name` FROM `shop_storages_interfaces_types` WHERE `id` = `shop_storages`.`interface_type` ) AS `interface_type_name` FROM `shop_storages` ORDER BY `name`");
								$storages_query->execute();
								while( $storage = $storages_query->fetch() )
								{
									?>
									<option value="<?php echo $storage["id"]; ?>"><?php echo $storage["name"]; ?> (ID <?php echo $storage["id"]; ?>), <?php echo $storage["interface_type_name"]; ?></option>
									<?php
								}
								?>
							</select>
							<script>
								document.getElementById("storage_id").value = <?php echo $storage_id; ?>;
							</script>
						</div>
					</div>
				</div>
				
				
				

			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-12 float-e-margins">
						<button class="btn btn-success" type="button" onclick="filterOrdersItems();"><i class="fa fa-filter"></i> Отфильтровать</button>
						<button class="btn btn-primary" type="button" onclick="unsetFilterOrdersItems();"><i class="fa fa-square"></i> Снять фильтры</button>
					</div>
				</div>
			</div>
		</div>
	</div>
    
    
    
    
    

    <script>
    // ------------------------------------------------------------------------------------------------
    //Устновка cookie в соответствии с фильтром
    function filterOrdersItems()
    {
        var orders_items_filter = new Object;
        
        //1. Время с
        orders_items_filter.time_from = document.getElementById("time_from").value;
        //2. Время по
        orders_items_filter.time_to = document.getElementById("time_to").value;
        
        //3. Номер заказа
        orders_items_filter.order_id = document.getElementById("order_id").value;
        
        //4. Статус заказа
        orders_items_filter.order_status = document.getElementById("order_status").value;
        
        //5. Оплачен
        orders_items_filter.paid = document.getElementById("paid").value;
        
        //6. Покупатель
        orders_items_filter.customer = document.getElementById("customer").value;
        
        //7. Статус позиции
        orders_items_filter.order_item_status = document.getElementById("order_item_status").value;
        
        //8. Офис обслуживания
        orders_items_filter.office_id = document.getElementById("office_id").value;
        
		//9. Наименование товара
        orders_items_filter.product_name = document.getElementById("product_name").value;
		
		//10. Заказ просмотрен
		orders_items_filter.viewed = document.getElementById("viewed").value;
		
		//11. ID склада
		orders_items_filter.storage_id = document.getElementById("storage_id").value;
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_filter="+JSON.stringify(orders_items_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterOrdersItems()
    {
        var orders_items_filter = new Object;
        
        //1. Время с
        orders_items_filter.time_from = "";
        //2. Время по
        orders_items_filter.time_to = "";
        
        //3. Номер заказа
        orders_items_filter.order_id = "";
        
        //4. Статус заказа
        orders_items_filter.order_status = 0;
        
        //5. Товар
        orders_items_filter.paid = -1;
        
        //6. Покупатель
        orders_items_filter.customer = "";
        
        //7. Статус позиции
        orders_items_filter.order_item_status = 0;
        
        //8. Офис обслуживания
        orders_items_filter.office_id = 0;
		
		//9. Наименование
        orders_items_filter.product_name = "";
        
		//10. Заказ просмотрен
		orders_items_filter.viewed = -1;
		
		//11. ID склада
		orders_items_filter.storage_id = -1;
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_filter="+JSON.stringify(orders_items_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
    }
    // ------------------------------------------------------------------------------------------------
    </script>
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
   
    
    

    
    
    
    
    <script>
    // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки позиций заказов
    function sortOrdersItems(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("orders_items_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("orders_items_sort"));
            //Если поле это же - обращаем направление
            if(current_sort_cookie.field == field)
            {
                if(current_sort_cookie.asc_desc == "asc")
                {
                    asc_desc = "desc";
                }
                else
                {
                    asc_desc = "asc";
                }
            }
        }
        
        
        var orders_items_sort = new Object;
        orders_items_sort.field = field;//Поле, по которому сортировать
        orders_items_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_sort="+JSON.stringify(orders_items_sort)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
    }
    // ------------------------------------------------------------------------------------------------
    // возвращает cookie с именем name, если есть, если нет, то undefined
    function getCookie(name) 
    {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }
    // ------------------------------------------------------------------------------------------------
    //Переход на другую страницу заказа
    function goToPage(need_page)
    {
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_items_need_page="+need_page+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
    }
    // ------------------------------------------------------------------------------------------------
    </script>
	
	
	
	
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
					$sao_propocol_mode = 2;//Режим работы протокола - страница "Позиции заказов"
					require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/sao/actions_exec_propocol.php");
					// ---------- End SAO ----------
					
					
					
					//Определяем текущую сортировку и обозначаем ее:
					$orders_items_sort = NULL;
					if( isset($_COOKIE["orders_items_sort"]) )
					{
						$orders_items_sort = $_COOKIE["orders_items_sort"];
					}
					$sort_field = "id";
					$sort_asc_desc = "desc";
					if($orders_items_sort != NULL)
					{
						$orders_items_sort = json_decode($orders_items_sort, true);
						$sort_field = $orders_items_sort["field"];
						$sort_asc_desc = $orders_items_sort["asc_desc"];
					}
					
					if( strtolower($sort_asc_desc) == "asc" )
					{
						$sort_asc_desc = "asc";
					}
					else
					{
						$sort_asc_desc = "desc";
					}
					
					
					if( array_search($sort_field, array('id', 'product_name', 'price', 'count_need', 'price_sum', 'price_purchase_sum', 'profit', 'status', 'time', 'order_id', 'office_id', 't2_time_to_exe', 'customer', 'checks_count') ) === false )
					{
						$sort_field = "id";
					}
					
					
					//Формируем сложный SQL-запрос для получения всей информации по каждой позиции
					$binding_values = array();
					
					//Запрос времени оформления заказа
					$SELECT_time = "(SELECT `time` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					//Запрос наименований
					$SELECT_type1_name = "(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_orders_items`.`product_id`)";
					$SELECT_type2_name = "CONCAT(`t2_manufacturer`, ' ', `t2_article`, '. ', `t2_name`)";//Для типа продукта = 2
					$SELECT_product_name = "(CONCAT( IFNULL($SELECT_type1_name,''), $SELECT_type2_name))";

					//Запрос суммы позиции
					$SELECT_price_sum = "CAST(`price`*`count_need` AS DECIMAL(8,2))";
					//Запрос офисов обслуживания
					$SELECT_offices = "(SELECT `office_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					
					
					//Запрос клиента
					//Формируем подзапрос для значений профиля пользователя (только для тех полей, которые выводятся в таблицу пользователей в менеджер пользователей колонками)
					$users_profile_SQL = "";
					$users_profile_fields_query = $db_link->prepare("SELECT `name` FROM `reg_fields` WHERE `to_users_table` = 1;");
					$users_profile_fields_query->execute();
					while( $users_profile_field = $users_profile_fields_query->fetch() )
					{
						if( $users_profile_SQL != "" )
						{
							$users_profile_SQL = $users_profile_SQL.",";
						}
						
						//Допустимы только буквы и знаки нижнего подчеркивания
						$field_name = str_replace( array(' ', '#', '-', "'", '"'), '', $users_profile_field["name"] );
						
						$users_profile_SQL = $users_profile_SQL." IF( IFNULL((SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 )), '') != '' , CONCAT(', ', (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$field_name."' AND `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ))),'') ";
					}
					if( $users_profile_SQL != "" )
					{
						$users_profile_SQL = ",".$users_profile_SQL;
					}
					//SQL-подзапрос компонует строку с данными пользователя
					$SELECT_clients = " IF( (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` ) = 0, CONCAT('Незарегистрированный покупатель (ID 0), Телефон: ', (SELECT `phone_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`), IF( (SELECT `email_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)='', '', CONCAT(', E-mail: ', (SELECT `email_not_auth` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`) ))), CONCAT( 'ID ', (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` ), ', E-mail: ', (SELECT IF(`email`!='', `email`, 'Не указан') FROM `users` WHERE `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ) LiMIT 1 ), ', Телефон: ', (SELECT IF(`phone`!='', `phone`, 'Не указан') FROM `users` WHERE `user_id` = (SELECT `user_id` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id` LIMIT 1 ) LiMIT 1 ) ".$users_profile_SQL." ) )";
					
					
					
					//Запрос закупа
					$SELECT_price_purchase_sum = "IFNULL((SELECT SUM(`price_purchase`*(`count_reserved`+`count_issued`)) FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id`), CAST(`t2_price_purchase`*`count_need` AS DECIMAL(8,2)))";
					//Запрос маржы
					$SELECT_profit = "CAST(($SELECT_price_sum - $SELECT_price_purchase_sum) AS DECIMAL(8,2))";
					//Запрос статуса заказа
					$SELECT_order_status = "(SELECT `status` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					//Запрос флаг "Заказ оплачен"
					$SELECT_paid = "(SELECT `paid` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					//Запрос флага "Заказ просмотрен" viewed_flag
					$SELECT_viewed = " IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders_items`.`order_id` AND `user_id` = ? LIMIT 1), 1 ) ";
					
					array_push($binding_values, $manager_id);
					
					
					
					//SAO
					$SELECT_item_sao_state = "IFNULL( (SELECT `name` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_background = "IFNULL( (SELECT `color_background` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					$SELECT_item_sao_color_text = "IFNULL( (SELECT `color_text` FROM `shop_sao_states` WHERE `id` = `shop_orders_items`.`sao_state` ), '')";
					//Получаем через запятую возможные дейстия для SAO для данного состояния и данного поставщика
					$SELECT_item_sao_actions = " IFNULL(( SELECT GROUP_CONCAT(`id` SEPARATOR ',') FROM `shop_sao_actions` WHERE id IN (SELECT `action_id` FROM `shop_sao_states_types_actions_link` WHERE `state_type_id` = (SELECT `id` FROM `shop_sao_states_types_link` WHERE `state_id` = `shop_orders_items`.`sao_state` AND `interface_type_id` =  (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_orders_items`.`t2_storage_id` ) )) ), '')";
					
					
					
					//Данные о способе получения:
					$SELECT_how_get_caption = "(SELECT `caption` FROM `shop_obtaining_modes` WHERE `id` = (SELECT `how_get` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)  )";
					$SELECT_how_get_handler = "(SELECT `handler` FROM `shop_obtaining_modes` WHERE `id` = (SELECT `how_get` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)  )";
					$SELECT_how_get_json = "(SELECT `how_get_json` FROM `shop_orders` WHERE `id` = `shop_orders_items`.`order_id`)";
					
					
					
					
					//Фильтры
					$WHERE_CONDITIONS = " WHERE ";

					//По офисам обслуживания - только те, с которыми работает данный менеджер
					$sub_WHERE_offices = "";
					foreach($offices_list as $office_id => $office_caption)
					{
						if($sub_WHERE_offices != "")$sub_WHERE_offices .= ",";
						$sub_WHERE_offices .= "?";
						
						array_push($binding_values, $office_id);
					}
					$WHERE_CONDITIONS .= "$SELECT_offices IN ($sub_WHERE_offices)";

					//Ставим ПОЛЬЗОВАТЕЛЬСКИЕ фильтры
					$orders_items_filter = NULL;
					if( isset($_COOKIE["orders_items_filter"]) )
					{
						$orders_items_filter = $_COOKIE["orders_items_filter"];
					}
					if($orders_items_filter != NULL)
					{
						$orders_items_filter = json_decode($orders_items_filter, true);

						//1. Время с
						if($orders_items_filter["time_from"] != "")
						{
							$WHERE_CONDITIONS .= " AND $SELECT_time > ?";
							
							array_push($binding_values, $orders_items_filter["time_from"]);
						}

						//2. Время по
						if($orders_items_filter["time_to"] != "")
						{
							$WHERE_CONDITIONS .= " AND $SELECT_time < ?";
							
							array_push($binding_values, $orders_items_filter["time_to"]);
						}

						//3. Номер заказа
						if($orders_items_filter["order_id"] != "")
						{
							$WHERE_CONDITIONS .= " AND `order_id` = ?";
							
							array_push($binding_values, $orders_items_filter["order_id"]);
						}
						
						//4. Статус заказа
						if($orders_items_filter["order_status"] != 0 )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_order_status = ?";
							
							array_push($binding_values, $orders_items_filter["order_status"]);
						}
						
						//5. Оплата
						if($orders_items_filter["paid"] != -1 )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_paid = ?";
							
							array_push($binding_values, $orders_items_filter["paid"]);
						}
						
						//6. Покупатель
						if($orders_items_filter["customer"] != "" )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_clients  LIKE ?";
							
							array_push($binding_values, "%".htmlentities($orders_items_filter["customer"])."%");
						}
						
						//7. Статус позиции
						if($orders_items_filter["order_item_status"] != 0 )
						{
							$WHERE_CONDITIONS .= " AND `status` = ?";
							
							array_push($binding_values, $orders_items_filter["order_item_status"]);
						}

						//8. Офис обслуживания
						if($orders_items_filter["office_id"] != 0 )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_offices = ?";
							
							array_push($binding_values, $orders_items_filter["office_id"]);
						}

						//9. Наименование
						if($orders_items_filter["product_name"] != "" )
						{
							$WHERE_CONDITIONS .= " AND $SELECT_product_name LIKE ?";
							
							array_push($binding_values, '%'.$orders_items_filter["product_name"].'%');
						}
						
						//10. Заказ просмотрен
						if($orders_items_filter["viewed"] != -1 )
						{
							$WHERE_CONDITIONS .= " AND IFNULL( (SELECT `viewed_flag` FROM `shop_orders_viewed` WHERE `order_id` = `shop_orders_items`.`order_id` AND `user_id` = ? LIMIT 1), 1 ) = ?";
							
							
							array_push($binding_values, $manager_id);
							array_push($binding_values, $orders_items_filter["viewed"]);
						}
						
						
						//11. ID Склада
						if($orders_items_filter["storage_id"] != -1 )
						{
							$WHERE_CONDITIONS .= " AND IF( `t2_storage_id` = 0, IFNULL((SELECT `storage_id` FROM `shop_orders_items_details` WHERE `order_item_id` = `shop_orders_items`.`id` AND `storage_id` = ? LIMIT 1 ), 0) , `t2_storage_id` ) = ?";
							
							
							array_push($binding_values, $orders_items_filter["storage_id"]);
							array_push($binding_values, $orders_items_filter["storage_id"]);
						}
					}

					//ЗАПРОС 
					$SQL_SELECT_ITEMS = "SELECT SQL_CALC_FOUND_ROWS *, 
						$SELECT_product_name AS `product_name`, 
						$SELECT_price_sum AS `price_sum`, 
						$SELECT_offices AS `office_id`, 
						$SELECT_clients AS `customer`, 
						$SELECT_price_purchase_sum AS `price_purchase_sum`,
						$SELECT_profit AS `profit`,
						$SELECT_time AS `time`,
						$SELECT_order_status AS `order_status`,
						$SELECT_paid AS `paid`,
						$SELECT_viewed AS `viewed_flag`,
						$SELECT_item_sao_state AS `sao_state_name`,
						$SELECT_item_sao_color_background AS `sao_state_color_background`, 
						$SELECT_item_sao_color_text AS `sao_state_color_text`, 
						$SELECT_item_sao_actions AS `sao_actions`,
						$SELECT_how_get_caption AS `how_get_caption`,
						(SELECT COUNT(`id`) FROM `shop_kkt_checks_products_to_orders_items_map` WHERE `order_item_id` = `shop_orders_items`.`id`) AS `checks_count`
						FROM `shop_orders_items` $WHERE_CONDITIONS ORDER BY `$sort_field` $sort_asc_desc";
					

					
					$elements_query = $db_link->prepare($SQL_SELECT_ITEMS);
					$elements_query->execute($binding_values);
					
					$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
					$elements_count_rows_query->execute();
					$elements_count_rows = $elements_count_rows_query->fetchColumn();
					
					
					
					// -------------------------------------------------------------------
					//Получаем суммарные показатели: Сумма всех заказов (price_sum_total), Количество заказов (orders_count), Количество позиций (positions_count), Сумма маржи (profit_sum_total), Сумма закупа (price_purchase_sum_total)
					$SQL_SELECT_TOTAL_INDICATORS = "SELECT 
						SUM($SELECT_price_sum) AS `price_sum_total`,
						COUNT(*) AS `positions_count`,
						COUNT( DISTINCT(`order_id`) ) AS `orders_count`,
						SUM($SELECT_profit) AS `profit_sum_total`,
						SUM($SELECT_price_purchase_sum) AS `price_purchase_sum_total`
						FROM `shop_orders_items` $WHERE_CONDITIONS ";
					
					//Удаляем первый элемент массива связанных значений (это был manager_id для флага "Просмотрен")
					array_shift($binding_values);
					
					$total_indicators_query = $db_link->prepare($SQL_SELECT_TOTAL_INDICATORS);
					$total_indicators_query->execute($binding_values);
					$total_indicators = $total_indicators_query->fetch();
					// -------------------------------------------------------------------
					
					//Массивы для JS с id элементов и с чекбоксами элементов
					$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
					$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
					
					$for_js = $for_js."var orders_items_to_orders_map = new Array();\n";//Для связи позиций заказов и номеров заказов
					
					$for_js = $for_js."var orders_items_ids_to_orders_items_objects = new Array();";//Для связи ID позиций заказов с их объектами (требуется для пробивки чеков)
					
					//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
					//---------------------------------------------------------------------------------------------->
					//Определяем количество страниц для вывода:
					$p = $DP_Config->list_page_limit;//Штук на страницу
					$count_pages = (int)($elements_count_rows / $p);//Количество страниц
					if($elements_count_rows%$p)//Если остались еще элементы
					{
						$count_pages++;
					}
					//Определяем, с какой страницы начать вывод:
					$s_page = 0;
					if( isset($_COOKIE['orders_items_need_page']) )
					{
						$s_page = $_COOKIE['orders_items_need_page'];
						if($s_page >= $count_pages)
						{
							$s_page = $count_pages-1;//Чтобы не выходить за пределы
						}
					}
					$elements_counter = 0;
					//----------------------------------------------------------------------------------------------|
					?>
					<table id="orders_items_table" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>">
						<thead>
							<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();" /></th>
							<th data-toggle="true"></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('id');" id="id_sorter">ID</a></th>
							<th><a href="javascript:void(0);" onclick="sortOrdersItems('product_name');" id="product_name_sorter">Наименование</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('checks_count');" id="checks_count_sorter">Чеки</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price');" id="price_sorter">Цена</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('count_need');" id="count_need_sorter">Кол-во</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('price_sum');" id="price_sum_sorter">Сумма</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('price_purchase_sum');" id="price_purchase_sum_sorter">Закуп</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('profit');" id="profit_sorter">Маржа</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('status');" id="status_sorter">Статус</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('time');" id="time_sorter">Дата</a></th>
							<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrdersItems('order_id');" id="order_id_sorter">Заказ</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('office_id');" id="office_id_sorter">Офис</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('t2_time_to_exe');" id="t2_time_to_exe_sorter">Срок</a></th>
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('customer');" id="customer_id_sorter">Клиент</a></th>
							
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);" onclick="sortOrdersItems('how_get_caption');" id="how_get_caption_sorter">Способ доставки</a></th>
							
							<th data-hide="phone,tablet,default"><a href="javascript:void(0);">Подробнее</a></th>
							
							<th data-hide="phone,tablet,default">Данные склада</th>
						</thead>
						<tbody>
						<?php
						$items_counter = 0;
						while( $item = $elements_query->fetch() )
						{
							//Пропускаем нужное количество блоков в соответствии с номером требуемой страницы
							if($elements_counter < $s_page*$p)
							{
								$elements_counter++;
								continue;
							}
							if($elements_counter >= $s_page*$p+$p)
							{
								break;
							}
							$elements_counter++;
							//Для Javascript
							$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$item["id"]."\";\n";//Добавляем элемент для JS
							$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$item["id"].";\n";//Добавляем элемент для JS
							
							$for_js = $for_js."orders_items_to_orders_map[".$item["id"]."] = ".$item["order_id"].";\n";//Добавляем элемент для JS
							
							
							$for_js = $for_js."orders_items_ids_to_orders_items_objects[".$item["id"]."] = {\"product_name\":\"".$item["product_name"]."\",\"price\":".$item["price"].",\"count_need\":".$item["count_need"]."};\n";//Добавляем элемент для JS
							
							
							$item_id = $item["id"];
							$item_product_type = $item["product_type"];
							$item_status = $item["status"];
							$item_order_id = $item["order_id"];
							$item_product_name = $item["product_name"];
							$item_price = $item["price"];
							$item_count_need = $item["count_need"];
							$item_price_sum = $item["price_sum"];
							$item_office_id = $item["office_id"];
							$item_customer = $item["customer"];
							
							$item_how_get_caption = $item["how_get_caption"];
							
							$item_price_purchase_sum = $item["price_purchase_sum"];
							$item_profit = $item["profit"];
							$item_time = $item["time"];
							$item_t2_time_to_exe = $item["t2_time_to_exe"];
							$item_t2_time_to_exe_guaranteed = $item["t2_time_to_exe_guaranteed"];
							
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
							
							//Флаг "Заказ просмотрен"
							$viewed_class = "";
							$viewed_flag = $item["viewed_flag"];
							if( $viewed_flag == 0)
							{
								$viewed_class = " not_viewed";
							}
							
							
							

							
							//SAO
							$item_sao_state_name = $item["sao_state_name"];
							$item_sao_state = $item["sao_state"];
							$item_sao_state_color_background = $item["sao_state_color_background"];
							$item_sao_state_color_text = $item["sao_state_color_text"];
							$item_sao_actions = $item["sao_actions"];
							$item_sao_message = $item["sao_message"];
							
							
							//Чеки
							$item_checks_count = $item["checks_count"];
							if( $item_checks_count == 0 )
							{
								$item_checks_count = "Нет";
							}
							else
							{
								$item_checks_count = "<span onclick=\"show_order_item_checks(".$item_id.");\">".$item_checks_count." <i class=\"fas fa-search\"></i></span>";
							}
							?>
							

							<tr class="<?php echo $viewed_class; ?>" id="order_item_record_<?php echo $item_id; ?>" style="background-color:<?php echo $orders_items_statuses[$item_status]["color"]; ?>">
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $item_id; ?>');" id="checked_<?php echo $item_id; ?>" name="checked_<?php echo $item_id; ?>" /></td>
								<td></td>
								<td><?php echo $item_id; ?></td>
								<td><?php echo $item_product_name; ?></td>
								<td><?php echo $item_checks_count; ?></td>
								<td><?php echo number_format($item_price, 2, '.', ''); ?></td>
								<td><?php echo $item_count_need; ?></td>
								<td><?php echo number_format($item_price_sum, 2, '.', ''); ?></td>
								<td><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></td>
								<td><?php echo number_format($item_profit, 2, '.', ''); ?></td>
								<td id="order_item_status_name_td_<?php echo $item_id; ?>"><?php echo $orders_items_statuses[$item_status]["name"]; ?></td>
								<td><?php echo date("d.m.Y", $item_time)." ".date("G:i", $item_time); ?></td>
								<td>
									<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $item_order_id; ?>">
										Заказ ID <?php echo $item_order_id; ?><br>
										<font style="font-size:0.8em;">
										<?php
										if( $item["paid"] == 0 )
										{
											echo "Не оплачен";
										}
										else if( $item["paid"] == 1 )
										{
											echo "Оплачен полностью";
										}
										else
										{
											echo "Оплачен частично";
										}
										?>
										</font>
									</a>
								</td>
								<td><?php echo $offices_list[$item_office_id]; ?></td>
								<td><?php echo $item_t2_time_to_exe; ?></td>
								<td><?php echo $item_customer; ?></td>
								<td>
									<?php echo $item_how_get_caption; ?>
								</td>
								<td>
									<a class="btn btn-success " href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/orders/order?order_id=<?php echo $item_order_id; ?>" target="_blank"><i class="fa fa-search"></i> <span class="bold">Детальная информация заказа</span></a>
								</td>
								<td>
									<div class="row">
										<div class="col-lg-12">
										
											
											
											<?php
											if($item["paid"] == 0)
											{
											?>
												<div style="position:relative; left:-70px; top:60px;">
													<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items/edit?id=<?=$item_id;?>" title="Редактировать позицию"> <i style="font-size: 4em;" class="far fa-edit"></i></a>
												</div>
											<?php
											}
											?>
											
										
										
										
											<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped table-bordered">
												<thead>
													<tr>
														<th rowspan="2" style="vertical-align:middle;">Склад</th>
														<th rowspan="2" style="vertical-align:middle;">Поставка</th>
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
															<td><?php echo $storages_list[$detail["storage_id"]]; ?> (ID <?php echo $detail["storage_id"]; ?>)</td>
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
														<td><?php echo $storages_list[$item["t2_storage_id"]]; ?> (ID <?php echo $item["t2_storage_id"]; ?>)</td>
														<td><?php echo "-"; ?></td>
														<td><?php echo number_format($item["t2_price_purchase"], 2, '.', ''); ?></td>
														<td><?php echo $item["count_need"]; ?></td>
														<td><?php echo number_format($item["t2_price_purchase"]*$item["count_need"], 2, '.', ''); ?></td>
														<?php
														if( $item_sao_state > 0 )
														{
															?>
															<td id="order_item_sao_state_td_<?php echo $item_id; ?>" style="background-color:<?php echo $item_sao_state_color_background; ?>; color:<?php echo $item_sao_state_color_text; ?>;vertical-align:middle;">
																<?php echo $item_sao_state_name; ?>
															</td>
															<td id="order_item_sao_info_td_<?php echo $item_id; ?>">
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
															<td id="order_item_sao_actions_td_<?php echo $item_id; ?>">
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
													<tr>
														<td colspan="2"></td>
														<td><strong>Итого</strong></td>
														<td><strong><?php echo $item_count_need; ?></strong></td>
														<td><strong><?php echo number_format($item_price_purchase_sum, 2, '.', ''); ?></strong></td>
														<td colspan="3"></td>
													</tr>
												</tbody>
											</table>
										</div>
									</div>
								</td>
							</tr>
							<?php
							$items_counter++;
						}//while() - по позициям
						?>
						</tbody>
						<tfoot style="display:none;"><tr><td><ul class="pagination"></ul></td></tr></tfoot>
					</table>
				</div>
				
				
				<?php
				//START ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
				if( $count_pages > 1 )
				{
					?>
					<div class="row">
						<div class="col-lg-12 text-center">
							<div class="dataTables_paginate paging_simple_numbers">
								<ul class="pagination">
								<?php
								for($i=0; $i < $count_pages; $i++)
								{
									//Класс первой страницы
									$previous = "";
									if($i == 0) $previous = "previous";
									
									//Класс последней страницы
									$next = "";
									if($i == $count_pages-1) $next = "next";
									
									if($i == $s_page)//Текущая страница
									{
										?>
										<li class="paginate_button active <?php echo $previous; ?> <?php echo $next; ?>"><a href="javascript:void(0);"><?php echo $i; ?></a></li>
										<?php
									}
									else
									{
										?>
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="javascript:void(0);" onclick="goToPage(<?php echo $i; ?>);"><?php echo $i; ?></a></li>
										<?php
									}
								}
								?>
								</ul>
							</div>
						</div>
					</div>
				<?php
				}
				//END ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
				?>
					
				
			</div>
			<div class="panel-footer">
				<div class="row">
					<div class="col-lg-2">
						<button class="btn btn-info " type="button" onclick="create_check_for_orders_items();"><i class="fas fa-receipt"></i> Оформить кассовый чек</button>
					</div>
				
					<div class="col-lg-6">	
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
					
					
					
					<div class="col-lg-4">	
						<div class="input-group">
							<select id="setOrderViewed" class="form-control">
								<option value="1">Пометить заказ, как просмотренный</option>
								<option value="0">Пометить заказ, как не просмотренный</option>
							</select>
							<span class="input-group-btn">
								<button onclick="setOrderViewed();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Ok</span></button>
							</span>
						</div>
					</div>
					
					
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Показатели по позициям с учетом фильтра <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('В этом блоке приведены показатели по всем позициям с учетом фильтра. Если позиции в таблице выше не помещаются на одну страницу (т.е. требуется переключать страницы), то суммарные показатели рассчитываются, не смотря на это - со всех страниц по всем позициям, которые соответствуют настройкам фильтра (фильтр расположен вверху на данной странице).');"><i class="fa fa-info"></i></button>
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-lg-12 text-center">
						<div class="table-responsive">
							<table cellpadding="1" cellspacing="1" class="table">
								<thead>
									<tr>
										<th style="text-align:center;">Всего позиций</th>
										<th style="text-align:center;">Всего заказов</th>
										<th style="text-align:center;">Сумма по позициям</th>
										<th style="text-align:center;">Маржа по позициям</th>
										<th style="text-align:center;">Закуп по позициям</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td style="text-align:center;"><?php echo $total_indicators["positions_count"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["orders_count"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["price_sum_total"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["profit_sum_total"]; ?></td>
										<td style="text-align:center;"><?php echo $total_indicators["price_purchase_sum_total"]; ?></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	

	
	
	<script>
	// -----------------------------------------------------------------------------------------------------------
	//Установить статус просмотра для заказов
	function setOrderViewed()
	{
		var orders_items_checked = getCheckedElements();
		
		if(orders_items_checked.length == 0)
		{
			alert("Не отмечены позиции заказов");
			return;
		}
		
		//Далее получаем заказы
		var orders = new Array();
		for(var i=0; i < orders_items_checked.length; i++)
		{
			orders.push( orders_items_to_orders_map[orders_items_checked[i]] );
		}
		
		
		//Объект запроса
		var request_object = new Object;
		request_object.orders = orders;
		request_object.viewed_flag = document.getElementById("setOrderViewed").value;
		request_object.user_id = <?php echo $manager_id; ?>;

		//console.log(request_object);
		//return;
		
		jQuery.ajax({
			type: "POST",
			async: true, //Запрос асинхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_set_orders_viewed.php",
			dataType: "json",//Тип возвращаемого значения
			data: "request_object="+JSON.stringify(request_object),
			success: function(answer)
			{
				//console.log(answer);
				//return;
				
				if(answer.status == true)
				{
					//Обновляем страницу
					location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items';
				}
				else
				{
					console.log(answer);
					alert("Ошибка сервера");
				}
			}
		});
		
	}
	// -----------------------------------------------------------------------------------------------------------
	</script>
	
    
    
    
	
	
	<script>
		jQuery( window ).load(function() {
			$('#orders_items_table').footable();
			
			document.getElementById("<?php echo $sort_field; ?>_sorter").innerHTML += "<img src=\"/content/files/images/sort_<?php echo $sort_asc_desc; ?>.png\" style=\"width:15px\" />";
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
                        //console.log(answer);
                        if(answer.status == true)
                        {
                            //Обновляем страницу
                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/items?success_message='+encodeURI('Выполнено');
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