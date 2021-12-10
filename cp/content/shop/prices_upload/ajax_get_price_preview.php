<?php
//Серверный скрипт для просмотра загруженных прайс-листов админом
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
    $answer["result"] = 0;
    $answer["message"] = "Ошибка подключения к БД";
	header('Content-Type: application/json;charset=utf-8;');
    exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");




//Проверка прав:
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
//Проверяем право менеджера
if( ! DP_User::isAdmin())
{
	$result["status"] = false;
	$result["message"] = "Forbidden";
	$result["code"] = 501;
	header('Content-Type: application/json;charset=utf-8;');
	exit(json_encode($result));//Вообще не является администратором бэкенда
}





$price_id = (int)$_POST["price_id"];


//Сначала получаем количество:
$price_records_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_docpart_prices_data` WHERE `price_id` = ?;");
$price_records_query->execute( array($price_id) );
$price_records_count = $price_records_query->fetchColumn();

if( $price_records_count == 0 )
{
	?>
	<div class="text-center">
		В данном прайс-листе отсутствуют данные. Возможно Вы еще не загружали файлы в данный прайс-лист, либо данные были очищены.
	</div>
	<?php
	exit;
}


$price_records_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices_data` WHERE `price_id` = ? LIMIT 10;");
$price_records_query->execute( array($price_id) );

?>
<div>
<h3 style="font-weight:bold;">Предпросмотр прайс-листа</h3>
<p>В таблице ниже приведены несколько строк из данного прайс-листа. Вам необходимо убедиться в корректности данных. <font style="color:#000;font-weight:bold;">Корректным считается прайс-лист</font>, если:<br>
- данные в таблице ниже соответствуют своим колонкам (т.е. к примеру, цена должна быть строго в колонке "Цена" и т.д.)<br>
- заполнены обязательные колонки "Производитель", "Артикул", "Наименование"<br>
- заполнена колонка "Цена" значением типа "Число с точкой" больше 0<br>
- заполнена колонка "Количество" значением типа "Целое число" больше 0<br><br>

Если <font style="color:#000;font-weight:bold;">хотя бы одно</font> из этих условий не выполнено - прайс-лист загрузился некорректно и Вам необходимо скорректировать настройки, либо использовать корректный файл.</p>

<table style="border-spacing: 7px 5px;">

<tr>
	<th style="padding: 5px;">Производитель</th>
	<th style="padding: 5px;">Артикул</th>
	<th style="padding: 5px;">Наименование</th>
	<th style="padding: 5px;">Цена</th>
	<th style="padding: 5px;">Количество</th>
</tr>

<?php
while( $record = $price_records_query->fetch() )
{
	?>
	<tr>
		<td style="padding: 5px;"><?php echo $record["manufacturer"]; ?></td>
		<td style="padding: 5px;"><?php echo $record["article"]; ?></td>
		<td style="padding: 5px;"><?php echo $record["name"]; ?></td>
		<td style="padding: 5px;"><?php echo $record["price"]; ?></td>
		<td style="padding: 5px;"><?php echo $record["exist"]; ?></td>
	</tr>
	<?php
}
?>
</table>
</div>