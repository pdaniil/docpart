<?php
/**
Страничный скрипт для раздела "Экспорт базы данных в XML"
*/
defined('_ASTEXE_') or die('No access');
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
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
<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Экспорт данных
		</div>
		<div class="panel-body text-center">
			<button id="barProgressBtn" class="btn btn-primary">Начать экспорт</button>
			<div id="progress" class="progress" style="margin-top: 50px; margin-bottom: 50px; ">
			  <div id="barProgress" class="progress-bar-striped progress-bar " role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="color: white;"></div>
			</div>
			<div id="log" style="color: grey;">
			</div>
			
		</div>
	</div>
</div>   
<script>

		function listener ()
		{
			let filename = 'db.xml';
			function resetProgress(e) {
			document.querySelector('#barProgress').style.width =Math.round(e.loaded/e.total*100) +'%';
			document.querySelector('#barProgress').innerHTML = Math.round(e.loaded/e.total*100) +'%';
			}
			let request = new XMLHttpRequest();
			let flag = false;
			request.open("GET", "/<?php echo $DP_Config->backend_dir; ?>/content/shop/data_transfer/database_transfer/ajax_export/create_xml.php?filename="+filename);
			request.send();
			request.onprogress = resetProgress;
			request.onload = function ()
			{
				document.querySelector('#barProgressBtn').classList.add('disabled');
				document.querySelector('#barProgressBtn').removeEventListener('click', listener);
				window.location = '<?php echo "http://".$_SERVER["SERVER_NAME"]."/".$DP_Config->backend_dir;?>/content/shop/data_transfer/database_transfer/ajax_export/download.php?file='+filename;
				
			}
		}
		document.querySelector('#barProgressBtn').addEventListener('click', listener);
</script>