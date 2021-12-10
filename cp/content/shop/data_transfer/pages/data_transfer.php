<?php
/**
Страничный скрипт для раздела "Перенос данных"
*/
defined('_ASTEXE_') or die('No access');
?>



<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Функции переноса данных
		</div>
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/eksport-kataloga-tovarov-v-xml-i-json">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>catalogue_export.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выгрузка каталога XML/JSON</div>
			</a>
			
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/vygruzka-na-yandeksmarket">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>yml.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выгрузка каталога на Яндекс.Маркет</div>
			</a>
			
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/import-kataloga-tovarov-iz-xml-i-json">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>catalogue_import.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Импорт каталога товаров</div>
			</a>
			
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/import-tovarov-v-katalog-iz-csv">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>catalogue_import_csv.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Импорт товаров в каталог из CSV</div>
			</a>
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/vygruzka-bazy-dannyx-v-xml">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>out.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Экспорт базы данных из XML</div>
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx/import-bazy-dannyx-iz-xml">
				<div class="panel_a_img" style="background: url('<?php echo "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/"; ?>in.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Импорт базы данных в XML</div>
			</a>
			
			</a>
			
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выход</div>
			</a>
		</div>
	</div>
</div>