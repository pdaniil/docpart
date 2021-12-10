<?php
/**
 * Страничный скрипт для корзины
*/
defined('_ASTEXE_') or die('No access');




//Получаем данные по валюте магазина
$currency_query = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;');
$currency_query->execute( array($DP_Config->shop_currency) );
$currency_record = $currency_query->fetch();
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}




require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();
if($user_id > 0)
{
	//Поля для авторизованного пользователя
	$session_id = 0;
}
else
{
	//Поля для НЕ авторизованного пользователя
	$session_record = DP_User::getUserSession();
	if($session_record == false)
	{
		$result = array();
		$result["status"] = false;
		$result["code"] = "incorrect_session";
		$result["message"] = "Ошибка сессии";
		exit(json_encode($result));
	}
	
	$session_id = $session_record["id"];
}




$cart_records = array();//Список записей корзины для этого пользователя

//Получаем содержимое его корзины из базы данных
$cart_records_query = $db_link->prepare('SELECT `id` FROM `shop_carts` WHERE `user_id` = ? AND `session_id` = ?;');
$cart_records_query->execute( array($user_id, $session_id) );
while($cart_record = $cart_records_query->fetch() )
{
	array_push($cart_records, $cart_record["id"]);
}
?>




<div id="cart_area"></div>
<style>
#cart_area .table th,
#cart_area .table td{
	vertical-align: middle;
    white-space: nowrap;
}
#cart_area .table input[type="checkbox"]{
	width: 25px;
    height: 25px;
}
</style>



