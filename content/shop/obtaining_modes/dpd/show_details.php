<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Подтверждения заказа" - frontend
?>
<p class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<table class="table">
	<tr>
		<th>Информация о доставке</th>
	</tr>
	<tr>
		<td>Адрес: <?=$how_get_json["address"];?></td>
	</tr>
</table>