<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Мой заказ" - frontend

if($how_get_json["block"] != "")
{
	$how_get_json["block"] = "корп. ".$how_get_json["block"].", ";
}
$address = $how_get_json["street"].", д.".$how_get_json["house"].", ".$how_get_json["block"]." кв(офис). ".$how_get_json["flat_office"];
?>
<p class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<table class="table">
	<tr>
		<th>Информация о доставке</th>
	</tr>
	<tr>
		<td>Город: <?=$how_get_json["city"];?></td>
	</tr>
	<tr>
		<td>Адрес: <?=$address;?></td>
	</tr>
	<tr>
		<td>Телефон: <?=$how_get_json["phone"];?></td>
	</tr>
</table>