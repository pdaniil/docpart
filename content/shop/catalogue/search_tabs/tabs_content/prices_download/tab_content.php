<?php
//Скрипт для вывода содержимого таба "VIN-запрос"
defined('_ASTEXE_') or die('No access');
?>
<!--<div class="search_tab_clar">Скачивание прайс-листа в формате Excel</div>-->
<div class="input-group">
	<div class="search_tab_clar">
		<?php
		//Получаем группу пользователя
		//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
		$userProfile = DP_User::getUserProfile();
		$group_id = $userProfile["groups"][0];//Берем первую группу пользователя
		
		
		//Проверяем наличие файла для соответствующей группы
		if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/prices_".$group_id.".csv") )
		{
			?>
			<div>В некоторых случаях удобнее работать с прайс-листом в формате MS Excel. Специально для этого мы подготовили файл для скачивания.</div>
			<div>
				Последнее обновление: <?php echo date ("d.m.Y в H:i:s", filemtime($_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/prices_".$group_id.".csv")); ?>
			</div>
			<div><a style="color:#FFF;" href="<?php echo "/content/files/Documents/prices_tmp/prices_".$group_id.".csv"; ?>"><i class="fa fa-sm fa-download"></i> Скачать прайс-лист</a></div>
			<?php
		}
		else
		{
			?>
			<p>В данный момент отсутствуют доступные для скачивания файлы</p>
			<?php
		}
		
		
		//Выводим ссылку на скачивание
		?>
	</div>
</div>