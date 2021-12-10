<?php
//Страничный скрипт для страницы с перечнем табов поиска
defined('_ASTEXE_') or die('No access');
?>

<?php
if( !empty( $_POST["action"] ) )
{	
	//Для открытия той же страницы
    $s_page = "";
    if(!empty($_POST['s_page']))
	{
	    $s_page = "&s_page=".$_POST['s_page'];
	}
	
	if( $_POST["action"] == "activation" )
	{
		if($_POST["activate_tab"] == 1)
		{
			//Активируем таб
			$activated_result = $db_link->prepare("UPDATE `shop_docpart_search_tabs` SET `enabled` = 1 WHERE `id`=?;")->execute( array($_POST["tab_id"]) );
		}
		else
		{
			//Деактивируем таб
			$activated_result = $db_link->prepare("UPDATE `shop_docpart_search_tabs` SET `enabled` = 0 WHERE `id`=?;")->execute( array($_POST["tab_id"]) );
		}
		if($activated_result != false)
		{
			//Переадресация с сообщением о результатах выполнения
			$success_message = "Выполнено успешно";
			?>
			<script>
				location="/<?php echo $DP_Config->backend_dir; ?>/shop/taby-poiska?success_message=<?php echo $success_message.$s_page; ?>";
			</script>
			<?php
		}
		else
		{
			//Переадресация с сообщением о результатах выполнения
			$error_message = "Ошибка";
			?>
			<script>
				location="/<?php echo $DP_Config->backend_dir; ?>/shop/taby-poiska?error_message=<?php echo $error_message.$s_page; ?>";
			</script>
			<?php
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
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Табы поиска
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th>ID</th>
								<th>Название</th>
								<th>Порядок отображения</th>
								<th class="text-center">Доступен</th>
							</tr>
						</thead>
						<tbody>
						<?php
						
						//Массивы для JS с id элементов и с чекбоксами элементов
						$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
						$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
						

						$elements_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `shop_docpart_search_tabs` ORDER BY `order`;");
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
							
							
							$a_item = "<a href=\"/".$DP_Config->backend_dir."/shop/taby-poiska/tab-poiska?tab_id=".$element_record["id"]."\">";
						?>
							<tr>
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/></td>
								<td><?php echo $a_item.$element_record["id"]; ?></a></td>
								<td><?php echo $a_item.$element_record["caption"]; ?></a></td>
								<td><?php echo $a_item.$element_record["order"]; ?></a></td>
								<td class="text-center">
									
									
									<?php 
									if($element_record["enabled"] == 1) 
									{
										?>
										<form method="POST">
											<input type="text" name="action" value="activation" style="display:none" />
											
											
											<input type="text" name="activate_tab" value="-1" style="display:none"/>
											<input type="text" name="tab_id" value="<?php echo $element_record["id"];?>" style="display:none"/>
											<input type="text" name="s_page" value="<?php echo $s_page;?>" style="display:none"/>
											<input type="image" class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/on.png" />
										</form>
										<?php
									}
									else
									{
										?>
										<form method="POST">
											<input type="text" name="action" value="activation" style="display:none" />
											<input type="text" name="activate_tab" value="1" style="display:none"/>
											<input type="text" name="tab_id" value="<?php echo $element_record["id"];?>" style="display:none"/>
											<input type="text" name="s_page" value="<?php echo $s_page;?>" style="display:none"/>
											<input type="image" class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/off.png" />
										</form>
										<?php
									}
									?>
								</td>
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
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="<?php echo "/".$DP_Config->backend_dir."/shop/taby-poiska?s_page=$i"; ?>"><?php echo $i; ?></a></li>
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
}
?>