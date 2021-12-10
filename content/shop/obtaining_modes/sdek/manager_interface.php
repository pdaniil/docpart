<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа" - backend
?>
<p>Способ получения - <b><?=$obtain_mode["caption"];?></b></p>
<table class="table">
	<tr>
		<th colspan="2">Точка выдачи</th>
	</tr>
	<tr>
		<td>Город: <?=$how_get_json['city'];?></td>
		<td>Адрес: <?=$how_get_json['address'];?></td>
	</tr>
	<tr>
		<td>Название точки выдачи: <?=$how_get_json['point_name'];?></td>
		<td>ID точки выдачи в СДЭК: <?=$how_get_json['point_id'];?></td>
	</tr>
	<tr>
		<td>Время работы: <?=$how_get_json['work_time'];?></td>
		<td>Телефон: <?=$how_get_json['phone'];?></td>
	</tr>
</table>