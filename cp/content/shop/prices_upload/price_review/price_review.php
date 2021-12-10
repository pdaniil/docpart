<?php
//Страничный скрипт для простановки цен
defined('_ASTEXE_') or die('No access');



if( isset( $_GET['action'] ) )
{
	
}
else//Действий нет - выводим страницу
{
	?>
	
	<?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	
	
	<?php
	$price_id = $_GET["price_id"];
	$price_id_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_docpart_prices` WHERE `id` = ?;");
	$price_id_query->execute( array($price_id) );
	if( $price_id_query->fetchColumn() == 0 )
	{
		exit;
	}
	?>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/prices">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/excel.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер прайс-листов</div>
				</a>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>

			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Инструкция
			</div>
			<div class="panel-body">

<p>Данная функция предназначена для простановки цен в своем прайс-листе.<br>
Эта функция нужна, если в магазине скопился товар, закупленный давно (месяцы или годы назад) и закупочные цены которого уже не имеют значения для сегодняшнего дня. Т.е. стоит цель - продать залежавшийся товар по конкурентной и при этом максимально выгодной для магазина цене.</p>

<p>Данная функция позволяет проставить цены на товары в таком прайс-листе на основе свежих прайс-листов, полученных, например, от своих поставщиков или конкурентов.</p>


<p>Как пользоваться функцией:</p>


<p>1. Загрузите на свой сайт прайс-листы от своих поставщиков (как обычно, через "Менеджер прайс-листов")</p>

<p>2. Загрузите свой прайс-лист, в котором Вы хотите проставить цены</p>

<p>3. Нажмите кнопку, отмеченную значком <i class="fas fa-sync"></i>, напротив своего прайс-листа. Откроется данная страница</p>

<p>4. Укажите желаемые настройки простановки цен.</p>

<ul>
	<li><strong>Базовая отметка</strong> Это уровень цен из прайс-листов от поставщиков, на основе которого будет определяться цена на такие же товары в вашем прайс-листе.</li>

	<li><strong>Действие</strong> Т.е. каким образом из цен поставщиков получить цену для своего товара.</li>

	<li><strong>Процент к действию</strong> Процент от цены поставщиков, который прибавляется или вычитается из цены поставщика, чтобы получилась цена для вашего прайс-листа</li>

	<li><strong>Перечень прайс-листов</strong> Выберите прайс-листы поставщиков, на основе которых вы хотите проставить цены в своем прайс-листе</li>
</ul>


<p>5. Нажмите кнопку "Проставить цены" (на данной странице). Запустится процесс простановки цен в соответствии с настройками. Цены будут проставлены прямо в вашем прайс-листе, который вы тут же можете использовать для создания "Склада" и подключения его к точке выдачи - чтобы ваши покупатели уже сразу могли заказывать товар из этого прайс-листа.<br>
После завершения процесса простановки цен, будет показан отчет, из которого можно будет понять, по каким позициям прайс-листа проставлены цены, а по каким - нет (например, если такие товары не были найдены у поставщиков)</p>


<p>Особенности: функция работает только с прайс-листами. Т.е. API-поставщики не учитываются.</p>


<h3>Пример 1.</h3>
<p>Например, нужно продать товар по цене, которая на 10% ниже средней по рынку.<br>
Тогда, выбираем:<br>
Базовая отметка - "Средняя цена"<br>
Действие - "Вычесть"<br>
Процент к действию - 10<br>
Перечень прайс-листов - Выбираем все доступные<br>
<br>
По каждому товару в вашем прайс-листе, платформа найдет все такие же товары в прайс-листах поставщиков, посчитает среднюю цену, вычтет из нее 10% и полученную цену запишет для данного товара в вашем прайс-листе.</p>


<h3>Пример 2.</h3>
<p>Например, нужно продать товар по цене, которая на 3% выше минимальной по рынку.<br>
Тогда, выбираем:<br>
Базовая отметка - "Минимальная цена"<br>
Действие - "Прибавить"<br>
Процент к действию - 3<br>
Перечень прайс-листов - Выбираем все доступные<br>
<br>
Для каждого товара в вашем прайс-листе, будут найдены все такие же товары в других прайс-листах, затем будет найдена самая низкая цена на такой товар, к ней прибавится 3% и получившаяся цена запишется для данного товара в вашем прайс-листе.</p>

			</div>
		</div>
	</div>
	
	
	
	
	
	<div class="col-lg-12" id="options_div">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Настройки проставновки цен
			</div>
			<div class="panel-body form-horizontal">
				
				
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Базовая отметка</label>

                    <div class="col-sm-10">
						<select class="form-control m-b" id="base_mark">
							<option value="min">Минимальная цена</option>
							<option value="middle">Средняя цена</option>
							<option value="max">Максимальная цена</option>
						</select>
                    </div>
                </div>
				
				
				<div class="hr-line-dashed"></div>
				
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Действие</label>

                    <div class="col-sm-10">
						<select class="form-control m-b" id="plus_minus">
							<option value="plus">Прибавить</option>
							<option value="minus">Вычесть</option>
						</select>
                    </div>
                </div>
				
				
				<div class="hr-line-dashed"></div>
				
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Процент к действию</label>

                    <div class="col-sm-10">
						<input class="form-control" placeholder="Укажите процент (целое число), сколько нужно добавить или вычесть относительно базовой отметки. Пустое значение означает 0" id="percent" type="number" />
                    </div>
                </div>
				
				
				<div class="hr-line-dashed"></div>
				
				
				
				<div class="form-group">
					<label class="col-sm-2 control-label">Перечень прайс-листов</label>

                    <div class="col-sm-10">
						
						<select multiple="multiple" id="prices">
							
							<?php
							$prices_query = $db_link->prepare("SELECT * FROM `shop_docpart_prices` WHERE `id` != ?;");
							$prices_query->execute( array( $price_id ) );
							while( $price = $prices_query->fetch() )
							{
								?>
								<option value="<?php echo $price['id']; ?>"><?php echo $price['name']." (ID ".$price['id'].")"; ?></option>
								<?php
							}
							?>
						
						</select>
						<script>
							//Делаем из селектора виджет с чекбоками
							$('#prices').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
						</script>
						
                    </div>
                </div>
				
				
				<div class="hr-line-dashed"></div>
				
				<div id="buttons_div">
					<button type="button" class="btn w-xs btn-primary2" onclick="review_price();"><i class="fas fa-sync"></i> Проставить цены</button>
				</div>
		
			</div>
		</div>
	</div>
		
		
		
	
	<div class="col-lg-12" id="progress_bar_div" style="display:none;">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Индикация процесса
			</div>
			<div class="panel-body form-horizontal">
		
				<div class="m-t-xl" style="margin-top:0!important;">
					<h3 class="m-b-xs">Простановка цен</h3>
					<span class="font-bold no-margins" id="progress_text">
						Выполнено 76.43%
					</span>
					<div class="progress m-t-xs full progress-small">
						<div style="width: 55%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="55" role="progressbar" class=" progress-bar progress-bar-success" id="progress_bar"></div>
					</div>
					
					<div class="row" id="cancel_button_div">
						<div class="col-md-12">
							<button type="button" class="btn w-xs btn-danger" onclick="cancel_process();"><i class="fas fa-stop"></i> Отмена</button>
							<script>
							function cancel_process()
							{
								can_go_on = 0;
								
								if( confirm('Прервать процесс простановки цен?') )
								{
									document.getElementById('options_div').setAttribute('style', 'display:block;');
									document.getElementById('progress_bar_div').setAttribute('style', 'display:none;');
									return;
								}
								else
								{
									//Продолжаем
									can_go_on = 1;
									send_request_for_one_part();
								}
							}
							</script>
						</div>
					</div>
					
					<div class="row" id="work_result">
						
						<div class="col-md-12">
							<h3>Процесс завершен. Цены для данного прайс-листа проставились в соответствии с настройкми.</h3>
						</div>
						
						
						<div class="col-md-4">
							Количество позиций прайс-листа (всего): <span id="items_count">10000</span><br>
							<button type="button" class="btn w-xs btn-primary" onclick="download_price(1);" id="download_button_1"><i class="fas fa-download"></i> Скачать</button> <img src="/content/files/images/ajax-loader-transparent.gif" id="download_img_1" style="display:none;" />
						</div>
						
						<div class="col-md-4">
							Количесто позиций с проставленными ценами: <span id="reviewed_yes">5000</span><br>
							<button type="button" class="btn w-xs btn-success" onclick="download_price(2);" id="download_button_2"><i class="fas fa-download"></i> Скачать</button> <img src="/content/files/images/ajax-loader-transparent.gif" id="download_img_2" style="display:none;" />
						</div>
						
						<div class="col-md-4">
							Количество позиций с непроставленными ценами <span id="reviewed_no">5000</span><br>
							<button type="button" class="btn w-xs btn-danger" onclick="download_price(3);" id="download_button_3"><i class="fas fa-download"></i> Скачать</button> <img src="/content/files/images/ajax-loader-transparent.gif" id="download_img_3" style="display:none;" />
						</div>
						
						<script>
						// -----------------------------------------------------------------------------------------
						function download_price(type)
						{
							//Индикация рядом
							document.getElementById('download_img_'+type).setAttribute('style', 'display:block;');
							
							//Все кнопки неактивны
							document.getElementById('download_button_1').disabled = true;
							document.getElementById('download_button_2').disabled = true;
							document.getElementById('download_button_3').disabled = true;
							
							
							jQuery.ajax({
								type: "POST",
								async: true,
								url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_upload/price_review/ajax_create_csv.php",
								dataType: "text",//Тип возвращаемого значения
								data: "price_id=<?php echo $price_id; ?>&type="+type,
								success: function(answer)
								{	
									//Все кнопки активны
									document.getElementById('download_button_1').disabled = false;
									document.getElementById('download_button_2').disabled = false;
									document.getElementById('download_button_3').disabled = false;
									
									//Убрать индикацию
									document.getElementById('download_img_1').setAttribute('style', 'display:none;');
									document.getElementById('download_img_2').setAttribute('style', 'display:none;');
									document.getElementById('download_img_3').setAttribute('style', 'display:none;');
									
									
									var answer_ob = JSON.parse(answer);
									
									//Если некорректный парсинг ответа
									if( typeof answer_ob.status === "undefined" )
									{
										alert("Ошибка парсинга ответа сервера");
									}
									else
									{
										//Корректный парсинг ответа
										if(answer_ob.status == true)
										{
											//Здесь скачиваем файл
											var a = document.createElement("a");
											a.href = answer_ob.csv_path_rel;
											a.download = answer_ob.csv_name;
											a.click();
										}
										else
										{
											alert(answer_ob.message);
										}
									}							
								}
							});
						}
						// -----------------------------------------------------------------------------------------
						</script>
						
					</div>
				</div>
				
	
			</div>
		</div>
	</div>
	
	
	
	<script>
	<?php
	//Для управления процессом, нужно знать количество позиций в прайс-листе
	$items_count_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_docpart_prices_data` WHERE `price_id` = ?;");
	$items_count_query->execute( array( $price_id ) );
	$items_count = $items_count_query->fetchColumn();
	?>
	var items_count = parseInt(<?php echo (int)$items_count; ?>);
	var process_complete_parts = '';//Для массива, в котором будет храниться учет обработанных частей
	var items_per_time = 100;//Сколько строк обрабатывать за один запрос
	var can_go_on = 1;//Флаг - можно продолжать
	var was_complete = 0;//Флаг - процесс был завершен
	// -----------------------------------------------------------------------------------
	//Нажатие кнопки "Проставить цены"
	function review_price()
	{
		if( was_complete )
		{
			if( !confirm('Начать процесс заново?') )
			{
				return;
			}
		}
		
		
		//Проверка настроек
		//Процент
		var percent = document.getElementById('percent').value;
		percent = parseInt(percent*100)/100;
		//Значение не должно быть отрицательным
		if( percent < 0 )
		{
			alert('Процент не должен быть отрицательным');
			return;
		}
		//Перечень прайс-листов, откуда брать цены
		var prices = [].concat( $("#prices").multipleSelect('getSelects') );
		if( prices.length == 0 )
		{
			alert('Выберите хотя бы один прайс-лист, на основе которого нужно будет проставить цены');
			return;
		}
		
		//Обнуляем переменные для управления процессом
		process_complete_parts = new Array();
		var process_parts_count = items_count/items_per_time;
		if( items_count%items_per_time > 0 )
		{
			process_parts_count++;
		}
		for(var i=0; i < process_parts_count; i++)
		{
			process_complete_parts.push(0);
		}
		can_go_on = 1;//Флаг - можно продолжать
		was_complete = 0;
		
		
		//Индикация процесса (СТАРТ)
		document.getElementById('progress_bar').setAttribute('aria-valuenow', '0');
		document.getElementById('progress_bar').setAttribute('style', 'width: 0%');
		document.getElementById('progress_text').innerHTML = 'Выполнено 0%';
		document.getElementById('work_result').setAttribute('style', 'display:none;');
		document.getElementById('options_div').setAttribute('style', 'display:none;');
		document.getElementById('progress_bar_div').setAttribute('style', 'display:block;');
		document.getElementById('cancel_button_div').setAttribute('style', 'display:block;');
		
		
		
		//Отправляем первый запрос
		send_request_for_one_part();
	}
	// -----------------------------------------------------------------------------------
	//Отправка запроса для одной части
	function send_request_for_one_part()
	{
		//Определяем, на каком шаге сейчас
		var is_start = 0;
		var is_end = 0;
		var start_from = 0;
		if( process_complete_parts[0] == 0 )
		{
			is_start = 1;
		}
		if( parseInt(process_complete_parts[ process_complete_parts.length-1 ]) == 0 && parseInt(process_complete_parts[ process_complete_parts.length-2 ]) == 1 )
		{
			is_end = 1;
		}
		for( var i=0; i < process_complete_parts.length; i++)
		{
			if( process_complete_parts[i] == 0 )
			{
				process_complete_parts[i] = 1;
				start_from = items_per_time*i;
				break;
			}
		}
		
		
		//Здесь уже можно не проверять
		var percent = document.getElementById('percent').value;
		var prices = [].concat( $("#prices").multipleSelect('getSelects') );
		
		jQuery.ajax({
			type: "POST",
			async: true,
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/prices_upload/price_review/ajax_price_review.php",
			dataType: "text",//Тип возвращаемого значения
			data: "start="+is_start+"&end="+is_end+"&price_id=<?php echo $price_id; ?>&base_mark="+document.getElementById('base_mark').value+"&plus_minus="+document.getElementById('plus_minus').value+"&percent="+percent+"&prices="+encodeURIComponent(JSON.stringify(prices))+"&from="+start_from+"&items_per_time="+items_per_time,
			success: function(answer)
			{				
				var answer_ob = JSON.parse(answer);
				
				//Если некорректный парсинг ответа
				if( typeof answer_ob.status === "undefined" )
				{
					alert("Ошибка парсинга ответа сервера. Процесс прерван");
				}
				else
				{
					//Корректный парсинг ответа
					if(answer_ob.status == true)
					{
						//Индикация процесса (СЕРЕДИНА)
						var complete_value = 0;
						for( var i=0; i < process_complete_parts.length; i++)
						{
							if( process_complete_parts[i] == 0 )
							{
								complete_value = (i*100)/process_complete_parts.length;
								break;
							}
						}
						document.getElementById('progress_bar').setAttribute('aria-valuenow', complete_value);
						document.getElementById('progress_bar').setAttribute('style', 'width: '+complete_value+'%');
						document.getElementById('progress_text').innerHTML = 'Выполнено '+parseInt(complete_value)+'%';
						
						
						if( parseInt(process_complete_parts[ process_complete_parts.length-1 ]) == 1 )
						{
							//Индикация процесса (ЗАВЕРШЕНО)
							document.getElementById('progress_bar').setAttribute('aria-valuenow', '100');
							document.getElementById('progress_bar').setAttribute('style', 'width: 100%');
							document.getElementById('progress_text').innerHTML = 'Выполнено 100%';
							//Индикация процесса (РЕЗУЛЬТАТ)
							document.getElementById('items_count').innerHTML = answer_ob.result.items_count;
							document.getElementById('reviewed_yes').innerHTML = answer_ob.result.reviewed_yes;
							document.getElementById('reviewed_no').innerHTML = answer_ob.result.reviewed_no;
							document.getElementById('work_result').setAttribute('style', 'display:block;');
							document.getElementById('options_div').setAttribute('style', 'display:block;');
							document.getElementById('cancel_button_div').setAttribute('style', 'display:none;');
							
							was_complete = 1;
							return;
						}
						
						
						if( can_go_on == 1 )
						{
							send_request_for_one_part();
						}
					}
					else
					{
						alert(answer_ob.message + '. Процесс прерван');
					}
				}							
			}
		});
	}
	// -----------------------------------------------------------------------------------
	</script>
	<?php
}
?>