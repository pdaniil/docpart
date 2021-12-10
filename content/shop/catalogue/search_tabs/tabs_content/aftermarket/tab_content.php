<?php
//Скрипт для вывода содержимого таба "Поиск по артикулу"
defined('_ASTEXE_') or die('No access');
?>
<div class="search_tab_clar">Поиск автозапчастей по неоригинальным каталогам. Выберите каталог:</div>


<?php
//1. Получаем админские настройки таба
$aftermarket_tab_query = $db_link->prepare('SELECT * FROM `shop_docpart_search_tabs` WHERE `name` = :name;');
$aftermarket_tab_query->bindValue(':name', 'aftermarket');
$aftermarket_tab_query->execute();
$aftermarket_tab_record = $aftermarket_tab_query->fetch(PDO::FETCH_ASSOC);
$aftermarket_tab_parameters_values = json_decode($aftermarket_tab_record["parameters_values"], true);


$aftermarket_catalogs_parts_html = "";
if( isset($aftermarket_tab_parameters_values["aftermarket_catalogs_parts_com_show"]) )
{
	if( $aftermarket_tab_parameters_values["aftermarket_catalogs_parts_com_show"] === "on" )
	{
		$aftermarket_catalogs_parts_html = "<div class=\"search_tab_car_catalogue\"><a href=\"https://aftermarket.catalogs-parts.com/#{client:".$aftermarket_tab_parameters_values["aftermarket_catalogs_parts_com_id"].";page:models;lang:ru;catalog:pc}\" target=\"_blank\"><i class=\"fa fa-check\"></i> ".$aftermarket_tab_parameters_values["aftermarket_catalogs_parts_com_caption"]."</a></div>";
	}
}




$aftermarket_ilcats_html = "";
if( isset($aftermarket_tab_parameters_values["aftermarket_ilcats_show"]) )
{
	if( $aftermarket_tab_parameters_values["aftermarket_ilcats_show"] === "on" )
	{
		$aftermarket_ilcats_html = "<div class=\"search_tab_car_catalogue\"><a href=\"http://aftermarket.autocats.ru.com/pid/".$aftermarket_tab_parameters_values["aftermarket_ilcats_pid"]."/clid/".$aftermarket_tab_parameters_values["aftermarket_ilcats_clid"]."\" target=\"_blank\"><i class=\"fa fa-check\"></i> ".$aftermarket_tab_parameters_values["aftermarket_ilcats_caption"]."</a></div>";
	}
}



if( $aftermarket_tab_parameters_values["aftermarket_catalogs_parts_com_order"] <= $aftermarket_tab_parameters_values["aftermarket_ilcats_order"] )
{
	$aftermarket_html = $aftermarket_catalogs_parts_html.$aftermarket_ilcats_html;
}
else
{
	$aftermarket_html = $aftermarket_ilcats_html.$aftermarket_catalogs_parts_html;
}


if( $aftermarket_html == "")
{
	$aftermarket_html = "<p class=\"search_tab_car_catalogue_back\" style=\"text-decoration:none;\"><i class=\"fa fa-wrench\"></i> Каталоги Aftermarket не подключены к сайту. Их можно подключить в панели управления на странице \"Табы поиска\"</p>";
}
echo $aftermarket_html;
?>




<p class="search_tab_car_catalogue_back" style="text-decoration:none;"><i class="fa fa-info-circle"></i> После выбора каталога откроется отдельная страница для подбора запчастей. При нажатии на нужную запчасть, Вы будете автоматически переброшены на поиск цен и наличия выбранной запчасти, а также ее аналогов и заменителей в нашем интернет-магазине.</p>