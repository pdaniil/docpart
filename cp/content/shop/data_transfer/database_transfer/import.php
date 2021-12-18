<?php
/**
Страничный скрипт для раздела "Экспорт базы данных в XML"
*/
defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
?>
<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir."/shop/perenos-dannyx"?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>

<?
if (!empty($_FILES) ) //Если есть файлы на загрузку
{
	$tmp = $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/'.$DP_Config->tmp_db_xml;
	$name = $tmp.'/tmp_db.xml';
	move_uploaded_file($_FILES["userfile"]["tmp_name"], $name);
	$param = http_build_query( array( "name" => $name ) );
	?>
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Импорт данных
				</div>
				<div class="panel-body">
					<h4>Не перезагружайте страницу, пока идёт иморт базы данных.</h4>
					<div id="log_place">
						
					</div>
				</div>
			</div>
		</div>
		
		<script>
			
			let url_u = "<?php echo $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/data_transfer/database_transfer/ajax_import/ajax_upload.php?'.$param; ?>";
			
			console.log(url_u);
			jQuery.ajax({
				type: "GET",
				async: true, //Запрос синхронный
				url: url_u,
				dataType: "text",//Тип возвращаемого значения
				success: function(answer)
				{
					alert(answer);
				}
			}); 
			
			
			
			
		</script>
		
	<?



}
else //Просто выводим данные
{
?>

<style>
	.progress-bar{
		background-color: green;
		color: white;
		text-align: center;
	}
</style>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Импорт данных
		</div>
		<div class="panel-body">
				
			<!-- Тип кодирования данных, enctype, ДОЛЖЕН БЫТЬ указан ИМЕННО так -->
			<form enctype="multipart/form-data" id="form_db_import" method="POST" style="width: 400px;">
					<div class="form-group">
					<input type="hidden" class="form-control" name="MAX_FILE_SIZE" value="30000000" />
						<label for="" class="col-lg-4 control-label">
							Отправить этот файл:
						</label>
						<div class="col-lg-8" >
							<input name="userfile" id="file_form_db" class="form-control" type="file" />
						</div>
						<input class="form-control" id="btn_send_form_db" type="button" value="Отправить файл" />
					</div>
			</form>
			
		</div>
	</div>
</div>   


<script>
	var getFileExt = function (path) {
		return path.split('.').pop();
	}
	
	function send_form_db()
	{
		let file_db = document.querySelector('#file_form_db');
		if ("" == file_db.value)
		{
			alert('Перед началом необходимо выбрать файл.');
			return;
		}
		else if (getFileExt(file_db.value).toLowerCase() != 'xml')
		{
			alert('Расширение выбранного файла на поддерживается. Выберите файл с расширением XML.');
			return;
		}
		else
		{
			form_db.submit();
		}
	}

	let form_db = document.querySelector('#form_db_import');
	let btn_send_form_db = document.querySelector('#btn_send_form_db');
	
	btn_send_form_db.addEventListener('click', send_form_db);
</script>

<?php 
}
?>