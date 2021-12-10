<?php
/**
 * Скрипт страницы управления одним плагином
*/
defined('_ASTEXE_') or die('No access');


//Режим редактирования Фронтэнд/Бэкэнд
$edit_mode = null;
if(isset($_COOKIE["edit_mode"]))
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
if(!empty($_POST["save_plugin_action"]))//Есть действия
{
    //Сначала проверяем, доступно ли управление для этого плагина
    $check_access_query = $db_link->prepare( "SELECT * FROM `plugins` WHERE `id` = ?;" );
	$check_access_query->execute( array($_POST["id"]) );
    $check_access_record = $check_access_query->fetch();
    if($check_access_record["control_lock"] == true)
    {
       $warning_message = "Управление этим плагином не доступно - изменения не сохранены";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugin?plugin_id=<?php echo $_POST["id"]; ?>&warning_message=<?php echo $warning_message; ?>";
        </script>
        <?php
        exit();
    }
    
    //Управление доступно - сохраняем
    $SQL_UPDATE = "UPDATE `plugins` SET `caption` = ?, `description` = ?, `activated` = ?, `order` = ?, `data_value` = ? WHERE `id` = ?;";
	
	
	
    if( $db_link->prepare($SQL_UPDATE)->execute( array($_POST["caption"], $_POST["description"], $_POST["activated"], $_POST["order"], $_POST["data_value"], $_POST["id"]) ) != true)
    {
        $error_message = "Ошибка сохранения - изменения не сохранены";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugin?plugin_id=<?php echo $_POST["id"]; ?>&error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit();
    }
    else
    {
        $success_message = "Выполнено успешно";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/plugins/plugin?plugin_id=<?php echo $_POST["id"]; ?>&success_message=<?php echo $success_message ;?>";
        </script>
        <?php
        exit();
    }
}//~if(!empty($_POST["save_plugin_action"]))//Есть действия
else//Действий нет - выводим страницу
{
    ?>
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    
    
    <?php
    //Получаем текущие данные по плагину
	$plugin_query = $db_link->prepare("SELECT * FROM `plugins` WHERE `id` = ?;");
	$plugin_query->execute( array($_GET["plugin_id"]) );
    $plugin_record = $plugin_query->fetch();
    
    //Исходные данные:
    $id = $plugin_record["id"];//ID плагина
    $activated = $plugin_record["activated"];//Активирован
    $control_lock = $plugin_record["control_lock"];//Управление заблокировано
    $caption = $plugin_record["caption"];//Заголовок
    $description = $plugin_record["description"];//Описание
    $order = $plugin_record["order"];//Порядок запуска
    $data_structure = $plugin_record["data_structure"];//Структура спецнастроек
    $data_value = $plugin_record["data_value"];//Значения спецнастроек
    
    
    //Обрабатываем некоторые параметры
    //Чекбокс "Активирован"
    $activated_ckecked = "";
    if($activated == true)
    {
        $activated_ckecked = " checked";
    }
    //Управление доступно
    $control_state = "<font style=\"font-weight:bold; color:#00A100\"> доступно</font>";
    if($control_lock == true)
    {
        $control_state = "<font style=\"font-weight:bold; color:#C10000\"> не доступно</font>";
    }
    //Структура спецнастроек
    if($data_structure == "") $data_structure = "[]";
    //Значения спецнастроек
    if($data_value == "") $data_value = "[]";
    ?>
    
    
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="save_plugin();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/plugins/plugins_manager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/puzzle.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер плагинов</div>
				</a>
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
    
    
    
    
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Основные настройки плагина
			</div>
			<div class="panel-body">
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						ID плагина
					</label>
					<div class="col-lg-6">
						<?php echo $plugin_record["id"]; ?>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Название
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" name="caption_input" id="caption_input" value="<?php echo $caption; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Описание
					</label>
					<div class="col-lg-6">
						<textarea class="form-control" name="description_input" id="description_input"><?php echo $description; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Включить
					</label>
					<div class="col-lg-6">
						<input type="checkbox" name="activated_checkbox" id="activated_checkbox" <?php echo $activated_ckecked; ?>/>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						В очереди запуска
					</label>
					<div class="col-lg-6">
						<input class="form-control" type="text" name="order_input" id="order_input" value="<?php echo $order; ?>" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Управление
					</label>
					<div class="col-lg-6">
						<?php echo $control_state; ?>
					</div>
				</div>
				
			</div>
		</div>
	</div>
	
	
	
	
	<?php
	if($data_structure != "[]")
	{
		?>
		<div class="col-lg-6">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Специальные настройки плагина
				</div>
				<div class="panel-body">
					<?php
					require_once("content/control/get_widget.php");//Скрипт для получения html-кода виджетов различных типов
    	        
					//Текущие значения спецнастроек плагина
					$data_value = json_decode($data_value, true);
					
					$data_structure = json_decode($plugin_record["data_structure"], true);//Структура спецнастроек в форма JSON
					for($i=0; $i<count($data_structure); $i++)
					{
						if($i > 0)
						{
							?>
							<div class="hr-line-dashed col-lg-12"></div>
							<?php
						}
						
						$options = array();//Переменная для списка возможных опций
						//Проверяем существование поля "Способ получения возможных значений"
						if(!empty($data_structure[$i]["options_way"]))
						{
							//Получаем список опций указанным способом
							switch($data_structure[$i]["options_way"])
							{
								case "direct":
									$options = json_decode($data_structure[$i]["options"], true);
									break;
								case "sql":
									$SQL_SELECT_OPTIONS = str_replace(array("<is_frontend>"), $is_frontend, $data_structure[$i]["options"]);//Подставляем режим работы
									
									$options_query = $db_link->prepare($SQL_SELECT_OPTIONS);
									$options_query->execute();
									while( $options_record = $options_query->fetch() )
									{
										array_push($options, array("caption"=>$options_record["caption"], "value"=>$options_record["value"]));
									}
									break;
							};
						}//if() - если предполагается получение возможных опций настройки
						
						$value = $data_value[$data_structure[$i]["name"]];//Текущее значение спецнастройки
						
						$widget = get_widget($data_structure[$i]["type"], $data_structure[$i]["name"], $value, $options);
						?>
						<div class="form-group">
							<label for="" class="col-lg-6 control-label">
								<?php echo $data_structure[$i]["caption"];?>
							</label>
							<div class="col-lg-6">
								<?php echo $widget;?>
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
	
	
	


    

    
    
    

    
    
    
    
    
    
    
    
    <?php
	//Блок формы сохранения выводим только, если управление доступно
	if($control_lock == false)
	{
	?>
    
        <!-- ********************************************************************** -->
        <!-- START - БЛОК ФОРМЫ СОХРАНЕНИЯ -->
        <script>
        <?php
        if($data_structure == "[]")
        {
            ?>
            var data_structure = "";
            <?php
        }
        else
        {
            ?>
            var data_structure = <?php echo json_encode($data_structure);?>;//Структура спецнастроек
            <?php
        }
        ?>
        // ----------------------------------------------------------------
        //Сохранение плагина
        function save_plugin()
        {
            //1. Заголовок
            if(document.getElementById("caption_input").value == "")
            {
                webix.message({type:"error", text:"Заполните заголовок"});
                return;
            }
            document.getElementById("caption").value = document.getElementById("caption_input").value;
            
            
            //2. Включен
			if( document.getElementById("activated_checkbox").checked == true )
			{
				document.getElementById("activated").value = '1';
			}
			else
			{
				document.getElementById("activated").value = '0';
			}
            
            
            
            //3. Порядок запуска
            document.getElementById("order").value = document.getElementById("order_input").value;
            
            
            //4. Описание
            document.getElementById("description").value = document.getElementById("description_input").value;
            
          
            
            //5. Значения специальных настроек
            var data_value = new Object;
            if(data_structure.length > 0)
            {
                //5.1 По списку специальных настроек
                for(var i=0; i < data_structure.length; i++)
                {
                    //5.2 Для кажой настройки получить значение из виджета
                    //5.3 Записать это значение в общий объект спецнастроек
                    data_value[data_structure[i]["name"]] = document.getElementById(data_structure[i]["name"]).value;
                }
            }
            
            //5.4 Перевести объект в JSON-формат и записать его в поле формы
            document.getElementById("data_value").value = JSON.stringify(data_value);
            
            
            //6. Отправляем форму
        	document.forms["save_plugin_form"].submit();//Отправляем
        }
        // ----------------------------------------------------------------
        </script>
        <form name="save_plugin_form" style="display:none" method="POST">
            <input type="hidden" name="save_plugin_action" id="save_plugin_action" value="save_plugin_action" /> <!-- Говорит скрипту, что идет сохранение плагина -->
            <input type="hidden" name="id" id="id" value="<?php echo $id;?>" /> <!-- ID плагина -->
            <!-- Ok --><input type="hidden" name="caption" id="caption" value="" /> <!-- Заголовок -->
            <!-- Ok --><input type="hidden" name="activated" id="activated" value="" /> <!-- Включен (либо " checked") -->
            <!-- Ok --><input type="hidden" name="order" id="order" value="" /> <!-- Порядок запуска -->
            <!-- Ok --><input type="hidden" name="data_value" id="data_value" value="" /> <!-- Значения специальных настроек -->
            <!-- Ok --><input type="hidden" name="description" id="description" value="" /> <!-- Описание -->
            
        </form>
        <!-- END - БЛОК ФОРМЫ СОХРАНЕНИЯ -->
        <!-- ********************************************************************** -->
    
    <?php
	}//if - //Блок формы сохранения выводим только, если управление доступно
    ?>
    
    
    
    <?php
}//~else//Действий нет - выводим страницу
?>