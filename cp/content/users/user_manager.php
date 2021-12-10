<?php
/**
 * Страничный скрипт - управление пользователями
*/
defined('_ASTEXE_') or die('No access');


//Сначала проверяем наличие аргументов для операций над учетными записями:
if(!empty($_GET["unlock_user"]))
{
    //Для открытия той же страницы
    $s_page = "";
    if(!empty($_GET['s_page']))
	{
	    $s_page = "&s_page=".$_GET['s_page'];
	}
    
	
	
	//Не даем заблокировать собственную учетную запись
	if( DP_User::getAdminId() == $_GET["user_id"] )
	{
		//Переадресация с сообщением о результатах выполнения
		$warning_message = "Вы попытались заблокировать свою учетную запись. Если это сделать, то Вы потеряете доступ в панель управления. Этого делать нельзя. Действие прервано.";
		
		//Переадресация с сообщением о результатах выполнения
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?warning_message=<?php echo urlencode($warning_message).$s_page; ?>";
        </script>
        <?php
		exit;
	}
	
	
	
	
	
	try
	{
		//Старт транзакции
		if( ! $db_link->beginTransaction()  )
		{
			throw new Exception("Не удалось стартовать транзакцию");
		}
		
		
		if($_GET["unlock_user"] == 1)
		{
			//Разблокируем пользователя
			if( ! $db_link->prepare("UPDATE `users` SET `unlocked` = 1 WHERE `user_id`=?;")->execute( array($_GET["user_id"]) ) )
			{
				throw new Exception("Ошибка разблокировки пользователя");
			}
		}
		else
		{
			//Блокируем пользователя
			if( ! $db_link->prepare("UPDATE `users` SET `unlocked` = 0 WHERE `user_id`=?;")->execute( array($_GET["user_id"]) ) )
			{
				throw new Exception("Ошибка блокировки пользователя");
			}
			
			//Удаляем его сессии
			if( ! $db_link->prepare('DELETE FROM `sessions` WHERE `user_id` = ?;')->execute( array($_GET["user_id"]) ) )
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
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?error_message=<?php echo urlencode($e->getMessage()).$s_page; ?>";
        </script>
        <?php
		exit;
	}

	//Дошли до сюда, значит выполнено ОК
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	$success_message = "Выполнено успешно";
	?>
	<script>
		location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?success_message=<?php echo urlencode($success_message).$s_page; ?>";
	</script>
	<?php
	exit;
}//else if(!empty($_GET["unlock_user"]))
else if(!empty($_GET["delete_users"]))
{
    //Для открытия той же страницы
    $s_page = "";
    if(!empty($_GET['s_page']))
	{
	    $s_page = "&s_page=".$_GET['s_page'];
	}
    
    $users_list_to_del = json_decode($_GET["users_list"], true);
    $SQL_to_del = "DELETE FROM `users` WHERE";
    $binding_values = array();
	for($i=0; $i<count($users_list_to_del); $i++)
    {
		//Блокируем удаление учетной записи админа
		if( DP_User::getAdminId() == $users_list_to_del[$i] )
		{
			//Переадресация с сообщением о результатах выполнения
            $warning_message = "Вы попытались удалить свою учетную запись. Если это сделать, то Вы потеряете доступ в панель управления. Этого делать нельзя. Действие прервано.";
			?>
			<script>
				location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?warning_message=<?php echo $warning_message.$s_page; ?>";
			</script>
			<?php
			exit;
		}
		
		
        $SQL_to_del .= " user_id=?";
		array_push($binding_values, $users_list_to_del[$i]);
        if(!(($i+1) >= count($users_list_to_del))) $SQL_to_del .= " OR";//Если итерация не последняя
    }
	$delete_result = $db_link->prepare($SQL_to_del);
	$delete_result = $delete_result->execute($binding_values);
    if($delete_result === true)//Учетные записи пользователей удалены, теперь удаляем записи таблицы профилей пользователей
    {
        $SQL_to_del = "DELETE FROM `users_profiles` WHERE";
        $binding_values = array();
		for($i=0; $i<count($users_list_to_del); $i++)
        {
            $SQL_to_del .= " user_id=".$users_list_to_del[$i];
			array_push($binding_values, $users_list_to_del[$i]);
            if(!(($i+1) >= count($users_list_to_del))) $SQL_to_del .= " OR";//Если итерация не последняя
        }
        $delete_result = $db_link->prepare($SQL_to_del);
		$delete_result = $delete_result->execute($binding_values);
        if($delete_result === true)//Записи из таблицы профилей пользователей успешно удалены
        {
            //Учетные записи и профили удалены. Теперь нужно удалить привязки к группам:
            $SQL_to_del = "DELETE FROM `users_groups_bind` WHERE";
            $binding_values = array();
			for($i=0; $i<count($users_list_to_del); $i++)
            {
                $SQL_to_del .= " `user_id`=".$users_list_to_del[$i];
				array_push($binding_values, $users_list_to_del[$i]);
                if(!(($i+1) >= count($users_list_to_del))) $SQL_to_del .= " OR";//Если итерация не последняя
            }
            $delete_result = $db_link->prepare($SQL_to_del);
			$delete_result = $delete_result->execute($binding_values);
            if($delete_result === true)
            {
                //Переадресация с сообщением о результатах выполнения
                $success_message = "Удаление выполнено успешно";
                ?>
                <script>
                    location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?success_message=<?php echo $success_message.$s_page; ?>";
                </script>
                <?php
				exit;
            }
            else
            {
                //Переадресация с сообщением о результатах выполнения
                $warning_message = "Выполнено с ошибкой: остались привязки к группам - их нужно зачистить в ручную и разобраться, в чем ошибка";
                ?>
                <script>
                    location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?warning_message=<?php echo $warning_message.$s_page; ?>";
                </script>
                <?php
				exit;
            }
        }
        else//Ошибка при удалении профилей пользователей
        {
            //Переадресация с сообщением о результатах выполнения
            $warning_message = "Выполнено с ошибкой: в таблице профилей пользователей остались записи удаленных пользователей и остались привязки к группам - их нужно зачистить в ручную и разобраться, в чем ошибка";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?warning_message=<?php echo $warning_message.$s_page; ?>";
            </script>
            <?php
			exit;
        }
    }
    else
    {
        //Переадресация с сообщением о результатах выполнения
        $error_message = "Ошибка удаления учетных записей: Не выполнено";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager?error_message=<?php echo $error_message.$s_page; ?>";
        </script>
        <?php
		exit;
    }
}//~else if(!empty($_REQUEST["delete_users"]))
else//Аргументов нет - просто выводим список пользователей
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
				<a class="panel_a" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/usermanager/user">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/user_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<a class="panel_a" href="javascript:void(0);" onclick="delete_users();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/user_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>
			   
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	<?php
	//Получаем массив дополнительных полей регистрации, по которым есть фильтры
	$reg_fields_to_filter = array();//Массив дополнительных полей регистрации, для который есть фильтр
	$reg_fields_query = $db_link->prepare("SELECT * FROM `reg_fields` WHERE `to_filter` = 1 ORDER BY `order`;");
	$reg_fields_query->execute();
	while( $reg_field = $reg_fields_query->fetch() )
	{
		array_push($reg_fields_to_filter, $reg_field);
	}
	?>

	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Фильтр пользователей
			</div>
			<div class="panel-body">
				<?php
				$user_id = "";
				$group_id = "";
				$email = "";
				$phone = "";
				$unlocked = "";
				

				//Получаем текущие значения фильтра:
				$users_filter = NULL;
				if( isset($_COOKIE["users_filter"]) )
				{
					$users_filter = $_COOKIE["users_filter"];
				}
				if($users_filter != NULL)
				{
					$users_filter = json_decode($users_filter, true);
					$user_id = $users_filter["user_id"];
					$group_id = $users_filter["group_id"];
					$email = $users_filter["email"];
					$phone = $users_filter["phone"];
					$unlocked = $users_filter["unlocked"];
					
					//Для дополнительных полей
					for( $i=0; $i < count($reg_fields_to_filter); $i++ )
					{
						if( !isset($users_filter[(string)$reg_fields_to_filter[$i]["name"]]) )
						{
							$users_filter[(string)$reg_fields_to_filter[$i]["name"]] = '';
						}
						
						$reg_fields_to_filter[$i]["filter_current_value"] = $users_filter[(string)$reg_fields_to_filter[$i]["name"]];
					}
				}
				?>
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							ID пользователя
						</label>
						<div class="col-lg-6">
							<input type="text"  id="user_id" value="<?php echo $user_id; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Группа пользователя
						</label>
						<div class="col-lg-6">
							<select id="group_id" class="form-control">
								<option value="-1">Все</option>
								<?php
								$groups_query = $db_link->prepare("SELECT * FROM `groups`");
								$groups_query->execute();
								while($group = $groups_query->fetch() )
								{
									?>
									<option value="<?php echo $group["id"]; ?>"><?php echo $group["value"]." (ID ".$group["id"].")"; ?></option>
									<?php
								}
								?>
							</select>
							<script>
								document.getElementById("group_id").value = <?php echo $group_id; ?>;
							</script>
						</div>
					</div>
				</div>
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							E-mail							
						</label>
						<div class="col-lg-6">
							<input type="text"  id="email" value="<?php echo $email; ?>" class="form-control"/>
						</div>
					</div>
				</div>
				

				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Разблокирован
						</label>
						<div class="col-lg-6">
							<select id="unlocked" class="form-control">
								<option value="-1">Все</option>
								<option value="1">Разблокирован</option>
								<option value="0">Заблокирован</option>
							</select>
							<script>
								document.getElementById("unlocked").value = <?php echo $unlocked; ?>;
							</script>
						</div>
					</div>
				</div>
				
				
				
				<div class="col-lg-4"></div>
				
				
				
				<div class="col-lg-4">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Телефон							
						</label>
						<div class="col-lg-6">
							<input type="text"  id="phone" value="<?php echo $phone; ?>" class="form-control"/>
						</div>
					</div>
				</div>
				
				
				
				<?php
				if( count($reg_fields_to_filter) > 0 )
				{
					?>
					<div class="col-lg-12">
						<h4>Дополнительные поля регистрации:</h4>
					</div>
					<?php
					
					foreach( $reg_fields_to_filter AS $field )
					{
						$reg_field_value = "";
						if( isset($field["filter_current_value"]) )
						{
							$reg_field_value = $field["filter_current_value"];
						}
						
						?>
						<div class="col-lg-4">
							<div class="form-group">
								<label for="" class="col-lg-6 control-label">
									<?php echo $field["caption"]; ?>
								</label>
								<div class="col-lg-6">
									<input type="text"  id="<?php echo $field["name"]; ?>" value="<?php echo $reg_field_value; ?>" class="form-control"/>
								</div>
							</div>
						</div>
						<?php
					}
				}
				?>
			</div>
			<div class="panel-footer">
				<button class="btn btn-success" type="button" onclick="filterUsers();"><i class="fa fa-filter"></i> Отфильтровать</button>
				<button class="btn btn-primary" type="button" onclick="unsetFilterUsers();"><i class="fa fa-square"></i> Снять фильтры</button>
			</div>
		</div>
	</div>
	

    
	
	
	<script>
    // ------------------------------------------------------------------------------------------------
    //Устновка cookie в соответствии с фильтром
    function filterUsers()
    {
        var users_filter = new Object;
        
		//1. ID пользователя
		users_filter.user_id = document.getElementById("user_id").value;
		//2. Группа
		users_filter.group_id = document.getElementById("group_id").value;
		//3.1 E-mail
		users_filter.email = document.getElementById("email").value;
		//3.2 E-mail
		users_filter.phone = document.getElementById("phone").value;
		//5. Разблокирован
		users_filter.unlocked = document.getElementById("unlocked").value;
		
		//Дополнительные поля регистрации
		<?php
		foreach( $reg_fields_to_filter AS $field )
		{
			?>
			users_filter.<?php echo $field["name"]; ?> = document.getElementById("<?php echo $field["name"]; ?>").value;
			<?php
		}
		?>
		
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "users_filter="+JSON.stringify(users_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/users/usermanager';
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterUsers()
    {
        var users_filter = new Object;
        
		users_filter.user_id = "";
		users_filter.group_id = -1;
		users_filter.email = "";
		users_filter.phone = "";
		users_filter.unlocked = -1;
		
		//Дополнительные поля регистрации
		<?php
		foreach( $reg_fields_to_filter AS $field )
		{
			?>
			users_filter.<?php echo $field["name"]; ?> = "";
			<?php
		}
		?>

        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "users_filter="+JSON.stringify(users_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/users/usermanager';
    }
    // ------------------------------------------------------------------------------------------------
    </script>
	
	
	
	
	
	
	
	
    
    
    
    
    
    

    <?php
    //Выводим таблицу
    ?>
	<script>
    // ------------------------------------------------------------------------------------------------
    //Установка куки сортировки пользователей
    function sortUsers(field)
    {
        var asc_desc = "asc";//Направление по умолчанию
        
        //Берем из куки текущий вариант сортировки
        var current_sort_cookie = getCookie("users_sort");
        if(current_sort_cookie != undefined)
        {
            current_sort_cookie = JSON.parse(getCookie("users_sort"));
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
        
        
        var users_sort = new Object;
        users_sort.field = field;//Поле, по которому сортировать
        users_sort.asc_desc = asc_desc;//Направление сортировки
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "users_sort="+JSON.stringify(users_sort)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/users/usermanager';
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
        document.cookie = "users_need_page="+need_page+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location='/<?php echo $DP_Config->backend_dir; ?>/users/usermanager';
    }
    // ------------------------------------------------------------------------------------------------
    </script>
	
	
	<?php
	//Формируем массив полей профиля пользователей, которые выводятся в таблицу
	$profile_colomns = array();
	$profile_colomns_names_checked = array();//Массив для хранения имен полей профиля (для проверки на безопасность вставки в SQL-запрос)
	$profile_colomns_query = $db_link->prepare("SELECT * FROM `reg_fields` WHERE `to_users_table` = 1 ORDER BY `order`;");
	$profile_colomns_query->execute();
	while( $column = $profile_colomns_query->fetch() )
	{
		//Обрабатываем значение перед вставкой в SQL-запрос. $column["name"] - могут быть только буквы и знак _
		$column["name"] = str_replace(array(" ", "-", "#", "'", "(", ")"), "", $column["name"]);
		
		array_push($profile_colomns_names_checked, $column["name"]);
		
		array_push($profile_colomns, $column);
	}
	?>
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Таблица пользователей
			</div>
			<div class="panel-body">
				<div class="table-responsive">
					<table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
						<thead> 
							<tr> 
								<th><input type="checkbox" id="check_uncheck_all" name="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>
								<th><a href="javascript:void(0);" onclick="sortUsers('user_id');" id="user_id_sorter">ID</a></th>
								<th>Группа</th>
								<th><a href="javascript:void(0);" onclick="sortUsers('reg_variant');" id="reg_variant_sorter">Рег.вариант</a></th>
								<th>
									<a href="javascript:void(0);" onclick="sortUsers('email');" id="email_sorter">E-mail</a>
								</th>
								<th>
									<a href="javascript:void(0);" onclick="sortUsers('phone');" id="phone_sorter">Телефон</a>
								</th>
								
								<?php
								//Выводим дополнительные поля регистрации
								foreach($profile_colomns AS $column)
								{
									?>
									<th><a href="javascript:void(0);" onclick="sortUsers('<?php echo $column["name"]; ?>');" id="<?php echo $column["name"]; ?>_sorter"><?php echo $column["caption"]; ?></a></th>
									<?php
								}
								?>
								
								<th><a href="javascript:void(0);" onclick="sortUsers('time_registered');" id="time_registered_sorter">Время создания</a></th>
								<th><a href="javascript:void(0);" onclick="sortUsers('time_last_visit');" id="time_last_visit_sorter">Последний визит</a></th>
								<th><a href="javascript:void(0);" onclick="sortUsers('admin_created');" id="admin_created_sorter">Способ создания</a></th>
								<th><a href="javascript:void(0);" onclick="sortUsers('unlocked');" id="unlocked_sorter">Разблок.</a></th>
							</tr>
							<script>
								<?php
								//Определяем текущую сортировку и обозначаем ее:
								$users_sort = $_COOKIE["users_sort"];
								$sort_field = "user_id";
								$sort_asc_desc = "desc";
								if($users_sort != NULL)
								{
									$users_sort = json_decode($users_sort, true);
									$sort_field = $users_sort["field"];
									$sort_asc_desc = $users_sort["asc_desc"];
								}
								
								if( strtolower($sort_asc_desc) == "asc" )
								{
									$sort_asc_desc = "asc";
								}
								else
								{
									$sort_asc_desc = "desc";
								}
								
								if( array_search($sort_field, array('user_id', 'reg_variant', 'email', 'phone', 'time_registered', 'time_last_visit', 'admin_created', 'unlocked') ) === false && array_search($sort_field,$profile_colomns_names_checked) === false )
								{
									$sort_field = "user_id";
								}
								
								?>
								document.getElementById("<?php echo $sort_field; ?>_sorter").innerHTML += "<img src=\"/content/files/images/sort_<?php echo $sort_asc_desc; ?>.png\" style=\"width:15px\" />";
							</script>
						</thead>
						<tbody>
						<?php
						//Получаем ассоциативный массив group_id => "Имя группы"
						$groups_list_query = $db_link->prepare("SELECT * FROM `groups`");
						$groups_list_query->execute();
						$groups_list = array();
						while( $groups_list_record = $groups_list_query->fetch() )
						{
							$groups_list[$groups_list_record["id"]] = $groups_list_record["value"];
						}
						
						//Массивы для JS с id групп и с чекбоксами групп
						$for_js = "var users_array = new Array();\n";//Выведем массив для JS с чекбоксами пользователй
						$for_js = $for_js."var users_id_array = new Array();\n";//Выведем массив для JS с ID пользователей
						


						//Подстрока с условиями фильтрования пользователей
						$WHERE_CONDITIONS = "";
						$binding_values = array();
						//По куки фильтра:
						$users_filter = NULL;
						if( isset($_COOKIE["users_filter"]) )
						{
							$users_filter = $_COOKIE["users_filter"];
						}
						if($users_filter != NULL)
						{
							$users_filter = json_decode($users_filter, true);
							
							//1. ID
							if($users_filter["user_id"] != "")
							{
								if($WHERE_CONDITIONS != "")
								{
									$WHERE_CONDITIONS .= " AND ";
								}
								$WHERE_CONDITIONS .= " `users`.`user_id` = ?";
								
								array_push($binding_values, $users_filter["user_id"]);
							}
							
							//2. Группа
							if($users_filter["group_id"] != -1)
							{
								if($WHERE_CONDITIONS != "")
								{
									$WHERE_CONDITIONS .= " AND ";
								}
								$WHERE_CONDITIONS .= " `users_groups_bind`.`group_id` = ?";
								
								array_push($binding_values, $users_filter["group_id"]);
							}
							
							//3.1 E-mail
							if($users_filter["email"] != "")
							{
								if($WHERE_CONDITIONS != "")
								{
									$WHERE_CONDITIONS .= " AND ";
								}
								$WHERE_CONDITIONS .= " `users`.`email` = ?";
								
								array_push($binding_values, htmlentities($users_filter["email"]));
							}
							
							//3.2 Телефон
							if($users_filter["phone"] != "")
							{
								if($WHERE_CONDITIONS != "")
								{
									$WHERE_CONDITIONS .= " AND ";
								}
								$WHERE_CONDITIONS .= " `users`.`phone` = ?";
								
								array_push($binding_values, htmlentities($users_filter["phone"]));
							}

							//5. Разблокирован
							if($users_filter["unlocked"] != -1)
							{
								if($WHERE_CONDITIONS != "")
								{
									$WHERE_CONDITIONS .= " AND ";
								}
								$WHERE_CONDITIONS .= " `users`.`unlocked` = ?";
								
								array_push($binding_values, $users_filter["unlocked"]);
							}
							
							
							//Дополнительные поля регистрации:
							foreach( $reg_fields_to_filter AS $field )
							{
								if( isset( $users_filter[(string)$field["name"]] ) )
								{
									if($users_filter[(string)$field["name"]] != "")
									{
										if($WHERE_CONDITIONS != "")
										{
											$WHERE_CONDITIONS .= " AND ";
										}
										$WHERE_CONDITIONS .= " IF( (SELECT COUNT(`users_profiles`.`user_id`) FROM users_profiles WHERE `users_profiles`.`data_key` =? AND `users_profiles`.`data_value` LIKE ? AND `users_profiles`.`user_id` = `users`.`user_id`)=1 , 1, 0 )=1";
										
										array_push($binding_values, $field["name"]);
										array_push($binding_values, "%".htmlentities($field["filter_current_value"])."%");
									}
								}
							}

							
							if($WHERE_CONDITIONS != "")
							{
								$WHERE_CONDITIONS = " WHERE ".$WHERE_CONDITIONS;
							}
						}//~if($users_filter != NULL)
						
						
						//Формируем часть SQL-запрос для получения значений колонок профиля пользователя
						$get_profile_cols_SQL = "";
						foreach($profile_colomns AS $column)
						{
							if( $get_profile_cols_SQL != "" )
							{
								$get_profile_cols_SQL = $get_profile_cols_SQL.",";
							}
							
							$get_profile_cols_SQL = $get_profile_cols_SQL." (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = '".$column["name"]."' AND `user_id` = `users`.`user_id`) AS `".$column["name"]."` ";
						}
						if( $get_profile_cols_SQL != "" )
						{
							$get_profile_cols_SQL = ",".$get_profile_cols_SQL;
						}
						
						
						//Получаем список зарегистрированных пользователей
						$users_list_SQL = "SELECT SQL_CALC_FOUND_ROWS DISTINCT(users.`user_id`) AS `user_id`, 
						`users`.`reg_variant` AS `reg_variant`,
						`users`.`email` AS `email`,
						`users`.`email_confirmed` AS `email_confirmed`,
						`users`.`phone` AS `phone`,
						`users`.`phone_confirmed` AS `phone_confirmed`,
						`users`.`unlocked` AS `unlocked`,
						`users`.`time_registered` AS `time_registered`,
						`users`.`time_last_visit` AS `time_last_visit`,
						`users`.`admin_created` AS `admin_created` ".$get_profile_cols_SQL."
							FROM
						users
						INNER JOIN reg_variants ON reg_variants.id = users.reg_variant
						INNER JOIN users_profiles ON users.user_id = users_profiles.user_id
						INNER JOIN users_groups_bind ON users_groups_bind.user_id = users.user_id".$WHERE_CONDITIONS." ORDER BY `$sort_field` $sort_asc_desc";
						
						

						$users_list_query = $db_link->prepare($users_list_SQL);
						$users_list_query->execute($binding_values);
						
						
						$elements_count_rows_query = $db_link->prepare('SELECT FOUND_ROWS();');
						$elements_count_rows_query->execute();
						$elements_count_rows = $elements_count_rows_query->fetchColumn();
						
						
						//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
						//---------------------------------------------------------------------------------------------->
						//Определяем количество страниц для вывода:
						$p = $DP_Config->list_page_limit;//Штук на страницу
						$count_pages = (int)($elements_count_rows / $p);//Количество страниц
						if($elements_count_rows%$p)//Если остались еще пользователи
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
						
						for($i=0, $d=0; $i<$elements_count_rows && $d<$p; $i++, $d++)//Цикл по всех пользователям
						{
							$users_list_array = $users_list_query->fetch();
							
							//Пропускаем нужное количество блоков в соответствии с номером требуемой страницы
							if($i < $s_page*$p)
							{
								$d--;
								continue;
							}
							
							$a_item = "<a href=\"".$DP_Config->domain_path.$DP_Config->backend_dir."/users/usermanager/user?user_id=".$users_list_array["user_id"]."\">";
							?>
							<tr>
								<td><input type="checkbox" onchange="on_one_check_changed('checked_<?php echo $users_list_array["user_id"]; ?>');" id="checked_<?php echo $users_list_array["user_id"]; ?>" name="checked_<?php echo $users_list_array["user_id"]; ?>"/></td>
								<td><?php echo $a_item.$users_list_array["user_id"]; ?></a></td>
								<td>
									<?php
										//Получаем список групп пользователя
										$user_groups_list_query = $db_link->prepare("SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;");
										$user_groups_list_query->execute( array($users_list_array["user_id"]) );
										$first = true;
										while( $user_group_record = $user_groups_list_query->fetch() )
										{
											if(!$first)
											{
												echo ";<br>";
											}
											else
											{
												$first = false;
											}
											echo $groups_list[$user_group_record["group_id"]];
										}
										
									?>
								</td>
								
								<?php
								$for_js = $for_js."users_array[users_array.length] = \"checked_".$users_list_array["user_id"]."\";\n";//Добавляем элемент для JS
								$for_js = $for_js."users_id_array[users_id_array.length] = ".$users_list_array["user_id"].";\n";//Добавляем элемент для JS
								
								//Получаем Регистрационный вариант пользователя:
								$reg_variant_name_query = $db_link->prepare("SELECT * FROM `reg_variants` WHERE `id`=?;");
								$reg_variant_name_query->execute( array($users_list_array["reg_variant"]) );
								$reg_variant_name_record = $reg_variant_name_query->fetch();
								?>
								<td><?php echo $a_item.$reg_variant_name_record["caption"]; ?></a></td>
								<td>
									<?php echo $a_item.$users_list_array["email"]; ?></a>
									
									<?php
									if( !empty($users_list_array["email"]) )
									{
										if( $users_list_array["email_confirmed"] == 0 )
										{
											?>
											<i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>
											<?php
										}
										else
										{
											?>
											<i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>
											<?php
										}
									}
									?>
									
								</td>
								<td>
									<?php echo $a_item.$users_list_array["phone"]; ?></a>
									
									<?php
									if( !empty($users_list_array["phone"]) )
									{
										if( $users_list_array["phone_confirmed"] == 0 )
										{
											?>
											<i class="fa fa-exclamation-triangle" style="color:#F00;cursor:pointer;" title="Не подтвержден"></i>
											<?php
										}
										else
										{
											?>
											<i class="fa fa-check-circle" style="color:#0A0;cursor:pointer;" title="Подтвержден"></i>
											<?php
										}
									}
									?>
									
								</td>
								
								<?php
								//Выводим дополнительные поля регистрации
								foreach($profile_colomns AS $column)
								{
									?>
									<td><?php echo $a_item.$users_list_array[(string)$column["name"]]; ?></a></td>
									<?php
								}
								?>
								
								
								<td><?php echo $a_item.date("d.m.Y H:i:s", $users_list_array["time_registered"]); ?></a></td>
								<td><?php if($users_list_array["time_last_visit"] != "") echo $a_item.date("d.m.Y H:i:s", $users_list_array["time_last_visit"]); else echo $a_item."Никогда"; ?></a></td>
								<td><?php if($users_list_array["admin_created"] == 1) echo $a_item."Администратор"; else echo $a_item."Регистрация"; ?></a></td>
								<td class="text-center">
									<?php 
										if($users_list_array["unlocked"] == 1) 
										{
											?>
											<form>
												<input type="text" name="unlock_user" value="-1" style="display:none"/>
												<input type="text" name="user_id" value="<?php echo $users_list_array["user_id"]; ?>" style="display:none"/>
												<input type="text" name="s_page" value="<?php echo $s_page; ?>" style="display:none"/>
												<input type="image" class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/on.png" />
											</form>
											<?php
										}
										else
										{
											?>
											<form>
												<input type="text" name="unlock_user" value="1" hidden/>
												<input type="text" name="user_id" value="<?php echo $users_list_array["user_id"]; ?>" hidden/>
												<input type="text" name="s_page" value="<?php echo $s_page; ?>" style="display:none"/>
												<input type="image" class="a_col_img" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/off.png" />
											</form>
											<?php
										}
									?>
								</td>
							</tr>
							<?php
						}//for($i)
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
										<li class="paginate_button <?php echo $previous; ?> <?php echo $next; ?>"><a href="<?php echo "/".$DP_Config->backend_dir."/users/usermanager?s_page=$i"; ?>"><?php echo $i; ?></a></li>
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
    echo $for_js;//Выводим массив с чекбоксами для пользователей
    ?>
    // ----------------------------------------------------------------------------------------
	//Обработка переключения Выделить все/Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
        for(var i=0; i<users_array.length;i++)
        {
            document.getElementById(users_array[i]).checked = state;
        }
    }//~function on_check_uncheck_all()
    // ----------------------------------------------------------------------------------------
    //Обработка переключения одного чекбокса
    function on_one_check_changed(id)
    {
        //Если хотя бы одна группа снята - снимаем общий чекбокс
        for(var i=0; i<users_array.length;i++)
        {
            if(document.getElementById(users_array[i]).checked == false)
            {
                document.getElementById("check_uncheck_all").checked = false;
                break;
            }
        }
    }//~function on_one_check_changed(id)
	// ----------------------------------------------------------------------------------------
    </script>
    
    
    
    
    
    
    
    <!-- Start форма удаления отмеченных пользователей -->
    <form  id="delete_users_form" name="delete_users_form" style="display:none">
        <input type="text" name="delete_users" id="delete_users" value="delete_users" style="display:none"/>
        <input type="text" name="users_list" id="users_list" value="" style="display:none"/>
        <input type="text" name="s_page" id="s_page" value="<?php echo $s_page; ?>" style="display:none"/>
    </form>
    <script>
    //Отправка формы удаления пользователей
    function delete_users()
    {
        //Составляем список отмеченных пользователей:
        var users_list = "";
        for(var i=0; i < users_array.length; i++)
        {
            if(document.getElementById(users_array[i]).checked == true)
            {
                if(users_list.length != 0) users_list += ",";//Если уже есть отмеченные пользователи
                users_list += users_id_array[i];
            }
        }
        if(users_list.length == 0)
        {
            webix.message({type:"error", text:"Не указаны пользователи для удаления"});
            return;
        }
        
		
		
		
		//Блокируем удаление учетной записи админа
		for(var i=0; i<users_array.length;i++)
        {
			if( parseInt(users_id_array[i]) == parseInt(<?php echo DP_User::getAdminId(); ?>) )
			{
				if(document.getElementById(users_array[i]).checked == true)
				{
					alert("Вы отметили для удаления учетную запись, под которой Вы сейчас работаете в панели управления, т.е. свою же учетную запись. Если ее удалить - Вы не сможете зайти в панель управления. Этого делать нельзя. Действие прервано.");
					return;
				}
			}
        }
		
		
		
		
        if(!confirm("Вы действительно хотите удалить отмеченных пользователей?"))
        {
            return;
        }
        
        users_list = "[" + users_list + "]";//Преобразуем в массив JSON
        
        document.getElementById("users_list").value = users_list;
        
        document.forms["delete_users_form"].submit();//Отправка формы удаления пользователей
    }
    </script>
    <!-- End форма удаления отмеченных пользователей -->
    

<?php
}//else - Аргументов нет - просто выводим список пользователей
?>