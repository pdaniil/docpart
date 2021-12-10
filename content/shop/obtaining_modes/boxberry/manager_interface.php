<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа" - backend

//В зависимости от выбранного способо доставки Boxberyy
if($how_get_json["type"] == "pickup")
{
	$param_name_value = "Доставка Boxberyy - Самовывоз из точки выдачи";
	$table_body = "
		<tr>
			<th>Адрес точки выдачи</th>
		</tr>
		<tr>
			<td>{$how_get_json["address"]}</td>
		</tr>
	"; 
}
elseif($how_get_json["type"] == "courier")
{
	$param_name_value = "Доставка Boxberyy - Курьерская доставка";
	$table_body = "	
		<tr>
			<th>Имя</th>
			<th>Фамилия</th>
			<th>Отчество</th>
			<th>Телефон</th>
			<th>Email</th>
			<th>Почтовый индекс</th>
			<th>Город</th>
			<th>Адресс</th>
		</tr>
		<tr>
			<td>{$how_get_json["name"]}</td>
			<td>{$how_get_json["surname"]}</td>
			<td>{$how_get_json["patronymic"]}</td>
			<td>{$how_get_json["cellphone"]}</td>
			<td>{$how_get_json["email"]}</td>
			<td>{$how_get_json["post_index"]}</td>
			<td>{$how_get_json["city"]}</td>
			<td>{$how_get_json["address"]}</td>
		</tr>
	";
}
?>
<p>Способ получения - <b><?php echo trim($param_name_value);?></b></p>
<table class="table">
<?php echo trim($table_body);?>
</table>	