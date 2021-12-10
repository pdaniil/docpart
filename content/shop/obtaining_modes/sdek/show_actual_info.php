<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Мой заказ" - frontend
?>
<p class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<table class="table">
	<tr>
		<th colspan="2" >Точка выдачи</th>
	</tr>
	<tr>
		<td>Город: <?=$how_get_json['city'];?></td>
		<td>Адрес: <?=$how_get_json['address'];?></td>
	</tr>
	<tr>
		<td colspan="2" >Название точки выдачи: <?=$how_get_json['point_name'];?></td>
	</tr>
	<tr>
		<td>Время работы: <?=$how_get_json['work_time'];?></td>
		<td>Телефон: <?=$how_get_json['phone'];?></td>
	</tr>
</table>