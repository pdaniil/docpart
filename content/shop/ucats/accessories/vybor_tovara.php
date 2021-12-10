<?php
/*
Скрипт для вывода каталога автохимии
*/
defined('_ASTEXE_') or die('No access');

//Входные данные
$car_name = htmlentities($_GET["car_name"], ENT_QUOTES, "UTF-8");
$model = htmlentities($_GET["model"], ENT_QUOTES, "UTF-8");
$year = htmlentities($_GET["year"], ENT_QUOTES, "UTF-8");
$img = htmlentities($_GET["img"], ENT_QUOTES, "UTF-8");
?>

<table class="table">
	<tr>
		<td>
			<div align="left" style="padding:5px;"><b>Марка:</b> <a href="/shop/katalogi-ucats/avtoaksessuary/vybor-modeli?car_name=<?php echo $car_name; ?>" class="bread_crumbs_a"><?php echo ucwords($car_name); ?></a></div>
			<div align="left" style="padding:5px;"><b>Модель:</b> <?php echo $model." ".$year; ?></div>
		</td>
		<td>
			
		</td>
	</tr>
</table>




<div id="products_block" style="width:100%;text-align:center;margin-top:25px;padding-top:10px;background-color:#FFF;border-radius:8px;">
	<img src="/content/files/images/ajax-loader-transparent.gif" />
</div>
<script>
groupChanged();//После загрузки страницы - обрабатываем выбранную группу товаров
</script>