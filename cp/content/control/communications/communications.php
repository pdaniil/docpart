<?php
//Скрипт для страницы "Настройка способов связи"
defined('_ASTEXE_') or die('No access');



if( isset($_POST["action"]) )
{
	
}
else//Действий нет, выводим страницу
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
				//Настройка SMS
				print_backend_button( array("background_color"=>"#e74c3c", "fontawesome_class"=>"fas fa-mobile-alt", "caption"=>"Настройка SMS", "url"=>"/".$DP_Config->backend_dir."/control/sms-operatory", ) );
				?>
				
				
				<?php
				//Настройка E-mail
				print_backend_button( array("background_color"=>"#33cc33", "fontawesome_class"=>"far fa-envelope", "caption"=>"Настройка E-mail", "url"=>"/".$DP_Config->backend_dir."/control/config?need_config_group=3") );
				?>
				
				
				<?php
				//Настройка Уведомлений
				print_backend_button( array("background_color"=>"#e74c3c", "fontawesome_class"=>"fas fa-envelope-open-text", "caption"=>"Настройка уведомлений", "url"=>"/".$DP_Config->backend_dir."/control/notifications_settings") );
				?>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			
			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Инструкция
			</div>
			<div class="panel-body">
				
				<p style="font-weight:bold;font-size:1.5em;">Внимательно прочтите данную инструкцию!</p>
				
				<p>Платформа позволяет одновременно использовать оба вида связи, E-mail и Телефон, для регистрации пользователей, а также для всех видов уведомлений на сайте (оплата заказов, изменение статусов заказов и т.д.). При необходимости вы можете включить какой-то один из этих видов связи, либо, включить оба.</p>
				
				<p>Каждый из указанных способов связи становится доступен автоматически, если к нему настроено подключение:</p>
				
				<ul>
					<li>для E-mail - SMTP-подключение <a href="https://docpart.ru/video-uroki/nastrojka-pochty-po-smtp" style="text-decoration:underline;">Видео-урок</a></li>
					<li>для SMS - API-подключение к одному из SMS-операторов <a href="https://docpart.ru/video-uroki/sms-dlya-internet-magazina-avtozapchastej" style="text-decoration:underline;">Видео-урок</a></li>
				</ul>
				
				<p>Покупатель, указывая на сайте свой E-mail или Телефон должен обязательно его подтвердить через код подтверждения, отправляемый автоматически. Внимание! Только подтвержденный E-mail или Телефон можно использовать в качестве логина на сайте.</p>

				<p style="font-weight:bold;font-size:1.2em;">Доступен и E-mail и SMS</p>
				<p>Если на сайте включены оба вида связи, то, покупатель при регистрации сможет выбрать, через какой контакт зарегистрироваться (E-mail или Телефон). Т.е. если, к примеру, покупатель укажет при регистрации E-mail, то, телефон он также сможет добавить уже после регистрации (и наоборот).</p>

				<p style="font-weight:bold;font-size:1.2em;">Доступен либо E-mail, либо SMS</p>
				<p>Если на сайте будет настроен только один из способов связи, то, у покупателя не будет выбора, и он сможет зарегистрироваться только через включенный на сайте способ связи (E-mail или Телефон).</p>

				<p style="font-weight:bold;font-size:1.2em;">E-mail и SMS не подключены</p>
				<p>Если на сайте не настроен ни один из способов связи, то, покупатель не сможет зарегистрироваться совсем (форма регистрации будет отображаться покупателю, но, при ее отправке возникнет ошибка).</p>
			
			
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				E-mail
			</div>
			<div class="panel-body">
				
				
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label class="col-sm-4 control-label">Настройки подключения указаны:</label>
							<div class="col-sm-8">
								
								<?php
								$email_settings_pointed = false;//Флаг - настройки E-mail заданы
								
								
								if( !empty($DP_Config->from_name) && !empty($DP_Config->from_email) && !empty($DP_Config->smtp_mode) && !empty($DP_Config->smtp_encryption) && !empty($DP_Config->smtp_host) && !empty($DP_Config->smtp_port) && !empty($DP_Config->smtp_username) && !empty($DP_Config->smtp_password) )
								{
									$email_settings_pointed = true;
									?>
									<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Настройки указаны"></i>
									<?php
								}
								else
								{
									$email_settings_pointed = false;
									?>
									<i class="fas fa-exclamation-triangle" style="color:#FF0000;cursor:pointer;font-size:1.5em;" title="Настройки не указаны или указаны не полностью"></i>
									<?php
								}
								?>
								
								
							</div>
						</div>
					</div>
					
					<div class="col-md-6">
						<div class="form-group">
							<label class="col-sm-4 control-label">Корректность настроек:</label>
							<div class="col-sm-8">
								<?php
								//Результат проверки настроек выводим только если они заданы
								if( $email_settings_pointed )
								{
									$email_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
									$email_debug_query->execute( array('email') );
									$email_debug = $email_debug_query->fetch();
									
									if( $email_debug == false )
									{
										?>
										<i class="far fa-circle" style="color:#AAA;cursor:pointer;font-size:1.5em;" title="Корректность не проверялась"></i>
										<?php
									}
									else
									{
										//Коррекно
										if( $email_debug['status'] == 1 )
										{
											//Выводим время, когда была последняя проверка. Считаем, что проверка за последние сутки - новая, от суток до недели - средняя, более недели - старая
											
											if( time() - $email_debug['time'] < 86400 )
											{
												//Новая
												$title="";
												$style="";
											}
											else if( time() - $email_debug['time'] >= 86400 && time() - $email_debug['time'] < 604800 )
											{
												//Средняя
												$title="Проверка была давно. Желательно проверить снова";
												$style="background-color:#f5de1c;color:#000;cursor:pointer;";
											}
											else
											{
												//Старая
												$title="Проверка была давно. Нужно проверить снова";
												$style="background-color:#ff0000;color:#FFF;cursor:pointer;";
											}
											
											?>
											<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Настройки корректны"></i> Проверено <span title="<?php echo $title; ?>" style="<?php echo $style; ?>"><?php echo date("d.m.Y в H:i:s", $email_debug['time']); ?></span>
											<?php
										}
										else//Не корректно
										{
											?>
											<i class="fas fa-exclamation-triangle" style="color:#C33;cursor:pointer;font-size:1.5em;" title="Настройки не корректны"></i> Проверка была <?php echo date("d.m.Y в H:i:s", $email_debug['time']); ?>
											<br>Отладочная информация: <span style="background-color:#EFEFEF;"><?php echo $email_debug['debug_result']; ?></span>
											<?php
										}
									}
								}
								else
								{
									?>
									Сначала задайте настройки для почты, прежде, чем проверять их корректность
									<?php
								}
								?>
							</div>
						</div>
					</div>
					
					
					<div class="col-md-2 text-right">
						<a class="btn w-xs btn-success" href="<?php echo "/".$DP_Config->backend_dir."/control/config?need_config_group=3"; ?>"><i class="far fa-envelope"></i> Настройки E-mail</a>
					</div>
					
					
				</div>
				
				
				
				<?php
				//Форму тестирования E-mail выводим только если заданы настройки
				if( $email_settings_pointed )
				{
					?>
					<div class="hr-line-dashed"></div>
				
					<div class="row">
						<div class="col-md-12">
							
							<h5>Тестирование настроек E-mail:</h5>
							<p>Будет отправлено тестовое письмо с целью проверить корректность настройки почты. Укажите адрес получателя тестового письма и затем нажмите кнопку "<i class="far fa-envelope"></i> Тест!"</p>
							
							
							<?php
							//Для автозаполнения адреса получателя тестового письма
							$email_for_test_letter = "";
							$my_admin_profile = DP_User::getAdminProfile();
							if( !empty($my_admin_profile["email"]) )
							{
								$email_for_test_letter = $my_admin_profile["email"];
							}
							else if( !empty($DP_Config->from_email) )
							{
								$email_for_test_letter = $DP_Config->from_email;
							}
							?>
							
							
							<div class="input-group">
								<input type="text" class="form-control" placeholder="Укажите адрес получателя, которому будет отправлено тестовое письмо" value="<?php echo $email_for_test_letter; ?>" id="email_for_test" />
								<span class="input-group-btn">
									<button class="btn btn-primary" onclick="test_email();"><i class="far fa-envelope"></i> Тест!</button> 
								</span>
							</div>
							<script>
							function test_email()
							{
								var email_for_test = document.getElementById('email_for_test').value;
								
								
								if( email_for_test == '' )
								{
									alert('Не указан E-mail получателя тестового письма');
									return;
								}
								
								//Отправка тестового письма
								jQuery.ajax({
									type: "POST",
									async: false, //Запрос синхронный
									url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/control/communications/ajax_test_notification.php",
									dataType: "text",//Тип возвращаемого значения
									data: "contact="+email_for_test+"&type=email",
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
												alert('Настройки E-mail успешно протестированы');
											}
											else
											{
												alert(answer_ob.message);
											}
										}
										
										
										location = "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/communications";
									}
								});
								
								
							}
							</script>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Телефон (SMS-оператор)
			</div>
			<div class="panel-body">
				
				
				<div class="row">
					<div class="col-md-4">
						<div class="form-group">
							<label class="col-sm-4 control-label">Настройки подключения указаны:</label>
							<div class="col-sm-8">
								
								<?php
								$sms_settings_pointed = false;//Флаг - настройки SMS-оператора заданы
								
								
								
								$check_sms_query = $db_link->prepare("SELECT COUNT(*) FROM `sms_api` WHERE `active` = ?;");
								$check_sms_query->execute( array(1) );
								if( $check_sms_query->fetchColumn() == 1 )
								{
									$sms_settings_pointed = true;
									?>
									<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Настройки указаны"></i>
									<?php
								}
								else
								{
									$sms_settings_pointed = false;
									?>
									<i class="fas fa-exclamation-triangle" style="color:#FF0000;cursor:pointer;font-size:1.5em;" title="Настройки не указаны или указаны не полностью"></i>
									<?php
								}
								?>
								
								
							</div>
						</div>
					</div>
					
					<div class="col-md-6">
						<div class="form-group">
							<label class="col-sm-4 control-label">Корректность настроек:</label>
							<div class="col-sm-8">
								<?php
								//Результат проверки настроек выводим только если они заданы
								if( $sms_settings_pointed )
								{
									$sms_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
									$sms_debug_query->execute( array('sms') );
									$sms_debug = $sms_debug_query->fetch();
									
									if( $sms_debug == false )
									{
										?>
										<i class="far fa-circle" style="color:#AAA;cursor:pointer;font-size:1.5em;" title="Корректность не проверялась"></i>
										<?php
									}
									else
									{
										//Коррекно
										if( $sms_debug['status'] == 1 )
										{
											//Выводим время, когда была последняя проверка. Считаем, что проверка за последние сутки - новая, от суток до недели - средняя, более недели - старая
											
											if( time() - $sms_debug['time'] < 86400 )
											{
												//Новая
												$title="";
												$style="";
											}
											else if( time() - $sms_debug['time'] >= 86400 && time() - $sms_debug['time'] < 604800 )
											{
												//Средняя
												$title="Проверка была давно. Желательно проверить снова";
												$style="background-color:#f5de1c;color:#000;cursor:pointer;";
											}
											else
											{
												//Старая
												$title="Проверка была давно. Нужно проверить снова";
												$style="background-color:#ff0000;color:#FFF;cursor:pointer;";
											}
											
											?>
											<i class="fas fa-check-circle" style="color:#0C0;cursor:pointer;font-size:1.5em;" title="Настройки корректны"></i> Проверено <span title="<?php echo $title; ?>" style="<?php echo $style; ?>"><?php echo date("d.m.Y в H:i:s", $sms_debug['time']); ?></span>
											<?php
										}
										else//Не корректно
										{
											?>
											<i class="fas fa-exclamation-triangle" style="color:#C33;cursor:pointer;font-size:1.5em;" title="Настройки не корректны"></i> Проверка была <?php echo date("d.m.Y в H:i:s", $sms_debug['time']); ?>
											<br>Отладочная информация: <span style="background-color:#EFEFEF;"><?php echo $sms_debug['debug_result']; ?></span>
											<?php
										}
									}
								}
								else
								{
									?>
									Сначала задайте настройки для SMS-оператора, прежде, чем проверять их корректность
									<?php
								}
								?>
							</div>
						</div>
					</div>
					
					
					<div class="col-md-2 text-right">
						<a class="btn w-xs btn-success" href="/<?php echo $DP_Config->backend_dir; ?>/control/sms-operatory"><i class="fas fa-mobile-alt"></i> Настройки SMS-оператора</a>
					</div>
					
					
				</div>
				
				
				
				<?php
				//Форму тестирования SMS выводим только если заданы настройки
				if( $sms_settings_pointed )
				{
					?>
					<div class="hr-line-dashed"></div>
				
					<div class="row">
						<div class="col-md-12">
							
							<h5>Тестирование настроек SMS-оператора:</h5>
							<p>Будет отправлено тестовое SMS с целью проверить корректность настройки SMS-оператора. Укажите номер телефона получателя тестового SMS и затем нажмите кнопку "<i class="fas fa-mobile-alt"></i> Тест!"</p>
							
							
							<?php
							//Для автозаполнения номера телефона получателя тестового SMS
							$phone_for_test_sms = "";
							$my_admin_profile = DP_User::getAdminProfile();
							if( !empty($my_admin_profile["phone"]) )
							{
								$phone_for_test_sms = $my_admin_profile["phone"];
							}
							?>
							
							
							<div class="input-group">
								<input type="text" class="form-control" placeholder="Укажите номер телефона получателя, которому будет отправлено тестовое SMS" value="<?php echo $phone_for_test_sms; ?>" id="phone_for_test" />
								<span class="input-group-btn">
									<button class="btn btn-primary" onclick="test_sms();"><i class="fas fa-mobile-alt"></i> Тест!</button> 
								</span>
							</div>
							<script>
							function test_sms()
							{
								var phone_for_test = document.getElementById('phone_for_test').value;
								
								
								if( phone_for_test == '' )
								{
									alert('Не указан номер телефона получателя тестового SMS');
									return;
								}
								
								//Отправка тестового SMS
								jQuery.ajax({
									type: "POST",
									async: false, //Запрос синхронный
									url: "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/control/communications/ajax_test_notification.php",
									dataType: "text",//Тип возвращаемого значения
									data: "contact="+phone_for_test+"&type=phone",
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
												alert('Настройки SMS успешно протестированы');
											}
											else
											{
												alert(answer_ob.message);
											}
										}
										
										
										location = "<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/communications";
									}
								});
								
								
							}
							</script>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</div>
	
	
	<?php
}
?>