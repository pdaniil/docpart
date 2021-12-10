<?php
//Скрипт с модальным окном для ручного создания чека. Подключается только в desktop.php
defined('_ASTEXE_') or die('No access');
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
		/*$check_default_options = array("taxationSystem"=>"0", "kkt_device_id"=>"", "check_product_tax"=>"1", "check_product_paymentMethodType"=>"1", "check_product_paymentSubjectType"=>"1", "check_payment_type"=>"1");*/
	}
}
?>

<div class="text-center m-b-md">
	<div class="modal fade" id="modal_kkt_create_new_check" tabindex="-1" role="dialog"  aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="color-line"></div>
				<div class="modal-header">
					<h4 class="modal-title">Создание нового чека</h4>
				</div>
				<div class="modal-body">
					<div class="row text-center" id="modal_body_row_loading" style="display:none;">
						<img src="/content/files/images/ajax-loader-transparent.gif" /><br>
						Пожалуйста, подождите...
					</div>
					<div class="row" id="modal_body_row">
						
						<div class="col-md-6 form-horizontal" id="div_check_widgets">
							
							<h5>1. Информация</h5>
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Тип чека</label>

								<div class="col-sm-8">
									<select onchange="check_object_init_general_info();preview_check();" class="form-control m-b" id="tag_1054_select">
										<?php
										$ref_tag_1054_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1054` ORDER BY `value`;");
										$ref_tag_1054_query->execute();
										while( $ref_tag_1054 = $ref_tag_1054_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1054["value"]; ?>"><?php echo $ref_tag_1054["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label">Налогообложение</label>

								<div class="col-sm-8">
									<select onchange="check_object_init_general_info();preview_check();" class="form-control m-b" id="tag_1055_select">
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
									<select onchange="check_object_init_general_info();preview_check();" class="form-control m-b" id="kkt_id_select">
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
								<label class="col-sm-4 control-label" for="customer_contact_input">Контакт покупателя</label>
								<div class="col-sm-8">
								
									<?php
									$customer_contact = '';
									if($customer_id > 0){
										// Клиент зарегистрирован
										$main_field_query = $db_link->prepare("SELECT `email`, `phone` FROM `users` WHERE `user_id` = ".$customer_id);
										$main_field_query->execute();
										$main_field_record = $main_field_query->fetch();
										
										$main_field = '';
										if(!empty($main_field_record["email"])){
											$main_field = trim($main_field_record["email"]);
										}else{
											if(!empty($main_field_record["phone"])){
												$main_field = trim($main_field_record["phone"]);
											}
										}
										
										if(!empty($main_field)){
											if(strpos($main_field, '@') === false){
												$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $main_field);
												if(strlen($phone) == 11){
													$phone = substr($phone, 1);
												}
												$customer_contact = '+7'.$phone;
											}else{
												$customer_contact = $main_field;
											}
										}
									}else{
										$order_id_tmp = (int) trim($order_id);
										$order_data_query = $db_link->prepare("SELECT `how_get_json`, `phone_not_auth`, `email_not_auth` FROM `shop_orders` WHERE `id` = $order_id_tmp;");
										$order_data_query->execute();
										$order_data_record = $order_data_query->fetch();
										
										$notify_settings = '';
										if(!empty($order_data_record["email_not_auth"])){
											$notify_settings = trim($order_data_record["email_not_auth"]);
										}else{
											if(!empty($order_data_record["phone_not_auth"])){
												$notify_settings = trim($order_data_record["phone_not_auth"]);
											}
										}
										
										if(!empty($notify_settings)){
											if(strpos($notify_settings, '@') === false){
												$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $notify_settings);
												if(strlen($phone) == 11){
													$phone = substr($phone, 1);
												}
												$customer_contact = '+7'.$phone;
											}else{
												$customer_contact = $notify_settings;
											}
										}else{
											$how_get_json = json_decode($order_data_record["how_get_json"], true);
											if(!empty($how_get_json['phone_not_auth'])){
												$phone = $how_get_json['phone_not_auth'];
												$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $phone);
												if(strlen($phone) == 11){
													$phone = substr($phone, 1);
												}
												$customer_contact = '+7'.$phone;
											}else{
												if(!empty($how_get_json['phone'])){
													$phone = $how_get_json['phone'];
													$phone = str_replace(array(' ', '+7', '(', ')', '-', '_'), '', $phone);
													if(strlen($phone) == 11){
														$phone = substr($phone, 1);
													}
													$customer_contact = '+7'.$phone;
												}
											}
										}
									}
									?>
								
									<input onkeyup="check_object_init_general_info();preview_check();" id="customer_contact_input" type="text" class="form-control" placeholder="Укажите email или телефон" value="<?=$customer_contact;?>"/>
								</div>
							</div>
							
							
							
							
							<div class="hr-line-dashed"></div>
							
							
							
							<h5>2. Добавление товара</h5>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="product_name_input">Наименование</label>
								<div class="col-sm-8">
									<input id="product_name_input" type="text" class="form-control" placeholder=""/>
								</div>
							</div>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="product_price_input">Цена за ед.</label>
								<div class="col-sm-8">
									<input id="product_price_input" type="text" class="form-control" placeholder=""/>
								</div>
							</div>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="product_count_input">Количество</label>
								<div class="col-sm-8">
									<input id="product_count_input" type="text" class="form-control" placeholder=""/>
								</div>
							</div>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="tag_1199_select">Ставка НДС</label>
								<div class="col-sm-8">
									<select class="form-control m-b" id="tag_1199_select">
										<?php
										$ref_tag_1199_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1199` ORDER BY `value`;");
										$ref_tag_1199_query->execute();
										while( $ref_tag_1199 = $ref_tag_1199_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1199["value"]; ?>"><?php echo $ref_tag_1199["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="tag_1214_select">Платежный метод</label>
								<div class="col-sm-8">
									<select class="form-control m-b" id="tag_1214_select">
										<?php
										$ref_tag_1214_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1214` ORDER BY `value`;");
										$ref_tag_1214_query->execute();
										while( $ref_tag_1214 = $ref_tag_1214_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1214["value"]; ?>"><?php echo $ref_tag_1214["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="tag_1212_select">Предмет расчета</label>

								<div class="col-sm-8">
									<select class="form-control m-b" id="tag_1212_select">
										<?php
										$ref_tag_1212_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1212` ORDER BY `value`;");
										$ref_tag_1212_query->execute();
										while( $ref_tag_1212 = $ref_tag_1212_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_tag_1212["value"]; ?>"><?php echo $ref_tag_1212["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							<div class="form-group">
								<div class="col-sm-12">
									<button class="btn btn-primary" onclick="add_product_to_check();">Добавить в чек</button>
								</div>
							</div>
							
							
							<div class="hr-line-dashed"></div>
							
							
							<h5>3. Добавление оплаты</h5>
							
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="payment_types_tag_select">Тип платежа</label>
								<div class="col-sm-8">
									<select class="form-control m-b" id="payment_types_tag_select">
										<?php
										$ref_payment_types_tags_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_payment_types_tags` ORDER BY `value`;");
										$ref_payment_types_tags_query->execute();
										while( $ref_payment_type_tag = $ref_payment_types_tags_query->fetch() )
										{
											?>
											<option value="<?php echo $ref_payment_type_tag["value"]; ?>"><?php echo $ref_payment_type_tag["for_print"]; ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							
							
							<div class="form-group">
								<label class="col-sm-4 control-label" for="payment_amount">Сумма</label>
								<div class="col-sm-8">
									<input id="payment_amount" type="text" class="form-control" placeholder=""/>
								</div>
							</div>
							
							
							<div class="form-group">
								<div class="col-sm-12">
									<button class="btn btn-primary" onclick="add_payment_to_check();">Добавить в чек</button>
								</div>
							</div>
							
						</div>
						
						<div class="col-md-6" id="div_check_preview">
							
							
							
						</div>
						
						
					</div>
				</div>
				<div class="modal-footer" id="modal_kkt_create_new_check_footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
					
					<button type="button" class="btn btn-success" onclick="create_check();"><i class="fas fa-receipt"></i> Создать чек</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
// ----------------------------------------------------------------------------
var check_object = "";//Переменная для хранения объекта создаваемого чека.
var checks_created_count = 0;//Количество чеков, созданных окном
var check_default_options = JSON.parse('<?php echo json_encode($check_default_options); ?>');//Настройки для чека по умолчанию (может использоваться при заполнении чека на основе позиций заказов)
// ----------------------------------------------------------------------------
//Функция создания чека (создание в базе и отправка на реальный ККТ, если подключен)
function create_check()
{
	//Сначала проверяем данные объекта.
	if( check_object.customer_contact == "" )
	{
		if( !confirm("Не указан контакт покупателя. Всё-равно создать чек?") )
		{
			return;
		}
	}
	
	
	//Содержимое товарных позиций и платежей не проверяем, т.к. все это проверяется на этапе добавления/редактирования. Достаточно только проверить наличие позиций, платежей и равенство сумм по позициям и платежам
	if( check_object.products.length == 0 )
	{
		alert("В чек не добавлена ни одна товарная позиция");
		return;
	}
	if( check_object.payments.length == 0 )
	{
		alert("В чек не добавлен ни один платеж");
		return;
	}
	//Равенство сумм по позициям и платежам
	var products_sum = 0;
	var payments_sum = 0;
	for(var i=0; i < check_object.products.length; i++)
	{
		products_sum = products_sum + (check_object.products[i].price * check_object.products[i].count);
	}
	for(var i=0; i < check_object.payments.length; i++)
	{
		payments_sum = payments_sum + parseFloat(check_object.payments[i].amount);
	}
	if( products_sum != payments_sum )
	{
		alert("Сумма по позициям не равна сумме по платежам "+products_sum+" - "+payments_sum);
		return;
	}
	
	
	
	
	
	jQuery.ajax({
		type: "POST",
		async: true,
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/kkt/ajax_create_check.php",
		dataType: "text",
		data: "check_object=" + encodeURIComponent(JSON.stringify(check_object)),
		beforeSend: function()
		{
			//Скрываем виджеты чека
			document.getElementById("modal_body_row").setAttribute("style", "display:none;");
			document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "display:none;");
			document.getElementById("modal_body_row_loading").setAttribute("style", "");
		},
		success: function(answer)
		{
			console.log(answer);
			var answer_ob = JSON.parse(answer);
			
			if( typeof answer_ob.status === "undefined" )
			{
				alert("Ошибка выполнения скрипта создания чека");
				
				//Показываем виджеты чека
				document.getElementById("modal_body_row").setAttribute("style", "");
				document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
				document.getElementById("modal_body_row_loading").setAttribute("style", "display:none;");
			}
			else
			{
				if(answer_ob.status == true)
				{
					alert("Чек успешно создан. Вы можете создать еще чек");
					
					//Показываем виджеты чека
					document.getElementById("modal_body_row").setAttribute("style", "");
					document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
					document.getElementById("modal_body_row_loading").setAttribute("style", "display:none;");
					
					init_modal_kkt_create_new_check();//Переинициализация окна
					
					checks_created_count++;
				}
				else
				{
					alert(answer_ob.message);
					
					//Показываем виджеты чека
					document.getElementById("modal_body_row").setAttribute("style", "");
					document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
					document.getElementById("modal_body_row_loading").setAttribute("style", "display:none;");
				}
			}
		}
	});
}
// ----------------------------------------------------------------------------
/*
//Отмена создания чека (просто закрыть окно)
function cancel_check()
{
	
}
*/
// ----------------------------------------------------------------------------
var name_default = "";
var price_default = "";
var count_default = "";
//Инициализация модального окна для создания чека (при нажатии кнопки "Новый чек")
function init_modal_kkt_create_new_check()
{
	//Полный сброс переменной для создаваемого чека
	check_object = new Object;
	check_object.check_type = 'usual';//Обычный чек (т.е. не чек коррекции)
	check_object.products = new Array();
	check_object.payments = new Array();
	
	
	//Перед открытие модального окна - выставляем все его виджеты в исходное состояние
	//1. Тип чека (Приход/расход и т.д.)
	document.getElementById("tag_1054_select").selectedIndex = 0;
	//2. Налогообложение
	document.getElementById("tag_1055_select").value = <?php echo $check_default_options["taxationSystem"]; ?>;
	//3. Выбор ККТ
	document.getElementById("kkt_id_select").value = <?php echo $check_default_options["kkt_device_id"]; ?>;
	//4. Контакт клиента
	//document.getElementById("customer_contact_input").value = "";
	
	//5. Наименование товара
	document.getElementById("product_name_input").value = name_default;
	//6. Цена за единицу
	document.getElementById("product_price_input").value = price_default;
	//7. Количество товара
	document.getElementById("product_count_input").value = count_default;
	//8. Ставка НДС
	document.getElementById("tag_1199_select").value = <?php echo $check_default_options["check_product_tax"]; ?>;
	//9. Платежный метод
	document.getElementById("tag_1214_select").value = <?php echo $check_default_options["check_product_paymentMethodType"]; ?>;
	//10.Предмет расчета
	document.getElementById("tag_1212_select").value = <?php echo $check_default_options["check_product_paymentSubjectType"]; ?>;
	
	//11.Тип платежа
	document.getElementById("payment_types_tag_select").value = <?php echo $check_default_options["check_payment_type"]; ?>;
	//12. Сумма
	document.getElementById("payment_amount").value = "";
	
	
	
	//Инициализация объекта чека общими настройками (т.е. кроме товаров и платежей)
	check_object_init_general_info();
	
	//Предпросмотр чека
	preview_check();
	
	
	//ОТКРЫВАЕМ ОКНО
	jQuery('#modal_kkt_create_new_check').modal();
	
	
	//Делаем активной область добавления данных в чек и footer окна (если к примеру пользователь закрыл окно находясь в режиме редактирования)
	document.getElementById("div_check_widgets").setAttribute("style", "");
	document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
}
// ----------------------------------------------------------------------------
//Инициализация объекта чека (Кроме товаров и платежей)
function check_object_init_general_info()
{
	//Тип чека
	check_object.tag_1054 = new Object;
	check_object.tag_1054.value = document.getElementById("tag_1054_select").value;
	check_object.tag_1054.for_print = document.getElementById("tag_1054_select").options[document.getElementById("tag_1054_select").selectedIndex].text;
	
	//Налогообложение
	check_object.tag_1055 = new Object;
	check_object.tag_1055.value = document.getElementById("tag_1055_select").value;
	check_object.tag_1055.for_print = document.getElementById("tag_1055_select").options[document.getElementById("tag_1055_select").selectedIndex].text;
	
	//Касса
	check_object.kkt = new Object;
	check_object.kkt.id = document.getElementById("kkt_id_select").value;
	check_object.kkt.for_print = document.getElementById("kkt_id_select").options[document.getElementById("kkt_id_select").selectedIndex].text;
	
	//Контакт покупателя
	check_object.customer_contact = document.getElementById("customer_contact_input").value;
}
// ----------------------------------------------------------------------------
//Добавить товар (предмет расчета) в чек
var product_next_local_id = 1;
function add_product_to_check()
{
	//Формируем объект товара и добавляем его в check_object
	var product = new Object;
	product.local_id = product_next_local_id;
	product.name = document.getElementById("product_name_input").value;//Наименование
	product.price = document.getElementById("product_price_input").value;//Цена
	product.count = document.getElementById("product_count_input").value;//Количество
	product.tag_1199 = document.getElementById("tag_1199_select").value;//Ставка НДС
	product.tag_1214 = document.getElementById("tag_1214_select").value;//Признак способа расчета
	product.tag_1212 = document.getElementById("tag_1212_select").value;//Признак предмета расчета
	product.order_item_id = 0;
	
	
	//Проверки и расчеты
	if( product.name == "" )
	{
		alert("Наименование товара не заполнено");
		return;
	}
	if( isNaN(product.price) )
	{
		alert("Некорректное значение цены (требуется число)");
		return;
	}
	else if( product.price <= 0 )
	{
		alert("Некорректное значение цены (должно быть больше 0)");
		return;
	}
	if( isNaN(product.count) )
	{
		alert("Некорректное значение количества (требуется число)");
		return;
	}
	if( product.count != parseInt(product.count) || product.count < 1 )
	{
		alert("Некорректное значение количества (требуется целое число больше 0)");
		return;
	}
	
	
	check_object.products[check_object.products.length] = product;
	
	product_next_local_id++;
	
	preview_check();
	
	
	//Сброс виджетов для добавления товара
	document.getElementById("product_name_input").value = name_default;
	document.getElementById("product_price_input").value = price_default;
	document.getElementById("product_count_input").value = count_default;
	document.getElementById("tag_1199_select").value = <?php echo $check_default_options["check_product_tax"]; ?>;
	document.getElementById("tag_1214_select").value = <?php echo $check_default_options["check_product_paymentMethodType"]; ?>;
	document.getElementById("tag_1212_select").value = <?php echo $check_default_options["check_product_paymentSubjectType"]; ?>;
}
// ----------------------------------------------------------------------------
//Добавление оплаты в чек
var payment_next_local_id = 1;
function add_payment_to_check()
{
	//Формируем объект оплаты и добавляем его в check_object
	var payment = new Object;
	payment.local_id = payment_next_local_id;
	payment.type_tag = document.getElementById("payment_types_tag_select").value;
	payment.amount = document.getElementById("payment_amount").value;
	
	
	if( isNaN(payment.amount) )
	{
		alert("Некорректное значение суммы платежа (требуется число)");
		return;
	}
	else if( payment.amount <= 0 )
	{
		alert("Некорректное значение суммы платежа (должно быть больше 0)");
		return;
	}
	
	
	//Логически - не должно быть несколько платежей одним способом, т.е. вот так: наличными 500, наличными 1500, ИТОГО 2000. Поэтому, все платежи одним способом - соединяем в один объект платежа:
	var same_type_exists = false;//Флаг - платеж таким способом уже есть
	for(var i=0; i < check_object.payments.length; i++)
	{
		//Если платеж таким способом уже есть
		if( check_object.payments[i].type_tag == payment.type_tag )
		{
			//Суммируем размер
			payment.amount = parseFloat(payment.amount) + parseFloat(check_object.payments[i].amount);
			//Перезаписываем объект в массиве платежей
			check_object.payments[i] = payment;
			
			same_type_exists = true;
		}
	}
	//Если платежа таким способом еще не было - добавлям его в массив платежей
	if( ! same_type_exists )
	{
		check_object.payments[check_object.payments.length] = payment;
	}
	
	payment_next_local_id++;
	
	preview_check();
	
	
	//Сброс виджетов для добавления платежа
	document.getElementById("payment_types_tag_select").value = <?php echo $check_default_options["check_payment_type"]; ?>;
	document.getElementById("payment_amount").value = "";
}
// ----------------------------------------------------------------------------
//Функция предпросмотра чека
function preview_check()
{
	var div_check_preview = document.getElementById("div_check_preview");
	
	var check_preview_html = "<div id=\"elem_1\" class=\"text-center\"><?php echo date("d.m.Y",time()); ?></div>";
	check_preview_html += "<div id=\"elem_2\" class=\"text-center\">Чек №__ Смена №__</div>";
	
	//Тип чека (Приход/Расход и т.д.)
	check_preview_html += "<div id=\"elem_3\" class=\"text-center\" style=\"border-bottom:1px solid #e5e5e5;\"><b>"+check_object.tag_1054.for_print+"</b></div>";
	
	
	//Товары
	var total_price_by_products = 0;//ИТОГО по товарам (чтобы сравнивать с платежами)
	check_preview_html += "<table style=\"width:100%;\">";
	check_preview_html += "<tr id=\"elem_4\"> <th>№</th> <th>Название</th> <th>Цена</th> <th>Кол.</th> <th>Сумма</th> <th></th> </tr>";
	for(var i=0; i < check_object.products.length; i++)
	{
		total_price_by_products = total_price_by_products + (check_object.products[i].price * check_object.products[i].count);
		
		var local_id = check_object.products[i].local_id;
		
		var dotted_line = "";
		if( i > 0 )
		{
			dotted_line = " style=\"border-top:1px dotted #e5e5e5;\"";
		}
		
		
		
		//HTML кнопки редактирования позиции чека
		var edit_button = "<i class=\"fas fa-pencil-alt\" style=\"color:#3A3;cursor:pointer;\" title=\"Отредактировать позицию\" onclick=\"edit_product_in_check_object("+local_id+");\"></i>";
		/*
		//Если потребуется запретить редактирование позиций заказов - расскомментить этот блок
		if( check_object.products[i].order_item_id > 0 )
		{
			edit_button = "<button class=\"btn btn-xs btn-info btn-circle\" type=\"button\" onclick=\"show_hint('Эта позиция привязана к заказу. Ее нельзя отредактировать в чеке.');\"><i class=\"fa fa-info\"></i></button>";
		}
		*/
		
		
		check_preview_html += "<tr"+dotted_line+" id=\"tr_1_"+local_id+"\"> <td style=\"vertical-align:top;\">"+(i+1)+"</td> <td id=\"td_name_"+local_id+"\" style=\"word-break:break-all;vertical-align:top;\">"+check_object.products[i].name+"</td> <td id=\"td_price_"+local_id+"\" style=\"vertical-align:top;\">"+check_object.products[i].price+"</td> <td id=\"td_count_"+local_id+"\" style=\"vertical-align:top;\">"+check_object.products[i].count+"</td> <td style=\"vertical-align:top;\">"+(check_object.products[i].price * check_object.products[i].count) +"</td> <td id=\"td_actions_"+local_id+"\" style=\"vertical-align:top;\"> "+edit_button+" <i class=\"far fa-trash-alt\" style=\"color:#C33;cursor:pointer;\" title=\"Удалить позицию\" onclick=\"delete_product_from_check_object("+local_id+");\"></i>  </td> </tr>";
		
		//Значения из селектов:
		var tag_1212 = "";
		for(var i_1212 = 0; i_1212 < document.getElementById("tag_1212_select").options.length; i_1212++ )
		{
			if( document.getElementById("tag_1212_select").options[i_1212].value == check_object.products[i].tag_1212 )
			{
				tag_1212 = document.getElementById("tag_1212_select").options[i_1212].text;
				break;
			}
		}
		var tag_1199 = "";
		for(var i_1199 = 0; i_1199 < document.getElementById("tag_1199_select").options.length; i_1199++ )
		{
			if( document.getElementById("tag_1199_select").options[i_1199].value == check_object.products[i].tag_1199 )
			{
				tag_1199 = document.getElementById("tag_1199_select").options[i_1199].text;
				break;
			}
		}
		var tag_1214 = "";
		for(var i_1214 = 0; i_1214 < document.getElementById("tag_1214_select").options.length; i_1214++ )
		{
			if( document.getElementById("tag_1214_select").options[i_1214].value == check_object.products[i].tag_1214 )
			{
				tag_1214 = document.getElementById("tag_1214_select").options[i_1214].text;
				break;
			}
		}
		
		
		check_preview_html += "<tr id=\"tr_2_"+local_id+"\"> <td colspan=\"2\" id=\"td_1212_"+local_id+"\">"+tag_1212+"</td> <td colspan=\"2\" id=\"td_1199_"+local_id+"\">"+tag_1199+"</td> <td colspan=\"2\" id=\"td_1214_"+local_id+"\">"+tag_1214+"</td> </tr>";
		
	}
	total_price_by_products = total_price_by_products.toFixed(2);
	check_preview_html += "<tr id=\"elem_5\"> <td></td> <td></td> <td colspan=\"2\" style=\"font-weight:bold;\">Сумма по товарам</td> <td style=\"font-weight:bold;\">"+total_price_by_products+"</td> <td></td> </tr>";
	check_preview_html += "</table>";
	
	
	//Платежи
	var payments_by = new Array();//Для распределения платежей по видам
	var total_sum = 0;
	<?php
	$ref_payment_types_tags_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_payment_types_tags` ORDER BY `value`;");
	$ref_payment_types_tags_query->execute();
	while( $ref_payment_type_tag = $ref_payment_types_tags_query->fetch() )
	{
		?>
		var payment_by = new Object;
		payment_by.value = <?php echo $ref_payment_type_tag["value"] ; ?>;
		payment_by.for_print = '<?php echo $ref_payment_type_tag["for_print"] ; ?>';
		payment_by.sum = 0;
		
		payments_by[payments_by.length] = payment_by;
		<?php
	}
	?>
	//Распределяем платежи по видам
	for(var i=0; i < check_object.payments.length; i++)
	{
		//Общая сумма
		total_sum = total_sum + parseFloat(check_object.payments[i].amount);
		
		for( var i_p = 0 ; i_p < payments_by.length ; i_p++ )
		{
			if( parseInt(payments_by[i_p].value) == parseInt(check_object.payments[i].type_tag) )
			{
				payments_by[i_p].sum = parseFloat(payments_by[i_p].sum) + parseFloat(check_object.payments[i].amount);
				payments_by[i_p].sum = payments_by[i_p].sum.toFixed(2);
			}
		}
	}
	total_sum = total_sum.toFixed(2);
	//Отображаем платежи по всем видам
	check_preview_html += "<div id=\"elem_6\" class=\"text-center\" style=\"border-bottom:1px solid #e5e5e5;\"></div>";
	check_preview_html +="<table id=\"elem_7\" style=\"width:100%;\">";
	check_preview_html += "<tr id=\"elem_9\"> <td style=\"font-weight:bold;font-size:18px;\">ИТОГО</td> <td style=\"font-weight:bold;font-size:18px;\">"+total_sum+"</td> <td></td> </tr>";
	for( var i_p = 0 ; i_p < payments_by.length ; i_p++ )
	{
		if( payments_by[i_p].sum > 0 )
		{
			check_preview_html += "<tr id=\"tr_payment_"+payments_by[i_p].value+"\"> <td>"+payments_by[i_p].for_print+"</td> <td id=\"td_edit_payment_"+payments_by[i_p].value+"\">"+payments_by[i_p].sum+"</td> <td id=\"td_payment_actions_"+payments_by[i_p].value+"\"> <i class=\"fas fa-pencil-alt\" style=\"color:#3A3;cursor:pointer;\" title=\"Отредактировать платеж данного типа\" onclick=\"edit_payment_in_check_object("+payments_by[i_p].value+");\"></i> <i class=\"far fa-trash-alt\" style=\"color:#C33;cursor:pointer;\" title=\"Удалить платеж данного типа\" onclick=\"delete_payment_from_check_object("+payments_by[i_p].value+");\"></i> </td> </tr>";
		}
	}
	check_preview_html +="</table>";
	
	
	
	
	check_preview_html += "<div id=\"elem_8\"><div class=\"text-center\" style=\"border-bottom:1px dotted #e5e5e5;margin-top:10px;\"></div>";
	check_preview_html += "<div class=\"\">Вид налогообложения: "+check_object.tag_1055.for_print+"</div>";
	check_preview_html += "<div class=\"\">Контакт покупателя: "+check_object.customer_contact+"</div>";
	check_preview_html += "<div class=\"\">Будет использована касса: "+check_object.kkt.for_print+" (ID "+check_object.kkt.id+")</div></div>";
	
	
	div_check_preview.innerHTML = check_preview_html;
}
// ----------------------------------------------------------------------------
//Удаление позиции из чека
function delete_product_from_check_object(local_id)
{
	for(var i=0; i < check_object.products.length; i++)
	{
		if( parseInt(check_object.products[i].local_id) == parseInt(local_id) )
		{
			check_object.products.splice(i, 1);
			break;
		}
	}
	
	preview_check();
}
// ----------------------------------------------------------------------------
//Активация редактирования позиции
function edit_product_in_check_object(local_id)
{
	//На месте записи показываем виджеты для редактирования, кнопки "Сохранить", "Отмена"
	for(var i=0; i < check_object.products.length; i++)
	{
		if( parseInt(check_object.products[i].local_id) == parseInt(local_id) )
		{
			document.getElementById("td_name_"+local_id).innerHTML = "<input class=\"form-control\" type=\"text\" id=\"edit_name_"+local_id+"\" value=\""+check_object.products[i].name+"\" >";
			document.getElementById("td_price_"+local_id).innerHTML = "<input class=\"form-control\" style=\"width:50px\" type=\"text\" id=\"edit_price_"+local_id+"\" value=\""+check_object.products[i].price+"\" >";
			document.getElementById("td_count_"+local_id).innerHTML = "<input class=\"form-control\" style=\"width:50px\" type=\"text\" id=\"edit_count_"+local_id+"\" value=\""+check_object.products[i].count+"\" >";
			
			
			document.getElementById("td_1212_"+local_id).innerHTML = "<select class=\"form-control m-b\" id=\"edit_1212_"+local_id+"\"><?php $ref_tag_1212_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1212` ORDER BY `value`;"); $ref_tag_1212_query->execute(); while( $ref_tag_1212 = $ref_tag_1212_query->fetch() ){?> <option value=\"<?php echo $ref_tag_1212["value"]; ?>\"><?php echo $ref_tag_1212["for_print"]; ?></option><?php } ?></select>";
			document.getElementById("edit_1212_"+local_id).value = check_object.products[i].tag_1212;
			
			document.getElementById("td_1199_"+local_id).innerHTML = "<select class=\"form-control m-b\" id=\"edit_1199_"+local_id+"\"><?php $ref_tag_1199_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1199` ORDER BY `value`;"); $ref_tag_1199_query->execute(); while( $ref_tag_1199 = $ref_tag_1199_query->fetch() ){?> <option value=\"<?php echo $ref_tag_1199["value"]; ?>\"><?php echo $ref_tag_1199["for_print"]; ?></option><?php } ?></select>";
			document.getElementById("edit_1199_"+local_id).value = check_object.products[i].tag_1199;
			
			document.getElementById("td_1214_"+local_id).innerHTML = "<select class=\"form-control m-b\" id=\"edit_1214_"+local_id+"\"><?php $ref_tag_1214_query = $db_link->prepare("SELECT * FROM `shop_kkt_ref_tag_1214` ORDER BY `value`;"); $ref_tag_1214_query->execute(); while( $ref_tag_1214 = $ref_tag_1214_query->fetch() ){?> <option value=\"<?php echo $ref_tag_1214["value"]; ?>\"><?php echo $ref_tag_1214["for_print"]; ?></option><?php } ?></select>";
			document.getElementById("edit_1214_"+local_id).value = check_object.products[i].tag_1214;
			
			
			document.getElementById("td_actions_"+local_id).innerHTML = "<i class=\"far fa-save\" style=\"color:#3A3;cursor:pointer;\" title=\"Применить\" onclick=\"save_edit_product("+local_id+");\"></i> <i class=\"far fa-window-close\" style=\"color:#C33;cursor:pointer;\" title=\"Отмена\" onclick=\"calcel_edit_product();\"></i>";
		}
		else
		{
			//Все виджеты делаем недоступными, пока редактируется данная позиция
			document.getElementById("tr_1_"+check_object.products[i].local_id).setAttribute("style", "pointer-events: none; opacity: 0.4;");
			document.getElementById("tr_2_"+check_object.products[i].local_id).setAttribute("style", "pointer-events: none; opacity: 0.4;");
		}
	}
	
	
	
	//Все виджеты делаем недоступными, пока редактируется данная позиция
	document.getElementById("div_check_widgets").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "pointer-events: none; opacity: 0.4;background-color:#AAA;");
	document.getElementById("elem_1").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_2").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_3").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_4").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_5").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_6").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_7").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_8").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	
	
	
	//После нажатия "Сохранить" - запись изменений в объект и preview_check(), "Отмена" - preview_check()
}
// ----------------------------------------------------------------------------
//Отмена режима редактирования без сохранения изменений
function calcel_edit_product()
{
	//Делаем доступной область добавления данных для чека и footer окна (остальные сами перерисуются)
	document.getElementById("div_check_widgets").setAttribute("style", "");
	document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
	
	
	//Перерисовка предпросмотра чека
	preview_check();
}
// ----------------------------------------------------------------------------
//Сохранение изменений при редактировании позиции чека
function save_edit_product(local_id)
{
	for(var i=0; i < check_object.products.length; i++)
	{
		if( parseInt(check_object.products[i].local_id) == parseInt(local_id) )
		{
			//Пересоздаем объект позиции (его local_id - такой же)
			var product = new Object;
			product.local_id = local_id;
			product.name = document.getElementById("edit_name_"+local_id).value;//Наименование
			product.price = document.getElementById("edit_price_"+local_id).value;//Цена
			product.count = document.getElementById("edit_count_"+local_id).value;//Количество
			product.tag_1199 = document.getElementById("edit_1199_"+local_id).value;//Ставка НДС
			product.tag_1214 = document.getElementById("edit_1214_"+local_id).value;//Платежный метод
			product.tag_1212 = document.getElementById("edit_1212_"+local_id).value;//Предмет расчета
			product.order_item_id = check_object.products[i].order_item_id;
			
			//Проверки и расчеты (такие же, как при создании позиции чека)
			if( product.name == "" )
			{
				alert("Наименование товара не заполнено");
				return;
			}
			if( isNaN(product.price) )
			{
				alert("Некорректное значение цены (требуется число)");
				return;
			}
			else if( product.price <= 0 )
			{
				alert("Некорректное значение цены (должно быть больше 0)");
				return;
			}
			if( isNaN(product.count) )
			{
				alert("Некорректное значение количества (требуется число)");
				return;
			}
			if( product.count != parseInt(product.count) || product.count < 1 )
			{
				alert("Некорректное значение количества (требуется целое число больше 0)");
				return;
			}
			
			
			//Проверки пройдены - перезаписываем объект в массиве
			check_object.products[i] = product;
		}
	}
	
	
	//Вызываем функцию "Отмены редактирования" - там идет просто снятие блокировки с виджетов и перерисовка чека
	calcel_edit_product();
}
// ----------------------------------------------------------------------------
//Удаление платежа определенного типа
function delete_payment_from_check_object(type_tag)
{
	for(var i=0; i < check_object.payments.length; i++)
	{
		if( parseInt(check_object.payments[i].type_tag) == parseInt(type_tag) )
		{
			check_object.payments.splice(i, 1);
			break;
		}
	}
	
	//Перерисовка предпросмотра чека
	preview_check();
}
// ----------------------------------------------------------------------------
//Редактирование платежа определенного типа
function edit_payment_in_check_object(type_tag)
{
	for(var i=0; i < check_object.payments.length; i++)
	{
		if( parseInt(check_object.payments[i].type_tag) == parseInt(type_tag) )
		{
			//Показываем поле ввода для редактирования значения
			document.getElementById("td_edit_payment_"+type_tag).innerHTML = "<input type=\"text\" value=\""+check_object.payments[i].amount+"\" id=\"input_edit_payment_"+type_tag+"\" class=\"form-control\" />";
			
			
			//Кнопки Сохранить и отмена
			document.getElementById("td_payment_actions_"+type_tag).innerHTML = "<i class=\"far fa-save\" style=\"color:#3A3;cursor:pointer;\" title=\"Применить\" onclick=\"save_edit_payment("+type_tag+");\"></i> <i class=\"far fa-window-close\" style=\"color:#C33;cursor:pointer;\" title=\"Отмена\" onclick=\"calcel_edit_payment();\"></i>";
			
		}
		else
		{
			//Виджеты для остальных типов платежей делаем недоступными
			document.getElementById("tr_payment_"+check_object.payments[i].type_tag).setAttribute("style", "pointer-events: none; opacity: 0.4;");
		}
	}
	
	
	
	//Все виджеты делаем недоступными, пока редактируется платеж данного типа
	document.getElementById("div_check_widgets").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "pointer-events: none; opacity: 0.4;background-color:#AAA;");
	document.getElementById("elem_1").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_2").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_3").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_4").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_5").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_6").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	//document.getElementById("elem_7").setAttribute("style", "pointer-events: none; opacity: 0.4;");//Блок с платежами блокировать весь не нужно.
	document.getElementById("elem_8").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	document.getElementById("elem_9").setAttribute("style", "pointer-events: none; opacity: 0.4;");
	//Блокируем виджеты товарных позиций
	for(var i=0; i < check_object.products.length; i++)
	{
		document.getElementById("tr_1_"+check_object.products[i].local_id).setAttribute("style", "pointer-events: none; opacity: 0.4;");
		document.getElementById("tr_2_"+check_object.products[i].local_id).setAttribute("style", "pointer-events: none; opacity: 0.4;");
	}
	
}
// ----------------------------------------------------------------------------
//Отмена редактирования платежа
function calcel_edit_payment()
{
	//Делаем доступной область добавления данных для чека и footer окна (остальные сами перерисуются)
	document.getElementById("div_check_widgets").setAttribute("style", "");
	document.getElementById("modal_kkt_create_new_check_footer").setAttribute("style", "");
	
	
	//Перерисовка предпросмотра чека
	preview_check();
}
// ----------------------------------------------------------------------------
//Сохранить изменение платежа
function save_edit_payment(type_tag)
{
	for(var i=0; i < check_object.payments.length; i++)
	{
		if( parseInt(check_object.payments[i].type_tag) == parseInt(type_tag) )
		{
			//Пересоздаем объект платежа
			var payment = new Object;
			payment.local_id = check_object.payments[i].local_id;//Такой же
			payment.type_tag = type_tag;
			payment.amount = document.getElementById("input_edit_payment_"+type_tag).value;
			
			
			if( isNaN(payment.amount) )
			{
				alert("Некорректное значение суммы платежа (требуется число)");
				return;
			}
			else if( payment.amount <= 0 )
			{
				alert("Некорректное значение суммы платежа (должно быть больше 0)");
				return;
			}
			
			//Перезаписываем объект платежа в массиве платежей
			check_object.payments[i] = payment;
			
			break;
		}
	}
	
	
	//Вызываем функцию "Отмены редактирования" - там идет просто снятие блокировки с виджетов и перерисовка чека
	calcel_edit_payment();
}
// ----------------------------------------------------------------------------
</script>