<?php
if(count($cart_records) == 0)
{
    ?>
    <script>
        document.getElementById("cart_area").innerHTML = "Корзина пуста";
    </script>
    <?php
}
else
{
    ?>
    <script>
    // --------------------------------------------------------------------------------------
    //Переотобразить корзину
    function refreshCartArea()
    {
		//Строки с суммой в корзине
		sum_total = Number(sum_total).toFixed(2);
		var sum_total_num = Number(sum_total);
		sum_total = digit(sum_total);
		<?php
		//Индикатор валюты перед ценой
		if($DP_Config->currency_show_mode == "sign_before")
		{
			?>
			sum_total = '<font class=\"currency\"><?php echo $currency_indicator; ?></font> <font class=\"price\">' + sum_total + "</font>";
			<?php
		}
		//Индикатор валюты после цены
		else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
		{
			?>
			sum_total = '<font class=\"price\">'+sum_total+'</font> <font class=\"currency\"><?php echo $currency_indicator; ?></font>';
			<?php
		}
		?>
		// ----------------------------------------------
		
        //Обновление корзины снизу
        if(typeof updateCartInfo == 'function') {
            updateCartInfo();
        }
        
        // ----------------------------------------------
        
		//Обработка модуля корзины - в случае наличия: (Для старых шаблонов)
		var cart_module_positions = document.getElementById("cart_module_positions");
		if(cart_module_positions != undefined)
		{
			cart_module_positions.innerHTML = "<b>Товаров в корзине</b> " + cart_records.length;
		}
		var cart_module_sum = document.getElementById("cart_module_sum");
		if(cart_module_sum != undefined)
		{
			cart_module_sum.innerHTML = "<b>На сумму</b> " + sum_total;
		} 
		
		// ----------------------------------------------
		
		
        //Если последний товар был удален из корзины
        if(cart_records.length == 0)
        {
            document.getElementById("cart_area").innerHTML = "Корзина пуста";
            return;
        }
        
       
		// Определим есть ли в корзине товары с изображениями и только при их наличии будем отображать колонку с картинкой товара
		var there_is_images = false;
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].image_src != '' ){
				there_is_images = true;
				break;
			}
		}
		
		// Определим все ли позиции уорзины выбраны
		var check_all = 'checked';
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].checked_for_order == 0 ){
				check_all = '';
				break;
			}
		}
		
		var cart_html = "";//HTML корзины
		
		cart_html += '<div style="overflow: hidden; overflow-x: auto;">';
		cart_html += '<table class="table cart_table">';
		cart_html += 	'<tr>';
		cart_html += 		'<th><input type="checkbox" '+check_all+' id="check_uncheck_all" onchange="on_check_uncheck_all();"/></th>';
		
		if(there_is_images === true){
		cart_html += 		'<th></th>';
		}
		
		cart_html += 		'<th>Производитель</th>';
		cart_html += 		'<th>Артикул</th>';
		cart_html += 		'<th>Наименование</th>';
		cart_html += 		'<th>Срок</th>';
		cart_html += 		'<th>Цена</th>';
		cart_html += 		'<th>Количество</th>';
		cart_html += 		'<th>Сумма</th>';
		cart_html += 		'<th></th>';
		cart_html += 	'</tr>';
		
        for(var i=0; i < cart_records.length; i++)
        {
            var checked_for_order = 'checked';
			if( cart_records[i].checked_for_order == 0 ){
				checked_for_order = '';
			}
			
			//Строки с ценами
			var price = digit(Number(cart_records[i].price).toFixed(2));//Цена позиции
			var price_sum = digit(Number(cart_records[i].price_sum).toFixed(2));//Сумма позиции
			<?php
			if($DP_Config->currency_show_mode == "sign_before")
			{
				?>
				price = '<?php echo $currency_indicator; ?> '+ price;
				price_sum = '<?php echo $currency_indicator; ?> '+ price_sum;
				<?php
			}
			else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
			{
				?>
				price = price +' <?php echo $currency_indicator; ?>';
				price_sum = price_sum +' <?php echo $currency_indicator; ?>';
				<?php
			}
			?>
			
			
			var style_tr = '';// Стили строки позиции корзины (используется при различных доработках)
			
			
			//////////////////////////////////////////////////////////////////////// - Доработка id 34
			
			//////////////////////////////////////////////////////////////////////// - END Доработка id 34
			
			
			cart_html += '<tr style="'+style_tr+'">';
			
				cart_html += '<td>';
					cart_html += '<input type="checkbox" '+checked_for_order+' onclick="check_for_order('+cart_records[i].id+');">';
				cart_html += '</td>';
				
				
				if(there_is_images === true){
				if(cart_records[i].image_src !== ''){
				cart_html += '<td>';
                    cart_html += '<img style="max-width:50px; max-height:70px;" src="'+cart_records[i].image_src+'"/>';
                cart_html += '</td>';
				}else{
				cart_html += '<td>';
					cart_html += '';
				cart_html += '</td>';
				}
				}
			
			
                cart_html += '<td style="font-weight: bold;">';
                    cart_html += cart_records[i].manufacturer;
                cart_html += '</td>';
                
				
				cart_html += '<td style="font-weight: bold;">';
                    cart_html += cart_records[i].article;
                cart_html += '</td>';
				
				
				cart_html += '<td style="white-space: normal; line-height: 1em; width: 100%; min-width: 200px;">';
                    cart_html += cart_records[i].name;
                cart_html += '</td>';
				
				
				cart_html += '<td>';
                    cart_html += cart_records[i].time_to_exe;
                cart_html += '</td>';
				
				
				cart_html += '<td>';
                    cart_html += price;
                cart_html += '</td>';
				
				
				cart_html += '<td><table style="margin:auto;"><tr>';
                    cart_html += '<td><a style="display: inline-block; background: #f5f5f5; font-weight: bold; width: 22px; height: 25px; line-height: 24px; text-decoration: none; text-align: center; border-radius: 3px 0px 0px 3px; border: 1px solid #999; border-right: 0;" onclick="minusCountNeed('+cart_records[i].id+');" href="javascript:void(0);"><span style="position: relative; top: -2px;">-</span></a></td>';
                    cart_html += '<td><input style="width: 40px; height: 25px; line-height: 24px; text-align: center; border: 1px solid #999; border-radius: unset; box-shadow: none;" type="text" value="'+cart_records[i].count_need+'" onkeyup="onKeyUpCountNeed('+cart_records[i].id+');" id="count_need_'+cart_records[i].id+'"/></td>';
                    cart_html += '<td><a style="display: inline-block; background: #f5f5f5; font-weight: bold; width: 22px; height: 25px; line-height: 24px; text-decoration: none; text-align: center; border-radius: 0px 3px 3px 0px; border: 1px solid #999; border-left: 0;" onclick="plusCountNeed('+cart_records[i].id+');" href="javascript:void(0);"><span>+</span></a></td>';
                cart_html += '</tr></table></td>';
				
				
				cart_html += '<td style="font-weight: bold;">';
                    cart_html += price_sum;
                cart_html += '</td>';
				
				
				cart_html += '<td style="text-align: right;">';
					
					cart_html += '<a style="margin-right:5px;" href="javascript:void(0);" onclick="RefreschRecord('+cart_records[i].id+');" title="Перезаказать"><i style="font-size: 20px; position: relative; top: -1px;" class="fa fa-refresh" aria-hidden="true"></i></a>';
					
					cart_html += '<a style="margin-right:5px;" href="javascript:void(0);" title="Добавить в блокнот" onclick="show_add_bloknot('+cart_records[i].id+');"><i style="font-size: 18px; position: relative; top: -2px;" class="fa fa-car"></i></a>';
					
					cart_html += '<a href="javascript:void(0);" onclick="deleteRecord('+cart_records[i].id+');" title="Удалить"><i style="font-size: 25px;" class="fa fa-times" aria-hidden="true"></i></a>';
                
				cart_html += '</td>';
				
            cart_html += '</tr>';
        }
		
        cart_html += '</table>';
        cart_html += '</div>';
		
		
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
			
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
		
		cart_html += '<p style="background: #eee; padding: 10px; margin: 10px 0px; border-radius: 5px; border: 1px solid #ddd;"><i class="fa fa-info-circle" aria-hidden="true"></i> Стоимость заказа в корзине указана на условиях самовывоза. Для выполнения заказа может потребоваться предоплата.</p>';
		
		
        cart_html += '<div style="padding-top: 10px; text-align: right; font-size: 18px; font-weight: bold;"><span style="font-size: 14px; font-weight: normal;">Итого:</span> '+sum_total+'</div>';
        
        
        <?php
        //В зависимости от того, зарегистрирован ли пользователь - указываем ссылку для кнопки "Оформить заказ"
        if($user_id > 0)
        {
            $order_link = "/shop/checkout/how_get";//Сразу на страницу выбора способа получения
        }
        else
        {
            $order_link = "/shop/checkout/login_offer";//Предложить авторизацию
        }
        ?>
        
		
		if( sum_total_num > 0 )
		{
			cart_html += '<div style="text-align: right;"><a class="btn btn-ar btn-primary" href="<?php echo $order_link; ?>">Оформить заказ</a></div>';
		}
		
		
        document.getElementById("cart_area").innerHTML = cart_html;
    }
    // --------------------------------------------------------------------------------------
	var ajax_flag = false;
    // --------------------------------------------------------------------------------------
    //Функция добавления требуемого количества
    function plusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Объект для запроса
        var request_object = new Object;
        request_object.id = cart_record_id;
        request_object.count_need = parseInt(current_count_need) + parseInt(min_order);
        
        
        //Увеличиваем наличие на сервере и только после этого отображаем
        jQuery.ajax({
            type: "POST",
            async: true, //Запрос синхронный
            url: "/content/shop/order_process/ajax_change_count_need.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
				//console.log(answer);
				
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    if(answer.code == "not_enough")
                    {
                        alert("Превышено наличие на складе");
                    }
                    else
                    {
                        alert("Ошибка добавления количества");
                    }
                }
				ajax_flag = false;
            }
        });
    }
    // --------------------------------------------------------------------------------------
    //Функция вычитания требуемого количества
    function minusCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
		
		var min_order = 1;
		for(var i=0; i < cart_records.length; i++)
        {
			if(cart_records[i]['id'] == cart_record_id){
				min_order = cart_records[i]['min_order'];
			}
		}
        //Проверка допустимости
        if( parseInt(current_count_need-1) > 0 )
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = parseInt(current_count_need) - parseInt(min_order);
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object)),
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        alert("Ошибка уменьшения количества");
                    }
					ajax_flag = false;
                }
            });
        }else{
			ajax_flag = false;
		}
    }
    // --------------------------------------------------------------------------------------
    //Функция изменения количества при ручном вводе в поле
    function onKeyUpCountNeed(cart_record_id)
    {
		if(ajax_flag){
			return;
		}
		ajax_flag = true;
		
        //Текущее количество
        var current_count_need = parseInt(document.getElementById("count_need_"+cart_record_id).value);
        
        //Если введено допустимое значение
        if(current_count_need > 0)
        {
            //Объект для запроса
            var request_object = new Object;
            request_object.id = cart_record_id;
            request_object.count_need = current_count_need;
            
            //Уменьшаем наличие на сервере и только после этого отображаем
            jQuery.ajax({
                type: "POST",
                async: true, //Запрос синхронный
                url: "/content/shop/order_process/ajax_change_count_need.php",
                dataType: "json",//Тип возвращаемого значения
                data: "request_object="+encodeURI(JSON.stringify(request_object)),
                success: function(answer)
                {
                    if(answer.status == true)
                    {
                        cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                        calculateSums();//Пересчитываем суммы по товарам и итого
                        refreshCartArea();//Отображаем товары в корзине
                    }
                    else
                    {
                        if(answer.code == "not_enough")//Превышено наличие на складе - но мы зарезервировали максимально-доступное количество
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
                            alert("Превышено наличие на складе");
                        }
                        else
                        {
                            cart_records[getElementIndex(answer.id)].count_need = answer.count_need;//В объект на уровне клиента
                            calculateSums();//Пересчитываем суммы по товарам и итого
                            refreshCartArea();//Отображаем товары в корзине
							alert("Ошибка изменения количества");
                        }
                    }
					ajax_flag = false;
                }
            });
        }
        else//Просто исправляем обратно
        {
            alert("Введено недопустимое значение");
            document.getElementById("count_need_"+cart_record_id).value = cart_records[getElementIndex(cart_record_id)].count_need;
			ajax_flag = false;
        }
    }
    // --------------------------------------------------------------------------------------
    //Удаление из Корзины
    function deleteRecord(cart_record_id)
    {
        //Объект для запроса
        var request_object = new Object;
		request_object.records_to_del = new Array();
		request_object.records_to_del.push(cart_record_id)
    
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_delete_cart_record.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    //Удаляем элемет из массива
                    cart_records.splice(getElementIndex(answer.records_to_del[0]), 1);
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Ошибка удаления");
                }
            }
        });
    }
    // --------------------------------------------------------------------------------------
    //Получить индекс из списка по ID записи
    function getElementIndex(cart_record_id)
    {
        //Сначала определяем индекс объекта в списке javascript
        for(var i=0; i < cart_records.length; i++)
        {
            if(cart_record_id == cart_records[i].id)
            {
                return i;
            }
        }
    }
    // --------------------------------------------------------------------------------------
    //Пересчитать суммы по продуктам
    function calculateSums()
    {
        sum_total = 0;//Обнуляем общую сумму
        
        for(var i=0; i < cart_records.length; i++)
        {
			cart_records[i].price_sum = cart_records[i].price*cart_records[i].count_need;
            
			if( cart_records[i].checked_for_order == 0 )
			{
				continue;
			}
			
            sum_total = sum_total + cart_records[i].price_sum;
        }
    }
    // --------------------------------------------------------------------------------------
    //Снять / отметить для заказа
	function check_for_order(cart_record_id)
	{
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
		
		//////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
		//Объект для запроса
        var request_object = new Object;
		request_object.records = new Array();
		request_object.records.push(cart_record_id);
		
        jQuery.ajax({
            type: "POST",
            async: false, //Запрос синхронный
            url: "/content/shop/order_process/ajax_check_for_order.php",
            dataType: "json",//Тип возвращаемого значения
            data: "request_object="+encodeURI(JSON.stringify(request_object)),
            success: function(answer)
            {
                console.log(answer);
                if(answer.status == true)
                {
                    cart_records[getElementIndex(answer.records[0].cart_record_id)].checked_for_order = answer.records[0].checked_for_order;
					
					
                    calculateSums();//Пересчитываем суммы по товарам и итого
                    refreshCartArea();//Отображаем товары в корзине
                }
                else
                {
                    alert("Серверная ошибка");
                }
            }
        });
	}
	// --------------------------------------------------------------------------------------
	//Обработка переключения Выделить все / Снять все
    function on_check_uncheck_all()
    {
        var state = document.getElementById("check_uncheck_all").checked;
        
		var request_object = new Object;
		request_object.records = new Array();
		
		for(var i=0; i < cart_records.length; i++)
        {
			if( cart_records[i].checked_for_order != state )
			{
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
				request_object.records.push(cart_records[i].id);
			}
        }
		
        if(request_object.records.length > 0){
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/content/shop/order_process/ajax_check_for_order.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURI(JSON.stringify(request_object)),
				success: function(answer)
				{
					if(answer.status == true)
					{
						for(var i=0; i < answer.records.length; i++)
						{
							cart_records[getElementIndex(answer.records[i].cart_record_id)].checked_for_order = answer.records[i].checked_for_order;
						}
						
						calculateSums();//Пересчитываем суммы по товарам и итого
						refreshCartArea();//Отображаем товары в корзине
					}
					else
					{
						alert("Серверная ошибка");
					}
				}
			});
		}else{
			calculateSums();//Пересчитываем суммы по товарам и итого
			refreshCartArea();//Отображаем товары в корзине
		}
    }//~function on_check_uncheck_all()
	// --------------------------------------------------------------------------------------
	// Функция отделяет тысячные знаки пробелом. Используется для отображения цены
	function digit(str){
		var parts = (str + '').split('.'),
			main = parts[0],
			len = main.length,
			output = '',
			i = len - 1;
		
		while(i >= 0) {
			output = main.charAt(i) + output;
			if ((len - i) % 3 === 0 && i > 0) {
				output = ' ' + output;
			}
			--i;
		}

		if (parts.length > 1) {
			output += '.' + parts[1];
		}
		return output;
	}
	// --------------------------------------------------------------------------------------
	// Функция удаляет из корзине товар и переносит пользователя в проценку
	function RefreschRecord(id){
		var url = cart_records[getElementIndex(id)].url_refresh;
		deleteRecord(id);
		window.location = url;
	}
	// --------------------------------------------------------------------------------------
    var cart_records = new Array();
    var sum_total = 0;//Сумма заказа
    <?php

	$cart = array();// Массив объектов содержимого корзины

	//Формируем объекты содержимого корзины
    for($i=0; $i < count($cart_records); $i++)
    {
        $cart_object = array();
    
		$cart_record_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
        $cart_record_query->execute( array($cart_records[$i]) );
        $cart_record = $cart_record_query->fetch();
        
        //Заполняем поля, которые не зависят от типа продукта:
        $cart_object["id"] = $cart_records[$i];
        $cart_object["price"] = $cart_record["price"];
        $cart_object["product_type"] = $cart_record["product_type"];
        $cart_object["count_need"] = $cart_record["count_need"];
		$cart_object["checked_for_order"] = $cart_record["checked_for_order"];
        
		//Срок поставки
		if($cart_record["t2_time_to_exe"] < $cart_record["t2_time_to_exe_guaranteed"]){
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"] .' - '. $cart_record["t2_time_to_exe_guaranteed"];
		}else{
			$cart_object["time_to_exe"] = $cart_record["t2_time_to_exe"];
		}
		if($cart_object["time_to_exe"] == 0){
			$cart_object["time_to_exe"] = 'На складе';
		}else{
			$cart_object["time_to_exe"] = $cart_object["time_to_exe"] . ' дн.';
		}
		
		//Минимальная партия
		$cart_object["min_order"] = (int) $cart_record["t2_min_order"];
		if($cart_object["min_order"] <= 0){
			$cart_object["min_order"] = 1;
		}
        
		//Поля зависящие от типа продукта
		$cart_object["name"] = '';
		$cart_object["image_src"] = '';
		$cart_object["manufacturer"] = '';
		$cart_object["article"] = '';
		$cart_object["product_id"] = '';
		$cart_object["url_refresh"] = '';
		
		//////////////////////////////////////////////////////////////////////// - Доработка id 34
        
        //////////////////////////////////////////////////////////////////////// - END Доработка id 34
		
        //Заполняем поля объекта корзины в зависимости от типа продукта (1 - каталожный, 2 - docpart)
        switch($cart_record["product_type"])
        {
            case 1:
                $product_id = (int) $cart_record["product_id"];
				
				$cart_object["product_id"] = $product_id;
				
				//////////////////////////////////////////////////////////////////////// - Доработка id 34
				
				//////////////////////////////////////////////////////////////////////// - END Доработка id 34
				
                //Получаем Наименование, id категории и alias продукта
                $product_query = $db_link->prepare("SELECT `category_id`, `caption`, `alias` FROM `shop_catalogue_products` WHERE `id` = $product_id;");
				$product_query->execute();
                $product_record = $product_query->fetch();
				$category_id = (int) $product_record['category_id'];
				$product_alias = trim($product_record['alias']);
				$product_name = trim($product_record['caption']);
				$product_name = str_replace( array("'", '"', "\n", "\t", "\r", "\\"), "", $product_name);
				if(!empty($product_name)){
					$cart_object["name"] = $product_name;
				}
                
                //Получаем изображение
				$image_query = $db_link->prepare("SELECT `id`, `file_name` FROM `shop_products_images` WHERE `product_id` = ? ORDER BY `id` ASC LIMIT 1;");
				$image_query->execute( array($product_id) );
                $image_record = $image_query->fetch();
				$image_record["file_name"] = trim($image_record["file_name"]);
				if( !empty($image_record["file_name"]) )
                {
                    if( strpos($image_record["file_name"], "/") )
					{
						$cart_object["image_src"] = $image_record["file_name"];
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$image_record["file_name"])){
							$cart_object["image_src"] = "/content/files/images/products_images/".$image_record["file_name"];
						}
					}
                }
                
				//Получаем свойство Производитель
				$product_manufacturer_query = $db_link->prepare("SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = $product_id AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = $category_id AND `value` = 'Производитель' AND `property_type_id` = 5))");
				$product_manufacturer_query->execute();
                $product_manufacturer_record = $product_manufacturer_query->fetch();
				$product_manufacturer = trim($product_manufacturer_record['value']);
				if(!empty($product_manufacturer)){
					$cart_object["manufacturer"] = $product_manufacturer;
				}
				
				//Получаем свойство Артикул
				$product_article_query = $db_link->prepare("SELECT `value` FROM `shop_properties_values_text` WHERE `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = $category_id AND `value` = 'Артикул' AND `property_type_id` = 3) AND `product_id` = $product_id");
				$product_article_query->execute();
                $product_article_record = $product_article_query->fetch();
				$product_article = trim($product_article_record['value']);
				if(!empty($product_article)){
					$cart_object["article"] = $product_article;
				}
				
				//Получаем ссылку на продукт для переоценки
				$product_category_query = $db_link->prepare("SELECT `url` FROM `shop_catalogue_categories` WHERE `id` = $category_id");
				$product_category_query->execute();
                $product_category_record = $product_category_query->fetch();
				$product_category_url = trim($product_category_record['url']);
				$cart_object["url_refresh"] = '/'.$product_category_url.'/'.$product_alias;
				
                break;
            case 2:
				//Получаем Наименование
				$product_name = trim($cart_record["t2_name"]);
				$product_name = str_replace( array("'", '"', "\n", "\t", "\r", "\\"), "", $product_name);
				if(!empty($product_name)){
					$cart_object["name"] = $product_name;
				}
                
                //Получаем изображение
				$product_image = NULL;
				if( isset($cart_record["image"]) )
				{
					$product_image = trim($cart_record["image"]);
				}
				if( !empty($product_image) )
                {
                    if( strpos($product_image, "/") )
					{
						$cart_object["image_src"] = $product_image;
					}else{
						if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/products_images/".$product_image)){
							$cart_object["image_src"] = "/content/files/images/products_images/".$product_image;
						}
					}
                }
				
				//Получаем свойство Производитель
				$product_manufacturer = trim($cart_record["t2_manufacturer"]);
				$product_manufacturer = str_replace( array("'", '"', "\n", "\t", "\r", "\\"), "", $product_manufacturer);
				if(!empty($product_manufacturer)){
					$cart_object["manufacturer"] = $product_manufacturer;
				}
				
				//Получаем свойство Артикул
				$product_article = trim($cart_record["t2_article"]);
				$product_article = str_replace( array(" ", "'", '"', "\n", "\t", "\r", "\\"), "", $product_article);
				if(!empty($product_article)){
					$cart_object["article"] = $product_article;
				}
				
				//Получаем ссылку на продукт для переоценки
				$cart_object["url_refresh"] = "/parts/".htmlentities($cart_object["manufacturer"])."/".$cart_object["article"];
				
                break;
        }
		
		$cart[] = $cart_object;
		
    }//for($i) - формируем объект корзины
    ?>
	cart_records = JSON.parse('<?php echo json_encode($cart); ?>');
    calculateSums();//Пересчитываем суммы по товарам и итого
    refreshCartArea();//Отображаем товары в корзине
    </script>
    <?php
}//~else - в корзине есть записи
?>














