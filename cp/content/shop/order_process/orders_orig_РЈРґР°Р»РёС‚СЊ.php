<?php
/**
 * Страничный скрипт для отображения заказов
 * 
 * Заказы отображаются:
 * - от тех офисов, для которых данный пользователь назначен менеджером;
 * - в соответствии с фильтром;
 * - упорядоченные по определенному полю;
 * - ограниченный диапазон
*/
defined('_ASTEXE_') or die('No access');


//Технические данные для работы с заказами
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/order_process/orders_background.php");
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
				Фильтр заказов
			</div>
			<div class="panel-body">
				<?php
				$time_from = "";
				$time_to = "";
				$order_id = "";
				$status = "0";
				$paid = -1;
				$customer = "";
				//Получаем текущие значения фильтра:
				$orders_filter = $_COOKIE["orders_filter"];
				if($orders_filter != NULL)
				{
					$orders_filter = json_decode($orders_filter, true);
					$time_from = $orders_filter["time_from"];
					$time_to = $orders_filter["time_to"];
					$order_id = $orders_filter["order_id"];
					$status = $orders_filter["status"];
					$paid = $orders_filter["paid"];
					$customer = $orders_filter["customer"];
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
							Номер заказа
						</label>
						<div class="col-lg-6">
							<input type="text"  id="order_id" value="<?php echo $order_id; ?>" class="form-control" />
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
								<option value="1">Оплачен</option>
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
							Статус
						</label>
						<div class="col-lg-6">
							<select id="status" class="form-control">
								<option value="0">Все</option>
								<?php
								foreach($orders_statuses as $status_id=>$status_data)
								{
									$selected = "";
									if($status == $status_id)
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
							Клиент
						</label>
						<div class="col-lg-6">
							<input type="text"  id="customer" value="<?php echo $customer; ?>" class="form-control" />
						</div>
					</div>
				</div>
			</div>
			<div class="panel-footer">
				<button class="btn btn-success" type="button" onclick="filterOrders();"><i class="fa fa-filter"></i> Отфильтровать</button>
				<button class="btn btn-primary" type="button" onclick="unsetFilterOrders();"><i class="fa fa-square"></i> Снять фильры</button>
			</div>
		</div>
	</div>
	
	
	
	
    <script>
    // ------------------------------------------------------------------------------------------------
    //Устновка cookie в соответствии с фильтром
    function filterOrders()
    {
        var orders_filter = new Object;
        
        //1. Время с
        orders_filter.time_from = document.getElementById("time_from").value;
        //2. Время по
        orders_filter.time_to = document.getElementById("time_to").value;
        
        //3. Номер заказа
        orders_filter.order_id = document.getElementById("order_id").value;
        
        //4. Статус заказа
        orders_filter.status = document.getElementById("status").value;
        
        //5. Оплачен
        orders_filter.paid = document.getElementById("paid").value;
        
        //6. Покупатель
        orders_filter.customer = document.getElementById("customer").value;
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterOrders()
    {
        var orders_filter = new Object;
        
        //1. Время с
        orders_filter.time_from = "";
        //2. Время по
        orders_filter.time_to = "";
        
        //3. Номер заказа
        orders_filter.order_id = "";
        
        //4. Статус заказа
        orders_filter.status = 0;
        
        //5. Товар
        orders_filter.paid = -1;
        
        //6. Покупатель
        orders_filter.customer = "";
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
    }
    // ------------------------------------------------------------------------------------------------
    </script>

    
    
    
    
    
    
    
    <div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия с отмеченными
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-2 control-label">
						Присвоить статус
					</label>
					<div class="col-lg-8">
						<select id="setOrderStatusSelect" class="form-control">
							<?php
							foreach($orders_statuses as $status_id=>$status_data)
							{
								$selected = "";
								if($status == $status_id)
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
					<div class="col-lg-2">
						<button class="btn w-xs btn-success" onclick="setOrdersStatus();">Выполнить</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
    
    
    
    <script>
        //Выставить статус для заказов
        function setOrdersStatus()
        {
            var checkedOrders = getCheckedElements();//Список отмеченных заказов
            if(checkedOrders.length == 0)
            {
                alert("Выберите заказа из списка");
                return;
            }
            
            var needStatus = document.getElementById("setOrderStatusSelect").value;
            
            jQuery.ajax({
                    type: "GET",
                    async: false, //Запрос синхронный
                    url: "/content/shop/protocol/set_order_status.php",
                    dataType: "json",//Тип возвращаемого значения
                    data: "initiator=1&orders="+JSON.stringify(checkedOrders)+"&status="+needStatus,
                    success: function(answer)
                    {
                        console.log(answer);
                        if(answer.status == true)
                        {
                            //Обновляем страницу
                            location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
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
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    <script>
    // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки заказов
    function sortOrders(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("orders_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("orders_sort"));
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
        
        
        var orders_sort = new Object;
        orders_sort.field = field;//Поле, по которому сортировать
        orders_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "orders_sort="+JSON.stringify(orders_sort)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
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
        document.cookie = "orders_need_page="+need_page+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
    }
    // ------------------------------------------------------------------------------------------------
    </script>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Заказы
			</div>
			<div class="panel-body">
				<!--<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">-->
					<table id="orders_table" class="footable table table-bordered table-hover" >
						<thead>
							<tr>
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();" /></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('id');" id="id_sorter">ID</a></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('time');" id="time_sorter">Дата</a></th>
								<th><a href="javascript:void(0);" onclick="sortOrders('price_sum');" id="price_sum_sorter">Сумма</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('price_purchase');" id="price_purchase_sorter">Закуп</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('profit');" id="profit_sorter">Маржа</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('paid');" id="paid_sorter">Оплачен</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('status');" id="status_sorter">Статус</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('how_get');" id="how_get_sorter">Способ получения</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('customer');" id="customer_sorter">Покупатель</a></th>
								<th data-hide="phone,tablet"><a href="javascript:void(0);" onclick="sortOrders('office_id');" id="office_id_sorter">Офис</a></th>
							</tr>
							<script>
								<?php
								//Определяем текущую сортировку и обозначаем ее:
								$orders_sort = $_COOKIE["orders_sort"];
								$sort_field = "id";
								$sort_asc_desc = "desc";
								if($orders_sort != NULL)
								{
									$orders_sort = json_decode($orders_sort, true);
									$sort_field = $orders_sort["field"];
									$sort_asc_desc = $orders_sort["asc_desc"];
								}
								?>
								document.getElementById("<?php echo $sort_field; ?>_sorter").innerHTML += "<img src=\"/content/files/images/sort_<?php echo $sort_asc_desc; ?>.png\" style=\"width:15px\" />";
							</script>
						</thead>
						<tbody>
						<?php
						//Подстрока с условиями фильтрования заказов
						$WHERE_CONDITIONS = " WHERE ";
						
						//По офисам обслуживания - только те, с котроми работает данный менеджер
						$sub_WHERE_offices = "";
						foreach($offices_list as $office_id => $office_caption)
						{
							if($sub_WHERE_offices != "")$sub_WHERE_offices .= " OR ";
							$sub_WHERE_offices .= "`office_id`=$office_id";
						}
						$WHERE_CONDITIONS .= "(".$sub_WHERE_offices.")";
						
						//По куки фильтра:
						$orders_filter = $_COOKIE["orders_filter"];
						if($orders_filter != NULL)
						{
							$orders_filter = json_decode($orders_filter, true);
							
							//1. Время с
							if($orders_filter["time_from"] != "")
							{
								$WHERE_CONDITIONS .= " AND `time` > ".$orders_filter["time_from"];
							}
							
							//2. Время по
							if($orders_filter["time_to"] != "")
							{
								$WHERE_CONDITIONS .= " AND `time` < ".$orders_filter["time_to"];
							}
							
							//3. Номер заказа
							if($orders_filter["order_id"] != "")
							{
								$WHERE_CONDITIONS .= " AND `id` = ".$orders_filter["order_id"];
							}
							
							
							//4. Номер заказа
							if($orders_filter["status"] != 0 )
							{
								$WHERE_CONDITIONS .= " AND `status` = ".$orders_filter["status"];
							}
							
							
							//5. Оплата
							if($orders_filter["paid"] != -1 )
							{
								$WHERE_CONDITIONS .= " AND `paid` = ".$orders_filter["paid"];
							}
							
							
							//6. Покупатель
							if($orders_filter["customer"] != "" )
							{
								$WHERE_CONDITIONS .= " AND `user_id` = ".$orders_filter["customer"];
							}
						}
						
						
						//Подстрока с условиями фильтрования статусов позиций, которые не участвуют в ценовых расчетах
						$WHERE_statuses_not_count = "";
						$WHERE_statuses_not_count_without_and = "";
						for($i=0; $i<count($orders_items_statuses_not_count); $i++)
						{
							$WHERE_statuses_not_count .= " AND `status` != ".$orders_items_statuses_not_count[$i];
							
							if($i > 0)$WHERE_statuses_not_count_without_and .= " AND ";
							$WHERE_statuses_not_count_without_and .= " `status` != ".$orders_items_statuses_not_count[$i];
						}
						
						
						
						$SQL_SELECT_ORDERS = "SELECT `".$DP_Config->dbprefix."shop_orders`.`id` AS `id`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`time` AS `time`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`paid` AS `paid`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`status` AS `status`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`how_get` AS `how_get`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`status` AS `status`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`user_id` AS `customer`, ";
						$SQL_SELECT_ORDERS .= "`".$DP_Config->dbprefix."shop_orders`.`office_id` AS `office_id`, ";
						
						//Сумма заказа
						$sql_select_order_sum = " CAST( (SELECT SUM(`price`*`count_need`) FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` $WHERE_statuses_not_count ) AS DECIMAL(8,2)) ";
						$SQL_SELECT_ORDERS .= $sql_select_order_sum." AS `price_sum`,";
						
						//Сумма закупа
						//$sql_select_order_purchase = " CAST( ( (SELECT SUM(`price_purchase`*`count_reserved`) FROM `".$DP_Config->dbprefix."shop_orders_items_details` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` AND `order_item_id` IN (SELECT `id` FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE $WHERE_statuses_not_count_without_and) ) + (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id`) ) AS DECIMAL(8,2) ) ";
						//Вариант до 21.11.2015
						//$sql_select_order_purchase = " CAST( IFNULL( (SELECT SUM(`price_purchase`*`count_reserved`) FROM `".$DP_Config->dbprefix."shop_orders_items_details` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` AND `order_item_id` IN (SELECT `id` FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE $WHERE_statuses_not_count_without_and) ), (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` AND $WHERE_statuses_not_count_without_and) ) AS DECIMAL(8,2) ) ";
						
						$sql_select_order_purchase = "((CAST( IFNULL( (SELECT SUM(`price_purchase`*`count_reserved`) FROM `".$DP_Config->dbprefix."shop_orders_items_details` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` AND `order_item_id` IN (SELECT `id` FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE $WHERE_statuses_not_count_without_and) ), 0 ) AS DECIMAL(8,2) ) ) + (CAST( IFNULL( (SELECT SUM(`t2_price_purchase`*`count_need`) FROM `".$DP_Config->dbprefix."shop_orders_items` WHERE `order_id`= `".$DP_Config->dbprefix."shop_orders`.`id` AND $WHERE_statuses_not_count_without_and),  0) AS DECIMAL(8,2) ) ))";
						
						
						
						
						$SQL_SELECT_ORDERS .= $sql_select_order_purchase." AS `price_purchase`,";//Сумма закупа
						
						//Прибыль
						$SQL_SELECT_ORDERS .= " $sql_select_order_sum - $sql_select_order_purchase  AS `profit`";
						
						
						$SQL_SELECT_ORDERS .= " FROM `".$DP_Config->dbprefix."shop_orders` $WHERE_CONDITIONS ORDER BY `$sort_field` $sort_asc_desc";
						
						$elements_query = mysqli_query($db_link, $SQL_SELECT_ORDERS);
						
						
						//Массивы для JS с id элементов и с чекбоксами элементов
						$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
						$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
						
						//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
						//---------------------------------------------------------------------------------------------->
						//Определяем количество страниц для вывода:
						$p = $DP_Config->list_page_limit;//Штук на страницу
						$count_pages = (int)(mysqli_num_rows($elements_query) / $p);//Количество страниц
						if(mysqli_num_rows($elements_query)%$p)//Если остались еще элементы
						{
							$count_pages++;
						}
						//Определяем, с какой страницы начать вывод:
						$s_page = 0;
						if($_COOKIE['orders_need_page'] != NULL)
						{
							$s_page = $_COOKIE['orders_need_page'];
							if($s_page > $count_pages)
							{
								$s_page = $count_pages-1;//Чтобы не выходить за пределы
							}
						}
						$elements_counter = 0;
						//----------------------------------------------------------------------------------------------|
						
						
						while($element_record = mysqli_fetch_array($elements_query))
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
							$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
							$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
							
							
							$order_id = $element_record["id"];
							$time = $element_record["time"];
							$price_sum = $element_record["price_sum"];
							$price_purchase = $element_record["price_purchase"];
							$profit = $element_record["profit"];
							$paid = $element_record["paid"];
							$status = $element_record["status"];
							$how_get = $element_record["how_get"];
							$customer = $element_record["customer"];
							$office_id = $element_record["office_id"];
							
							
							
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/orders/order?order_id=".$order_id."\">";
							
							?>
							<tr>
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>" /></td>
							
								<td><?php echo $a_item.$order_id; ?></a></td>
								<td><?php echo $a_item.date("d.m.Y", $time)." ".date("G:i", $time); ?></a></td>
								<td><?php echo $a_item.number_format($price_sum, 2, '.', ''); ?></a></td>
								<td><?php echo $a_item.number_format($price_purchase, 2, '.', ''); ?></a></td>
								<td><?php echo $a_item.number_format($profit, 2, '.', ''); ?></a></td>
								<td><?php if($paid) echo $a_item."Да"; else echo $a_item."Нет"; ?></a></td>
								<td><?php echo $a_item.$orders_statuses[$status]["name"]; ?></a></td>
								<td>
									<?php
									if($how_get == 1)
									{
										echo $a_item."Самовывоз";
									}
									else
									{
										echo $a_item."Доставка";
									}
									?>
									</a>
								</td>
								<td><?php echo $a_item.$customer; ?></a></td>
								<td><?php echo $a_item.$offices_list[$office_id]; ?></a></td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				<!--</div>-->
				
				
				<?php
				//START ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
				if( $count_pages > 1 && false)
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
		</div>
	</div>
	
	
	<script>
		jQuery( window ).load(function() {
			$('#orders_table').footable();
		});
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
    
}//~else//Действий нет - выводим страницу
?>