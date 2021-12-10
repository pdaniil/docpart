<?php
/**
 * Страница управления списком регистрационных вариантов
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if(!empty($_POST["save_action"]))//Создание или редактирование
{
	//Предварительно ставим поле order = 0, чтобы затем понять, какие регистрационные варианты были удалены
	if( $db_link->prepare("UPDATE `reg_variants` SET `order` = 0;")->execute() != true)
	{
		$error_message = "Не удалось сбросить порядок вариантов";
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	
	
	
	//Элементы списка
	$reg_variants = json_decode($_POST["tree_json"], true);
	//Работаем с существующими элементами
	for($i=0; $i < count($reg_variants); $i++)
	{
		$order = $i+1;
		
		if($reg_variants[$i]["is_new"] == true)//Новые варианты - добавляем
		{
			if( $db_link->prepare("INSERT INTO `reg_variants` (`caption`, `order`) VALUES (?,?);")->execute( array($reg_variants[$i]["value"], $order) ) != true)
			{
				$error_message = "Не удалось создать регистрационный вариант: ";
				?>
				<script>
					location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?error_message=<?php echo $error_message; ?>";
				</script>
				<?php
				exit;
			}
		}
		else//Старые обновляем
		{
			if( $db_link->prepare("UPDATE `reg_variants` SET `caption` = ?, `order` = ? WHERE `id` = ?;")->execute( array($reg_variants[$i]["value"], $order, $reg_variants[$i]["id"]) ) != true)
			{
				$error_message = "Не удалось обновить регистрационный вариант: ";
				?>
				<script>
					location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?error_message=<?php echo $error_message; ?>";
				</script>
				<?php
				exit;
			}
			
		}
	}
	
	$query = $db_link->prepare("SELECT `user_id` FROM `users` WHERE `reg_variant` IN(SELECT `id` FROM `reg_variants` WHERE `order` = 0) LIMIT 1;");
	$query->execute();
	$row = $query->fetch();
	if(!empty($row)){
		$error_message = "Не удалось удалить регистрационные варианты. В менеджере пользователей присутствуют пользователи с удаляемыми вариантами регистрации. Сначала нужно изменить вариант регистрации у данных пользователей в их профилях.";
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	
	//Теперь удаляем варианты, которые были удалены при редактировании
	if( $db_link->prepare("DELETE FROM `reg_variants` WHERE `order` = 0;")->execute() != true)
	{
		$error_message = "Не удалось удалить регистрационные варианты";
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?error_message=<?php echo $error_message; ?>";
		</script>
		<?php
		exit;
	}
	

	$success_message = "Выполнено успешно";
	?>
	<script>
		location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/users/registracionnye-varianty?success_message=<?php echo $success_message; ?>";
	</script>
	<?php
	exit;
}
else//Действий нет - выводим страницу
{
	//Получаем текущие данные
	$reg_variants = array();
	$reg_variants_query = $db_link->prepare("SELECT * FROM `reg_variants` ORDER BY `order`;");
	$reg_variants_query->execute();
	while( $reg_variant = $reg_variants_query->fetch() )
	{
		array_push( $reg_variants, array("id"=>$reg_variant["id"], "value"=>$reg_variant["caption"], "is_new"=>0) );
	}
	$reg_variants = json_encode($reg_variants);
    ?>
    
    
    <!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none">
        <input name="save_action" id="save_action" type="text" value="save_action" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
    </form>
    <!--Форма для отправки-->
    
    
    
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
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<a class="panel_a" onClick="delete_selected_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>
				
				
				<a class="panel_a" onClick="unselect_tree();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять выделение</div>
				</a>
				

				<a class="panel_a" href="javascript:void(0);" onclick="save_tree();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
    
    
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Редактор списка
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
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
    	//Добавляем элемент в выделенный узел
    	var parentId= tree.getSelectedId();//Выделеный узел
    	var newItemId = tree.add( {value:"Новый элемент", is_new:true}, 0, 0);//Добавляем новый узел и запоминаем его ID
    	
    	onSelected();//Обработка текущего выделения
    }
    //-----------------------------------------------------
    //Удаление выделеного элемента
    function delete_selected_item()
    {
		if(tree.count() == 1)
		{
			alert("Нельзя удалить последний регистрационный вариант");
			return;
		}
		
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
    //Сохранение списка
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
    
    //Инициализация редактора дерева после загруки страницы
    function tree_start_init()
    {
    	var saved_list = <?php echo $reg_variants; ?>;
	    tree.parse(saved_list);
	    tree.openAll();
    }
    tree_start_init();
    onSelected();//Обработка текущего выделения
    </script>
    
    
    <?php
}//~else//Действий нет - выводим страницу
?>