<?php
/**
 * Скрипт для страницы редактирования дерева стран и городов
*/
defined('_ASTEXE_') or die('No access');

require_once("content/shop/geo/dp_geo_node_record.php");//Определение класса географического узла
?>

<?php
// --------------------------------- Start PHP - метод ---------------------------------
//Рекурсивная функция для перевода иерархического массива (JSON) в линейный массив (просто набор объектов)
function getLinearListOfNodes($hierarchy_array)
{
    $linear_array = array();//Линейный массив
    
    for($i=0; $i<count($hierarchy_array); $i++)
    {
        //Генерируем объект записи материала и заносим его в линейный массив
        $current_category = new DP_GeoNode;
        $current_category->id = $hierarchy_array[$i]["id"];
        $current_category->count = $hierarchy_array[$i]['$count'];
        $current_category->level = $hierarchy_array[$i]['$level'];
        $current_category->value = $hierarchy_array[$i]["value"];
        $current_category->parent = $hierarchy_array[$i]['$parent'];
        $current_category->from_server = $hierarchy_array[$i]['from_server'];
        
        array_push($linear_array, $current_category);
        
        //Рекурсивный вызов для вложенного уровня
        if($hierarchy_array[$i]['$count'] > 0)
        {
            $data_linear_array = getLinearListOfNodes($hierarchy_array[$i]["data"]);
            //Добавляем массив вложенного уровня к текущему
            for($j=0; $j<count($data_linear_array); $j++)
            {
                array_push($linear_array, $data_linear_array[$j]);
            }//for(j)
        }
    }//for(i)
    
    return $linear_array;
}//~function getLinearListOfNodes($hierarchy_array)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ End PHP - метод ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~



