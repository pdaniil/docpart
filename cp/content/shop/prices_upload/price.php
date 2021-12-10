<?php
/**
 * Страница для создания и редактирования одного прайс-листа
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if( !empty($_POST["action"]) )//Действия
{
    //Данные формы - одинаково для создания и редактирования
    $price_id = $_POST["price_id"];
    $name = $_POST["name"];
    $load_mode = $_POST["load_mode"];
    
    $ftp_host = $_POST["ftp_host"];
    $ftp_user = $_POST["ftp_user"];
    $ftp_password = $_POST["ftp_password"];
    
    $sender_email = $_POST["sender_email"];
	
	if( isset($_POST['delete_email_messages']) && $_POST['delete_email_messages'] == 'delete_email_messages' )
	{
		$delete_email_messages = 1;
	}
	else
	{
		$delete_email_messages = 0;
	}
	
	
	
    
    $strings_to_left = (int)$_POST["strings_to_left"];
    $manufacturer_col = (int)$_POST["manufacturer_col"];
    $article_col = (int)$_POST["article_col"];
    $name_col = (int)$_POST["name_col"];
    $exist_col = (int)$_POST["exist_col"];
    $price_col = (int)$_POST["price_col"];
    $time_to_exe_col = (int)$_POST["time_to_exe_col"];
    $storage_col = (int)$_POST["storage_col"];
    $min_order_col = (int)$_POST["min_order_col"];
    
	$file_name_substring = $_POST["file_name_substring"];
	
    $clean_before = 0;
    if(!empty($_POST["clean_before"]))
    {
        $clean_before = 1;
    }
    
    
    if($_POST["action"] == "create")
    {
        if( $db_link->prepare("INSERT INTO `shop_docpart_prices` (`name`, `load_mode`, `ftp_host`, `ftp_user`, `ftp_password`, `sender_email`, `delete_email_messages`, `strings_to_left`, `manufacturer_col`, `article_col`, `name_col`, `exist_col`, `price_col`, `time_to_exe_col`, `storage_col`, `clean_before`, `min_order_col`, `file_name_substring`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);")->execute( array($name, $load_mode, $ftp_host, $ftp_user, $ftp_password, $sender_email, $delete_email_messages, $strings_to_left, $manufacturer_col, $article_col, $name_col, $exist_col, $price_col, $time_to_exe_col, $storage_col, $clean_before, $min_order_col, $file_name_substring) ) != true)
        {
            $error_message = "Не удалось создать учетную запись прайс-листа";
            ?>
            <script>
                location="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/price?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        else//Учетная запись создана
        {
            //Получаем ID созданной записи
            $price_id = $db_link->lastInsertId();
            
            $success_message = "Конфигурация успешно создана";
			?>
			<script>
				location="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/price?price_id=<?php echo $price_id; ?>&success_message=<?php echo $success_message; ?>";
			</script>
			<?php
			exit;
        }
    }
    else//Редактирование
    {
        $SQL_UPDATE = "UPDATE `shop_docpart_prices` SET 
            `name` = ?, 
            `load_mode` = ?, 
            `ftp_host` = ?, 
            `ftp_user` = ?, 
            `ftp_password` = ?, 
            `sender_email` = ?, 
			`delete_email_messages` = ?, 
            `strings_to_left` = ?, 
            `manufacturer_col` = ?, 
            `article_col` = ?,
            `name_col` = ?, 
            `exist_col` = ?, 
            `price_col` = ?, 
            `time_to_exe_col` = ?, 
            `storage_col` = ?, 
            `clean_before` = ?,
            `min_order_col` = ?,
			`file_name_substring` = ?
            WHERE `id` = ?;";
			
			
		$binding_values = array($name, $load_mode, $ftp_host, $ftp_user, $ftp_password, $sender_email, $delete_email_messages, $strings_to_left, $manufacturer_col, $article_col, $name_col, $exist_col, $price_col, $time_to_exe_col, $storage_col, $clean_before, $min_order_col, $file_name_substring, $price_id);
			
        
        
        if( $db_link->prepare($SQL_UPDATE)->execute($binding_values) != true)
        {
            $error_message = "Не удалось обновить учетную запись прайс-листа";
            ?>
            <script>
                location="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/price?price_id=<?php echo $price_id; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        else//Учетную запись обновили
        {
            $success_message = "Конфигурация успешно обновлена";
            ?>
            <script>
                location="/<?php echo $DP_Config->backend_dir; ?>/shop/prices/price?price_id=<?php echo $price_id; ?>&success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
    }
}//if( !empty($_POST["action"]) )//Действия
else//Действий нет - выводим страницу
{
    ?>
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    <?php
    //Исходные данные
    $price_id = 0;
    $page_name = "Создание учетной записи прайс-листа";
    $action_type = "create";
    
    $name = "";
    $load_mode = 1;
    
    $ftp_host = "";
    $ftp_user = "";
    $ftp_password = "";
    
    $sender_email = "";
	$delete_email_messages = 0;
    
    $strings_to_left = 0;
    $manufacturer_col = "";
    $article_col = "";
    $name_col = "";
    $exist_col = "";
    $price_col = "";
    $time_to_exe_col = "";
    $storage_col = "";
    $min_order_col = "";
	$file_name_substring = "";
    $clean_before = 0;
    
    
    //Переход для редактирования прайс-листа
    if(!empty($_GET["price_id"]))
    {
        $price_id = $_GET["price_id"];
		
		$price_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices` WHERE `id` = ?;");
		$price_query->execute( array($price_id) );
        $price_record = $price_query->fetch();
        
        $name = $price_record["name"];
        $page_name = "Редактирование настроек прайс-листа \"$name\"";
        $action_type = "edit";
        $load_mode = $price_record["load_mode"];
        
        $ftp_host = $price_record["ftp_host"];
        $ftp_user = $price_record["ftp_user"];
        $ftp_password = $price_record["ftp_password"];
        
        $sender_email = $price_record["sender_email"];
		$delete_email_messages = $price_record["delete_email_messages"];
        
        $strings_to_left = $price_record["strings_to_left"];
        $manufacturer_col = $price_record["manufacturer_col"];
        $article_col = $price_record["article_col"];
        $name_col = $price_record["name_col"];
        $exist_col = $price_record["exist_col"];
        $price_col = $price_record["price_col"];
        $time_to_exe_col = $price_record["time_to_exe_col"];
        $storage_col = $price_record["storage_col"];
        $min_order_col = $price_record["min_order_col"];
		$file_name_substring = $price_record["file_name_substring"];
        $clean_before = $price_record["clean_before"];
    }
    
    ?>
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" href="javascript:void(0);" onclick="document.forms['save_form'].submit();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/excel.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер прайс-листов</div>
				</a>
				
				
				<?php
				if( $price_id > 0 )
				{
					print_backend_button( array("url"=>"/".$DP_Config->backend_dir."/shop/prices/review?price_id=".$price_id, "background_color"=>"#8e44ad", "fontawesome_class"=>"fas fa-sync", "caption"=>"Простановка цен") );
				}
				?>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
    

    
    
    
    <form method="POST" name="save_form">
        <input type="hidden" name="action" value="<?php echo $action_type; ?>" />
        
        <input type="hidden" name="price_id" value="<?php echo $price_id; ?>" />
		
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Общие настройки
				</div>
				<div class="panel-body">
				
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Название прайс-листа
						</label>
						<div class="col-lg-6">
							<input type="text" name="name" value="<?php echo $name; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Способ загрузки
						</label>
						<div class="col-lg-6">
							<select name="load_mode" id="load_mode" onchange="on_load_mode_changed();" class="form-control">
    	                        <?php
    	                        //Получим способы загрузки прайс-листов
                                $load_modes_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices_load_modes` ORDER BY `id` ASC;");
								$load_modes_query->execute();
                                while($load_mode_record = $load_modes_query->fetch() )
                                {
                                    ?>
                                    <option value="<?php echo $load_mode_record["id"]; ?>"><?php echo $load_mode_record["name"]; ?></option>
                                    <?php
                                }
    	                        ?>
    	                    </select>
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							При автозагрузке полностью обновлять
						</label>
						<div class="col-lg-6">
							<?php
							$clean_before_check = "";
							if($clean_before == 1)
							{
								$clean_before_check = "checked=\"checked\"";
							}
							?>
							<input type="checkbox" name="clean_before" <?php echo $clean_before_check; ?> />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Подстрока в имени файла (для автозагрузки с E-mail и FTP)
						</label>
						<div class="col-lg-6">
							<input type="text" name="file_name_substring" value="<?php echo $file_name_substring; ?>" class="form-control" placeholder="" />
						</div>
					</div>
					
				</div>
			</div>
		</div>
		
		

        
        <script>
            //Обработка переключения способа загрузки
            function on_load_mode_changed()
            {
                var load_mode = parseInt(document.getElementById("load_mode").value);//Выбранный способ загрузки
                
                var auto_upload_config_window_ftp = document.getElementById("auto_upload_config_window_ftp");//Окно настроек автозагрузки с FTP
                var auto_upload_config_window_email = document.getElementById("auto_upload_config_window_email");//Окно настроек автозагрузки с E-mail
                
                
                switch(load_mode)
                {
                    case 1:
                        auto_upload_config_window_ftp.setAttribute("style", "display:none;");
                        auto_upload_config_window_email.setAttribute("style", "display:none;");
                        break;
                    case 2:
                        auto_upload_config_window_ftp.setAttribute("style", "display:block;");
                        auto_upload_config_window_email.setAttribute("style", "display:none;");
                        break;
                    case 3:
                        auto_upload_config_window_ftp.setAttribute("style", "display:none;");
                        auto_upload_config_window_email.setAttribute("style", "display:block;");
                        break;
                }
            }
        </script>
        
		
		
		<div class="col-lg-12" id="auto_upload_config_window_ftp" style="display:none;">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Настройки загрузки прайс-листа с FTP-сервера
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Сервер
						</label>
						<div class="col-lg-6">
							<input type="text" name="ftp_host" value="<?php echo $ftp_host; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Пользователь
						</label>
						<div class="col-lg-6">
							<input type="text" name="ftp_user" value="<?php echo $ftp_user; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Пароль
						</label>
						<div class="col-lg-6">
							<input type="text" name="ftp_password" value="<?php echo $ftp_password; ?>" class="form-control" />
						</div>
					</div>
				</div>
			</div>
		</div>
		
		
		
		<div class="col-lg-12" id="auto_upload_config_window_email" style="display:none;">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Настройки загрузки прайс-листа с E-mail
				</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Адрес отправителя
						</label>
						<div class="col-lg-6">
							<input type="text" name="sender_email" value="<?php echo $sender_email; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Удалять письмо после обработки
						</label>
						<div class="col-lg-6">
							<?php
							$checked = "";
							if($delete_email_messages == 1)
							{
								$checked = " checked='checked' ";
							}
							?>
						
							<input type="checkbox" name="delete_email_messages" value="delete_email_messages" class="form-control" <?php echo $checked; ?> />
						</div>
					</div>
				</div>
			</div>
		</div>
		
		
		
		
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Настройки структуры прайс-листа
				</div>
				<div class="panel-body">
				
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Пропустить строк
						</label>
						<div class="col-lg-6">
							<input type="text" name="strings_to_left" value="<?php echo $strings_to_left; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Производитель"
						</label>
						<div class="col-lg-6">
							<input type="text" name="manufacturer_col" value="<?php echo $manufacturer_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Артикул"
						</label>
						<div class="col-lg-6">
							<input type="text" name="article_col" value="<?php echo $article_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Наименование"
						</label>
						<div class="col-lg-6">
							<input type="text" name="name_col" value="<?php echo $name_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Цена"
						</label>
						<div class="col-lg-6">
							<input type="text" name="price_col" value="<?php echo $price_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Наличие"
						</label>
						<div class="col-lg-6">
							<input type="text" name="exist_col" value="<?php echo $exist_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Номер склада"
						</label>
						<div class="col-lg-6">
							<input type="text" name="storage_col" value="<?php echo $storage_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Срок доставки"
						</label>
						<div class="col-lg-6">
							<input type="text" name="time_to_exe_col" value="<?php echo $time_to_exe_col; ?>" class="form-control" />
						</div>
					</div>
					<div class="hr-line-dashed col-lg-12"></div>
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Номер колонки "Минимальный заказ"
						</label>
						<div class="col-lg-6">
							<input type="text" name="min_order_col" value="<?php echo $min_order_col; ?>" class="form-control" />
						</div>
					</div>
				
				</div>
			</div>
		</div>
    </form>
    
    
    <?php
    //Если был переход для редатирования существующего прайс-листа - инициализируем виджеты
    if($action_type == "edit")
    {
        ?>
        <script>
            //Выставляем способ загрузки
            document.getElementById("load_mode").value = <?php echo $load_mode; ?>;
            on_load_mode_changed();
        </script>
        <?php
    }
    ?>
    
    
    
    
    
    
    <?php
}
?>