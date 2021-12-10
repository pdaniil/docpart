<?php
/**
Страничный скрипт для страницы экспорта каталога в xml
*/
defined('_ASTEXE_') or die('No access');
?>

<link rel="stylesheet" href="/lib/webix/codebase/webix.css" type="text/css" />
<script src="/lib/webix/codebase/webix.js" type="text/javascript"></script>
<link rel="stylesheet" href="/<backend_dir>/templates/<template_dir>/css/control/control.css" type="text/css" />
<script src="/lib/iso_9_js_master_translit/translit.js" type="text/javascript"></script>

<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Действия
		</div>
		<div class="panel-body">
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/shop/perenos-dannyx">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/xml.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Функции переноса данных</div>
			</a>
		
		
		
			<a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>">
				<div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/power_off.png') 0 0 no-repeat;"></div>
				<div class="panel_a_caption">Выход</div>
			</a>
		</div>
	</div>
</div>






<div class="col-lg-12">
	<div class="hpanel">
		<div class="panel-heading hbuilt">
			Настройки
		</div>
		<div class="panel-body">
			<div class="col-lg-12 text-center"><h3>Настройки формирования данных</h3></div>
			
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Магазины
				</label>
				<div class="col-lg-6">
					<select multiple="multiple" id="offices">
						<?php
						$offices_query = $db_link->prepare("SELECT * FROM `shop_offices`");
						$offices_query->execute();
						while( $office = $offices_query->fetch() )
						{
							?>
							<option value="<?php echo $office["id"]; ?>"><?php echo $office["caption"]." (ID ".$office["id"].")"; ?></option>
							<?php
						}
						?>
					</select>
					<script>
						//Делаем из селектора виджет с чекбоками
						$('#offices').multipleSelect({placeholder: "Нажмите для выбора...", width:"100%"});
						
						$("#offices").multipleSelect('checkAll');
					</script>
				</div>
			</div>
			
			<div class="hr-line-dashed col-lg-12"></div>
			
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Группа пользователей (для наценки) <button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Нужно выбрать конкретную группу пользователей от которой будут браться наценки, например группа Посетители');"><i class="fa fa-info"></i></button>
				</label>
				<div class="col-lg-6">
					<select id="groups" class="form-control">
						<?php
						$groups_query = $db_link->prepare("SELECT * FROM groups;");
						$groups_query->execute();
						while( $group = $groups_query->fetch() )
						{
							?>
							<option value="<?php echo $group["id"]; ?>"><?php echo $group["value"]; ?> (ID <?php echo $group["id"]; ?>)</option>
							<?php
						}
						?>
					</select>
				</div>
			</div>
			
			
			
			<div class="col-lg-12 text-center"><h3>Настройки выгрузки данных</h3></div>
			
			<div class="form-group">
				<label for="" class="col-lg-6 control-label">
					Способ выгрузки данных
				</label>
				<div class="col-lg-6">
					<select id="data_output_mode" class="form-control">
						<option value="create_file">Создать файл во временной папке</option>
						<option value="download_file">Скачать в виде файла XML</option>
						<option value="open_file_browser">Открыть в отдельной вкладке браузера</option>
					</select>
				</div>
				<div class="col-lg-12">
				<label class="control-label">Важно:</label>
				<br>
				Адрес файла при создании его во временной папке: <?=$DP_Config->domain_path.$DP_Config->backend_dir;?>/tmp/yml_dump.xml
				<br>
				Перед выгрузкой данных каталога необходимо настроить курсы валют в разделе Панель управления -> Настройка курсов валют. Либо отключить не используемые валюты.
				</div>
			</div>
			
			
			
			<?php
			require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");//Определение класса записи категории
			require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix
			?>

			
			<div class="hr-line-dashed col-lg-12"></div>
			<div class="col-lg-12 text-center"><h3>Выберете категории товаров</h3></div>
			
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt">
					Категории
					<button class="btn btn-xs btn-info btn-circle" type="button" onclick="show_hint('Товары без цены и количества, а так же принадлежащие неопубликованной категории или категории которая является подкатегорией неопубликованной категории, либо не опубликованные товары в выгрузку YML-файла НЕ попадут');"><i class="fa fa-info"></i></button>
					</div>
					<div class="panel-body">
						
						<div>
							<div style="padding:0 0 10px 0;">
							<button onclick="catalogue_tree.checkAll();" class="btn w-xs btn-success">Отметить все</button>
							<button onclick="catalogue_tree.uncheckAll();" class="btn w-xs btn-primary2">Снять все</button>
							</div>
						</div>
						<div id="container_A" style="height:350px;"></div>
						
						<div style="padding:15px 0px;" class="hidden">
							<label for="">Склад: </label>
							<select id="storages" name="storages" class="form-control" style="display:inline-block; width: auto;">
							<?php
								$storages_query = $db_link->prepare("SELECT * FROM `shop_storages`");
								$storages_query->execute();
								while( $storages = $storages_query->fetch() )
								{
									if((int)$storages['interface_type'] === 1){
										$arr_users = json_decode($storages['users']);
										foreach($arr_users as $id_user)
										{
											if((int)$id_user === (int)$admin_id)
											{
												?>
												<option value="<?php echo $storages["id"]; ?>"><?php echo $storages["name"]." (ID ".$storages["id"].")"; ?></option>
												<?php
											}
										}
									}
								}
							?>
							</select> 
						</div>
					</div>
				</div>
			</div>
			<script>
			//Массив ID не опубликованных категорий с их вложенными подкатегориями что бы не выводить у них возможность выбора
			var no_published = new Array();
			
			/*ДЕРЕВО КАТАЛОГА ТОВАРОВ*/
			//Для редактируемости дерева
			webix.protoUI({
				name:"edittree"
			}, webix.EditAbility, webix.ui.tree);
			//Формирование дерева
			catalogue_tree = new webix.ui({
				editable:false,//не редактируемое
				container:"container_A",//id блока div для дерева
				view:"tree",
				select:false,//можно выделять элементы
				drag:false,//можно переносить
				//Шаблон элемента дерева
				template:function(obj, common)//Шаблон узла дерева
					{
						var folder = common.folder(obj, common);
						var value_text = "<span>" + obj.value + "</span>";//Вывод текста
						var checkbox = common.checkbox(obj, common);
						
						//Индикация материала, снятого с публикации
						var icon_system = "";
						if(no_published.indexOf(obj.$parent) !== -1){
							no_published.push(obj.id);//Добавляем ID в массив
							value_text = "<span title=\"Категория находится внутри неопубликованной  категории\" style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
							checkbox = '';
						}
						if(obj.published_flag == false)
						{
							console.log(obj);
							
							no_published.push(obj.id);//Добавляем ID в массив
							
							icon_system += "<img src='/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/lock.png' class='col_img' style='float:right; margin:0px 4px 8px 4px;'>";
							value_text = "<span title=\"Категория не опубликована\" style=\"color:#AAA\">" + obj.value + "</span>";//Вывод текста
							checkbox = '';
						}
						
						
						return common.icon(obj, common) + checkbox + folder + icon_system + value_text;
					},//~template
			});
			webix.event(window, "resize", function(){ catalogue_tree.adjust(); });

			var saved_catalogue = <?php echo $catalogue_tree_dump_JSON; ?>;
			catalogue_tree.parse(saved_catalogue);
			catalogue_tree.openAll();
			/*~ДЕРЕВО*/
			
			catalogue_tree.attachEvent("onItemCheck", function(id){
				onTreeListItemCheck(id);
			});
			
			</script>
			
			<script>
			// ---------------------------------------------------------------------------------------------
			//ОБРАБОТКА выделения вложенных элементов
			var auto_check = false;//Для предотвращения обработки программного выставления чекбоксов (семафор)
			function onTreeListItemCheck(item_id)
			{
				if(auto_check)
				{
					return;
				}
				
				//Получаем состояние отмеченного элемента:
				var is_checked = eval("catalogue_tree").isChecked(item_id);
				
				//Массив вложенных элементов
				var childItems = getChildItems(item_id);
				
				auto_check = true;//Начинаем обработку чекбоксов
				
				
				//Далее логика
				if( is_checked )
				{
					
					//Выставляются все элементы, вложенные в него (рекурсивно, т.е. до упора), а также, отмечаются все элементы, находящиеся выше него по цепочке - до самого верхнего
					//Обработка вложенных элементов
					var childItems = getChildItems(item_id);
					for(var i=0; i < childItems.length; i++)
					{
						eval("catalogue_tree").checkItem( childItems[i] );
					}
					
					//Обработка элементов родительской ветви
					var parent_brunch = getUpperBrunch(item_id);
					for(var i=0; i < parent_brunch.length; i++)
					{
						eval("catalogue_tree").checkItem( parent_brunch[i] );
					}
				}
				else
				{
					if( eval("catalogue_tree").getItem(item_id).$count != eval("catalogue_tree").getItem(item_id).webix_kids && eval("catalogue_tree").getItem(item_id).webix_kids != undefined )
					{
						webix.message("В узле " + eval("catalogue_tree").getItem(item_id).value + " ("+item_id+") не загружены вложенные узлы. Раскройте узел для управления вложенными узлами");
					}

					
					//Снимаются все элементы, вложенные в него (рекурсивно, т.е. до упора). При этом, элементы, находящиеся выше, остаются отмеченными
					//Обработка вложенных элементов
					var childItems = getChildItems(item_id);
					for(var i=0; i < childItems.length; i++)
					{
						eval("catalogue_tree").uncheckItem( childItems[i] );
						
						if( eval("catalogue_tree").getItem(childItems[i]).$count != eval("catalogue_tree").getItem(childItems[i]).webix_kids && eval("catalogue_tree").getItem(childItems[i]).webix_kids != undefined )
						{
							webix.message("В узле " + eval("catalogue_tree").getItem(childItems[i]).value + " ("+childItems[i]+") не загружены вложенные узлы. Раскройте узел для управления вложенными узлами");
						}
					}
				}
				auto_check = false;//Прекращаем обработку чекбоксов
			}
			// ---------------------------------------------------------------------------------------------
			//Рекурсивная функция получения всех вложенных элементов указанного узла дерева
			function getChildItems(item_id)
			{
				var childItems = new Array();//Массив вложенных элеметов

				var first = true;
				var nextItem = undefined;
				
				while(true)
				{
					if(first)
					{
						nextItem = eval("catalogue_tree").getFirstChildId( item_id );//Первый вложенный элемент
						
						first = false;
					}
					else
					{
						nextItem = eval("catalogue_tree").getNextSiblingId( nextItem );//Следующий вложенный элемент
					}
					
					
					if( nextItem == null ){break;}
					childItems.push(nextItem);//Добавляем первый вложенный элемент в массив
					
					
					if( eval("catalogue_tree").getFirstChildId( nextItem ) != null )
					{	
						childItems = childItems.concat(getChildItems(nextItem));
					}
				}
				
				return childItems;
			}
			// ---------------------------------------------------------------------------------------------
			//Рекурсивная функция получения всей родительской ветви к верху дерева
			function getUpperBrunch(item_id)
			{
				var parent_brunch = new Array();//Массив ветви
				
				var parent_id = eval("catalogue_tree").getParentId(item_id);//ID родительского узла
				
				//console.log(parent_id);
				
				if(parent_id != 0)
				{
					parent_brunch.push(parent_id);
					
					parent_brunch = parent_brunch.concat(getUpperBrunch(parent_id));
				}
				
				return parent_brunch;
			}
			// ---------------------------------------------------------------------------------------------
			</script>
			
		</div>
		<div class="panel-footer">
			<div class="row">
				<div class="col-lg-12">
					<button onclick="exec_export();" class="btn btn-success " type="button"><i class="fa fa-download"></i> <span class="bold">Выгрузить</span></button>
					<span id="ink" style="display:none;"><img style="height: 31px; margin-right: 5px;" src="/content/files/images/ajax-loader-transparent.gif"/> Подождите, идет формирование файла</span>
				</div>
			</div>
		</div>
	</div>
