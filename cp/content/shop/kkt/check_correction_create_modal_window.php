<?php
//Скрипт с модальным окном для ручного создания чека КОРРЕКЦИИ. Подключается только на странице ЧЕКИ. К id элементов добавляется correction_, чтобы не конфликтовать с модальным окном для обычного чека.
defined('_ASTEXE_') or die('No access');









// Для некоторых систем предусмотренны разные интерфейсы настроек создания чека коррекции.
// Поэтому получаем основную кассу что бы поней определить выводимы интерфейс для пользователя

$query = $db_link->prepare("SELECT * FROM `shop_kkt_devices` WHERE `handler` != '' LIMIT 1");
$query->execute();
$kkt_devices = $query->fetch();
$kkt_devices = $kkt_devices['handler'];
?>

<?php
// Настройки параметров чека
$shop_kkt_default_setting_query = $db_link->prepare("SELECT * FROM `shop_kkt_default_setting` WHERE `type` = 2 LIMIT 1;");
$shop_kkt_default_setting_query->execute();
$check_default_options = $shop_kkt_default_setting_query->fetch();

if(empty($check_default_options)){
	//Настройки чека по умолчанию (настраиваются в корневом разделе по чекам)
	if( isset($_COOKIE["check_default_options"]) )
	{
		$check_default_options = json_decode($_COOKIE["check_default_options"], true);
	}
	else
	{
		$check_default_options = null;
	}
	//Если куки не записано, или прочитано некорректно через json_decode - задаем значения по умолчанию из БД
	if( $check_default_options == null )
	{
		//Получаем настройки по умолчанию из БД
		$check_default_options_query_SQL = "SELECT 
			(SELECT `value` FROM `shop_kkt_ref_tag_1055` ORDER BY `value` LIMIT 1) AS `taxationSystem`,
			(SELECT `id` FROM `shop_kkt_devices` ORDER BY `id` LIMIT 1) AS `kkt_device_id`,
			(SELECT `value` FROM `shop_kkt_ref_tag_1199` ORDER BY `value` LIMIT 1) AS `check_product_tax`,
			(SELECT `value` FROM `shop_kkt_ref_tag_1214` ORDER BY `value` LIMIT 1) AS `check_product_paymentMethodType`,
			(SELECT `value` FROM `shop_kkt_ref_tag_1212` ORDER BY `value` LIMIT 1) AS `check_product_paymentSubjectType`,
			(SELECT `value` FROM `shop_kkt_ref_payment_types_tags` ORDER BY `value` LIMIT 1) AS `check_payment_type`
		FROM shop_kkt_devices";
		
		$check_default_options_query = $db_link->prepare($check_default_options_query_SQL);
		$check_default_options_query->execute();
		$check_default_options = $check_default_options_query->fetch();
	}
}
?>

