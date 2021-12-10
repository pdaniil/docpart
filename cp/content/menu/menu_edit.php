<?php
/**
 * Скрипт управления одним меню: создание / редактирование
*/
defined('_ASTEXE_') or die('No access');


//Режим редактирования Фронтэнд/Бэкэнд
$edit_mode = null;
if( isset($_COOKIE["edit_mode"]) )
{
	$edit_mode = $_COOKIE["edit_mode"];
}
switch($edit_mode)
{
    case "frontend":
        $is_frontend = 1;
        break;
    case "backend":
        $is_frontend = 0;
        break;
    default:
        $is_frontend = 1;
        break;
}
?>


<?php
if(!empty($_POST["save_action"]))
{
    //Возможны два действия: создание новго меню или сохранение изменений для существующего меню
    if($_POST["save_action"] == "update")
    {
        if( $db_link->prepare("UPDATE `menu` SET `caption`=?, `structure` = ?, `menu_ul_class`=?, `menu_ul_id`=? WHERE `id`=?;")->execute( array($_POST["menu_caption"], $_POST["menu_tree"], $_POST["menu_ul_class"], $_POST["menu_ul_id"], $_POST["menu_id"]) ) != true)
        {
            $error_message = "Ошибка сохранения";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/menu/menu_edit?menu_id=<?php echo $_POST["menu_id"]; ?>&error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        else
        {
            $success_message = "Меню успешно отредактировано";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/menu/menu_edit?menu_id=<?php echo $_POST["menu_id"]; ?>&success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
    }
    else if($_POST["save_action"] == "create")
    {
        if( $db_link->prepare("INSERT INTO `menu` (`is_frontend`, `caption`, `structure`, `menu_ul_class`, `menu_ul_id`) VALUES (?, ?, ?, ?, ?);")->execute( array($is_frontend, $_POST["menu_caption"], $_POST["menu_tree"], $_POST["menu_ul_class"], $_POST["menu_ul_id"]) ) != true)
        {
            $error_message = "Не удалось создать меню";
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/menu/menu_edit?error_message=<?php echo $error_message; ?>";
            </script>
            <?php
            exit;
        }
        else//Операция INSERT успешна
        {
            $success_message = "Меню успешно создано";
            $menu_id = $db_link->lastInsertId();
            ?>
            <script>
                location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/menu/menu_edit?menu_id=<?php echo $menu_id;?>&success_message=<?php echo $success_message; ?>";
            </script>
            <?php
            exit;
        }
    }//else - создание нового меню
}
else//Если нет действий - вывод страницы
{
    //Для получения списка существующих материалов
    require_once("content/content/dp_content_record.php");
    require_once("content/content/get_content_records.php");
    ?>
    
    <?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
    
    
    <?php
    //Исходные значения:
    $menu_caption = "";//Название меню
    $menu_ul_class = "";//Атрибут class контейнера
    $menu_ul_id = "";//Атрибут id контейнера
    $menu_tree_dump_JSON = "[]";//Дерево пунктов меню
    
    //Если задан ID меню - идет редактирование существующего меню
    if(!empty($_GET["menu_id"]))
    {
		$menu_query = $db_link->prepare("SELECT * FROM `menu` WHERE `id` = ?;");
		$menu_query->execute( array($_GET["menu_id"]) );
        $menu_record = $menu_query->fetch();
        $menu_caption = $menu_record["caption"];//Название меню
        $menu_ul_class = $menu_record["menu_ul_class"];//Атрибут class контейнера
        $menu_ul_id = $menu_record["menu_ul_id"];//Атрибут id контейнера
        $menu_tree_dump_JSON = $menu_record["structure"];//Дерево пунктов меню
    }
    ?>
    
    
    
    
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="add_new_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<a class="panel_a" onClick="delete_selected_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png') 0 0 no-repeat;"></div>
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
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir;?>/menu/menu_manager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/menu_manager.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер меню</div>
				</a>
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Параметры меню
			</div>
			<div class="panel-body">
				<div class="row">
					<div class="col-lg-2">
						<label for="" class="control-label">Название меню</label>
					</div>
					<div class="col-lg-2">
						<input type="text" id="menu_caption_input" value="<?php echo $menu_caption; ?>" class="form-control" />
					</div>
					
					
					<div class="col-lg-2">
						<label for="" class="control-label">Атрибут class контейнера</label>
					</div>
					<div class="col-lg-2">
						<input type="text" id="menu_ul_class_input" value="<?php echo $menu_ul_class; ?>" class="form-control" />
					</div>
					
					<div class="col-lg-2">
						<label for="" class="control-label">Атрибут id контейнера</label>
					</div>
					<div class="col-lg-2">
						<input type="text" id="menu_ul_id_input" value="<?php echo $menu_ul_id; ?>" class="form-control" />
					</div>
				</div>
			</div>
		</div>
	</div>
    
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Пункты меню
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-6" id="item_info_div_col">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Параметры выбранного пункта
			</div>
			<div class="panel-body">
				<div id="item_info_div">
				</div>
			</div>
		</div>
	</div>
	
	
	
    

  

   
    
    
    <form method="POST" name="save_menu" style="display:none">
        <?php
        //Если при загрузке страницы задан ID меню, то действие формы - обновление меню
        if(!empty($_GET["menu_id"]))
        {
            ?>
            <input type="hidden" name="save_action" value="update" />
            <input type="hidden" name="menu_id" value="<?php echo $_GET["menu_id"]; ?>" />
            <?php
        }
        else//Если ID меню не задан при загруке страницы, то действия формы - создание меню
        {
            ?>
            <input type="hidden" name="save_action" value="create" />
            <?php
        }
        ?>
        
        <!-- Поле для дампа дерева пунктов меню -->
        <input type="hidden" name="menu_tree" id="menu_tree" value=""/>
        <!-- Поле для заголовка меню -->
        <input type="hidden" name="menu_caption" id="menu_caption" value=""/>
        <!-- Поле для Атрибута class контейнера  -->
        <input type="hidden" name="menu_ul_class" id="menu_ul_class" value=""/>
        <!-- Поле для Атрибута id контейнера -->
        <input type="hidden" name="menu_ul_id" id="menu_ul_id" value=""/>
    </form>
    
    
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
    //Событие при выборе элемента дерева
    tree.attachEvent("onAfterSelect", function(id)
    {
    	onSelected();
    });
    //Обработка выбора элемента
    function onSelected()
    {
        //Если элементы не созданы
    	if(tree.count() == 0)
    	{
    	    document.getElementById("item_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("item_info_div_col").setAttribute("style", "display:none");
    	    return;
    	}
    	
    	//Выделенный узел
    	var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    document.getElementById("item_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("item_info_div_col").setAttribute("style", "display:none");
    	    return;
    	}
		
		//Показать контейнер для параметров
		document.getElementById("item_info_div_col").setAttribute("style", "display:block");
    	
    	
    	var node = "";//Ссылка на объект узла
    	//Выделенный узел
    	node = tree.getItem(node_id);
    	
    	var parameters_table_html = "";
		
		
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Заголовок</label><div class=\"col-lg-6\">"+node.value+"</div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Уровень вложенности</label><div class=\"col-lg-6\">"+node.$level+"</div></div>";
		
		//parameters_table_html += "<div class=\"col-lg-12 text-center form-group\"><label>Настройки ссылки</label></div>";
		parameters_table_html += "<div class=\"col-lg-12 text-center\"><h3>Настройки ссылки</h3></div>";
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Задать содержимое тега a</label><div class=\"col-lg-6\"><select class=\"form-control\" id=\"a_innerhtml_mode\" onchange=\"dynamicApplying('a_innerhtml_mode'); on_a_innerhtml_mode_changed();\"><option value=\"auto\">Равно заголовку</option> <option value=\"user\">Указать вручную</option> </select></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Содержимое тега a</label><div class=\"col-lg-6\"><div id=\"a_innerhtml_div\"></div></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Задать атрибут href</label><div class=\"col-lg-6\"><select class=\"form-control\" id=\"link_mode\" onchange=\"dynamicApplying('link_mode'); on_link_mode_changed();\"><option value=\"url\">Вручную</option> <option value=\"content\">Указать материал</option> </select></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\" id=\"link_value_name\"></label><div class=\"col-lg-6\"><div id=\"link_value\"></div></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут target</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('target');\" type=\"text\" id=\"target\" value=\""+node.target+"\" /></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут onclick</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('onclick');\" type=\"text\" id=\"onclick\" value=\""+node.onclick+"\" /></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут class</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('class_a');\" type=\"text\" id=\"class_a\" value=\""+node.class_a+"\" /></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут id</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('id_a');\" type=\"text\" id=\"id_a\" value=\""+node.id_a+"\" /></div></div>";
		
		
        parameters_table_html += "<div class=\"col-lg-12 text-center\"><h3>Настройки пункта списка (li)</h3></div>";
        
        
        parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут class</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('class_li');\" type=\"text\" id=\"class_li\" value=\""+node.class_li+"\" /></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут id</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('id_li');\" type=\"text\" id=\"id_li\" value=\""+node.id_li+"\" /></div></div>";
        
        
        
        
        if(node.$count > 0)
        {
			parameters_table_html += "<div class=\"col-lg-12 text-center\"><h3>Настройки контейнера (ul)</h3></div>";
			
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут class</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('class_ul');\" type=\"text\" id=\"class_ul\" value=\""+node.class_ul+"\" /></div></div>";
			parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Атрибут id</label><div class=\"col-lg-6\"><input class=\"form-control\" onKeyUp=\"dynamicApplying('id_ul');\" type=\"text\" id=\"id_ul\" value=\""+node.id_ul+"\" /></div></div>";
        }
        
        

    	document.getElementById("item_info_div").innerHTML = parameters_table_html;
    	
    	
    	//Селектор режима ссылки в текущее значение:
    	for(var i=0; i< document.getElementById("link_mode").options.length; i++)
    	{
    	    if(node.link_mode == document.getElementById("link_mode").options[i].value)
    	    {
    	        document.getElementById("link_mode").options[i].selected = true;
    	        break;
    	    }
    	}
    	on_link_mode_changed();//Обработка текущего значения селектора режима ссылки
    	
    	
    	
    	//Селектор режима innerHTML тега a в текущее значение:
    	for(var i=0; i< document.getElementById("a_innerhtml_mode").options.length; i++)
    	{
    	    if(node.a_innerhtml_mode == document.getElementById("a_innerhtml_mode").options[i].value)
    	    {
    	        document.getElementById("a_innerhtml_mode").options[i].selected = true;
    	        break;
    	    }
    	}
    	on_a_innerhtml_mode_changed();//Обработка текущего значения селектора режима innerHTML тега a
    	
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
    //Обработка смены режима ссылки
    function on_link_mode_changed()
    {
        var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
        
        if(document.getElementById("link_mode").value == "url")//Если установлен ручной ввод url
        {
            document.getElementById("link_value_name").innerHTML="Атрибут href";
            document.getElementById("link_value").innerHTML="<input class=\"form-control\" onKeyUp=\"dynamicApplying('href');\" type=\"text\" id=\"href\" value=\""+node.href+"\" />";
            node.content_id = 0;//Чтобы не тянуть настройки для материала
        }
        else if(document.getElementById("link_mode").value == "content")//Если установлен выбор материала сайта
        {
            document.getElementById("link_value_name").innerHTML="Материал сайта";
            node.href = "";//Чтобы не тянуть настройки для ручного ввода url
            
            if(node.content_id == 0)//У текущего  пункта меню не выбран материал
            {
                document.getElementById("link_value").innerHTML="Не выбран <button onclick=\"openContentWindow();\" class=\"btn btn-success\" type=\"button\"><i class=\"fa fa-file\"></i> Выбрать</button>";
            }
            else//Если выбран - пишем его заголовок для индикации
            {
                var content_node = tree_1.getItem(node.content_id);
                
                console.log(content_node);
                
                if(content_node != undefined)//Материал не найден - возможно он был ранее удален
                {
                    content_value = content_node.value;
                }
                else
                {
                    content_value = "Удален";
                }
            
                document.getElementById("link_value").innerHTML = content_value+" <button onclick=\"openContentWindow();\" class=\"btn btn-success\" type=\"button\"><i class=\"fa fa-file\"></i> Выбрать</button>";
            }
        }
    }
    //----------------------------------------------------
    //Обработка смены режима задания содержимого тега a
    function on_a_innerhtml_mode_changed()
    {
        var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
    
        if(document.getElementById("a_innerhtml_mode").value == "auto")//Содержимое тега равно значению value элемента дерева
        {
            node.a_innerhtml = node.value;
            document.getElementById("a_innerhtml_div").innerHTML = node.a_innerhtml;
        }
        else if(document.getElementById("a_innerhtml_mode").value == "user")//Содержимое тега задается вручную
        {
            document.getElementById("a_innerhtml_div").innerHTML = "<input class=\"form-control\" onKeyUp=\"dynamicApplying('a_innerhtml');\" type=\"text\" id=\"a_innerhtml\" name=\"a_innerhtml\" value=\""+node.a_innerhtml+"\" />";
        }
    }
    //----------------------------------------------------
    //Обработчик До перетаскивания узлов дерева
	tree.attachEvent("onBeforeDrop",function(context)
	{
	    return true;
	});//~tree.attachEvent("onAfterDrop",function(context)
    //-----------------------------------------------------
    /*
    Атрибуты пункта меню:
    value - Заголовок пункта для редактора меню
    class_li - атрибут class для элемента li
    class_ul - атрибут class для элемента ul
    class_a - атрибут class для элемента a
    id_li - атрибут id для элемента li
    id_ul - атрибут id для элемента ul
    id_a - атрибут id для элемента a
    a_innerhtml_mode - способ задания innerHTML тега a ("auto" - берется из заголовка, т.е. поля value элемента дерева, "user" - задается вручную). Это поле используется только в редакторе. Т.е. innerHTML в любом варианте хранится в дампе меню
    a_innerhtml - значение innerHTML тега a
    link_mode - Режим ссылки (т.е. заполнение атрибута href): url (заполняется вручную), content (указывается id материала, и href заполняется автоматически на этапе вывода страницы, т.к. url матариала может меняться по мере редактирования сайта)
    content_id - ID материала - для режима content
    href - атрибут href - для режима url
    target - атрибут target
    onclick - атрибут onclick
    
    //На перспективу:
    img_src - путь к изображению
    */
    //Добавить новый элемент в дерево
    function add_new_item()
    {
    	//Добавляем элемент в выделенный узел
    	var parentId= tree.getSelectedId();//Выделеный узел
    	
    	var newItemId = tree.add( {value:"Новый пункт", class_li:"", class_ul:"", class_a:"", id_li:"", id_ul:"", id_a:"", a_innerhtml_mode:"auto", a_innerhtml:"Новый пункт", link_mode:"content", content_id:0, href:"", target:"", onclick:"", img_src:""}, 0, parentId);//Добавляем новый узел и запоминаем его ID
    	onSelected();//Обработка текущего выделения
    	tree.open(parentId);//Раскрываем родительский узел
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
    //Сохранение на сервере
    function save_tree()
    {
        //1.1 Задаем название меню
        var menu_caption_input = document.getElementById("menu_caption_input").value;
        if(menu_caption_input == "")
        {
            webix.message({type:"error", text:"Необходимо заполнить название меню"});
            return;
        }
        document.getElementById("menu_caption").value = menu_caption_input;
        
        //1.2 Атрибут class для контейнера menu
        document.getElementById("menu_ul_class").value = document.getElementById("menu_ul_class_input").value;
        
        //1.3 Атрибут id для контейнера menu
        document.getElementById("menu_ul_id").value = document.getElementById("menu_ul_id_input").value;
        
        
        //2. Задаем состав пунктов меню
        //Получаем строку JSON:
    	var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
    	
    	
        //Задаем значение поля в форме:
    	var tree_json_input = document.getElementById("menu_tree");
    	tree_json_input.value = tree_dump;
        
        
        //3. Отправляем форму
    	document.forms["save_menu"].submit();//Отправляем
    }
    //-----------------------------------------------------
    //Инициализация редактора дерева меню после загруки страницы
    function menu_start_init()
    {
    	var saved_menu = <?php echo $menu_tree_dump_JSON;?>;
	    tree.parse(saved_menu);
	    tree.openAll();
    }
    menu_start_init();
    onSelected();//Обработка текущего выделения
    </script>
    
    
    
    
	
	
	<!--Start Модальное окно: Выбор материала-->
	<div class="text-center m-b-md">
		<div class="modal fade" id="modalWindow_1" tabindex="-1" role="dialog"  aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="color-line"></div>
					<div class="modal-header">
						<h4 class="modal-title">Дерево страниц</h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-lg-12">
								
							</div>
							<div class="col-lg-12">
								<div id="container_A_1" style="height:300px;"></div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button class="btn btn-primary" type="button" onClick="unselect_content_tree();"><i class="fa fa-square"></i> Снять выделение</button>

						<button class="btn btn-success" type="button" onClick="apply_content_button_click();"><i class="fa fa-check"></i> Применить</button>

						<button onClick="closeContentWindow();" class="btn btn-default">Отмена</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
    
    
    
    <!--Start Модальное окно: Выбор материала-->
    <script>
        //Функции для окна:
        var tree_1 = getContentTree();//Дерево материалов
        //-----------------------------------------------------
        //Открыть окно
        function openContentWindow()
        {
            $('#modalWindow_1').modal();//Открыть окно
            
			//После отображения окна - подгоняем дерево под размер
			$('#modalWindow_1').on('shown.bs.modal',function(){
				tree_1.adjust();
			});
			
            document.getElementById("container_A_1").innerHTML = "";//Предварительно очищаем контейнер
        
            //Создаем дерево
            tree_1 = getContentTree();
			
            //Выделяем текущий выбранный материал для данного пункта меню
            var node_id = tree.getSelectedId();//ID выделенного узла дерева пунктов меню
    	    node = tree.getItem(node_id);//Выделенный узел дерева пунктов меню
            if(node.content_id != 0)
            {
                tree_1.select(node.content_id);//Выделяем узел дерева материалов
            }
        }
        //-----------------------------------------------------
        //Функция получения дерева метериалов
        function getContentTree()
        {
            var content_tree_local = new webix.ui({
                
                //Шаблон элемента дерева
            	template:function(obj, common)//Шаблон узла дерева
                	{
                        var folder = common.folder(obj, common);
                	    var icon = "";
                	    var value_text = "<span>" + obj.value + "</span>";//Вывод текста
                	    
                	    //Индикация системного материала
                	    var icon_system = "";
                	    if(obj.system_flag == true)
                        {
                            icon_system = "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/gear.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                        }
                	    
                	    //Индикация материала, снятого с публикации
                	    if(obj.published_flag == false)
                        {
                            icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                            value_text = "<span style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
                        }
                	    
                	    //Индикация главного материала
                	    if(obj.main_flag == 1)
                        {
                            icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/star.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                            value_text = "<span style=\"font-weight:bold\">" + obj.value + "</span>";//Вывод текста
                        }
                	    
                	    
                        return common.icon(obj, common) + icon + folder + icon_system + value_text;
                	},//~template
            
            
            
            	editable:false,//Не редактируемое
                container:"container_A_1",//id блока div для дерева
                view:"tree",
            	select:true,//можно выделять элементы
            	drag:false//Нельзя переносить
            });
			
			webix.event(window, "resize", function(){ content_tree_local.adjust(); });
            
            var site_content = <?php echo $content_tree_dump_JSON; ?>;
            content_tree_local.parse(site_content);
            content_tree_local.openAll();
            
			content_tree_local.adjust();
			
            return content_tree_local;
        }
        //-----------------------------------------------------
        //Закрыть окно
        function closeContentWindow()
        {
            $('#modalWindow_1').modal('hide');//Скрыть окно
        }
        //-----------------------------------------------------
        //Снятие выделения с дерева материалов
        function unselect_content_tree()
        {
            tree_1.unselect();
        }
        //-----------------------------------------------------
        //Нажатие применить в окне выбора материала
        function apply_content_button_click()
        {
            //1. Получаем текущий выделенный пункт меню
            var node_id = tree.getSelectedId();//ID выделенного узла
    	    node = tree.getItem(node_id);//Выделенный узел
            
            //2. Получаем текущий выделенный материал
            var content_node_id = tree_1.getSelectedId();//ID выделенного узла
            if(content_node_id != 0)
            {
                content_node = tree_1.getItem(content_node_id);//Выделенный узел
                //3. Задаем значение поля в пункте меню
                node.content_id = content_node.id;
            }
            else
            {
                //3. Задаем значение поля в пункте меню
                node.content_id = 0;
            }
            
            //4. Закрываем окно с деревом материалов
            closeContentWindow();
            
            //5. Обрабатываем выделение дерева пунктов меню
            onSelected();//Обработка текущего выделения
        }
        //-----------------------------------------------------
    </script>
    <!--End Модальное окно: Выбор материала-->
    
    
    
    
    <?php
}//Вывод страницы
?>