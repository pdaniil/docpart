<?php
/*
Скрипт вывода статистики запросов по артикулу
*/
defined('_ASTEXE_') or die('No access');
?>
<script type="text/javascript" src="/lib/highcharts/highcharts-custom.js"></script>
<script type="text/javascript" src="/lib/highcharts/highcharts-3d.js"></script>
<script type="text/javascript" src="/lib/highcharts/exporting.js"></script></script>

<!--<h3>Стистика запросов по артикулу</h3>-->

<?php
/*
1. Рейтинг артикулов:
- указание артикулов
- временной диапазон
- фильтр по пользователям


2. Динамика популярности артикулов:
- выводится графиком
- временной диапазон
- фильтр по пользователям

*/
?>



<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройка параметров графика
		</div>
		<div class="panel-body">
			<?php
			//Значения фильтра по умолчанию: за текущий день для всех пользователей
			list($y,$m,$d) = explode('-', date('Y-m-d', time()));
			$time_from = mktime(0,0,0,$m,$d,$y);//1. Время с
			$time_to = mktime(0,0,0,$m,$d+1,$y);//2. Время по
			$customer = -1;//3. Покупатель

			
			//Получаем текущие значения фильтра:
			$stat_article_queries_rating_filter = NULL;
			if( isset($_COOKIE["stat_article_queries_rating_filter"]) )
			{
				$stat_article_queries_rating_filter = $_COOKIE["stat_article_queries_rating_filter"];
			}
			if($stat_article_queries_rating_filter != NULL)
			{
				$stat_article_queries_rating_filter = json_decode($stat_article_queries_rating_filter, true);
				$time_from = $stat_article_queries_rating_filter["time_from"];
				$time_to = $stat_article_queries_rating_filter["time_to"];
				$customer = $stat_article_queries_rating_filter["customer"];
			}
			?>
			
			<div class="col-lg-6">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Дата с
					</label>
					<div class="col-lg-6">
						<div style="position:relative;height:34px;">
							<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_from" value="<?php echo $time_from; ?>" class="form-control" />
							<input style="position:absolute; z-index:1;" type="text" id="time_from_show" class="form-control" />
							<script>
							//Инициализируем datetimepicker
							jQuery("#time_from").datetimepicker({
								lang:"ru",
								closeOnDateSelect:true,
								closeOnTimeSelect:false,
								dayOfWeekStart:1,
								format:'unixtime',
								onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
								{
									var time_string = "";
									var date_ob = new Date(current_time);
									time_string += date_ob.getDate()+".";
									time_string += (date_ob.getMonth() + 1)+".";
									time_string += date_ob.getFullYear()+" ";
									time_string += date_ob.getHours()+":"+date_ob.getMinutes();
									document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
								}
								<?php
								if($time_from != "")
								{
									?>
									,
									onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_from_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
								}
								?>
							});
							</script>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col-lg-6">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Дата по
					</label>
					<div class="col-lg-6">
						<div style="position:relative;height:34px;">
							<input style="position:absolute; z-index:2; opacity:0" type="text"  id="time_to" value="<?php echo $time_to; ?>" class="form-control" />
							<input style="position:absolute; z-index:1;" type="text" id="time_to_show" class="form-control" />
							<script>
							//Инициализируем datetimepicker
							jQuery("#time_to").datetimepicker({
								lang:"ru",
								closeOnDateSelect:true,
								closeOnTimeSelect:false,
								dayOfWeekStart:1,
								format:'unixtime',
								onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
								{
									var time_string = "";
									var date_ob = new Date(current_time);
									time_string += date_ob.getDate()+".";
									time_string += (date_ob.getMonth() + 1)+".";
									time_string += date_ob.getFullYear()+" ";
									time_string += date_ob.getHours()+":"+date_ob.getMinutes();
									document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
								}
								<?php
								if($time_to != "")
								{
									?>
									,
									onGenerate:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
									{
										var time_string = "";
										var date_ob = new Date(current_time);
										time_string += date_ob.getDate()+".";
										time_string += (date_ob.getMonth() + 1)+".";
										time_string += date_ob.getFullYear()+" ";
										time_string += date_ob.getHours()+":"+date_ob.getMinutes();
										document.getElementById("time_to_show").value = time_string;//Показываем время в понятном виде
									}
									<?php
								}
								?>
							});
							</script>
						</div>
					</div>
				</div>
			</div>
			
			<div class="col-lg-6">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Все пользователи
					</label>
					<div class="col-lg-6">
						<?php
						$checked = "";
						if($customer == -1)
						{
							$checked = "checked=\"checked\"";
						}
						?>
						<input type="checkbox" onchange="all_customers_checked();" id="all_customers_checkbox" value="all_customers" <?php echo $checked; ?> />
					</div>
				</div>
			</div>
			
			<div class="col-lg-6">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						ID пользователя
					</label>
					<div class="col-lg-6">
						<?php
						$customer_str = "";
						$disabled = "";
						if($customer >= 0)
						{
							$customer_str = $customer;
						}
						else
						{
							$disabled = "disabled";
						}
						?>
						<input type="text"  id="customer" value="<?php echo $customer_str; ?>" <?php echo $disabled; ?> class="form-control"/>
					</div>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-lg-12 float-e-margins">
					<button class="btn btn-success" type="button" onclick="filterStatistics();"><i class="fa fa-filter"></i> Применить</button>
					<button class="btn btn-primary" type="button" onclick="unsetFilterStatistics();"><i class="fa fa-square"></i> Сброс настроек</button>
				</div>
			</div>
		</div>
	</div>
