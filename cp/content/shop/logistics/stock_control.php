<?php
/**
 * Сктраничный скрипт для управлениям наличием товара на складах
 * 
 * Суть: на странице отображается каталог товаров на основе справочника.
 * Кладовщик выбирает конкретный товар из каталога. Затем открывается страница товара с виджетами для управления наличием товара на складах, которые может редактировать данный поставщик
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if( ! empty($_POST["action"]) )//Есть действия
{
    
}
else//Действий нет - выводим страницу
{
    $is_products_mode = true;//Флаг - страница работает в режиме отображения товаров
    $category_block_type = 3;//Тип блоков категорий - для управления наличием (используется в /content/shop/catalogue/printCategories.php)
    
    //ID категории для отображения
    if(!empty($_GET["category_id"]))
    {
        $category_id = $_GET["category_id"];
    }
    else
    {
        $category_id = 0;
    }
    
    
    
    if($category_id > 0)
    {
        //Есть параметр category_id - нужно понять, является ли он конечным (count = 0)
        $category_record_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories` WHERE `id` = ?;");
		$category_record_query->execute( array($category_id) );
        $category_record = $category_record_query->fetch();
        
        if($category_record["count"] == 0)//Подкатегорий нет - значит отображаем товары
        {
            $is_products_mode = true;
            $product_block_type = 3;//Параметр для скрипта /content/shop/catalogue/printProducts.php - знать, как выводить товары
        }
        else
        {
            $is_products_mode = false;
        }
    }
    else
    {
        $is_products_mode = false;//Будем выводить категории (причем корневые)
    }
    
    

    //Решаем, что выводить:
    if($is_products_mode == false)//Подкатегории
    {
		?>
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					Выберите категорию товаров
				</div>
				<div class="panel-body">
				<?php
				//Общий скрипт вывода категорий в основную область страницы
				require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printCategories.php");
				?>
				</div>
			</div>
		</div>
		<?php
    }
    else//Товары
    {
        //START: Быстрое редактирование цен 
		?>
		<script>
		var storages_list = new Array();//Список складов для данного кладовщика
		
		<?php
		//Получаем склады, с которыми может работать этот пользователь
		$user_id = DP_User::getAdminId();
		//Получаем список складов с типом продукта 1:
		$SQL_SELECT_STORAGES = "SELECT
			`shop_storages`.`id` AS `id`,
			`shop_storages`.`name` AS `name`,
			`shop_storages`.`connection_options` AS `connection_options`,
			`shop_storages`.`interface_type` AS `interface_type`
			FROM
			`shop_storages`
			INNER JOIN `shop_storages_interfaces_types` ON `shop_storages`.`interface_type` = `shop_storages_interfaces_types`.`id`
			WHERE
			`shop_storages_interfaces_types`.`product_type` = 1 AND
			`shop_storages`.`users` LIKE ?;";
		
		$storages_query = $db_link->prepare($SQL_SELECT_STORAGES);
		$storages_query->execute( array('%'.$user_id.'%') );
		while( $storage = $storages_query->fetch() )
		{
			?>
			
			storages_list[storages_list.length] = new Object;
			storages_list[storages_list.length-1].id = parseInt(<?php echo $storage["id"]; ?>);
			storages_list[storages_list.length-1].name = '<?php echo $storage["name"]; ?>';
			<?php
		}
		?>
		//console.log(storages_list);
		</script>

		<script>
		var local_ids_counter = 1;//Счетчик для локальных ID
		var products_ids_pre = new Array();//ID товаров предыдущего отображения
		var products_ids = new Array();//ID товаров текущего отображения
		var products_ids_new = new Array();//ID товаров, которые есть в products_ids и которых нет в products_ids_pre (т.е. новые)
		var productsStoragesSupplies = new Array();//Переменная для хранения объекта описания записей поставок по каждому продукту, по каждому складу. Индекс массива первого уровня - ID товаров. Индексы ассивов второго уровня - ID складов. Третий уровень - список поставок
		
		// ----------------------------------------------------------------------------------------------------
		//Инициализация страницы для работы с быстрым редактированием цен. Вызов идет после получения HTML от ajax_get_products_page.php
		function init_quick_edit()
		{
			//1. Заполняем список отображенных товаров
			var products_work_quick_edit = document.getElementsByClassName("work_quick_edit");//Получаем блоки для виджетов быстрого редактирования
			products_ids = new Array();//Сбрасываем массив id продуктов
			for(var i=0; i < products_work_quick_edit.length; i++)//ПРОДУКТЫ
			{
				var product_id = parseInt(products_work_quick_edit[i].getAttribute("product_id"));
				products_ids.push(product_id);
			}
			// --------------------------------------
			//2. Удалить из объекта описания товары, которые не отображаются после обновления фильтра поиска
			for(var i = 0; i < products_ids_pre.length; i++)
			{
				if( products_ids.indexOf(products_ids_pre[i]) < 0 )//Если элемент не найден в обновленном списке
				{
					delete productsStoragesSupplies[products_ids_pre[i]];
				}
			}
			// --------------------------------------
			//3. Обработка контейнеров и объектов описания
			for(var i=0; i < products_ids.length; i++)//По всем отображенным товарам
			{
				var product_id = products_ids[i];
				
				//Если товара нет в объекте описания
				if(productsStoragesSupplies[product_id] == undefined)
				{
					productsStoragesSupplies[product_id] = new Array();//Создаем массив в объекте описания
					
					for(var s = 0; s < storages_list.length; s++)//СКЛАДЫ - добавляем в объект описания
					{
						var storage_id = storages_list[s].id;
						
						productsStoragesSupplies[product_id][storage_id] = new Array();//Добавляем склады в объект описания
					}
				}
				
				//Если контейнер и товара пустой - значит нужно добавить таблицы для складов
				if(document.getElementById("work_quick_edit_"+product_id).innerHTML == "")
				{
					//Добавляем контейнеры для каждого склада: HTML и JS-описание
					for(var s = 0; s < storages_list.length; s++)//СКЛАДЫ
					{
						var storage_id = storages_list[s].id;
						var storage_name = storages_list[s].name;
						
						var supplies_table = "<div class=\"table-responsive\"><table cellpadding=\"1\" cellspacing=\"1\" class=\"table table-condensed table-striped\"><thead><tr><th><input type=\"checkbox\" id=\"check_uncheck_all_"+product_id+"_"+storage_id+"\" onchange=\"on_check_uncheck_all("+product_id+", "+storage_id+");\"/></th><th>ID</th><th>Цена отпускная</th><th>Цена зачеркнутая</th><th>Цена закупа</th><th>Наличие</th><th>Зарезерв</th><th>Отпущено</th><th>Дата</th><th></th></tr></thead><tbody id=\"tbody_"+product_id+"_"+storage_id+"\"></tbody></table></div>";
						
						var storage_control = "<div align=\"left\"><a href=\"javascript:void(0);\" onclick=\"addRecord("+product_id+", "+storage_id+");\"><img src=\"/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/add.png\" class=\"col_img_popup\"></a> <a href=\"javascript:void(0);\" onclick=\"deleteCheckedRecords("+product_id+", "+storage_id+");\"><img src=\"/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png\" class=\"col_img_popup\"></a> Склад \""+storage_name+"\"</div>";
						
						document.getElementById("work_quick_edit_"+product_id).innerHTML +=  storage_control + supplies_table;
					}
				}
			}
			// --------------------------------------
			//4. Определение списка новых товаров (которых не было в предыдущем отображении)
			products_ids_new = new Array();//Сбрасываем массив новых товаров
			for(var i=0; i < products_ids.length; i++)
			{
				if( products_ids_pre.indexOf(products_ids[i]) < 0 )//Такого товара не было в предыдущем списке
				{
					products_ids_new.push(products_ids[i]);
				}
			}
			products_ids_pre = products_ids.slice();//Переопределяем список для ID в предыдущем отображении
			// --------------------------------------
			//5. Запрос поставок по товарам, которые еще не получали
			//Делаем запрос для получения всех поставок по всем складам по всем товарам
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/storage_data_quick_edit/ajax_get_supplies.php",
				dataType: "json",//Тип возвращаемого значения
				data: "storages="+encodeURIComponent(JSON.stringify(storages_list))+"&products="+encodeURIComponent(JSON.stringify(products_ids_new)),
				success: function(answer){
					console.log(answer);
					if(answer.status != "ok")
					{
						alert("Ошибка получения записей поставок");
						return;
					}
					//Распределить результат в объект описания поставок
					for(var s = 0; s < storages_list.length; s++)//СКЛАДЫ
					{
						var storage_id = storages_list[s].id;
						
						for(var p = 0; p < answer.storages[storages_list[s].id].length; p++)//ПОСТАВКИ
						{
							var product_id = answer.storages[storages_list[s].id][p].product_id;
							
							var supply = new Object;
							supply.id_local = local_ids_counter;local_ids_counter++;//Локальный ID
							supply.product_id = product_id;
							supply.category_id = <?php echo $category_id; ?>;
							supply.storage_id = storage_id;
							supply.id = answer.storages[storages_list[s].id][p].id;//ID поставки в системе склада
							supply.price = answer.storages[storages_list[s].id][p].price;
							supply.price_crossed_out = answer.storages[storages_list[s].id][p].price_crossed_out;
							supply.price_purchase = answer.storages[storages_list[s].id][p].price_purchase;
							supply.arrival_time = answer.storages[storages_list[s].id][p].arrival_time;
							supply.exist = answer.storages[storages_list[s].id][p].exist;
							supply.reserved = answer.storages[storages_list[s].id][p].reserved;
							supply.issued = answer.storages[storages_list[s].id][p].issued;
							supply.saved = true;
							
							productsStoragesSupplies[product_id][storage_id].push(supply);
						}
					}
					
					//Отображение
					var products_ids_for_draw = new Array();//ID товаров, для которых требуется перетображение
					//Если последнее действие - add, то делаем отображение только по новым товарам. Остальные уже отображены
					if(propucts_request.innerHTML_mode_current == "add")
					{
						products_ids_for_draw = products_ids_new.slice();
					}
					else if(propucts_request.innerHTML_mode_current == "refresh")//Если последнее действие - refresh - то нужно полностью переотобразить ВСЕ таблицы поставок
					{
						products_ids_for_draw = products_ids.slice();
					}
					console.log(propucts_request.innerHTML_mode_current);
					//Запуск отображения
					for(var i = 0; i < products_ids_for_draw.length; i++)//ТОВАРЫ
					{
						var product_id = products_ids_for_draw[i];
						
						for(var s = 0; s < storages_list.length; s++)//СКЛАДЫ
						{
							var storage_id = storages_list[s].id;
							
							showProductSuppliesInStore(product_id, storage_id);
						}
					}
				}
			}); 
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция получения HTML таблицы поставок для определенного товара в определенном складе
		function showProductSuppliesInStore(product_id, storage_id)
		{
			var tbody = document.getElementById("tbody_"+product_id+"_"+storage_id);
			var tbody_innerHTML = "";
			
			
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				var supply_object = productsStoragesSupplies[product_id][storage_id][i];
				
				var record_id = supply_object.id;
				if(record_id == 0)record_id = "Новая";
				
				var id_local = supply_object.id_local;
				
				tbody_innerHTML += "<tr>";
					tbody_innerHTML += "<td><input type=\"checkbox\" id=\"record_"+product_id+"_"+storage_id+"_"+id_local+"\" onchange=\"on_one_check_changed("+product_id+","+storage_id+", "+id_local+");\" class=\"form-control\" /></td>";
					tbody_innerHTML += "<td>"+record_id+"</td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"price_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'price');\" value=\""+supply_object.price+"\" onfocus=\"this.value = this.value;\" value_type=\"float\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"price_crossed_out_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'price_crossed_out');\" value=\""+supply_object.price_crossed_out+"\" onfocus=\"this.value = this.value;\" value_type=\"float\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"price_purchase_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'price_purchase');\" value=\""+supply_object.price_purchase+"\" onfocus=\"this.value = this.value;\" value_type=\"float\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"exist_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'exist');\" value=\""+supply_object.exist+"\" onfocus=\"this.value = this.value;\" value_type=\"int\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"reserved_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'reserved');\" value=\""+supply_object.reserved+"\" onfocus=\"this.value = this.value;\" value_type=\"int\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><input type=\"text\" id=\"issued_"+product_id+"_"+storage_id+"_"+id_local+"\" onKeyUp=\"dynamicApplying("+product_id+", "+storage_id+","+id_local+", 'issued');\" value=\""+supply_object.issued+"\" onfocus=\"this.value = this.value;\" value_type=\"int\" class=\"form-control\" /></td>";
					
					tbody_innerHTML += "<td><div style=\"position:relative;width:130px;height:34px;\">";
						//Для хранения метки времени
						tbody_innerHTML += "<input style=\"position:absolute; z-index:2; opacity:0;left:0;right:0;top:0;bottom:0;\" type=\"text\" id_local=\""+id_local+"\" storage_id=\""+storage_id+"\" product_id=\""+product_id+"\" id=\"arrival_time_"+product_id+"_"+storage_id+"_"+id_local+"\" value=\""+supply_object.arrival_time+"\" class=\"form-control\" />";
						//Для отображения даты поставки
						tbody_innerHTML += "<input style=\"position:absolute; z-index:1;left:0;\" type=\"text\" id=\"arrival_time_show_"+product_id+"_"+storage_id+"_"+id_local+"\" class=\"form-control\" />";
					tbody_innerHTML += "</div></td>";
					
					//Колонка "Управление"
					tbody_innerHTML += "<td>";
					
						//Кнопка Удалить
						tbody_innerHTML += "<a href=\"javascript:void(0);\" onclick=\"deleteRecord("+product_id+", "+storage_id+", "+id_local+");\">";
							tbody_innerHTML += "<img src=\"/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png\" class=\"col_img_popup\">";
						tbody_innerHTML += "</a> ";
						
						//Кнопка Сохранить
						if(supply_object.saved)
						{
							tbody_innerHTML += "<img src=\"/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save_gray.png\" class=\"col_img_popup\">";
						}
						else
						{
							tbody_innerHTML += "<a href=\"javascript:void(0);\" onclick=\"saveSupplyChanges("+product_id+" ,"+storage_id+", "+id_local+");\">";
								tbody_innerHTML += "<img src=\"/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png\" class=\"col_img_popup\">";
							tbody_innerHTML += "</a>";
						}
						
					tbody_innerHTML += "</td>";
				tbody_innerHTML += "</tr>";
			}
			
			tbody.innerHTML = tbody_innerHTML;
			
			datetimePickersInit(product_id, storage_id);//Теперь инициализируем датапикеры
		}
		// ----------------------------------------------------------------------------------------------------
		//Инициализация datetimePickers для записей одного склада
		function datetimePickersInit(product_id, storage_id)
		{
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				var id_local = productsStoragesSupplies[product_id][storage_id][i].id_local;
				
				//Инициализируем datetimepicker
				jQuery("#arrival_time_"+product_id+"_"+storage_id+"_"+id_local).datetimepicker({
					lang:"ru",
					closeOnDateSelect:true,
					closeOnTimeSelect:false,
					dayOfWeekStart:1,
					format:'unixtime',
					onClose:function(current_time, input)//При закрытии datetimepicker - отображаем в поле индикации
					{
						//showDatetime(current_time, input, 1);
					},
					onGenerate:function(current_time, input)//При создании datetimepicker - отображаем в поле индикации
					{
						showDatetime(current_time, input, 0);
					},
					onChangeDateTime:function(current_time, input)//При создании datetimepicker - отображаем в поле индикации
					{
						showDatetime(current_time, input, 1);
					},
				});
			}
		}
		// --------------------------------------------------------------------------------------
		//Метод отображения времени в понятном виде - в специальном поле (storage_id - склад, i - индекс записи в объекте описания)
		function showDatetime(current_time, input, after_edit)
		{
			var input_id = input[0].id;//Получаем id того input, к которому привязан этот датапикер
			var input = document.getElementById(input_id);//DOM инпута, из которого теперь можно получить storage_id и индекс записи продукта
			
			var storage_id = input.getAttribute("storage_id");
			var product_id = input.getAttribute("product_id");
			var id_local = input.getAttribute("id_local");
			
			var timestamp = parseInt(document.getElementById("arrival_time_"+product_id+"_"+storage_id+"_"+id_local).value)*1000;
			
			var time_string = "";
			
			var date_ob = new Date(timestamp);
			
			time_string += date_ob.getDate()+".";
			time_string += (date_ob.getMonth() + 1)+".";
			time_string += date_ob.getFullYear()+" ";
			
			time_string += date_ob.getHours()+":"+date_ob.getMinutes();
			
			//Если событие - после изменения - то ставим значение в объект описания и перерисовываем
			if(after_edit)
			{
				//Указываем новое значение в объект описания и перерисовыаем
				for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
				{
					if(productsStoragesSupplies[product_id][storage_id][i].id_local ==  id_local)
					{
						productsStoragesSupplies[product_id][storage_id][i].arrival_time = timestamp/1000;
						productsStoragesSupplies[product_id][storage_id][i].saved = false;
						showProductSuppliesInStore(product_id, storage_id);//Перерисовываем только для этого продукта и склада
						break;
					}
				}
			}
			else//Если событие - после генерации - просто показываем значение
			{
				document.getElementById("arrival_time_show_"+product_id+"_"+storage_id+"_"+id_local).value = time_string;//Показываем время в понятном виде
			}
			console.log("work");
		}
		// ----------------------------------------------------------------------------------------------------
		//Возвращает текущее положение каретки.
		function getCurrentCaretPosition(ctrl)
		{
			/**
				var ctrl - нужный input
			*/
			// IE < 9 Support
			if (document.selection) 
			{
				ctrl.focus();
				var range = document.selection.createRange();
				var rangelen = range.text.length;
				range.moveStart ('character', -ctrl.value.length);
				var start = range.text.length - rangelen;
				return {'start': start, 'end': start + rangelen };
			}
			// IE >=9 and other browsers
			else if (ctrl.selectionStart || ctrl.selectionStart == '0') 
			{
				return {'start': ctrl.selectionStart, 'end': ctrl.selectionEnd };
			} 
			else 
			{
				return {'start': 0, 'end': 0};
			}
		}
		// ----------------------------------------------------------------------------------------------------
		//Устанавливает каретку в нужное положение
		function setCurrentCaretPosition(ctrl, position)
		{
			/**
				var ctrl - нужный input
				var position - объект возвращаемый функцией getCurrentCaretPosition(ctrl);
			*/
			// IE >= 9 and other browsers
			if(ctrl.setSelectionRange)
			{
				ctrl.focus();
				ctrl.setSelectionRange(position.start, position.end);
			}
			// IE < 9
			else if (ctrl.createTextRange) 
			{
				var range = ctrl.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция динамическиго применения значений
		function dynamicApplying(product_id, storage_id, id_local, parameter)
		{
			var id_input = parameter+"_"+product_id+"_"+storage_id+"_"+id_local;
			
			var input = document.getElementById(id_input);
			var value = input.value;
			
			var currentPosition = getCurrentCaretPosition(input);
			
			//Заменяем запятые на точки
			value = value.replace(',', '.');
			
			//Указываем новое значение в объект описания и перерисовыаем
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(productsStoragesSupplies[product_id][storage_id][i].id_local ==  id_local)
				{
					productsStoragesSupplies[product_id][storage_id][i][parameter] = value;
					productsStoragesSupplies[product_id][storage_id][i].saved = false;
					showProductSuppliesInStore(product_id, storage_id);//Перерисовываем только для этого продукта и склада
					//Определяем input после перерисовки 
					var input = document.getElementById(id_input);
					//Устанавливаем каретку
					setCurrentCaretPosition(input, currentPosition);
					
					// jQuery('#'+parameter+"_"+product_id+"_"+storage_id+"_"+id_local).focus(); //Старый метод
					break;
				}
			}
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция добавления поставки
		function addRecord(product_id, storage_id)
		{
			console.log("Добавляем поставку продукта "+product_id+" в склад "+storage_id);
			
			//1. Создаем запись поставки
			var supply = new Object;
			supply.id_local = local_ids_counter;local_ids_counter++;//Локальный ID
			supply.product_id = product_id;
			supply.category_id = <?php echo $category_id; ?>;
			supply.storage_id = storage_id;
			supply.id = 0;//ID поставки в системе склада
			supply.price = 0;
			supply.price_crossed_out = 0;
			supply.price_purchase = 0;
			supply.arrival_time = parseInt(Date.now()/1000);
			supply.exist = 0;
			supply.reserved = 0;
			supply.issued = 0;
			supply.saved = false;
			
			//2. Добавляем запись в объект описания
			productsStoragesSupplies[product_id][storage_id].push(supply);
			
			//3. Перерисовываем область Продукт-Склад
			showProductSuppliesInStore(product_id, storage_id);
		}
		// ----------------------------------------------------------------------------------------------------
		//Удалеление одной записи
		var deleteServerOk = false;//Флаг - говорит о том, что записи с сервера были удалены успешно
		function deleteRecord(product_id, storage_id, id_local)
		{
			console.log("Удаляем запись продукта "+product_id+" склада "+storage_id+". id_local "+id_local);
			
			//1. Удаляем запись из объекта описания и сервера
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(productsStoragesSupplies[product_id][storage_id][i].id_local ==  id_local)
				{
					//1. Проверяем, является ли данная поставка новой (т.е. ее еще не успели сохранить на сервере)
					if(productsStoragesSupplies[product_id][storage_id][i].id == 0)//Поставка новая - просто удаляем
					{
						productsStoragesSupplies[product_id][storage_id].splice(i, 1);//Удаляем поставку из объекта описания
					}
					else//Поставка была ранее сохранена на сервере, поэтому - сначала удаляем ее с сервера
					{
						deleteServerOk = false;
						
						//Серверный скрипт удаляет целый список, поэтому приводим к массиву
						var storage_supplies = new Array();
						storage_supplies.push(productsStoragesSupplies[product_id][storage_id][i].id);
						
						jQuery.ajax({
							type: "POST",
							async: false, //Запрос синхронный !!!
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/storage_data_quick_edit/ajax_delete_supplies_product_storage.php",
							dataType: "json",//Тип возвращаемого значения
							data: "storage="+storage_id+"&product="+product_id+"&supplies="+encodeURIComponent(JSON.stringify(storage_supplies)),
							success: function(answer){
								if(answer.status == true)
								{
									deleteServerOk = true;
								}
								else
								{
									deleteServerOk = false;
								}
							}
						}); 
						
						if(deleteServerOk)
						{
							productsStoragesSupplies[product_id][storage_id].splice(i, 1);//Удаляем поставку из объекта описания
						}
						else
						{
							alert("Серверная ошибка удаления записи");
							return;
						}
						
					}
					break;
				}
			}
			
			//2. Перерисовываем область
			showProductSuppliesInStore(product_id, storage_id);
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция удаления отмеченных поставок
		function deleteCheckedRecords(product_id, storage_id)
		{
			console.log("Удаляем отмеченные поставки для продукта "+product_id+" на складе "+storage_id);
			console.log("Отмечены id_local");
			console.log( getCheckedElements(product_id, storage_id) );
			
			//1. Получаем список id_local удаляемых записей
			var id_local_list = getCheckedElements(product_id, storage_id);
			
			
			
			//2. Распределяем удаляемые записи на "локальные" и "серверные"
			var storage_supplies = new Array();
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(productsStoragesSupplies[product_id][storage_id][i].id > 0)
				{
					storage_supplies.push(productsStoragesSupplies[product_id][storage_id][i].id);
				}
			}
			
			//2. Делаем запрос на удаление серверных записей
			if(storage_supplies.length > 0)
			{
				deleteServerOk = false;
			
				jQuery.ajax({
					type: "POST",
					async: false, //Запрос синхронный !!!
					url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/storage_data_quick_edit/ajax_delete_supplies_product_storage.php",
					dataType: "json",//Тип возвращаемого значения
					data: "storage="+storage_id+"&product="+product_id+"&supplies="+encodeURIComponent(JSON.stringify(storage_supplies)),
					success: function(answer){
						if(answer.status == true)
						{
							deleteServerOk = true;
						}
						else
						{
							deleteServerOk = false;
						}
					}
				}); 
			}
			else
			{
				deleteServerOk = true;
			}
			
			
			
			
			//3. В случае успеха - удаляем все (и локальные и серверные) записи из объекта описания и перерисовываем облать
			if(deleteServerOk)
			{
				for(var l = 0; l < id_local_list.length; l++)
				{
					for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
					{
						if(productsStoragesSupplies[product_id][storage_id][i].id_local == id_local_list[l])
						{
							productsStoragesSupplies[product_id][storage_id].splice(i, 1);//Удаляем поставку из объекта описания
							break;
						}
					}
				}
				showProductSuppliesInStore(product_id, storage_id);
				
				//Снимаем отметку с общего чекбокса
				document.getElementById("check_uncheck_all_"+product_id+"_"+storage_id).checked = false;
			}
			else
			{
				alert("Серверная ошибка удаления записей поставок");
			}
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция сохранения изменений в поставке
		var saveServerOk = false;//Флаг - говорит о том, что изменения сохранены успешно на сервере
		function saveSupplyChanges(product_id, storage_id, id_local)
		{
			console.log("Сохраняем изменения для товара "+product_id+" склад "+storage_id+" id_local "+id_local);
			
			
			//Создаем массив с единственным объектом поставки
			var supplies_objects = new Array();
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(productsStoragesSupplies[product_id][storage_id][i].id_local == id_local)
				{
					if( ! checkSupply(productsStoragesSupplies[product_id][storage_id][i]) )
					{
						continue;
					}
					
					
					supplies_objects.push(productsStoragesSupplies[product_id][storage_id][i]);
					break;
				}
			}
			
			
			if(supplies_objects.length == 0)
			{
				return;
			}
			
			//Передаем массив с поставкой на сервер для сохранения данных
			jQuery.ajax({
				type: "POST",
				async: false, //Запрос синхронный !!!
				url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/logistics/storage_data_quick_edit/ajax_saveSuppliesChanges_product_storage.php",
				dataType: "json",//Тип возвращаемого значения
				data: "storage="+storage_id+"&product="+product_id+"&supplies_objects="+encodeURIComponent(JSON.stringify(supplies_objects)),
				success: function(answer){
					if(answer.status == "ok")
					{
						//Обрабатываем полученный ответ
						for(var sup=0; sup < answer.supplies_objects.length; sup++)
						{	
							if(answer.supplies_objects[sup].no_error == 0)
							{
								alert("Серверная ошибка при сохранении изменений поставки id_local "+answer.supplies_objects[sup].id_local);
								continue;
							}
							
							//Изменения данной записи были сохранены успешно.
							var product_id = answer.supplies_objects[sup].product_id;
							var storage_id = answer.supplies_objects[sup].storage_id;
							var id_local = answer.supplies_objects[sup].id_local;
							var is_new = answer.supplies_objects[sup].is_new;
							var id = answer.supplies_objects[sup].id;
							
							for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)
							{
								if(productsStoragesSupplies[product_id][storage_id][i].id_local == id_local)
								{
									if(is_new)//Если запись новая - указываем ID, которое было присвоено на сервере
									{
										productsStoragesSupplies[product_id][storage_id][i].id = id;
									}
									
									productsStoragesSupplies[product_id][storage_id][i].saved = true;//Указываем, что изменения сохранены
									
									break;
								}
							}
							//Перерисовываем область
							showProductSuppliesInStore(product_id, storage_id);
						}
					}
				}
			});
		}
		// ----------------------------------------------------------------------------------------------------
		//Функция проверки корректности значений полей в записи поставки
		function checkSupply(supply)
		{
			//Цена отпускная
			var price = parseFloat(supply.price);
			if( ! (Number(price) === price && price >= 0) )
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Цена отпускная\". Необходимо использовать положительные числа");
				return false;
			}
			//Зачеркнутая цена
			var price_crossed_out = parseFloat(supply.price_crossed_out);
			if( ! (Number(price_crossed_out) === price_crossed_out && price_crossed_out >= 0) )
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Цена зачеркнутая\". Необходимо использовать положительные числа");
				return false;
			}
			//Зачеркнутая закупочная
			var price_purchase = parseFloat(supply.price_purchase);
			if( ! (Number(price_purchase) === price_purchase && price_purchase >= 0) )
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Зачеркнутая закупочная\". Необходимо использовать положительные числа");
				return false;
			}
			//Наличие
			var exist = parseInt(supply.exist);
			if(isNaN(exist) || exist < 0)
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Наличие\". Необходимо использовать целые положительные числа");
				return false;
			}
			//Зарезервировано
			var reserved = parseInt(supply.reserved);
			if(isNaN(reserved) || reserved < 0)
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Зарезервировано\". Необходимо использовать целые положительные числа");
				return false;
			}
			//Отпущено
			var issued = parseInt(supply.issued);
			if(isNaN(issued) || issued < 0)
			{
				alert("В поставке id_local "+supply.id_local+" не корректное значение поля \"Отпущено\". Необходимо использовать целые положительные числа");
				return false;
			}
			
			return true;
		}
		// ----------------------------------------------------------------------------------------------------
		</script>
		
		<script>
		//ОБРАБОТКА ЧЕКБОКСОВ ДЛЯ ЗАПИСЕЙ
		// ----------------------------------------------------------------------
		//Обработка переключения Выделить все/Снять все
		function on_check_uncheck_all(product_id, storage_id)
		{
			var state = document.getElementById("check_uncheck_all_"+product_id+"_"+storage_id).checked;
			
			
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				document.getElementById("record_"+product_id+"_"+storage_id+"_"+productsStoragesSupplies[product_id][storage_id][i].id_local).checked = state;
			}
		}//~function on_check_uncheck_all()
		// ----------------------------------------------------------------------
		//Обработка переключения одного чекбокса
		function on_one_check_changed(product_id, storage_id, id_local)
		{
			//Если хотя бы один чекбокс снят - снимаем общий чекбокс
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(document.getElementById("record_"+product_id+"_"+storage_id+"_"+productsStoragesSupplies[product_id][storage_id][i].id_local).checked == false)
				{
					document.getElementById("check_uncheck_all_"+product_id+"_"+storage_id).checked = false;
					break;
				}
			}
		}//~function on_one_check_changed(id)
		// ----------------------------------------------------------------------
		//Получение массива id_local отмеченых записей одного склада одного продукта
		function getCheckedElements(product_id, storage_id)
		{
			var checked_ids = new Array();
			
			for(var i=0; i < productsStoragesSupplies[product_id][storage_id].length; i++)//ПОСТАВКИ ПРОДУКТА В ДАННОМ СКЛАДЕ
			{
				if(document.getElementById("record_"+product_id+"_"+storage_id+"_"+productsStoragesSupplies[product_id][storage_id][i].id_local).checked == true)
				{
					checked_ids.push(productsStoragesSupplies[product_id][storage_id][i].id_local);
				}
			}

			return checked_ids;
		}
		// ----------------------------------------------------------------------
		</script>
		
		<?php //END: Быстрое редактирование цен ?>
		
		
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					<div class="panel-tools">
						<a class="showhide"><i class="fa fa-chevron-up"></i></a>
					</div>
				
					Действия
				</div>
				<div class="panel-body">
					
					<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Выход</div>
					</a>
					
					<a class="panel_a right-sidebar-toggle" style="float:right;" id="sidebar" href="javascript:void(0);">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/filter.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Фильтр свойств товаров</div>
					</a>
				</div>
			</div>
		</div>
		
		
        <?php
        //Общий скрипт вывода товаров в основную область страницы
        require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printProducts.php");
    }//~else - выводим Товары
}
?>