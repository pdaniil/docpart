<?php
//Скрипт для вывода содержимого таба "VIN-запрос" (специально для каталогов neoriginal.ru)
defined('_ASTEXE_') or die('No access');
?>
<div class="search_tab_clar">Поиск автозапчастей по VIN-коду автомобиля</div>
<form role="form" action="/originalnye-katalogi" method="GET">
	
	<input type="hidden" name="VinAction" value="Search" />
	<input type="hidden" name="language" value="ru" />
	
	<div class="input-group">
		<input value="" type="text" class="form-control" placeholder="Введите VIN" name="vin" />
		<span class="input-group-btn">
			<button class="btn btn-ar btn-default" type="submit">Поиск</button>
		</span>
	</div>
</form>