<?php
defined('_ASTEXE_') or die('No access');
// Скрипт вывода детальной информации по выбранному способу получения. Используется на странице "Мой заказ" - frontend
?>
<p class="lead">Способ получения - <?=$obtain_mode["caption"];?></p>
<?php
$office_to_show = $how_get_json["office_id"];
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/get_in_office/show_office_info.php");
?>