<script>
//START - Блок для создания чеков по указанным позициям заказов
// -----------------------------------------------------------------------------------------------------------
//Функция, вызываемая по нажатию кнопки "Оформить чек" (для выбранных позиций заказов - на странице "Заказ" и "Позиции заказов")
function create_check_for_orders_items()
{
	var orders_items = getCheckedElements();//Список отмеченных заказов
	if(orders_items.length == 0)
	{
		alert("Выберите товарные позиции из списка");
		return;
	}
	
	//Открываем модальное окно для создания чека
	init_modal_kkt_create_new_check();
	
	
	//Заполняем объект чека позициями
	var sum_by_all_products = 0;//Сумма по всем позициям
	for(var i=0; i < orders_items.length; i++)
	{
		sum_by_all_products = sum_by_all_products + ( orders_items_ids_to_orders_items_objects[orders_items[i]].price*orders_items_ids_to_orders_items_objects[orders_items[i]].count_need );
		
		
		//Формируем объект товара
		var product = new Object;
		product.local_id = product_next_local_id;
		product.name = orders_items_ids_to_orders_items_objects[orders_items[i]].product_name;//Наименование
		product.price = orders_items_ids_to_orders_items_objects[orders_items[i]].price;//Цена
		product.count = orders_items_ids_to_orders_items_objects[orders_items[i]].count_need;//Количество
		product.tag_1199 = check_default_options.check_product_tax;//Ставка НДС
		product.tag_1214 = check_default_options.check_product_paymentMethodType;//Признак способа расчета
		product.tag_1212 = check_default_options.check_product_paymentSubjectType;//Признак предмета расчета
		product.order_item_id = orders_items[i];
		
		//Добавляем его в check_object
		check_object.products[check_object.products.length] = product;
		
		//Следующий локальный ID позиции чека
		product_next_local_id++;
	}
	
	
	//Добавляем платеж
	var payment = new Object;
	payment.local_id = payment_next_local_id;
	payment.type_tag = check_default_options.check_payment_type;
	payment.amount = sum_by_all_products;
	check_object.payments[check_object.payments.length] = payment;
	payment_next_local_id++;
	
	//Предпросмотр чека
	preview_check();
}
// -----------------------------------------------------------------------------------------------------------
//Переход на страницу с отображением чеков позиции заказа
function show_order_item_checks(order_item_id)
{
	var filter = new Object;
	
	//...Установка фильтров
	filter.order_item_id = order_item_id;
	
	//Устанавливаем cookie (на полгода)
	var date = new Date(new Date().getTime() + 15552000 * 1000);
	document.cookie = "checks_filter="+JSON.stringify(filter)+"; path=/; expires=" + date.toUTCString();
	
	//Переход на страницу чеков
	var win = window.open('/<?php echo $DP_Config->backend_dir; ?>/shop/onlajn-kassy/checks', '_blank');
	win.focus();
}
// -----------------------------------------------------------------------------------------------------------
//Переход на страницу с отображением чеков по заказу
function show_order_checks(order_id)
{
	var filter = new Object;
	
	//...Установка фильтров
	filter.order_id = order_id;
	
	//Устанавливаем cookie (на полгода)
	var date = new Date(new Date().getTime() + 15552000 * 1000);
	document.cookie = "checks_filter="+JSON.stringify(filter)+"; path=/; expires=" + date.toUTCString();
	
	//Переход на страницу чеков
	var win = window.open('/<?php echo $DP_Config->backend_dir; ?>/shop/onlajn-kassy/checks', '_blank');
	win.focus();
}
// -----------------------------------------------------------------------------------------------------------
//END - Блок для создания чеков по указанным позициям заказов
</script>






<script>
	jQuery( window ).load(function() {
		//Если были созданы чеки, то, после закрытия окна - перезагружаем страницу (чтобы чеки отобразились, например в таблице чеков)
		$("#modal_kkt_create_new_check").on('hidden.bs.modal', function(){
			if( checks_created_count > 0 )
			{
				location = location;
			}
		});
		
	});
</script>