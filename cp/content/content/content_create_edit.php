<?php
//Страничный скрипт создания и редактирования одного материала - для схемы работы "No tree"
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
//Рекурсивная функция обновления вложенных узлов
function handle_child_nodes($content_id)
{
	global $db_link;
	
	//Получаем нужные поля данного узла
	$content_data_query = $db_link->prepare("SELECT `url`, `level`, `count` FROM `content` WHERE `id` = ?;");
	if( ! $content_data_query->execute( array($content_id) ) )
	{
		return false;
	}
	$content_data_record = $content_data_query->fetch();
	if( $content_data_record == false )
	{
		return false;
	}
	
	
	$url = $content_data_record["url"];
	$level = $content_data_record["level"];
	$count = $content_data_record["count"];
	
	if( $count == 0 )
	{
		return true;
	}
	
	
	//Получаем список вложенных узлов
	$child_nodes_query = $db_link->prepare("SELECT `id`,`alias`,`count` FROM `content` WHERE `parent` = ?;");
	if( ! $child_nodes_query->execute( array($content_id) ) )
	{
		return false;
	}
	while( $child_node = $child_nodes_query->fetch() )
	{
		$child_alias = $child_node["alias"];
		$child_id = $child_node["id"];
		$child_count = $child_node["count"];
		
		//Сначала меняем level и url
		if( ! $db_link->prepare("UPDATE `content` SET `level` = ?+1, `url` = ? WHERE `id` = ?;")->execute( array($level, $url."/".$child_alias, $child_id  ) ) )
		{
			return false;
		}
		
		//Рекурсивный вызов для вложенных узлов
		if( $child_count > 0 )
		{
			//Если была ошибка хотя бы на одном узле - возращаем false
			if( ! handle_child_nodes($child_id) )
			{
				return false;
			}
		}
	}
	
	return true;
}



