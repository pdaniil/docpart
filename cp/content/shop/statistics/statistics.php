<?php
/*
Страничный скрипт Статистики интернет-магазина
*/
defined('_ASTEXE_') or die('No access');


$statistics_type = "article_queries_rating";//По учолчанию - выбранный отчет - Рейтинг запросов по артикулу
if( isset($_COOKIE["statistics_type"]) )
{
	$statistics_type = $_COOKIE["statistics_type"];
}
?>



<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки
		</div>
		<div class="panel-body">
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Выбор отчета
				</label>
				<div class="col-lg-6">
					<select id="statistics_type_select" onchange="onchange_statistics_type_select();" class="form-control">
						<option value="article_queries_rating">Рейтинг запросов по артикулу</option>
						<option value="article_queries_time_chart">График количества запросов по артикулу</option>
					</select>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
document.getElementById("statistics_type_select").value = '<?php echo $statistics_type; ?>';
function onchange_statistics_type_select()
{
	//Устанавливаем cookie (на полгода)
	var date = new Date(new Date().getTime() + 15552000 * 1000);
	document.cookie = "statistics_type="+document.getElementById("statistics_type_select").value+"; path=/; expires=" + date.toUTCString();
	
	//Обновляем страницу
	location='/<?php echo $DP_Config->backend_dir; ?>/shop/statistika';
}
</script>



<?php
//В зависимости от выставленного типа отчета - подключаем соответствующий скрипт
if($statistics_type == "article_queries_rating")
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/statistics/stat_article_queries_rating/page.php");
}
else if($statistics_type == "article_queries_time_chart")
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/statistics/stat_article_queries_time_chart/page.php");
}
?>