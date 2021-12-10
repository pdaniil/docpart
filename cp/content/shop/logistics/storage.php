<?php
/**
 * Страница управления одним складом (создание / редактирование)
 * 
 * 
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if(!empty($_POST["save_action"]))
{
    $id = $_POST["storage_id"];
    $name = trim($_POST["name"]);
	$short_name = trim($_POST["short_name"]);
	$currency = $_POST["currency"];
    $interface_type = $_POST["interface_type"];
    $users = $_POST["users"];
    $connection_options = $_POST["connection_options"];
    
	
	//Обрабатываем настройки склада
	$connection_options = json_decode($connection_options, true);
	foreach( $connection_options AS $key => $object )
	{
		//Из строк удаляем пробелы по краям
		if( ! is_array($object) )
		{
			$connection_options[$key] = trim($object);
		}
		
		//Вероятность доставки - часто ставят знак процента
		if( $key == "probability" )
		{
			$object = str_replace( array(' ', '%') , '', $object);
			$connection_options[$key] = $object;
		}
		
		//Поддомен для API ABCP
		if($_POST["handler_folder"] == 'abcp')
		{
			if( $key == 'subdomain' )
			{
				$object = strtolower($object);
				$object = str_replace( array('http://', 'https://', '.public.api.abcp.ru') , '', $object);
				$object = str_replace( array('/') , '', $object);
				$connection_options[$key] = $object;
			}
		}
	}
	$connection_options = json_encode($connection_options);
	
	
    if($_POST["save_action"] == "create")
    {
        if( $db_link->prepare("INSERT INTO `shop_storages` (`name`, `interface_type`, `users`, `connection_options`, `currency`, `short_name`) VALUES (?,?,?,?,?,?);")->execute( array($name, $interface_type, $users, $connection_options, $currency, $short_name) ) != true)
        {
            $error_message = "Не удалось создать учетную запись склада";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
		else//Успешное создание склада
		{
			$id = $db_link->lastInsertId();//ID созданного склада
			
			$success_message = "Учетная запись склада создана";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?success_message=<?php echo $success_message; ?>&id=<?php echo $id; ?>";
            </script>
            <?php
            exit;
		}
    }//~if($_POST["save_action"] == "create")
    else if($_POST["save_action"] == "edit")
    {		
        if( $db_link->prepare("UPDATE `shop_storages` SET `name` = ?, `interface_type` = ?, `users` = ?, `connection_options` = ?, `currency` = ?, `short_name` = ?  WHERE `id` = ?;")->execute( array($name, $interface_type, $users, $connection_options, $currency, $short_name, $id) ) != true)
        {
            $error_message = "Не удалось обновить учетную запись склада";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?id=<?php echo $id; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
		else//Обновление успешно
		{
			$success_message = "Склад успешно отредактирован";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/storages/storage?id=<?php echo $id; ?>&success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
		}
    }//~else if($_POST["save_action"] == "edit")
}//~if(!empty($_POST["save_action"]))
else//Вывод страницы
{
    //Исходные данные
    $action_type = "create";//Тип действия при сохранении
    $page_caption = "Создание склада";//Название страницы
    
    $id = 0;//ID склада
    $name = "";//Название склада
	$short_name = "";//Короткое название
	$currency = $DP_Config->shop_currency;//По умолчанию ставим валюту магазина
    $interface_type = 1;//Тип интерфейса
    $users = array();//Список кладовщиков
    $connection_options = array();//Настройки подключения
    if(!empty($_GET["id"]))
    {
        $id = $_GET["id"];//ID склада
        
        $action_type = "edit";//Тип действия при сохранении
        
		$storage_query = $db_link->prepare("SELECT * FROM `shop_storages` WHERE `id`=?;");
		$storage_query->execute( array($id) );
        $storage_record = $storage_query->fetch();
        
        $name = $storage_record["name"];//Название склада
		$short_name = $storage_record["short_name"];
		$page_caption = "Редактирование склада <b>$name</b>";//Название страницы
        $interface_type = $storage_record["interface_type"];//Тип интерфейса
		$currency = $storage_record["currency"];//Валюта склада
        $users = $storage_record["users"];//Список кладовщиков (JSON)
        $connection_options = $storage_record["connection_options"];//Настройки подключения (JSON)
    }
    ?>
    
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    <!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none">
        <input name="save_action" id="save_action" type="text" value="<?php echo $action_type; ?>" style="display:none"/>
        
        <!-- Настройки склада -->
        <input type="text" name="storage_id" id="storage_id" value="<?php echo $id; ?>" />
        <input type="text" name="name" id="name" value="" />
		<input type="text" name="short_name" id="short_name" value="" />
		<input type="text" name="currency" id="currency" value="" />
        <input type="text" name="interface_type" id="interface_type" value="" />
        <input type="text" name="users" id="users" value="" />
        <input type="text" name="connection_options" id="connection_options" value="" />
        <input type="text" name="handler_folder" id="handler_folder" value="" />
    </form>
    <!--Форма для отправки-->
    
    
	
	
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
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/storages">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/storage.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер складов</div>
				</a>
				
				<?php
				if( (int)$id > 0 && (int)$DP_Config->suppliers_api_debug == 1 )
				{
					$id = (int)$id;
					
					if( file_exists($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/tmp/suppliers_api_log/".$id.".php") )
					{		
						print_backend_button( array("caption"=>"Отладка", "url"=>"/".$DP_Config->backend_dir."/shop/logistics/storages/storage/api_debug?storage_id=".$id, "background_color"=>"#f1c40f", "fontawesome_class"=>"fas fa-bug", "target"=>"_blank", "show_anyway"=>true) );
					}
				}
				?>
				

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	

    
    
    
    
    
    
    <script>
    //Объект описания технических интерфейсов
    var interfaces_types = new Array();
    </script>
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Общие настройки склада
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Название склада
					</label>
					<div class="col-lg-6">
						<input type="text" name="name_input" id="name_input" value="<?php echo $name; ?>" class="form-control" />
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Короткое название для покупателей
					</label>
					<div class="col-lg-6">
						<input type="text" name="short_name_input" id="short_name_input" value="<?php echo $short_name; ?>" class="form-control" />
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Валюта склада
					</label>
					<div class="col-lg-6">
						<select name="currency_select" id="currency_select" class="form-control">
						<?php
						$currencies_query = $db_link->prepare("SELECT * FROM `shop_currencies` WHERE `available` = 1 ORDER BY `order`;");
						$currencies_query->execute();
						while( $currency_record = $currencies_query->fetch() )
						{
							?>
							<option value="<?php echo $currency_record["iso_code"]; ?>"><?php echo $currency_record["iso_name"]; ?></option>
							<?php
						}
						?>
						</select>
						<script>
						document.getElementById("currency_select").value = <?php echo $currency; ?>;
						</script>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тип технического интерфейса
					</label>
					<div class="col-lg-6">
						<select name="interface_type_select" id="interface_type_select" onchange="on_interface_changed();" class="form-control">
    	                    <?php
    	                        //Запрашиваем ВСЕ типы технических интерфейсов - для инициализации виджетов настройки
								$SQL_SELECT_interfaces_types = "SELECT *, 
								Replace(Replace(Replace(Replace(Replace(`name`, 'Веб-сервис', 'API'), 'Веб сервис', 'API') , '(API)', ''), '(Web-Сервис)',''), 'Форум-Авто (forum-auto.ru)', 'API Форум-Авто (forum-auto.ru)') AS `name` 
								FROM `shop_storages_interfaces_types` ORDER BY `name`;";
								
								$storages_interfaces_types_query = $db_link->prepare($SQL_SELECT_interfaces_types);
								$storages_interfaces_types_query->execute();
								
    	                        while( $interface = $storages_interfaces_types_query->fetch() )
    	                        {
    	                            //Инициализация опций соединения со складом для типа "select"
    	                            $connection_options_of_interface = json_decode($interface["connection_options"], true);
    	                            for($i=0; $i < count($connection_options_of_interface); $i++)
    	                            {
    	                                if($connection_options_of_interface[$i]["type"] == "select")
    	                                {
    	                                    if($connection_options_of_interface[$i]["options_way"] == "sql")
    	                                    {
    	                                        //Делаем запрос элементов списка
    	                                        $SQL_SELECT_OPTIONS = $connection_options_of_interface[$i]["options"];
    	                                        $options = array();//Сюда запишем полученные свойства через SQL
    	                                        
												$select_items_query = $db_link->prepare($SQL_SELECT_OPTIONS);
												$select_items_query->execute();
    	                                        while( $options_record = $select_items_query->fetch() )
                    	                        {
                    	                            array_push($options, array("caption"=>$options_record["caption"], "value"=>$options_record["value"]));
                    	                        }
                    	                        $connection_options_of_interface[$i]["options"] = $options;//Заменяем строку SQL-запроса на массив свойств
    	                                    }
    	                                }
    	                            }
    	                            
    	                            ?>
    	                            <script>
    	                            interfaces_types[<?php echo $interface["id"]; ?>] = new Object;//Добавляем описание интерфейса в объект
    	                            interfaces_types[<?php echo $interface["id"]; ?>].connection_options = JSON.parse('<?php echo json_encode($connection_options_of_interface); ?>');
    	                            interfaces_types[<?php echo $interface["id"]; ?>].product_type = <?php echo $interface["product_type"]; ?>;
									interfaces_types[<?php echo $interface["id"]; ?>].handler_folder = '<?php echo $interface["handler_folder"]; ?>';
									interfaces_types[<?php echo $interface["id"]; ?>].description = '<?php echo $interface["description"]; ?>';
									
    	                            </script>
    	                            <option value="<?php echo $interface["id"]; ?>"><?php echo $interface["name"]; ?></option>
    	                            <?php
    	                        }
    	                    ?>
    	                </select>
						<style>
						#interface_type_select,
						#interface_type_select option
						{
							text-transform: capitalize;
						}
						</style>
					</div>
				</div>
				
				
				<div class="hr-line-dashed col-lg-12" id="type_description_hr" style="display:none;"></div>
				<div class="form-group" id="type_description_form" style="display:none;">
					<label for="" class="col-lg-6 control-label">
						Описание выбранного типа
					</label>
					<div class="col-lg-6" id="type_description_text">
					</div>
				</div>
				
				
			</div>
		</div>
	</div>
	

    
    <script>
    //Обработка смены типа интерфейса
    function on_interface_changed()
    {
        var current_interface_type = document.getElementById("interface_type_select").value;
    
        var mysql_options_div_fields = document.getElementById("mysql_options_div_fields");
        
        var html = "";
        
		if(interfaces_types[current_interface_type].connection_options.length == 0)
		{
			document.getElementById("connection_options_div").setAttribute("style", "display:none;");
		}
		else
		{
			document.getElementById("connection_options_div").setAttribute("style", "");
		}
		
        for(var i=0; i < interfaces_types[current_interface_type].connection_options.length; i++)
        {
			if( interfaces_types[current_interface_type].connection_options[i].type == "hidden" )
			{
				continue;
			}
			
			if( i > 0)
			{
				html += "<div class=\"hr-line-dashed col-lg-12\"></div>";
			}
			
            //В зависимости от типа свойства - выводим виджет для настроки
			html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">"+interfaces_types[current_interface_type].connection_options[i].caption+"</label><div class=\"col-lg-6\">";
            
            if(interfaces_types[current_interface_type].connection_options[i].type == "text" || 
            interfaces_types[current_interface_type].connection_options[i].type == "number" || 
            interfaces_types[current_interface_type].connection_options[i].type == "color" || 
            interfaces_types[current_interface_type].connection_options[i].type == "password" ||
            interfaces_types[current_interface_type].connection_options[i].type == "checkbox")
            {
				var value_default = "";
				if(interfaces_types[current_interface_type].connection_options[i].type == "color")value_default = "#FFFFFF";
                html += "<input class=\"form-control\" type=\""+interfaces_types[current_interface_type].connection_options[i].type+"\" id=\""+interfaces_types[current_interface_type].connection_options[i].name+"\" value=\""+value_default+"\" />";
            }
            else if(interfaces_types[current_interface_type].connection_options[i].type == "select")
            {
                html += "<select class=\"form-control\" id=\""+interfaces_types[current_interface_type].connection_options[i].name+"\">";
                    for(var o=0; o < interfaces_types[current_interface_type].connection_options[i].options.length; o++)
                    {
                        html += "<option value=\""+interfaces_types[current_interface_type].connection_options[i].options[o].value+"\">"+interfaces_types[current_interface_type].connection_options[i].options[o].caption+"</option>";
                    }
                html += "</select>";
            }
			
			html += "</div></div>";
        }
        
        mysql_options_div_fields.innerHTML = html;
        
		if(interfaces_types[current_interface_type].handler_folder == "rossko") {
            <?php if($action_type == "create") : ?>
            document.getElementById("hidden_connection_options_div").innerHTML = '<div class="hpanel">' +
                '<div class="panel-heading hbuilt">Настройки Rossko Личный кабинет</div>' +
            		'<div class="panel-body"><div class=\"alert alert-warning\">Заполните поля KEY1 и KEY2 и обновите страницу.</div></div>' +
            	'</div>';
            <?php endif; ?>
        }
		
		//Указываем тип технического интерфейса в форму (может потребоваться при сохранении настроек склада)
		document.getElementById("handler_folder").value = interfaces_types[current_interface_type].handler_folder;
		
		
		//Описание типа:
		if( interfaces_types[current_interface_type].description != "" )
		{
			document.getElementById("type_description_text").innerHTML = interfaces_types[current_interface_type].description;
			document.getElementById("type_description_hr").setAttribute("style", "display:block;");
			document.getElementById("type_description_form").setAttribute("style", "display:block;");
		}
		else
		{
			document.getElementById("type_description_text").innerHTML = "";
			document.getElementById("type_description_hr").setAttribute("style", "display:none;");
			document.getElementById("type_description_form").setAttribute("style", "display:none;");
		}
    }
    </script>
    
    
	<div class="col-lg-12">
         <div id="hidden_connection_options_div"></div>
    </div>
    
    
	<div class="col-lg-12" id="connection_options_div">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Настройки подключения
			</div>
			<div class="panel-body" id="mysql_options_div_fields">
			</div>
		</div>
	</div>
	
	
    
	
    <div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Кладовщики
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Выберите кладовщиков из списка
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
							<option value="<?php echo $user_id; ?>"><?php echo "($user_id) $surname $name $email_phone"; ?></option>
							<?php
						}
						?>
						</select>
						<script>
							//Делаем из селектора виджет с чекбоками
							$('#users_selector').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
						</script>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
    
    <script>
    //Функция сохранения
    function save_action()
    {
        //1. Название склада:
        if(document.getElementById("name_input").value == "")
        {
            alert("Заполните название");
            return;
        }
        document.getElementById("name").value = document.getElementById("name_input").value;
        
		
		//1.05 Короткое название
		if(document.getElementById("short_name_input").value == "")
        {
            alert("Заполните короткое название");
            return;
        }
        document.getElementById("short_name").value = document.getElementById("short_name_input").value;
		
		
		//1.1 Валюта склада
		document.getElementById("currency").value = document.getElementById("currency_select").value;
		
		
        //2. Тип интерфеса
        var interface_type = document.getElementById("interface_type_select").value;
        document.getElementById("interface_type").value = interface_type;
        
        //3. Кладовщики
        var users_array = [].concat( $("#users_selector").multipleSelect('getSelects') );
        document.getElementById("users").value = JSON.stringify(users_array);
        
        //3. Настройки подключения к интерфейсу
        var connection_options = new Object;//Объект настроек
        for(var i=0; i < interfaces_types[interface_type].connection_options.length; i++)
        {
			if( interfaces_types[interface_type].connection_options[i].type == "hidden" )
			{
				continue;
			}
			
			
            if(interfaces_types[interface_type].connection_options[i].type == "checkbox")//Запись значений для чекбокса
            {
                if(document.getElementById(interfaces_types[interface_type].connection_options[i].name).checked)
                {
                    connection_options[interfaces_types[interface_type].connection_options[i].name] = 1;
                }
                else
                {
                    connection_options[interfaces_types[interface_type].connection_options[i].name] = 0;
                }
            }
            else//Запись значений для строковых типов (text, password) и для списков (select)
            {
                connection_options[interfaces_types[interface_type].connection_options[i].name] = document.getElementById(interfaces_types[interface_type].connection_options[i].name).value;
            }
        }

		if(interfaces_types[interface_type].handler_folder == "rossko") {
            let privateConnection = [
                    'requisite_id',
                    'delivery_id',
                    'address_id',
                    'payment_id',
                    'delivery_name',
                    'delivery_phone',
                    'delivery_comment',
                    'delivery_parts'
                ];
                
            privateConnection.forEach(function(item, i, arr) {
                let input = document.getElementById(item);
                if(item == 'delivery_parts') {
                    if(input !== null) {
                        let value = input.checked ? 1 : 0;
                        connection_options[item] = value;
                    }
                } else {
                    if(input !== null && input.value !== '') {
                        connection_options[item] = input.value;
                    }
                }
            });
        }

        document.getElementById("connection_options").value = JSON.stringify(connection_options);
        
        console.log(document.getElementById("connection_options").value);
        
        //alert("Ok");
        //return;
        
        document.forms["form_to_save"].submit();
    }//~function save_action()
    
    
    <?php
    //ДЕЙСТВИЕ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ (ИНИЦИАЛИЗАЦИЯ ЗНАЧЕНИЙ)
    //Если тип действия - редактирование, то инициализируем страницу текущими данными
    if($action_type == "edit")
    {
        ?>
        //Тип интерфейса
        var saved_interface_type = parseInt(<?php echo $interface_type; ?>);
        document.getElementById("interface_type_select").value = saved_interface_type;
        on_interface_changed();//Обработка текущего выбора типа интерфейса (для отображения полей ввода)
        
        
        //Кладовщики
        $('#users_selector').multipleSelect('setSelects', <?php echo $users; ?>);
        
        //Настройки соединения
        var connection_options = JSON.parse('<?php echo $connection_options; ?>');
        for(var i=0; i < interfaces_types[saved_interface_type].connection_options.length; i++)
        {
			if( interfaces_types[saved_interface_type].connection_options[i].type == "hidden" )
			{
				continue;
			}
			
            if(interfaces_types[saved_interface_type].connection_options[i].type == "checkbox")//Инициализация значений для чекбокса
            {
                document.getElementById(interfaces_types[saved_interface_type].connection_options[i].name).checked = parseInt(connection_options[interfaces_types[saved_interface_type].connection_options[i].name]);
            }
            else//Инициализация значений для строковых типов (text, password) и для списков (select)
            {
                document.getElementById(interfaces_types[saved_interface_type].connection_options[i].name).value = connection_options[interfaces_types[saved_interface_type].connection_options[i].name];
            }
        }

		if(interfaces_types[saved_interface_type].handler_folder == "rossko") {
            
			//Объект для запроса
			var request_object = new Object;
			request_object.action = 'get_data';
			request_object.key1 = document.getElementById("key1").value || null;
			request_object.key2 = document.getElementById("key2").value || null;
			request_object.connection_options = connection_options;
			
			   jQuery.ajax({
				type: "POST",
				async: false,
				url: "/content/shop/docpart/suppliers_handlers/rossko/storage_options.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object)),
				success: function(answer)
				{
					console.log(answer);
					
					if (answer.html != "") {
						document.getElementById("hidden_connection_options_div").innerHTML = answer.html;
					}
				}
			}); 
	}
        <?php
    }
    else//Открыли страницу для создания нового склада
    {
        ?>
        on_interface_changed();//Обработка текущего выбора типа интерфейса
		
        <?php
    }
    ?>
    </script>
    
    <?php
}//~else//Вывод страницы
?>