if( !empty($_POST["action"]) )
{
	$time = time();
	$content = json_decode($_POST["content_object"], true);
	
	
	//Обработка корректности json_decode($_POST["content_object"], true)
	$content_fields_names = array("content_id", "alias", "value", "parent", "description", "is_frontend", "content_type", "content", "title_tag", "description_tag", "keywords_tag", "author_tag", "main_flag", "css_js", "robots_tag", "published_flag", "groups_access", "check_hash");
	for( $i=0; $i < count($content_fields_names) ; $i++)
	{
		if( ! isset($content[$content_fields_names[$i]]) )
		{
			?>
			<script>
			alert("Ошибка json_decode(). Изменения не будут записаны в базу данных. Вы будете автоматически перенаправлены на страницу \"Менеджера материалов\"");
			
			
			location = "/<?php echo $DP_Config->backend_dir; ?>/content/content_manager?error_message=<?php echo urlencode("Ошибка json_decode(). Изменения не записаны в базу данных."); ?>";
			</script>
			<?php
			exit;
		}
	}
	
	//Проверка хеша (content_id, is_frontend)
	if( md5( $content["content_id"].$content["is_frontend"].$DP_Config->secret_succession ) != $content["check_hash"] )
	{
		?>
		<script>
		alert("Ошибка проверки хеша. Изменения не будут записаны в базу данных. Вы будете автоматически перенаправлены на страницу \"Менеджера материалов\"");
		
		
		location = "/<?php echo $DP_Config->backend_dir; ?>/content/content_manager?error_message=<?php echo urlencode("Ошибка проверки хеша. Изменения не записаны в базу данных."); ?>";
		</script>
		<?php
		exit;
	}
	
	
	
	//Защита от изменения режима редактирования (если, к примеру, пользователь переключил режим на другой странице, открытой параллельно)
	if( $is_frontend != $content["is_frontend"] )
	{
		$current_mode_name = "Фронтенд";
		$content_side_name = "Бэкенд";
		if( !$is_frontend )
		{
			$current_mode_name = "Бэкенд";
			$content_side_name = "Фронтенд";
		}
		
		?>
		<script>
		alert("Внимание! Текущий режим редактирования \"<?php echo $current_mode_name; ?>\" не соответствует данному материалу, относящемуся к режиму \"<?php echo $content_side_name; ?>\". Возможно вы переключили режим редактирования, находясь на другой странице панели управления. Изменения не будут записаны в базу данных. Вы будете автоматически перенаправлены на страницу \"Менеджера материалов\"");
		
		
		location = "/<?php echo $DP_Config->backend_dir; ?>/content/content_manager?error_message=<?php echo urlencode("Не допускается переключать режим в процессе создания или редактирования материала."); ?>";
		</script>
		<?php
		exit;
	}
	
	
	if($content["content_id"] == 0)//Создание материала
	{
		//Формируем переменные для SQL-запроса INSERT
		$count = 0;
		$url = $content["alias"];//!!! ДАЛЕЕ ЗАВИСИТ ОТ PARENT
		$level = 1;//!!! ДАЛЕЕ ЗАВИСИТ ОТ PARENT
		$alias = $content["alias"];
		$value = $content["value"];
		$parent = $content["parent"];
		$description = $content["description"];
		$is_frontend = $content["is_frontend"];
		$content_type = $content["content_type"];
		$content_content = $content["content"];
		$title_tag = $content["title_tag"];
		$description_tag = $content["description_tag"];
		$keywords_tag = $content["keywords_tag"];
		$author_tag = $content["author_tag"];
		$main_flag = $content["main_flag"];
		$modules_array = "[]";
		$css_js = $content["css_js"];
		$robots_tag = $content["robots_tag"];
		$system_flag = 0;
		$published_flag = $content["published_flag"];
		$open = 0;
		$time_created = $time;
		//$time_edited = $time;
		$order = 1;
		
		try
		{
			//Старт транзакции
			if( ! $db_link->beginTransaction()  )
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			if($parent > 0)//Создаваемый материал - вложен, значит нужно сформировать поля в зависимости от родительского узла
			{
				//Получаем данные родительского узла
				$parent_query = $db_link->prepare('SELECT `level`,`url`, `is_frontend` FROM `content` WHERE `id` = ?;');
				if( $parent_query->execute( array($parent) ) == false )
				{
					//SQL-ошибка получения данных родительского узла
					throw new Exception("SQL-ошибка получения данных родительского узла");
				}
				$parent_record = $parent_query->fetch();
				
				if( $parent_record == false )
				{
					//Ошибка определения данных родительского узла
					throw new Exception("Ошибка определения данных родительского узла");
				}
				
				$parent_level = $parent_record["level"];
				$parent_url = $parent_record["url"];
				$parent_is_frontend = $parent_record["is_frontend"];
				
				if( $is_frontend != $parent_is_frontend )
				{
					throw new Exception("Поле is_frontend родительского узла не равен полю is_frontend создаваемого узла. Возможно было ручное редактирования на уровне html");
				}
				
				
				//Изменяем данные материала для INSERT
				$level = $level + $parent_level;
				$url = $parent_url."/".$url;
			}
			
			
			//Добавляем сам материал
			$SQL_INSERT = "INSERT INTO `content` (`count`,`url`,`level`,`alias`,`value`,`parent`,`description`,`is_frontend`,`content_type`,`content`,`title_tag`,`description_tag`,`keywords_tag`,`author_tag`,`main_flag`,`modules_array`,`css_js`,`robots_tag`,`system_flag`,`published_flag`,`open`,`time_created`,`order`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
			
			$binding_values = array($count, $url, $level, $alias, $value, $parent, $description, $is_frontend, $content_type, $content_content, $title_tag, $description_tag, $keywords_tag, $author_tag, $main_flag, $modules_array, $css_js, $robots_tag, $system_flag, $published_flag, $open, $time_created, $order);

			if( ! $db_link->prepare($SQL_INSERT)->execute($binding_values) )
			{
				throw new Exception("SQL-ошибка добавления материала");
			}
			//Материал добавлен - получаем его id
			$content_id = (int)$db_link->lastInsertId();
			
			if( $content_id == 0 )
			{
				throw new Exception("Ошибка получения ID материала");
			}
			
			//Создаваемый материал - вложен, значит нужно обработать родительский узел - добавить count
			if($parent > 0)
			{
				if( ! $db_link->prepare("UPDATE `content` SET `count` = `count`+1 WHERE `id` = ?;")->execute( array($parent) ) )
				{
					throw new Exception("SQL-ошибка инкрементирования count для родительского узла");
				}
			}
			
			//Ставим main_flag = 0 для другого материала, если этот указали главным
			if( $main_flag == 1 )
			{
				if( ! $db_link->prepare('UPDATE `content` SET `main_flag` = 0 WHERE `main_flag` = 1 AND `id` != ? AND `is_frontend` = ?;')->execute( array($content_id, $is_frontend) ) )
				{
					throw new Exception("SQL-ошибка снятия флага Главный для предыдущего материала");
				}
			}
			
			//Добавляем права доступа
			$groups_access = json_decode($content["groups_access"], true);
			if( count($groups_access) > 0 )
			{
				$binding_values = array();
				
				$SQL_content_access = "INSERT INTO `content_access` (`content_id`, `group_id`) VALUES ";
				for($i=0; $i < count($groups_access); $i++)
				{
					if($i > 0)
					{
						$SQL_content_access .= ",";
					}
					$SQL_content_access .= " (?, ?) ";
					
					array_push($binding_values, $content_id);
					array_push($binding_values, $groups_access[$i]);
				}
				if( ! $db_link->prepare($SQL_content_access)->execute( $binding_values ) )
				{
					throw new Exception("SQL-ошибка записи прав доступа");
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
				location="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager/content?error_message=<?php echo $error_message; ?>";
			</script>
			<?php
			exit;
		}
		
		//Дошли сюда - значит все запросы выполнены без ошибок
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		
		//Выполнено успешно
		$success_message = urlencode("Материал успешно создан");
		?>
		<script>
			location="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager/content?content_id=<?php echo $content_id; ?>&success_message=<?php echo $success_message; ?>";
		</script>
		<?php
		exit;
	}
	else//Редактирование материала
	{
		//Формируем переменные для SQL-запроса UPDATE
		$content_id = (int)$content["content_id"];
		//$count = 0;//НЕ МЕНЯЕТСЯ
		$url = $content["alias"];//!!! ДАЛЕЕ ЗАВИСИТ ОТ НОВОГО PARENT
		$level = 1;//!!! ДАЛЕЕ ЗАВИСИТ ОТ НОВОГО PARENT
		$alias = $content["alias"];
		$value = $content["value"];
		$parent = $content["parent"];
		$description = $content["description"];
		$is_frontend = $content["is_frontend"];//НЕ МЕНЯЕТСЯ
		$content_type = $content["content_type"];
		$content_content = $content["content"];
		$title_tag = $content["title_tag"];
		$description_tag = $content["description_tag"];
		$keywords_tag = $content["keywords_tag"];
		$author_tag = $content["author_tag"];
		$main_flag = $content["main_flag"];
		//$modules_array = "";//НЕ МЕНЯЕТСЯ
		$css_js = $content["css_js"];
		$robots_tag = $content["robots_tag"];
		//$system_flag = 0;//НЕ МЕНЯЕТСЯ
		$published_flag = $content["published_flag"];
		//$open = 0;//НЕ МЕНЯЕТСЯ
		//$time_created = $time;//НЕ МЕНЯЕТСЯ
		$time_edited = $time;
		//$order = 1;//НЕ МЕНЯЕТСЯ
		
		//Все действия с БД выполняем с помощью транзакции
		try
		{
			if( ! $db_link->beginTransaction() )//Старт транзакции
			{
				throw new Exception("Не удалось стартовать транзакцию");
			}
			
			//Получаем текущие данные узла
			$current_query = $db_link->prepare('SELECT `parent`, `main_flag`, `system_flag`, `is_frontend` FROM `content` WHERE `id` = ?;');
			if( ! $current_query->execute( array($content_id) ) )
			{
				throw new Exception("SQL-ошибка получения текущих данных материала");
			}
			$current_record = $current_query->fetch();
			
			if( $current_record == false )
			{
				throw new Exception("Ошибка определения текущих данных материала");
			}
			
			$current_parent = $current_record["parent"];
			$current_main_flag = $current_record["main_flag"];
			$system_flag = $current_record["system_flag"];
			$current_is_frontend = $current_record["is_frontend"];
			
			if( $current_is_frontend != $is_frontend )
			{
				throw new Exception("Прежнее значение поля is_frontend не соответствует переданному значению. Возможно было ручное редактирование html");
			}
			
			
			//Защита от изменения и удаления системных материалов
			if( $DP_Config->allow_edit_system_content != true )
			{
				if( $system_flag )
				{
					throw new Exception("Включена защита от изменения или удаления системных материалов. Данный материал - системный");
				}
			}
			
			
			//Получаем данные родительского узла, которые влияют на поля редактируемого материала
			if($parent > 0)
			{
				//Получаем данные родительского узла
				$parent_query = $db_link->prepare( 'SELECT `level`,`url`,`is_frontend` FROM `content` WHERE `id` = ?;' );
				if($parent_query->execute( array($parent) ) == false)
				{
					throw new Exception("SQL-ошибка получения данных родительского узла");
				}
				$parent_record = $parent_query->fetch();
				
				if( $parent_record == false )
				{
					throw new Exception("Ошибка определения данных родительского узла");
				}
				
				$parent_level = $parent_record["level"];
				$parent_url = $parent_record["url"];
				$parent_is_frontend = $parent_record["is_frontend"];
				
				
				if( $is_frontend != $parent_is_frontend )
				{
					throw new Exception("Передан ID родительского узла, который относится к другому режиму редактирования. Возможно было ручное редактирование html");
				}
				
				
				//Изменяем данные материала для UPDATE
				$level = $level + $parent_level;
				$url = $parent_url."/".$url;
			}
			
			
			
			//Обновляем данные материала
			if( ! $db_link->prepare("UPDATE `content` SET `url` = ?, `level` = ?, `alias` = ?, `value` = ?, `parent` = ?, `description` = ?, `content_type` = ?, `content` = ?, `title_tag` = ?, `description_tag` = ?, `keywords_tag` = ?, `author_tag` = ?, `main_flag` = ?, `css_js` = ?, `robots_tag` = ?, `published_flag` = ?, `time_edited` = ? WHERE `id` = ?;")->execute( array($url, $level, $alias, $value, $parent, $description, $content_type, $content_content, $title_tag, $description_tag, $keywords_tag, $author_tag, $main_flag, $css_js, $robots_tag, $published_flag, $time_edited, $content_id) ) )
			{
				throw new Exception("SQL-ошибка обновления данных узла");
			}
			
			
			//Обрабатываем поля count для родительских узлов
			if( $parent != $current_parent )//Был перенос
			{
				//Увеличивем count у нового parent
				if($parent > 0)
				{
					if( ! $db_link->prepare("UPDATE `content` SET `count` = `count`+1 WHERE `id` = ?;")->execute( array($parent) ) )
					{
						throw new Exception("SQL-ошибка инкрементирования count у родительского узла");
					}
				}
				
				//Уменьшаем count с старого родительского узла
				if( $current_parent > 0 )
				{
					if( ! $db_link->prepare("UPDATE `content` SET `count` = `count`-1 WHERE `id` = ?;")->execute( array($current_parent) ) )
					{
						throw new Exception("SQL-ошибка декрементирования count у предыдущего родительского узла");
					}
				}
			}
			
			
			//Если пользователь установил этот материал Главным. (снять его он не мог)
			if( $current_main_flag != $main_flag )
			{
				//Ставим main_flag = 0 для материала, который был главным до этого
				if( ! $db_link->prepare("UPDATE `content` SET `main_flag` = 0 WHERE `main_flag` = 1 AND `id` != ? AND `is_frontend` = ?;")->execute( array($content_id, $is_frontend) ) )
				{
					throw new Exception("SQL-ошибка снятия флага Главный для другого материала");
				}
			}
			
			
			//Обновляем права доступа
			$groups_access = json_decode($content["groups_access"], true);
			if( ! $db_link->prepare("DELETE FROM `content_access` WHERE `content_id` = ?;")->execute( array($content_id) ) )
			{
				throw new Exception("SQL-ошибка очистки старых записей прав доступа");
			}
			if( count($groups_access) > 0 )
			{
				$binding_values = array();
				
				$SQL_content_access = "INSERT INTO `content_access` (`content_id`, `group_id`) VALUES ";
				for($i=0; $i < count($groups_access); $i++)
				{
					if($i > 0)
					{
						$SQL_content_access .= ",";
					}
					$SQL_content_access .= " (?, ?) ";
					
					array_push($binding_values, $content_id);
					array_push($binding_values, $groups_access[$i]);
					
				}
				if( ! $db_link->prepare($SQL_content_access)->execute( $binding_values ) )
				{
					throw new Exception("SQL-ошибка записи прав доступа");
				}
			}
			
			
			//Обработка вложенных узлов
			if( ! handle_child_nodes($content_id) )
			{
				throw new Exception("Возникла ошибка при обработке вложенных узлов");
			}
			
			
			//throw new Exception("Тестовое исключение");
		}
		catch (Exception $e)
		{
			$db_link->rollBack();//Откатываем все изменения и закрываем транзакцию
			//Ошибка получения данных родительского узла
			$error_message = urlencode($e->getMessage().". Изменения не записаны в базу данных");
			?>
			<script>
				location="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager/content?content_id=<?php echo $content_id; ?>&error_message=<?php echo $error_message; ?>";
			</script>
			<?php
			exit;
		}
		
		
		//Дошли сюда - значит все запросы выполнены без ошибок
		$db_link->commit();//Коммитим все изменения и закрываем транзакцию
		//Выполнено успешно
		$success_message = urlencode("Выполнено успешно");
		?>
		<script>
			location="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager/content?content_id=<?php echo $content_id; ?>&success_message=<?php echo $success_message; ?>";
		</script>
		<?php
		exit;
	}
}
else//Действий нет - выводим страницу
{
	?>
	
	<?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	<?php
	$content_id = 0;
	$parent = 0;
	$value = "";
	$alias = "";
	$description = "";
	$content_type = "text";
	$content = "";
	$title_tag = "";
	$description_tag = "";
	$keywords_tag = "";
	$author_tag = "";
	$css_js = "";
	$robots_tag = "";
	$published_flag = 1;
	$main_flag = 0;
	$groups_access = array();
	$parent_value = "Корень дерева";
	
	
	if( !empty( $_GET["content_id"] ) )
	{
		$content_id = (int)$_GET["content_id"];
		
		$content_query = $db_link->prepare("SELECT * FROM `content` WHERE `id` = ?;");
		$content_query->execute( array($content_id) );
		$content_record = $content_query->fetch();
		
		
		if( $content_record == false )
		{
			?>
			<script>
			alert("Внимание! Материал с таким ID не найден. Вы будете перенаправлены на страницу \"Менеджера материалов\"");
			
			location = "/<?php echo $DP_Config->backend_dir; ?>/content/content_manager";
			</script>
			<?php
			exit;
		}
		
		
		//Если пользователь руками передал сюда ID материала, относящегося к другому режиму редактирования - запрещаем его редактировать
		if( $is_frontend != $content_record["is_frontend"] )
		{
			$current_mode_name = "Фронтенд";
			$content_side_name = "Бэкенд";
			if( !$is_frontend )
			{
				$current_mode_name = "Бэкенд";
				$content_side_name = "Фронтенд";
			}
			
			?>
			<script>
			alert("Внимание! Материал с указанным ID относится к части сайта - \"<?php echo $content_side_name; ?>\". При этом текущий режим редактирования - \"<?php echo $current_mode_name; ?>\". Не допускается редактировать материал, относящийся не к текущему режиму редактирования. Вы будете перенаправлены на страницу \"Менеджера материалов\", на которой можно перевести панель управления в другой режим и затем снова открыть данный материал для редактирования.");
			
			location = "/<?php echo $DP_Config->backend_dir; ?>/content/content_manager?error_message=<?php echo urlencode("Не допускается редактировать материал, относящийся не к текущему режиму редактирования"); ?>";
			</script>
			<?php
			exit;
		}
		
		
		$parent = $content_record["parent"];
		$value = $content_record["value"];
		$alias = $content_record["alias"];
		$description = $content_record["description"];
		$content_type = $content_record["content_type"];
		$content = $content_record["content"];
		$title_tag = $content_record["title_tag"];
		$description_tag = $content_record["description_tag"];
		$keywords_tag = $content_record["keywords_tag"];
		$author_tag = $content_record["author_tag"];
		$css_js = $content_record["css_js"];
		$robots_tag = $content_record["robots_tag"];
		$published_flag = $content_record["published_flag"];
		$main_flag = $content_record["main_flag"];
		
		
		//Получаем права доступа к материалу
		$groups_access_query = $db_link->prepare("SELECT `group_id` FROM `content_access` WHERE `content_id` = ?;");
		$groups_access_query->execute( array($content_id) );
		while($groups_access_record = $groups_access_query->fetch() )
		{
			array_push($groups_access, (int)$groups_access_record["group_id"]);
		}
		
		//Получаем данные родительского узла
		$parent_value = "Корень дерева";
		if($parent > 0)
		{
			$parent_query = $db_link->prepare('SELECT `value` FROM `content` WHERE `id` = ?;');
			$parent_query->execute( array($parent) );
			$parent_record = $parent_query->fetch();
			$parent_value = $parent_record["value"];
		}
	}
	?>
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				<a class="panel_a" onClick="save_action();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				<?php
				if( $content_id > 0 )
				{
					?>
					<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager/content">
						<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
						<div class="panel_a_caption">Создать еще материал</div>
					</a>
					<?php
				}
				?>
				
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/content/content_manager">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/documents.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Менеджер материалов</div>
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
				Мета-данные материала
			</div>
			<div class="panel-body">
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						H1 *
					</label>
					<div class="col-lg-6">
						<input type="text" onKeyUp="on_h1_changed();" id="value_input" value="<?php echo $value; ?>" class="form-control"/>
						
						<script>
						//Обработка ввода H1 - инициализируем алиас на транслите
						function on_h1_changed()
						{
							if( document.getElementById("alias_autotranslit").checked )
							{
								var alias = "";
								alias = iso_9_translit(document.getElementById("value_input").value,  5);//5 - русский текст
								alias = alias.replace(/\s/g, '-');
								alias = alias.toLowerCase();
								alias = alias.replace(/[^\d\sA-Z\-_]/gi, '');//Убираем все символы кроме букв, цифр, тире и нинего подчеркивания
								
								document.getElementById("alias_input").value = alias;
							}
						}
						</script>
						
						
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Алиас-автотранслит
					</label>
					<div class="col-lg-6">
						<input type="checkbox" id="alias_autotranslit" value="alias_autotranslit" class="form-control" checked="checked" />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Алиас *
					</label>
					<div class="col-lg-6">
						<input type="text" id="alias_input" value="<?php echo $alias; ?>" class="form-control"/>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тег Title *
					</label>
					<div class="col-lg-6">
						<input type="text" id="title_tag_input" value="<?php echo $title_tag; ?>" class="form-control"/>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тег Description
					</label>
					<div class="col-lg-6">
						<textarea id="description_tag_input" class="form-control"/><?php echo $description_tag; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тег Keywords
					</label>
					<div class="col-lg-6">
						<textarea id="keywords_tag_input" class="form-control"/><?php echo $keywords_tag; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тег Robots
					</label>
					<div class="col-lg-6">
						<input type="text" id="robots_tag_input" value="<?php echo $robots_tag; ?>" class="form-control"/>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тег Author
					</label>
					<div class="col-lg-6">
						<input type="text" id="author_tag_input" value="<?php echo $author_tag; ?>" class="form-control"/>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Технические настройки
			</div>
			<div class="panel-body">
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						ID материала
					</label>
					<div class="col-lg-6">
						<?php
						if( $content_id == 0 )
						{
							?>
							Новый материал - ID еще не присвоен
							<?php
						}
						else
						{
							echo $content_id;
						}
						?>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Пояснение *
					</label>
					<div class="col-lg-6">
						<textarea id="description_input" class="form-control"/><?php echo $description; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Теги CSS и JavaScript
					</label>
					<div class="col-lg-6">
						<textarea id="css_js_input" class="form-control"/><?php echo $css_js; ?></textarea>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Тип содержимого
					</label>
					<div class="col-lg-6">
						<select id="content_type_select" name="content_type_select" onchange="content_type_changed();" class="form-control">
    	                    <option value="text">Текст</option>
    	                    <option value="php">Подключаемый php-скрипт</option>
    	                </select>
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Установить главным
					</label>
					<div class="col-lg-6">
						<?php
						$attribs = "";
						if( $main_flag == true )
						{
							$attribs = " checked=\"checked\" disabled=\"disabled\" ";
						}
						?>
						<input type="checkbox" class="form-control" id="main_flag_input" <?php echo $attribs; ?> />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Опубликовать
					</label>
					<div class="col-lg-6">
						<?php
						$attribs = "";
						if( $published_flag == true )
						{
							$attribs = " checked=\"checked\" ";
						}
						if( $main_flag == true )
						{
							$attribs .= " disabled=\"disabled\" ";
						}
						?>
						<input type="checkbox" class="form-control" id="published_flag_input" <?php echo $attribs; ?> />
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Права доступа
					</label>
					<div class="col-lg-6">
						<select multiple="multiple" id="groups_selector">
						
						<?php
						//Получаем группы пользователей						
						//Получаем максимальный уровень вложенности групп
						$max_level_group_query = $db_link->prepare("SELECT MAX(`level`) AS `max_level` FROM `groups`;");
						$max_level_group_query->execute();
						$max_level_group_record = $max_level_group_query->fetch();
						$max_level_group = $max_level_group_record["max_level"];
						//Формируем SQL-запрос для получения записей в виде древовидной структуры (для групп)
						$SQL_GROUPS = "SELECT ";
						$SQL_GROUPS_fields = "";
						$SQL_GROUPS_joins = "";
						for($l=1; $l <= $max_level_group; $l++)
						{
							if( $l > 1 )
							{
								$SQL_GROUPS_fields = $SQL_GROUPS_fields.",";
								
								$l_last = $l -1;
								
								$SQL_GROUPS_joins = $SQL_GROUPS_joins." LEFT JOIN `groups` AS `t$l` ON `t$l`.`parent` = `t$l_last`.`id` ";
							}
							
							
							$SQL_GROUPS_fields = $SQL_GROUPS_fields."
							`t$l`.`id` AS `l".$l."_id`,
							`t$l`.`value` AS `l".$l."_value`,
							`t$l`.`level` AS `l".$l."_level`,
							`t$l`.`for_backend` AS `l".$l."_for_backend`";
						}
						//Собираем строку запроса
						$SQL_GROUPS = $SQL_GROUPS.$SQL_GROUPS_fields." FROM `groups` AS `t1` ".$SQL_GROUPS_joins." WHERE `t1`.`parent` =0;";
						
						
						
						
						$groups_query = $db_link->prepare($SQL_GROUPS);
						$groups_query->execute();
						
						$already_shown = array();//Фильтр - для уже показанных групп
						while( $group_record = $groups_query->fetch() )
						{
							$for_backend_group = false;//Флаг - группа для бэкенда. По-умолчанию, перед обработкой ветки - false
							
							//Заходим в ветку
							for($l=1; $l <= $max_level_group; $l++)
							{
								if( $group_record["l".$l."_for_backend"] == 1 )
								{
									$for_backend_group = true;//Начали выводить для бэкенда. Эта группа и все ее вложенные (до конца for) - для бэкенда
								}
								
								if($group_record["l".$l."_id"] == NULL)
								{
									break;//К следующей ветке
								}
								
								//Такой узел уже был показан выше
								if( array_search((int)$group_record["l".$l."_id"], $already_shown) === false )
								{
									array_push($already_shown, (int)$group_record["l".$l."_id"]);
								}
								else
								{
									continue;
								}
								
								
								//Добавляем обозначение вложенности
								for($lev=1; $lev < $group_record["l".$l."_level"]; $lev++)
								{
									$group_record["l".$l."_value"] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$group_record["l".$l."_value"];
								}
								
								
								//Если текущий режим - бэкенд, и в итерации - группа не для бэкнда
								if( $is_frontend == 0 && !$for_backend_group )
								{
									continue;
								}
								?>
								<option value="<?php echo $group_record["l".$l."_id"]; ?>"><?php echo $group_record["l".$l."_value"]; ?></option>
							<?php
							}
						}//for
						?>
						</select>
						
						
						<script>
							//Делаем из селектора виджет с чекбоками
							$('#groups_selector').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
							
							//Инициализируем выбранные значения
							$('#groups_selector').multipleSelect('setSelects', <?php echo json_encode($groups_access); ?>);
						</script>
						
						
					</div>
				</div>
				<div class="hr-line-dashed col-lg-12"></div>
				
				
				
				<div class="form-group">
					<label for="" class="col-lg-6 control-label">
						Родительский узел
					</label>
					<div class="col-lg-6">
						<input type="hidden" id="parent_input" value="<?php echo $parent; ?>" />
						<button onClick="pointContentParent();" class="btn btn-success " type="button"><i class="fa fa-hand-pointer-o"></i> <span class="bold" id="parent_indicator"><?php echo $parent_value." (ID $parent) "; ?></span></button>
					</div>
				</div>
				<!-- Модальное окно "Выбор родительского узла" -->
				<div class="text-center m-b-md">
					<div class="modal fade" id="modalWindow_contentParent" tabindex="-1" role="dialog"  aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="color-line"></div>
								<div class="modal-header">
									<h4 class="modal-title">Выберите родительский узел</h4>
								</div>
								<div class="modal-body">
									<div class="row" id="parent_content_tree">
									</div>
									<script>
									//Функция запроса материалов для выбора родительского
									var s_page = 0;
									function get_content_json_list()
									{
										document.getElementById("parent_content_tree").innerHTML = "<div class=\"text-center\">Пожалуйста, подождите</div> <div class=\"spinner\"> <div class=\"rect1\"></div> <div class=\"rect2\"></div> <div class=\"rect3\"></div> <div class=\"rect4\"></div> <div class=\"rect5\"></div> </div>";
										
										
										jQuery.ajax({
											type: "GET",
											async: true,
											url: "/<?php echo $DP_Config->backend_dir; ?>/content/content/ajax_get_content_json_list.php?code=<?php echo urlencode($DP_Config->secret_succession); ?>&content_id=<?php echo $content_id; ?>&is_frontend=<?php echo $is_frontend; ?>&s_page="+s_page,
											dataType: "text",//Тип возвращаемого значения
											success: function(answer)
											{
												
												answer = JSON.parse(answer);
												console.log(answer);
												if(answer["status"] != true)
												{
													document.getElementById("parent_content_tree").innerHTML = "Ошибка получения списка материалов от сервера";
												}
												else
												{
													//Страницы здесь выводятся в иерархическом режиме - как в менеджере материалов
													var content = answer["content"];
													var max_level = answer["max_level"];
													var count_total_for_pagination = answer["count_total_for_pagination"];
													var count_total = answer["count_total"];
													var list_page_limit = answer["list_page_limit"];
													
													var content_html = "<table cellpadding=\"1\" cellspacing=\"1\" class=\"table table-condensed table-striped\"><thead><tr><th>П/П</th><th></th><th>ID</th><th>H1</th></tr></thead><tbody>";
													
													
													
													//Выставление текущего
													var checked = "";
													if( parseInt(document.getElementById("parent_input").value) == 0 )
													{
														checked = " checked=\"checked\" ";
													}
													
													content_html += "<tr id=\"tr_0\" caption=\"Корень дерева\"><td>0</td><td><input type=\"radio\" name=\"parent_content_radio\" value=\"0\" "+checked+" id=\"parent_radio_0\" /></td><td><label for=\"parent_radio_0\">0</label></td><td><label for=\"parent_radio_0\">Корень дерева</label></td></tr>";
													
													var already_shown = new Array();
													
													var strings_print_count = 1;
													
													for(var i=0; i < content.length; i++)
													{
														for(var l=1; l <= max_level; l++)
														{
															if(content[i]["l"+l+"_id"] == undefined)
															{
																break;//К следующей ветке
															}
															
															//Если это - этот же материал - переход к следующей ветке, т.к. нельзя вложить материал в самого себя и во вложенные материалы
															if(content[i]["l"+l+"_id"] == parseInt(<?php echo $content_id; ?>))
															{
																break;//К следующей ветке
															}
															
															//Такой узел уже был показан выше
															if( already_shown[content[i]["l"+l+"_id"]] == undefined )
															{
																already_shown[content[i]["l"+l+"_id"]] = content[i]["l"+l+"_id"];
															}
															else
															{
																continue;
															}
															
															var value_to_show = content[i]["l"+l+"_value"];
															
															//Добавляем отступ относительно корня дерева
															value_to_show = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+value_to_show;
															
															
															//Добавляем обозначение вложенности
															for(var lev=1; lev < content[i]["l"+l+"_level"]; lev++)
															{
																value_to_show = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"+value_to_show;
															}
															
															//Выставление текущего
															var checked = "";
															if( parseInt(document.getElementById("parent_input").value) == parseInt(content[i]["l"+l+"_id"]) )
															{
																checked = " checked=\"checked\" ";
															}
															
															content_html += "<tr id=\"tr_"+content[i]["l"+l+"_id"]+"\" caption=\""+content[i]["l"+l+"_value"]+"\"><td>"+strings_print_count+"</td><td><input type=\"radio\" name=\"parent_content_radio\" value=\""+content[i]["l"+l+"_id"]+"\" "+checked+" id=\"parent_radio_"+content[i]["l"+l+"_id"]+"\" /></td><td><label for=\"parent_radio_"+content[i]["l"+l+"_id"]+"\">"+content[i]["l"+l+"_id"]+"</label></td><td><label for=\"parent_radio_"+content[i]["l"+l+"_id"]+"\">"+value_to_show+"</label></td></tr>";
															strings_print_count++;
														}
													}
													
													content_html += "</tbody><tfoot><tr><td colspan=\"4\" style=\"text-align:center;\">";
													
													
													//START ВЫВОД ПЕРЕКЛЮЧАТЕЛЕЙ СТРАНИЦ ТАБЛИЦЫ
													//Исходные данные для переключателя страниц:
													var current_page = parseInt(s_page);
													var rows_per_page = list_page_limit;
													var elements_count_rows = count_total_for_pagination;
													
													content_html += '<div class="btn-group">';//HTML переключателя страниц
													
													
													//КНОПКА "ВЛЕВО"
													var to_left_disabled = "";
													if( current_page == 0 )
													{
														to_left_disabled = "disabled";
													}
													
													content_html += '<a class="btn btn-default '+to_left_disabled+'" onclick="go_to_page(0);" href="javascript:void(0);">Первая</a>';
													content_html += '<a class="btn btn-default '+to_left_disabled+'" onclick="go_to_page('+ parseInt(current_page-1) +');" href="javascript:void(0);"><i class="fa fa-chevron-left"></i></a>';
													
													
													//Определяем количество страниц
													var pages_count = parseInt(elements_count_rows/rows_per_page);
													if( parseInt(elements_count_rows%rows_per_page) > 0 )
													{
														pages_count++;
													}
													
													
													//Выводим кнопки для конкретных страниц (с номерами)
													/*
													Количество страниц, теоретически, может быть очень большим. Чтобы не гонять цикл, пропускаем страницы до тех, которые нужно выводить
													*/
													var i_start = current_page - 2;
													if(i_start < 0)
													{
														i_start = 0;
													}
													
													for( var i = i_start; i < pages_count; i++)
													{
														//Две кнопки до текущей - показываем
														if( parseInt(current_page - i) > 2  )
														{
															continue;
														}
														
														
														//Две кнопки после текущей - показываем
														if( parseInt(i - current_page) > 2  )
														{
															break;
														}
														
														
														
														var active = "";
														if( parseInt(i) == parseInt(current_page) )
														{
															active = "active";
														}
														
														content_html += '<a href="javascript:void(0);" class="btn btn-default '+active+'" onclick="go_to_page('+parseInt(i)+');">'+ parseInt(i+1) +'</a>';
														
													}
													
													//КНОПКА "ВПРАВО"
													var to_right_disabled = "";
													if( parseInt(current_page+1) == parseInt(pages_count) )
													{
														to_right_disabled = "disabled";
													}
													
													content_html += '<a href="javascript:void(0);" class="btn btn-default '+to_right_disabled+'" onclick="go_to_page('+parseInt(current_page+1)+');"><i class="fa fa-chevron-right"></i></a>'
													content_html += '<a href="javascript:void(0);" class="btn btn-default '+to_right_disabled+'" onclick="go_to_page('+parseInt(pages_count-1)+');">Последняя</a>';
													content_html += '</div><br>';
													
													content_html += '<div style="text-align:left;color:#000;">Страницы здесь - для выбора корневого узла редактируемой (или создаваемой) страницы, выводятся в иерархическом виде. Т.о. количество выводимых здесь на одну страницу элементов может превышать лимит, который в настройках CMS для списка составляет - <b>'+rows_per_page+'</b>. Это обусловлено тем, что корневые элементы могут повторяться на смежных страницах, если их вложенные элементы не умещаются на одной странице. Кроме этого, если Вы редактируете здесь уже существующий материал, то он сам, а также все вложенные в него элементы, здесь не показываются, поэтому на некоторых страницах количество элементов может быть даже меньше <b>'+rows_per_page+'</b>.<br>Элементов: <b>'+count_total+'</b><br>Страниц: <b>'+pages_count+'</b></div>';
													
													content_html += '</td></tr></tfoot></table>';
													
													
													document.getElementById("parent_content_tree").innerHTML = content_html;
												}
											}
										});
									}
									</script>
								</div>
								<div class="modal-footer">
									<button onclick="applyContentParent();" class="btn btn-success " type="button"><i class="fa fa-check"></i> <span class="bold">Применить</span></button>
								
									<button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<script>
				//-----------------------------------------------------
				//Кнопка "Указать родительский узел"
				function pointContentParent()
				{
					$('#modalWindow_contentParent').modal();//Открыть окно
					
					get_content_json_list();//Запросить материалы для выбора родительского
				}
				//-----------------------------------------------------
				//Кнопка "Применить" в окне выбора товара
				function applyContentParent()
				{
					var selected_parent = jQuery('input[name="parent_content_radio\"]:checked').val();
					
					if( selected_parent == undefined )
					{
						alert("Не выбран родительский материал");
						return;
					}
					
					
					//Устанавливаем индикацию и записываем в input
					document.getElementById("parent_input").value = selected_parent;
					document.getElementById("parent_indicator").innerHTML = document.getElementById("tr_"+selected_parent).getAttribute("caption")+" (ID "+selected_parent+")";
					
					
					//Скрыть окно выбора сопутствующих товаров товаров
					$('#modalWindow_contentParent').modal('hide');
				}
				//-----------------------------------------------------
				//Переход на другую страницу с выбором родительского материала
				function go_to_page(need_page)
				{
					s_page = need_page;
					
					get_content_json_list();//Запросить материалы для выбора родительского
				}
				//-----------------------------------------------------
				</script>
				
				
				
				
				
				
				
				
				
				
				
				
			</div>
		</div>
	</div>
	
	
	
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Содержимое материала
			</div>
			<div class="panel-body">
				<div id="content_value_area"></div>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<!-- Для загрузки файлов через TinyMCE -->
	<iframe id="file_form_target" name="file_form_target" style="display:none"></iframe>
	<form id="file_form" action="/<?php echo $DP_Config->backend_dir; ?>/lib/tinymce/postAcceptor.php" target="file_form_target" method="post" enctype="multipart/form-data" style="width:0px;height:0;overflow:hidden">
		<input id="image_input" name="image" type="file" onchange="onFileSelected();">
	</form>
    <script>
	//Обработка выбора файла текстовом редакторе
	function onFileSelected()
	{
		//Создаем данные для формы
		var formData = new FormData();
		formData.append('image', $('input[type=file]')[0].files[0]); 
		
		//Передаем форму с файлом на сервер
		$.ajax({
			url: '/<?php echo $DP_Config->backend_dir; ?>/lib/tinymce/postAcceptor.php',
			data: formData,
			dataType:"json",
			type: "POST",
			contentType: false,
			processData: false,
			success : function (answer){
				console.log("Ответ сервера: "+answer);
				
				if(answer.status == true)
				{
					//Указываем имя файл в окне его выбора от TinyMCE и закрываем окно
					top.$('.mce-btn.mce-open').parent().find('.mce-textbox').val(answer.url).closest('.mce-window').find('.mce-primary').click();
					
					//Очищаем input
					document.getElementById("image_input").value = '';
				}
				else
				{
					alert("Ошибка: "+answer.message)
					
					//Очищаем input
					document.getElementById("image_input").value = '';
				}
			}
		})
	}
	</script>
	
	

    
   

    <script>
    //-----------------------------------------------------
    //Вспомогательные паременные для запоминания содержимого при переключении типа 
    var text_content = "";
    var php_content = "";
    var already_loaded = false;//Флаг - Страница полностью загружена. При загрузке страницы еще некоторые объекты не доступны и к ним нельзя обращаться.
    
    //Переключение типа содержимого
    function content_type_changed()
    {
        var content_type = document.getElementById("content_type_select").value;
        var content_value_area = document.getElementById("content_value_area");
        
        if(content_type == "php")
        {
            if(already_loaded)
            {
                text_content = tinymce.activeEditor.getContent();//Сначала запоминаем текстовое содержимое, чтобы не потерять
            }
			
			
			content_value_area.innerHTML = "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Путь к файлу</label><div class=\"col-lg-6\"><input type=\"text\" name=\"php_file_path\" id=\"php_file_path\" value=\"\" class=\"form-control\" /></div></div>";
			
			
            
            if(already_loaded)
            {
                document.getElementById("php_file_path").value = php_content;//Восстанавливаем запомненное содержимое (если оно было)
            }
        }
        else if(content_type == "text")
        {
            if(already_loaded)
            {
                php_content = document.getElementById("php_file_path").value;//Сначала запоминаем php-содержимое, чтобы не потерять
            }
            
            content_value_area.innerHTML = "<textarea style=\"min-height:400px\" class=\"tinymce_editor\" id=\"tinymce_editor\"></textarea>";
            tinymce.init({
                selector: "textarea.tinymce_editor",
                plugins: [
                    "advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table contextmenu paste textcolor"
                ],
				extended_valid_elements:"script[*]",
                toolbar: [ 
                        "newdocument | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect | formatselect | fontselect | fontsizeselect | ", 
                        "cut copy paste | bullist numlist | outdent indent | blockquote | undo redo | removeformat subscript superscript | link image | forecolor backcolor",
                ],
				file_browser_callback: function(field_name, url, type, win) {
					if(type=='image') $('#file_form input').click();
				}
            });
            
            if(already_loaded)
            {
                document.getElementById("tinymce_editor").value = tinymce.activeEditor.setContent(text_content);//Восстанавливаем запомненное содержимое (если оно было)
            }
        }
    }//~function content_type_changed()
    

    
    
    
    //-------------- ДЕЙСВТВИЯ ПОСЛЕ ЗАГРУЗКИ СТРАНИЦЫ -------------->
    //Тип содержимого при загрузке страницы:
    var current_content_type = "<?php echo $content_type; ?>";
    
    //Выставляем текущий вариант типа содержимого:
    content_type_select = document.getElementById("content_type_select");//Селектор типов содержимого
    for(var j=0; j<content_type_select.options.length; j++)
    {
        if(content_type_select.options[j].value == current_content_type)
        {
            content_type_select.options[j].selected = true;
            break;
        }
    }
    content_type_changed();//Обработка выбора типа содержимого
    
    
    
    //Заполняем текущее содержимое:
	<?php
	if($content_type == "text")
	{
		$content = addcslashes(str_replace(array("\n","\r"), '', $content), "'");
		$content = str_replace("/", "\/", $content);
	}
	else if($content_type == "php")
	{
		$content = $content;
	}
	?>
	var current_content = '<?php echo $content; ?>';
    if(current_content_type == "text")
    {
        //console.log(current_content);
        document.getElementById("tinymce_editor").value = current_content;
    }
    else if(current_content_type == "php")
    {
        document.getElementById("php_file_path").value = current_content;
    }
    
    already_loaded = true;//Страница загружена
    </script>
	
	
	
	
	<form method="POST" name="save_form" style="display:none;">
		<input type="hidden" name="action" value="save_action" />
		<input type="hidden" id="content_object" name="content_object" value="" />
	</form>
	<script>
	var alias_unique = false;//Флаг - поле алиас уникально в пределах одного уровня одной ветви
	//Функция сохранения
	function save_action()
	{
		//Собираем объект
		var content_object = new Object;
		
		content_object.content_id = <?php echo $content_id; ?>;
		content_object.is_frontend = <?php echo $is_frontend; ?>;
		content_object.value = document.getElementById("value_input").value;
		content_object.alias = document.getElementById("alias_input").value;
		content_object.title_tag = document.getElementById("title_tag_input").value;
		content_object.description_tag = document.getElementById("description_tag_input").value;
		content_object.keywords_tag = document.getElementById("keywords_tag_input").value;
		content_object.robots_tag = document.getElementById("robots_tag_input").value;
		content_object.author_tag = document.getElementById("author_tag_input").value;
		content_object.description = document.getElementById("description_input").value;
		content_object.css_js = document.getElementById("css_js_input").value;
		content_object.content_type = document.getElementById("content_type_select").value;
		content_object.check_hash = '<?php echo md5($content_id.$is_frontend.$DP_Config->secret_succession); ?>';
		
		if(document.getElementById("main_flag_input").checked)
		{
			content_object.main_flag = 1;
		}
		else
		{
			content_object.main_flag = 0;
		}
		
		if(document.getElementById("published_flag_input").checked)
		{
			content_object.published_flag = 1;
		}
		else
		{
			content_object.published_flag = 0;
		}
		
		
		
		var groups_access = [].concat( $("#groups_selector").multipleSelect('getSelects') );
		content_object.groups_access = JSON.stringify(groups_access);
		
		content_object.parent = parseInt(document.getElementById("parent_input").value);
		
		
		content_object.content = "";
		if(content_object.content_type=="text")
		{
			content_object.content = tinymce.activeEditor.getContent();
		}
		else
		{
			content_object.content = document.getElementById("php_file_path").value;
		}
		
		
		
		//console.log(content_object);
		
		
		//ПЕРЕД ОТПРАВКОЙ ФОРМЫ ПРОВЕРЯЕМ ВСЕ ПОЛЯ
		//H1 (value)
		if(content_object.value == "")
		{
			alert("Заполние H1");
			return;
		}
		//Алиас
		if(content_object.alias == "")
		{
			alert("Заполние Алиас");
			return;
		}
		//Алиас - на наличие недопустимых знаков (допустимы a-z_-)
		var regex = new RegExp("[a-z0-9\-_]{0,}");
		var match = regex.exec(String(content_object.alias));
		if(match == null)
		{
			alert("В поле Алиас введено некорректное значение. Используйте цифры, знак тире, знак нижнего подчеркивания и латинские буквы в нижнем регистре");
			return false;
		}
		else
		{
			var match_value = String(match[0]);//Подходящая подстрока
			if(match_value != content_object.alias)
			{
				alert("Поле Алиас содержит лишние знаки. Используйте цифры, знак тире, знак нижнего подчеркивания и латинские буквы в нижнем регистре");
				return false;
			}
		}
		//Алиас - на уникальность в пределах одного уровня одной ветки
		alias_unique = false;
		jQuery.ajax({
			type: "GET",
			async: false, //Запрос синхронный
			url: "/<?php echo $DP_Config->backend_dir; ?>/content/content/ajax_check_alias.php?code=<?php echo urlencode($DP_Config->secret_succession); ?>&content_id=<?php echo $content_id; ?>&is_frontend=<?php echo $is_frontend; ?>&alias="+content_object.alias+"&parent="+content_object.parent,
			dataType: "json",//Тип возвращаемого значения
			success: function(answer)
			{
				if(answer.status == true)
				{
					if(answer.message == "ok")
					{
						alias_unique = true;
					}
					else
					{
						alias_unique = false;
					}
				}
				else
				{
					alert("Ошибка проверки уникальности Алиас");
				}
			}
		});
    	if(alias_unique == false)
    	{
    		alert("В пределах одного уровня одной ветки уже есть материал с таким же полем Алиас. Алиас должен быть уникальным");
    		return false;
    	}
		
		
		
		
		
		//Title
		if(content_object.title_tag == "")
		{
			alert("Заполние Тег Title");
			return;
		}
		//Пояснение
		if(content_object.description == "")
		{
			alert("Заполние Пояснение");
			return;
		}
		
		
		//Обработка главного
		if(content_object.main_flag == 1)
		{
			if(content_object.parent > 0)
			{
				alert("Главным может быть только материал верхнего уровня");
				return;
			}
			
			if(content_object.published_flag == 0)
			{
				alert("Главный материал не может быть снят с публикации");
				return;
			}
		}
		
		
		//console.log(content_object);
		//alert("ok");
		//return;
		
		//Заполняем форму и отправляем
		document.getElementById("content_object").value = JSON.stringify(content_object);
		document.forms["save_form"].submit();
	}
	</script>
	
	
	
	<?php
}
?>