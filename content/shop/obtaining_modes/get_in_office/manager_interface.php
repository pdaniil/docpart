<?php
defined('_ASTEXE_') or die('No access');
//Скрипт вывода графического интерфейса для менеджера в панели управления - на странице "Карта заказа"
?>
<p>Способ получения - <b><?=$obtain_caption;?></b></p>
<?php
$office_to_show = $how_get_json["office_id"];
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/obtaining_modes/get_in_office/show_office_info.php");
?>