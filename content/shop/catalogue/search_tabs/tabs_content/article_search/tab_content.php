<?php
//Скрипт для вывода содержимого таба "Поиск по артикулу"
defined('_ASTEXE_') or die('No access');
?>
<div class="search_tab_clar">Поиск автозапчастей по оригинальным и неоригинальным номерам</div>
<form role="form" action="/shop/part_search" method="GET">
	<div class="input-group">
		<input value="<?php echo $value_for_input_search; ?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
		<span class="input-group-btn">
			<button class="btn btn-ar btn-default" type="submit">Поиск</button>
		</span>
	</div>
</form>