<!---------------------------------------------- ГАРАЖ ---------------------------------------------->
<style>
body {
   padding: 0 !important;
}
#my_modal_box_for_garage .modal {
	z-index:99999999;
	padding-right: 0px !important;
}
#my_modal_box_for_garage .modal-header {
  text-align: center;
  font-size: 14px;
  background: #fff;
  color:#000;
  border-bottom: 1px solid #999;
}
#my_modal_box_for_garage .close{
	color:#000;
}
#my_modal_box_for_garage .modal-footer {
	border-top: 1px solid #999;
	text-align: center;
}
#my_modal_box_for_garage .modal-dialog {
    max-width: 601px;
    margin: 30px auto;
    width: 100%;
}
</style>
<div id="my_modal_box_for_garage">
  <div class="modal fade" id="modal_garage" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="padding:10px 15px;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <b>Выберите автомобиль, в блокнот которого будет добавлена позиция</b>
        </div>
        <div class="modal-body" style="color:#000; padding:40px 50px;">
			<div id="add_bloknot_content">
				<?php
				$query = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = ?;');
				$query->execute( array($user_id) );
				echo '<select id="garage_auto" class="form-control">';
				echo '<option value="0">Общий блокнот</option>';
				while($car = $query->fetch())
				{
					echo '<option value="'.$car['id'].'">'. $car["mark"]." ".$car["model"]." ".$car["year"]." года - ". $car["caption"] .'</option>';
				}
				echo '</select>';
				?>
			</div>
			<div id="add_bloknot_msg"></div>
        </div>
        <div id="add_bloknot_btn" class="modal-footer">
			<a style="margin-bottom: 5px;" class="btn btn-ar btn-primary" onclick="add_bloknot();">Добавить в блокнот</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
