<?php
//Страничный скрипт отображения таблицы notifications_settings (менеджер уведомлений)
defined('_ASTEXE_') or die('No access');


//Если есть действия
if( isset($_POST['action']) )
{
	//Действие - восстановление настроек по умолчанию
	if( $_POST['action'] == 'set_default' )
	{
		$notifications_ids = json_decode($_POST['notifications_ids'], true);
		
		$SQL_set_default = "UPDATE `notifications_settings` SET `email_subject` = `default_email_subject`, `email_body` = `default_email_body`, `sms_body` = `default_sms_body`, `email_on` = `foreseen_email`, `sms_on` = `foreseen_sms` WHERE `id` IN (".str_repeat('?,', count($notifications_ids)-1 )."?);";
		
		if( !$db_link->prepare($SQL_set_default)->execute($notifications_ids) )
		{
			//Переадресация с сообщением о результатах выполнения
			$error_message = "Ошибка настройки";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?error_message=<?php echo urlencode($error_message); ?>";
			</script>
			<?php
			exit;
		}
		else
		{
			//Переадресация с сообщением о результатах выполнения
			$success_message = "Выполнено успешно";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?success_message=<?php echo urlencode($success_message); ?>";
			</script>
			<?php
			exit;
		}
	}
	//Отправлять на email и Отправлять на Телефон
	else if( $_POST['action'] == 'set_send' )
	{
		$type = $_POST['type'];//E-mail или Телефон
		$notification_id = $_POST['notification_id'];
		$set_send = $_POST['set_send'];//Вкл или выкл
		
		
		//$type используется в SQL-запросах. Проверяем значение
		if( $type != 'email' && $type != 'sms' )
		{
			exit;
		}
		
		
		
		//Отключать отправку можно в любом случае.
		//Включать можно только, если предусмотрен соответствующий способ отправки для данного уведомления
		if( $set_send == 1 )
		{
			//Проверяем, предусмотрен ли данный способ отправки по этому уведомлению
			$foreseen_query = $db_link->prepare("SELECT * FROM `notifications_settings` WHERE `id` = ?;");
			$foreseen_query->execute( array($notification_id) );
			$foreseen_record = $foreseen_query->fetch();
			
			if( $foreseen_record['foreseen_'.$type] == 0 )
			{
				//Переадресация с сообщением о результатах выполнения
				$warning_message = "Для этого уведомления не предусмотен данный способ отправки";
				?>
				<script>
					location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?warning_message=<?php echo urlencode($warning_message); ?>";
				</script>
				<?php
				exit;
			}
		}
		
		
		
		//Включаем/отключаем
		if( !$db_link->prepare("UPDATE `notifications_settings` SET `".$type."_on` = ? WHERE `id` = ?;")->execute( array($set_send, $notification_id) ) )
		{
			//Переадресация с сообщением о результатах выполнения
			$error_message = "Ошибка настройки";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?error_message=<?php echo urlencode($error_message); ?>";
			</script>
			<?php
			exit;
		}
		else
		{
			//Переадресация с сообщением о результатах выполнения
			$success_message = "Выполнено успешно";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?success_message=<?php echo urlencode($success_message); ?>";
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
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
				
				<?php
				//Кнопка восстановления настроек по-умолчанию
				print_backend_button( array("background_color"=>"#8e44ad", "fontawesome_class"=>"fas fa-undo", "caption"=>"Вернуть заводские настройки", "url"=>"javascript:void(0);", "onclick"=>"set_default_checked();") );
				?>
				<form name="set_default_form" method="POST">
					<input type="hidden" name="action" value="set_default" />
					<input type="hidden" name="notifications_ids" id="notifications_ids" value="" />
				</form>
				<script>
				// ----------------------------------------------------------------------------------
				//Откат к настройкам по-умолчанию для отмеченных уведомлений
				function set_default_checked()
				{
					var notifications_ids = getCheckedElements();
					
					if( notifications_ids.length == 0 )
					{
						alert("Не отмечены уведомления, для которых нужно восстановить заводские настройки");
						return;
					}
					
					if( !confirm("Внимание! Для отмеченных уведомлений будет произведен откат всех настроек к исходному состоянию дистрибутива CMS (включая тексты сообщений, настройки отправки по E-mail и телефону и т.д.) Выполнить откат настроек в исходное состояние?") )
					{
						return;
					}
					
					document.getElementById('notifications_ids').value = JSON.stringify(notifications_ids);
					
					document.forms['set_default_form'].submit();
				}
				// ----------------------------------------------------------------------------------
				//Откат к настройкам по-умолчанию для определенного уведомления
				function set_default_one(notification_id)
				{
					if( !confirm("Внимание! Для данного уведомления будет произведен откат всех настроек к исходному состоянию дистрибутива CMS (включая тексты сообщений, настройки отправки по E-mail и телефону и т.д.) Выполнить откат настроек в исходное состояние?") )
					{
						return;
					}
					
					document.getElementById('notifications_ids').value = '['+notification_id+']';
					
					document.forms['set_default_form'].submit();
				}
				// ----------------------------------------------------------------------------------
				</script>
				
				
			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Перечень уведомлений сайта
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="footable table table-hover toggle-arrow " data-sort="false">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th>ID</th>
								<th>Название</th>
								<th>Обозначение</th>
								<th>Событие</th>
								<th>Описание</th>
								<th class="text-center">На Email</th>
								<th class="text-center">На Телефон</th>
								<th class="text-center">Действия</th>
							</tr>
						</thead>
						<tbody>
							<?php
							//Массивы для JS с id элементов и с чекбоксами элементов
							$for_js = "var elements_array = new Array();\n";//Выведем массив для JS с чекбоксами элементов
							$for_js = $for_js."var elements_id_array = new Array();\n";//Выведем массив для JS с ID элементов
							
							
							$elements_query = $db_link->prepare("SELECT * FROM `notifications_settings` ORDER BY `id` ASC;");
							$elements_query->execute();
							while( $element_record = $elements_query->fetch() )
							{
								//Для Javascript
								$for_js = $for_js."elements_array[elements_array.length] = \"checked_".$element_record["id"]."\";\n";//Добавляем элемент для JS
								$for_js = $for_js."elements_id_array[elements_id_array.length] = ".$element_record["id"].";\n";//Добавляем элемент для JS
								
								
								$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/control/notifications_settings/notification?notification_id=".$element_record["id"]."\">";
								?>
								<tr>
									<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $element_record["id"]; ?>');" id="checked_<?php echo $element_record["id"]; ?>" name="checked_<?php echo $element_record["id"]; ?>"/></td>
									<td><?php echo $a_item.$element_record["id"]; ?></a></td>
									<td><?php echo $a_item.$element_record["caption"]; ?></a></td>
									<td><?php echo $a_item.$element_record["name"]; ?></a></td>
									<td><?php echo $a_item.$element_record["event"]; ?></a></td>
									<td><?php echo $a_item.$element_record["description"]; ?></a></td>
									<td class="text-center">
										<form method="POST" name="set_send_email_<?php echo $element_record["id"]; ?>">
											<input type="hidden" name="action" value="set_send" />
											<input type="hidden" name="type" value="email" />
											<input type="hidden" name="notification_id" value="<?php echo $element_record["id"]; ?>" />
											<?php
											if( $element_record["email_on"] == 1 )
											{
												?>
												<input type="hidden" name="set_send" value="0" />
												<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Отправляется на E-mail" onclick="forms['set_send_email_<?php echo $element_record["id"]; ?>'].submit();"></i>
												<?php
											}
											else
											{
												?>
												<input type="hidden" name="set_send" value="1" />
												<i class="fas fa-minus-circle" style="color:#C33;cursor:pointer;font-size:1.5em;" title="Не отправляется на E-mail" onclick="forms['set_send_email_<?php echo $element_record["id"]; ?>'].submit();"></i>
												<?php
											}
											?>
										</form>
									</td>
									<td class="text-center">
										<form method="POST" name="set_send_sms_<?php echo $element_record["id"]; ?>">
											<input type="hidden" name="action" value="set_send" />
											<input type="hidden" name="type" value="sms" />
											<input type="hidden" name="notification_id" value="<?php echo $element_record["id"]; ?>" />
											<?php
											if( $element_record["sms_on"] == 1 )
											{
												?>
												<input type="hidden" name="set_send" value="0" />
												<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Отправляется на Телефон" onclick="forms['set_send_sms_<?php echo $element_record["id"]; ?>'].submit();"></i>
												<?php
											}
											else
											{
												?>
												<input type="hidden" name="set_send" value="1" />
												<i class="fas fa-minus-circle" style="color:#C33;cursor:pointer;font-size:1.5em;" title="Не отправляется на Телефон" onclick="forms['set_send_sms_<?php echo $element_record["id"]; ?>'].submit();"></i>
												<?php
											}
											?>
										</form>
									</td>
									<td class="text-center">
										<i class="fas fa-undo" style="color:#8e44ad;cursor:pointer;font-size:1.5em;" title="Вернуть заводские настройки" onclick="set_default_one(<?php echo $element_record["id"]; ?>);"></i>
										
										
										<?php echo $a_item; ?><i class="fas fa-edit" style="color:#3498db;cursor:pointer;font-size:1.5em;" title="Редактировать тексты сообщений"></i></a>
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