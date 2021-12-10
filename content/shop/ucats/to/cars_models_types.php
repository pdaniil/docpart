<?php
/*
Скрипт демонстрационной страницы каталога ТО
Выбор комплектации
*/
defined('_ASTEXE_') or die('No access');

//Получаем данные:
$car_name = htmlentities($_GET["car_name"], ENT_QUOTES, "UTF-8");
$model_caption = htmlentities($_GET["model_caption"], ENT_QUOTES, "UTF-8");
$car_id = (int)$_GET["car_id"];
$model_id_to = (int)$_GET["model_id_to"];
$img = htmlentities($_GET["img"], ENT_QUOTES, "UTF-8");
?>
<table class="table">
	<tr>
		<td>
			<div align="left" style="padding:5px;"><b>Марка:</b> <a href="/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya/vybor-modeli?car_id=<?php echo $car_id; ?>&car_name=<?php echo $car_name; ?>" class="bread_crumbs_a"><?php echo ucwords($car_name); ?></a></div>
			<div align="left" style="padding:5px;"><b>Модель:</b> <?php echo $model_caption; ?></div>
		</td>
		<td>
			<img src="<?php echo $img; ?>" />
		</td>
	</tr>
</table>


<?php
//Получаем список моделей выбранной марки через веб-сервис каталога
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $DP_Config->ucats_url."ucats/to/get_types.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&model_id=$model_id_to");
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$curl_result = curl_exec($curl);
curl_close($curl);
$curl_result = json_decode($curl_result, true);

if($curl_result["status"] == "ok")
{
	?>
	<table class="table">
		<tr>
			<th align="left">Двигатель</th>
			<th align="left">Мощность</th>
			<th align="left">Топливо</th>
			<th align="left">Годы выпуска</th>
		</tr>
	<?php
	for($i=0; $i < count($curl_result["list"]); $i++)
	{
		$tr_class = "even";
		if($i % 2 == 0)
		{
			$tr_class = "odd";
		}
		
		$engine = $curl_result["list"][$i];
		
		$type_id = $engine["id"];
		$engine_name = $engine["name"]." ".$engine["engine_model"];
		$engine_horse = $engine["engine_horse"]." л.с.";
		$engine_fuel = $engine["engine"];
		$engine_type_year = $engine["type_year"];
		
		$href = "/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya/vybor-modeli/vybor-komplektacii/spisok-zapchastej?car_id=$car_id&model_id_to=$model_id_to&model_caption=".urlencode($model_caption)."&car_name=".urlencode($car_name)."&type_id=$type_id&type_caption=".urlencode($engine_name." ".$engine_horse." ".$engine_fuel." ".$engine_type_year)."&img=".urlencode($img);
		?>
		<tr class="<?php echo $tr_class; ?>">
			<td><a href="<?php echo $href; ?>" class="bread_crumbs_a"><?php echo $engine_name; ?></a></td>
			<td><a href="<?php echo $href; ?>" class="bread_crumbs_a"><?php echo $engine_horse; ?></a></td>
			<td><a href="<?php echo $href; ?>" class="bread_crumbs_a"><?php echo $engine_fuel; ?></a></td>
			<td><a href="<?php echo $href; ?>" class="bread_crumbs_a"><?php echo $engine_type_year; ?></a></td>
		</tr>
		<?php
	}
	?>
	</table>
	<?php
}
else
{
	var_dump($curl_result);
}
?>