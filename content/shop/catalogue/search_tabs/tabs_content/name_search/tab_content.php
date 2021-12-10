<?php
//Скрипт для вывода содержимого таба "Поиск по наименованию"
defined('_ASTEXE_') or die('No access');
?>
<div class="search_tab_clar">Поиск товаров по наименованию в каталоге собственного наличия</div>
<form role="form" action="/shop/search" method="GET">
	<div class="input-group">
		<input value="<?php echo $value_for_input_search_string; ?>" type="text" class="form-control" placeholder="Введите наименование товара" name="search_string" />
		<span class="input-group-btn">
			<button class="btn btn-ar btn-default" type="submit">Поиск</button>
		</span>
	</div>
</form>