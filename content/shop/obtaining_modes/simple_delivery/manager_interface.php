<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа" - backend

if($how_get_json["block"] != "")
{
	$how_get_json["block"] = "корп. ".$how_get_json["block"].", ";
}
$address = $how_get_json["street"].", д.".$how_get_json["house"].", ".$how_get_json["block"]." кв(офис). ".$how_get_json["flat_office"];
?>
<p>Способ получения - <b><?=$obtain_mode["caption"];?></b></p>
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