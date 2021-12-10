<?php
/**
 * Страничный скрипт для страницы пользователя:
 * - создание;
 * - редактирование.
*/
defined('_ASTEXE_') or die('No access');



//Есть действия
if( isset($_POST["save_action"]) )
{
	//Создаем пользователя
    if($_POST["save_action"] == "create")
    {
		//Через транзакцию
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			
			//1. СОЗДАТЬ УЧЕТНУЮ ЗАПИСЬ В ТАБЛИЦЕ users
			//Флаг блокировки пользователя
			if( !empty( $_POST["unlocked"] ) )
			{
				$unlocked = 1;
			}
			else
			{
				$unlocked = 0;
			}
			//Основные контакты
			$email = $_POST["email"];
			$email_confirmed = $_POST["email_confirmed"];
			$phone = $_POST["phone"];
			$phone_confirmed = $_POST["phone_confirmed"];
			//Запрещаем устанавливать флаг "Подтвержден" для пустого контакта
			if( $email == '' )
			{
				$email_confirmed = 0;
				$email = NULL;//Чтобы не возникло ошибки с duplicate index
			}
			if( $phone == '' )
			{
				$phone_confirmed = 0;
				$phone = NULL;//Чтобы не возникло ошибки с duplicate index
			}
			//Должен быть указан минимум один контакт
			if( $email == NULL && $phone == NULL )
			{
				throw new Exception("Должен быть указан минимум один контакт");
			}
			//Пароль
			$password = $_POST["password"];
			if( ! $db_link->prepare("INSERT INTO `users` (`reg_variant`, `email`, `email_confirmed`, `phone`, `phone_confirmed`, `password`, `unlocked`, `time_registered`, `admin_created`) VALUES (?,?,?,?,?,?,?,?,?);")->execute( array($_POST["reg_variant"], $email, $email_confirmed, $phone, $phone_confirmed, md5($password.$DP_Config->secret_succession), $unlocked, time(), 1) ) )
			{
				throw new Exception("Ошибка создания учетной записи пользователя");
			}
			//1.1 Получить ID созданного пользователя
			$created_user_id = $db_link->lastInsertId();
			
			
			//2. СОЗДАТЬ ЗАПИСИ В ТАБЛИЦЕ users_profiles
			$fields = json_decode($_POST["fields_json"], true);
			for( $i=0 ; $i < count($fields) ; $i++ )
			{
				if( ! $db_link->prepare("INSERT INTO `users_profiles` (`user_id`, `data_key`, `data_value`) VALUES (?,?,?);")->execute( array($created_user_id, $fields[$i]["name"], htmlentities($fields[$i]["value"])) ) )
				{
					throw new Exception("Ошибка записи профиля пользователя");
				}
			}
			
			//3. СОЗДАТЬ ЗАПИСИ В ТАБЛИЦЕ users_groups_bind
			$groups = json_decode($_POST["groups"], true);
			for($i=0; $i<count($groups); $i++)
			{
				if( $db_link->prepare("INSERT INTO `users_groups_bind` (`user_id`, `group_id`) VALUES (?,?);")->execute( array($created_user_id, $groups[$i]) ) != true)
				{
					throw new Exception("Ошибка привязки пользователя к группам");
				}
			}	
		}
		catch (Exception $e)
		{
			//Откатываем все изменения
			$db_link->rollBack();
			?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager/user?error_message=<?php echo urlencode( $e->getMessage() ); ?>";
            </script>
            <?php
            exit;
		}

		//Дошли до сюда, значит выполнено ОК
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		?>
		<script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager/user?user_id=<?php echo $created_user_id; ?>&success_message=<?php echo urlencode("Пользователь успешно создан"); ?>";
        </script>
        <?php
        exit;
    }
	//Редактируем пользователя
    else if($_POST["save_action"] == "update")
    {
        //Через транзакцию
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			//1. ОБНОВЛЯЕМ (UPDATE) УЧЕТНУЮ ЗАПИСЬ В ТАБЛИЦЕ users
			//Флаг блокировки пользователя
			if( !empty( $_POST["unlocked"] ) )
			{
				$unlocked = 1;
			}
			else
			{
				$unlocked = 0;
			}
			//Основные контакты
			$email = $_POST["email"];
			$email_confirmed = $_POST["email_confirmed"];
			$phone = $_POST["phone"];
			$phone_confirmed = $_POST["phone_confirmed"];
			//Запрещаем устанавливать флаг "Подтвержден" для пустого контакта
			if( $email == '' )
			{
				$email_confirmed = 0;
				$email = NULL;//Чтобы не возникло ошибки с duplicate index
			}
			if( $phone == '' )
			{
				$phone_confirmed = 0;
				$phone = NULL;//Чтобы не возникло ошибки с duplicate index
			}
			//Должен быть указан минимум один контакт
			if( $email == NULL && $phone == NULL )
			{
				throw new Exception("Должен быть указан минимум один контакт");
			}
			//Пароль
			$password = $_POST["password"];
			
			$SQL_UPDATE = "UPDATE `users` SET ";
			$SQL_UPDATE .= " `email`=? ";
			$SQL_UPDATE .= ", `email_confirmed`=?";
			$SQL_UPDATE .= ", `phone`=?";
			$SQL_UPDATE .= ", `phone_confirmed`=?";
			if($password != "")
			{
				$SQL_UPDATE .= ", `password`=?";
			}
			$SQL_UPDATE .= ", `unlocked`=?";
			$SQL_UPDATE .= ", `reg_variant`=?";
			$SQL_UPDATE .= " WHERE `user_id` = ?";
			if($password != "")
			{
				$binding_values = array( $email, $email_confirmed, $phone, $phone_confirmed, md5($password.$DP_Config->secret_succession), $unlocked, $_POST["reg_variant"], $_POST["user_id"] );
			}
			else
			{
				$binding_values = array( $email, $email_confirmed, $phone, $phone_confirmed, $unlocked, $_POST["reg_variant"], $_POST["user_id"] );
			}
			if( ! $db_link->prepare($SQL_UPDATE)->execute( $binding_values ) )
			{
				throw new Exception("Ошибка обновления учетной записи пользователя");
			}
			
			//2. ОБНОВЛЕНИЕ ПРОФИЛЯ ПОЛЬЗОВАТЕЛЯ (таблица users_profiles)
			$fields = json_decode($_POST["fields_json"], true);
			//Удаляем текущие записи:
			if( ! $db_link->prepare("DELETE FROM `users_profiles` WHERE `user_id` = ?;")->execute( array($_POST["user_id"]) ) )
			{
				throw new Exception("Ошибка 1 При обработке профиля пользователя");
			}
			else
			{
				for($i=0; $i<count($fields); $i++)
				{
					if( $db_link->prepare("INSERT INTO `users_profiles` (`user_id`, `data_key`, `data_value`) VALUES (?,?,?);")->execute( array($_POST["user_id"], $fields[$i]["name"], htmlentities($fields[$i]["value"])) ) != true)
					{
						throw new Exception("Ошибка 2 При обработке профиля пользователя");
					}
				}
			}
			
			
			
			//3. ОБНОВИТЬ ЗАПИСИ В ТАБЛИЦЕ users_groups_bind
			//Удаляем текущие записи:
			if( $db_link->prepare("DELETE FROM `users_groups_bind` WHERE `user_id` = ?;")->execute( array($_POST["user_id"]) ) != true)
			{
				throw new Exception("Ошибка 1 При обработке групп пользователя");
			}
			else
			{
				$groups = json_decode($_POST["groups"], true);//Новый список групп
				for($i=0; $i<count($groups); $i++)
				{
					if( $db_link->prepare("INSERT INTO `users_groups_bind` (`user_id`, `group_id`) VALUES (?,?);")->execute( array($_POST["user_id"], $groups[$i]) ) != true)
					{
						throw new Exception("Ошибка 2 При обработке групп пользователя");
					}
				}
			}
			
			
			
			//4. Очистка сессий пользователя в случае, если:
			// - или, выставлен флаг unlocked = 0
			// - или, оба контакта неподтверждены
			if( $unlocked == 0 || ( $email_confirmed == 0 && $phone_confirmed == 0 ) )
			{
				if( ! $db_link->prepare('DELETE FROM `sessions` WHERE `user_id` = ?;')->execute( array($_POST["user_id"]) ) )
				{
					throw new Exception("Ошибка удаления текущих сессий пользователя");
				}
			}
		}
		catch (Exception $e)
		{
			//Откатываем все изменения
			$db_link->rollBack();
			?>
            <script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager/user?user_id=<?php echo $_POST["user_id"]; ?>&error_message=<?php echo urlencode( $e->getMessage() ); ?>";
            </script>
            <?php
            exit;
		}

		//Дошли до сюда, значит выполнено ОК
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager/user?user_id=<?php echo $_POST["user_id"]; ?>&success_message=<?php echo urlencode( "Пользователь успешно отредактирован" ); ?>";
        </script>
        <?php
        exit;
		
    }//~else if($_POST["save_action"] == "update")//Редактируем пользователя
}//if() - действия по сохранению
else//Действий нет - выводим страницу
{
    //Для дерева групп
    require_once("content/users/dp_group_record.php");//Определение класса записи группы
    require_once("content/users/get_group_records.php");//Получение объекта иерархии существующих групп для вывода в дерево-webix
    ?>
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
	
	<?php
	$user_id = 0;
	if( isset( $_GET['user_id'] ) )
	{
		$user_id = $_GET['user_id'];
	}
	?>
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="save_action();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/users/usermanager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/user.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер пользователей</div>
				</a>
				
				
				<?php
				//Если страница в режиме редактирования пользователя - выводим кнопки на заказы и счета
				if( $user_id > 0 )
				{
					?>
					<a class="panel_a" href="javascript:void(0);" onclick="locationOrders();">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/store.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Заказы пользователя</div>
					</a>
					<script>
					function locationOrders()
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
						//6. Просмотрен
						orders_filter.viewed = -1;
						//7. Покупатель
						orders_filter.customer = <?php echo $user_id; ?>;
						//Устанавливаем cookie (на полгода)
						var date = new Date(new Date().getTime() + 15552000 * 1000);
						document.cookie = "orders_filter="+JSON.stringify(orders_filter)+"; path=/; expires=" + date.toUTCString();

						//Обновляем страницу
						location='/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders';
					}
					</script>
					
					
					
					<a class="panel_a" href="javascript:void(0);" onclick="locationBalance();">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/credit_card.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Операции с балансом</div>
					</a>
					<script>
					function locationBalance()
					{
						var account_operations_filter = new Object;

						account_operations_filter.time_from = "";
						account_operations_filter.time_to = "";
						account_operations_filter.income = -1;
						account_operations_filter.operation_code = -1;
						account_operations_filter.user_id = <?php echo $user_id; ?>;

						//Устанавливаем cookie (на полгода)
						var date = new Date(new Date().getTime() + 15552000 * 1000);
						document.cookie = "account_operations_filter="+JSON.stringify(account_operations_filter)+"; path=/; expires=" + date.toUTCString();

						//Обновляем страницу
						location='/<?php echo $DP_Config->backend_dir; ?>/shop/finance/account_operations';
					}
					</script>
					<?php
				}//~if( $user_id > 0 )
				?>
				
				
				
				
				<?php
				//Авторизация от имени пользователя
				if( $user_id > 0 )
				{
					?>
					<a class="panel_a" href="javascript:void(0);" onclick="auth_with_user();">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/key.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Авторизация от пользователя</div>
					</a>
					<script>
					//Функция авторизации от имени пользователя
					function auth_with_user()
					{
						jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/users/auth_with_user.php",
							dataType: "json",//Тип возвращаемого значения
							data: "user_id=<?php echo $user_id; ?>",
							success: function(answer){
								if(answer.status == true)
								{
									window.open(
									  '<?php echo $DP_Config->domain_path; ?>',
									  '_blank'
									);
								}
								else
								{
									alert("Ошибка авторизации");
								}
							}
						});
					}
					</script>
					<?php
				}
				?>

			 
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	


    
    <?php
    //СОЗДАЕМ СПИСОК ДОПОЛНИТЕЛЬНЫХ ПОЛЕЙ РЕГИСТРАЦИИ ДЛЯ JAVASCRIPT. ЭТОТ СПИСОК ИСПОЛЬЗУЕТСЯ: 1. Создание (буферизация введеных значений при переключении регистрационных вариантов). 2. Редактирование (буферизация и загрузка текущих значений)
	$all_fields_query = $db_link->prepare("SELECT * FROM `reg_fields` WHERE `main_flag` = ? ORDER BY `order` ASC;");
	$all_fields_query->execute( array(0) );
    ?>
    <script>
    var reg_fields = new Array();//Массив с объектами всех полей
    <?php
    while( $additional_field = $all_fields_query->fetch() )
    {
		?>
        reg_fields[reg_fields.length] = new Object();//Создаем новый объект поля. И инициализируем его поля:
        reg_fields[reg_fields.length - 1].name = "<?php echo $additional_field["name"]; ?>";
        reg_fields[reg_fields.length - 1].caption = "<?php echo $additional_field["caption"]; ?>";
        reg_fields[reg_fields.length - 1].show_for = <?php echo $additional_field["show_for"]; ?>;
        reg_fields[reg_fields.length - 1].required_for = <?php echo $additional_field["required_for"]; ?>;
        reg_fields[reg_fields.length - 1].maxlen = <?php echo $additional_field["maxlen"]; ?>;
        reg_fields[reg_fields.length - 1].regexp = "<?php echo $additional_field["regexp"]; ?>";
        reg_fields[reg_fields.length - 1].widget_type = "<?php echo $additional_field["widget_type"]; ?>";
        reg_fields[reg_fields.length - 1].widget_options = <?php echo $additional_field["widget_options"]; ?>;
        reg_fields[reg_fields.length - 1].value_buffer = "";//Текущее значения - для сохранения при переключении регистрационных вариантов
        <?php
    }
    ?>
    </script>
    
    
    
    
    
    
    <div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Данные пользователя
			</div>
			<div class="panel-body">
				<?php
				if( $user_id > 0 )
				{
					?>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							ID пользователя
						</label>
						<div class="col-lg-6">
							<?php echo $user_id; ?>
						</div>
					</div>
					<?php
				}
				?>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<?php
				//БЛОК РЕГИСТРАЦИОННЫХ ВАРИАНТОВ
				$reg_variants_query = $db_link->prepare("SELECT COUNT(*) FROM `reg_variants` ORDER BY `order` ASC;");
				$reg_variants_query->execute();
				$hidden_style = "";
				if( $reg_variants_query->fetchColumn() == 1)//Регистрационный вариант - единственный - селектор не показываем
				{
					$hidden_style = " style=\"display:none;\"";
				}
				?>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Регистрационный вариант
					</label>
					<div class="col-lg-6">
						<select onchange="regenerateFields();" id="reg_variant_selector"<?php echo $hidden_style; ?> class="form-control">
						<?php
						$reg_variants_query = $db_link->prepare("SELECT * FROM `reg_variants` ORDER BY `order` ASC;");
						$reg_variants_query->execute();
						
						while( $reg_variants_record = $reg_variants_query->fetch() )
						{
							?>
							<option value="<?php echo $reg_variants_record["id"]; ?>"><?php echo $reg_variants_record["caption"]; ?></option>
							<?php
						}
						?>
						</select>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>

				<?php
				//БЛОК БЛОКИРОВКИ
				?>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Разблокирован
					</label>
					<div class="col-lg-6">
						<input type="checkbox" name="unlocked_checkbox" id="unlocked_checkbox" class="form-control" />
					</div>
				</div>
				
				
				
				<?php
				//БЛОК ОСНОВНЫХ ПОЛЕЙ РЕГИСТРАЦИИ
				?>
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12 text-center"><h3><strong>Основные поля регистрации</strong></h3></div>

				<div class="form-group col-lg-8">
					<label for="" class="col-lg-4 control-label">
						E-mail
					</label>
					<div class="col-lg-8">
						<input type="text" name="email" id="email" value="" class="form-control" />
					</div>
				</div>
				
				<div class="form-group col-lg-4">
					<label for="" class="col-lg-6 control-label">
						E-mail подтвержден
					</label>
					<div class="col-lg-6">
						<input type="checkbox" name="email_confirmed" id="email_confirmed" class="form-control" />
					</div>
				</div>
				
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group col-lg-8">
					<label for="" class="col-lg-4 control-label">
						Телефон
					</label>
					<div class="col-lg-8">
						<input type="text" name="phone" id="phone" placeholder="9005556677" value="" class="form-control" />
					</div>
				</div>
				
				
				<div class="form-group col-lg-4">
					<label for="" class="col-lg-6 control-label">
						Телефон подтвержден
					</label>
					<div class="col-lg-6">
						<input type="checkbox" name="phone_confirmed" id="phone_confirmed" class="form-control" />
					</div>
				</div>
				
			
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Пароль
					</label>
					<div class="col-lg-6">
						<input type="password" name="password" id="password" value="" class="form-control" placeholder="Не заполнять, если не требуется менять" />
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Повтор пароля
					</label>
					<div class="col-lg-6">
						<input type="password" name="password_repeat" id="password_repeat" value="" class="form-control" placeholder="Не заполнять, если не требуется менять" />
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				

				
				<!-- Блок для дополнительных полей -->
				<div id="additional_fields_div" class="col-lg-12">
				</div>
				
				
				<script>
				//Перегенировать поля
				function regenerateFields()
				{
					var current_reg_variant = document.getElementById("reg_variant_selector").value;
					
					var additional_html = "";//HTML для дополнительных полей регистрации
					for(var i=0; i < reg_fields.length; i++)
					{
						//Обработка show_for:
						if(reg_fields[i].show_for.indexOf(parseInt(current_reg_variant)) < 0)
						{
							continue;//Это поле не показываем
						}
						
						//Обработка required_for
						var required_for = "";//Для звездочки
						if(reg_fields[i].required_for.indexOf(parseInt(current_reg_variant)) >= 0)
						{
							required_for = "*";//Это поле не показываем
						}
						
						
						additional_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">"+reg_fields[i].caption+required_for+"</label><div class=\"col-lg-6\">";

						//Виджет:
						switch(reg_fields[i].widget_type)
						{
							case "text":
								additional_html += "<input onKeyUp=\"dynamicApplying('"+reg_fields[i].name+"');\" type=\"text\" name=\""+reg_fields[i].name+"\" id=\""+reg_fields[i].name+"\" value='"+reg_fields[i].value_buffer.replace('/(["\'\])/g', "\\$1")+"' class=\"form-control\" />";
								break;
						};
						additional_html += "</div></div>";
						
						additional_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
					}

					
					additional_html = "<div class=\"col-lg-12 text-center\"><h3><strong>Дополнительные поля регистрации</strong></h3></div>" + additional_html;
					
					document.getElementById("additional_fields_div").innerHTML = additional_html;
				}//~function regenerateFields()
				
				
				
				// --------------------------------------------------------------------------
				//Функция динамическиго применния значений для текстовых строк
				function dynamicApplying(attribute)
				{
					var str_value = document.getElementById(attribute).value;//Текущее значение
					//Ищем поле
					for(var i=0; i < reg_fields.length; i++)
					{
						if(reg_fields[i].name == attribute)
						{
							reg_fields[i].value_buffer = str_value;
							console.log(reg_fields[i].value_buffer);
							break;
						}
					}
				}
				
				
				regenerateFields();//Генерируем после загрузки страницы
				</script>
			</div>
		</div>
	</div>
	
	
	
    
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Группы пользователя
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;"></div>
				<script>
					var tree = "";//ПЕРЕМЕННАЯ ДЛЯ ДЕРЕВА
					
					//Инициализация дерева групп после загруки страницы
					function groups_tree_init()
					{
						/*ДЕРЕВО*/
						//Формирование дерева
						tree = new webix.ui({
						
							//Шаблон элемента дерева
							template:function(obj, common)//Шаблон узла дерева
								{
									var folder = common.folder(obj, common);
									var icon = "";
									var value_text = "<span>" + obj.value + "</span>";//Вывод текста
									var checkbox = common.checkbox(obj, common);//Чекбокс
									
									if(obj.for_registrated == true)
									{
										icon += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/check.png' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
									}
									if(obj.for_guests == true)
									{
										icon += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/guest.png' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
									}
									if(obj.for_backend == true)
									{
										icon += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/shield.png' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
									}
									if(obj.unblocked == 0)
									{
										icon += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
									}
									return common.icon(obj, common)+ checkbox + common.folder(obj, common)  + icon + value_text;
								},//~template
						
							editable:false,//редактируемое
							container:"container_A",//id блока div для дерева
							view:"tree",
							select:true,//можно выделять элементы
							drag:false,//можно переносить
						});
						/*~ДЕРЕВО*/
						webix.event(window, "resize", function(){ tree.adjust(); });
					
						var saved_groups = <?php echo $group_tree_dump_JSON; ?>;
						tree.parse(saved_groups);
						tree.openAll();
						
						var user_groups_list = [];//Группы пользователя
						for(var i = 0 ; i < user_groups_list.length ; i++)
						{
							tree.checkItem(user_groups_list[i]);
						}
					}
					groups_tree_init();
				</script>
			</div>
		</div>
	</div>
    
    
    
    
    
    
    
    <!-- Форма сохранения -->
    <form name="save_form" style="display:none" method="POST">
        <input type="hidden" name="save_action" id="save_action" value="" /><!-- Тип действия create / update -->
        <?php
        if( $user_id > 0 )
        {
            ?>
            <input type="hidden" name="user_id" id="user_id" value="<?php echo $user_id; ?>" /><!-- ID редактируемого пользователя -->
            <?php
        }
        ?>
        <input type="hidden" name="reg_variant" id="reg_variant" value="" /><!-- Регистрационный вариант -->
        <input type="checkbox" style="display:none" name="unlocked" id="unlocked" /><!-- Флаг - Разблокирован -->
        <input type="hidden" name="fields_json" id="fields_json" value="" /><!-- Дополнительные поля регистрации -->
        <input type="hidden" name="groups" id="groups" value="" /><!-- Указанные группы в формате массива JSON -->
		
		<input type="hidden" name="email" id="email_form" value="" /><!-- E-mail -->
		<input type="hidden" name="email_confirmed" id="email_confirmed_form" value="" /><!-- Подтверждение E-mail -->
		<input type="hidden" name="phone" id="phone_form" value="" /><!-- Телефон -->
		<input type="hidden" name="phone_confirmed" id="phone_confirmed_form" value="" /><!-- Подтверждение телефона -->
		<input type="hidden" name="password" id="password_form" value="" /><!-- Пароль -->
    </form>
    
    
    <script>
    //Флаг уникальности основного поля регистрации
    var email_check = false;
    var phone_check = false;
    //Функция сохранения
    function save_action()
    {
        //1. ПРОВЕРКА КОРРЕКТНОСТИ ЗАПОЛНЕНИЯ
        //1.1 Текущий регистрационный вариант
        var currentRegVariant = document.getElementById("reg_variant_selector").value;
        
        //1.2 Проверка факта заполнения ДОПОЛНИТЕЛЬНЫХ полей какими-либо значениями
    	for(var i=0; i<reg_fields.length; i++)
    	{
    		if(reg_fields[i].required_for.indexOf(parseInt(currentRegVariant)) != -1)//Заполнение требуется для данного Регистрационного Варианта
    		{
    			if(document.getElementById(reg_fields[i].name).value == "")//Но поле не заполнено
    			{
    				alert("Заполните поле "+reg_fields[i].caption);
    				return false;
    			}
    		}
    	}//for(i)
		
		//1.3 Проверка соответствия заполненных значений ДОПОЛНИТЕЛЬНЫХ регулярным выражениям
    	//Если поле пустое - значит его можно было не заполнять (проверка на факт заполнения следует раньше). Но есть там есть значение, то оно обязательно должно соответствовать RegExp, даже если оно не обязательно к заполнению
    	for(var i=0; i<reg_fields.length; i++)
    	{
    		if(reg_fields[i].show_for.indexOf(parseInt(currentRegVariant)) == -1)//У этого поля не указан текущий Регистрационный Вариант - его нет в форме
    		{
    			continue;
    		}
			
			//Если регулярное выражение пустое - значит пропускаем, т.к. требований к содержимому нет
			if(reg_fields[i].regexp == "")
			{
				continue;
			}
    		
    		if(String(document.getElementById(reg_fields[i].name).value) != "")
    		{
    			var current_value = String(document.getElementById(reg_fields[i].name).value);//Заполненное значение
    			var regex = new RegExp(reg_fields[i].regexp);//Регулярное выражение для поля
    			//Далее ищем подстроку по регулярному выражению
    			var match = regex.exec(String(current_value));
    			if(match == null)
    			{
    				//webix.message({type:"error", text:"В поле "+reg_fields[i].caption+" введено некорректное значение"});
    				alert("В поле "+reg_fields[i].caption+" введено некорректное значение");
    				return false;
    			}
    			else
    			{
    				var match_value = String(match[0]);//Подходящая подстрока
    				if(match_value != current_value)
    				{
    					//webix.message({type:"error", text:"Поле "+reg_fields[i].caption+" содержит лишние знаки"});
    					alert("Поле "+reg_fields[i].caption+" содержит лишние знаки");
    					return false;
    				}
    			}
    			//Заполнено правильно, если: есть подстрока по регулярному выражению и она полностью равна самой строке
    		}
    	}
		
		
		
		//1.4. ПРОВЕРКА ПАРОЛЯ
		<?php
		//Если идет редактирование пользователя, то пароль заполнять не обязательно - в этом случае пароль останется прежним. Если идет создание пользователя, то, заполнить пароль обязательно.
		if( $user_id == 0 )
		{
			?>
			if( document.getElementById("password").value == '' )
			{
				alert("Для создаваемого пользователя необходимо указать пароль");
    			return false;
			}
			<?php
		}
		?>
        //Обработка заполнения пароля:
    	if(document.getElementById("password").value != document.getElementById("password_repeat").value)//Пароли должны совпадать
    	{
    		alert("Пароли не совпадают");
    		return false;
    	}
    	<?php
    	//ДЛЯ РЕДАКТИРОВАНИЯ ПОЛЬЗОВАТЕЛЯ
    	if( $user_id > 0 )//Редактирование пользователя
    	{
    	    ?>
    	    //Проверям минимально допустимую длину пароля
    	    if(document.getElementById("password").value.length < <?php echo $DP_Config->min_password_len; ?> && document.getElementById("password").value.length > 0)
        	{
    		    alert("Пароль должен состоять не менее, чем из <?php echo $DP_Config->min_password_len; ?> знаков");
    		    return false;
        	}
    	    <?php
    	}
    	else//Создание пользователя
    	{
    	    ?>
    	    //Проверям минимально допустимую длину пароля
    	    if(document.getElementById("password").value.length < <?php echo $DP_Config->min_password_len; ?>)
        	{
    		    alert("Пароль должен состоять не менее, чем из <?php echo $DP_Config->min_password_len; ?> знаков");
    		    return false;
        	}
    	    <?php
    	}
    	?>
    	
    	

    	
    	//1.5 Проверка уникальности и корректности Email и Телефона. Только при создании пользователя, либо, если введенный Email или Телефон не равен текущему (т.е. был изменен)
    	var email = document.getElementById("email").value;//Введеный email
		var email_confirmed = 0;
		if( document.getElementById("email_confirmed").checked )
		{
			email_confirmed = 1;
		}
    	var phone = document.getElementById("phone").value;//Введеный phone
		var phone_confirmed = 0;
		if( document.getElementById("phone_confirmed").checked )
		{
			phone_confirmed = 1;
		}
		
		email_check = false;
		phone_check = false;
    	<?php
    	//ПРИ РЕДАКТИРОВАНИИ ПОЛЬЗОВАТЕЛЯ, ПРОВЕРКА УНИКАЛЬНОСТИ email и phone ПРОВОДИТСЯ ТОЛЬКО ЕСЛИ БЫЛИ ИЗМЕНЕНИЯ
    	if( $user_id > 0 )
    	{
    	?>
		if( current_email != email )
		{
    	<?php
    	}
    	?>
		if( email != '' )
		{
			//Сама проверка
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "<?php echo $DP_Config->domain_path; ?>content/users/check_reg_contact.php",
				dataType: "text",//Тип возвращаемого значения
				data: "reg_contact="+email+"&reg_contact_type=email",
				success: function(answer)
				{
					console.log(answer);
					
					var answer_ob = JSON.parse(answer);
					
					//Если некорректный парсинг ответа
					if( typeof answer_ob.status === "undefined" )
					{
						email_check = false;
						alert("Ошибка проверки email");
					}
					else
					{
						//Корректный парсинг ответа
						if(answer_ob.status == true)
						{
							email_check = true;
						}
						else
						{
							email_check = false;
							alert(answer_ob.message);
						}
					}
				}
			}); 
			if(email_check == false)
			{
				return false;
			}
			else if( parseInt(<?php echo DP_User::getAdminId(); ?>) == parseInt(<?php echo $user_id; ?>) )
			{
				//Если ранее E-mail был указан, то нужно показать предупреждение. (если не был указан, то можно не показывать)
				if( current_email != '' )
				{
					alert("Внимание, изменен E-mail! E-mail используется в качестве логина. Поэтому, при следующем входе на сайт используйте уже новый E-mail в качестве логина.");
				}
			}
		}
		else
		{
			//Значит новое значение контакта - пустое. Показываем предупреждение, если это своя учетная запись
			if( parseInt(<?php echo DP_User::getAdminId(); ?>) == parseInt(<?php echo $user_id; ?>) )
			{
				alert("Внимание, E-mail не указан! Поэтому, при следующем входе на сайт используйте только Телефон в качестве логина");
			}
		}
    	<?php
    	//Если редактирование - добавляем скобку } 
        if( $user_id > 0 )
    	{
    	?>
    	}
    	<?php
    	}//------------------------|
    	?>
		//Тоже самое для телефона
		<?php
    	//ПРИ РЕДАКТИРОВАНИИ ПОЛЬЗОВАТЕЛЯ, ПРОВЕРКА УНИКАЛЬНОСТИ email и phone ПРОВОДИТСЯ ТОЛЬКО ЕСЛИ БЫЛИ ИЗМЕНЕНИЯ
    	if( $user_id > 0 )
    	{
    	?>
		if( current_phone != phone )
		{
		<?php
    	}
    	?>
		if( phone != '' )
		{
			//Сама проверка
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "<?php echo $DP_Config->domain_path; ?>content/users/check_reg_contact.php",
				dataType: "text",//Тип возвращаемого значения
				data: "reg_contact="+phone+"&reg_contact_type=phone",
				success: function(answer)
				{
					var answer_ob = JSON.parse(answer);
				
					//Если некорректный парсинг ответа
					if( typeof answer_ob.status === "undefined" )
					{
						phone_check = false;
						alert("Ошибка проверки телефона");
					}
					else
					{
						//Корректный парсинг ответа
						if(answer_ob.status == true)
						{
							phone_check = true;
						}
						else
						{
							phone_check = false;
							alert(answer_ob.message);
						}
					}
				}
			}); 
			if(phone_check == false)
			{
				return false;
			}
			else if( parseInt(<?php echo DP_User::getAdminId(); ?>) == parseInt(<?php echo $user_id; ?>) )
			{
				//Если ранее Телефон был указан, то нужно показать предупреждение. (если не был указан, то можно не показывать)
				if( current_phone != '' )
				{
					alert("Внимание, изменен Телефон! Телефон используется в качестве логина. Поэтому, при следующем входе на сайт используйте уже новый Телефон в качестве логина.");
				}
			}
		}
		else
		{
			//Значит новое значение контакта - пустое. Показываем предупреждение, если это своя учетная запись
			if( parseInt(<?php echo DP_User::getAdminId(); ?>) == parseInt(<?php echo $user_id; ?>) )
			{
				alert("Внимание, Телефон не указан! Поэтому, при следующем входе на сайт используйте только E-mail в качестве логина");
			}
		}
		<?php
    	//Если редактирование - добавляем скобку } 
        if( $user_id > 0 )
    	{
    	?>
		}
        <?php
    	}
    	?>
        
        
        //1.6 Проверка установки групп
        var groups_checked = tree.getChecked();
        if(groups_checked.length == 0)
        {
			alert("Группы не отмечены. Пользователь обязательно должен быть привязан к группе.");
            return false;
        }
		
		
		
		//Проверка контактов
		/*
		1. У любой учетной записи должен быть указан минимум 1 контакт.
		2. Не допускается, чтобы пустой контакт имел статус "Подтвержден" (это можно проверять на уровне сервера - если email или phone пустой - флаг равен 0 автоматически)
		3. У обычного пользователя оба контакта могут быть неподтвержденными (например, так решил администратор сайта - т.е. пользователь не сможет по ним зайти)
		4. У своей учетки - обязательно должен быть хотя бы один подтвержденный контакт
		*/
		//1
		if( email == '' && phone == '' )
		{
			alert("Должен быть указан хотя бы один контакт - E-mail или Телефон");
            return false;
		}
		//2 - на уровне сервера
		
		
		
		<?php
		//1.7. Защита от некорректных действий СО СВОЕЙ УЧЕТНОЙ ЗАПИСЬЮ. 
		if( DP_User::getAdminId() == $user_id )
		{
			//Первым делом защищаем изменение групп пользователей админом, если он редактирует свою учетную запись
			//Формируем массив отмеченных групп. При сохранении - не допустим, чтобы он отличался
			?>
			for(var g=0; g < groups_checked_at_starting.length; g++)
			{
				if(groups_checked.indexOf(groups_checked_at_starting[g]) < 0)
				{
					alert("Вы являетесь Администратором и редактируете свою учетную запись. Вы не можете менять привязку своей учетной записи к группам - это может привести к потере доступа в панель управления. Поэтому, если нужно поменять привязку своей учетной записи к группам - создайте еще одного пользователя с правами Администратора, зайдите в панель управления от его имени и затем редактируйте данную учетную запись.");
					return false;
				}
			}
			for(var g=0; g < groups_checked.length; g++)
			{
				if(groups_checked_at_starting.indexOf(groups_checked[g]) < 0)
				{
					alert("Вы являетесь Администратором и редактируете свою учетную запись. Вы не можете менять привязку своей учетной записи к группам - это может привести к потере доступа в панель управления. Поэтому, если нужно поменять привязку своей учетной записи к группам - создайте еще одного пользователя с правами Администратора, зайдите в панель управления от его имени и затем редактируйте данную учетную запись.");
					return false;
				}
			}
			
			
			//Другие проверки (email, phone, unlocked)
			//Проверка контактов. Как минимум один контакт указан (проверено выше).
			var is_work_email = true;
			if( email == '' || parseInt(email_confirmed) == 0 )
			{
				is_work_email = false;
			}
			var is_work_phone = true;
			if( phone == '' || parseInt(phone_confirmed) == 0 )
			{
				is_work_phone = false;
			}
			if( !is_work_email && !is_work_phone )
			{
				alert('Вы являетесь Администратором и редактируете свою учетную запись. Чтобы не потерять доступ к панели управления, вам необходимо иметь как минимум один заполненный подтвержденный контакт - E-mail или Телефон, т.к. E-mail и Телефон используются в качестве логина.');
				return false;
			}
			
			//Проверка флага блокировки
			if( document.getElementById("unlocked_checkbox").checked == false )
			{
				alert('Вы являетесь Администратором и редактируете свою учетную запись. Нельзя заблокировать свою учетную запись - это может привести к потере доступа в панель управления.');
				return false;
			}
			<?php
		}
		?>
		
		
		
    	
    	//2. ИНИЦИАЛИЗАЦИЯ ФОРМЫ СОХРАНЕНИЯ
    	//2.1 Простые поля
    	<?php
    	if( $user_id > 0 )
    	{
    	    ?>
    	    document.getElementById("save_action").value = "update";//Редактирование существующего
    	    <?php
    	}
    	else
    	{
    	    ?>
    	    document.getElementById("save_action").value = "create";//Создание нового
    	    <?php
    	}
    	?>
        document.getElementById("reg_variant").value = currentRegVariant;//Регистрационный вариант
        document.getElementById("unlocked").checked = document.getElementById("unlocked_checkbox").checked;//Флаг - Разблокирован
        //2.2 Дополнительные поля регистрации
        var reg_fields_to_server = new Array();
        for(var i=0; i<reg_fields.length; i++)
        {
            if(reg_fields[i].show_for.indexOf(parseInt(currentRegVariant)) == -1)//У этого поля не указан текущий Регистрационный Вариант - его нет в форме
    		{
    			continue;
    		}
    		
    		reg_fields_to_server[reg_fields_to_server.length] = new Object;
    		reg_fields_to_server[reg_fields_to_server.length - 1].name = reg_fields[i].name;
    		reg_fields_to_server[reg_fields_to_server.length - 1].value = document.getElementById(reg_fields[i].name).value;
        }
        document.getElementById("fields_json").value = JSON.stringify(reg_fields_to_server);
        //2.3 Группы пользователя
        document.getElementById("groups").value = JSON.stringify(groups_checked);
        //Основные поля регистрации
		document.getElementById('email_form').value = email;
		document.getElementById('phone_form').value = phone;
		document.getElementById('email_confirmed_form').value = email_confirmed;
		document.getElementById('phone_confirmed_form').value = phone_confirmed;
		document.getElementById('password_form').value = document.getElementById("password").value;
		
		
		//alert('Всё ок');
		//return false;
        
        document.forms["save_form"].submit();
    }//function save_action()
    </script>
    
    
    
    
    
    
    
    
    
    
    
    <?php
    //ДЛЯ СТРАНИЦЫ В РЕЖИМЕ РЕДАКТИРОВАНИЯ ПОЛЬЗОВАТЕЛЯ
    $page_mode = "create";//Режим работы страницы по умолчанию - создание пользователя
    //ЕСЛИ ЕСТЬ ID ПОЛЬЗОВАТЕЛЯ - ПОЛУЧАЕМ ЕГО ДАННЫЕ (СТРАНИЦА РАБОТАЕТ В РЕЖИМЕ РЕДАКТИРОВАНИЯ)
    if( $user_id > 0 )
    {
        $page_mode = "update";//Режим работы страницы - редактирование пользователя
        
        //1. Получить регистрационный вариант
        $user_record_query = $db_link->prepare("SELECT * FROM `users` WHERE `user_id` = ?;");
		$user_record_query->execute( array($user_id) );
        $user_record = $user_record_query->fetch();
        $current_reg_variant = $user_record["reg_variant"];
        
        
        //2. Получить учетную запись
        $current_email = $user_record["email"];
        $current_email_confirmed = $user_record["email_confirmed"];
        $current_phone = $user_record["phone"];
        $current_phone_confirmed = $user_record["phone_confirmed"];
        $current_unlocked = $user_record["unlocked"];
        
        
        //3. Получить профиль
        ?>
        <script>
		var current_email = $('<textarea />').html('<?php echo $current_email; ?>').text();
		var current_phone = $('<textarea />').html('<?php echo $current_phone; ?>').text();
		var current_email_confirmed = <?php echo $current_email_confirmed; ?>;
		var current_phone_confirmed = <?php echo $current_phone_confirmed; ?>;
        <?php
		$user_profile_query = $db_link->prepare("SELECT * FROM `users_profiles` WHERE `user_id` = ?;");
		$user_profile_query->execute( array($user_id) );
        while( $user_profile_record = $user_profile_query->fetch() )
        {
            //Задаем значение в поле буферизации списка JavaScript
            ?>
            for(var i=0; i < reg_fields.length; i++)
            {
                if(reg_fields[i].name == '<?php echo $user_profile_record["data_key"]; ?>')
                {
                    reg_fields[i].value_buffer = '<?php echo $user_profile_record["data_value"]; ?>';
                }
            }
            <?
        }
        ?>
        </script>
        <?php
        
        //4. Получить список групп
		$groups_query = $db_link->prepare("SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;");
		$groups_query->execute( array($user_id) );
        $groups = array();
        while( $group_record = $groups_query->fetch() )
        {
            array_push($groups, $group_record["group_id"]);
        }
        
        
        //5. ИНИЦИАЛИЗАЦИЯ
        ?>
        <script>
            //1. Текущий регистрационный вариант:
            document.getElementById("reg_variant_selector").value = <?php echo $current_reg_variant; ?>;
            
            //2. Блокировка:
            document.getElementById("unlocked_checkbox").checked = <?php echo $current_unlocked; ?>;
            
            //3. Основные поля регистрации:
            document.getElementById("email").value = current_email;
            document.getElementById("email_confirmed").checked = current_email_confirmed;
            document.getElementById("phone").value = current_phone;
			document.getElementById("phone_confirmed").checked = current_phone_confirmed;
            
            //4. Отмечаем текущие группы
            var current_groups = <?php echo json_encode($groups); ?>;
            for(var i=0; i < current_groups.length; i++)
            {
                tree.checkItem(current_groups[i]);
            }
            
            regenerateFields();//Генерируем после загрузки страницы
        </script>
        <?php
		
		
		
		//Админ редактирует свою учетную запись. Ставим защиту от изменения групп
		if( DP_User::getAdminId() == $user_id )
		{
			//Формируем массив отмеченных групп. При сохранении - не допустим, чтобы он отличался
			?>
			<script>
			var groups_checked_at_starting = tree.getChecked();
			</script>
			<?php
		}

		
    }
}//else //Действий нет - выводим страницу
?>