if( !empty($_POST["save_tree"]) )//Сохраняем дерево географичнских узлов
{
    //Генерируем линейный массив на основе полученого иерархического
    $php_dump = json_decode($_POST["tree_json"], true);
    $linear_array = array();//Линейный массив материалов
    $linear_array = getLinearListOfNodes($php_dump);//Генерируем линейный массив категорий
    
    //Линейный массив получен - далее работаем с БД
    
    $no_update_error = true;//Накопительный результат обновления записей
    $no_insert_error = true;//Накопительный результат создания записей
    
    for($i=0; $i < count($linear_array); $i++)
    {
		$order = $i + 1;
		
        $id = $linear_array[$i]->id;
        $count = $linear_array[$i]->count;
        $level = $linear_array[$i]->level;
        $value = $linear_array[$i]->value;
        $parent = $linear_array[$i]->parent;
        
        if($linear_array[$i]->from_server == 1)//Обновление записи
        {
            if( $db_link->prepare("UPDATE `shop_geo` SET `count` = ?, `level` = ?, `value` = ?, `parent` = ?, `order` = ? WHERE `id` = ?;")->execute( array($count, $level, $value, $parent, $order, $id) ) != true)
            {
                $no_update_error = false;
            }
        }
        else//Создание записи
        {
            if( $db_link->prepare("INSERT INTO `shop_geo` (`id`, `count`, `level`, `value`, `parent`, `order`) VALUES (?,?,?,?,?,?);")->execute( array($id, $count, $level, $value, $parent, $order) ) != true)
            {
                $no_insert_error = false;
            }
        }
    }
    
    
    
    
    
    //По всем записям базы данных для удаления записей, которые были удалены при редактировании
    $no_delete_error = true;//Накопительный результат удаления узлов
    $deleted_nodes_list = array();//Массив с ID удаляемыйх узлов
    $all_nodes_query = $db_link->prepare("SELECT * FROM `shop_geo`");
	$all_nodes_query->execute();
	while( $node_record = $all_nodes_query->fetch() )
    {
        $such_node_exist = false;
        for($j=0; $j < count($linear_array); $j++)
        {
            if($node_record["id"] == $linear_array[$j]->id)
            {
                $such_node_exist = true;
                break;
            }
        }
        
        //Если такого узла нет в сохраняемом перечне, значит при редактировании он был удален - удаляем его из БД
        if(!$such_node_exist)
        {
            array_push($deleted_nodes_list, $node_record["id"]);//Добавляем ID в список
			if( $db_link->prepare("DELETE FROM `shop_geo` WHERE `id` = ?;")->execute( array($node_record["id"]) ) != true )
            {
                $no_delete_error = false;
            }
        }
    }
    

    //ПРОВЕРКА ВЫПОЛНЕНИЯ
    if($no_update_error && $no_insert_error && $no_delete_error)
    {
        $success_message = "Дерево географических узлов сохранено успешно!";
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/geo/nodes?success_message=<?php echo $success_message; ?>";
        </script>
        <?php
        exit;
    }
    else
    {
        $error_message = "Возникли ошибки: <br>";
        if(!$no_update_error)
        {
            $error_message .= "Ошибка обновления существующих узлов<br>";
        }
        if(!$no_insert_error)
        {
            $error_message .= "Ошибка создания новых узлов<br>";
        }
        if(!$no_delete_error)
        {
            $error_message .= "Ошибка удаление узлов из базы данных<br>";
        }
        ?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/geo/nodes?error_message=<?php echo $error_message; ?>";
        </script>
        <?php
        exit;
    }
    
}//if( !empty($_POST["save_tree"]) )
else//Действий нет - выводим страницу
{
    require_once("content/shop/geo/get_geo_tree.php");//Получение объекта иерархии существующих географических узлов для вывода в дерево-webix
	
	//Получаем ID следующего добавляемого узла
	$next_id_query = $db_link->prepare("SHOW TABLE STATUS LIKE 'shop_geo'");
	$next_id_query->execute();
	$next_id_record = $next_id_query->fetch();
	if( $next_id_record == false )
	{
		exit("SQL error: next_id_query");
	}
    $next_id = $next_id_record["Auto_increment"];//ID следующего добавляемого узла
	
	
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
				<a class="panel_a" onClick="add_new_country();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/flag_plus.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить страну</div>
				</a>
				
				<a class="panel_a" onClick="add_new_region();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/shield_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить регион</div>
				</a>
				
				<a class="panel_a" onClick="add_new_city();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/city_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить город</div>
				</a>
				
				
				
				<a class="panel_a" onClick="delete_selected_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
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
        <input name="save_tree" id="save_tree" type="text" value="ok" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
    </form>
    <!--Форма для отправки-->
    
    
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Дерево стран, регионов и городов
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Информация
			</div>
			<div class="panel-body">
				<p>Все созданные магазины должны быть привязаны к ГЕО-узлам. Что бы включить для клиента выбор ГЕО-узла необходимо в разделе Панель управления -> Модули, включить модуль "Выбор города".</p>
				<p><b>Когда необходимо включать модуль выбора года:</b></p>

				<p>Модуль выбора города стоит включать, только в том случае если у вас создано несколько магазинов. Если магазин в платформе один, он выбирается автоматически.</p>

				<b>Как правильно настроить привязку магазина к ГЕО-узлам:</b></p>

				<p>Нужно в разделе География в Панели управления создать нужные ГЕО-узлы под каждую точку выдачи (созданный магазин).
				Создаваемые элементы раздела География не обязательно должны быть такими как это сделано по умолчанию: Страна, Регион, Город. Каждый уровень вложенности в разделе География это просто текст, который будет отображен клиенту. Например, для более удобного отображения мы советуем создать иерархию ГЕО-узлов таким образом: Регион, Город, Адрес точки выдачи.</p>
				
				<p>Затем нужно привязать каждый созданный магазин к своему ГЕО-узлу, магазин должен быть привязан ко всем 3-м уровням вложенности ГЕО-узлов, но так что бы на 3-ем уровне в иерархии ГЕО–узлов был привязан только один магазин.
				Получается клиент, заходя на сайт первый раз, видит модуль выбора ГЕО-узла, выбирает нужный для него и видит товары именно по складам, которые подключены к магазину, который в свою очередь подключен к выбранному ГЕО-узлу.</p>

				<p><b>Почему нельзя привязывать несколько магазинов к одному ГЕО-узлу на 3-ем уровне вложенности:</b></p>

				<p>Если клиент выберет ГЕО-узел к которому привязано несколько магазинов, тогда при проценке он будет тратить в 2 раза больше времени так как необходимо будет по очереди проценить все склады каждого отдельного магазина который привязан к выбранному ГЕО-узлу и после добавления товара в корзину клиент увидит ошибку и не сможет оформить заказ если он добавит в корзину товары с разных магазинов, дать возможность выбора точки выдачи невозможно, так как товар клиент кладёт в корзину от определенного склада подключенного к определенному магазину, у этого товара определенная наценка, если клиенту дать затем при оформлении заказа выбрать другую точку выдачи то может так получиться что у этой другой точки выдачи совершенно другие наценки, да и сам склад с которого добавлен товар не подключен к этому магазину, и сроки могут быть другие и т.д.</p>

				<p>Поэтому клиент должен видеть товары только с одного конкретного магазина, для этого при входе на сайт клиент выбирает ГЕО-узел и соответствующий ему магазин, а только потом уже делает проценку.</p>
			</div>
		</div>
	</div>
	
	
	
    

    
    <script type="text/javascript" charset="utf-8">
    var next_id = <?php echo $next_id; ?>;//id следующего узла
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
	//----------------------------------------------------
    //Обработчик До перетаскивания узлов дерева
    //При переносе нельзя менять уровни (т.е. страна остается страной, регион остается регионом, город остается городом)
	tree.attachEvent("onBeforeDrop",function(context)
	{
	    var node_id = context.source;//ID переносимого элемента
	    var node_parent_id = tree.getItem(node_id).$parent;//ID исходного родителя элемента
	    
	    var target_id = context.target;//ID того элемента, на место которого переносим
	    var target_parent_id = tree.getItem(target_id).$parent;//ID целевого родителя элемента
	    
	    if(tree.getItem(target_id).$level != tree.getItem(node_id).$level)
	    {
	        alert("Действие не допустимо");
	        return false;
	    }
	    else
	    {
	        return true;
	    }
	});//~tree.attachEvent("onAfterDrop",function(context)
    //-----------------------------------------------------
    //Добавить новую страну
    function add_new_country()
    {
    	//Добавляем элемент в выделенный узел
    	var newItemId = tree.add( {id:next_id, value:"Новая страна", from_server:0}, 0, 0);//Добавляем новый узел и запоминаем его ID
    	onSelected();//Обработка текущего выделения
    	
    	next_id++;//ID следующего узла
    }
    //-----------------------------------------------------
    //Добавить новый регион
    function add_new_region()
    {
        //Регион добавляется в страну (страна - элемент с уровнем 1)
        var country_item_id = tree.getSelectedId();
        if(country_item_id == 0)
        {
            alert("Сначала выберите страну");
            return;
        }
        var country_item = tree.getItem(country_item_id);
        if(country_item.$level > 1)
        {
            alert("Сначала выберите страну");
            return;
        }
        
    	//Добавляем элемент в выделенный узел
    	var newItemId = tree.add( {id:next_id, value:"Новый регион", from_server:0}, 0, country_item_id);//Добавляем новый узел и запоминаем его ID
    	onSelected();//Обработка текущего выделения
    	tree.open(country_item_id);//Раскрываем родительский узел
    	next_id++;//ID следующего узла
    }
    //-----------------------------------------------------
    //Добавить новый город
    function add_new_city()
    {
        //Город добавляется в регион (регион - элемент с уровнем 2)
        var region_item_id = tree.getSelectedId();
        if(region_item_id == 0)
        {
            alert("Сначала выберите регион");
            return;
        }
        var region_item = tree.getItem(region_item_id);
        if(region_item.$level != 2)
        {
            alert("Сначала выберите регион");
            return;
        }
    
    	//Добавляем элемент в выделенный узел
    	var newItemId = tree.add( {id:next_id, value:"Новый город", from_server:0}, 0, region_item_id);//Добавляем новый узел и запоминаем его ID
    	onSelected();//Обработка текущего выделения
    	tree.open(region_item_id);//Раскрываем родительский узел
    	next_id++;//ID следующего узла
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
    //Сохранение дерева
    function save_tree()
    {
    	//Получаем строку JSON:
    	var tree_json_to_save = tree.serialize();
    	var tree_dump = JSON.stringify(tree_json_to_save);
    	
    	//Задаем значение поля в форме:
    	var tree_json_input = document.getElementById("tree_json");
    	tree_json_input.value = tree_dump;
    	
    	document.forms["form_to_save"].submit();//Отправляем
    }
    //-----------------------------------------------------
    //Инициализация редактора дерева материалов после загруки страницы
    function tree_start_init()
    {
    	var saved_tree = <?php echo $tree_dump_JSON; ?>;
	    tree.parse(saved_tree);
	    tree.openAll();
    }
    tree_start_init();
    onSelected();//Обработка текущего выделения
    //-----------------------------------------------------
    </script>
    
    <?php
}
?>