var id_in_bloknot = -1;// id позиции корзины которую будем добавлять в блокнот
// Функция отображения блока добавления позиции в блокнот
function show_add_bloknot(id){
	<?php
	if(empty($user_id)){
	?>
	alert('Для добавления позиций в блокнот необходимо авторизоваться на сайте');
	return;
	<?php
	}
	?>
	id_in_bloknot = id;
	$("#modal_garage").modal();
}
// Функция добавления позиции в блокнот гаража
function add_bloknot(){
	if(id_in_bloknot >= 0){
		var n = document.getElementById("garage_auto").options.selectedIndex;
		var garage_id = document.getElementById("garage_auto").options[n].value;
		
		var Products = cart_records[getElementIndex(id_in_bloknot)];
		
		var request_object = new Object;
		request_object.manufacturer = encodeURIComponent(Products.manufacturer);
		request_object.article = encodeURIComponent(Products.article);
		request_object.name = encodeURIComponent(Products.name);
		request_object.exist = encodeURIComponent(Products.count_need);
		request_object.price = encodeURIComponent(Products.price);
		
		jQuery.ajax({
			type: "POST",
			async: true,
			url: "/content/shop/docpart/garage/ajax_add_to_notepad.php",
			dataType: "json",
			data: "garage="+garage_id+"&product="+JSON.stringify(request_object),
			success: function(answer)
			{
				var icon = '<i style="font-size: 30px; color: green;" class="fa fa-check"></i> ';
				if(answer.status != true){
					icon = '<i style="font-size: 30px; color: red;" class="fa fa-times"></i> ';
				}
				
				document.getElementById('add_bloknot_content').style.display = "none";
				document.getElementById('add_bloknot_btn').style.display = "none";
				document.getElementById('add_bloknot_msg').innerHTML = '<table><tr><td style="padding-right:5px;">'+icon+'</td><td>'+answer.message+'</td></tr></table>';
				
				setTimeout(function(){
					$("#modal_garage").modal('hide');
					
				}, 2000);
				
				setTimeout(function(){
					document.getElementById('add_bloknot_content').style.display = "block";
					document.getElementById('add_bloknot_btn').style.display = "block";
					document.getElementById('add_bloknot_msg').innerHTML = '';
				}, 2300);
			},
			error: function (e, ajaxOptions, thrownError){
				alert('Ошибка');
			}
		});
	}
}
</script>
<!-------------------------------------------- End ГАРАЖ -------------------------------------------->