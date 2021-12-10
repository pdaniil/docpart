<?php
/**
 * Страница управления плагинами в бэкэнде
*/
defined('_ASTEXE_') or die('No access');

//Режим редактирования Фронтэнд/Бэкэнд
$edit_mode = null;
if( isset($_COOKIE["edit_mode"]) )
{
	$edit_mode = $_COOKIE["edit_mode"];
}
switch($edit_mode)
{
    case "frontend":
        $is_frontend = 1;
        break;
    case "backend":
        $is_frontend = 0;
        break;
    default:
        $is_frontend = 1;
        break;
}
?>

<?php
//Метод удаления не пустого каталога
function removeNotEmptyDir($dir)
{
    if(is_file($dir)) return unlink($dir);
    
    $dh=opendir($dir);
    while(false!==($file=readdir($dh)))
    {
            if($file=='.'||$file=='..') continue;
            removeNotEmptyDir($dir."/".$file);
    }
    closedir($dh);
    
    return rmdir($dir);
}
?>

<?php
//Если есть действия
if(!empty($_POST["plugins_action"]))
{
    $plugins_list = json_decode($_POST["plugins_list"], true);//Список плагинов
    
    //Защищаем от изменений плагины с заблокированным управлением:
    for($i=0; $i < count($plugins_list); $i++)
    {
		$check_plugin_query = $db_link->prepare("SELECT * FROM `plugins` WHERE `id` = ?;");
		$check_plugin_query->execute( array($plugins_list[$i]) );
        $check_plugin_record = $check_plugin_query->fetch();
        if($check_plugin_record["control_lock"] == true)
        {
            //Возникла ошибка
            $warning_message = "Действие прервано: в списке оказались плагины с заблокированным управлением";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager?warning_message=<?php echo $warning_message; ?>";
            </script>
            <?php
            exit();
        }
    }
    
    //Проверка пройдена - выполняем действия
    
    
    
    if($_POST["plugins_action_type"] == "delete")
    {
        //Сначала удаляем файлы и каталоги:
        for($i=0; $i < count($plugins_list); $i++)
        {
            //Получаем список файлов и каталогов плагина:
			$current_plugin_query = $db_link->prepare("SELECT * FROM `plugins` WHERE `id` = ?;");
			$current_plugin_query->execute( array($plugins_list[$i]) );
            $current_plugin_record = $current_plugin_query->fetch();
            $dirs_files = json_decode($current_plugin_record["dirs_files"], true);
            //По всему списку файлов и каталогов данного плагина:
            for($j=0; $j < count($dirs_files); $j++)
            {
                //Подставляем имя каталога бэкэнда (если оно обозначено в строке):
                $path = $_SERVER['DOCUMENT_ROOT']."/".str_replace(array("<backend_dir>"), $DP_Config->backend_dir, $dirs_files[$j]["path"]);
                
                if($dirs_files[$j]["type"] == "dir")//Рекурсивно удаляем каталог
                {
                    if(file_exists($path))
                    {
                        removeNotEmptyDir($path);
                    }
                }
                else if($dirs_files[$j]["type"] == "file")//Удаляем файл
                {
                    if(file_exists($path))
                    {
                        unlink($path);
                    }
                }
            }//for($j) - по удаляемым файлам и каталогам
        }//for($i) - по всем удаляемым плагинам
        
        //Удаление указанных плагинов
        $SQL_DELETE = "DELETE FROM `plugins` WHERE ";
        $SQL_SUB_IDS = "";
		$binding_values = array();
        for($i=0; $i < count($plugins_list); $i++)
        {
            if($SQL_SUB_IDS != "") $SQL_SUB_IDS .= " OR";
            $SQL_SUB_IDS .= " `id`=?";
			
			array_push($binding_values, $plugins_list[$i]);
        }//for($i) - по всем удаляемым плагинам

        if( $db_link->prepare($SQL_DELETE.$SQL_SUB_IDS)->execute( $binding_values ) != true)
        {
            //Возникла ошибка
            $error_message = "Ошибка - Указанные плагины не удалены";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit();
        }
        else//Плагины удалены успешно
        {
            //Выполнено без ошибок
            $success_message = "Указанные плагины удалены успешно";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager?success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit();
        }
    }//if - удаление плагинов
    else if($_POST["plugins_action_type"] == "activated")
    {
        //Включение / Отключение указанных плагинов
        $SQL_UPDATE_ACTIVATE = "UPDATE `plugins` SET `activated` = ? WHERE ";
        $SQL_SUB_IDS = "";
        $binding_values = array();
		array_push($binding_values, (int)$_POST["flag_value"]);
		for($i=0; $i < count($plugins_list); $i++)
        {
            if($SQL_SUB_IDS != "") $SQL_SUB_IDS .= " OR ";
            $SQL_SUB_IDS .= " `id`=? ";
			
			array_push($binding_values, $plugins_list[$i]);
        }

        if( $db_link->prepare($SQL_UPDATE_ACTIVATE.$SQL_SUB_IDS)->execute( $binding_values ) != true)
        {
            //Возникла ошибка
            $error_message = "Ошибка - не выполнено";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit();
        }
        else
        {
            //Выполнено без ошибок
            $success_message = "Выполнено без ошибок";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager?success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit();
        }
    }//~else if($_POST["plugins_action_type"] == "activated")
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
				<a class="panel_a" onClick="deletePlugins(0);" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>
				
				
				<a class="panel_a" onClick="activatePlugins(0, true);" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/on.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Включить</div>
				</a>
				
				
				<a class="panel_a" onClick="activatePlugins(0, false);" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Отключить</div>
				</a>
				
				
				<script>
				//Функция установки режима редактирования Фронтэнд/Бэкэнд
				function set_edit_mode(mode)
				{
					$.getJSON("<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/control/set_edit_mode_cookie.php?edit_mode="+encodeURI(mode)+"&callback=?", function(data){
							location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugins_manager";
						});
				}
				</script>
				
				<?php
				if($is_frontend)
				{
				?>
					<a class="panel_a" onClick="set_edit_mode('backend');" href="javascript:void(0);">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/backend_edit.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Редактировать бэкэнд</div>
					</a>
				<?php
				}
				else
				{
				?>
					<a class="panel_a" onClick="set_edit_mode('frontend');" href="javascript:void(0);">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/frontend_edit.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Редактировать фронтэнд</div>
					</a>
				<?php
				}
				?>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Таблица плагинов
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();" /></th>
								<th>ID</th>
								<th>Название</th>
								<th>Порядок</th>
								<th class="text-center">Активен</th>
							</tr>
						</thead>
						<tbody>
						<?php
						
						//Массивы для JS с id элементов и с чекбоксами элементов
						$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
						$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
						
						$elements_query = $db_link->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM `plugins` WHERE `is_frontend` = ?;");
						$elements_query->execute( array($is_frontend) );
						
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
							
							//Если управление плагином заблокировано, то не выводим для него чекбокс
							if($element_record["control_lock"] == false)
							{
								//Для Javascript
								$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
								$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
							}
							
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/plugins/plugin?plugin_id=".$element_record["id"]." \">";
						?>
							<tr>
								<td>
									<?php
									//Если управление плагином заблокировано, то не выводим для него чекбокс
									if($element_record["control_lock"] == false)
									{
									?>
										<input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/>
									<?php
									}
									else
									{
										?>
										<img class="col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/shield.png" title="Управление заблокировано" />
										<?php
									}
									?>
								</td>
								<td><?php echo $a_item.$element_record["id"]; ?></a></td>
								<td><?php echo $a_item.$element_record["caption"]; ?></a></td>
								<td><?php echo $a_item.$element_record["order"]; ?></a></td>
								<td class="text-center">
								<?php
								if($element_record["activated"] == 1)
								{
									$onclick = "activatePlugins(".$element_record["id"].", 0);";
									//Для плагинов с заблокированным управлением не выводим ссылку
									if($element_record["control_lock"] == true)
									{
										$onclick = "alert('Управление этим плагином заблокировано');";
									}
									
									?>
									<a href="javascript:void(0);" onclick="<?php echo $onclick; ?>"><img class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/on.png" /></a>
									<?php
								}
								else
								{
									$onclick = "activatePlugins(".$element_record["id"].", 1);";
									//Для плагинов с заблокированным управлением не выводим ссылку
									if($element_record["control_lock"] == true)
									{
										$onclick = "alert('Управление этим плагином заблокировано');";
									}
									
									?>
									<a href="javascript:void(0);" onclick="<?php echo $onclick; ?>"><img class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/off.png" /></a>
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
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="<?php echo "/".$DP_Config->backend_dir."/plugins/plugins_manager?s_page=$i"; ?>"><?php echo $i; ?></a></li>
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
	
	
	
	



    
    <!-- START Общая форма для действий менеджера: активация/деактивация/удаление -->
    <form name="plugins_manager_action_form" method="POST" style="display:none">
        <input type="hidden" name="plugins_action" id="plugins_action" value="plugins_action" /><!-- Говорит, что есть действие -->
        <input type="hidden" name="plugins_action_type" id="plugins_action_type" value="" /><!-- Тип действия (Удаление или Активация) -->
        <input type="hidden" name="flag_value" id="flag_value" value="" /><!-- Значение действия -->
        <input type="hidden" name="plugins_list" id="plugins_list" value="" />
    </form>
    <!-- END Общая форма для действий менеджера: активация/деактивация/удаление -->
    
    
    
    
    <!-- START БЛОК АКТИВАЦИИ / ДЕАКТИВАЦИИ ПЛАГИНОВ -->
    <script>
    //Функция активации плагинов
    function activatePlugins(plugin, activate_value)
    {
        if(plugin == 0)//Если равно 0 - активируем отмеченные галочками плагины
        {
            document.getElementById("plugins_list").value = JSON.stringify(getCheckedElements());
            if(document.getElementById("plugins_list").value == "[]")
            {
                alert("Нет отмеченых плагинов");
                return;
            }
        }
        else//Активируем только один указанный плагин
        {
            document.getElementById("plugins_list").value = "["+plugin+"]";
        }
        
        document.getElementById("plugins_action_type").value = "activated";//Тип действия - активация / деактивация
        document.getElementById("flag_value").value = activate_value;
        
        //Отправляем форму
    	document.forms["plugins_manager_action_form"].submit();//Отправляем
    }
    </script>
    <!-- END БЛОК АКТИВАЦИИ / ДЕАКТИВАЦИИ ПЛАГИНОВ -->
    
    
    
    
    <!-- START БЛОК УДАЛЕНИЯ ПЛАГИНОВ -->
    <script>
    //Функция удаления плагинов
    function deletePlugins(plugin)
    {
        if(plugin == 0)//Если равно 0 - удаляем отмеченные галочками плагины
        {
            document.getElementById("plugins_list").value = JSON.stringify(getCheckedElements());
            if(document.getElementById("plugins_list").value == "[]")
            {
                alert("Нет отмеченых плагинов");
                return;
            }
        }
        else//Удаляем только один указанный плагин
        {
            document.getElementById("plugins_list").value = "["+plugin+"]";
        }
        
        if(!confirm("Указанные плагины будут безвозвратно удалены. Продолжить?"))
        {
            return;
        }
        
        document.getElementById("plugins_action_type").value = "delete";//Тип действия - удаление
        
        //Отправляем форму
    	document.forms["plugins_manager_action_form"].submit();//Отправляем
    }
    </script>
    <!-- END БЛОК УДАЛЕНИЯ ПЛАГИНОВ -->
    
    
    
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
}//else - Действий нет - выводим страницу
?>