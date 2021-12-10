<?php
/**
 * Скрипт для работы со деревом материалов
*/
defined('_ASTEXE_') or die('No access');

require_once("content/content/dp_content_record.php");//Определение класса записи материала

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
// --------------------------------- Start PHP - метод ---------------------------------
//Рекурсивная функция для перевода иерархического массива (JSON перечня материалов) в линейный массив (просто набор объектов материалов)
function getLinearListOfContent($hierarchy_array)
{
    $linear_array = array();//Линейный массив
    
    for($i=0; $i<count($hierarchy_array); $i++)
    {
        //Генерируем объект записи материала и заносим его в линейный массив
        $current_content = new DP_ContentRecord;
        $current_content->id = $hierarchy_array[$i]["id"];
        $current_content->count = $hierarchy_array[$i]['$count'];
        $current_content->url = str_replace(array("'"), "''", $hierarchy_array[$i]["url"]);
        $current_content->level = $hierarchy_array[$i]['$level'];
        $current_content->alias = strtolower(str_replace(array("'"), "''", $hierarchy_array[$i]["alias"]));
        $current_content->value = $hierarchy_array[$i]["value"];
        $current_content->parent = $hierarchy_array[$i]['$parent'];
        $current_content->main_flag = $hierarchy_array[$i]['main_flag'];
        $current_content->title_tag = $hierarchy_array[$i]['title_tag'];
        $current_content->description_tag = $hierarchy_array[$i]['description_tag'];
        $current_content->keywords_tag = $hierarchy_array[$i]['keywords_tag'];
        $current_content->author_tag = $hierarchy_array[$i]['author_tag'];
        $current_content->robots_tag = $hierarchy_array[$i]['robots_tag'];
        $current_content->description = $hierarchy_array[$i]["description"];
        $current_content->groups_access = $hierarchy_array[$i]["groups_access"];
        $current_content->published_flag = $hierarchy_array[$i]["published_flag"];
        $current_content->css_js = $hierarchy_array[$i]["css_js"];
        if((bool)$hierarchy_array[$i]["open"] == true)
        {
            $current_content->open = 1;
        }
        else
        {
            $current_content->open = 0;
        }
        
        array_push($linear_array, $current_content);
        
        //Рекурсивный вызов для вложенного уровня
        if($hierarchy_array[$i]['$count'] > 0)
        {
            $data_linear_array = getLinearListOfContent($hierarchy_array[$i]["data"]);
            //Добавляем массив вложенного уровня к текущему
            for($j=0; $j<count($data_linear_array); $j++)
            {
                array_push($linear_array, $data_linear_array[$j]);
            }//for(j)
        }
    }//for(i)
    
    return $linear_array;
}//~function getLinearListOfContent($hierarchy_array)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ End PHP - метод ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//Сохранение материалов
if(!empty($_POST["save_tree"]))//Для действий
{
    //Генерируем линейный массив на основе полученого иерархического
    $php_dump = json_decode($_POST["tree_json"], true);
	$linear_array = array();//Линейный массив материалов
    $linear_array = getLinearListOfContent($php_dump);//Генерируем линейный массив материалов
	
	//Работаем с БД через транзакцию
	try
	{
		//Старт транзакции
		if( ! $db_link->beginTransaction()  )
		{
			throw new Exception("Не удалось стартовать транзакцию");
		}
		
		
		//Если json_decode() выше сработала с ошибкой
		if( ! is_array($linear_array) )
		{
			throw new Exception("Ошибка преобразования дерева страниц в линейный массив (неверный тип linear_array)");
		}
		if( count($linear_array) == 0 )
		{
			throw new Exception("Ошибка преобразования дерева страниц в линейный массив (массив linear_array пустой)");
		}
		
		
		
		//По всем элементам линейного массива: Созданние и Обновление
		for($i=0; $i<count($linear_array); $i++)
		{
			$order = $i + 1;
			
			//Проверяем существование записи материала:
			$check_content_exist_query = $db_link->prepare('SELECT COUNT(*) FROM `content` WHERE `id`=?;');
			$check_content_exist_query->execute( array($linear_array[$i]->id) );
			if($check_content_exist_query->fetchColumn() == 1)
			{
				//Запись существует - ее нужно обновить
				if( $db_link->prepare("UPDATE `content` SET `count`=?,`url`=?,`level`=?,`alias`=?,`value`=?,`parent`=?,`description`=?, `main_flag` = ?, `title_tag`=?, `description_tag`=?, `keywords_tag`=?, `robots_tag`=?, `published_flag`=?, `open`=?, `css_js`=?, `order` = ? WHERE `id`=?;")->execute( array($linear_array[$i]->count, $linear_array[$i]->url, $linear_array[$i]->level, $linear_array[$i]->alias, $linear_array[$i]->value, $linear_array[$i]->parent, $linear_array[$i]->description, $linear_array[$i]->main_flag, $linear_array[$i]->title_tag, $linear_array[$i]->description_tag, $linear_array[$i]->keywords_tag, $linear_array[$i]->robots_tag, $linear_array[$i]->published_flag, $linear_array[$i]->open, $linear_array[$i]->css_js, $order, $linear_array[$i]->id) ) != true )
				{
					throw new Exception("SQL-ошибка обновления существующих материалов (UPDATE)");
				}
			}
			else
			{
				//Запись не существует - ее нужно создать
				if( $db_link->prepare("INSERT INTO `content` (`id`,`count`,`url`,`level`,`alias`,`value`,`parent`,`description`,`is_frontend`, `main_flag`, `content_type`, `title_tag`, `description_tag`, `keywords_tag`, `robots_tag`, `modules_array`, `published_flag`, `open`, `css_js`, `time_created`, `order`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);")->execute( array($linear_array[$i]->id, $linear_array[$i]->count, $linear_array[$i]->url, $linear_array[$i]->level, $linear_array[$i]->alias, $linear_array[$i]->value, $linear_array[$i]->parent, $linear_array[$i]->description, $is_frontend, $linear_array[$i]->main_flag, 'text', $linear_array[$i]->title_tag, $linear_array[$i]->description_tag, $linear_array[$i]->keywords_tag,$linear_array[$i]->robots_tag, '[]', $linear_array[$i]->published_flag, $linear_array[$i]->open, $linear_array[$i]->css_js, time(), $order) ) != true )
				{
					throw new Exception("SQL-ошибка добавления новых материалов (INSERT)");
				}
			}
		}//for($i) По всем элементам линейного массива:
		
		
		//Удаление материалов, которые были удалены из дерева при редактировании
		$all_content_record_query = $db_link->prepare("SELECT * FROM `content` WHERE `is_frontend` = ?;");
		$all_content_record_query->execute( array($is_frontend) );
		while($content_record = $all_content_record_query->fetch() )
		{
			$such_content_record_exist = false;
			for($j=0; $j < count($linear_array); $j++)
			{
				if($content_record["id"] == $linear_array[$j]->id)
				{
					$such_content_record_exist = true;
					break;
				}
			}
			
			//Если такого материала нет в сохраняемом перечне, значит при редактировании он был удален - удаляем его из БД
			if(!$such_content_record_exist)
			{
				if( $db_link->prepare("DELETE FROM `content` WHERE `id` = ?;")->execute( array($content_record["id"]) ) != true)
				{
					throw new Exception("SQL-ошибка удаления материалов (DELETE)");
				}
			}
		}
		
		
		//Сохраняем права доступа материалам
		$SQL_ACCESS_TURNING = "";
		for($i=0; $i<count($linear_array); $i++)
		{
			//Удаляем предыдщие записи по доступу
			if( $db_link->prepare("DELETE FROM `content_access` WHERE `content_id` = ?;")->execute( array($linear_array[$i]->id) ) != true)
			{
				throw new Exception("SQL-ошибка предварительной очистки записей прав доступа к материалам");
			}
			
			//Создаем новые записи
			for($j=0; $j<count($linear_array[$i]->groups_access); $j++)
			{
				if( $db_link->prepare("INSERT INTO `content_access` (`content_id`, `group_id`) VALUES (?, ?);")->execute( array($linear_array[$i]->id, $linear_array[$i]->groups_access[$j]) ) != true )
				{
					throw new Exception("SQL-ошибка добавления записей прав доступа к материалам");
				}
			}
		}
	}
	catch (Exception $e)
	{
		$db_link->rollBack();//Откатываем все изменения
		
		//Ошибка
		$error_message = urlencode($e->getMessage().". Изменения не записаны в базу данных");
		?>
		<script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/content_tree?error_message=<?php echo $error_message; ?>";
        </script>
		<?php
		exit;
	}
	
	
	//Дошли сюда - значит все запросы выполнены без ошибок
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	
	//Выполнено успешно
	$success_message = urlencode("Дерево материалов сохранено успешно!");
	?>
	<script>
		location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/content_tree?success_message=<?php echo $success_message; ?>";
	</script>
	<?php
	exit;
	
}//Сохранение материалов
else//если действий нет - выводим страницу
{
    require_once("content/content/get_content_records.php");//Получение объекта иерархии существующих материалов для вывода в дерево-webix
    
	//Получаем ID следующего материала
	$next_id_query = $db_link->prepare("SHOW TABLE STATUS LIKE 'content'");
	$next_id_query->execute();
	$next_id_record = $next_id_query->fetch();
	if( $next_id_record == false )
	{
		exit("SQL error: next_id_query");
	}
    $next_id = $next_id_record["Auto_increment"];//ID следующего добавляемого материала
	
	
	
	
    //Получить текущий главный материал (id дерева webix)
    $get_main_id_query = $db_link->prepare('SELECT * FROM `content` WHERE `main_flag`=1 AND `is_frontend`=?;');
	$get_main_id_query->execute( array($is_frontend) );
    $get_main_id_record = $get_main_id_query->fetch();
    $current_main_id = $get_main_id_record["id"];
    
    //Для дерева групп
    require_once("content/users/dp_group_record.php");//Определение класса записи группы
    require_once("content/users/get_group_records.php");//Получение объекта иерархии существующих групп для вывода в дерево-webix
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
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить</div>
				</a>
				
				<a class="panel_a" onClick="delete_selected_item();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить</div>
				</a>
				
				<a class="panel_a" onClick="unselect_tree();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять выделение</div>
				</a>
				
				
				<a class="panel_a" onClick="published_flag_invert();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/public.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Публикация</div>
				</a>
				
				
				<a class="panel_a" onClick="save_tree();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				
				<a class="panel_a" onClick="edit_selected_content();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/content_edit.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Редактировать</div>
				</a>
				
				
				<a class="panel_a" onClick="setMain();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/star.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Установить главным</div>
				</a>
				
				
				
				
				<script>
				//Функция установки режима редактирования материалов Фронтэнд/Бэкэнд
				function set_edit_mode(mode)
				{
					$.getJSON("<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/control/set_edit_mode_cookie.php?edit_mode="+encodeURI(mode)+"&callback=?", function(data){
							location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/content_tree";
						});
				}
				</script>
				
				<?php
				if($is_frontend)
				{
				?>
					<a class="panel_a" onClick="set_edit_mode('backend');" href="javascript:void(0);">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/backend_edit.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Редактировать бэкэнд</div>
					</a>
				<?php
				}
				else
				{
				?>
					<a class="panel_a" onClick="set_edit_mode('frontend');" href="javascript:void(0);">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/frontend_edit.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Редактировать фронтэнд</div>
					</a>
				<?php
				}
				?>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Дерево материалов
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
				Параметры выбранного материала
			</div>
			<div class="panel-body">
				<div id="content_info_div">
				</div>
			</div>
		</div>
	</div>
	
	
	

    

    
    <!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none">
        <input name="save_tree" id="save_tree" type="text" value="ok" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
    </form>
    <!--Форма для отправки-->
    
    
    
    <script type="text/javascript" charset="utf-8">
    var next_id = <?php echo $next_id;?>;//Следующий id
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

        	    //Индикация системного материала
        	    var icon_system = "";
        	    if(obj.system_flag == true)
                {
                    icon_system = "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/gear.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                }
        	    
        	    //Индикация материала, снятого с публикации
        	    if(obj.published_flag == false)
                {
                    icon_system += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                    value_text = "<span style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
                }
        	    
        	    //Индикация главного материала
        	    if(obj.main_flag == 1)
                {
                    icon_system += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/star.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                    value_text = "<span style=\"font-weight:bold\">" + obj.value + "</span>";//Вывод текста
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
    /*~ДЕРЕВО*/
	webix.event(window, "resize", function(){ tree.adjust(); });
    //-----------------------------------------------------
    webix.protoUI({
        name:"editlist" // or "edittree", "dataview-edit" in case you work with them
    }, webix.EditAbility, webix.ui.list);
    
    
    //-----------------------------------------------------
    
    //Инвертирование флага "Опубликован"
    function published_flag_invert()
    {
        var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    alert("Необходимо выделить узел дерева");
    	    return;
    	}
    	node = tree.getItem(node_id);//Выделенный узел
    	
    	if(node.main_flag == true)
    	{
    	    alert("Нельзя снимать с публикации главный материал");
    	    return;
    	}
		
		
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
			if(checkBranchAttribute(node_id, "system_flag"))
			{
				alert("Включена защита от изменения и удаления системных материалов. Изменения, которые Вы попытались внести, затрагивают один или несколько системных материалов. Действие отменено.");
				return false;
			}
			<?php
		}
		?>
		
    	
    	if(node.published_flag == 1)
    	{
    	    node.published_flag = 0;
    	}
    	else
    	{
    	    node.published_flag = 1;
    	}
    	tree.refresh();
    }
    
    //-----------------------------------------------------
    //Редактировать выделенный материал
    function edit_selected_content()
    {
        var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    alert("Необходимо выделить узел дерева");
    	    return;
    	}
		
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
			if(checkBranchAttribute(node_id, "system_flag"))
			{
				alert("Включена защита от изменения и удаления системных материалов. Изменения, которые Вы попытались внести, затрагивают один или несколько системных материалов. Действие отменено.");
				return false;
			}
			<?php
		}
		?>
		
    	node = tree.getItem(node_id);//Выделенный узел

    	
    	//Предупреждение о том, что данный материал системный
    	if(node.system_flag == true)
    	{
    	    if(!confirm("Данный материал является системным. Вы уверены, что хотите его отредактировать?"))
    	    {
    	        return;
    	    }
    	}
		
		
		//Данный материал еще не записан в базу данных CMS
		if(node.new_content == true)
		{
			alert("Данный материал еще не записан в базу данных CMS. Прежде, чем его редактировать, нажмите кнопку Сохранить");
    	    return;
		}
			
    	
    	
    	location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/edit_content?content_id="+node.id;
    }
    
    //-----------------------------------------------------
    
    var current_main_id = <?php echo $current_main_id;?>;
    //Назначить главным
    function setMain()
    {
        var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    alert("Необходимо выделить узел дерева");
    	    return;
    	}
    	if(node_id == current_main_id)//Материал уже главный
    	{
    	    return;
    	}
    	
    	node = tree.getItem(node_id);//Выделенный узел
    	node.main_flag = 1;
    	
    	last_main_node = tree.getItem(current_main_id);
    	last_main_node.main_flag = 0;
    	
    	current_main_id = node_id;
    	
    	tree.refresh();
    }
    
    //-----------------------------------------------------
    //Событие при выборе элемента дерева
    tree.attachEvent("onAfterSelect", function(id)
    {
    	onSelected();
    });
    //Обработка выбора элемента
	var check_system_flag_semafor = true;//Семафор "Проверять флаг системного материала"
    function onSelected()
    {
        //Если материалы не созданы
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
    	
    	var parameters_table_html = "";

		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">ID</label><div class=\"col-lg-6\">"+node.id+"</div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Название</label><div class=\"col-lg-6\">"+node.value+"</div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Уровень вложенности</label><div class=\"col-lg-6\">"+node.$level+"</div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">ID родителя</label><div class=\"col-lg-6\">"+node.$parent+"</div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Alias</label><div class=\"col-lg-6\"><input onkeyup=\"apply_options_for_content();\" type=\"text\" id=\"alias_input\" value=\""+node.alias+"\" class=\"form-control\" /></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Пояснение</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onkeyup=\"apply_options_for_content();\" id=\"description_input\">"+node.description+"</textarea></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Тег title</label><div class=\"col-lg-6\"><input class=\"form-control\" onkeyup=\"apply_options_for_content();\" type=\"text\" id=\"title_tag_input\" value=\""+node.title_tag+"\"/></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета description</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onkeyup=\"apply_options_for_content();\" id=\"description_tag_input\">"+node.description_tag+"</textarea></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета keywords</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onkeyup=\"apply_options_for_content();\" id=\"keywords_tag_input\">"+node.keywords_tag+"</textarea></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета robots</label><div class=\"col-lg-6\"><input class=\"form-control\" onkeyup=\"apply_options_for_content();\" type=\"text\" id=\"robots_tag_input\" value=\""+node.robots_tag+"\"/></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">CSS и JS</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onkeyup=\"apply_options_for_content();\" id=\"css_js_input\">"+node.css_js+"</textarea></div></div>";
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		parameters_table_html += "<div class=\"col-lg-12\"><label for=\"\" class=\"col-lg-12 control-label\">Права доступа для групп пользователей</label></div>";
		
		parameters_table_html += "<div class=\"col-lg-12\"><div id=\"container_G\" style=\"height:150px;\"></div></div>";
		
		

    	document.getElementById("content_info_div").innerHTML = parameters_table_html;
    	
    	//Теперь инициализируем дерево групп
    	groups_tree_init();
    	
    	//Отмечаем допущенные группы:
		check_system_flag_semafor = false;//Семафор. Пока отмечаем группы - не проверять флаг "Системный материал"
		var groups_access_local = node.groups_access;
    	for(var i=0; i< groups_access_local.length; i++)
    	{
    	    groups_tree.checkItem(groups_access_local[i]);
    	}
		check_system_flag_semafor = true;//Снова выставляем семафор
    }//function onSelected()
    //-----------------------------------------------------
    var groups_tree = "";//ПЕРЕМЕННАЯ ДЛЯ ДЕРЕВА ГРУПП
        	    
    //Инициализация дерева групп после загруки страницы
    function groups_tree_init()
    {
        /*ДЕРЕВО*/
        //Формирование дерева
        groups_tree = new webix.ui({
        
            //Шаблон элемента дерева
        	template:function(obj, common)//Шаблон узла дерева
            	{
                    var folder = common.folder(obj, common);
            	    var icon = "";
                    
                    <?php
                    //Для материалов бэкэнда делаем доступными только группы бэкэнда
                    if(!$is_frontend)
                    {
                        ?>
                        var checkbox = "";
                        var value_text = "";
                        if(is_group_for_backend(obj.id) == true)//ГРУППА ДОСТУПНА
                        {
                            checkbox = common.checkbox(obj, common);//Чекбокс
                            value_text = "<span>" + obj.value + "</span>";//Вывод текста
                        }
                        else//НЕ ДОСТУПНА
                        {
                            checkbox = common.checkbox(obj, common);
                            checkbox = "<input type='checkbox' class='webix_tree_checkbox' disabled='disabled'>";
                            
                            value_text = "<span><font style=\"color:#AAA\">" + obj.value + "</font></span>";//Вывод текста
                        }
                        <?php
                    }
                    else//Для фронтэнда - все группы доступны
                    {
                        ?>
                        var value_text = "<span>" + obj.value + "</span>";//Вывод текста
                        var checkbox = common.checkbox(obj, common);//Чекбокс
                        <?php
                    }
                    ?>
                    
                    
                    
                    if(obj.for_registrated == true)
                    {
                        icon += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/check.png' class='col_img' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
                    }
                    if(obj.for_guests == true)
                    {
                        icon += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/guest.png' class='col_img' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
                    }
                    if(obj.for_backend == true)
                    {
                        icon += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/shield.png' class='col_img' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
                    }
                    if(obj.unblocked == 0)
                    {
                        icon += "<img src='/<?php echo $DP_Config->backend_dir;?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='width:18px; height:18px; float:right; margin:0px 4px 8px 4px;'>";
                    }
                    
                    return common.icon(obj, common)+ checkbox + common.folder(obj, common)  + icon + value_text;
            	},//~template
        
            editable:false,//редактируемое
            container:"container_G",//id блока div для дерева
            view:"tree",
        	select:true,//можно выделять элементы
        	drag:false,//можно переносить
        });
        /*~ДЕРЕВО*/
		
		webix.event(window, "resize", function(){ groups_tree.adjust(); });
		
    	var saved_groups = <?php echo $group_tree_dump_JSON; ?>;
	    groups_tree.parse(saved_groups);
	    groups_tree.openAll();
		
		
		//Событие при выставлении/снятии чекбоксов групп - динамичнское применение настроек
		groups_tree.attachEvent("onItemCheck", function(id)
		{
			apply_options_for_content();
		});
    }
    //-----------------------------------------------------
    //Функция проверки группы на доступ к бэкэнду. Рекурсивный вызов для проверки родительских групп
    function is_group_for_backend(node_id)
    {
        var node = groups_tree.getItem(node_id);//Объект узла группы
    
        if(node.for_backend == true)
        {
            return true;
        }
        
        if(node.$parent != 0)
        {
            return is_group_for_backend(node.$parent);
        }
        else
        {
            return false;
        }
    }
    //-----------------------------------------------------
    
    //Событие при успешном редактировании элемента дерева
    tree.attachEvent("onValidationSuccess", function(){
        onSelected();
    });
    //-----------------------------------------------------
	//Событие двойного клика узла. Проверяем, не является ли системным.
	tree.attachEvent("onItemDblClick", function(id, e, node)
	{
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
			if(checkBranchAttribute(id, "system_flag"))
			{
				alert("Включена защита от изменения и удаления системных материалов. Действие отменено.");
				return false;
			}
			<?php
		}
		?>
	});
    //-----------------------------------------------------
	//Событие завершения редактирования названия узла - присваиваем автоматом поле alias
	tree.attachEvent("onAfterEditStop", function(state, editor, ignoreUpdate)
	{    
        //Задаем поле Alias - как транслитерация поля value;
        var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);//Выделенный узел
        
		//Алиас автоматически заполняем транслитом ТОЛЬКО если его значение пустое
		if( node.alias == "" )
		{
			node.alias = iso_9_translit(node.value,  5);//5 - русский текст
			node.alias = node.alias.replace(/\s/g, '-');
			node.alias = node.alias.toLowerCase();
			node.alias = node.alias.replace(/[^\d\sA-Z\-_]/gi, '');//Убираем все символы кроме букв, цифр, тире и нинего подчеркивания
		}
		
        onSelected();
    });
    //-----------------------------------------------------
	//Обработчик После перетаскивания узлов дерева
	tree.attachEvent("onAfterDrop",function(){
	    onSelected();
	});
    //-----------------------------------------------------
    //Применить настройки для материала
    function apply_options_for_content()
    {
        //1. Определяем выбранный материал
        var node_id = tree.getSelectedId();//ID выделенного узла
		if(node_id == 0)
		{
			return;
		}
		
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Проверяем, если есть семафор
			if( check_system_flag_semafor )
			{
				//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
				if(checkBranchAttribute(node_id, "system_flag"))
				{
					alert("Включена защита от изменения и удаления системных материалов. Изменения, которые Вы попытались внести, затрагивают один или несколько системных материалов. Действие отменено.");
					onSelected();
					return;
				}
			}
			<?php
		}
		?>
		

    	node = tree.getItem(node_id);//Выделенный узел

        //2. Сохраняем alias - это обязательное поле
		node.alias = document.getElementById("alias_input").value;
		
        //3. Сохраняем Пояснение
        node.description = document.getElementById("description_input").value;
        
        //4. Сохраняем title_tag
        node.title_tag = document.getElementById("title_tag_input").value;
        
        //5. Сохраняем description_tag
        node.description_tag = document.getElementById("description_tag_input").value;
        
        //6. Сохраняем keywords_tag
        node.keywords_tag = document.getElementById("keywords_tag_input").value;
        
        //7. Сохраняем robots_tag
        node.robots_tag = document.getElementById("robots_tag_input").value;
        
        //7.1 Сохраняем css_js
        var css_js_input = document.getElementById("css_js_input").value.replace(/\n/g, "\r\n");
        node.css_js = css_js_input;
        
        
        //8. Сохраняем права доступа в виде массива отмеченных групп
        node.groups_access = new Array();//Массив с выбранными группами
        node.groups_access = groups_tree.getChecked();
        
        //Сообщение о результате предварительного сохранения
        //webix.message("Настройки материала предварительно сохранены");
    }
    //-----------------------------------------------------
    //Метод построения полных url для всех узлов
    function buildAllUrl(node_id)
    {
        
    }//~function buildFullUrl(node_id) 
    //----------------------------------------------------
    //Обработчик До перетаскивания узлов дерева
	tree.attachEvent("onBeforeDrop",function(context)
	{
	    var node_id = context.source;//ID переносимого элемента
		
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
			if(checkBranchAttribute(node_id, "system_flag"))
			{
				alert("Включена защита от изменения и удаления системных материалов. Изменения, которые Вы попытались внести, затрагивают один или несколько системных материалов. Действие отменено.");
				return false;
			}
			<?php
		}
		?>
		
	    var node_parent_id = tree.getItem(node_id).$parent;//ID исходного родителя элемента
	    
	    var target_id = context.target;//ID того элемента, на место которого переносим
	    var target_parent_id = tree.getItem(target_id).$parent;//ID целевого родителя элемента
	    
	    //Если элемент остался на том же уровне той же ветви - перенос допустим
	    if(node_parent_id == target_parent_id)
	    {
	        //webix.message("ОТЛАДКА: уровень тот же");
	        return true;
	    }
	    
	    //Если уровень элемента изменился, то необходимо убедиться в отсутствии на новом уровне узлов с таким же атрибутом name
	    //function isAliasRepeated(parent_id, alias, node_id)
	    if(isAliasRepeated(target_parent_id, tree.getItem(node_id).alias, node_id))
	    {
	        webix.alert({
                        title: "Ошибка",
                        text: "В месте назначение уже есть материал с таким же Alias. Перенос не выполнен",
                        type:"confirm-warning"
                    });
	        return false;
	    }
	});//~tree.attachEvent("onAfterDrop",function(context)
    //-----------------------------------------------------
    //Метод проверки существования повторяющихся значений атрибута alias на одном уровне
    //parent_id - родитель уровня; alias - проверяемое значение атрибута; except_node_id - узел, который не должен участвовать в проверке (например, когда сравнивается его собственное значение)
    function isAliasRepeated(parent, alias, except_node_id)
    {
        if(alias == "") return false;//Пустые значения вообще не проверяем
        
        if(parent == 0)//Работаем с узлами верхнего уровня
        {
            var first_id_same_level = tree.getFirstChildId(0);//Получаем Id самого первого узла дерева - он в любом случае на верхнем уровне
            var current_id = 0;//ID текущего проверяемого узла
            while(true)
            {
                //Сначала опрелеляем id текущего проверяемого узла
                if(current_id == 0)//Т.е. первая итерация цикла
                {
                    current_id = first_id_same_level;//Первый узел на уровне (в данном случае - первый узел дерева)
                }
                else
                {
                    current_id = tree.getNextSiblingId(current_id);//Получаем id следующего узла
                    if(current_id == null || current_id == false)//Больше узлов нет
                    {
                        break;
                    }
                }
                if(except_node_id == current_id)//Сам узел - пропускаем
                {
                    continue;
                }
                if(tree.getItem(current_id).$parent != 0)//Это может быть вложенный элемент (т.е. его вернул метод getNextSiblingId()). Он не должен проходить эту проверку, т.к. мы проверяем в данном случае только узлы верхнего уровня
                {
                    continue;
                }
                //Проверяемый узел подлежит проверке значения:
                var current_checked_node = tree.getItem(current_id);//Проверяемый узел
                if(current_checked_node.alias == alias)
                {
                    return true;//АТРИБУТ alias ПОВТОРЯЕТСЯ
                }
            }//~while(true)
        }//~if()
        else//Работаем с вложеженными узлами одного уровня одной ветви
        {
            var node_parent = tree.getItem(parent);//Родительский узел
            var first_id_same_level = tree.getFirstChildId(parent);//Получаем id первого узла на этом уровне в этой ветви
            var current_id = 0;//ID текущего проверяемого узла
            for(var i=0; i<node_parent.$count; i++)
            {
                //Сначала опрелеляем id текущего проверяемого узла
                if(i==0)
                {
                    current_id = first_id_same_level;//Первый узел на уровне
                }
                else
                {
                    current_id = tree.getNextSiblingId(current_id);//Получаем id следующего узла
                }
                if(except_node_id == current_id)//Проверяемый узел
                {
                    continue;
                }
                var current_checked_node = tree.getItem(current_id);//Проверяемый узел
                if(current_checked_node.alias == alias)
                {
                    return true;//АТРИБУТ alias ПОВТОРЯЕТСЯ
                }
            }//~for
        }
        
        return false;//Повторений атрибута не найдено
    }//~function isAliasRepeated(parent, alias, except_node_id = 0)
    //-----------------------------------------------------
    //Добавить новый элемент в дерево
    function add_new_item()
    {
    	//Добавляем элемент в выделенный узел
    	var parentId= tree.getSelectedId();//Выделеный узел
    	var newItemId = tree.add( {value:"Новый материал", id:next_id, alias:"", description:"", groups_access:[], url:"", main_flag:0, title_tag:"", description_tag:"", keywords_tag:"", robots_tag:"", system_flag:0, published_flag:1, css_js:"", new_content:true}, 0, parentId);//Добавляем новый узел и запоминаем его ID
    	onSelected();//Обработка текущего выделения
    	next_id++;//Следующий ID материала
    	tree.open(parentId);//Раскрываем родительский узел
    }
    //-----------------------------------------------------
    //Удаление выделеного элемента
    function delete_selected_item()
    {
    	var nodeId = tree.getSelectedId();
    	
    	//Проверяем, не удалится ли главный материал:
    	if(checkBranchAttribute(nodeId, "main_flag"))
    	{
    	    alert("Нельзя удалять главный материал");
    	    return;
    	}
		
		<?php
		//Защита от изменения и удаления системных материалов
		if( $DP_Config->allow_edit_system_content != true )
		{
			?>
			//Рекурсивно проверяем каждый элемент ветви начиная с nodeId. Если хотя бы в одном выставлен флаг system_flag - прерываем выполнение функции
			if(checkBranchAttribute(nodeId, "system_flag"))
			{
				alert("Включена защита от изменения и удаления системных материалов. Изменения, которые Вы попытались внести, затрагивают один или несколько системных материалов. Действие отменено.");
				return;
			}
			<?php
		}
		?>
    	
    	
    	//Проверяем, не удаляется ли системный материал
    	if(checkBranchAttribute(nodeId, "system_flag"))
    	{
    	    //Предупреждение о том, что данный материал или его вложенный материал -  системный (один или несколько)
        	if(!confirm("Вы уверены, что хотите удалить системный материал?"))
        	{
        		return;
        	}
    	}
    	
    	
    	tree.remove(nodeId);
    	onSelected();
    }
    //-----------------------------------------------------
    //Метод проверки выставления флаговых аттрибутов в ветви. Применяется при удалении узла
    //true - хотя бы один атрибут выставлен, false - ни одного не выставлено
    function checkBranchAttribute(node_id, attribute)
    {
        var node = tree.getItem(node_id);//Объект узла
        
        //Проверяем атрибут в узле
        if(node[attribute] == true)
        {
            return true;
        }
        
        if(node.$count > 0)
        {
            var node_in_id = tree.getFirstChildId(node_id);//ID первого вложенного элемента
            
            //Проверяем
            if(checkBranchAttribute(node_in_id, attribute))
            {
                return true;
            }
            
            //Если вложенных больше двух - далее по циклу
            for(var i=1; i < node.$count; i++)
            {
                node_in_id = tree.getNextSiblingId(node_in_id);
            
                if(checkBranchAttribute(node_in_id, attribute))
                {
                    return true;
                }
            }
        }
        
        //Сам узел и все его вложенные узлы не содержат выставленный атрибут
        return false;
    }
    //-----------------------------------------------------
    //Отладочный метод
    function debug_func()
    {
    	
    }
    //-----------------------------------------------------
    //Снятие выделения с дерева
    function unselect_tree()
    {
    	tree.unselect();
    	onSelected();
    }
    //-----------------------------------------------------
    //Сохранение перечня материалов
    function save_tree()
    {
        //1. Проверить каждый узел дерева на предмет заполненности всех параметров
        //Если где-то не хватает данных - прервать метод и выдать сообщение об ошибке
        //В этом же методе одновременно проставляются полные url каждого элемента
        var tree_In_JSON = tree.serialize();//Получаем JSON-представление дерева
        if( ! checkEveryOneInArray(tree_In_JSON))//Передаем JSON представление дерева в рекурсивный метод проверки атрибутов
        {
            return false;
        }
        
    	//Получаем строку JSON:
    	var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
    	
    	//Задаем значение поля в форме:
    	var tree_json_input = document.getElementById("tree_json");
    	tree_json_input.value = tree_dump;

    	document.forms["form_to_save"].submit();//Отправляем
    }
    //-----------------------------------------------------
    //Метод перебора всех элементов дерева - рекурсивный. Осуществляется проверка наличия значений атрибутов и простановка полных URL для каждого материала
    function checkEveryOneInArray(level_array)
    {
        for(var i=0; i<level_array.length; i++)
        {
			//ПРОВЕРКА ЗАПОЛНЕНИЯ АЛИАС
			if(level_array[i]["alias"] == "")
			{
				webix.alert({
					title: "Ошибка",
					text: "<div align='left'><b>В материале \""+level_array[i]["value"]+"\" (ID "+level_array[i]["id"]+") поле Алиас не заполнено</b>",
					type:"confirm-error"
				});
				return false;
			}
			
			//ПРОВЕРКА УНИКАЛЬНОСТИ АЛИАС
			var node_id_parent = level_array[i]["$parent"];
			if( isAliasRepeated(node_id_parent, level_array[i]["alias"], level_array[i]["id"] ) )
			{
				webix.alert({
					title: "Ошибка",
					text: "<div align='left'><b>В материале \""+level_array[i]["value"]+"\" (ID "+level_array[i]["id"]+") поле Алиас дублируется с одним или несколькими материалами в той же ветви</b>",
					type:"confirm-error"
				});
				return false;
			}
			
			//ПРОВЕРКА ЗАПОЛНЕНИЯ ПОЯСНЕНИЯ
			if(level_array[i]["description"] == "")
			{
				webix.alert({
					title: "Ошибка",
					text: "<div align='left'><b>В материале \""+level_array[i]["value"]+"\" (ID "+level_array[i]["id"]+") поле Пояснение не заполнено</b>",
					type:"confirm-error"
				});
				return false;
			}
			
            //Здесь можно поставить полный URL для данного материала (узла), т.к. он сам и элементы из его ветви всех уровней вышего него прошли проверку
            var node = tree.getItem(level_array[i]["id"]);//Получаем объект узла дерева
            if(node.$level == 1)//Для верхних элементов, их полные url равны их алиасам
            {
                node.url = node.alias;
            }
            else//Для вложенных элементов, их url равны <url родителя>+"/"+<свой алиас>
            {
                node.url = tree.getItem(node.$parent).url + "/" + node.alias;
            }
            
            
            //Рекурсивный вызов для вложенного уровня
            if(level_array[i]['$count'] > 0)
            {
                if(checkEveryOneInArray(level_array[i]["data"]) == false)
                {
                    return false;//Если метод вернул false - дальше проверять нет смысла - выходим
                }
            }
        }
        
        return true;
    }//~function checkEveryOneInArray(level_array)
    //-----------------------------------------------------
    //Инициализация редактора дерева материалов после загруки страницы
    function content_start_init()
    {
    	var saved_content = <?php echo $content_tree_dump_JSON;?>;
	    tree.parse(saved_content);
    }
    content_start_init();
    onSelected();//Обработка текущего выделения
    </script>
    
    
    <!-- Блок для предварительной подгрузки изображений -->
    <div style="width: 0px; height: 0px; display: inline; background-image: url('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/content/images/folder_open_main.png'); background-image: url('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/content/images/folder_close_main.png'); background-image: url('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/content/images/folder_open.png'); background-image: url('<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/content/images/folder_close.png');"></div>

<?php
}//else - выводим страницу
?>