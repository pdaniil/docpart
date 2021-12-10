<?php
/**
 * Скрипт для страницы менеджера прайс-листов
*/
defined('_ASTEXE_') or die('No access');


?>


<?php
if( ! empty($_POST["action"]))
{
    if($_POST["action"] == "delete_prices")
    {
        $prices = json_decode($_POST["prices"], true);
        
        //Делаем строку с перечислением через запятую прайс-листов
        $binding_values = array();
		$prices_str = "";
        for($i=0; $i < count($prices); $i++)
        {
            if($i > 0) $prices_str .= ",";
            $prices_str .= "?";
			
			array_push($binding_values, $prices[$i]);
        }
        
        
        //Удаляем учетную запись (записи) прайс-листа
        $records_delete_result = true;//Результат
        if( $db_link->prepare("DELETE FROM `shop_docpart_prices` WHERE `id` IN ($prices_str);")->execute($binding_values) != true)
        {
            $records_delete_result = false;//Результат
        }
        
        
        //Удаляем данные прайс-листов
        $prices_data_delete_result = true;
        if( $db_link->prepare("DELETE FROM `shop_docpart_prices_data` WHERE `price_id` IN ($prices_str);")->execute($binding_values) != true)
        {
            $prices_data_delete_result = false;//Результат
        }
		
		
        if($records_delete_result && $prices_data_delete_result)
        {
            $success_message = "Выбранные прайс-листы успешно удалены";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/prices?success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
        else
        {
            $error_message = "Возникли ошибки: <br>";
            if(!$records_delete_result)
            {
                $error_message .= "Ошибка удаления учетной записи прайс-листа<br>";
            }
            if(!$prices_data_delete_result)
            {
                $error_message .= "Ошибка удалени данных из прайс-листов<br>";
            }
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/prices?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        
        
        
        
        
        
        
    }
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
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/price">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<a class="panel_a" href="javascript:void(0);" onclick="deletePrices();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>


				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
    
    
    
    
    
    
    
    <!-- Блок удаления прайс-листов -->
    <form name="delete_prices_form" method="POST">
        <input type="hidden" name="action" value="delete_prices" />
        <input type="hidden" name="prices" id="prices_to_delete" value="" />
    </form>
    <script>
        //Удаление прайс-листов
        function deletePrices()
        {
            var prices = getCheckedElements();
            
            if(prices.length == 0)
            {
                alert("Выберите прайс-листы для удаления");
                return;
            }
            if(!confirm("Выбранные прайс-листы будут удалены. Продолжить?"))
            {
                return;
            }
            
            
            
            
            document.getElementById("prices_to_delete").value = JSON.stringify(prices);
            document.forms["delete_prices_form"].submit();
        }
    </script>
    
    
    
    
    
    
    
    
    
    
    <?php
    //Получим способы загрузки прайс-листов
    $load_modes = array();
	$load_modes_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices_load_modes`");
    $load_modes_query->execute();
    while($load_mode = $load_modes_query->fetch() )
    {
        $load_modes[$load_mode["id"]] = $load_mode["name"];
    }
    ?>
    
    
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Таблица прайс-листов
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					
					<?php
					//Массивы для JS с id элементов и с чекбоксами элементов
					$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
					$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
					
					
					$elements_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS *, (SELECT COUNT(`id`) FROM `shop_docpart_prices_data` WHERE `price_id` = `shop_docpart_prices`.`id`) AS `records_count` FROM `shop_docpart_prices`;");
					$elements_query->execute();
					
					
					$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
					$elements_count_rows_query->execute();
					$elements_count_rows = $elements_count_rows_query->fetchColumn();
					?>
					
					
					
					<table cellpadding="1" cellspacing="1" class="footable table table-hover toggle-arrow " data-sort="false" data-page-size="<?php echo $elements_count_rows; ?>" id="prices_table">
					
					<!--<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">-->
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th data-toggle="true"></th>
								<th>ID</th>
								<th>Название</th>
								<th>Последнее обновление</th>
								<th>Количество записей</th>
								<th>Способ загрузки</th>
								<th class="text-center">Загрузка<br>файла</th>
								<th class="text-center">Действия</th>
								<th style="text-align:center;" data-hide="phone,tablet,default"></th>
							</tr>
						</thead>
						<tbody>
						<?php
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
						if(!empty($_GET['s_page']))
						{
							$s_page = $_GET['s_page'];
						}
						//----------------------------------------------------------------------------------------------|
						
						
						for($i=0, $d=0; $i<$elements_count_rows && $d<$p; $i++, $d++)
						{
							$element_record = $elements_query->fetch();
							
							//Пропускаем нужное количество блоков в соответствии с номером требуемой страницы
							if($i < $s_page*$p)
							{
								$d--;
								continue;
							}
							
							//Для Javascript
							$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
							$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
							
						?>
							<?php
							//Последнее обновление
							if($element_record["last_updated"] == 0)
							{
								$element_record["last_updated"] = "Никогда";
							}
							else
							{
								$element_record["last_updated"] = date("d.m.Y H:i:s", $element_record["last_updated"]);
							}
							?>
							
							<?php
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/prices/price?price_id=".$element_record["id"]." \">";
							?>
						
						
							<tr>
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/></td>
								<td></td>
								<td><?php echo $a_item.$element_record["id"]; ?></a></td>
								<td><?php echo $a_item.$element_record["name"]; ?></a></td>
								<td><?php echo $a_item.$element_record["last_updated"]; ?></a></td>
								<td><?php echo $a_item.$element_record["records_count"]; ?></a></td>
								<td><?php echo $a_item.$load_modes[$element_record["load_mode"]]; ?></a></td>
								<td class="text-center">
									<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/upload?price_id=<?php echo $element_record["id"]; ?>">
										<img src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/upload.png" title="Ручная загрузка" class="col_img_popup" style="margin:0px 4px 8px 4px;">
									</a>
								</td>
								
								
								<td class="text-center">
									<a href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/review?price_id=<?php echo $element_record["id"]; ?>" title="Простановка цен на основе других прайс-листов"><i class="fas fa-sync"></i></a>
								</td>
								
								
								<td style="text-align:center;" id="price_preview_<?php echo $element_record["id"]; ?>">
									<div class="text-center">
									Здесь можно посмотреть, корректно ли загрузился файл.<br>
									<img src="/content/files/images/ajax-loader-transparent.gif" /><br>
									Пожалуйста, подождите...
									</div>
								</td>
								<script>
								jQuery.ajax({
									type: "POST",
									async: true, //Запрос синхронный
									url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_upload/ajax_get_price_preview.php",
									dataType: "text",//Тип возвращаемого значения
									data: "price_id=<?php echo $element_record["id"]; ?>",
									success: function(answer)
									{
										document.getElementById("price_preview_<?php echo $element_record["id"]; ?>").innerHTML = answer;
									}
								}); 
								</script>
								
							</tr>
						<?php
						}//for
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
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="<?php echo "/".$DP_Config->backend_dir."/shop/prices?s_page=$i"; ?>"><?php echo $i; ?></a></li>
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
    </script>
    
	
	<script>
		jQuery( window ).load(function() {
			$('#prices_table').footable();
		});
	</script>
	
    <?php
}//~else - вывод страницы
?>