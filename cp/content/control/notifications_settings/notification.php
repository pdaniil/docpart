<?php
//Страничный скрипт для настройки одного уведомления
defined('_ASTEXE_') or die('No access');


//Если есть действия
if( isset($_POST['action']) )
{
	if( $_POST['action'] != 'save' )
	{
		exit;
	}
	
	
	$notification_id = $_POST["notification_id"];
	
	
	//Получаем запись уведомления
	$notification_query = $db_link->prepare("SELECT * FROM `notifications_settings` WHERE `id` = ?;");
	$notification_query->execute( array($notification_id) );
	$notification = $notification_query->fetch();
	
	if( $notification == false)
	{
		exit;
	}
	
	
	/*
	Что может настраивать пользователь:
	- заголовок письма
	- текст письма
	- текст SMS

	- вкл/выкл E-mail
	- вкл/выкл SMS
	
	При этом данные настройки для E-mail и для Телефона можно делать только если у данного уведомления выставлен флаг foreseen
	*/
	
	
	//Для E-mail
	if( $notification['foreseen_email'] == 1 )
	{
		$email_subject = $_POST['email_subject'];
		$email_body = $_POST['email_body'];
		$email_on = (int)isset($_POST['email_on']);
	}
	else
	{
		$email_subject = NULL;
		$email_body = NULL;
		$email_on = 0;
	}
	
	
	//Для телефона
	if( $notification['foreseen_sms'] == 1 )
	{
		$sms_body = $_POST['sms_body'];
		$sms_on = (int)isset($_POST['sms_on']);
	}
	else
	{
		$sms_body = '';
		$sms_on = 0;
	}
	
	
	
	if( !$db_link->prepare("UPDATE `notifications_settings` SET `email_subject` = ?, `email_body` = ?, `email_on` = ?, `sms_body` = ?, `sms_on` = ? WHERE `id` = ?;")->execute( array($email_subject, $email_body, $email_on, $sms_body, $sms_on, $notification_id) ) )
	{
		//Переадресация с сообщением о результатах выполнения
		$error_message = "Ошибка записи настроек";
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings/notification?notification_id=<?php echo $notification_id; ?>&error_message=<?php echo urlencode($error_message); ?>";
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
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings/notification?notification_id=<?php echo $notification_id; ?>&success_message=<?php echo urlencode($success_message); ?>";
		</script>
		<?php
		exit;
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
				//Сохранить
				print_backend_button( array("background_color"=>"#63ce1c", "fontawesome_class"=>"fas fa-save", "caption"=>"Сохранить", "onclick"=>"document.forms['save_notification_form'].submit();", "url"=>"javascript:void(0);") );
				?>
				
				
				<?php
				//Кнопка восстановления настроек по-умолчанию
				print_backend_button( array("background_color"=>"#8e44ad", "fontawesome_class"=>"fas fa-undo", "caption"=>"Вернуть заводские настройки", "url"=>"javascript:void(0);", "onclick"=>"set_default();") );
				?>
				
				
				<?php
				//Обратно к уведомлениям
				print_backend_button( array("background_color"=>"#e74c3c", "fontawesome_class"=>"fas fa-envelope-open-text", "caption"=>"Вернуться в менеджер уведомлений", "url"=>$DP_Config->domain_path.$DP_Config->backend_dir."/control/notifications_settings") );
				?>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
				
				
			</div>
		</div>
	</div>
	
	
	<?php
	$notification_query = $db_link->prepare('SELECT * FROM `notifications_settings` WHERE `id` = ?;');
	$notification_query->execute( array($_GET['notification_id']) );
	$notification = $notification_query->fetch();
	if( $notification == false )
	{
		//Переадресация с сообщением о результатах выполнения
		$warning_message = "Уведомление не найдено";
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/notifications_settings?warning_message=<?php echo urlencode($warning_message); ?>";
		</script>
		<?php
		exit;
	}
	?>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Информация по уведомлению "<?php echo $notification["caption"]; ?>"
			</div>
			<div class="panel-body">
				
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						ID
					</label>
					<div class="col-lg-9">
						<?php echo $notification["id"]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Обозначение
					</label>
					<div class="col-lg-9">
						<?php echo $notification["name"]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Название
					</label>
					<div class="col-lg-9">
						<?php echo $notification["caption"]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Описание
					</label>
					<div class="col-lg-9">
						<?php echo $notification["description"]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Событие, при котором данное уведомление отправляется
					</label>
					<div class="col-lg-9">
						<?php echo $notification["event"]; ?>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Отправляется на неподтвержденные контакты
					</label>
					<div class="col-lg-9">
						<?php
						if( $notification["send_for_not_confirmed"] == 1 )
						{
							echo "Да";
						}
						else
						{
							echo "Нет";
						}
						?>
					</div>
				</div>
				
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-3 control-label">
						Переменные для шаблонов уведомления
					</label>
					<div class="col-lg-9">
						<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
							<thead>
								<tr>
									<th>Параметр</th>
									<th>Ключ</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$notification_vars = json_decode($notification["vars"], true);
								for( $i=0 ; $i < count($notification_vars) ; $i++ )
								{
									?>
									<tr>
										<td><?php echo $notification_vars[$i]['caption']; ?></td>
										<td>%<?php echo $notification_vars[$i]['name']; ?>%</td>
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
	</div>
	
	
	
	<form method="POST" name="save_notification_form">
	<input type="hidden" name="action" value="save" />
	<input type="hidden" name="notification_id" value="<?php echo $_GET['notification_id']; ?>" />
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Настройки уведомления для E-mail
			</div>
			<div class="panel-body">
				<?php
				if( $notification['foreseen_email'] == 1 )
				{
					?>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Отправлять на E-mail
						</label>
						<div class="col-lg-9">
							<?php
							$checked = '';
							if( $notification['email_on'] == 1 )
							{
								$checked = ' checked="checked" ';
							}
							?>
							<input class="form-control" type="checkbox" name="email_on" id="email_on" <?php echo $checked; ?> />
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Заголовок письма
						</label>
						<div class="col-lg-9">
							<input class="form-control" type="text" name="email_subject" id="email_subject" value="<?php echo $notification['email_subject']; ?>" placeholder="Заголовок письма" />
						</div>
					</div>
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					<div class="form-group">
						<div class="col-lg-12">
							<label for="" class="control-label">Текст письма:</label>
							<div id="email_body_div"></div>
							<script>
							// --------------------------------------------------------------------------------
							//Инициализация редактора
							function init_TinyMCE()
							{
								var email_body_div = document.getElementById("email_body_div");
								

								email_body_div.innerHTML = "<textarea style=\"min-height:400px\" class=\"tinymce_editor\" id=\"email_body\" name=\"email_body\"></textarea>";
								tinymce.init({
									selector: "textarea.tinymce_editor",
									toolbar: "bold italic | fontselect | fontsizeselect | styleselect | forecolor | backcolor",
									plugins: [
										"code fullscreen textcolor"
									],
								});
								
								
								<?php
								$email_body = addcslashes(str_replace(array("\n","\r"), '', $notification['email_body']), "'");
								$email_body = str_replace("/", "\/", $email_body);
								?>
								
								
								//Заполняем текущее содержимое:
								document.getElementById("email_body").value = '<?php echo $email_body; ?>';
							}//~function init_TinyMCE()
							// --------------------------------------------------------------------------------
							init_TinyMCE();
							</script>
							
						</div>
					</div>
					
					
					
					
					
					<?php
				}
				else
				{
					?>
					Для данного уведомления не доступна отправка по E-mail
					<?php
				}
				?>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Настройки уведомления для Телефона (по SMS)
			</div>
			<div class="panel-body">
				<?php
				if( $notification['foreseen_sms'] == 1 )
				{
					?>
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Отправлять на Телефон
						</label>
						<div class="col-lg-9">
							<?php
							$checked = '';
							if( $notification['sms_on'] == 1 )
							{
								$checked = ' checked="checked" ';
							}
							?>
							<input class="form-control" type="checkbox" name="sms_on" id="sms_on" <?php echo $checked; ?> />
						</div>
					</div>
					
					
					<div class="hr-line-dashed col-lg-12"></div>
					
					
					<div class="form-group">
						<label for="" class="col-lg-3 control-label">
							Текст SMS-сообщения
						</label>
						<div class="col-lg-9">
							
							<textarea class="form-control" name="sms_body" id="sms_body" placeholder="Текст SMS-сообщения"><?php echo $notification["sms_body"]; ?></textarea>
							
						</div>
					</div>
					
					<?php
				}
				else
				{
					?>
					Для данного уведомления не доступна отправка по Телефону
					<?php
				}
				?>
			</div>
		</div>
	</div>
	
	
	</form>
	
	<script>
	// -------------------------------------------------------------------------------------------
	//Восстановление настроек по умолчанию
	function set_default()
	{
		<?php
		if( $notification['foreseen_email'] == 1 )
		{
			?>
			document.getElementById('email_on').checked = true;
			
			document.getElementById('email_subject').value = '<?php echo $notification['default_email_subject']; ?>';
			

			<?php
			$default_email_body = addcslashes(str_replace(array("\n","\r"), '', $notification['default_email_body']), "'");
			$default_email_body = str_replace("/", "\/", $default_email_body);
			?>
			
			tinymce.get("email_body").setContent('<?php echo $default_email_body; ?>');
			<?php
		}
		
		if( $notification['foreseen_sms'] == 1 )
		{
			?>
			document.getElementById('sms_on').checked = true;
			
			document.getElementById('sms_body').value = '<?php echo $notification['default_sms_body']; ?>';
			<?php
		}
		?>
		
		alert('Загружены настройки из исходного дистрибутива');
	}
	// -------------------------------------------------------------------------------------------
	</script>
	
	<?php
}
?>