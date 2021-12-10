<?php
//Страничный скрипт для настройки SMS-операторов
defined('_ASTEXE_') or die('No access');



if( !empty($_POST["save_action"]) )//Действия
{
	$result = true;//Накопительный результат
	
	//Получаем id системы
	$system_id = $_POST["system_id"];
	if($system_id > 0)
	{
		//Предварительно отключаем все системы
		if( $db_link->prepare("UPDATE `sms_api` SET `active`=0;")->execute() != true)
		{
			$result = false;
		}
		
		
		//Ставим новые настройки:
		if( $db_link->prepare("UPDATE `sms_api` SET `active`=1, `parameters_values` = ? WHERE `id` = ?;")->execute( array($_POST["parameters_values"], $system_id) ) != true)
		{
			$result = false;
		}
	}
	else//Отключаем все системы
	{
		if( $db_link->prepare("UPDATE `sms_api` SET `active`=0;")->execute() != true)
		{
			$result = false;
		}
	}
	
	if($result)
	{
		$success_message = "Выполнено успешно";
		?>
		<script>
			location="/<?php echo $DP_Config->backend_dir; ?>/control/sms-operatory?success_message=<?php echo $success_message; ?>";
		</script>
		<?php
		exit;
	}
	else
	{
		$error_message = "Ошибка";
		?>
		<script>
			location="/<?php echo $DP_Config->backend_dir; ?>/control/sms-operatory?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	
}
else
{
	?>
	
	<?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	<script>
	var sms_systems = new Array();
	<?php
	$sms_systems_query = $db_link->prepare("SELECT * FROM `sms_api`;");
	$sms_systems_query->execute();
	while($sms_system = $sms_systems_query->fetch() )
	{
		if($sms_system["parameters"] == "" || $sms_system["parameters"] == NULL)
		{
			$sms_system["parameters"] = "[]";
		}
		if($sms_system["parameters_values"] == "" || $sms_system["parameters_values"] == NULL)
		{
			$sms_system["parameters_values"] = "[]";
		}
		?>
		sms_systems[sms_systems.length] = new Object;
		sms_systems[sms_systems.length-1].id = <?php echo $sms_system["id"]; ?>;
		sms_systems[sms_systems.length-1].name = '<?php echo $sms_system["name"]; ?>';
		sms_systems[sms_systems.length-1].parameters = JSON.parse('<?php echo $sms_system["parameters"]; ?>');
		sms_systems[sms_systems.length-1].parameters_values = JSON.parse('<?php echo $sms_system["parameters_values"]; ?>');
		sms_systems[sms_systems.length-1].description = '<?php echo $sms_system["description"]; ?>';
		sms_systems[sms_systems.length-1].active = <?php echo $sms_system["active"]; ?>;
		<?php
	}
	?>
	</script>
	
	
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
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Текущий оператор
			</div>
			<div class="panel-body" id="current_system_indicator">
			</div>
		</div>
	</div>
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Выбор оператора
			</div>
			<div class="panel-body">
				<select id="system_selector" name="system_selector" onchange="on_system_changed();" class="form-control">
				</select>
			</div>
		</div>
	</div>

	<script>
	//Обработка смены типа интерфейса
	function on_system_changed()
	{
		//Текущая выбранная система
		var current_system_selected = document.getElementById("system_selector").value;
		
		//Блок для виджетов настройки
		var mysql_options_div_fields = document.getElementById("mysql_options_div_fields");
		
		var html = "";
		
		//Ищем выбранную систему в списка объектов описания
		for(var i=0; i < sms_systems.length; i++)//По системам
		{
			if(sms_systems[i].id != current_system_selected)
			{
				sms_systems[i].active = 0;
				continue;
			}
			
			sms_systems[i].active = 1;
			
			for(var j=0; j < sms_systems[i].parameters.length; j++)//По параметрам выбранной системы
			{
				if( j > 0)
				{
					html += "<div class=\"hr-line-dashed col-lg-12\"></div>";
				}
				
				//В зависимости от типа свойства - выводим виджет для настроки
				html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">"+sms_systems[i].parameters[j].caption+"</label><div class=\"col-lg-6\">";
				
				
				if(sms_systems[i].parameters[j].type == "text" || 
				sms_systems[i].parameters[j].type == "number" || 
				sms_systems[i].parameters[j].type == "color" || 
				sms_systems[i].parameters[j].type == "password" ||
				sms_systems[i].parameters[j].type == "checkbox")
				{					
					html += "<input type=\""+sms_systems[i].parameters[j].type+"\" id=\""+sms_systems[i].parameters[j].name+"\" name=\""+sms_systems[i].parameters[j].name+"\" value=\"\" class=\"form-control\" />";
				}
				else if(sms_systems[i].parameters[j].type == "select")
				{
					html += "<select name=\""+sms_systems[i].parameters[j].name+"\" id=\""+sms_systems[i].parameters[j].name+"\" class=\"form-control\">";
						for(var o=0; o < sms_systems[i].parameters[j].options.length; o++)
						{
							html += "<option value=\""+sms_systems[i].parameters[j].options[o].value+"\">"+sms_systems[i].parameters[j].options[o].caption+"</option>";
						}
					html += "</select>";
				}
				html += "</div></div>";
			}
			break;
		}
		
		if(html == "")
		{
			html = "Параметры настройки для выбранного варианта не предусмотрены";
		}
		
		mysql_options_div_fields.innerHTML = html;
		
	}
	</script>
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Настройки выбранной SMS-оператора
			</div>
			<div class="panel-body" id="mysql_options_div_fields">
			</div>
		</div>
	</div>

	<form method="POST" name="form_to_save">
		<input type="hidden" name="save_action" value="ok" />
		<input type="hidden" name="system_id" id="system_id" value="" />
		<input type="hidden" name="parameters_values" id="parameters_values" value="" />
	</form>
	<script>
	//Функция сохранения
    function save_action()
    {
        //1. Оператор
        var system_id = document.getElementById("system_selector").value;
        document.getElementById("system_id").value = system_id;
        
		//2. Настройки
		var parameters_values = new Object;
		for(var i=0; i < sms_systems.length; i++)
		{
			if(sms_systems[i].active == 1)
			{
				for(var j=0; j < sms_systems[i].parameters.length; j++)
				{
					if(sms_systems[i].parameters[j].type == "checkbox")//Инициализация значений для чекбокса
					{
						if(document.getElementById(sms_systems[i].parameters[j].name).checked)
						{
							parameters_values[sms_systems[i].parameters[j].name] = 1;
						}
						else
						{
							parameters_values[sms_systems[i].parameters[j].name] = 0;
						}
					}
					else//Инициализация значений для строковых типов (text, password) и для списков (select)
					{
						parameters_values[sms_systems[i].parameters[j].name] = document.getElementById(sms_systems[i].parameters[j].name).value;
					}
				}
			}
		}
        document.getElementById("parameters_values").value = JSON.stringify(parameters_values);
        
        document.forms["form_to_save"].submit();
    }//~function save_action()
	</script>
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<!-- Действия после загрузки страницы -->
	<script>
	//Заполняем селектор операторов
	var system_selector_html = "<option value=\"0\">Нет</option>";
	var current_selected_id = 0;
	for(var i=0; i < sms_systems.length; i++)
	{
		system_selector_html += "<option value=\""+sms_systems[i].id+"\">"+sms_systems[i].name+"</option>";
		
		//Отмечаем текущую активную
		if(sms_systems[i].active == 1)
		{
			current_selected_id = sms_systems[i].id;
			document.getElementById("current_system_indicator").innerHTML = "В данный момент sms-сообщения отправляются через оператора: "+sms_systems[i].name;
		}
	}
	document.getElementById("system_selector").innerHTML = system_selector_html;
	
	//Указываем текущий выбранный элемент
	document.getElementById("system_selector").value = current_selected_id;
	
	//Показываем менеджеру текущего оператора
	if(current_selected_id == 0)
	{
		document.getElementById("current_system_indicator").innerHTML = "В данный момент к сайту не подключен SMS-оператор";
	}
	
	//Обработка текущего выбора
	on_system_changed();
	
	
	for(var i=0; i < sms_systems.length; i++)
	{
		if(sms_systems[i].active == 1)
		{
			for(var j=0; j < sms_systems[i].parameters.length; j++)
			{
				if(sms_systems[i].parameters[j].type == "checkbox")//Инициализация значений для чекбокса
				{
					document.getElementById(sms_systems[i].parameters[j].name).checked = sms_systems[i].parameters_values[sms_systems[i].parameters[j].name];
				}
				else//Инициализация значений для строковых типов (text, password) и для списков (select)
				{
					document.getElementById(sms_systems[i].parameters[j].name).value = sms_systems[i].parameters_values[sms_systems[i].parameters[j].name];
				}
			}
		}
	}
	</script>
	<?php
}
?>