<?php
//Страничный скрипт редактора видов финансовых операций
defined('_ASTEXE_') or die('No access');



//Если есть действия
if( isset($_POST['action']) )
{
	if( $_POST['action'] == 'delete_operations' )
	{
		$operations_to_del = json_decode($_POST['operations_to_del'], true);
		
		
		//Перед удалением нужно проверить, что в списке удаляемых операций нет: системных операций и нет операций, которые совершались
		
		//Проверяем наличие системных операций
		$system_operations_check_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_accounting_codes` WHERE `system` = ? AND `id` IN ('.str_repeat('?,', count($operations_to_del)-1 ).'?);');
		$system_operations_check_query->execute( array_merge(array(1), $operations_to_del) );
		if( $system_operations_check_query->fetchColumn() > 0 )
		{
			//Переадресация с сообщением о результатах выполнения
			$warning_message = "В списке отмеченных вами на удаление видов операций, был один или несколько видов системных операций. Системные виды операции удалить невозможно. Удаление не выполнено";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor?warning_message=<?php echo urlencode($warning_message); ?>";
			</script>
			<?php
			exit;
		}
		
		
		
		//Проверяем наличие видов операций, которые совершались
		$used_operations_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_users_accounting` WHERE `operation_code` IN ('.str_repeat('?,', count($operations_to_del)-1 ).'?);');
		$used_operations_query->execute( $operations_to_del );
		if( $used_operations_query->fetchColumn() > 0 )
		{
			//Переадресация с сообщением о результатах выполнения
			$warning_message = "В списке отмеченных вами на удаление видов операций, был один или несколько видов операций, которые совершались ранее  (отражены в балансах покупателей). Такие виды операций удалить невозможно. Удаление не выполнено.";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor?warning_message=<?php echo urlencode($warning_message); ?>";
			</script>
			<?php
			exit;
		}
		
		
		if( ! $db_link->prepare('DELETE FROM `shop_accounting_codes` WHERE `id` IN ('.str_repeat('?,', count($operations_to_del)-1 ).'?);')->execute( $operations_to_del ) )
		{
			//Переадресация с сообщением о результатах выполнения
			$error_message = "Ошибка удаления видов операций";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor?error_message=<?php echo urlencode($error_message); ?>";
			</script>
			<?php
			exit;
		}
		else
		{
			//Переадресация с сообщением о результатах выполнения
			$success_message = "Отмеченные виды операций удалены";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/finance/operations_editor?success_message=<?php echo urlencode($success_message); ?>";
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
				
				
				<?php
				//Добавить новый вид операции
				print_backend_button( array("background_color"=>"#63ce1c", "fontawesome_class"=>"fas fa-plus", "caption"=>"Добавить новый вид операции", "url"=>"/".$DP_Config->backend_dir."/shop/finance/operations_editor/operation") );
				?>
				
				
				<?php
				//Удалить отмеченные операции
				print_backend_button( array("background_color"=>"#ff4b39", "fontawesome_class"=>"fas fa-trash", "caption"=>"Удалить отмеченные виды", "url"=>"javascript:void(0);", "onclick"=>"delete_operations();") );
				?>
				
				
				<?php
				//Вернуться обратно в "Счета покупателей"
				print_backend_button( array("background_color"=>"#27ae60", "fontawesome_class"=>"fas fa-money-check-alt", "caption"=>"Вернуться в счета покупателей", "url"=>"/".$DP_Config->backend_dir."/shop/finance/account_operations") );
				?>
				
				
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
				
				
			</div>
		</div>
	</div>
	
	
	
	
	<form method="POST" name="operations_delete_form">
		<input type="hidden" name="action" value="delete_operations" />
		<input type="hidden" name="operations_to_del" id="operations_to_del" value="" />
	</form>
	<script>
	//Обработка нажатия кнопки удаления операций
	function delete_operations()
	{
		var operations = getCheckedElements();
		
		if( operations.length == 0 )
		{
			alert('Не отмечены виды операций для удаления');
			return;
		}
		
		
		if( !confirm('Отмеченные виды операций будут удалены. Продолжить?') )
		{
			return;
		}
		
		
		document.getElementById('operations_to_del').value = JSON.stringify(operations);
		document.forms['operations_delete_form'].submit();
	}
	</script>
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Виды финансовых операций
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="footable table table-hover toggle-arrow " data-sort="false">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th>ID (код)</th>
								<th>Направление</th>
								<th>Наименование</th>
								<th>Обозначение</th>
								<th class="text-center">Системная</th>
							</tr>
						</thead>
						<tbody>
							<?php
							//Массивы для JS с id элементов и с чекбоксами элементов
							$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
							$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
							
							
							$elements_query = $db_link->prepare("SELECT * FROM `shop_accounting_codes` ORDER BY `id` ASC;");
							$elements_query->execute();
							while( $element_record = $elements_query->fetch() )
							{
								//Для Javascript
								$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
								$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
								
								
								$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/shop/finance/operations_editor/operation?operation_id=".$element_record["id"]."\">";
								?>
								<tr>
									<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/></td>
									<td><?php echo $a_item.$element_record["id"]; ?></a></td>
									
									<td>
									<?php
									if( $element_record["income"] == 1 )
									{
										echo $a_item.'Приходная <i class="fas fa-long-arrow-alt-right"></i>';
									}
									else
									{
										echo $a_item.'<i class="fas fa-long-arrow-alt-left"></i> Расходная';
									}
									?>
									</a>
									</td>
									<td><?php echo $a_item.$element_record["name"]; ?></a></td>
									
									<td><?php echo $a_item.$element_record["key"]; ?></a></td>
									
									
									<td class="text-center">
									<?php
									if( $element_record["system"] )
									{
										?>
										<i class="fas fa-cog"></i>
										<?php
									}
									?>
									</td>
									
									
									
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>
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