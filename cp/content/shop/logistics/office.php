<?php
/**
 * Страница управления одним магазином (создание/редактирование)
*/
defined('_ASTEXE_') or die('No access');
?>


<?php
if(!empty($_POST["save_action"]))
{
    $id = $_POST["office_id"];
    $caption = $_POST["caption"];
    $country = $_POST["country"];
    $region = $_POST["region"];
    $city = $_POST["city"];
    $address = $_POST["address"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $coordinates = $_POST["coordinates"];
    $description = $_POST["description"];
    $users = $_POST["users"];
    $timetable = $_POST["timetable"];
    
    
    if($_POST["save_action"] == "create")
    {
        if( $db_link->prepare("INSERT INTO `shop_offices` (`caption`, `country`, `region`, `city`, `address`, `phone`, `email`, `coordinates`, `description`, `users`, `timetable`) VALUES (?,?,?,?,?,?,?,?,?,?,?);")->execute( array($caption, $country, $region, $city, $address, $phone, $email, $coordinates, $description, $users, $timetable) ) != true)
        {
            $error_message = "Не удалось создать учетную запись магазина";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/offices/office?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        
        //Получаем id созданного магазина
        $id = $db_link->lastInsertId();
        
        
        $success_message = "Магазин успешно создан";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/offices/office?office_id=<?php echo $id; ?>&success_message=<?php echo $success_message; ?>";
        </script>
        <?php
        exit;
    }//~if($_POST["save_action"] == "create")
    else if($_POST["save_action"] == "edit")
    {
        if( $db_link->prepare("UPDATE `shop_offices` SET `caption` = ?, `country` = ?, `region` = ?, `city` = ?, `address` = ?, `phone` = ?, `email` = ?, `coordinates` = ?, `description` = ?, `users` = ?, `timetable` = ? WHERE `id` = ?;")->execute( array($caption, $country, $region, $city, $address, $phone, $email, $coordinates, $description, $users, $timetable, $id) ) != true)
        {
            $error_message = "Ошибка обновления информации";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/offices/office?office_id=<?php echo $id; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        else
        {
            $success_message = "Информация успешно обновлена";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/offices/office?office_id=<?php echo $id; ?>&success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
    }//~else if($_POST["save_action"] == "edit")
}//~if(!empty($_POST["save_action"]))
else//Действий нет - выводим страницу
{
    //Исходные данные:
    $page_title = "Создание магазина";
    $action_type = "create";//Тип действия при сохранении
    $id = 0;//ID магазина
    $caption = "";
    $country = "";
    $region = "";
    $city = "";
    $address = "";
    $phone = "";
    $email = "";
    $coordinates = "";
    $description = "";
    $users = array();
    $timetable = "";
    if(!empty($_GET["office_id"]))
    {
        $page_title = "Редактирование магазина";
        $id = $_GET["office_id"];
        $action_type = "edit";
		
		$office_query = $db_link->prepare("SELECT * FROM `shop_offices` WHERE `id` = ?;");
		$office_query->execute( array($id) );
        $office = $office_query->fetch();
        $caption = $office["caption"];
        $country = $office["country"];
        $region = $office["region"];
        $city = $office["city"];
        $address = $office["address"];
        $phone = $office["phone"];
        $email = $office["email"];
        $coordinates = $office["coordinates"];
        $description = $office["description"];
        $users = json_decode($office["users"], true);
        $timetable = $office["timetable"];
    }
    ?>
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    
    <form name="save_form" style="display:none" method="POST">
        <input type="hidden" name="save_action" value="<?php echo $action_type; ?>" />
        <input type="hidden" name="office_id" value="<?php echo $id; ?>" />
        
        <input type="hidden" name="caption" id="caption" value="" />
        <input type="hidden" name="country" id="country" value="" />
        <input type="hidden" name="region" id="region" value="" />
        <input type="hidden" name="city" id="city" value="" />
        <input type="hidden" name="address" id="address" value="" />
        <input type="hidden" name="phone" id="phone" value="" />
        <input type="hidden" name="email" id="email" value="" />
        <input type="hidden" name="coordinates" id="coordinates" value="" />
        <input type="hidden" name="description" id="description" value="" />
        <input type="hidden" name="users" id="users" value="" />
        <input type="hidden" name="timetable" id="timetable" value="" />
    </form>
    
    
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/shop/logistics/offices">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/offices.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Все магазины</div>
				</a>
				
				<?php
				if($id > 0)
				{
					?>
					<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/shop/logistics/offices/office/geo_nodes?office_id=<?php echo $id; ?>">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/geo_link.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Гео-привязка</div>
					</a>
					
					<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/shop/logistics/offices/office/storages_link?office_id=<?php echo $id; ?>">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/storages_link.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Склады и наценки</div>
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
				Общие настройки магазина
			</div>
			<div class="panel-body">
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Название*
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="caption_input" value="<?php echo $caption; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Страна
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="country_input" value="<?php echo $country; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Регион
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="region_input" value="<?php echo $region; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Город
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="city_input" value="<?php echo $city; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Адрес
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="address_input" value="<?php echo $address; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Телефон
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="phone_input" value="<?php echo $phone; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Email
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="email_input" value="<?php echo $email; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Координаты
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" id="coordinates_input" value="<?php echo $coordinates; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Описание
					</label>
					<div class="col-lg-6">
						<textarea class="form-control" id="description_input"><?php echo $description; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Режим работы
					</label>
					<div class="col-lg-6">
						<textarea class="form-control" id="timetable_input"><?php echo $timetable; ?></textarea>
					</div>
				</div>
				
			</div>
		</div>
	</div>
    
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Менеджеры
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Выберите менеджеров из списка
					</label>
					<div class="col-lg-6">
						<?php
						//Получить список групп для бэкенда:
						require_once("content/users/helper.php");//Скрипт со вспомогательными возможностями пакета "Пользователи"
						
						$root_backend_group_query = $db_link->prepare("SELECT * FROM `groups` WHERE `for_backend` = 1;");
						$root_backend_group_query->execute();
						$root_backend_group_record = $root_backend_group_query->fetch();
						$root_backend_group = $root_backend_group_record["id"];//ID корневой группы для бэкэнда
						//Далее по инструкции для функции getInsertedGroups($group) (получение групп с единым корнем)
						$one_root_groups = array();//0
						array_push($one_root_groups, $root_backend_group);//1
						getInsertedGroups($root_backend_group);//2
						//Теперь получаем список пользователей, которые допущены в бэкенд
						$SQL_SELECT_ADMINS = "SELECT DISTINCT(`user_id`) FROM `users_groups_bind` WHERE";
						$binding_values = array();
						for($i=0; $i < count($one_root_groups); $i++)
						{
							if($i > 0) $SQL_SELECT_ADMINS .= " OR";
							$SQL_SELECT_ADMINS .= " `group_id` = ?";
							
							array_push($binding_values, $one_root_groups[$i]);
						}
						
						?>
						<select multiple="multiple" id="users_selector">
						<?php
						$user_query = $db_link->prepare($SQL_SELECT_ADMINS);
						$user_query->execute($binding_values);
						while( $user_id_record = $user_query->fetch() )
						{
							$user_id = $user_id_record["user_id"];
							
							//Запрашиваем подробные данные по пользователю: (<id>)<Фамилия> <Имя> <email phone>
							$general_user_data_query = $db_link->prepare("SELECT `email`, `phone` FROM `users` WHERE `user_id` = ?;");
							$general_user_data_query->execute( array($user_id) );
							$general_user_data_record = $general_user_data_query->fetch();
							$email_phone = '';
							if( !empty( $general_user_data_record["email"] ) )
							{
								$email_phone = 'E-mail: '.$general_user_data_record["email"];
							}
							if( !empty( $general_user_data_record["phone"] ) )
							{
								if( !empty($email_phone) )
								{
									$email_phone = $email_phone . ', ';
								}
								$email_phone = $email_phone.'Телефон: '.$general_user_data_record["phone"];
							}
							//Запрашиваем фамилию:
							$surname_query = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'surname';");
							$surname_query->execute( array($user_id) );
							$surname_record = $surname_query->fetch();
							$surname = $surname_record["data_value"];
							//Запрашиваем имя:
							$name_query = $db_link->prepare("SELECT `data_value` FROM `users_profiles` WHERE `user_id` = ? AND `data_key` = 'name';");
							$name_query->execute( array($user_id) );
							$name_record = $name_query->fetch();
							$name = $name_record["data_value"];
							?>
							<option value="<?php echo $user_id; ?>"><?php echo "($user_id) $surname $name, $email_phone"; ?></option>
							<?php
						}
						?>
						</select>
						<script>
							//Делаем из селектора виджет с чекбоками
							$('#users_selector').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
							
							//Инициализируем выбранные значения
							$('#users_selector').multipleSelect('setSelects', <?php echo json_encode($users); ?>);
						</script>
					</div>
				</div>
			</div>
		</div>
	</div>

    
    
  

    
    
    <script>
    //Сохранение
    function save_action()
    {
        //Проверяем корректноть данных
        if(document.getElementById("caption_input").value == "")
        {
            alert("Заполните название");
            return;
        }
        
        //Менеджеры
        var users_array = [].concat( $("#users_selector").multipleSelect('getSelects') );
        if(users_array.length == 0)
        {
            if(!confirm("Не указан ни один менеджер. Продолжить сохранение?"))
            {
                return;
            }
        }
        document.getElementById("users").value = JSON.stringify(users_array);
        
        //Заполняем текстовые поля
        var fields_names = new Array("caption", "country", "region", "city", "address", "phone", "email", "coordinates", "description", "timetable");
        for(var i=0; i < fields_names.length; i++)
        {
            document.getElementById(fields_names[i]).value = document.getElementById(fields_names[i]+"_input").value;
        }
        
        
        document.forms["save_form"].submit();
    }
    </script>

    <?php
}//else//Действий нет - выводим страницу
?>