</div>






<a href="" id="a_download" target="_blank" download></a>
<a href="" id="a_open_tab" target="_blank"></a>

<script>
var flag = false;
//Функция запроса на экспорт
function exec_export()
{	
	if(flag){
		return false;
	}
	
	var request = new Object;
	request.data_output_mode = document.getElementById("data_output_mode").value;
	request.offices = [].concat( $("#offices").multipleSelect('getSelects') );
	request.group_id = document.getElementById("groups").value;
	/*
	request.output_format = document.getElementById("output_format").value;
	request.output_products_text = document.getElementById("output_products_text").checked;
	request.output_products_images = document.getElementById("output_products_images").checked;
	request.output_products_suggestions = document.getElementById("output_products_suggestions").checked;
	
    
	*/

	var arr_category = catalogue_tree.getChecked();
	if(arr_category.length <= 0){
		alert('Выберете категории');
		flag = false;
		return false;
	}
	request.arr_category = arr_category;
	
	flag = true;
	document.getElementById('ink').style.display = 'inline-block';
	
	jQuery.ajax({
		type: "GET",
		async: true, //Запрос синхронный
		url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/data_transfer/ajax/ajax_export_to_yml.php",
		dataType: "json",//Тип возвращаемого значения
		data: "export_options="+encodeURI(JSON.stringify(request)),
		success: function(answer)
		{
			flag = false;
			document.getElementById('ink').style.display = 'none';
			console.log(answer);
			if(answer.status == true)
			{
				if(document.getElementById("data_output_mode").value == "create_file")
				{
					alert("Файл создан во временной папке");
				}
				else if(document.getElementById("data_output_mode").value == "download_file")
				{
					document.getElementById("a_download").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_download").click();
				}
				else if(document.getElementById("data_output_mode").value == "open_file_browser")
				{
					document.getElementById("a_open_tab").setAttribute("href", '/<?php echo $DP_Config->backend_dir; ?>/tmp/'+answer.filename);
					document.getElementById("a_open_tab").click();
				}
			}
			else
			{
				alert("Ошибка");
			}
		},
		error: function (e, ajaxOptions, thrownError){
			document.getElementById('ink').style.display = 'none';
			flag = false;
			alert('Ошибка: '+ e.status +' - '+ thrownError);
		}
	});
}
</script>