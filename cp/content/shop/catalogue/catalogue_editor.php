<?php
/**
 * Страница редатора каталога
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");//Определение класса записи категории
?>



<?php
// --------------------------------- Start PHP - метод ---------------------------------
//Рекурсивная функция для перевода иерархического массива (JSON перечня категорий) в линейный массив (просто набор объектов категорий)
function getLinearListOfCategories($hierarchy_array)
{
    $linear_array = array();//Линейный массив
    
    for($i=0; $i<count($hierarchy_array); $i++)
    {
        //Генерируем объект записи материала и заносим его в линейный массив
        $current_category = new DP_CatalogueCategory;
        $current_category->id = $hierarchy_array[$i]["id"];
        $current_category->alias = $hierarchy_array[$i]["alias"];
        $current_category->url = $hierarchy_array[$i]["url"];
        $current_category->count = $hierarchy_array[$i]['$count'];
        $current_category->level = $hierarchy_array[$i]['$level'];
        $current_category->value = $hierarchy_array[$i]["value"];
        $current_category->parent = $hierarchy_array[$i]['$parent'];
        $current_category->title_tag = $hierarchy_array[$i]['title_tag'];
        $current_category->description_tag = $hierarchy_array[$i]['description_tag'];
        $current_category->keywords_tag = $hierarchy_array[$i]['keywords_tag'];
        $current_category->robots_tag = $hierarchy_array[$i]['robots_tag'];
        $current_category->import_format = $hierarchy_array[$i]['import_format'];
        $current_category->export_format = $hierarchy_array[$i]['export_format'];
        $current_category->properties = $hierarchy_array[$i]['properties'];
		$current_category->published_flag = $hierarchy_array[$i]['published_flag'];
		$current_category->image = $hierarchy_array[$i]['image'];
		$current_category->img_blob = $hierarchy_array[$i]['img_blob'];
		$current_category->img_blob_name = $hierarchy_array[$i]['img_blob_name'];
		$current_category->by_template = $hierarchy_array[$i]['by_template'];
        
        array_push($linear_array, $current_category);
        
        //Рекурсивный вызов для вложенного уровня
        if($hierarchy_array[$i]['$count'] > 0)
        {
            $data_linear_array = getLinearListOfCategories($hierarchy_array[$i]["data"]);
            //Добавляем массив вложенного уровня к текущему
            for($j=0; $j<count($data_linear_array); $j++)
            {
                array_push($linear_array, $data_linear_array[$j]);
            }//for(j)
        }
    }//for(i)
    
    return $linear_array;
}//~function getLinearListOfCategories($hierarchy_array)
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ End PHP - метод ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


if(!empty($_POST["save_tree"]))
{
	try
	{
		//Старт транзакции
		if( ! $db_link->beginTransaction()  )
		{
			throw new Exception("Не удалось стартовать транзакцию");
		}
		
		//Генерируем линейный массив на основе полученого иерархического
		$php_dump = json_decode($_POST["tree_json"], true);
		$linear_array = array();//Линейный массив материалов
		$linear_array = getLinearListOfCategories($php_dump);//Генерируем линейный массив категорий
		
		//По всем элементам линейного массива: Созданние и Обновление
		for($i=0; $i<count($linear_array); $i++)
		{
			$is_category_new = true;
			
			$order = $i+1;//Порядок отображения категории
			
			//Проверяем существование записи категории:
			$check_category_exist_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_catalogue_categories` WHERE `id`=?;");
			$check_category_exist_query->execute( array($linear_array[$i]->id) );
			if($check_category_exist_query->fetchColumn() == 1)
			{
				$is_category_new = false;//Категория уже существовала ранее
				//Запись существует - ее нужно обновить

				if( ! $db_link->prepare("UPDATE `shop_catalogue_categories` SET `alias`=?, `url`=?, `count`=?, `level`=?, `value`=?, `parent`=?, `title_tag`=?, `description_tag`=?, `keywords_tag`=?, `robots_tag`=?, `import_format`=?, `export_format`=?, `order` = ?, `published_flag` = ?, `image` = ? WHERE `id`=?;")->execute( array($linear_array[$i]->alias, $linear_array[$i]->url, $linear_array[$i]->count, $linear_array[$i]->level, $linear_array[$i]->value, $linear_array[$i]->parent, $linear_array[$i]->title_tag, $linear_array[$i]->description_tag, $linear_array[$i]->keywords_tag, $linear_array[$i]->robots_tag, $linear_array[$i]->import_format, $linear_array[$i]->export_format, $order, $linear_array[$i]->published_flag, $linear_array[$i]->image, $linear_array[$i]->id) ) )
				{
					throw new Exception("Ошибка обновления старых категорий");
				}
			}
			else
			{
				//Запись не существует - ее нужно создать
				if( ! $db_link->prepare("INSERT INTO `shop_catalogue_categories` (`id`, `alias`, `url`,`count`, `level`, `value`, `parent`, `title_tag`, `description_tag`, `keywords_tag`, `robots_tag`, `import_format`, `export_format`, `order`, `published_flag`, `image`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);")->execute( array($linear_array[$i]->id, $linear_array[$i]->alias, $linear_array[$i]->url, $linear_array[$i]->count, $linear_array[$i]->level, $linear_array[$i]->value, $linear_array[$i]->parent, $linear_array[$i]->title_tag, $linear_array[$i]->description_tag, $linear_array[$i]->keywords_tag, $linear_array[$i]->robots_tag, $linear_array[$i]->import_format, $linear_array[$i]->export_format, $order, $linear_array[$i]->published_flag, $linear_array[$i]->image) ) )
				{
					throw new Exception("Ошибка добавления новых категорий");
				}
			}
			
			
			//НОВЫЙ ПОДЭТАП - РАБОТА СО СВОЙСТВАМИ КАТЕГОРИЙ
			$properties = $linear_array[$i]->properties;
			//Удаляем свойства, которые были удалены при редактировании категории - только если категория НЕ новая
			if($is_category_new == false)
			{
				//Получаем все свойства категории, какие у нас вообще только есть
				
				$all_category_properties_query = $db_link->prepare("SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = ?;");
				$all_category_properties_query->execute( array($linear_array[$i]->id) );
				
				while( $current_property = $all_category_properties_query->fetch() )
				{
					$do_not_delete = false;//Флаг - не нужно удалять
					for($p = 0; $p < count($properties); $p++)
					{
						if($current_property["id"] == $properties[$p]["id"])
						{
							$do_not_delete = true;
							break;//for($p)
						}
					}
					
					if($do_not_delete == false)
					{
						if( ! $db_link->prepare("DELETE FROM `shop_categories_properties_map` WHERE `id` = ?;")->execute( array($current_property["id"]) ) )
						{
							throw new Exception("Ошибка удаления свойств категории");
						}
					}
				}//for($ep)
			}//if($is_category_new == false)
			//Создаем или обновляем свойства
			for($p = 0; $p < count($properties); $p++)
			{
				$property_order = $p + 1;//Порядковый номер свойства - для нужно расположения при отображении
				
				$SQL = "";
				
				//Если свойство имеет флаг just_created = 1, значит - это новое свойство - INSERT
				if($properties[$p]["just_created"] == true)
				{
					$SQL = "INSERT INTO `shop_categories_properties_map` (`category_id`, `property_type_id`, `value`, `list_id`, `order`, `for_similar`, `is_option`) VALUES (?, ?, ?, ?, ?, ?, ?);";
					
					$binding_values = array($properties[$p]["category_id"], $properties[$p]["property_type_id"], $properties[$p]["value"], $properties[$p]["list_id"], $property_order, $properties[$p]["for_similar"], $properties[$p]["is_option"]);
				}
				else//Свойтво уже было - то у него поле id равно id из таблицы свойств - UPDATE
				{
					$SQL = "UPDATE `shop_categories_properties_map` SET `value` = ?, `list_id` = ?, `order` = ?, `for_similar` = ?, `is_option` = ?  WHERE `id` = ?;";
					
					$binding_values = array($properties[$p]["value"], $properties[$p]["list_id"], $property_order, $properties[$p]["for_similar"], $properties[$p]["is_option"], $properties[$p]["id"]);
				}
				
				
				if( ! $db_link->prepare($SQL)->execute( $binding_values ) )
				{
					throw new Exception("Ошибка обработки свойств категорий");
				}
				//echo "Выполнено: ".$SQL."<br>";
			}
		}//for($i) По всем элементам линейного массива:
		
		
		//По всем записям базы данных для удаления записей, которые были удалены при редактировании
		$deleted_categories_list = array();//Массив с ID удаляемыйх категорий
		$deleted_categories_images = array();//Список имен файлов изображений удаленных категорий
		$all_categories_query = $db_link->prepare("SELECT * FROM `shop_catalogue_categories`;");
		$all_categories_query->execute();
		while( $category_record = $all_categories_query->fetch() )
		{
			$such_category_exist = false;
			for($j=0; $j < count($linear_array); $j++)
			{
				if($category_record["id"] == $linear_array[$j]->id)
				{
					$such_category_exist = true;
					break;
				}
			}
			
			//Если такой категории нет в сохраняемом перечне, значит при редактировании она была удалена - удаляем ее из БД, а также удаляем ее пиктограмму
			if(!$such_category_exist)
			{
				array_push($deleted_categories_list, $category_record["id"]);//Добавляем ID в список
				if( ! $db_link->prepare("DELETE FROM `shop_catalogue_categories` WHERE `id` = ?;")->execute( array($category_record["id"]) ) )
				{
					throw new Exception("Ошибка удаления категорий");
				}
				
				//Добавляем имя файла изображения этой категории в список файлов на удаление
				if( array_search($category_record["image"], $deleted_categories_images) === false && $category_record["image"] != NULL && $category_record["image"] != "" )
				{
					$deleted_categories_images[] = $category_record["image"];
				}
			}
		}
		//Удаляем свойства удаленных категорий
		$SQL_DELETE_PROPERTIES = "DELETE FROM `shop_categories_properties_map` WHERE";
		$binding_values = array();
		for($i=0; $i < count($deleted_categories_list); $i++)
		{
			if($i > 0)
			{
				$SQL_DELETE_PROPERTIES .= " OR";
			}
			$SQL_DELETE_PROPERTIES .= " category_id = ?";
			
			
			array_push($binding_values, $deleted_categories_list[$i]);
		}
		if(count($deleted_categories_list) > 0)
		{
			if( ! $db_link->prepare($SQL_DELETE_PROPERTIES)->execute( $binding_values ) )
			{
				throw new Exception("Ошибка удаления свойств удаленных категорий");
			}
		}
		//УДАЛЯЕМ ПРОДУКТЫ УДАЛЕННЫХ КАТЕГОРИЙ
		if(count($deleted_categories_list) > 0)
		{
			//Получаем список продуктов каждой категории
			$products_to_delete = array();
			$SQL_SELECT_PRODUCTS_TO_DELETE = "SELECT `id` FROM `shop_catalogue_products` WHERE `category_id` IN (";
			$binding_values = array();
			for($i=0; $i < count($deleted_categories_list); $i++)
			{
				if($i > 0)
				{
					$SQL_SELECT_PRODUCTS_TO_DELETE .= ",";
				}
				$SQL_SELECT_PRODUCTS_TO_DELETE .= "?";
				
				array_push($binding_values, $deleted_categories_list[$i]);
			}
			$SQL_SELECT_PRODUCTS_TO_DELETE .= ");";
			
			$products_to_delete_query = $db_link->prepare($SQL_SELECT_PRODUCTS_TO_DELETE);
			$products_to_delete_query->execute($binding_values);
			while($product = $products_to_delete_query->fetch() )
			{
				array_push($products_to_delete, $product["id"]);
			}
			
			if(count($products_to_delete) > 0)
			{
				//Подключаем модульный скрипт для удаления продуктов (он работает в контексте транзакции)
				require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/catalogue/delete_products_sub.php");
			}
		}// ~ удаление товаров удаленных категорий
		

		
		//СОХРАНЕНИЕ ИЗОБРАЖЕНИЙ
		$warning_message = '';//Если загрузка одного или нескольких файлов будет с ошибкой, то, запись в БД продолжаем. Но, пользователю нужно показать warning
		$warning_message_for_blob = '';
		$files_upload_dir = $_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/";
		for($i=0; $i < count($linear_array); $i++)
		{
			//Если категория создается на основе шаблона, то, проверяем наличие изображения в поле blob
			if( isset( $linear_array[$i]->by_template ) )
			{
				if( $linear_array[$i]->by_template > 0 )
				{
					if( !empty( $linear_array[$i]->img_blob ) )
					{
						//Получаем расширение файла
						$file_extension = explode(".", $linear_array[$i]->img_blob_name);
						$file_extension = $file_extension[count($file_extension)-1];
						//Имя файла будет вида <id категории>.$file_extension
						$saved_file_name = $linear_array[$i]->id.".".$file_extension;
						
						//Прежде, чем сохранять файл с таким именем, нужно проверить, чтобы в других категориях это имя файла не использовалось (иначе при замене файла у них файл тоже заменится)
						$file_used_by_other = false;//Флаг "Файл используется в других категориях"
						$file_pref = 1;//Префикс для уникальности имени файла
						do
						{
							$check_file_use_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_catalogue_categories` WHERE `image` = ? AND `id` != ?;');
							$check_file_use_query->execute( array( $saved_file_name, $linear_array[$i]->id) );
							if( $check_file_use_query->fetchColumn() > 0 )
							{
								$file_used_by_other = true;//Имя файла используется другими категориями, заменять файл нельзя
								
								//Имя файла будет вида <id категории>_<префикс>.$file_extension
								$saved_file_name = $linear_array[$i]->id."_".$file_pref.".".$file_extension;
								
								$file_pref++;
							}
							else
							{
								$file_used_by_other = false;//Имя уникально
							}
							
						}while( $file_used_by_other );
						//В итоге - полный путь к файлу
						$uploadfile = $files_upload_dir.$saved_file_name;
						
						//Создаем файл из blob шаблона категории
						$ifp = fopen( $uploadfile, 'wb' );//На запись в бинарном виде
						if( $ifp )
						{
							fwrite( $ifp, base64_decode( $linear_array[$i]->img_blob ) );
							fclose( $ifp ); 
						}
						else
						{
							$warning_message_for_blob = 'Не удалось создать один или несколько файлов изображений на основе шаблона категории. ';
						}
						
						if( ! $db_link->prepare("UPDATE `shop_catalogue_categories` SET `image` = ? WHERE `id` = ?;")->execute( array($saved_file_name, $linear_array[$i]->id) ) )
						{
							throw new Exception("Ошибка записи имени файла для пиктограммы категории (на основе шаблона)");
						}
					}
				}
			}
			
			
			
			
			//Если для данной категории загружается файл через инпут.
			$FILE_POST = $_FILES["img_".$linear_array[$i]->id];
			
			if( $FILE_POST["size"] > 0 )
			{
				//Получаем файл:
				$fileName = $FILE_POST["name"];
				
				//Получаем расширение файла
				$file_extension = explode(".", $fileName);
				$file_extension = $file_extension[count($file_extension)-1];
				//Имя файла будет вида <id категории>.$file_extension
				$saved_file_name = $linear_array[$i]->id.".".$file_extension;
				
				
				//Прежде, чем сохранять файл с таким именем, нужно проверить, чтобы в других категориях это имя файла не использовалось (иначе при замене файла у них файл тоже заменится)
				$file_used_by_other = false;//Флаг "Файл используется в других категориях"
				$file_pref = 1;//Префикс для уникальности имени файла
				do
				{
					$check_file_use_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_catalogue_categories` WHERE `image` = ? AND `id` != ?;');
					$check_file_use_query->execute( array( $saved_file_name, $linear_array[$i]->id) );
					if( $check_file_use_query->fetchColumn() > 0 )
					{
						$file_used_by_other = true;//Имя файла используется другими категориями, заменять файл нельзя
						
						//Имя файла будет вида <id категории>_<префикс>.$file_extension
						$saved_file_name = $linear_array[$i]->id."_".$file_pref.".".$file_extension;
						
						$file_pref++;
					}
					else
					{
						$file_used_by_other = false;//Имя уникально
					}
					
				}while( $file_used_by_other );
				
				
				$uploadfile = $files_upload_dir.$saved_file_name;
				
				if ( ! copy($FILE_POST['tmp_name'], $uploadfile) )
				{
					//throw new Exception("Ошибка загрузки файла для пиктограммы категории");
					$warning_message = 'Один или несколько файлов изображений не удалось загрузить. ';
				}
				else if( ! $db_link->prepare("UPDATE `shop_catalogue_categories` SET `image` = ? WHERE `id` = ?;")->execute( array($saved_file_name, $linear_array[$i]->id) ) )
				{
					throw new Exception("Ошибка записи имени файла для пиктограммы категории");
				}
			}
		}
		$warning_message = $warning_message.$warning_message_for_blob;
		
		
		//Новый блок для работы с изображениями
		//Удаляем изображения удаленных категорий
		for( $i=0 ; $i < count($deleted_categories_images) ; $i++)
		{
			//Перед тем, как удалить файл, проверяем, используется ли он в другой категории
			$check_file_use_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_catalogue_categories` WHERE `image` = ?;');
			$check_file_use_query->execute( array( $deleted_categories_images[$i] ) );
			if( $check_file_use_query->fetchColumn() == 0 )
			{
				//Данный файл не используется в других категориях - удаляем его
				if( file_exists( $_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/".$deleted_categories_images[$i] ) )
				{
					if( ! unlink($_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/".$deleted_categories_images[$i]))
					{
						//throw new Exception("Ошибка удаления файла для пиктограммы категории");
					}
				}
			}
		}
		
	}
	catch (Exception $e)
	{
		//Откатываем все изменения
		$db_link->rollBack();
		
		?>
        <script>
            location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/catalogue_editor?error_message=<?php echo urlencode($e->getMessage().". Изменения не записаны в базу данных."); ?>";
        </script>
        <?php
        exit;
	}

	//Дошли до сюда, значит выполнено ОК
	$db_link->commit();//Коммитим все изменения и закрываем транзакцию
	
	?>
	<script>
		location="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/shop/catalogue/catalogue_editor?success_message=<?php echo urlencode("Структура каталога сохранена успешно!"); ?>&warning_message=<?php echo urlencode($warning_message); ?>";
	</script>
	<?php
	exit;
}
else//Если действий нет - выводим страницу
{
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
    

	//Получаем ID следующей категории товара
	$next_id_query = $db_link->prepare("SHOW TABLE STATUS LIKE 'shop_catalogue_categories'");
	$next_id_query->execute();
	$next_id_record = $next_id_query->fetch();
	if( $next_id_record == false )
	{
		exit("SQL error: next_id_query");
	}
    $next_id = $next_id_record["Auto_increment"];//ID следующей добавляемой категории товара
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
				
				
				<a class="panel_a" onClick="open_properties_window();" href="javascript:void(0);">
					<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/share.png') 0 0 no-repeat;"></div>
					<div class="panel_a_caption">Свойства категории</div>
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
			
			
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-6">
						<script>
						//Через куки запоминаем настройку - так, чтобы при следующем отображении страницы выставить этот чекбокс в тоже самое значение
						// ------------------------------------------------------------------------------------------------
						//Функция текущего получения флага "Добавлять базовые свойства"
						function add_base_properties()
						{
							if(document.getElementById("base_properties").checked == true)
							{
								return true;
							}
							return false;
						}
						// ------------------------------------------------------------------------------------------------
						//Обработка изменения значения на чекбоксе
						function base_properties_changed()
						{
							var base_properties = "0";
							if( add_base_properties() )
							{
								base_properties = "1";
							}

							
							//Устанавливаем cookie
							var date = new Date(new Date().getTime() + 15552000 * 1000);
							document.cookie = "base_properties="+base_properties+"; path=/; expires=" + date.toUTCString();
						}
						// ------------------------------------------------------------------------------------------------
						</script>
						<?php
						$base_properties_checked = " checked=\"checked\" ";//Исходное положение по умолчанию
						if( isset($_COOKIE["base_properties"]) )
						{
							if((int)$_COOKIE["base_properties"] != 1)
							{
								$base_properties_checked = "";
							}
						}
						?>
						<input onchange="base_properties_changed();" type="checkbox" value="base_properties" id="base_properties" <?php echo $base_properties_checked; ?> /> <label for="base_properties">Добавлять базовые свойства к категориям</label> 
						<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Базовые свойства товара - это:<ul><li><b>Артикул</b> (типа &quot;Строка&quot;)</li><li><b>Производитель</b> (типа &quot;Линейный список&quot;)</li></ul>Эти свойства необходимы, если нужно обеспечить поиск товаров из своего каталога по артикулу. Такие свойства можно добавлять к категориям товаров в дереве категорий вручную, но, если выставить эту галку, то они будут добавляться в новые (создаваемые Вами) категории автоматически');"><i class="fa fa-info"></i></button>
					</div>
				</div>
			</div>
			
		</div>
	</div>

	
	<div class="col-lg-6">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Дерево категорий товаров
			</div>
			
			<div class="panel-body">
				<div id="container_A" style="height:350px;">
				</div>
			</div>
			
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-6" id="tree_footer_buttons">
					</div>
					
					<div class="col-md-6 text-right" id="copy_cut_buffer_div">
					</div>
				</div>
			</div>
			
			
			<div class="panel-footer">
				<div class="row">
					<div class="col-md-12">
						
						<a class="btn btn-primary" href="javascript:void(0);" style="border:0;" onclick="open_templates_window();"><i class="far fa-clone"></i> <span class="bold">Шаблоны</span></a> 
						
						<a class="btn btn-info create-template-button" style="border:0;" href="javascript:void(0);" onclick="create_template();"><i class="fas fa-arrow-left"></i> <span class="bold">Добавить в шаблоны</span></a> 
						
						<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('<b>Функция шаблонов категорий</b><br><br>Данная функция позволяет создавать шаблоны на основе категорий и затем использовать эти шаблоны для создания новых категорий с таким же набором свойств и изображением.<br><br><b>Для создания шаблона</b>, выберите категорию в дереве категорий и нажмите кнопку &quot;Добавить в шаблоны&quot;. Будет создан шаблон, в точности повторяющий описание категории.<br><br><b>Для создания категории</b> на основе шаблона, нажмите кнопку &quot;Шаблоны&quot; (откроется список шаблонов), выберите нужный шаблон и затем нажмите кнопку &quot;В буфер&quot;. Выбранный шаблон будет добавлен в буфер, после чего можно будет создавать новые категории нажатием кнопки &quot;Вставить&quot; (точно также, как при копировании шаблонов или вырезании).');"><i class="fa fa-info"></i></button>
						
					</div>
				</div>
			</div>
			
			
		</div>
	</div>
	
	
	<div class="col-lg-6" id="content_info_div_col">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Параметры выбранной категории
			</div>
			<div class="panel-body">
				<div id="content_info_div">
				</div>
			</div>
		</div>
	</div>
	
	

    <!--Форма для отправки-->
    <form name="form_to_save" method="post" style="display:none" enctype="multipart/form-data">
        <input name="save_tree" id="save_tree" type="text" value="ok" style="display:none"/>
        <input name="tree_json" id="tree_json" type="text" value="" style="display:none"/>
        
        <!-- Изображения загружаются с помощью input[type="file"], которые добавляются сюда при добавлении новой категории -->
        <div id="img_box">
        </div>
    </form>
    <!--Форма для отправки-->
    
	
	
	<script src="/<?php echo $DP_Config->backend_dir; ?>/content/shop/catalogue/copy_paste_categories.js"></script>

    
    <script type="text/javascript" charset="utf-8">
    var next_id = <?php echo $next_id;?>;//id следующей категории
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
				if(obj.published_flag == false)
                {
                    icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
                    value_text = "<span style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
                }
				
				
				//Индикация элемента, помеченного на вырезание
				if( typeof obj.to_cut != 'undefined' )
				{
					if( obj.to_cut == true )
					{
						//icon_system += "<i class=\"fas fa-cut\" style='float:right; margin:0px 4px 8px 4px;'></i>";
						folder = "<i class=\"fas fa-cut\" style='margin:0px 8px 8px 4px;color:#CCC;'></i>";
						value_text = "<span style=\"color:#CCC;\">" + obj.value + "</span>";//Вывод текста
					}
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
    //Обработка выбора элемента
    function onSelected()
    {
		//Кнопки Копировать и Вырезать не активны
		activate_copy_cut_buttons(false);
		
		
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
    	
		//Кнопки Копировать и Вырезать активны
		activate_copy_cut_buttons(true);
		
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
        
		var checked = "";
		if(node.published_flag == 1)
		{
			checked = " checked=\"checked\" ";
		}
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Видна покупателям</label><div class=\"col-lg-6\"><input onchange=\"dynamicApplyingCheck('published_flag');\" type=\"checkbox\" id=\"published_flag\" "+checked+" class=\"form-control\"/></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		//parameters_table_html += "<tr> <td>Уровень вложенности</td> <td>"+node.$level+"</td> </tr>";
        //parameters_table_html += "<tr> <td>ID родителя</td> <td>"+node.$parent+"</td> </tr>";

		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Alias</label><div class=\"col-lg-6\"><input type=\"text\" onKeyUp=\"dynamicApplying('alias');\" id=\"alias\" value=\""+node.alias+"\" class=\"form-control\" /></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Тег title</label><div class=\"col-lg-6\"><input type=\"text\" onKeyUp=\"dynamicApplying('title_tag');\" id=\"title_tag\" value=\""+node.title_tag+"\" class=\"form-control\"/></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
        
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета description</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onKeyUp=\"dynamicApplying('description_tag');\" id=\"description_tag\">"+node.description_tag+"</textarea></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета keywords</label><div class=\"col-lg-6\"><textarea class=\"form-control\" onKeyUp=\"dynamicApplying('keywords_tag');\" id=\"keywords_tag\">"+node.keywords_tag+"</textarea></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Мета robots</label><div class=\"col-lg-6\"><input type=\"text\" id=\"robots_tag\" onKeyUp=\"dynamicApplying('robots_tag');\" value=\""+node.robots_tag+"\" class=\"form-control\"/></div></div>";
		
		parameters_table_html += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
		
		//Изображение
		parameters_table_html += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Пиктограмма</label><div class=\"col-lg-6\"><button class=\"btn btn-success\" type=\"button\" onclick=\"document.getElementById('img_"+node.id+"').click();\"><i class=\"fa fa-file\"></i> <span class=\"bold\">Выбрать файл</span></button></div></div>";
		parameters_table_html += "<div class=\"col-lg-12 text-center\" id=\"image_div\"></div>";

    	document.getElementById("content_info_div").innerHTML = parameters_table_html;
    	
    	
    	//Выводим текущее изображение категории - для индикации
    	document.getElementById("image_div").innerHTML = "<img onerror = \"this.src = '<?php echo "/content/files/images/no_image.png"; ?>'\" src=\""+node.image_url+"\" style=\"max-width:96px; max-height:96px\" />";
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
    //Обработка изменения файла для выбранной категории
    function onFileChanged()
    {
        //Выделенный узел
    	var node_id = tree.getSelectedId();//ID выделенного узла
    	node = tree.getItem(node_id);

        var input_file = document.getElementById("img_"+node_id);//input для файла изображения
        var file = input_file.files[0];//Получаем выбранный файл
        
        if(file == undefined)
        {
            return;
        }
        
        //Запрещаем загружать файлы больше 50 Кб
        if(file.size > 512000)
        {
            input_file.value = null;
            alert("Размер файла превышает 0.5 Мб");
            return;
        }
        
        //Проверяем тип файла
        if(file.type != "image/jpeg" && file.type != "image/jpg" && file.type != "image/png" && file.type != "image/gif")
        {
            input_file.value = null;
            alert("Файл должен быть изображением");
            return;
        }
        
        
        //Создаем url файла для его отображения
        node.image_url = URL.createObjectURL(file);
    
        onSelected();
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
    	var parentId= tree.getSelectedId();//Выделеный узел
    	var newItemId = 0;
		
		//Проверка, чтобы вставка была не в вырезаемый узел
		if( parentId > 0 )
		{
			var parentItem = tree.getItem(parentId);
			if( typeof parentItem.to_cut != 'undefined' )
			{
				if( parentItem.to_cut )
				{
					alert('Нельзя вставить новый элемент в вырезаемую часть дерева');
					return;
				}
			}
		}
		
		
		if( add_base_properties() )
		{
			//Добавляем категорию с базовыми свойствами (Производитель и Артикул)
			newItemId = tree.add( {value:"Новая категория", id:next_id, alias:"", url:"", title_tag:"", description_tag:"", keywords_tag:"", robots_tag:"", import_format:"", export_format:"", image_url:"", published_flag:1, properties:[{value:"Артикул", category_id:next_id, property_type_id:3, just_created:1, list_id:0, for_similar:0, is_option:0},{value:"Производитель", category_id:next_id, property_type_id:5, just_created:1, list_id:10, for_similar:0, is_option:0}], image:''}, 0, parentId);//Добавляем новый узел и запоминаем его ID
		}
		else
		{
			//Добавляем категорию без свойств
			newItemId = tree.add( {value:"Новая категория", id:next_id, alias:"", url:"", title_tag:"", description_tag:"", keywords_tag:"", robots_tag:"", import_format:"", export_format:"", image_url:"", published_flag:1, image:'', properties:[]}, 0, parentId);//Добавляем новый узел и запоминаем его ID
		}
    	
    	//Добавляем поле для изображения в форму:
    	var input_file = document.createElement("input");
        input_file.setAttribute("type","file");
        input_file.setAttribute("name","img_"+next_id);
        input_file.setAttribute("id","img_"+next_id);
        input_file.setAttribute("accept","image/jpeg,image/jpg,image/png,image/gif");
        input_file.setAttribute("onchange","onFileChanged();");
        document.getElementById('img_box').appendChild(input_file);
    	
    	onSelected();//Обработка текущего выделения
    	next_id++;//Следующий ID материала
    	tree.open(parentId);//Раскрываем родительский узел
    	
    	/*
    	Принцип работы с изображениями.
    	Категория содержит поле image_url, которое используется исключительно для отображения пиктограммы
    	Кроме этого, в форме сохранения есть блок с элементами input[type=file], которые используются для сохранения изображений на сервере.
    	
    	Если при сохранении, для категории не задано значение в input, то для этой категории измений изображений не происходит.
    	Если значение задано, то происходит сохранение изображения на сервере
    	
    	*/
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
        var tree_In_JSON = tree.serialize();//Получаем JSON-представление дерева
        
        //Проверяем отсутствие совпадений атрибутов alias в каждой ветви на одном уровне
        if( ! detectDuplicatedAlias(tree_In_JSON) )
        {
            webix.alert({
                title: "Ошибка",
                text: "Атрибуты alias должны быть уникальными в рамках одного уровня одной ветви",
                type:"confirm-error"
            });
            return false;
        }
        
        
        //Проверка пустых значений атрибутов alias
        if( ! detectEmptyAlias(tree_In_JSON))//Передаем JSON представление дерева в рекурсивный метод проверки атрибутов
        {
            webix.alert({
                title: "Ошибка",
                text: "Необходимо заполнить все атрибуты alias созданных категорий",
                type:"confirm-error"
            });
            return false;
        }
    
        
        //webix.message("Ok")
        //return;
        
    
    	//Получаем строку JSON:
    	var tree_json_to_save = tree.serialize();
    	tree_dump = JSON.stringify(tree_json_to_save);
    	
    	//Задаем значение поля в форме:
    	var tree_json_input = document.getElementById("tree_json");
    	tree_json_input.value = tree_dump;
    	
    	document.forms["form_to_save"].submit();//Отправляем
    }
    //-----------------------------------------------------
    //Рекурсивный метод проверки повторяющихся значений атрибута alias
    function detectDuplicatedAlias(level_array)
    {
        for(var i=0; i<level_array.length; i++)
        {
            if(level_array[i]["alias"] == "") continue;//Пустой не интересует - он будет выявлен далее
            
            var node = tree.getItem(level_array[i]["id"]);//Получаем объект узла дерева
            if(isAliasRepeated(node.$parent, node.alias, node.id))
            {
                return false;//Если метод вернул false - дальше проверять нет смысла - выходим
            }
            
            //Рекурсивный вызов для вложенного уровня
            if(level_array[i]['$count'] > 0)
            {
                if(detectDuplicatedAlias(level_array[i]["data"]) == false)
                {
                    return false;//Если метод вернул false - дальше проверять нет смысла - выходим
                }
            }
        }
        
        return true;
    }
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
    //Рекурсивный метод проверки на 
    function detectEmptyAlias(level_array)
    {
        for(var i=0; i<level_array.length; i++)
        {
            //Проверяем атрибуты данного узла
            //Если здесь выявлено не полное заполнение, то сразу return false;
            //1. Проверяем Alias
            if(level_array[i]["alias"] == "") return false;
            
            //Здесь можно поставить полный URL для данной категории (узла), т.к. она сама и элементы из е ветви всех уровней выше нее прошли проверку
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
                if(detectEmptyAlias(level_array[i]["data"]) == false)
                {
                    return false;//Если метод вернул false - дальше проверять нет смысла - выходим
                }
            }
        }
        
        return true;
    }//~function detectEmptyAlias(level_array)
    //-----------------------------------------------------
    //Рекурсивный метод инициализации полей image_url для каждой категории после загрузки страницы
    function img_box_start_init(level_array)
    {
        for(var i=0; i<level_array.length; i++)
        {
            //Добавляем input - он пустой в любом случае, даже если изображение было добавлено при последнем редактировании
            var input_file = document.createElement("input");
            input_file.setAttribute("type","file");
            input_file.setAttribute("name","img_"+level_array[i]["id"]);
            input_file.setAttribute("id","img_"+level_array[i]["id"]);
            input_file.setAttribute("accept","image/jpeg,image/jpg,image/png,image/gif");
            input_file.setAttribute("onchange","onFileChanged();");
            document.getElementById('img_box').appendChild(input_file);
            
                      
            //Инициализируем image_url - будет использоваться скрипт для получения изображений каталога
            //level_array[i]["image_url"] = "<?php echo $DP_Config->domain_path; ?>content/shop/catalogue/get_category_image.php?id="+level_array[i]["id"];
            level_array[i]["image_url"] = "<?php echo $DP_Config->domain_path; ?>content/files/images/catalogue_images/"+level_array[i]["image"];
            
            //Рекурсивный вызов для вложенного уровня
            if(level_array[i]['$count'] > 0)
            {
                img_box_start_init(level_array[i]["data"]);
            }
        }
    }//~function img_box_start_init(level_array)
    //-----------------------------------------------------
    
    //Инициализация редактора дерева материалов после загруки страницы
    function catalogue_start_init()
    {
    	var saved_catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
    	img_box_start_init(saved_catalogue);//Инициализируем изображения для категорий
	    tree.parse(saved_catalogue);
	    tree.openAll();
    }
    catalogue_start_init();
    onSelected();//Обработка текущего выделения
    </script>
    
    
    
    
    
    
	
	
	
	
	<!-- START НОВОЕ МОДАЛЬНОЕ ОКНО -->
	<!-- START НОВОЕ МОДАЛЬНОЕ ОКНО -->
	<div class="text-center m-b-md">
		<div class="modal fade" id="modalPropertiesOfCategory" tabindex="-1" role="dialog"  aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="color-line"></div>
					<div class="modal-header">
						<h4 class="modal-title">Настройка свойств категории</h4>
					</div>
					<div class="modal-body">
						
						<div class="row">
							<div class="col-lg-12">
								<div class="hpanel">
									<div class="panel-heading hbuilt">
										Добавить свойство
									</div>
									<div class="panel-body text-center float-e-margins">
									
										<button type="button" class="btn w-xs btn-primary2" onclick="add_int_property();">Целое число</button>
									
										<button type="button" class="btn w-xs btn-info" onclick="add_float_property();">Число с точкой</button>
										
										<button type="button" class="btn w-xs btn-success" onclick="add_text_property();">Текст</button>
										
										<button type="button" class="btn w-xs btn-warning" onclick="add_bool_property();">Признак Да/Нет</button>
										
										<button type="button" class="btn w-xs btn-danger" onclick="add_list_property();">Линейный список</button>
									
										<button type="button" class="btn w-xs btn-primary" onclick="add_tree_list_property();">Древовидный список</button>
									
									</div>

								</div>
							</div>
						</div>
						
						<div class="row">
							<div class="col-lg-6">
								<div class="hpanel">
									<div class="panel-heading hbuilt">
										Список свойств
									</div>
									<div class="panel-body">
										<div id="container_B" style="height:150px;"></div>
									</div>
								</div>
							</div>
							
							
							<div class="col-lg-6" id="list_selector_div_container">
								<div class="hpanel">
									<div class="panel-heading hbuilt">
										Параметры выбранного свойства
									</div>
									<div class="panel-body">
										<div id="list_selector_div">
										</div>
									</div>
								</div>
							</div>
						</div>
						
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>
						<button type="button" class="btn btn-success" onclick="apply_properties(0);">Применить</button>
						<button type="button" class="btn btn-success" onclick="apply_properties(1);">Применить и закрыть</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- END НОВОЕ МОДАЛЬНОЕ ОКНО -->
	<!-- END НОВОЕ МОДАЛЬНОЕ ОКНО -->
	
	
	

    <script>
		var is_changes = false;//Флаг - при работе со свойствами катагории - есть несохраненные изменения
		
		//Событие при закрытии окна
		$('#modalPropertiesOfCategory').on('hide.bs.modal',function(){
			if(is_changes)
			{
				if( confirm("Несохраненные изменения будут потеряны. Закрыть окно настроек?")  )
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			
			return true;
		});
		
		//После отображения окна - подгоняем дерево под размер
		$('#modalPropertiesOfCategory').on('shown.bs.modal',function(){
			properties_tree.adjust();
		});
        // ----------------------------------------------------------------
        var properties_tree = "";//Переменная для дерева свойств
        // ----------------------------------------------------------------
        //Открыть модальное окно для настроек свойств категории
        function open_properties_window()
        {
			is_changes = false;//Окно только открываем - изменений свойств еще нет
			
            //Выделенный узел
        	var node_id = tree.getSelectedId();//ID выделенного узла
        	if(node_id == 0)
        	{
        	    alert("Выберите категорию");
        	    return;
        	}
        	
        	var node = "";//Ссылка на объект узла
        	//Выделенный узел
        	node = tree.getItem(node_id);
        	
            if(node.$count > 0)
            {
                alert("Данная категория не является конечной");
        	    return;
            }
            
            //Предварительно очищаем окно
            var container_B = document.getElementById("container_B");
            container_B.innerHTML = "";
            
            $('#modalPropertiesOfCategory').modal();//ОТКРЫВАЕМ ОКНО
            
        	/**Инициализируем дерево со свойствами категории*/
        	//Для редактируемости дерева
            webix.protoUI({
                name:"edittree"
            }, webix.EditAbility, webix.ui.tree);
            //Формирование дерева
            properties_tree = new webix.ui({
				
				//Шаблон элемента дерева
				template:function(obj, common)//Шаблон узла дерева
					{
						
						var folder = common.folder(obj, common);
						var icon = "";
						var value_text = "<span>" + obj.value + "</span>";//Вывод текста
						
						
						//В зависимости от типа свойства - обозначаем
						switch( parseInt(obj.property_type_id) )
						{
							case 1:
								value_text = "<span>" + obj.value + " <b>(Целое число)</b></span>";//Вывод текста
								break;
							case 2:
								value_text = "<span>" + obj.value + " <b>(Число с точкой)</b></span>";//Вывод текста
								break;
							case 3:
								value_text = "<span>" + obj.value + " <b>(Текст)</b></span>";//Вывод текста
								break;
							case 4:
								value_text = "<span>" + obj.value + " <b>(Признак Да/Нет)</b></span>";//Вывод текста
								break;
							case 5:
								value_text = "<span>" + obj.value + " <b>(Линейный список)</b></span>";//Вывод текста
								break;
							case 6:
								value_text = "<span>" + obj.value + " <b>(Древовидный список)</b></span>";//Вывод текста
								break;
						}
						
						return common.icon(obj, common) + icon + folder + value_text;
						
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
			webix.event(window, "resize", function(){ properties_tree.adjust(); });
            /*~ДЕРЕВО*/
            //-----------------------------------------------------
            webix.protoUI({
                name:"editlist" // or "edittree", "dataview-edit" in case you work with them
            }, webix.EditAbility, webix.ui.list);

    	    properties_tree.parse(node.properties);
    	    properties_tree.openAll();
    	    //-----------------------------------------------------
            //Событие при выборе элемента дерева
            properties_tree.attachEvent("onAfterSelect", function(id)
            {
            	onSelected_properties_tree();
            });
            onSelected_properties_tree();//Обрабатываем текущее выделение (его отсутствие)
        }
        // ----------------------------------------------------------------
        //Обработка выделения свойства
        function onSelected_properties_tree()
        {
            var list_selector_div = document.getElementById("list_selector_div");//Селектор
            
            var property_node_id = properties_tree.getSelectedId();//ID выделенного узла
            if(property_node_id == 0)
            {
                list_selector_div.innerHTML = "";
				document.getElementById("list_selector_div_container").setAttribute("style", "display:none;");
				return;
            }
            
			document.getElementById("list_selector_div_container").setAttribute("style", "display:block;");
			
            var property_node = "";//Ссылка на объект узла
        	//Выделенный узел
        	property_node = properties_tree.getItem(property_node_id);
            
			
			//Обозначение свойства справа (название и тип)
			var property_info_text = "";
			
			property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Название</label><div class=\"col-lg-6\">"+property_node.value+"</div></div>";
			
			property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			var for_similar_current_state = "";
			if(property_node.for_similar == 1)
			{
				for_similar_current_state = "checked";
			}
			property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Учитывать для похожих товаров</label><div class=\"col-lg-6\"><input onchange=\"setForSimilar();\" type=\"checkbox\" class=\"form-control\" id=\"for_similar_input\" "+for_similar_current_state+" /></div></div>";
			
			
			
			
			property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			var is_option_current_state = "";
			if(property_node.is_option == 1)
			{
				is_option_current_state = "checked";
			}
			property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Вариант исполнения</label><div class=\"col-lg-6\"><input onchange=\"setIsOption();\" type=\"checkbox\" class=\"form-control\" id=\"is_option_input\" "+is_option_current_state+" /></div></div>";
			
			
			
			
			property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
			
			property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Тип</label><div class=\"col-lg-6\">";
			
			switch( parseInt(property_node.property_type_id) )
			{
				case 1:
					property_info_text += "Целое число";
					break;
				case 2:
					property_info_text += "Число с точкой";
					break;
				case 3:
					property_info_text += "Текстовая строка";
					break;
				case 4:
					property_info_text += "Признак Да/Нет";
					break;
				case 5:
					property_info_text += "Линейный список";
					break;
				case 6:
					property_info_text += "Древовидный список";
					break;
			}
			property_info_text += "</div></div>";
			

            //Если это свойство - линейный список
            if(property_node.property_type_id == 5)
            {
				property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
				
				property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Линейный список</label><div class=\"col-lg-6\">";
				
                property_info_text += "<select class=\"form-control\" id=\"list_selector\" onchange=\"setListId();\">";
                property_info_text += "<option value=\"0\">Не выбран</option>";
                <?php
				$line_lists_query = $db_link->prepare("SELECT * FROM `shop_line_lists`");
				$line_lists_query->execute();
                while( $line_list_record = $line_lists_query->fetch() )
                {
                    ?>
                    property_info_text += "<option value=\"<?php echo $line_list_record["id"]; ?>\"><?php echo str_replace('"', '\"',$line_list_record["caption"]); ?></option>";
                    <?php
                }
                ?>
                property_info_text += "</select></div></div>";
				
				//Кнопка Удалить свойство
				property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
				property_info_text += "<div class=\"col-lg-12 text-center\"><button type=\"button\" class=\"btn w-xs btn-danger2\" onclick=\"delete_property();\">Удалить свойство</button></div>";
				
				
                list_selector_div.innerHTML = property_info_text;//Показали заполненный селектор
                
                //Выбираем текущее значение:
                document.getElementById("list_selector").value = property_node.list_id;
            }
			else if(property_node.property_type_id == 6)//Если это древовидный список
            {
				property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
				
				property_info_text += "<div class=\"form-group\"><label for=\"\" class=\"col-lg-6 control-label\">Древовидный список</label><div class=\"col-lg-6\">";
				
                property_info_text += "<select class=\"form-control\" id=\"list_selector\" onchange=\"setListId();\">";
                property_info_text += "<option value=\"0\">Не выбран</option>";
                <?php
				$tree_lists_query = $db_link->prepare("SELECT * FROM `shop_tree_lists`");
				$tree_lists_query->execute();
                while( $tree_list_record = $tree_lists_query->fetch() )
                {
                    ?>
                    property_info_text += "<option value=\"<?php echo $tree_list_record["id"]; ?>\"><?php echo str_replace('"', '\"',$tree_list_record["caption"]); ?></option>";
                    <?php
                }
                ?>
                property_info_text += "</select></div></div>";
				
				//Кнопка Удалить свойство
				property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
				property_info_text += "<div class=\"col-lg-12 text-center\"><button type=\"button\" class=\"btn w-xs btn-danger2\" onclick=\"delete_property();\">Удалить свойство</button></div>";
				
				
                list_selector_div.innerHTML = property_info_text;//Показали заполненный селектор
                
                //Выбираем текущее значение:
                document.getElementById("list_selector").value = property_node.list_id;
            }
            else
            {
				//Кнопка Удалить свойство
				property_info_text += "<div class=\"hr-line-dashed col-lg-12\"></div>";//РАЗДЕЛИТЕЛЬ-----
				property_info_text += "<div class=\"col-lg-12 text-center\"><button type=\"button\" class=\"btn w-xs btn-danger2\" onclick=\"delete_property();\">Удалить свойство</button></div>";
				
                list_selector_div.innerHTML = property_info_text;
            }
        }
        // ----------------------------------------------------------------
        //Функция добавления свойства "Целое число"
        function add_int_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Целое число", category_id:node_id, property_type_id:1, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Функция добавления свойства "Число с точкой"
        function add_float_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Число с точкой", category_id:node_id, property_type_id:2, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Функция добавления свойства "Строка"
        function add_text_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Строка", category_id:node_id, property_type_id:3, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Функция добавления свойства "Флаг"
        function add_bool_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Флаг", category_id:node_id, property_type_id:4, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Функция добавления свойства "Линейный список"
        function add_list_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Линейный список", category_id:node_id, property_type_id:5, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
		// ----------------------------------------------------------------
        //Функция добавления свойства "Древовидный список"
        function add_tree_list_property()
        {
            var node_id = tree.getSelectedId();//ID выделенного узла (ID категории)
        	properties_tree.add( {value:"Древовидный список", category_id:node_id, property_type_id:6, just_created:1, list_id:0, for_similar:0, is_option:0}, properties_tree.count(), 0);//Добавляем новый узел и запоминаем его ID
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Удаление свойства
        function delete_property()
        {
            var property_nodeId = properties_tree.getSelectedId();
        	properties_tree.remove(property_nodeId);
        	onSelected_properties_tree();
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
        //Запомнить list_id при переключении селектора
        function setListId()
        {
            var property_node_id = properties_tree.getSelectedId();//ID выделенного узла (ID категории)
            
            var property_node = "";//Ссылка на объект узла
        	//Выделенный узел
        	property_node = properties_tree.getItem(property_node_id);
        	
        	property_node.list_id = document.getElementById("list_selector").value;
			
			is_changes = true;//Ставим флаг - Есть изменения
        }
        // ----------------------------------------------------------------
		//Запомнить настройку for_similar для свойства (Учитывать для похожих товаров)
		function setForSimilar()
		{
			var property_node_id = properties_tree.getSelectedId();//ID выделенного узла (ID категории)
			
			var property_node = "";//Ссылка на объект узла
        	//Выделенный узел
        	property_node = properties_tree.getItem(property_node_id);
			
			if(document.getElementById("for_similar_input").checked == true)
			{
				property_node.for_similar = 1;
			}
			else
			{
				property_node.for_similar = 0;
			}
			
			
			is_changes = true;//Ставим флаг - Есть изменения
		}
		// ----------------------------------------------------------------
		//Запомнить настройку is_option для свойства (Флаг - является вариантом исполнения)
		function setIsOption()
		{
			var property_node_id = properties_tree.getSelectedId();//ID выделенного узла (ID категории)
			
			var property_node = "";//Ссылка на объект узла
        	//Выделенный узел
        	property_node = properties_tree.getItem(property_node_id);
			
			if(document.getElementById("is_option_input").checked == true)
			{
				property_node.is_option = 1;
			}
			else
			{
				property_node.is_option = 0;
			}
			
			
			is_changes = true;//Ставим флаг - Есть изменения
		}
		// ----------------------------------------------------------------
        //Применить настройки свойств для категории
        function apply_properties(close_after)
        {
            //Выделенный узел
        	var node_id = tree.getSelectedId();//ID выделенного узла
        	var node = "";//Ссылка на объект узла
        	//Выделенный узел
        	node = tree.getItem(node_id);
        	
        	//Дамп дерева свойств для категории:
        	var properties_json = properties_tree.serialize();//Получаем JSON-представление дерева
        	
			if(properties_json.length > 0){
				for (let property in properties_json) {
					if((properties_json[property]['property_type_id'] == '5' || properties_json[property]['property_type_id'] == '6') && properties_json[property]['list_id'] == '0'){
						alert('Ошибка. В свойстве '+properties_json[property]['value']+' не выбран конкретный список.');
						return false;
					}
				}
			}
			
        	node.properties = properties_json;//Сохраняем дерево свойств в объект узла категории
        	
			is_changes = false;//Ставим флаг - Нет изменений, т.к. мы только что их сохранили
			
			if(close_after)
			{
				$('#modalPropertiesOfCategory').modal('hide');
			}
        }
        // ----------------------------------------------------------------
    </script>
    <!--Start Модальное окно: Настройка свойств категории -->
    
	
	
	
	
	
	
	
	
	
	
	
	
	
	<!-- START МОДАЛЬНОЕ ОКНО - Шаблоны категорий -->
	<div class="text-center m-b-md">
		<div class="modal fade" id="modalCategoriesTemplates" tabindex="-1" role="dialog"  aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="color-line"></div>
					<div class="modal-header">
						<h4 class="modal-title">Шаблоны категорий</h4>
					</div>
					<div class="modal-body" id="modalCategoriesTemplates_workArea">
						
						Рабочая область
						
					</div>
					<div class="modal-footer">
						<button id="templates_window_close_button" type="button" class="btn btn-primary" data-dismiss="modal"><i class="fas fa-times"></i> Закрыть</button>
						<button type="button" id="delete_template_button" class="btn btn-danger" onclick="delete_category_template();"><i class="far fa-trash-alt"></i> Удалить</button>
						<button type="button" id="copy_template_button" class="btn btn-success" onclick="template_to_buffer();"><i class="far fa-copy"></i> В буфер</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Здесь хранится пустой файловый инпут шаблона, добавляемого в буфер для копирования -->
	<div style="display:none;" id="template_input_container">
	</div>
	<script>
	var backend_dir = '<?php echo $DP_Config->backend_dir; ?>';
	</script>
	<script src="/<?php echo $DP_Config->backend_dir; ?>/content/shop/catalogue/categories_templates/categories_templates.js"></script>
	<!-- END МОДАЛЬНОЕ ОКНО - Шаблоны категорий -->
	
	
	
	
	
	
	
	
	

    
    <?php
}//~else//Если действий нет - выводим страницу
?>