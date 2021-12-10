<?php
defined('_ASTEXE_') or die('No access');

/**
Страница для управления выводом товаров на главной
*/
?>

<?php
if( !empty($_POST["save_action"]) )//Переход с сохранением структуры
{
	$tree_dump = json_decode($_POST["tree_json"], true);
	
	//Флаги ошибок
	$no_error_preclean = true;
	$no_error_groups = true;
	$no_error_products = true;
	
	
	//Предварительно очищаем таблицы товаров на главной
	if( ! $db_link->prepare("DELETE FROM `shop_main_page_groups`")->execute() )
	{
		$no_error_preclean = false;
	}
	if( ! $db_link->prepare("DELETE FROM `shop_main_page_products`")->execute() )
	{
		$no_error_preclean = false;
	}
	
	
	//Теперь сохраняем структуру
	if($no_error_preclean)
	{
		for($g=0; $g < count($tree_dump); $g++)//Группы
		{
			if(!$no_error_products)
			{
				break;
			}
			
			
			$caption = $tree_dump[$g]["value"];
			$show_caption = (int)$tree_dump[$g]["show_caption"];
			$active = (int)$tree_dump[$g]["active"];
			$order = $g + 1;

			if( ! $db_link->prepare("INSERT INTO `shop_main_page_groups` (`caption`, `order`, `show_caption`, `active`) VALUES (?,?,?,?);")->execute( array($caption, $order, $show_caption, $active) ) )
			{
				$no_error_groups = false;
				break;
			}
			
			$group_id = $db_link->lastInsertId();
			
			//Товары группы
			$products = $tree_dump[$g]["data"];
			for( $p = 0; $p < count($products); $p++ )
			{
				$product_id = $products[$p]["product_id"];
				$order = $p + 1;
				
				if( ! $db_link->prepare("INSERT INTO `shop_main_page_products` (`product_id`, `order`, `group_id`) VALUES (?,?,?);")->execute( array($product_id, $order, $group_id) ) )
				{
					$no_error_products = false;
					break;
				}
			}
		}
	}
	
	
	
	
	//Выводим результат работы
    //Выполнено без ошибок
    if($no_error_preclean && $no_error_groups && $no_error_products)
    {
        $success_message = "Выполнено успешно!";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/tovary-na-glavnoj?success_message=<?php echo $success_message; ?>";
        </script>
        <?php
        exit;
    }
    else
    {
        $error_message = "Возникли ошибки: <br>";
        if(!$no_error_preclean)
        {
            $error_message .= "Ошибка предварительной очистки таблиц<br>";
        }
        if(!$no_error_groups)
        {
            $error_message .= "Ошибка записи групп товаров<br>";
        }
		if(!$no_error_products)
        {
            $error_message .= "Ошибка записи товаров<br>";
        }
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/tovary-na-glavnoj?error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    }
}
else//Действий нет - выводит страницу
{
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
	
	
	//Исходные данные:
	$main_page_products_tree_dump_JSON = array();
	
	$groups_query = $db_link->prepare("SELECT * FROM `shop_main_page_groups` ORDER BY `order`;");
	$groups_query->execute();
	while( $group_record = $groups_query->fetch() )
	{
		$group = array("is_product"=>false, "product_id"=>0, '$level'=>1, '$parent'=>0, 'value'=>$group_record["caption"], "show_caption"=>$group_record["show_caption"], "active"=>$group_record["active"], "data"=>array() );
		
		//Запрос товаров
		$products_query = $db_link->prepare("SELECT *, (SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_main_page_products`.`product_id`) AS `caption` FROM `shop_main_page_products` WHERE `group_id` = ? ORDER BY `order`;");
		$products_query->execute( array($group_record["id"]) );
		while( $product_record = $products_query->fetch() )
		{
			array_push($group["data"], array("is_product"=>true, "product_id"=>$product_record['product_id'], '$level'=>2, '$parent'=>$group_record["id"], 'value'=>$product_record['caption']) );
		}
		
		array_push($main_page_products_tree_dump_JSON, $group);
	}
	
	$main_page_products_tree_dump_JSON = json_encode($main_page_products_tree_dump_JSON);
	?>
    
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="add_new_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить группу</div>
				</a>
				
				<a class="panel_a" onClick="delete_selected_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить элемент</div>
				</a>
				
				<a class="panel_a" onClick="unselect_tree();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять выделение</div>
				</a>
				
				<a class="panel_a" onClick="save_tree();" href="javascript:void(0);">
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
	
	
	<!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none">
        <input name="save_action" id="save_action" type="text" value="save_action" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
    </form>
    <!--Форма для отправки-->
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Структура вывода товаров на главной странице
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-6" id="content_info_div_col">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Свойства выделенного элемента
			</div>
			<div class="panel-body">
				<div id="content_info_div">
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	<script type="text/javascript" charset="utf-8">
	/*ДЕРЕВО*/
    //Для редактируемости дерева
    webix.protoUI({
        name:"edittree"
    }, webix.EditAbility, webix.ui.tree);
    //Формирование дерева
    tree = new webix.ui({
		
		//Шаблон элемента дерева
    	template:function(obj, common)//Шаблон узла дерева
		{
			var folder = common.folder(obj, common);
			var icon = "";
			var value_text = "<span>" + obj.value + "</span>";//Вывод текста
			
			//Индикация материала, снятого с публикации
			var icon_system = "";
			if(obj.active == false)
			{
				icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
				value_text = "<span style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
			}
			
			return common.icon(obj, common) + icon + folder + icon_system + value_text;
		},//~template
		
        editable:true,//редактируемое
        editValue:"value",
    	editaction:"dblclick",//редактирование по двойному нажатию
        container:"container_A",//id блока div для дерева
        view:"edittree",
    	select:true,//можно выделять элементы
    	drag:true,//можно переносить
    	editor:"text",//тип редактирование - текстовый
    });
	webix.event(window, "resize", function(){ tree.adjust(); })
    /*~ДЕРЕВО*/
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
	//После завершения редактирования
	tree.attachEvent("onAfterEditStop", function(id)
    {
    	onSelected();
    });
	//-----------------------------------------------------
	//Перед началом редактирования
	tree.attachEvent("onBeforeEditStart", function(id){
		node = tree.getItem(id);
		
		if(node['$level'] > 1)
		{
			return false;
		}
		
		return true;
	});
	//-----------------------------------------------------
	//Обработчик До перетаскивания узлов дерева
	tree.attachEvent("onBeforeDrop",function(context)
	{
	    var node_id = context.source;//ID переносимого элемента
	    var node_parent_id = tree.getItem(node_id).$parent;//ID исходного родителя элемента
	    var node_parent = tree.getItem(node_parent_id);
		
		
	    var target_id = context.target;//ID того элемента, на место которого переносим
	    var target_parent_id = tree.getItem(target_id).$parent;//ID целевого родителя элемента
		var target_parent = tree.getItem(target_parent_id);
		
		//Перенос допустим. 
		if(node_parent_id == target_parent_id)
		{
			return true;
		}
		
		//Перенос не допустим
		if( node_parent_id == 0 || target_parent_id == 0) //(т.е. один из них не равен 0)
		{
			alert("Не выполнено. Перенос элемента на уровень с другим типом невозможен");
			return false;
		}
		
		
		//Перенос товара. Проверяем дублирование товара
		var product_id = tree.getItem(node_id).product_id;//ID товара
		var first_product_node_id_target = tree.getFirstChildId(target_parent_id);//Первый элемент в уровне назначения
		if(first_product_node_id_target != null)
		{
			while(true)
			{
				if(tree.getItem(first_product_node_id_target).product_id == product_id)
				{
					alert("Не выполнено. В данной групе уже есть такой товар");
					return false;
				}
				
				first_product_node_id_target = tree.getNextSiblingId(first_product_node_id_target);//Следующий товар группы
				if(first_product_node_id_target == null)
				{
					break;
				}
			}
		}
		
		
		
		//Во всех остальных случая перенос будет допустим, т.к. уровень переносимого элемента не изменится
		
		return true;
	});
	//-----------------------------------------------------
    //Обработка выбора элемента
    function onSelected()
    {
        //Если категории не созданы
    	if(tree.count() == 0)
    	{
    	    document.getElementById("content_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("content_info_div_col").setAttribute("style", "display:none");
    	    return;
    	}
    	
    	//Выделенный узел
    	var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    document.getElementById("content_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("content_info_div_col").setAttribute("style", "display:none");
    	    return;
    	}
    	
		//Показать контейнер для параметров
		document.getElementById("content_info_div_col").setAttribute("style", "display:block");
		
		
    	var node = "";//Ссылка на объект узла
    	//Выделенный узел
    	node = tree.getItem(node_id);
    	
		
		//Далее в зависимости от типа элемента (группа/товар)
		var parameters_table_html = "";
		if( parseInt(node['$level']) == 1)//Группа
		{
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Группа товаров</label><div class=\"col-lg-6\"> "+node.value+" </div></div>";
			
			parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			var checked = "";
			if(node.show_caption == true)
			{
				checked = " checked=\"checked\" ";
			}
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Показывать название</label><div class=\"col-lg-6\"><input onchange=\"dynamicApplyingCheck('show_caption');\" type=\"checkbox\" id=\"show_caption\" "+checked+" class=\"form-control\"/></div></div>";
			
			parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			checked = "";
			if(node.active == true)
			{
				checked = " checked=\"checked\" ";
			}
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Активировать</label><div class=\"col-lg-6\"><input onchange=\"dynamicApplyingCheck('active');\" type=\"checkbox\" id=\"active\" "+checked+" class=\"form-control\"/></div></div>";
			
			
			parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			
			parameters_table_html += "<div class=\"col-lg-12\"> <button onclick=\"editGroupProducts();\" class=\"btn btn-info \" type=\"button\"><i class=\"fa fa-pencil\"></i> Редактировать список товаров</button> </div>";
		}
		else if( parseInt(node['$level']) == 2)
		{
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Товар: </label><div class=\"col-lg-6\"> "+node.value+" </div></div>";
			
			parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			parameters_table_html += "<div class=\"col-lg-12\"> <button onclick=\"delete_selected_item();\" class=\"btn btn-danger \" type=\"button\"><i class=\"fa fa-trash-o\"></i> Удалить из группы</button> </div>";
		}
		else
		{
			alert("Сбой построения дерева. Обратитесь к разработчику");
		}
		
		
		document.getElementById("content_info_div").innerHTML = parameters_table_html;
    }//function onSelected()
	//-----------------------------------------------------
	//Функция динамическиго применния значений
	function dynamicApplying(attribute)
	{
	    var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
    	
    	var str_value = document.getElementById(attribute).value;
    	
    	var str_handled = str_value.replace(/"/g, "&quot;");
    	
    	node[attribute] = str_handled;
	}
	//-----------------------------------------------------
	//Функция динамического применения значений чекбоксов
	function dynamicApplyingCheck(attribute)
	{
		var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
		
		if(document.getElementById(attribute).checked == true)
		{
			node[attribute] = 1;
		}
		else
		{
			node[attribute] = 0;
		}
		
		tree.refresh();
	}
    //-----------------------------------------------------
    //Событие при успешном редактировании элемента дерева
    tree.attachEvent("onValidationSuccess", function(){
        onSelected();
    });
    //-----------------------------------------------------
    tree.attachEvent("onAfterEditStop", function(state, editor, ignoreUpdate){
        //Задаем поле Alias - как транслитерация поля value;
        var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
        node.alias = iso_9_translit(node.value,  5);//5 - русский текст
        node.alias = node.alias.replace(/\s/g, '-');
        node.alias = node.alias.toLowerCase();
		node.alias = node.alias.replace(/[^\d\sA-Z\-_]/gi, '');//Убираем все символы кроме букв, цифр, тире и нинего подчеркивания
		
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
    	//Добавляем элемент в выделенный узел
    	var newItemId = tree.add( {value:"Новая группа", show_caption:false, active:true}, tree.count(), 0);
    	
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
    //Сохранение перечня категорий
    function save_tree()
    {
    	//Получаем строку JSON:
    	var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
    	
    	//Задаем значение поля в форме:
    	var tree_json_input = document.getElementById("tree_json");
    	tree_json_input.value = tree_dump;
    	
    	document.forms["form_to_save"].submit();//Отправляем
    }
    //-----------------------------------------------------
    //Инициализация редактора дерева материалов после загруки страницы
    function catalogue_start_init()
    {
    	var saved_catalogue = <?php echo $main_page_products_tree_dump_JSON; ?>;
	    tree.parse(saved_catalogue);
	    tree.openAll();
    }
    catalogue_start_init();
    onSelected();//Обработка текущего выделения
    </script>
	
	
	
	
	
	
	
	
	<!-- Модальное окно "Редактирование товаров группы" -->
	<div class="text-center m-b-md">
		<div class="modal fade" id="modalWindow_productsEdit" tabindex="-1" role="dialog"  aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="color-line"></div>
					<div class="modal-header">
						<h4 class="modal-title">Отметьте товары для вывода в группу</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div id="container_B" style="height:350px;">
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button onclick="catalogue_tree.checkAll();" class="btn btn-primary2 " type="button"><i class="fa fa-check-square"></i> <span class="bold">Отметить все</span></button>
						
						<button onclick="catalogue_tree.uncheckAll();" class="btn btn-primary " type="button"><i class="fa fa-square-o"></i> <span class="bold">Снять все</span></button>
						
						<button onclick="catalogue_tree.openAll();" class="btn btn-primary2 " type="button"><i class="fa fa-folder-open"></i> <span class="bold">Раскрыть все</span></button>
						
						<button onclick="catalogue_tree.closeAll();" class="btn btn-primary " type="button"><i class="fa fa-folder"></i> <span class="bold">Закрыть все</span></button>
						
						
						
						
						<button onclick="applyProductsChecks();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Применить</span></button>
					
					
						<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
	//-----------------------------------------------------
	//Кнопка "Список товаров группы"
	var catalogue_tree = "";
	function editGroupProducts()
	{
		//Сбрасываем старое дерево
		catalogue_tree = "";
		document.getElementById("container_B").innerHTML = "";
		
		//Формирование дерева каталога
		catalogue_tree = new webix.ui({
			
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
		
		webix.event(window, "resize", function(){ catalogue_tree.adjust(); });
		
		var catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
		catalogue_tree.parse(catalogue);
		
		
		//Отмечаем уже добавленные товары
		var group_node_id = tree.getSelectedId();
		var product_node_id = tree.getFirstChildId(group_node_id);//Первый товар группы
		var catalogue_tree_JSON = catalogue_tree.serialize();//Получаем JSON-представление дерева каталога
		console.log(catalogue_tree_JSON);
		if(product_node_id != null)
		{
			while(true)
			{
				var product_node = tree.getItem(product_node_id);//Объект товара
				var product_id = product_node.product_id;//ID товара
				
				checkProductNodeByProductId(product_id, catalogue_tree_JSON);//Отмечаем товар в дереве выбора товаров
				
				product_node_id = tree.getNextSiblingId(product_node_id);//Следующий товар группы
				if(product_node_id == null)
				{
					break;
				}
			}
		}
		
		
		
		
		//После отображения окна - подгоняем дерево под размер
		$('#modalWindow_productsEdit').on('shown.bs.modal',function(){
			catalogue_tree.adjust();
		});
		
		$('#modalWindow_productsEdit').modal();//Открыть окно
	}
	//-----------------------------------------------------
	//Функция выставления галочки для товаров, которые уже находятся в группе
	function checkProductNodeByProductId(product_id, catalogue_tree_JSON)
	{
		for(var i=0; i < catalogue_tree_JSON.length; i++)
        {
			if(catalogue_tree_JSON[i].product_id == product_id)
			{
				catalogue_tree.checkItem(catalogue_tree_JSON[i].id);
				return;
			}
			
			if(catalogue_tree_JSON[i]["data"] != null)
			{
				checkProductNodeByProductId(product_id, catalogue_tree_JSON[i]["data"]);
			}
		}
	}
	//-----------------------------------------------------
	//Кнопка "Применить" в окне выбора товара
	function applyProductsChecks()
	{
		//Предварительно очищаем ветку группы
		//Выделенный узел
    	var group_node_id = tree.getSelectedId();//ID выделенного узла
		while(true)
		{
			var firstProductNodeId = tree.getFirstChildId(group_node_id);
			if(firstProductNodeId == null)
			{
				break;
			}
			
			tree.remove(firstProductNodeId);
		}
		
		
		//Теперь по циклу добавляем узлы товаров в дерево
		var checked_products = catalogue_tree.getChecked();
		for(var i=0; i < checked_products.length; i++)
		{
			var product_node = catalogue_tree.getItem(checked_products[i]);//Объект узла товара
			
			
			var newItemId = tree.add( {value:product_node.value, product_id:product_node.product_id}, tree.count(), group_node_id);
		}
		tree.openAll();
		

		//Скрыть окно выбора товаров
		$('#modalWindow_productsEdit').modal('hide');
	}
	//-----------------------------------------------------
	</script>
	<?php
}
?>