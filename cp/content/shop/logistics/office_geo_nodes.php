<?php
/**
 * Страница для настройки связи магазина с географическими узлами
*/
defined('_ASTEXE_') or die('No access');


require_once("content/shop/geo/dp_geo_node_record.php");//Определение класса географического узла
?>


<?php
if(!empty($_POST["save_action"]))
{
    $office_id = $_POST["office_id"];
    
    //1. Предварительно удаляем старые записи для этого магазина
    $db_link->prepare("DELETE FROM `shop_offices_geo_map` WHERE `office_id` = ?;")->execute( array($office_id) );
	
    //2. Создаем новые записи
    $geo_list = json_decode($_POST["geo_list"], true);
    
    $SQL_INSERT = "INSERT INTO `shop_offices_geo_map` (`office_id`, `geo_id`) VALUES ";
    $binding_values = array();
	for($i=0; $i < count($geo_list); $i++)
    {
        if($i > 0 )$SQL_INSERT .= ",";
        $SQL_INSERT .= "(?,?)";
		
		array_push($binding_values, $office_id);
		array_push($binding_values, $geo_list[$i]);
    }
	$db_link->prepare($SQL_INSERT)->execute($binding_values);
    
    $success_message = "Выполнено успешно";
    ?>
    <script>
        location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/logistics/offices/office/geo_nodes?office_id=<?php echo $office_id; ?>&success_message=<?php echo $success_message; ?>";
    </script>
    <?php
    exit;
    
}
else//Действий нет - выводим страницу
{
    if(empty($_GET["office_id"]))
    {
        exit;
    }
    $office_id = $_GET["office_id"];
    
    //Исходные данные:
    $page_title = "Связь магазина с гео-узлами";
    require_once("content/shop/geo/get_geo_tree.php");//Получение объекта иерархии существующих географических узлов для вывода в дерево-webix
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
				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				<a class="panel_a" href="javascript:void(0);" onclick="checkAll()">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/checkbox.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Отметить все</div>
				</a>
				
				<a class="panel_a" href="javascript:void(0);" onclick="uncheckAll()">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять все</div>
				</a>
				
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/offices/office?office_id=<?php echo $office_id; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/office.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Магазин</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/offices/office/storages_link?office_id=<?php echo $office_id; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/storages_link.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Склады магазина</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/logistics/offices">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/offices.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Все магазины</div>
				</a>


				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
    

    
    <form style="display:none" method="post" name="save_form">
        <input type="hidden" name="save_action" value="save_action" />
        <input type="hidden" name="office_id" value="<?php echo $office_id; ?>" />
        <input type="hidden" name="geo_list" id="geo_list" value="" />
    </form>
    
    
    
    
    <script>
    //Сохранение привязки
    function save_action()
    {
        var checked_geo_nodes = tree.getChecked();
        
        document.getElementById("geo_list").value = JSON.stringify(checked_geo_nodes);
        
        document.forms["save_form"].submit();
    }
    </script>
    
    
    
    
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Привязка к географическим узлам
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;"></div>
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
    /*ДЕРЕВО*/
    //Для редактируемости дерева
    webix.protoUI({
        name:"edittree"
    }, webix.EditAbility, webix.ui.tree);
    //Формирование дерева
    tree = new webix.ui({
        editable:false,//редактируемое
        container:"container_A",//id блока div для дерева
        //Шаблон элемента дерева
    	template:function(obj, common)//Шаблон узла дерева
        	{
        	    var value_text = "<span>" + obj.value + "</span>";//Вывод текста
        	    var checkbox = common.checkbox(obj, common);//Чекбокс

  
                return common.icon(obj, common)+ checkbox + common.folder(obj, common) + value_text;
        	},//~template
        view:"tree",
    	select:true,//можно выделять элементы
    	drag:false,//можно переносить
    });
    /*~ДЕРЕВО*/
	webix.event(window, "resize", function(){ tree.adjust(); });
    //-----------------------------------------------------
	//Функция "Отметить все"
	function checkAll()
	{
		tree.checkAll();
	}
	//-----------------------------------------------------
	//Функция "Снять все"
	function uncheckAll()
	{
		tree.uncheckAll();
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
    //-----------------------------------------------------
    </script>
    
    
    <script>
    <?php
    //Отметим ранее привязанные узлы
	$checked_geo_nodes = $db_link->prepare("SELECT `geo_id` FROM `shop_offices_geo_map` WHERE `office_id` = ?;");
	$checked_geo_nodes->execute( array($office_id) );
    while($checked_geo_node = $checked_geo_nodes->fetch() )
    {
        ?>
        tree.checkItem(<?php echo $checked_geo_node["geo_id"]; ?>);
        <?php
    }
    ?>
    </script>
    
    <?php
}//else//Действий нет - выводим страницу
?>