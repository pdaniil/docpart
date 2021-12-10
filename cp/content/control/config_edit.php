<?php
/**
 * Страница редактирования config.php
 * 
*/
defined('_ASTEXE_') or die('No access');

require_once("content/control/dp_configeditor.php");
?>

<?php
//ИЗМЕНЕНИЕ config.php
//DP_ConfigEditor::setParameter('site_name_first', 'false');

//ПЕРЕХОД ПОСЛЕ НАЖАТИЯ "СОХРАНИТЬ"
if(!empty($_POST["save_config"]))
{
	//Для возможности работы с настройками только определенной группы
	$need_config_group = 0;//Работаем со всеми настройками
	if( isset($_POST['need_config_group']) )
	{
		$need_config_group = (int)$_POST['need_config_group'];
	}
	if( $need_config_group < 0 )
	{
		$need_config_group = 0;
	}
	
	
    //Получаем перечень всех параметров:
	$config_parameters_query = $db_link->prepare("SELECT * FROM `config_items`;");
    $config_parameters_query->execute();
    while( $item = $config_parameters_query->fetch() )
    {
		//Если работаем только с настройками определенной группы, то, настройки других групп пропускаем
		if( $need_config_group > 0 && $item['config_group'] != $need_config_group )
		{
			continue;
		}
		
		
        //С некоторыми типами параметров необходимо работать особым образом:
        if($item["type"]=="password")//Для паролей: если передан пустой - оставляем как есть
        {
            if($_POST[$item["name"]] != "") DP_ConfigEditor::setParameter($item["name"], $_POST[$item["name"]]);
        }
        else if($item["type"]=="checkbox")//Для чекбоксов приводим к булевому типу
        {
            DP_ConfigEditor::setParameter($item["name"], filter_var($_POST[$item["name"]], FILTER_VALIDATE_BOOLEAN));
        }
        else//Для все остальных типов - как есть
        {
            DP_ConfigEditor::setParameter($item["name"], $_POST[$item["name"]]);
        }
    }
    
    
    $success_message = "Выполнено!";
	$need_config_group_arg = "";
	if( $need_config_group > 0 )
	{
		$need_config_group_arg = "&need_config_group=".$need_config_group;
	}
    ?>
    <script>
        location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/control/config?success_message=<?php echo $success_message.$need_config_group_arg; ?>";
    </script>
    <?php
    exit();
}
else//Если нет перехода после нажатия "Сохранить" - выводим форму с настройками
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
				<a class="panel_a" href="javascript:void(0);" onclick="save_config();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>

    
    <script>
    //Метод отправки формы
    function save_config()
    {
        document.forms["save_config_form"].submit();//Переход
    }
    </script>
    
    
    
    
    <?php
    $tabs = array();
    
	//Для возможности работы с настройками ТОЛЬКО определенной группы
	$need_config_group = 0;//Отображаем настройки для всех групп
	if( isset($_GET["need_config_group"]) )
	{
		$need_config_group = (int)$_GET["need_config_group"];
	}
	if( $need_config_group < 0 )
	{
		$need_config_group = 0;
	}
	
    //Получаем перечнь групп параметров config.php:
	$config_groups_query = $db_link->prepare('SELECT * FROM `config_groups` WHERE `visible` = ? ORDER BY `order` ASC;');
    $config_groups_query->execute(array(1));
    while( $group = $config_groups_query->fetch() )
    {
		//Если работаем с определенной группой, то, остальные пропускам
		if( $need_config_group > 0 && $group["id"] != $need_config_group )
		{
			continue;
		}
		
		
        $tabs[(string)$group["id"]] = array("caption"=>$group["caption"], "items"=>array());
    }
    
    //Получаем перечень всех параметров:
	$config_parameters_query = $db_link->prepare("SELECT * FROM `config_items` WHERE `visible` = ? ORDER BY `order` ASC;");
    $config_parameters_query->execute(array(1));
    while( $item = $config_parameters_query->fetch() )
    {
		//Если работаем с определенной группой, то, остальные пропускам
		if( $need_config_group > 0 && $item["config_group"] != $need_config_group )
		{
			continue;
		}
		
        array_push($tabs[(string)$item["config_group"]]["items"], $item);
    }
    
    ?>
    <form method="POST" name="save_config_form">
    <input type="hidden" name="save_config" value="save_config" />
    <input type="hidden" name="need_config_group" value="<?php echo $need_config_group; ?>" />
    <?php
    
    require_once("content/control/get_widget.php");//Скрипт для получения html-кода виджетов различных типов
    
    //Выводим перечень задач на страницу:
    foreach($tabs as $key => $tab)
    {
        ?>
		
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					<?php echo $tab["caption"]; ?>
				</div>
				<div class="panel-body">
					<?php
					for($i=0; $i<count($tab["items"]); $i++)
					{
						$widget = get_widget($tab["items"][$i]["type"], $tab["items"][$i]["name"], $DP_Config->{$tab["items"][$i]["name"]}, json_decode($tab["items"][$i]["options"], true));
						
						if($i > 0)
						{
							?>
							<div class="hr-line-dashed col-lg-12"></div>
							<?php
						}
						?>
						<div class="form-group">
							<label for="<?php echo $tab["items"][$i]["name"]; ?>" class="col-lg-6 control-label"><?php echo $tab["items"][$i]["caption"]; ?> 
							<?php
							if( isset($tab["items"][$i]["hint"]) )
							{
								if( $tab["items"][$i]["hint"] != "" )
								{
									?>
									<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('<?php echo htmlentities($tab["items"][$i]["hint"], ENT_QUOTES, "UTF-8"); ?>');"><i class="fa fa-info"></i></button>
									<?php
								}
							}
							?>
							</label>
							<div class="col-lg-6">
								<?php echo $widget; ?>
							</div>
						</div>
						<?php
					}//for()
					?>
				</div>
			</div>
		</div>
		
        <?php
    }//foreach()
    ?>
    </form>


<?php
}//else - если не было перехода после нажатия "Сохранить"
?>