</div>





<script>
    // ------------------------------------------------------------------------------------------------
    //Устновка cookie в соответствии с фильтром
    function filterStatistics()
    {
        var stat_article_queries_rating_filter = new Object;
        
        //1. Время с
        stat_article_queries_rating_filter.time_from = document.getElementById("time_from").value;
        //2. Время по
        stat_article_queries_rating_filter.time_to = document.getElementById("time_to").value;
        //3. Покупатель
		if(document.getElementById("all_customers_checkbox").checked)
		{
			stat_article_queries_rating_filter.customer = -1;
		}
		else
		{
			stat_article_queries_rating_filter.customer = document.getElementById("customer").value;
			
			if(isNaN(stat_article_queries_rating_filter.customer) || stat_article_queries_rating_filter.customer < 0 || stat_article_queries_rating_filter.customer == "")
			{
				alert("Укажите числовой ID пользователя");
				return;
			}
		}
		
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "stat_article_queries_rating_filter="+JSON.stringify(stat_article_queries_rating_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location = "/<?php echo $DP_Config->backend_dir; ?>/shop/statistika";
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterStatistics()
    {
        var stat_article_queries_rating_filter = new Object;
        
        //1. Время с
        stat_article_queries_rating_filter.time_from = "";
        //2. Время по
        stat_article_queries_rating_filter.time_to = "";
        //3. Покупатель
        stat_article_queries_rating_filter.customer = "";
        
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() - 100);
        document.cookie = "stat_article_queries_rating_filter="+JSON.stringify(stat_article_queries_rating_filter)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location = "/<?php echo $DP_Config->backend_dir; ?>/shop/statistika";
    }
    // ------------------------------------------------------------------------------------------------
	//Обработка переключения "Все покупатели"
	function all_customers_checked()
	{
		console.log(document.getElementById("all_customers_checkbox").checked);
		
		if(document.getElementById("all_customers_checkbox").checked)
		{
			document.getElementById("customer").disabled = true;
		}
		else
		{
			document.getElementById("customer").disabled = false;
		}	
	}
	// ------------------------------------------------------------------------------------------------
</script>




<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			График
		</div>
		<div class="panel-body">
			<div id="chart_container">
			</div>
		</div>
	</div>
</div>



<script>
var chart_data = "";
/*
//Пример, как должно быть
var chart_data = [
	['Firefox', 45.0],
	['IE', 26.8],
	{
		name: 'Chrome',
		y: 12.8,
		sliced: true,
		selected: true
	},
	['Safari', 8.5],
	['Opera', 6.2],
	['Others', 0.7],
	{
		name: 'Проверка',
		y: 80
	}
];*/

// Построить круговую диаграмму
function buildPieChart()
{
	//Получаем данные
	jQuery.ajax({
		type: "GET",
		async: false, //Запрос синхронный
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/statistics/stat_article_queries_rating/ajax_get_chart_data.php",
		dataType: "json",//Тип возвращаемого значения
		data: "initiator=1",
		success: function(answer)
		{
			console.log(answer);
			chart_data = answer;
		}
	});
	
	//Формируем диаграмму
	$('#chart_container').highcharts({
        chart: {
            type: 'pie',
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        title: {
            text: 'Рейтинг запросов по артикулу'
        },
        tooltip: {
            pointFormat: '<b>{series.name}:</b> <b>{point.percentage:.1f}%</b> <br> <b>Запросов: {point.y}</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: true,
                    format: '{point.name}'
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Browser share',
            data: chart_data
        }]
    });
}
buildPieChart();
</script>