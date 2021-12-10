<?php
/**
Страничный скрипт для редактирования сопутствующих товаров
*/
?>

<?php
if( ! empty($_POST["save_action"]) )
{
	$related_products_map = json_decode($_POST["related_products_map"], true);
	var_dump($related_products_map);
	
	
	//Полностью очищаем таблицу сопутсвующих товаров
	$no_error_preclean = true;

	if( $db_link->prepare("DELETE FROM `shop_related_products`")->execute() != true )
	{
		$no_error_preclean = false;
	}	
	
	
	
	//Создаем новые записи сопутсвующих товаров
	$no_error_save = true;
	foreach( $related_products_map AS $key => $related_products_list )
	{
		$product_id = str_replace("_str", "",$key);
		
		for($i=0; $i < count($related_products_list); $i++)
		{
			$order = $i + 1;
			$product_id_related = $related_products_list[$i]["product_id"];

			if( $db_link->prepare("INSERT INTO `shop_related_products` (`product_id`, `product_id_related`, `order`) VALUES (?,?,?);")->execute( array($product_id, $product_id_related, $order) ) != true)
			{
				$no_error_save = false;
			}
		}
	}
	
	
	
	
	//Выводим результат работы
    //Выполнено без ошибок
    if($no_error_preclean && $no_error_save)
    {
        $success_message = "Выполнено успешно!";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/soputstvuyushhie-tovary?success_message=<?php echo $success_message; ?>";
        </script>
        <?php
        exit;
    }
    else
    {
        $error_message = "Возникли ошибки: <br>";
        if(!$no_error_preclean)
        {
            $error_message .= "Ошибка предварительной очистки таблицы<br>";
        }
        if(!$no_error_save)
        {
            $error_message .= "Ошибка записи сопутсвующих товаров<br>";
        }
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/soputstvuyushhie-tovary?error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    }
}
else//Действий нет - выводим страницу
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
	?>
	
	<?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	
	
	
	<!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none">
        <input name="save_action" id="save_action" type="text" value="save_action" style="display:none"/>
        <input name="related_products_map" id="related_products_map" type="text" value="" style="display:none"/>
    </form>
    <!--Форма для отправки-->
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="unselect_tree();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять выделение</div>
				</a>
				
				<a class="panel_a" onClick="save_action();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Каталог товаров (Основные товары)
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-6" id="related_products_div_col">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Сопутствующие товары
			</div>
			<div class="panel-body">
				<div id="container_C" style="height:316px;">
				</div>
				<div class="row">
					<div class="col-lg-12"> <button onclick="editRelatedProducts();" class="btn btn-info " type="button"><i class="fa fa-pencil"></i> Редактировать список</button> </div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	<script>
	var related_products_map = new Object();//Карта сопутствующих товаров, которая используется для локального хранения связи основных и сопутствующих товаров.
	//Ключ каждого поля этого объекта записывается в форме: <product_id>_str
	//_str - это чтобы не раздвувать этот объект на массив с пустыми полями
	
	<?php
	//ИНИЦИАЛИЗАЦИЯ ИЗ БАЗЫ ДАННЫХ ПРИ ОТКРЫТИИ СТРАНИЦЫ
	$main_product_ids_query = $db_link->prepare("SELECT DISTINCT(`product_id`) FROM `shop_related_products`;");
	$main_product_ids_query->execute();
	while( $product_id_record = $main_product_ids_query->fetch() )
	{
		?>
		related_products_map["<?php echo $product_id_record["product_id"]; ?>_str"] = new Array();//Создаем массив для сопутсвующих товаров
		<?php
		//Запрос сопутсвующих товаров
		$related_products_list_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_related_products`.`product_id_related`) AS `product_related_name` FROM `shop_related_products` WHERE `product_id` = ? ORDER BY `order`;");
		$related_products_list_query->execute( array($product_id_record["product_id"]) );
		while( $relation = $related_products_list_query->fetch() )
		{
			?>
			related_products_map["<?php echo $product_id_record["product_id"]; ?>_str"].push({product_id:<?php echo $relation["product_id_related"]; ?>, value:'<?php echo $relation["product_related_name"]; ?>'});
			<?php
		}
	}
	

	
	?>
	
	
	//Формирование дерева каталога
	var catalogue_tree = new webix.ui({
		editable:false,//редактируемое
		container:"container_A",//id блока div для дерева
		view:"tree",
		select:"multiselect",//можно выделять элементы
		drag:false,//можно переносить
	});
	
	var catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
	catalogue_tree.parse(catalogue);
	catalogue_tree.openAll();
	webix.event(window, "resize", function(){ catalogue_tree.adjust(); })
	
	
	// ----------------------------------------------------------------------------------------------------
	//Событие перед выбором элемента дерева
    catalogue_tree.attachEvent("onBeforeSelect", function(id)
    {
    	var node = catalogue_tree.getItem(id);
		
		if(node.is_product == false)
		{
			alert("Нельзя выделить категорию. Сопутствующие товары можно указать только товарам");
			onSelected();
			return false;
		}
		else
		{
			//ЗДЕСЬ - СДЕЛАТЬ ОБРАБОТКУ КОНФЛИКТА
			var selected_ids = catalogue_tree.getSelectedId(true);//ID выделенного узла. true - массив
			if(selected_ids.length > 0)//Если уже есть выделенные элементы - проверяем конфликт сопутствующих товаров
			{
				if( ! checkDifferentRelations(id) )//Проверяем наличие конфликта
				{//Есть конфликт - предлагаем синхронизировать списки
					if( confirm("Внимание! Выявлено отличие списков сопутсвующих товаров у выделенных товаров и у выделяемого товара. Нажмите Отмена, чтобы прервать выделение этого товара. Нажмите Ок, чтобы заменить список сопутсвующих товаров для выделяемого товара") )
					{
						//СИНХРОНИЗИРУЕМ СПИСКИ. Т.е. список выделяемого товара заменяем на список одного из выделенных товаров
						related_products_map[ catalogue_tree.getItem(id).product_id + "_str" ] = related_products_map[ catalogue_tree.getItem(selected_ids[0]).product_id + "_str" ];
						
						return true;
					}
					else//Прерываем выделение
					{
						return false;
					}
				}
				else//Сопутствующие товары у выделяемого элемента и уже выделынных одинаковые - конфликта нет
				{
					return true;
				}
			}
			else//Выделенных элементов нет, значит и конфликта нет
			{
				return true;
			}
		}
    });
	// ----------------------------------------------------------------------------------------------------
	//Событие при выборе элемента дерева
    catalogue_tree.attachEvent("onAfterSelect", function(id)
    {
    	onSelected();
    });
	// ----------------------------------------------------------------------------------------------------
	//Снятие выделения с дерева
    function unselect_tree()
    {
    	catalogue_tree.unselect();
    	onSelected();
    }
	// ----------------------------------------------------------------------------------------------------
	var related_products_list = "";//Переменная для хранения дерева сопутсвующих товаров для правого блока
	/*
	product_id => array(дерево сопутсвующих товаров)
	*/
	//Обработка выбора элемента в дереве основных товаров
    function onSelected()
    {
		//0 НЕТ ВЫДЕЛЕННЫХ ОСНОВНЫХ ТОВАРОВ
        //Выделенные узлы
    	var selected_ids = catalogue_tree.getSelectedId(true);//ID выделенного узла. true - массив
    	if(selected_ids.length == 0)
    	{
    	    document.getElementById("container_C").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("related_products_div_col").setAttribute("style", "display:none");
    	    return;
    	}
		

		//1 ИНИЦИАЛИЗАЦИЯ
		//Есть выделенные узлы основных товаров - инициализируем список их сопутствующих товаров
		related_products_list = "";//Сбрасываем переменную списка
		document.getElementById("container_C").innerHTML = "";//Сбрасываем линейный список сопутсвующих товаров
		
		
		//2 ГОТОВИМ СПИСОК СОПУТСТВУЮЩИХ ТОВАРОВ ДЛЯ ВЫДЕЛЕННЫХ ОСНОВНЫХ
		var related_products_actual = new Array();//Массив, которым нужно инициализировать список сопутсвующих товаров. Берется на основе выделенных основных товаров
		
		//Берем список сопутствующих товаров от первого элемента. Если функция выполняется здесь, значит, у остальных списки такие же
		related_products_actual = related_products_map[ catalogue_tree.getItem(selected_ids[0]).product_id + "_str" ];
		

		
		//3 СПИСОК СОПУТСТВУЮЩИХ
		//Инициализируем линейный список сопутсвующих товаров
		related_products_list = new webix.ui({
			editable:false,//редактируемое
			container:"container_C",//id блока div для дерева
			view:"tree",
			select:true,//можно выделять элементы
			drag:true,//можно переносить
		});
		related_products_list.parse(related_products_actual);//Показываем список сопутствующих товаров
		webix.event(window, "resize", function(){ related_products_list.adjust(); });
		
		
		//4 ОТОБРАЖЕНИЕ
		//Показать контейнер
		document.getElementById("related_products_div_col").setAttribute("style", "display:block");
		related_products_list.adjust();
    }//function onSelected()
	// ----------------------------------------------------------------------------------------------------
	//Функция проверки отличающихся сопутствующих товаров при одновременном выделении
	function checkDifferentRelations(new_id)
	{
		//Выделенные узлы
    	var selected_ids = catalogue_tree.getSelectedId(true);//ID выделенного узла. true - массив
		
		//Сравниваем сопутствующие товары выделяемого элемента (его id получаем в аргументе) и одного из выделенных
		var new_list = related_products_map[ catalogue_tree.getItem(new_id).product_id + "_str" ];
		var active_list = related_products_map[ catalogue_tree.getItem(selected_ids[0]).product_id + "_str" ];
		
		//Приводим к массивам
		if(new_list == undefined)
		{
			new_list = new Array();
		}
		if(active_list == undefined)
		{
			active_list = new Array();
		}
		
		//Формируем строку для сравнения последнего товара
		var new_list_STR = "";
		for(var i=0; i < new_list.length; i++)
		{
			if( i > 0)
			{
				new_list_STR += "_";
			}
			new_list_STR += new_list[i].product_id;
		}
		//Формируем строку для сравнения предпоследнего товара
		var active_list_STR = "";
		for(var i=0; i < active_list.length; i++)
		{
			if( i > 0)
			{
				active_list_STR += "_";
			}
			active_list_STR += active_list[i].product_id;
		}
		
		
		if( new_list_STR != active_list_STR )
		{
			console.log("РАЗНЫЕ");
			console.log(new_list_STR);
			console.log(active_list_STR);
			return false;
		}
		else
		{
			console.log("ОДНАКОВЫЕ");
			return true;
		}
	}
	// ----------------------------------------------------------------------------------------------------
	//Кнопка Сохранить
	function save_action()
	{
		//Получаем строку JSON:
    	related_products_map_DUMP = JSON.stringify(related_products_map);
    	
    	//Задаем значение поля в форме:
    	document.getElementById("related_products_map").value = related_products_map_DUMP;
    	
    	document.forms["form_to_save"].submit();//Отправляем
	}
	// ----------------------------------------------------------------------------------------------------
	
	//Инициализация после загрузки страницы:
	onSelected();
	</script>
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<!-- Модальное окно "Выбор сопутствующих товаро" -->
	<div class="text-center m-b-md">
		<div class="modal fade" id="modalWindow_relatedProducts" tabindex="-1" role="dialog"  aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="color-line"></div>
					<div class="modal-header">
						<h4 class="modal-title">Отметьте товары</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="container_B" style="height:350px;">
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button onclick="catalogue_tree_related.checkAll();" class="btn btn-primary2 " type="button"><i class="fa fa-check-square"></i> <span class="bold">Отметить все</span></button>
						
						<button onclick="catalogue_tree_related.uncheckAll();" class="btn btn-primary " type="button"><i class="fa fa-square-o"></i> <span class="bold">Снять все</span></button>
						
						<button onclick="catalogue_tree_related.openAll();" class="btn btn-primary2 " type="button"><i class="fa fa-folder-open"></i> <span class="bold">Раскрыть все</span></button>
						
						<button onclick="catalogue_tree_related.closeAll();" class="btn btn-primary " type="button"><i class="fa fa-folder"></i> <span class="bold">Закрыть все</span></button>
						
						
						
						
						<button onclick="applyRelatedProducts();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Применить</span></button>
					
					
						<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
	//-----------------------------------------------------
	//Кнопка "Редактировать список сопутствующих товаров"
	var catalogue_tree_related = "";
	function editRelatedProducts()
	{
		//Сбрасываем старое дерево
		catalogue_tree_related = "";
		document.getElementById("container_B").innerHTML = "";
		
		//Формирование дерева каталога
		catalogue_tree_related = new webix.ui({
			
			//Шаблон элемента дерева
			template:function(obj, common)//Шаблон узла дерева
        	{
                var folder = common.folder(obj, common);
        	    var value_text = "<span>" + obj.value + "</span>";//Вывод текста
				var checkbox = "";
				
        	    //Чекбоксы только для товаров
				if(obj.is_product == true)
                {
                    checkbox = common.checkbox(obj, common);
                }
				
                return common.icon(obj, common) + checkbox + folder + value_text;
        	},//~template
			
			
			editable:false,//редактируемое
			container:"container_B",//id блока div для дерева
			view:"tree",
			select:false,//можно выделять элементы
			drag:false,//можно переносить
		});
		
		webix.event(window, "resize", function(){ catalogue_tree_related.adjust(); });
		
		var catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
		catalogue_tree_related.parse(catalogue);
		
		
		
		
		
		//Отмечаем товары, которые уже являются сопутстующими
		var selected_ids = catalogue_tree.getSelectedId(true);//ID выделенного узла. true - массив
		var related_products_actual = related_products_map[ catalogue_tree.getItem(selected_ids[0]).product_id + "_str" ];//Получаем список сопутствующих товаров от первого выделенного элемента. У остальных выделенных элементов список сопутствующих товаров будет такой же
		if( related_products_actual == undefined)//Приводим к массиву
		{
			related_products_actual = new Array;
		}
		

		//Отмечаем товары, которые уже являются сопутстующими
		var catalogue_tree_related_JSON = catalogue_tree_related.serialize();//Получаем JSON-представление дерева каталога
		for(var i=0; i < related_products_actual.length; i++)
		{
			checkProductNodeByProductId(related_products_actual[i].product_id, catalogue_tree_related_JSON);//Отмечаем товар в дереве выбора товаров
		}
		
		
		//После отображения окна - подгоняем дерево под размер
		$('#modalWindow_relatedProducts').on('shown.bs.modal',function(){
			catalogue_tree_related.adjust();
		});
		
		$('#modalWindow_relatedProducts').modal();//Открыть окно
	}
	//-----------------------------------------------------
	//Функция выставления галочки для товаров, которые уже являются сопутствующими при открытии модального окна
	function checkProductNodeByProductId(product_id, catalogue_tree_related_JSON)
	{
		for(var i=0; i < catalogue_tree_related_JSON.length; i++)
        {
			if(catalogue_tree_related_JSON[i].product_id == product_id)
			{
				catalogue_tree_related.checkItem(catalogue_tree_related_JSON[i].id);
				return;
			}
			
			if(catalogue_tree_related_JSON[i]["data"] != null)
			{
				checkProductNodeByProductId(product_id, catalogue_tree_related_JSON[i]["data"]);
			}
		}
	}
	//-----------------------------------------------------
	//Кнопка "Применить" в окне выбора товара
	function applyRelatedProducts()
	{
		//1. Получаем список отмеченных товаров и формируем на его основе массив с указанием id и наименования товаров
		var edited_list = new Array();//Список с объектами отмеченных товаров
		var checked_products = catalogue_tree_related.getChecked();
		for(var i=0; i < checked_products.length; i++)
		{
			var product_node = catalogue_tree_related.getItem(checked_products[i]);//Объект узла товара
			
			edited_list.push({value:product_node.value, product_id:product_node.product_id});
		}

		
		//2. Записываем этот массив в карту сопутствующих товаров. related_products_map
		var selected_ids = catalogue_tree.getSelectedId(true);//ID выделенного узла. true - массив
		for(var i=0; i < selected_ids.length; i++)
		{
			var main_product_node = catalogue_tree.getItem(selected_ids[i]);
			
			var product_id = main_product_node.product_id;
			
			related_products_map[product_id + "_str"] = edited_list;
		}
		
		
		
		//3. Переотображаем список сопутствующих товаров
		onSelected();
		

		//4. Скрыть окно выбора сопутствующих товаров товаров
		$('#modalWindow_relatedProducts').modal('hide');
		
	}
	//-----------------------------------------------------
	</script>
	
	<?php
}
?>