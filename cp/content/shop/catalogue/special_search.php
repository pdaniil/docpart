<?php
/**
Страничный скрипт для управления одним специальным поиском (создание/редактирование)
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
if( isset( $_POST["action"] ) )
{
	try
	{
		//Старт транзакции
		if( ! $db_link->beginTransaction()  )
		{
			throw new Exception("Не удалось стартовать транзакцию");
		}
		
		//Получаем данные
		$search_caption = $_POST["search_caption"];
		$search_title = $_POST["search_title"];
		$search_description = $_POST["search_description"];
		$search_keywords = $_POST["search_keywords"];
		$search_robots = $_POST["search_robots"];
		$search_alias = $_POST["search_alias"];
		$search_order = (int)$_POST["search_order"];
		$steps = json_decode($_POST["tree_json"], true);
		$deleted_steps = json_decode($_POST["deleted_steps"], true);
		$search_active = $_POST["search_active"];
		
		if( $_POST["search_id"] == 0 )//Создание
		{
			//Создаем учетную запись специального поиска
			if( ! $db_link->prepare("INSERT INTO `shop_special_searches` (`caption`, `alias`, `order`, `active`, `title`, `description`, `keywords`, `robots`) VALUES (?,?,?,?,?,?,?,?);")->execute( array($search_caption, $search_alias, $search_order, $search_active, $search_title, $search_description, $search_keywords, $search_robots) ) )
			{				
				throw new Exception("SQL-ошибка создания учетной записи специального поиска. Поиск не создан");
			}
			
			$search_id = $db_link->lastInsertId();//ID добавленного поиска
				
			//СОХРАНЕНИЕ ИЗОБРАЖЕНИЯ
			$files_upload_dir = $_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/";
			$FILE_POST = $_FILES["file_local"];
			if( $FILE_POST["size"] > 0 )
			{
				//Получаем файл:
				$fileName = $FILE_POST["name"];
				
				//Получаем расширение файла
				$file_extension = explode(".", $fileName);
				$file_extension = $file_extension[count($file_extension)-1];
				//Имя файла будет вида special_search_<id>.$file_extension
				$saved_file_name = "special_search_".$search_id.".".$file_extension;
				
				$uploadfile = $files_upload_dir.$saved_file_name;
				
				if (copy($FILE_POST['tmp_name'], $uploadfile))
				{
					if( $db_link->prepare("UPDATE `shop_special_searches` SET `img` = ? WHERE `id` = ?;")->execute( array($saved_file_name, $search_id) ) != true)
					{
						throw new Exception("Ошибка 1 обновления изображений");
					}
				} 
				else 
				{
					throw new Exception("Ошибка 2 обновления изображений");
				}
			}
			
			
			
			//СОХРАНЯЕМ ШАГИ ПОИСКА
			for($i=0; $i < count($steps); $i++)
			{
				$order = $i+1;
				$caption = $steps[$i]["value"];
				$alias = $steps[$i]["alias"];
				$type = $steps[$i]["type"];
				$objects = json_encode($steps[$i]["objects"]);
				
				if( ! $db_link->prepare("INSERT INTO `shop_special_searches_steps` (`search_id`, `caption`, `alias`, `type`, `objects`, `order`) VALUES (?, ?, ?, ?, ?, ?);")->execute( array($search_id, $caption, $alias, $type, $objects, $order) ) )
				{
					throw new Exception("Ошибка сохранения шагов");
				}
				
				$steps[$i]["id"] = $db_link->lastInsertId();
			}
			
			
			//Обработка метаданных для уровней вложенности шагов
			for($i=0; $i < count($steps); $i++)
			{
				//Уровни вложенности для данного шага
				$levels = $steps[$i]["levels"];
				
				//Добавляем настройки метаданных для каждого уровня вложенности данного шага
				for($lev = 0; $lev < count($levels); $lev++)
				{
					if( ! $db_link->prepare("INSERT INTO `shop_special_searches_metadata` (`value`, `search_id`, `step_id`, `step_level`, `h1`, `title`, `description`, `keywords`, `robots`) VALUES (?,?,?,?,?,?,?,?,?);")->execute( array($levels[$lev]["value"], $search_id, $steps[$i]["id"], $lev+1, $levels[$lev]["h1"], $levels[$lev]["title"], $levels[$lev]["description"], $levels[$lev]["keywords"], $levels[$lev]["robots"] ) ) )
					{
						throw new Exception("Ошибка добавления записи шаблона метаданных");
					}
				}
			}
		}
		else//РЕДАКТИРОВАНИЕ
		{
			$search_id = $_POST["search_id"];
			
			//УЧЕТНАЯ ЗАПИСЬ
			if( ! $db_link->prepare("UPDATE `shop_special_searches` SET `caption` = ?, `alias` = ?, `order`=?, `active` = ?, `title`=?, `description`=?, `keywords`=?, `robots`=? WHERE `id` = ?;")->execute( array($search_caption, $search_alias, $search_order, $search_active, $search_title, $search_description, $search_keywords, $search_robots, $search_id) ) )
			{
				throw new Exception("Ошибка обновления учетной записи спецпоиска");
			}
			

			//СОХРАНЕНИЕ ИЗОБРАЖЕНИЯ
			$files_upload_dir = $_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/";
			$FILE_POST = $_FILES["file_local"];
			if( $FILE_POST["size"] > 0 )
			{
				//Получаем файл:
				$fileName = $FILE_POST["name"];
				
				//Получаем расширение файла
				$file_extension = explode(".", $fileName);
				$file_extension = $file_extension[count($file_extension)-1];
				//Имя файла будет вида special_search_<id>.$file_extension
				$saved_file_name = "special_search_".$search_id.".".$file_extension;
				
				$uploadfile = $files_upload_dir.$saved_file_name;
				
				if (copy($FILE_POST['tmp_name'], $uploadfile))
				{
					if( $db_link->prepare("UPDATE `shop_special_searches` SET `img` = ? WHERE `id` = ?;")->execute( array($saved_file_name, $search_id) ) != true)
					{			
						throw new Exception("Ошибка 1 обработки изображений");
					}
				} 
				else 
				{
					throw new Exception("Ошибка 2 обработки изображений");
				}
			}
			
			
			//ШАГИ ПОИСКА
			//Добавляем новые шаги
			for($i=0; $i < count($steps); $i++)
			{
				$order = $i+1;
				$caption = $steps[$i]["value"];
				$alias = $steps[$i]["alias"];
				$type = $steps[$i]["type"];
				$objects = json_encode($steps[$i]["objects"]);
				
				
				if($steps[$i]["is_new"] == true)
				{
					if( ! $db_link->prepare("INSERT INTO `shop_special_searches_steps` (`search_id`, `caption`, `alias`, `type`, `objects`, `order`) VALUES (?, ?, ?, ?, ?, ?);")->execute( array($search_id, $caption, $alias, $type, $objects, $order) ) )
					{
						throw new Exception("Ошибка добавления нового шага");
					}
					
					
					$steps[$i]["id"] = $db_link->lastInsertId();
				}
			}
			//Обновляем существующие шаги
			for($i=0; $i < count($steps); $i++)
			{
				$order = $i+1;
				$caption = $steps[$i]["value"];
				$alias = $steps[$i]["alias"];
				$type = $steps[$i]["type"];
				$objects = json_encode($steps[$i]["objects"]);
				
				
				if($steps[$i]["is_new"] == false)
				{
					if( ! $db_link->prepare("UPDATE `shop_special_searches_steps` SET `caption` = ?, `alias` = ?, `type`=?, `objects` = ?, `order` = ? WHERE `id` = ?;")->execute( array($caption, $alias, $type, $objects, $order, $steps[$i]["id"]) ) )
					{
						throw new Exception("Ошибка обновления существующих шагов спецпоиска");
					}
				}
			}
			
			//Удаляем удаленные шаги
			if( count($deleted_steps) > 0 )
			{
				$binding_values = array();
				$STEPS_DELETE = "DELETE FROM `shop_special_searches_steps` WHERE `id` IN (";
				for($i=0; $i < count($deleted_steps); $i++)
				{
					if($i > 0)
					{
						$STEPS_DELETE = $STEPS_DELETE.",";
					}
					$STEPS_DELETE = $STEPS_DELETE."?";
					
					array_push($binding_values, $deleted_steps[$i]);
				}
				$STEPS_DELETE = $STEPS_DELETE. ");";
				
				if( ! $db_link->prepare($STEPS_DELETE)->execute($binding_values) )
				{
					throw new Exception("Ошибка удаления шагов спецпоиска");
				}
			}
			
			
			//Обработка метаданных для уровней вложенности шагов
			//Сначала удаляем старые записи для всего спецпоиска
			if( ! $db_link->prepare("DELETE FROM `shop_special_searches_metadata` WHERE `search_id` = ?;")->execute( array($search_id) ) )
			{
				throw new Exception("Ошибка удаления старых записей с шаблонами метаданных");
			}
			//Теперь добавляем записи
			for($i=0; $i < count($steps); $i++)
			{
				//Уровни вложенности для данного шага
				$levels = $steps[$i]["levels"];
				
				//Добавляем настройки метаданных для каждого уровня вложенности данного шага
				for($lev = 0; $lev < count($levels); $lev++)
				{
					if( ! $db_link->prepare("INSERT INTO `shop_special_searches_metadata` (`value`, `search_id`, `step_id`, `step_level`, `h1`, `title`, `description`, `keywords`, `robots`) VALUES (?,?,?,?,?,?,?,?,?);")->execute( array($levels[$lev]["value"], $search_id, $steps[$i]["id"], $lev+1, $levels[$lev]["h1"], $levels[$lev]["title"], $levels[$lev]["description"], $levels[$lev]["keywords"], $levels[$lev]["robots"] ) ) )
					{
						throw new Exception("Ошибка добавления записи шаблона метаданных");
					}
				}
			}
		}
	}
	catch (Exception $e)
	{
		//Откатываем все изменения
		$db_link->rollBack();
		
		//Если был переход на создание, то при ошибке, special_search_id не будет в GET-параметрах
		$special_search_id_str = "";
		if( (int)$_POST["search_id"] > 0 )
		{
			//Был переход на редактирование, значит special_search_id должен быть в GET-параметрах
			$special_search_id_str = "&special_search_id=".(int)$_POST["search_id"];
		}
		
		?>
		<script>
			location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/specialnye-poiski/specialnyj-poisk?error_message=<?php echo urlencode($e->getMessage()).$special_search_id_str; ?>";
		</script>
		<?php
		exit;
	}

	//Дошли до сюда, значит выполнено ОК
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	?>
	<script>
		location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/specialnye-poiski/specialnyj-poisk?special_search_id=<?php echo $search_id; ?>&success_message=<?php echo urlencode("Специальный поиск обновлен успешно!"); ?>";
	</script>
	<?php
	exit;
	
}
else//Действий нет - выводим страницу
{
	//Получаем список древовидных списков (Сам список - линейный, т.е. просто перечисление древовидных списков)
	$tree_lists_array = array();
	$tree_lists_array_query = $db_link->prepare("SELECT * FROM `shop_tree_lists` ORDER BY `id`;");
	$tree_lists_array_query->execute();
	while($tree_lists_array_record = $tree_lists_array_query->fetch() )
	{
		array_push($tree_lists_array, array("id"=>$tree_lists_array_record["id"], "value"=>$tree_lists_array_record["caption"]) );
	}
	
	
	//Получаем дерево категорий товаров $catalogue_tree_dump_JSON
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
	
	
	//Исходные данные (по умолчанию - для создания нового поиска):
	$special_search_id = 0;
	$action = "create";
	$steps = "[]";
	$search_caption = "";
	$search_alias = "";
	$search_order = "";
	$search_active = true;
	$search_title = "";
	$search_description = "";
	$search_keywords = "";
	$search_robots = "";
	
	//Идет редактирование
	if( isset($_GET["special_search_id"]) )
	{
		$special_search_id = $_GET["special_search_id"];
		$action = "edit";
		
		//Общие настройки поиска
		$search_query = $db_link->prepare("SELECT * FROM `shop_special_searches` WHERE `id` = ?;");
		$search_query->execute( array($special_search_id) );
		$search_record = $search_query->fetch();
		$search_caption = $search_record["caption"];
		$search_alias = $search_record["alias"];
		$search_order = $search_record["order"];
		$search_img = $search_record["img"];
		$search_active = (bool)$search_record["active"];
		$search_title = $search_record["title"];
		$search_description = $search_record["description"];
		$search_keywords = $search_record["keywords"];
		$search_robots = $search_record["robots"];
		
		
		//Шаги
		$steps = array();
		$search_steps_query = $db_link->prepare("SELECT * FROM `shop_special_searches_steps` WHERE `search_id` = ? ORDER BY `order`;");
		$search_steps_query->execute( array($special_search_id) );
		while( $step = $search_steps_query->fetch() )
		{
			//Получаем шаблоны метаданных для уровней вложенности данного шага
			$levels = array();
			$levels_query = $db_link->prepare("SELECT * FROM `shop_special_searches_metadata` WHERE `step_id` = ?;");
			$levels_query->execute( array($step["id"]) );
			while( $level = $levels_query->fetch() )
			{
				$levels[] = array("value"=>$level["value"], "h1"=>$level["h1"], "title"=>$level["title"], "description"=>$level["description"], "keywords"=>$level["keywords"], "robots"=>$level["robots"]);
			}
			
			
			array_push($steps, array("id"=>$step["id"], "value"=>$step["caption"], "alias"=>$step["alias"], "type"=>$step["type"], "is_new"=>false,"objects"=>json_decode($step["objects"], true), "levels"=>$levels ) );
		}
		$steps = json_encode($steps);
		
		
		//Картинка
		if( $search_img != "" )
		{
			$search_img = "/content/files/images/catalogue_images/".$search_img;
		}
	}
	
	?>
	
	
	<?php
        require_once("content/control/actions_alert.php");//Вывод сообщений о результатах действий
    ?>
	
	
	
	
	
	<!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none" enctype="multipart/form-data">
        <input name="search_id" id="search_id" value="<?php echo $special_search_id; ?>" />
		
		<input name="action" id="action" type="text" value="ok" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
        
		<input name="search_caption" id="search_caption" />
		<input name="search_alias" id="search_alias" />
		<input name="search_order" id="search_order" />
		
		<input name="search_active" id="search_active" />
		
		<input name="deleted_steps" id="deleted_steps" value="" />
		
		<input type="file" name="file_local" id="file_local" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="onFileChanged();" />
		
		<input name="search_title" id="search_title" />
		<input name="search_description" id="search_description" />
		<input name="search_keywords" id="search_keywords" />
		<input name="search_robots" id="search_robots" />
		
    </form>
    <!--Форма для отправки-->
	
	
	
	
	
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Действия
			</div>
			<div class="panel-body">
				
				
				<a class="panel_a" href="javascript:void(0);" onclick="add_new_item();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/add.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Добавить шаг</div>
				</a>
				
				
				<a class="panel_a" href="javascript:void(0);" onclick="delete_selected_item();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/delete.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Удалить шаг</div>
				</a>
				
				<a class="panel_a" href="javascript:void(0);" onclick="unselect_tree();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/selection_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Снять выделение</div>
				</a>
				
				
				
				<a class="panel_a" href="javascript:void(0);" onclick="save_action();">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Сохранить</div>
				</a>
				
				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/catalogue/specialnye-poiski">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/special_search.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Специальные поиски</div>
				</a>

				<a class="panel_a" href="/<?php echo $DP_Config->backend_dir?>">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Выход</div>
				</a>
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Общие настройки специального поиска
			</div>
			<div class="panel-body">
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Название (используется в h1)
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_caption_input" value="<?php echo $search_caption; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Алиас
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_alias_input" value="<?php echo $search_alias; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Порядок отображения
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_order_input" value="<?php echo $search_order; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Пиктограмма
						</label>
						<div class="col-lg-6 text-center">
							<button class="btn btn-success" type="button" onclick="document.getElementById('file_local').click();">
								<i class="fa fa-file"></i>
								<span class="bold">Выбрать файл</span>
							</button>
							<br><br>
							<img id="img_for_show" onerror = "this.src = '<?php echo "/content/files/images/no_image.png"; ?>'" src="<?php echo $search_img; ?>?chache=<?php echo time(); ?>" style="max-width:96px; max-height:96px" />

							<script>
							//Функция выбора файла
							function onFileChanged()
							{
								var input_file = document.getElementById("file_local");//input для файла изображения
								var file = input_file.files[0];//Получаем выбранный файл
								
								if(file == undefined)
								{
									return;
								}
								
								//Запрещаем загружать файлы больше 50 Кб
								if(file.size > 51200)
								{
									input_file.value = null;
									alert("Размер файла превышает 50 Кб");
									return;
								}
								
								//Проверяем тип файла
								if(file.type != "image/jpeg" && file.type != "image/jpg" && file.type != "image/png" && file.type != "image/gif")
								{
									input_file.value = null;
									alert("Файл должен быть изображением");
									return;
								}
								
								
								//Отображаем файл
								document.getElementById("img_for_show").setAttribute("src", URL.createObjectURL(file));
								
								
								//Сам файл для формы остается в инпуте
							}
							</script>
							
						</div>
					</div>
				</div>
				
				
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Поиск включен
						</label>
						<div class="col-lg-6">
							<?php
							$checked = "";
							if( $search_active )
							{
								$checked = " checked=\"checked\" ";
							}
							?>
						
							<input type="checkbox"  id="search_active_checkbox" value="search_active_checkbox" class="form-control" <?php echo $checked; ?> />
						</div>
					</div>
				</div>
				
				
			</div>
		</div>
	</div>
	
	
	
	
	
	
	<div class="col-lg-6">
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
					<a class="showhide"><i class="fa fa-chevron-up"></i></a>
				</div>
				Метаданные корневого раздела спецпоиска
			</div>
			<div class="panel-body">
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег title
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_title_input" value="<?php echo $search_title; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег мета-description
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_description_input" value="<?php echo $search_description; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег мета-keywords
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_keywords_input" value="<?php echo $search_keywords; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег мета-robots
						</label>
						<div class="col-lg-6">
							<input type="text"  id="search_robots_input" value="<?php echo $search_robots; ?>" class="form-control" />
						</div>
					</div>
				</div>
				
				
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12"></div>
	
	
	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Шаги поиска
			</div>
			<div class="panel-body">
				<div id="container_A" style="height:470px;">
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-6" id="step_info_div_col">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Параметры выбранного шага
			</div>
			<div class="panel-body">
				<div id="step_info_div">
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12"></div>
	
	
	<div class="col-lg-6" id="step_levels_div">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Уровни вложенности шага
			</div>
			<div class="panel-body">
				<div id="container_B" style="height:470px;">
				</div>
			</div>
			<div class="panel-footer">
				
				
				
				<div class="row">
					<div class="col-md-12">
						
						<a class="btn btn-success" href="javascript:void(0);" style="border:0;" onclick="add_new_item_B();" title="Добавить уровень вложенности"><i class="fas fa-plus"></i></a> 
						
						<a class="btn btn-danger" href="javascript:void(0);" style="border:0;" onclick="delete_selected_item_B();" title="Удалить уровень вложенности"><i class="fas fa-minus"></i></a>
						
						<a class="btn btn-primary" href="javascript:void(0);" style="border:0;" onclick="unselect_tree_B();" title="Снять выделение"><i class="fas fa-square"></i></a>
						
					</div>
				</div>
				
				
			</div>
		</div>
	</div>
	
	<div class="col-lg-6" id="step_levels_metatemplates_div">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Шаблоны метаданных выбранного уровня вложенности
			</div>
			<div class="panel-body">

				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег h1
						</label>
						<div class="col-lg-6">
							<textarea id="level_h1" class="form-control" onkeyup="on_metadata_edit();"></textarea>
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег title
						</label>
						<div class="col-lg-6">
							<textarea id="level_title" class="form-control" onkeyup="on_metadata_edit();"></textarea>
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег meta-description
						</label>
						<div class="col-lg-6">
							<textarea id="level_description" class="form-control" onkeyup="on_metadata_edit();"></textarea>
						</div>
					</div>
				</div>
				
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег meta-keywords
						</label>
						<div class="col-lg-6">
							<textarea id="level_keywords" class="form-control" onkeyup="on_metadata_edit();"></textarea>
						</div>
					</div>
				</div>
				
				<div class="hr-line-dashed col-lg-12"></div>
				
				<div class="col-lg-12">
					<div class="form-group">
						<label for="" class="col-lg-6 control-label">
							Тег meta-robots
						</label>
						<div class="col-lg-6">
							<textarea id="level_robots" class="form-control" onkeyup="on_metadata_edit();"></textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	<div class="col-lg-12">
		
		<div class="hpanel collapsed">
			<div class="panel-heading hbuilt">
				<div class="panel-tools">
                    <a class="showhide"><i class="fa fa-chevron-up"></i></a>
                </div>
				Инструкция работе со спецпоисками
			</div>
			<div class="panel-body">
				<h3>Внимательно прочтите данную инструкцию, чтобы понять суть функции специальных поисков!</h3>
				
				<p>Функция специальных поисков позволяет создавать дополнительные модули поиска товаров во встроенном каталоге.</p>
				<p>К примеру, с помощью специальных поисков можно создать такие функции, как «Подбор шин по автомобилю», «Подбор дисков по автомобилю», «Подбор автозапчастей по автомобилю» и тому подобное.</p>
				<p>Перед работой со специальными поисками, рекомендует сначала изучить базовые функции встроенного каталога товаров – создание категорий, работу со свойствами, создание товаров и т.д. Доступна документация и видео-уроки на сайте платформы Docpart.</p>

				<h3>Базовые параметры спецпоиска (блоки "Общие настройки специального поиска" и "Метаданные корневого раздела спецпоиска")</h3>
				<p>Название – это название будет отображаться на главной странице сайта и его увидит покупатель. Оно также будет отображаться в теге h1 корневого раздела спецпоиска. Это может быть, к примеру, «Подбор шин по автомобилю».</p>
				<p>Алиас – это важный параметр, который является частью URL (адреса страницы) и который обозначает подраздел сайта, относящийся к данному спецпоиску. Т.е. это корневой раздел специального поиска. Его значение может быть, к примеру, shiny_po_avtomobilu или podbor_diskov_po_avto.</p>
				<p>Порядок отображения – целое число, которое обозначает, в какой последовательности будет отображаться данный специальный поиск среди остальных специальных поисков. Чем меньше число, тем этот специальный поиск будет ближе к началу списка.</p>
				<p>Также для специального поиска можно выбрать пиктограмму, которая будет отображаться рядом с его ссылкой.</p>
				
				<p>В блоке "Метаданные корневого раздела спецпоиска" можно заполнить title, description, keywords и robots, которые будут выведены в корневом разделе специального поиска.</p>
				
				<h3>Шаги спецпоиска</h3>
				<p>Каждый специальный поиск может состоять из двух и более шагов. Действует правильно:</p>
				<ul>
					<li>тип последнего шага спецпоиска – это всегда одна или несколько категорий товаров из каталога</li>
					<li>тип шагов кроме последнего – это всегда определенный древовидный список</li>
				</ul>
				<p>Рассмотрим на примере.</p>
				<p>К примеру, для специального поиска «Подбор шин по автомобилю» следует создать два шага:</p>
				<ul>
					<li>- Шаг 1 – тип «Древовидный список» - выбрать дерево автомобилей. Шаг назвать «Применимость»</li>
					<li>- Шаг 2 – тип «Категория товаров» - выбрать категорию «Шины». Шаг назвать «Товары»</li>
				</ul>
				<p>Тогда покупатель на главной странице сайта увидит ссылку «Подбор шин по автомобилю». Перейдет по ней и увидит первый уровень вложенности дерева автомобилей (как правило, это марки). Выберет марку, откроется страница с моделями этой марки и так до последнего уровня вложенности дерева автомобилей. Выбрав последний элемент из дерева автомобилей, откроется категория товаров – «Шины», где будут показаны только шины, подходящие для конкретного выбранного автомобиля.</p>
				<p>Нужно иметь в виду, что для создания подобного специального поиска нужно заранее создать категорию «Шины» в каталоге, одним из свойств этой категории сделать «Применимость» с типом «Древовидный список» и выбрать древовидный список автомобилей (который тоже должен быть создан заранее). Затем при создании конкретных товаров в категории «Шины», нужно указывать для каждого товара один или несколько автомобилей из данного древовидного списка. Всё это рассмотрено в текстовом руководстве пользователя Docpart, а также в видео-уроках.</p>
				<p>При этом функция специальных поисков поддерживает мощную SEO-оптимизацию. На каждом уровне вложенности каждого шага можно задавать шаблон метаданных (h1, title, description, keywords и robots).</p>
				<p>К примеру, у вас в списке автомобилей 3 уровня вложенности: марка, модель, год выпуска (конкретная реализация зависит от фантазии вашего SEO-оптимизатора). Тогда, рассматривая всё тот же пример, для первого шага специального поиска «Применимость», можно задать шаблоны метаданных следующим образом.</p>
				<p>Выбираем шаг «Применимость» в списке шагов. Ниже отобразится окно «Уровни вложенности шага». С помощью кнопок «+» и «-» можно добавлять элементы. Каждый такой элемент – это, по сути, настройка для соответствующего уровня вложенности. Количество таких элементов должно соответствовать логике построения вашей конкретной иерархии соответствующего древовидного списка (которая зависит от фантазии вашего SEO-оптимизатора). В нашем примере, в дереве автомобилей – три уровня вложенности - марка, модель, год выпуска.</p>
				<p>Вот для этих уровней вложенности с помощью кнопки «+» добавим в список «Уровни вложенности шага» три элемента и назовем их соответствующими именами «Марка», «Модель», «Год выпуска». Чтобы переименовать элемент – щелкните по нему двойным щелчком мыши.</p>
				<p>Затем, чтобы настроить метаданные для уровня вложенности – выберите его, щелкнув по нему одним щелчком мыши. Справа отобразится окно «Шаблоны метаданных выбранного уровня вложенности». Там в соответствующих полях введите желаемых шаблоны для h1, title, description, keywords, robots.</p>
				<p>Шаблон метаданных может состоять из фиксированного текста, а также из динамических значений, которые будут подставляться в момент захода посетителя на страницу.</p>
				<p>Рассматривая всё тот же пример, допустим, мы хотим для уровня вложенности «Год выпуска» задать шаблон для тега title так, чтобы к примеру для конкретного автомобиля он выглядел бы так:</p>
				<p>Подбор шин для Audi A8 2015 года.</p>
				<p>Тогда, шаблон в поле title нужно будет указать такой:</p>
				<p>Подбор шин для %prev2% %prev1% %item_name% года.</p>
				<p>И таким же образом можно задать шаблоны для всех метаданных всех уровней вложенности деревьев всех шагов специального поиска.</p>
				<p>Ключи для динамически подставляемых значений, которые можно использовать в шаблонах:</p>
				<ul>
					<li>- название спецпоиска (%search_name%)</li>
					<li>- название текущего элемента дерева (%item_name%)</li>
					<li>- названия родительских элементов и элементов предыдущих списков на предыдущих шагах (%prev1%, %prev2%, %prev3%, %prev4%  и т.д.)</li>
				</ul>

				<p>Таким образом, получается мощный инструмент поиска товаров, который имеет ЧПУ структуру, а также позволяет задать необходимые настройки SEO-оптимизации для всех подразделов.</p>

				
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
				
				//Указание типа списка
				var type_str = "Древовидный список";
				if(obj.type == 1)
				{
					type_str = "Категории товаров";
				}
				
				
				var value_text = "<span><b>" + obj.value + "</b>, тип \""+type_str+"\", объектов "+obj.objects.length+"</span>";//Вывод текста

				return common.icon(obj, common) + common.folder(obj, common)  + icon + value_text;
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
    //Событие при выборе элемента дерева
    tree.attachEvent("onAfterSelect", function(id)
    {
    	onSelected();
    });
    //-----------------------------------------------------
    //Обработка выбора элемента
    function onSelected()
    {
		//Если шаги не созданы
    	if(tree.count() == 0)
    	{
    	    document.getElementById("step_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("step_info_div_col").setAttribute("style", "display:none");
			
			
			//Блок настройки метаданных (скрываем)
			document.getElementById("step_levels_div").setAttribute("style", "display:none");
			document.getElementById("step_levels_metatemplates_div").setAttribute("style", "display:none");
    	    return;
    	}
    	
    	//Выделенный узел
    	var node_id = tree.getSelectedId();//ID выделенного узла
    	if(node_id == 0)
    	{
    	    document.getElementById("step_info_div").innerHTML = "";
			
			//Скрыть контейнер для параметров
			document.getElementById("step_info_div_col").setAttribute("style", "display:none");
			
			
			//Блок настройки метаданных (скрываем)
			document.getElementById("step_levels_div").setAttribute("style", "display:none");
			document.getElementById("step_levels_metatemplates_div").setAttribute("style", "display:none");
    	    return;
    	}
		
		//Показать контейнер для параметров
		document.getElementById("step_info_div_col").setAttribute("style", "display:block");
    	
		
		//Блок настройки метаданных (показываем)
		document.getElementById("step_levels_div").setAttribute("style", "display:block;");//Блок с уровнями вложенности
		show_step_levels_list();//Инициализация дерева для отображения уровней вложенности
		document.getElementById("step_levels_metatemplates_div").setAttribute("style", "display:none");//Блок с метаданными пока скрыт
		
		
    	var node = "";//Ссылка на объект узла
    	//Выделенный узел
    	node = tree.getItem(node_id);
    	
    	var parameters_table_html = "";
		
		var node_id = node.id;
		if(node.is_new)
		{
			node_id = 0;
		}
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">ID</label><div class=\"col-lg-6\">"+node_id+"</div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Название</label><div class=\"col-lg-6\">"+node.value+"</div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----

		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Alias</label><div class=\"col-lg-6\"><input onkeyup=\"apply_options_for_content();\" type=\"text\" id=\"alias_input\" value=\""+node.alias+"\" class=\"form-control\" /></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		if(node.type == 1)
		{
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Тип шага</label><div class=\"col-lg-6\"><select onchange=\"apply_options_for_step();onSelected();\" id=\"type_selector\" class=\"form-control\" ><option value=\"2\">Древовидный список</option><option value=\"1\" selected=\"selected\">Категории товаров</option></select></div></div>";
		}
		else
		{
			parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Тип шага</label><div class=\"col-lg-6\"><select onchange=\"apply_options_for_step();onSelected();\" id=\"type_selector\" class=\"form-control\" ><option value=\"2\" selected=\"selected\">Древовидный список</option><option value=\"1\">Категории товаров</option></select></div></div>";
		}
		
		
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----

		parameters_table_html += "<div class=\"col-lg-12\"><label for=\"\" class=\"col-lg-12 control-label\">Выбор объектов</label></div>";
		
		parameters_table_html += "<div class=\"col-lg-12\"><div id=\"container_G\" style=\"height:150px;\"></div></div>";
		
		

    	document.getElementById("step_info_div").innerHTML = parameters_table_html;
    	
    	//Теперь инициализируем дерево объектов
    	objects_tree_init();
    	
		
    	//Отмечаем объекты:
		var objects_local = node.objects;
    	for(var i=0; i< objects_local.length; i++)
    	{
    	    objects_tree.checkItem(objects_local[i]);
    	}
		
		
		tree.refresh();
    }//function onSelected()
    //-----------------------------------------------------
	var objects_tree = "";//ПЕРЕМЕННАЯ ДЛЯ ДЕРЕВА ОБЪЕКТОВ ШАГА (Категории товаров или Древовидные списки)
        	    
    //Инициализация дерева групп после загруки страницы
    function objects_tree_init()
    {
        /*ДЕРЕВО*/
        //Формирование дерева
        objects_tree = new webix.ui({
        
            //Шаблон элемента дерева
        	template:function(obj, common)//Шаблон узла дерева
            	{
                    var folder = common.folder(obj, common);
            	    var icon = "";
                    
                    var value_text = "<span>" + obj.value + "</span>";//Вывод текста
                    var checkbox = common.checkbox(obj, common);//Чекбокс

                    return common.icon(obj, common)+ checkbox + common.folder(obj, common)  + icon + value_text;
            	},//~template
        
            editable:false,//редактируемое
            container:"container_G",//id блока div для дерева
            view:"tree",
        	select:true,//можно выделять элементы
        	drag:false,//можно переносить
        });
        /*~ДЕРЕВО*/
		
		webix.event(window, "resize", function(){ objects_tree.adjust(); });
		

		//В зависимости от выбранного типа шага - показываем или список древовидных списков или дерево категорий товаров
		if( document.getElementById("type_selector").value == 1 )
		{
			//Выводим дерево категорий товаров
			var saved_objects = <?php echo $catalogue_tree_dump_JSON; ?>;
			objects_tree.parse(saved_objects);
			objects_tree.openAll();
		}
		else//Выводим перечень древовидных списков
		{
			var saved_objects = <?php echo json_encode($tree_lists_array); ?>;
			objects_tree.parse(saved_objects);
			objects_tree.openAll();
		}
		
		
		
		//Событие при выставлении/снятии чекбоксов групп - динамичнское применение настроек
		objects_tree.attachEvent("onItemCheck", function(id)
		{
			apply_options_for_step();
			tree.refresh();
		});
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
    	var newItemId = tree.add( {value:"Новый шаг", is_new:true, alias:"", type:"1", objects:[], levels:[]}, tree.count(), 0);//Добавляем новый узел и запоминаем его ID
    	
    	onSelected();//Обработка текущего выделения
    }
    //-----------------------------------------------------
    //Удаление выделеного элемента
    var deleted_steps = new Array();//Массив с удаленными шагами
	function delete_selected_item()
    {
    	var nodeId = tree.getSelectedId();
		
		//Если этот шаг не новый (т.е. уже был ранее записан в БД), то вносим его в список на удаление
		node = tree.getItem(nodeId);//Выделенный узел
		if(node.is_new == false)
		{
			deleted_steps.push(node.id);
		}
		
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
	//Применить настройки для материала
    function apply_options_for_step()
    {
        //1. Определяем выбранный материал
        var node_id = tree.getSelectedId();//ID выделенного узла
		if(node_id == 0)
		{
			return;
		}
    	node = tree.getItem(node_id);//Выделенный узел

        //2. Сохраняем alias - это обязательное поле
		node.alias = document.getElementById("alias_input").value;
        
		//3. Сохраняем тип
		node.type = document.getElementById("type_selector").value;
		
		
        //4. Сохраняем перечень объектов
        node.objects = new Array();//Массив с выбранными объектами
        node.objects = objects_tree.getChecked();
        
        //Сообщение о результате предварительного сохранения
        //webix.message("Настройки материала предварительно сохранены");
    }
    //-----------------------------------------------------
	//Функция валидации всего специального поиска
	function validate_special_search()
	{
		//1. Должно быть заполнено название поиска
		if( document.getElementById("search_caption_input").value == "" )
		{
			alert("Заполните название поиска");
			return false;
		}
		
		
		//2. Должен быть заполнен Алиас поиска
		if( document.getElementById("search_alias_input").value == "" )
		{
			alert("Заполните алиас поиска");
			return false;
		}
		
		
		//3. Должен быть по хотя бы один шаг
		if(tree.count() == 0)
		{
			alert("Создайте хотя бы один шаг поиска");
			return false;
		}
		
		//4. Должен присутствовать шаг с типом "Категории товаров"
		var type_1 = false;
		var tree_In_JSON = tree.serialize();//Получаем JSON-представление дерева
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(tree_In_JSON[i]["type"] == 1)
			{
				type_1 = true;
				break;
			}
		}
		if(!type_1)
		{
			alert("Создайте шаг с типом \"Категории товаров\"");
			return false;
		}
		

		//5. Шаг с типом "Категории товаров" должен быть один
		var type_1_count = 0;
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(tree_In_JSON[i]["type"] == 1)
			{
				type_1_count++;
			}
		}
		if(type_1_count > 1)
		{
			alert("Шаг с типом \"Категории товаров\" должен быть только один. Удалите лишние шаги с типом \"Категории товаров\"");
			return false;
		}
		
		
		//6. После шага с типом "Категории товаров" не должно быть других шагов, т.е. он должен быть последним
		var meet_type_1 = false;
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(meet_type_1)
			{
				alert("После шага с типом \"Категории товаров\" не должно быть других шагов, т.е. шаг \""+tree_In_JSON[i-1]["value"]+"\""+" должен быть последним");
				return false;
			}
			
			
			if(tree_In_JSON[i]["type"] == 1)
			{
				meet_type_1 = true;//Встретили шаг с типом "Категории товаров"
			}
		}
		
		//7. В каждом шаге с типом "Древовидный список" должен быть только один объект (т.е. не 0 и не более 1)
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(tree_In_JSON[i]["type"] == 2)
			{
				if( parseInt(tree_In_JSON[i]["objects"].length) != 1)
				{
					alert("В каждом шаге с типом \"Древовидный список\" должен быть только один объект (т.е. не 0 и не более 1). Проверьте шаг \""+tree_In_JSON[i]["value"]+"\"");
					return false;
				}
			}
		}
		
		
		//8. В шаге с типом "Категории товаров" должен быть указан хотя бы один объект
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(tree_In_JSON[i]["type"] == 1)
			{
				if( parseInt(tree_In_JSON[i]["objects"].length) == 0)
				{
					alert("В шаге с типом \"Категории товаров\" должен быть указан хотя бы один объект. Проверьте шаг \""+tree_In_JSON[i]["value"]+"\"");
					return false;
				}
			}
		}
		
		
		//9. В каждом шаге должен быть заполнен Алиас
		for(var i=0; i < tree_In_JSON.length; i++)
		{
			if(tree_In_JSON[i]["alias"] == "")
			{
				alert("В каждом шаге должен быть заполнен Алиас. Проверьте шаг \""+tree_In_JSON[i]["value"]+"\"");
				return false;
			}
		}
		
		
		//Все проверки пройдены:
		return true;
	}
	//-----------------------------------------------------
	//Инициализация редактора дерева после загруки страницы
    function tree_start_init()
    {
    	var steps = <?php echo $steps; ?>;
	    tree.parse(steps);
	    tree.openAll();
    }
    tree_start_init();
    onSelected();//Обработка текущего выделения
    // ----------------------------------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------------------------------
    // ----------------------------------------------------------------------------------------------------------
	//Дерево для списка уровненей вложенности шага (для ЧПУ)
	let tree_B = "";
	function show_step_levels_list()
	{
		document.getElementById("container_B").innerHTML = "";
		
		//Формирование дерева
		tree_B = new webix.ui({
			
			//Шаблон элемента дерева
			template:function(obj, common)//Шаблон узла дерева
				{
					let n = 0;
					//Шаг
					let node_id = tree.getSelectedId();//ID выделенного узла
					let node = tree.getItem(node_id);//Объект выделенного узла
					//Находим уровень вложенности
					for(let i=0; i < node.levels.length; i++)
					{
						n++;
						if( parseInt(node.levels[i].id) == parseInt(obj.id) )
						{
							break;
						}
					}
					
					
					
					
					
					var folder = common.folder(obj, common);
					var icon = "";
					return common.icon(obj, common) + common.folder(obj, common)  + icon + "<span>" + n + ". <b>" +obj.value+"</b></span>";
				},//~template
			
			
			editable:true,//редактируемое
			editValue:"value",
			editaction:"dblclick",//редактирование по двойному нажатию
			container:"container_B",//id блока div для дерева
			view:"edittree",
			select:true,//можно выделять элементы
			drag:true,//можно переносить
			editor:"text",//тип редактирование - текстовый
		});
		//Событие при выборе элемента дерева
		tree_B.attachEvent("onAfterSelect", function(id)
		{
			onSelected_B();
		});
		//Обработчик После перетаскивания узлов дерева (когда меняется порядок, нужно его зафиксировать в объекте)
		tree_B.attachEvent("onAfterDrop",function(){
			
			//Шаг
			let node_id = tree.getSelectedId();//ID выделенного узла
			let node = tree.getItem(node_id);//Объект выделенного узла
			
			let levels_ob = tree_B.serialize();
			console.log(levels_ob);
			
			node.levels = JSON.parse(JSON.stringify(levels_ob));
			
			tree_B.refresh();
			onSelected_B();
		});
		/*tree_B.attachEvent("onAfterEditStop",function(){
			//Шаг
			let node_id = tree.getSelectedId();//ID выделенного узла
			let node = tree.getItem(node_id);//Объект выделенного узла
			
			let levels_ob = tree_B.serialize();
			console.log(levels_ob);
			
			node.levels = JSON.parse(JSON.stringify(levels_ob));
			
			tree_B.refresh();
			onSelected_B();
		});*/

		tree_B.adjust();
		
		
		
		let node_id = tree.getSelectedId();//ID выделенного узла
    	let node = tree.getItem(node_id);//Объект выделенного узла
		tree_B.parse(node.levels);
	    tree_B.openAll();
		
		
		onSelected_B();
	}
	//-----------------------------------------------------
	//Обработка выбора одного из уровней вложенности
	function onSelected_B()
	{
		let step_level_id = tree_B.getSelectedId();
		if( step_level_id == 0 )
		{
			document.getElementById("step_levels_metatemplates_div").setAttribute("style", "display:none;");
			return;
		}
		
		
		document.getElementById("step_levels_metatemplates_div").setAttribute("style", "display:block;");
		
		
		//Шаг
		let node_id = tree.getSelectedId();//ID выделенного узла
    	let node = tree.getItem(node_id);//Объект выделенного узла
		
		//Находим уровень вложенности
		for(let i=0; i < node.levels.length; i++)
		{
			if( parseInt(node.levels[i].id) == parseInt(step_level_id) )
			{
				//Заполняем поля ввода метаданных текущими значениями
				
				document.getElementById("level_h1").value = node.levels[i].h1;
				document.getElementById("level_title").value = node.levels[i].title;
				document.getElementById("level_description").value = node.levels[i].description;
				document.getElementById("level_keywords").value = node.levels[i].keywords;
				document.getElementById("level_robots").value = node.levels[i].robots;
				
				
				break;
			}
		}
		
		
	}
	//-----------------------------------------------------
    //Добавить новый уровень вложенности для выделенного шага
    function add_new_item_B()
    {
		let node_id = tree.getSelectedId();//ID выделенного узла
    	let node = tree.getItem(node_id);//Объект выделенного узла
		
		//Добавляем объект уровня вложенности прямо в объет шага
		node.levels.push( {value:"Новый уровень", is_new:true, h1:"", title:"", description:"", keywords:"", robots:""} );
		
		//Переотображаем дерево
		tree_B.clearAll();
		tree_B.parse(node.levels);
	    tree_B.openAll();
		
		onSelected_B();
    }
    //-----------------------------------------------------
	//Удалить уровень вложенности
	function delete_selected_item_B()
	{
		//Объект уровня вложенности
		let step_level_id = tree_B.getSelectedId();
		if( step_level_id == 0 )
		{
			alert("Не выбран уровень для удаления");
			return;
		}
		
		//Шаг
		let node_id = tree.getSelectedId();//ID выделенного узла
    	let node = tree.getItem(node_id);//Объект выделенного узла
		
		//Удаляем уровень вложенности
		for(let i=0; i < node.levels.length; i++)
		{
			console.log( node.levels[i].id + " - " + step_level_id );
			
			if( parseInt(node.levels[i].id) == parseInt(step_level_id) )
			{
				node.levels.splice(i,1);
				break;
			}
		}
		
		
		//Переотображаем дерево
		tree_B.clearAll();
		tree_B.parse(node.levels);
	    tree_B.openAll();
	}
	//-----------------------------------------------------
	//Снять выделение со списка уровненей вложенности
	function unselect_tree_B()
	{
		tree_B.unselect();
    	onSelected_B();
	}
	//-----------------------------------------------------
	//Функция отладки
	function debug()
	{
		var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
		
		console.log(tree_dump);
	}
	//-----------------------------------------------------
	//Функция применения вводимых значений в поля метаданных
	function on_metadata_edit()
	{
		let step_level_id = tree_B.getSelectedId();

		//Шаг
		let node_id = tree.getSelectedId();//ID выделенного узла
    	let node = tree.getItem(node_id);//Объект выделенного узла
		
		//Находим уровень вложенности
		for(let i=0; i < node.levels.length; i++)
		{
			if( parseInt(node.levels[i].id) == parseInt(step_level_id) )
			{
				//Заполняем поля ввода метаданных текущими значениями

				node.levels[i].h1 = document.getElementById("level_h1").value;
				node.levels[i].title = document.getElementById("level_title").value;
				node.levels[i].description = document.getElementById("level_description").value;
				node.levels[i].keywords = document.getElementById("level_keywords").value;
				node.levels[i].robots = document.getElementById("level_robots").value;
				
				break;
			}
		}
	}
	//-----------------------------------------------------
	</script>

	
	
	
	
	
	
	
	
	
	
	
	
	<script>
	//Функция сохранения изменений
	function save_action()
	{
		//Проверка корректности данных
		if( ! validate_special_search() )
		{
			return;
		}
		
		
		//Заполняем форму
		//1. Дерево шагов
    	var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
    	var tree_json_input = document.getElementById("tree_json");
    	tree_json_input.value = tree_dump;
		
		//2. Название поиска и метаданные
		document.getElementById("search_caption").value = document.getElementById("search_caption_input").value;
		document.getElementById("search_title").value = document.getElementById("search_title_input").value;
		document.getElementById("search_description").value = document.getElementById("search_description_input").value;
		document.getElementById("search_keywords").value = document.getElementById("search_keywords_input").value;
		document.getElementById("search_robots").value = document.getElementById("search_robots_input").value;
		
		//3. Алиас поиска
		document.getElementById("search_alias").value = document.getElementById("search_alias_input").value;
		
		//4. Порядок следования поиска
		document.getElementById("search_order").value = document.getElementById("search_order_input").value;
		
		//6. Список шагов на удаление
		document.getElementById("deleted_steps").value = JSON.stringify(deleted_steps);
		
		//7. Флаг активности
		var search_active = 1;
		if( document.getElementById("search_active_checkbox").checked == false )
		{
			search_active = 0;
		}
		document.getElementById("search_active").value = search_active;
		
		
		//Отправка формы
		document.forms["form_to_save"].submit();
	}
	</script>
	
	<?php
}
?>