<div class="text-center m-b-md">
	<div class="modal fade" id="correction_modal_kkt_create_new_check" tabindex="-1" role="dialog"  aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="color-line"></div>
				<div class="modal-header">
					<h4 class="modal-title">Создание нового чека КОРРЕКЦИИ</h4>
				</div>
				<div class="modal-body">
					<div class="row text-center" id="correction_modal_body_row_loading" style="display:none;">
						<img src="/content/files/images/ajax-loader-transparent.gif" /><br>
						Пожалуйста, подождите...
					</div>
					<div class="row" id="correction_modal_body_row">
						
						<div class="col-md-6 form-horizontal" id="correction_div_check_widgets">
							
							<h5>1. Информация</h5>
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Тип операции</label>

								<div class="col-sm-8">
									<select onchange="correction_check_object_init();correction_preview_check();" class="form-control m-b" id="correction_tag_1054_select">
										<?php
										$ref_tag_1054_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1054` WHERE `value` IN (1,3) ORDER BY `value`;");
										$ref_tag_1054_query->execute();
										while( $ref_tag_1054 = $ref_tag_1054_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1054["value"]; ?>"><?php echo $ref_tag_1054["for_print"]; ?> (коррекция)</option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Налогообложение</label>

								<div class="col-sm-8">
									<select onchange="correction_check_object_init();correction_preview_check();" class="form-control m-b" id="correction_tag_1055_select">
										<?php
										$ref_tag_1055_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1055` ORDER BY `value`;");
										$ref_tag_1055_query->execute();
										while( $ref_tag_1055 = $ref_tag_1055_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1055["value"]; ?>"><?php echo $ref_tag_1055["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Касса</label>

								<div class="col-sm-8">
									<select onchange="correction_check_object_init();correction_preview_check();" class="form-control m-b" id="correction_kkt_id_select">
										<?php
										$kkt_devices_query = $db_link->prepare("SELECT * FROM `shop_kkt_devices` ORDER BY `id`;");
										$kkt_devices_query->execute();
										while( $device = $kkt_devices_query->fetch() )
										{
											?>
											<option value="<?php echo $device["id"]; ?>"><?php echo $device["name"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Тип коррекции</label>

								<div class="col-sm-8">
									<select onchange="correction_check_object_init();correction_preview_check();" class="form-control m-b" id="correction_type">
										<?php
										$shop_kkt_ref_tag_1173_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1173` ORDER BY `value`;");
										$shop_kkt_ref_tag_1173_query->execute();
										while( $value = $shop_kkt_ref_tag_1173_query->fetch() )
										{
											?>
											<option value="<?php echo $value["value"]; ?>"><?php echo $value["description"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="correction_description">Описание коррекции</label>
								<div class="col-sm-8">
									<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_description" type="text" class="form-control" placeholder="Строка до 243 символов"/><button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Например: Отключение электричества');"><i class="fa fa-info"></i></button>
								</div>
							</div>
							
							
							
							
							
							<div class="form-group">
								<label for="" class="col-lg-4 control-label">
									Дата документа основания
								</label>
								<div class="col-lg-8">
									<div style="position:relative;height:34px;">
										<input style="position:absolute; z-index:2; opacity:0" type="text"  id="correction_causeDocumentDate" value="" class="form-control" />
										<input style="position:absolute; z-index:1;" type="text" id="correction_causeDocumentDate_show" class="form-control" />
										<script>
										//Инициализируем datetimepicker
										jQuery("#correction_causeDocumentDate").datetimepicker({
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
												//time_string += date_ob.getHours()+":"+date_ob.getMinutes();
												document.getElementById("correction_causeDocumentDate_show").value = time_string;//Показываем время в понятном виде
												
												correction_check_object_init();
												correction_preview_check();
											}
										});
										</script>
									</div>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="correction_causeDocumentNumber">Номер документа основания</label>
								
								<div class="col-sm-8">
									<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_causeDocumentNumber" type="text" class="form-control" placeholder="Строка до 32 символов"/><button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Введите дату и номер документа основания для формирования чека коррекции. Это может быть предписание ФНС или внутренний приказ\акт на формирование чека коррекции.');"><i class="fa fa-info"></i></button>
								</div>
							</div>
							
							
							
							
							
							
							
							
							
							
							<?php
							switch($kkt_devices){
								case 'komtet' :
								?>
								<div class="hr-line-dashed"></div>
								
								<h5>2. Сумма по чеку</h5>
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_totalSum">Сумма расчета по чеку</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_totalSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_payment_types_tag_select">Тип платежа</label>
									<div class="col-sm-8">
										<select class="form-control m-b" id="correction_payment_types_tag_select">
											<?php
											$ref_payment_types_tags_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_payment_types_tags` ORDER BY `value`;");
											$ref_payment_types_tags_query->execute();
											while( $ref_payment_type_tag = $ref_payment_types_tags_query->fetch() )
											{
												$selected = '';
												if($check_default_options['check_payment_type'] == $ref_payment_type_tag["value"]){
													$selected = 'selected';
												}
												?>
												<option <?=$selected;?> value="<?php echo $ref_payment_type_tag["value"]; ?>"><?php echo $ref_payment_type_tag["for_print"]; ?></option>
												<?php
											}
											?>
										</select>
									</div>
								</div>
								
								<div class="hr-line-dashed"></div>
								
								<h5>3. Ставка НДС</h5>
								
								<select class="form-control m-b" id="correction_tag_1199_select">
									<?php
									$ref_tag_1199_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1199` ORDER BY `value`;");
									$ref_tag_1199_query->execute();
									while( $ref_tag_1199 = $ref_tag_1199_query->fetch() )
									{
										$selected = '';
										if($check_default_options['check_product_tax'] == $ref_tag_1199["value"]){
											$selected = 'selected';
										}
										?>
										<option <?=$selected;?> value="<?php echo $ref_tag_1199["value"]; ?>"><?php echo $ref_tag_1199["for_print"]; ?></option>
										<?php
									}
									?>
								</select>
								
								<div class="hr-line-dashed"></div>
								
								<h5>4. Серийный номер кассы (принтера)</h5>
								<?php
										$kkt_devices_query = $db_link->prepare("SELECT * FROM `shop_kkt_devices` WHERE `handler` = 'komtet';");
										$kkt_devices_query->execute();
										$device = $kkt_devices_query->fetch();
										$handler_options_values = json_decode($device['handler_options_values'], true);
								?>
								<input id="printer_number" type="text" class="form-control" value="<?=$handler_options_values['kassa_serial_number'];?>"/>
								
								<?php
								break;
								default :
								?>
								<div class="hr-line-dashed"></div>
								
								<h5>2. Сумма по чеку</h5>
								
								
								<div class="form-group" style="background: #eee; padding-top: 10px;">
									<label class="col-sm-4 control-label" for="correction_totalSum">Сумма расчета по чеку</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_totalSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								<div class="hr-line-dashed"></div>
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_cashSum">Сумма по чеку наличными</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_cashSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_eCashSum">Сумма по чеку безналичными</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_eCashSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_prepaymentSum">Сумма по чеку предоплатой</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_prepaymentSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_postpaymentSum">Сумма по чеку постоплатой</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_postpaymentSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_otherPaymentTypeSum">Сумма по чеку встречным предоставлением</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_otherPaymentTypeSum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								<div class="hr-line-dashed"></div>
								
								<h5>3. НДС в чеке</h5>
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_tax1Sum">Сумма НДС в чеке по ставке 20%</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax1Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_tax2Sum">Сумма НДС в чеке по ставке 10%</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax2Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_tax3Sum">Сумма НДС в чеке по ставке 0%</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax3Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group" style="background: #eee; padding-top: 10px;">
									<label class="col-sm-4 control-label" for="correction_tax4Sum">Сумма по чеку без НДС</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax4Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_tax5Sum">Сумма НДС в чеке по ставке 20/120</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax5Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								
								
								
								<div class="form-group">
									<label class="col-sm-4 control-label" for="correction_tax6Sum">Сумма НДС в чеке по ставке 10/110</label>
									<div class="col-sm-8">
										<input onkeyup="correction_check_object_init();correction_preview_check();" id="correction_tax6Sum" type="text" class="form-control" placeholder=""/>
									</div>
								</div>
								<?php
								break;
							}
							?>
							
							
							
							
							
							
							
							
							

							
						</div>
						
						<div class="col-md-6" id="correction_div_check_preview">
							
							
							
						</div>
						
						
					</div>
				</div>
				<div class="modal-footer" id="correction_modal_kkt_create_new_check_footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
					
					<button type="button" class="btn btn-success" onclick="correction_create_check();"><i class="fas fa-receipt"></i> Создать чек</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
// ----------------------------------------------------------------------------
var correction_check_object = "";//Переменная для хранения объекта создаваемого чека КОРРЕКЦИИ.
var correction_checks_created_count = 0;//Количество чеков, созданных окном
var check_default_options = JSON.parse('<?php echo json_encode($check_default_options); ?>');//Настройки для чека по умолчанию
// ----------------------------------------------------------------------------
//Функция проверки значения денежных полей
function money_value_is_correct(value, caption)
{
	if( isNaN(value) )
	{
		alert("Некорректное значение поля \""+caption+"\" (требуется число)");
		return false;
	}
	
	return true;
}
// ----------------------------------------------------------------------------
//Функция создания чека КОРРЕКЦИИ (создание в базе и отправка на реальный ККТ, если подключен)
function correction_create_check()
{
	//Сначала проверяем данные объекта.
	//1. Должен быть документ (номер и дата) основания для формирования чека коррекции. Это может быть предписание ФНС или внутренний приказ\акт на формирование чека коррекции.
	if( document.getElementById("correction_causeDocumentDate_show").value == "" )
	{
		alert("Не указана дата документа основания");
		return;
	}
	
	if( document.getElementById("correction_causeDocumentNumber").value == "" )
	{
		alert("Не указан номер документа основания");
		return;
	}
	
	if( document.getElementById("correction_description").value == "" )
	{
		alert("Не указано описание, на основе чего производится коррекция");
		return;
	}
	
	
	
<?php
switch($kkt_devices){
case 'komtet' :
?>
	
	
	//2. Все показатели сумм по чеку
	if( ! money_value_is_correct(correction_check_object.correction_totalSum, "Сумма расчета по чеку") )
	{
		return;
	}
	
<?php
break;
default :
?>	
	
	//2. Все показатели сумм по чеку
	if( ! money_value_is_correct(correction_check_object.correction_totalSum, "Сумма расчета по чеку") || !money_value_is_correct(correction_check_object.correction_cashSum, "Сумма по чеку наличными") || !money_value_is_correct(correction_check_object.correction_eCashSum, "Сумма по чеку безналичными") || !money_value_is_correct(correction_check_object.correction_prepaymentSum, "Сумма по чеку предоплатой") || !money_value_is_correct(correction_check_object.correction_postpaymentSum, "Сумма по чеку постоплатой") || !money_value_is_correct(correction_check_object.correction_otherPaymentTypeSum, "Сумма по чеку встречным предоставлением") || !money_value_is_correct(correction_check_object.correction_tax1Sum, "Сумма НДС в чеке по ставке 20%") || !money_value_is_correct(correction_check_object.correction_tax2Sum, "Сумма НДС в чеке по ставке 10%") || !money_value_is_correct(correction_check_object.correction_tax3Sum, "Сумма НДС в чеке по ставке 0%") || !money_value_is_correct(correction_check_object.correction_tax4Sum, "Сумма по чеку без НДС") || !money_value_is_correct(correction_check_object.correction_tax5Sum, "Сумма НДС в чеке по ставке 20/120") || !money_value_is_correct(correction_check_object.correction_tax6Sum, "Сумма НДС в чеке по ставке 10/110") )
	{
		return;
	}
	
	//3. Равенство сумм платежей сумме по чеку
	if( correction_check_object.correction_totalSum != (correction_check_object.correction_cashSum+correction_check_object.correction_eCashSum+correction_check_object.correction_prepaymentSum+correction_check_object.correction_postpaymentSum+correction_check_object.correction_otherPaymentTypeSum) )
	{
		alert("Сумма платежей по всем способам не равна общей сумме по чеку");
		return;
	}
	
<?php
break;
}
?>
	
	console.log(correction_check_object);
	//return;
	
	jQuery.ajax({
		type: "POST",
		async: true,
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/kkt/ajax_create_check.php",
		dataType: "text",
		data: "check_object=" + encodeURIComponent(JSON.stringify(correction_check_object)),
		beforeSend: function()
		{
			//Скрываем виджеты чека
			document.getElementById("correction_modal_body_row").setAttribute("style", "display:none;");
			document.getElementById("correction_modal_kkt_create_new_check_footer").setAttribute("style", "display:none;");
			document.getElementById("correction_modal_body_row_loading").setAttribute("style", "");
		},
		success: function(answer)
		{
			console.log(answer);
			var answer_ob = JSON.parse(answer);
			
			if( typeof answer_ob.status === "undefined" )
			{
				alert("Ошибка выполнения скрипта создания чека");
				
				//Показываем виджеты чека
				document.getElementById("correction_modal_body_row").setAttribute("style", "");
				document.getElementById("correction_modal_kkt_create_new_check_footer").setAttribute("style", "");
				document.getElementById("correction_modal_body_row_loading").setAttribute("style", "display:none;");
			}
			else
			{
				if(answer_ob.status == true)
				{
					alert("Чек успешно создан. Вы можете создать еще чек");
					
					//Показываем виджеты чека
					document.getElementById("correction_modal_body_row").setAttribute("style", "");
					document.getElementById("correction_modal_kkt_create_new_check_footer").setAttribute("style", "");
					document.getElementById("correction_modal_body_row_loading").setAttribute("style", "display:none;");
					
					correction_init_modal_kkt_create_new_check();//Переинициализация окна
					
					correction_checks_created_count++;
				}
				else
				{
					alert(answer_ob.message);
					
					//Показываем виджеты чека
					document.getElementById("correction_modal_body_row").setAttribute("style", "");
					document.getElementById("correction_modal_kkt_create_new_check_footer").setAttribute("style", "");
					document.getElementById("correction_modal_body_row_loading").setAttribute("style", "display:none;");
				}
			}
		}
	});
	
}
// ----------------------------------------------------------------------------
//Инициализация модального окна для создания чека КОРРЕКЦИИ (при нажатии кнопки "Новый чек коррекциии")
function correction_init_modal_kkt_create_new_check()
{
	//Полный сброс переменной для создаваемого чека КОРРЕКЦИИ
	correction_check_object = new Object;
	correction_check_object.check_type = 'correction';//Чек коррекциии
	
	
	//Перед открытием модального окна - выставляем все его виджеты в исходное состояние
	//1. Тип чека (Приход/расход и т.д.)
	document.getElementById("correction_tag_1054_select").selectedIndex = 0;
	//2. Налогообложение
	document.getElementById("correction_tag_1055_select").value = <?php echo $check_default_options["taxationSystem"]; ?>;
	//3. Выбор ККТ
	document.getElementById("correction_kkt_id_select").value = <?php echo $check_default_options["kkt_device_id"]; ?>;
	//4. Тип коррекции
	document.getElementById("correction_type").selectedIndex = 0;
	//5. Описание коррекции
	document.getElementById("correction_description").value = "";
	//6. Дата документа основания
	document.getElementById("correction_causeDocumentDate").value = "";
	document.getElementById("correction_causeDocumentDate_show").value = "";
	//7. Номер документа основания
	document.getElementById("correction_causeDocumentNumber").value = "";
	
	//8. Сумма расчета по чеку
	document.getElementById("correction_totalSum").value = "0";
	
	
	
	
<?php
switch($kkt_devices){
case 'komtet' :
?>
	
	
	
	
	
<?php
break;
default :
?>
	
	
	//9. Сумма по чеку наличными
	document.getElementById("correction_cashSum").value = "0";
	
	//10. Сумма по чеку безналичными
	document.getElementById("correction_eCashSum").value = "0";
	
	//11. Сумма по чеку предоплатой
	document.getElementById("correction_prepaymentSum").value = "0";
	
	//12. Сумма по чеку постоплатой
	document.getElementById("correction_postpaymentSum").value = "0";
	
	//13. Сумма по чеку встречным предоставлением
	document.getElementById("correction_otherPaymentTypeSum").value = "0";
	
	
	//14. Сумма НДС в чеке по ставке 20%
	document.getElementById("correction_tax1Sum").value = "0";
	
	//15. Сумма НДС в чеке по ставке 10%
	document.getElementById("correction_tax2Sum").value = "0";
	
	//16. Сумма НДС в чеке по ставке 0%
	document.getElementById("correction_tax3Sum").value = "0";
	
	//17. Сумма по чеку без НДС
	document.getElementById("correction_tax4Sum").value = "0";
	
	//18. Сумма НДС в чеке по ставке 20/120
	document.getElementById("correction_tax5Sum").value = "0";
	
	//19. Сумма НДС в чеке по ставке 10/110
	document.getElementById("correction_tax6Sum").value = "0";
	
	
<?php
break;
}
?>
	
	
	
	//Инициализация объекта чека
	correction_check_object_init();
	
	//Предпросмотр чека
	correction_preview_check();
	
	
	//ОТКРЫВАЕМ ОКНО
	jQuery('#correction_modal_kkt_create_new_check').modal();
}
// ----------------------------------------------------------------------------
//Инициализация объекта чека КОРРЕКЦИИ
function correction_check_object_init()
{
	//Тип операции
	correction_check_object.tag_1054 = new Object;
	correction_check_object.tag_1054.value = document.getElementById("correction_tag_1054_select").value;
	correction_check_object.tag_1054.for_print = document.getElementById("correction_tag_1054_select").options[document.getElementById("correction_tag_1054_select").selectedIndex].text;
	
	//Налогообложение
	correction_check_object.tag_1055 = new Object;
	correction_check_object.tag_1055.value = document.getElementById("correction_tag_1055_select").value;
	correction_check_object.tag_1055.for_print = document.getElementById("correction_tag_1055_select").options[document.getElementById("correction_tag_1055_select").selectedIndex].text;
	
	//Касса
	correction_check_object.kkt = new Object;
	correction_check_object.kkt.id = document.getElementById("correction_kkt_id_select").value;
	correction_check_object.kkt.for_print = document.getElementById("correction_kkt_id_select").options[document.getElementById("correction_kkt_id_select").selectedIndex].text;
	
	
	//4. Тип коррекции
	correction_check_object.correction_type = new Object;
	correction_check_object.correction_type.value = document.getElementById("correction_type").value;
	correction_check_object.correction_type.for_print = document.getElementById("correction_type").options[document.getElementById("correction_type").selectedIndex].text;
	
	
	//5. Описание коррекции
	correction_check_object.correction_description = document.getElementById("correction_description").value;
	
	//6. Дата документа основания
	correction_check_object.correction_causeDocumentDate = new Object;correction_check_object.correction_causeDocumentDate.value = document.getElementById("correction_causeDocumentDate").value
	correction_check_object.correction_causeDocumentDate.for_print = document.getElementById("correction_causeDocumentDate_show").value;
	
	//7. Номер документа основания
	correction_check_object.correction_causeDocumentNumber = document.getElementById("correction_causeDocumentNumber").value;
	
	//8. Сумма расчета по чеку
	correction_check_object.correction_totalSum = parseFloat(document.getElementById("correction_totalSum").value);

	
	
	
	
	
<?php
switch($kkt_devices){
case 'komtet' :
?>
	
	// Тип платежа
	correction_check_object.payment = new Object;
	correction_check_object.payment.id = document.getElementById("correction_payment_types_tag_select").value;
	correction_check_object.payment.for_print = document.getElementById("correction_payment_types_tag_select").options[document.getElementById("correction_payment_types_tag_select").selectedIndex].text;
	
	// Ставка НДС
	correction_check_object.tag_1199 = new Object;
	correction_check_object.tag_1199.id = document.getElementById("correction_tag_1199_select").value;
	correction_check_object.tag_1199.for_print = document.getElementById("correction_tag_1199_select").options[document.getElementById("correction_tag_1199_select").selectedIndex].text;
	
	// Серийный номер кассы (принтера)
	correction_check_object.printer_number = document.getElementById("printer_number").value;
	
<?php
break;
default :
?>	
	
	
	//9. Сумма по чеку наличными
	correction_check_object.correction_cashSum = parseFloat(document.getElementById("correction_cashSum").value);
	
	//10. Сумма по чеку безналичными
	correction_check_object.correction_eCashSum = parseFloat(document.getElementById("correction_eCashSum").value);
	
	//11. Сумма по чеку предоплатой
	correction_check_object.correction_prepaymentSum = parseFloat(document.getElementById("correction_prepaymentSum").value);
	
	//12. Сумма по чеку постоплатой
	correction_check_object.correction_postpaymentSum = parseFloat(document.getElementById("correction_postpaymentSum").value);
	
	//13. Сумма по чеку встречным предоставлением
	correction_check_object.correction_otherPaymentTypeSum = parseFloat(document.getElementById("correction_otherPaymentTypeSum").value);
	
	
	//14. Сумма НДС в чеке по ставке 20%
	correction_check_object.correction_tax1Sum = parseFloat(document.getElementById("correction_tax1Sum").value);
	
	//15. Сумма НДС в чеке по ставке 10%
	correction_check_object.correction_tax2Sum = parseFloat(document.getElementById("correction_tax2Sum").value);
	
	//16. Сумма НДС в чеке по ставке 0%
	correction_check_object.correction_tax3Sum = parseFloat(document.getElementById("correction_tax3Sum").value);
	
	//17. Сумма по чеку без НДС
	correction_check_object.correction_tax4Sum = parseFloat(document.getElementById("correction_tax4Sum").value);
	
	//18. Сумма НДС в чеке по ставке 20/120
	correction_check_object.correction_tax5Sum = parseFloat(document.getElementById("correction_tax5Sum").value);
	
	//19. Сумма НДС в чеке по ставке 10/110
	correction_check_object.correction_tax6Sum = parseFloat(document.getElementById("correction_tax6Sum").value);
	
	
<?php
break;
}
?>

}
// ----------------------------------------------------------------------------
//Функция предпросмотра чека КОРРЕКЦИИ
function correction_preview_check()
{
	var div_check_preview = document.getElementById("correction_div_check_preview");
	
	var check_preview_html = "<div class=\"text-center\"><?php echo date("d.m.Y",time()); ?></div>";
	check_preview_html += "<div class=\"text-center\">Чек №__ Смена №__</div>";
	
	//Тип чека (Приход/Расход и т.д.)
	check_preview_html += "<div class=\"text-center\" style=\"border-bottom:1px solid #e5e5e5;\"><b>"+correction_check_object.tag_1054.for_print+"</b></div>";
	
	
	
	check_preview_html += "<div>";
	check_preview_html += "<div style=\"font-size:18px;\" class=\"\"><b>Сумма расчета по чеку:</b> "+correction_check_object.correction_totalSum.toFixed(2)+"</div>";
	
	
<?php
switch($kkt_devices){
case 'komtet' :
?>
	
	
	
	
	
<?php
break;
default :
?>		


	check_preview_html += "<div class=\"\"><b>Сумма по чеку наличными:</b> "+correction_check_object.correction_cashSum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма по чеку безналичными:</b> "+correction_check_object.correction_eCashSum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма по чеку предоплатой:</b> "+correction_check_object.correction_prepaymentSum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма по чеку постоплатой:</b> "+correction_check_object.correction_postpaymentSum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма по чеку встречным предоставлением:</b> "+correction_check_object.correction_otherPaymentTypeSum.toFixed(2)+"</div></div>";
	
	
	
	check_preview_html += "<div><div class=\"text-center\" style=\"border-bottom:1px dotted #e5e5e5;margin-top:10px;\"></div>";
	check_preview_html += "<div class=\"\"><b>Сумма НДС в чеке по ставке 20%:</b> "+correction_check_object.correction_tax1Sum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма НДС в чеке по ставке 10%:</b> "+correction_check_object.correction_tax2Sum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма НДС в чеке по ставке 0%:</b> "+correction_check_object.correction_tax3Sum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма по чеку без НДС:</b> "+correction_check_object.correction_tax4Sum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма НДС в чеке по ставке 20/120:</b> "+correction_check_object.correction_tax5Sum.toFixed(2)+"</div>";
	check_preview_html += "<div class=\"\"><b>Сумма НДС в чеке по ставке 10/110:</b> "+correction_check_object.correction_tax6Sum.toFixed(2)+"</div></div>";


<?php
break;
}
?>	
	
	
	check_preview_html += "<div><div class=\"text-center\" style=\"border-bottom:1px dotted #e5e5e5;margin-top:10px;\"></div>";
	check_preview_html += "<div class=\"\">Вид налогообложения: "+correction_check_object.tag_1055.for_print+"</div>";
	check_preview_html += "<div class=\"\">Будет использована касса: "+correction_check_object.kkt.for_print+" (ID "+correction_check_object.kkt.id+")</div>";
	
	check_preview_html += "<div class=\"\">Тип коррекции: "+correction_check_object.correction_type.for_print+"</div>";
	
	check_preview_html += "<div class=\"\">Описание коррекции: "+correction_check_object.correction_description+"</div>";
	
	check_preview_html += "<div class=\"\">Дата документа основания: "+correction_check_object.correction_causeDocumentDate.for_print+"</div>";
	
	check_preview_html += "<div class=\"\">Номер документа основания: "+correction_check_object.correction_causeDocumentNumber+"</div></div>";
	
	div_check_preview.innerHTML = check_preview_html;
}
// ----------------------------------------------------------------------------
</script>












<script>
	jQuery( window ).load(function() {
		//Если были созданы чеки, то, после закрытия окна - перезагружаем страницу (чтобы чеки отобразились, например в таблице чеков)
		$("#correction_modal_kkt_create_new_check").on('hidden.bs.modal', function(){
			if( correction_checks_created_count > 0 )
			{
				location = location;
			}
		});
		
	});
</script>