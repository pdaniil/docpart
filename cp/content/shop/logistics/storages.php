<?php
/**
 * Страница управления складами
 * 
 * Данная страница предназначена для создания/редактирования/удаления складов
*/
defined('_ASTEXE_') or die('No access');

$product_types = array(1=>"Treelax", 2=>"Docpart");
?>

<?php
if( ! empty($_POST["action"]))
{
    if($_POST["action"] == "delete_storages")
    {
        $storages = json_decode($_POST["storages"]);
        
        //Делаем строку с перечислением через запятую складов
        $storages_str = "";
        $binding_values = array();
		for($i=0; $i < count($storages); $i++)
        {
            if($i > 0) $storages_str .= ",";
            $storages_str .= "?";
			
			array_push($binding_values, $storages[$i]);
        }
        
        //Алгоритм удаления складов:
        //1. Удалить учетную запись склада (shop_storages)
        $delete_record_result = true;//Результат
        if( $db_link->prepare("DELETE FROM `shop_storages` WHERE `id` IN ($storages_str);")->execute($binding_values) != true)
        {
            $delete_record_result = false;//Результат
        }
        
        //2. Удаление привязок склада к магазинам (offices_storages_map)
        $delete_offices_storages_map_result = true;//Результат
		if( $db_link->prepare("DELETE FROM `shop_offices_storages_map` WHERE `storage_id` IN ($storages_str);")->execute( $binding_values ) != true)
        {
            $delete_offices_storages_map_result = false;//Результат
        }
        

        
        if($delete_record_result && $delete_offices_storages_map_result )
        {
            $success_message = "Выбранные склады успешно удалены";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages?success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
        else
        {
            $error_message = "Возникли ошибки: <br>";
            if(!$delete_record_result)
            {
                $error_message .= "Ошибка удаления учетной записи склада<br>";
            }
            if(!$delete_offices_storages_map_result)
            {
                $error_message .= "Ошибка удаления связей склада с магазинами<br>";
            }
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages?error_message=<?php echo $error_message; ?>";
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
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/storages/storage">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<?php
				//Ссылка на страницу "Свой IP"
				print_backend_button( array("url"=>"/content/usefull/ip.php", "background_color"=>"#ffc500", "fontawesome_class"=>"fas fa-network-wired", "caption"=>"Свой IP для проценки", "target"=>"_blank") );
				?>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/shop/logistics/storages/groups">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/modules.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Группы складов</div>
				</a>
				
				
				<a class="panel_a" onClick="deleteStorages();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>


				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
    
    
    <!-- Блок удаления складов -->
    <form name="delete_storages_form" method="POST">
        <input type="hidden" name="action" value="delete_storages" />
        <input type="hidden" name="storages" id="storages_to_delete" value="" />
    </form>
    <script>
        //Удаление складов
        function deleteStorages()
        {
            var storages = getCheckedElements();
            
            if(storages.length == 0)
            {
                alert("Выберите склады для удаления");
                return;
            }
            if(!confirm("Выбранные склады будут удалены. Продолжить?"))
            {
                return;
            }
            
            
            
            
            document.getElementById("storages_to_delete").value = JSON.stringify(storages);
            document.forms["delete_storages_form"].submit();
        }
    </script>
    
    
    
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Таблица складов
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th>ID</th>
								<th>Название</th>
								<th>Тип интерфейса</th>
								<th>Тип товара</th>
							</tr>
						</thead>
						<tbody>
						<?php
						
						//Массивы для JS с id элементов и с чекбоксами элементов
						$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
						$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
						
						
						$elements_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS *, (SELECT `name` FROM `shop_storages_interfaces_types` WHERE `id` = `shop_storages`.`interface_type`) AS `interface_type_name`, (SELECT `product_type` FROM `shop_storages_interfaces_types` WHERE `id` = `shop_storages`.`interface_type`) AS `product_type` FROM `shop_storages`;");
						$elements_query->execute();
						
						
						$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
						$elements_count_rows_query->execute();
						$elements_count_rows = $elements_count_rows_query->fetchColumn();
						
						
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
							
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/logistics/storages/storage?id=".$element_record["id"]." \">";
						?>
							<tr>
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/></td>
								<td><?php echo $a_item.$element_record["id"]; ?></a></td>
								<td><?php echo $a_item.$element_record["name"]; ?></a></td>
								<td><?php echo $a_item.$element_record["interface_type_name"]; ?></a></td>
								<td><?php echo $a_item.$product_types[$element_record["product_type"]]; ?></a></td>
							</tr>
						<?php
						}//for
						?>
						</tbody>
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
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="<?php echo "/".$DP_Config->backend_dir."/shop/logistics/storages?s_page=$i"; ?>"><?php echo $i; ?></a></li>
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
    
    <?php
}//~else - вывод страницы
?>