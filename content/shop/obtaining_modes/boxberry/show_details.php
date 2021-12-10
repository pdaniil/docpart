<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Подтверждения заказа" - frontend
?>
<p style="margin-bottom:2px;" class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<div class="row" style="margin:0;">
<div style="border: 2px solid #2020ff; padding-bottom: 10px; margin-bottom: 40px;">
<?php
if($how_get_json["type"] == "pickup")
{
?>
	<table class="table">
		<tr>
			<th>Точка выдачи</th>
		</tr>
		<tr>
			<td><?=$how_get_json["address"]; ?></td>
		</tr>
	</table>
<?php
}
else if($how_get_json["type"] == "courier")
{
	?>
	<table class="table">
		<tr>
			<th>Имя</th>
			<th>Фамилия</th>
			<th>Отчество</th>
			<th>Телефон</th>
			<th>Email</th>
			<th>Почтовый индекс</th>
			<th>Город</th>
			<th>Адрес</th>
		</tr>
		<tr>
			<td><?=$how_get_json["name"]; ?></td>
			<td><?=$how_get_json["surname"]; ?></td>
			<td><?=$how_get_json["patronymic"]; ?></td>
			<td><?=$how_get_json["cellphone"]; ?></td>
			<td><?=$how_get_json["email"]; ?></td>
			<td><?=$how_get_json["post_index"]; ?></td>
			<td><?=$how_get_json["city"]; ?></td>
			<td><?=$how_get_json["address"]; ?></td>
		</tr>
	</table>
	<?php
}
?>
</div>
</div>