<?php
/**
Скрипт страницы обновлений
*/
defined('_ASTEXE_') or die('No access');



//Текущая версия версия
$current_version_query = $db_link->prepare("SELECT * FROM `version_control` ORDER BY `id` DESC LIMIT 1;");
$current_version_query->execute();
$current_version_record = $current_version_query->fetch();
if($current_version_record != false)
{
	$current_version = $current_version_record["version"];
}
?>




<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Сервер обновлений
		</div>
		<div class="panel-body">
			
			<div class="form-group">
				<label class="col-lg-6 control-label">Сервер</label>
				<div class="col-lg-6">
					<?php echo $DP_Config->update_server; ?>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<div class="form-group">
				<label class="col-lg-6 control-label">Доступен</label>
				<div class="col-lg-6">
					<div id="server_status_div"><img src="/content/files/images/ajax-loader-transparent.gif" />
				</div>
			</div>
		
		</div>
	</div>
</div>






<div id="update_actions">
</div>




<script>
// -------------------------------------------------------------------------------------------------------------------
var available_updates = "";//Переменная для хранения списка ID доступных обновлений
var args = new Object;
args.query = "check_server";
//Отправляем запрос на проверку доступности сервера обновлений
jQuery.ajax({
	type: "POST",
	async: false,
	url: "/<?php echo $DP_Config->backend_dir;?>/content/control/version_control/ajax/ajax_query_to_server.php",
	dataType: "json",//Тип возвращаемого значения
	data: "args="+encodeURIComponent(JSON.stringify(args)),
	success: function(answer){
		console.log(answer);
		if(answer != null)
		{
			if(answer.status == "OK")
			{
				document.getElementById("server_status_div").innerHTML = "<img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/check.png\" />";
				
				
				getAvailableUpdates();//Делаем запрос на получение списка обновлений
			}
			else
			{
				document.getElementById("server_status_div").innerHTML = "<img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" />";
				
				alert("На сервере обновлений проводятся технические работы");
			}
		}
		else//Сервер вообще не доступен
		{
			document.getElementById("server_status_div").innerHTML = "<img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" />";
			
			alert("Сервер обновлений не доступен");
		}
		
	}
});
// -------------------------------------------------------------------------------------------------------------------
//Запрос на получение списка доступных обновлений
function getAvailableUpdates()
{
	console.log("Запрос списка обновлений");
	
	args = new Object;
	args.query = "get_updates_list";
	args.version = "<?php echo $current_version; ?>";//Своя текущая версия
	args.product = "<?php echo $DP_Config->product; ?>";//Свой продукт
	
	//Отправляем запрос на сервер обновлений
	jQuery.ajax({
		type: "POST",
		async: false,
		url: "/<?php echo $DP_Config->backend_dir;?>/content/control/version_control/ajax/ajax_query_to_server.php",
		dataType: "json",//Тип возвращаемого значения
		data: "args="+encodeURIComponent(JSON.stringify(args)),
		success: function(answer){
			console.log(answer);
			if(answer != null)
			{
				if(answer.status == "OK")
				{
					if(answer.updates_list.length == 0)
					{
						var update_actions_html = "";
						update_actions_html += "<div class=\"col-lg-12\">";
							update_actions_html += "<div class=\"hpanel\">";
								update_actions_html += "<div class=\"panel-heading hbuilt\">Статус версии</div>";
								update_actions_html += "<div class=\"panel-body\">Программа не требует обновлений</div>";
							update_actions_html += "</div>";
						update_actions_html += "</div>";
						document.getElementById("update_actions").innerHTML = update_actions_html;
					}
					else
					{
						available_updates = JSON.stringify(answer.updates_list);
						available_updates = JSON.parse(available_updates);
						console.log(available_updates);
						
						var update_actions_html = "";
						update_actions_html += "<div class=\"col-lg-12\">";
							update_actions_html += "<div class=\"hpanel\">";
								update_actions_html += "<div class=\"panel-heading hbuilt\">Статус версии</div>";
								update_actions_html += "<div class=\"panel-body\">Есть доступные обновления. <button onclick=\"installUpdates();\">Установить</button></div>";
							update_actions_html += "</div>";
						update_actions_html += "</div>";
						document.getElementById("update_actions").innerHTML = update_actions_html;
					}
				}
				else
				{
					alert("Сервер обновлений вернул ошибку получения списка обновлений");
				}
			}
		}
	});
}
// -------------------------------------------------------------------------------------------------------------------
//Функция установки обновлений
var current_update_index = 0;//Индекс текущего обновления
function installUpdates()
{
	console.log("Устанавливаем обновления");
	console.log(available_updates);
	
	//Блокируем действия пользователя
	var update_actions_html = "";
	update_actions_html += "<div class=\"col-lg-12\">";
		update_actions_html += "<div class=\"hpanel\">";
			update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
			update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Идет установка обновлений. Не закрывайте эту страницу до полной установки<br><img src=\"/content/files/images/ajax-loader-transparent.gif\" /></div></div>";
		update_actions_html += "</div>";
	update_actions_html += "</div>";
	document.getElementById("update_actions").innerHTML = update_actions_html;
	
	
	next_install();//Запуск функции установки следующего пакета
}
// -------------------------------------------------------------------------------------------------------------------
//Функция - установить следующий пакет
function next_install()
{
	if(available_updates.length <= current_update_index)
	{
		var update_actions_html = "";
		update_actions_html += "<div class=\"col-lg-12\">";
			update_actions_html += "<div class=\"hpanel\">";
				update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
				update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Установка выполнена <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/check.png\" /></div></div>";
			update_actions_html += "</div>";
		update_actions_html += "</div>";
		document.getElementById("update_actions").innerHTML = update_actions_html;
		return;
	}
	
	//ДАЛЕЕ СИНХРОННЫЕ ЗАПРОСЫ
	//Делаем запрос на скачивание пакета
	jQuery.ajax({
		type: "GET",
		async: false,
		url: "/<?php echo $DP_Config->backend_dir;?>/content/control/version_control/ajax/ajax_get_update_pack.php",
		dataType: "json",//Тип возвращаемого значения
		data: "update_id="+available_updates[current_update_index],
		success: function(answer){
			console.log("Ответ после скачивания пакета");
			console.log(answer);
			if(answer == null)
			{
				var update_actions_html = "";
				update_actions_html += "<div class=\"col-lg-12\">";
					update_actions_html += "<div class=\"hpanel\">";
						update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
						update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Ошибка 1 скачивания пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /></div></div>";
					update_actions_html += "</div>";
				update_actions_html += "</div>";
				document.getElementById("update_actions").innerHTML = update_actions_html;
				return;
			}
			if(answer.status != "OK")
			{
				var update_actions_html = "";
				update_actions_html += "<div class=\"col-lg-12\">";
					update_actions_html += "<div class=\"hpanel\">";
						update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
						update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Ошибка 2 скачивания пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /><br>"+answer.message+"</div></div>";
					update_actions_html += "</div>";
				update_actions_html += "</div>";
				document.getElementById("update_actions").innerHTML = update_actions_html;
				return;
			}
			//Делаем запрос на установку пакета
			jQuery.ajax({
				type: "GET",
				async: false,
				url: "/<?php echo $DP_Config->backend_dir;?>/tmp/updates/action.php",
				dataType: "json",//Тип возвращаемого значения
				success: function(answer){
					if(answer == null)
					{
						var update_actions_html = "";
						update_actions_html += "<div class=\"col-lg-12\">";
							update_actions_html += "<div class=\"hpanel\">";
								update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
								update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Критическая ошибка 1 action.php для пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /></div></div>";
							update_actions_html += "</div>";
						update_actions_html += "</div>";
						document.getElementById("update_actions").innerHTML = update_actions_html;
						return;
					}
					if(answer.status == "ERROR")
					{
						var update_actions_html = "";
						update_actions_html += "<div class=\"col-lg-12\">";
							update_actions_html += "<div class=\"hpanel\">";
								update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
								update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Критическая ошибка 2 action.php для пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /><br>Сообщение пакета обновления: "+answer.message+"</div></div>";
							update_actions_html += "</div>";
						update_actions_html += "</div>";
						document.getElementById("update_actions").innerHTML = update_actions_html;
						return;
					}
					if(answer.status == "OK")
					{
						//Делаем запрос на очистку папки tmp/updates
						jQuery.ajax({
							type: "GET",
							async: false,
							url: "/<?php echo $DP_Config->backend_dir;?>/content/control/version_control/ajax/ajax_clear_updates_dir.php",
							dataType: "json",
							success: function(answer){
								if(answer == null)
								{
									var update_actions_html = "";
									update_actions_html += "<div class=\"col-lg-12\">";
										update_actions_html += "<div class=\"hpanel\">";
											update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
											update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Ошибка 1 очистки временного каталога для пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /></div></div>";
										update_actions_html += "</div>";
									update_actions_html += "</div>";
									document.getElementById("update_actions").innerHTML = update_actions_html;
									return;
								}
								if(answer.status != "OK")
								{
									var update_actions_html = "";
									update_actions_html += "<div class=\"col-lg-12\">";
										update_actions_html += "<div class=\"hpanel\">";
											update_actions_html += "<div class=\"panel-heading hbuilt\">Статус обноления</div>";
											update_actions_html += "<div class=\"panel-body\"><div align=\"center\">Ошибка 2 очистки временного каталога для пакета ID "+available_updates[current_update_index]+". Процесс прерван. <img class=\"a_col_img\" src=\"/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png\" /><br>"+answer.message+"</div></div>";
										update_actions_html += "</div>";
									update_actions_html += "</div>";
									document.getElementById("update_actions").innerHTML = update_actions_html;
									return;
								}
								
								//Инкрементируем индекс - для установки следующего пакета
								current_update_index++;
								//Запуск функции установки следующего пакета
								next_install();
							}
						});
					}
				}
			});
		}
	});
}
// -------------------------------------------------------------------------------------------------------------------

</script>