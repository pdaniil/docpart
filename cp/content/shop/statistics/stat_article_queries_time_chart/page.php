<?php
/*
Скрипт вывода статистики запросов по артикулу
*/
defined('_ASTEXE_') or die('No access');
?>
<script type="text/javascript" src="/lib/highcharts/highcharts-custom.js"></script>
<script type="text/javascript" src="/lib/highcharts/exporting.js"></script></script>





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
			$articles = array();//Артикулы для поиска (по умолчанию)
			
			//Получаем текущие значения фильтра:
			$stat_article_queries_time_chart = NULL;
			if( isset($_COOKIE["stat_article_queries_time_chart"]) )
			{
				$stat_article_queries_time_chart = $_COOKIE["stat_article_queries_time_chart"];
			}
			if($stat_article_queries_time_chart != NULL)
			{
				$stat_article_queries_time_chart = json_decode($stat_article_queries_time_chart, true);
				$time_from = $stat_article_queries_time_chart["time_from"];
				$time_to = $stat_article_queries_time_chart["time_to"];
				$customer = $stat_article_queries_time_chart["customer"];
				$articles = $stat_article_queries_time_chart["articles"];
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
						<input type="checkbox" onchange="all_customers_checked();" id="all_customers_checkbox" value="all_customers" <?php echo $checked; ?>/>
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
						<input type="text"  id="customer" value="<?php echo $customer_str; ?>" <?php echo $disabled; ?> class="form-control" />
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





<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Список артикулов
		</div>
		<div class="panel-body">
			<div id="container_A" style="height:80px;"></div>
			<script type="text/javascript" charset="utf-8">
			/*ДЕРЕВО*/
			//Для редактируемости дерева
			webix.protoUI({
				name:"edittree"
			}, webix.EditAbility, webix.ui.tree);
			//Формирование дерева
			tree = new webix.ui({
				editable:true,//редактируемое
				editValue:"value",
				editaction:"dblclick",//редактирование по двойному нажатию
				container:"container_A",//id блока div для дерева
				view:"edittree",
				select:true,//можно выделять элементы
				drag:true,//можно переносить
				editor:"text",//тип редактирование - текстовый
			});
			/*~ДЕРЕВО*/
			webix.event(window, "resize", function(){ tree.adjust(); });
			//-----------------------------------------------------
			webix.protoUI({
				name:"editlist" // or "edittree", "dataview-edit" in case you work with them
			}, webix.EditAbility, webix.ui.list);
			//-----------------------------------------------------
			//Событие при выборе элемента дерева
			tree.attachEvent("onAfterSelect", function(id)
			{
				onSelected();
			});
			//-----------------------------------------------------
			//Обработка выбора элемента
			function onSelected()
			{
			}//function onSelected()
			//-----------------------------------------------------
			//Событие при успешном редактировании элемента дерева
			tree.attachEvent("onValidationSuccess", function(){
				onSelected();
			});
			//-----------------------------------------------------
			tree.attachEvent("onAfterEditStop", function(state, editor, ignoreUpdate){
				onSelected();
			});
			//-----------------------------------------------------
			//Обработчик После перетаскивания узлов дерева
			tree.attachEvent("onAfterDrop",function(){
				onSelected();
			});
			//-----------------------------------------------------
			//Добавить новый элемент в дерево
			function add_new_item()
			{
				var newItemId = tree.add( {value:"Артикул"}, tree.count(), 0);
				tree.scrollTo(0, newItemId);
				onSelected();//Обработка текущего выделения
			}
			//-----------------------------------------------------
			//Удаление выделеного элемента
			function delete_selected_item()
			{
				var nodeId = tree.getSelectedId();
				tree.remove(nodeId);
				onSelected();
			}
			//-----------------------------------------------------
			//Снятие выделения с дерева
			function unselect_tree()
			{
				tree.unselect();
				onSelected();
			}
			//-----------------------------------------------------
			//Инициализация редактора дерева после загруки страницы
			function tree_start_init()
			{
				var articles = <?php echo json_encode($articles); ?>;
				tree.parse(articles);
				tree.openAll();
			}
			tree_start_init();
			onSelected();//Обработка текущего выделения
			</script>
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-lg-12 float-e-margins">
					<button class="btn btn-success" type="button" onclick="add_new_item();"><i class="fa fa-plus-square"></i> Добавить</button>
					<button class="btn btn-danger" type="button" onclick="delete_selected_item();"><i class="fa fa-trash-o"></i> Удалить</button>
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
        var stat_article_queries_time_chart = new Object;
        
        //1. Время с
        stat_article_queries_time_chart.time_from = document.getElementById("time_from").value;
        //2. Время по
        stat_article_queries_time_chart.time_to = document.getElementById("time_to").value;
        //3. Покупатель
		if(document.getElementById("all_customers_checkbox").checked)
		{
			stat_article_queries_time_chart.customer = -1;
		}
		else
		{
			stat_article_queries_time_chart.customer = document.getElementById("customer").value;
			
			if(isNaN(stat_article_queries_time_chart.customer) || stat_article_queries_time_chart.customer < 0 || stat_article_queries_time_chart.customer == "")
			{
				alert("Укажите числовой ID пользователя");
				return;
			}
		}
		//4.Артикулы
		stat_article_queries_time_chart.articles = new Array();
		//Получаем массив элементов дерева:
    	var articles_tree_json = tree.serialize();
    	for(var i=0; i < articles_tree_json.length; i++)
		{
			stat_article_queries_time_chart.articles.push(articles_tree_json[i].value);
		}

        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() + 15552000 * 1000);
        document.cookie = "stat_article_queries_time_chart="+JSON.stringify(stat_article_queries_time_chart)+"; path=/; expires=" + date.toUTCString();
        
        //Обновляем страницу
        location = "/<?php echo $DP_Config->backend_dir; ?>/shop/statistika";
    }
    // ------------------------------------------------------------------------------------------------
    //Снять все фильтры
    function unsetFilterStatistics()
    {
        var stat_article_queries_time_chart = new Object;
        
        //1. Время с
        stat_article_queries_time_chart.time_from = "";
        //2. Время по
        stat_article_queries_time_chart.time_to = "";
        //3. Покупатель
        stat_article_queries_time_chart.customer = "";
        
        
        //Устанавливаем cookie (на полгода)
        var date = new Date(new Date().getTime() - 100);
        document.cookie = "stat_article_queries_time_chart="+JSON.stringify(stat_article_queries_time_chart)+"; path=/; expires=" + date.toUTCString();
        
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
//Пример, как должно быть
/*
var chart_data = [{
		name: 'Tokyo',
		data: [7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6]
	}, {
		name: 'New York',
		data: [-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5]
	}, {
		name: 'Berlin',
		data: [-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0]
	}, {
		name: 'London',
		data: [3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8]
	},
	{
		name: 'OC247',
		data: [3.9, 4.2, 2, 8.5, 11.9, 5.2, 17.0, 7, 14.2, 10.3, 6.6, 4.8]
	}];
*/
var xAxis = "";

// Построить круговую диаграмму
function buildChart()
{
	//Получаем данные
	jQuery.ajax({
		type: "GET",
		async: false, //Запрос синхронный
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/statistics/stat_article_queries_time_chart/ajax_get_chart_data.php",
		dataType: "json",//Тип возвращаемого значения
		data: "initiator=1",
		success: function(answer)
		{
			if(answer.status != true)
			{
				alert("Ошибка получения данных");
				return;
			}
			
			xAxis = answer.xAxis;
			chart_data = answer.chart_data;
			
			
			console.log(answer);
			//chart_data = answer;
		}
	});
	
	$(function () {
		$('#chart_container').highcharts({
			title: {
				text: 'График количества запросов по артикулу',
				x: -20 //center
			},
			subtitle: {
				text: '',
				x: -20
			},
			xAxis: {
				categories: xAxis
			},
			yAxis: {
				title: {
					text: 'Количество запросов'
				},
				plotLines: [{
					value: 0,
					width: 1,
					color: '#808080'
				}]
			},
			tooltip: {
				valueSuffix: ' Запросов'
			},
			legend: {
				layout: 'vertical',
				align: 'right',
				verticalAlign: 'middle',
				borderWidth: 0
			},
			series: chart_data
		});
	});
}
buildChart();
</script>