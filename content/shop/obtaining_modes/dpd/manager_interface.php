<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа" - backend
?>
<p>Способ получения - <b><?=$obtain_mode["caption"];?></b></p>
<table class="table">
	<tr>
		<th>Информация о доставке</th>
	</tr>
	<tr>
		<td>Адрес: <?=$how_get_json["address"];?></td>
	</tr>
</table>