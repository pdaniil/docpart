<?php
/**
 * Скрипт для вывода товаров каталога в основную область страницы. Вариант с пагинацией.
 * Этот вариант используем только для режимов 1 и 4. При этом, сохраняем совместимость для режимов 2 и 3 (возможно - пригодится).
 * 
 * В зависимости от типа параметра $product_block_type страница может выводить блоки категорий для следующих целей
 * 1 - Отображение для покупателей
 * 2 - Отображения для администратора каталога (при редактировании справочников товаров)
 * 3 - Отображение для кладовщика - для управления наличием товара
 * 
 * 4 - Отображение для покупателей - обращение через поисковую строку
*/
defined('_ASTEXE_') or die('No access');




//ДЛЯ РАБОТЫ С ПОЛЬЗОВАТЕЛЕМ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$userProfile = DP_User::getUserProfile();
$group_id = $userProfile["groups"][0];//Берем первую группу пользователя

//Если переход через специальный поиск
if( isset( $DP_Content->service_data["sp"] ) )
{
	DP_User::set_user_option("propucts_request_".$category_id, 'null');
}



//Если переход с url_filters (КРИТИЧНО К УДАЛЕНИЮ s или sort, т.е. если s или sort убрать из URL, то, работать будет без URL_Filters вообще). Выставляем флаг, который потом используется для инициализации кода, необходимого для работы с URL_Filters и сбрасываем текущие настройки фильтров категории в сессии
$is_with_url_filters = false;//Флаг - переход с url_filters
if( isset($_GET["s"]) && isset($_GET["sort"]) && $product_block_type == 1 )
{
	if( $_GET["s"] == 1 || $_GET["s"] == 2 || $_GET["s"] == 3 )
	{
		if( $_GET["sort"] == "price_asc" || $_GET["sort"] == "price_desc" || $_GET["sort"] == "name_asc" || $_GET["sort"] == "name_desc" )
		{
			DP_User::set_user_option("propucts_request_".$category_id, 'null');
			$is_with_url_filters = true;
		}
	}
}


function write_log_txt($string, $mode)
{
	$log = fopen("log.txt", $mode);
	fwrite($log, $string);
	fclose($log);
}

?>





<?php
//Выводим поисковую строку для бэкенда
if( $product_block_type == 2 || $product_block_type == 3 )
{
	?>
	<div class="col-lg-12">
		<div class="hpanel">
			<div class="panel-heading hbuilt">
				Фильтр по наименованию
			</div>
			<div class="panel-body">
				<div class="input-group">
					<input onchange="on_product_name_search_str_entered();" onkeyup="on_product_name_search_str_entered();" name="product_name_search_str" id="product_name_search_str" type="text" placeholder="Введите название товара для фильтра" class="form-control" />
					
					<span class="input-group-btn">
						<button onclick="on_name_filter_button();" class="btn btn-success " type="button"><i class="fa fa-filter"></i> <span class="bold">Отфильтровать</span></button>
					</span>
				</div>
			</div>
		</div>
	</div>
	<script>
	// ------------------------------------------------------------
	//Обработка нажатия на кнопку "Отфильтровать"
	function on_name_filter_button()
	{
		just_name_filter = true;//Флаг - нажата кнопка "Отфильровать" (по имени)
		
		productsCountRequest();
		
		//Если после productsCountRequest(), переменная just_name_filter не была выставлена в false. Т.е. нормальное выполнение функции.
		if(just_name_filter == true)
		{
			//Очичаем область товаров и показываем индикатор загрузки
			document.getElementById("products_area").innerHTML = "<div class=\"text-center\" id=\"start_loading_div\"><p>Пожалуйста, подождите...</p><img src=\"/content/files/images/ajax-loader-transparent.gif\" class=\"loading_img\" /></div>";
		}
	}
	// ------------------------------------------------------------
	//Обработка ввода (или изменения значения) в строку фильтра товаров по наименованию
	function on_product_name_search_str_entered()
	{
		//Подразумевается, что если пользователь меняет значение в строке фильтра, то текущий отображаемый результат становится не актуальны, поэтому, убираем блок "Показать еще"
		var div = document.getElementById("showAnother");
		if(div != null)
		{
			div.parentNode.removeChild(div);
		}
	}
	// ------------------------------------------------------------
	</script>
	<?php
}
?>







<div class="col-lg-12">
	<!-- НАСТРОКА ОТОБРАЖЕНИЯ ТОВАРОВ -->
	<div class="products_area_turning">

		<div class="showSort_name">Сортировка</div>
		<div class="showSort_wrap">
			<select id="sort_select" onchange="showResort();" class="form-control">
				<?php
				if($product_block_type == 1 || $product_block_type == 4)
				{
					?>
					<option value="price_asc">По цене - дешевле</option>
					<option value="price_desc">По цене - дороже</option>
					<?php
				}
				?>
				<option value="name_asc">По наименованию</option>
				<option value="name_desc">По наименованию - обратно</option>
			</select>
			<?php
			//URL_Filters - если в URL есть настройка сортировки
			if( $is_with_url_filters )
			{
				if( $_GET["sort"] == "price_asc" || $_GET["sort"] == "price_desc" || $_GET["sort"] == "name_asc" || $_GET["sort"] == "name_desc" )
				{
					?>
					<script>
					document.getElementById("sort_select").value = '<?php echo $_GET["sort"]; ?>';
					</script>
					<?php
				}
			}
			?>
		</div>


		<div class="showRestyle_name">Вид каталога</div>
		<div class="showRestyle_wrap">
			<div class="showRestyle" id="showRestyle_1" onclick="showRestyle(1);"></div>
			<div class="showRestyle" id="showRestyle_2" onclick="showRestyle(2);"></div>
			<div class="showRestyle" id="showRestyle_3" onclick="showRestyle(3);"></div>
		</div>
	</div>
</div>





<script>
	var just_start = true;//Только начинаем отображать страницу
	
	<?php
	//Для обоих вариантов поиска в бэкенде
	if( $product_block_type == 2 || $product_block_type == 3 )
	{
		?>
		var just_name_filter = false;//Флаг - была нажата кнопка фильтра по названию
		<?php
	}
	?>
	
	<?php
	//Если propucts_request уже записан в БД, то, оттуда его и берем
	$propucts_request = json_decode(DP_User::get_user_option_by_key("propucts_request_".$category_id), true);
	if( $propucts_request != NULL )
	{
		?>
		var propucts_request = <?php echo json_encode($propucts_request); ?>;
		
		//Выставляем индикатор сортировки
		if( typeof propucts_request.products_sort_mode !== 'undefined' )
		{
			document.getElementById("sort_select").value = propucts_request.products_sort_mode.field+"_"+propucts_request.products_sort_mode.asc_desc;
		}
		
		
		<?php
	}
	else//Создаем объект запроса
	{
		?>
		//Объект запроса товаров
		var propucts_request = new Object;
		propucts_request.category_id = <?php echo $category_id; ?>;
		propucts_request.productsPerPage = <?php echo $DP_Config->products_count_for_page; ?>;//Количество товаров на одну страницу
		propucts_request.countTotal = 0;//Хранение полного количества товаров по данным настройкам
		propucts_request.product_block_type = <?php echo $product_block_type; ?>;
		<?php
		
		//Если был запрос через поисковую строку
		if($product_block_type == 4)
		{
			?>
			propucts_request.search_string = "<?php echo trim(htmlspecialchars(strip_tags($_GET['search_string']))); ?>";
			
			<?php
			if(!isset($products_ids_str))
			{
				$products_ids_str = "";
			}
			if($products_ids_str === "")
			{
				$products_ids_str = "0";
			}
			?>
			propucts_request.products_ids_str = "<?php echo $products_ids_str; ?>";
			<?php
		}
	}
	//Установка стиля отображения товаров
	//Вид каталога из URL (URL_Filters) 
    if( $is_with_url_filters )
	{
		if( $_GET["s"] == 1 || $_GET["s"] == 2 || $_GET["s"] == 3 )
		{
			?>
			propucts_request.page_style = <?php echo (int)$_GET["s"]; ?>;
			
			//Запись в куки - чтобы при переходе по страницам вид оставался тем же, а не по умолчанию
			jQuery.ajax({
				type: "GET",
				async: true,
				url: "/content/shop/catalogue/set_cookie_products_style.php",
				dataType: "json",//Тип возвращаемого значения
				data: "products_style="+propucts_request.page_style,
				success: function(answer)
				{
				}
			 });
			
			<?php
		}
	}
	else if( isset($_COOKIE["products_style"]) )
	{
		?>
		propucts_request.page_style = <?php echo (int)$_COOKIE["products_style"]; ?>;
		<?php
	}
	else
	{
		?>
		propucts_request.page_style = 1;
		<?php
	}
	?>
</script>





<script>
// ----------------------------------------------------------------------------------------------------------
//Инициализация значений свойств
function initProperiesValues()
{
    for(var i=0; i < properties_list.length; i++)
    {
        switch( parseInt(properties_list[i].property_type_id) )
        {
            case 1:
            case 2:
                properties_list[i].min_need = jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 0 );
                properties_list[i].max_need = jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 1 );
                break;
            case 4:
                properties_list[i].true_checked = document.getElementById("checkbox_true_"+properties_list[i].property_id).checked;
                properties_list[i].false_checked = document.getElementById("checkbox_false_"+properties_list[i].property_id).checked;
                break;
            case 5:
                for(var o=0; o < properties_list[i].list_options.length; o++)
                {
                    properties_list[i].list_options[o].value = document.getElementById("list_"+properties_list[i].property_id+"_"+properties_list[i].list_options[o].id).checked;
                }
                break;
			case 6:
				//Для данного типа свойств, значения:
				//properties_list[i].current_level
				//properties_list[i].current_value
				//Указываются динамически сразу при работе с селектами в функции onTreeListSelectChange()
				break;
        }
    }
	
	
	<?php
	//Для бэкенда - добавляем стороку фильтра по наименованию
	if( $product_block_type == 2 || $product_block_type == 3 )
	{
		?>
		propucts_request.search_string = document.getElementById("product_name_search_str").value;
		<?php
	}
	?>
	
	
	propucts_request.properties_list = properties_list;//В объект запроса добавляем список свойств с инициализированными значениями
	
	
	filters_were_changed = true;//Флаг - Пользователь менял значение фильтров с момента загрузки страницы
}
// ----------------------------------------------------------------------------------------------------------
//Запрос количества продуктов, соответствующих указанным требованиям
function productsCountRequest()
{
    initProperiesValues();//Инициализируем список свойств выставленными значениями
	
	
	<?php
	//Для бэкенда - если ввели один только символ в поисковую строку
	if( $product_block_type == 2 || $product_block_type == 3 )
	{
		?>
		if( propucts_request.search_string.length == 1 )
		{
			//Убираем блок "Показать еще"
            var div = document.getElementById("showAnother");
            if(div != null)
            {
                div.parentNode.removeChild(div);
            }
			
			
			just_name_filter = false;//Убираем флаг "Нажата кнопка фильтра по имени"
			alert("В фильтре наименования товаров - один символ. Этого не достаточно для поиска");
			return;
		}
		<?php
	}
	?>
	
	
	
	if(popupID != undefined)//Если запрос количества был при использовании виджетов свойств - показываем указать количества
	{
		//Создаем код окна в соответствующем блоке свойства. popupID был инициализирован при изменении состояния виджета
		var productsCountBox_html = "<div id=\"productsCountBox\" class=\"productsCountBox\"> <div class=\"popup_count_div\" style=\"display:table-cell;padding-top:0;margin-top:0;\">Подождите... <img style=\"width:20px;display:inline-block;\" src=\"/content/files/images/ajax-loader-transparent.gif\" /> </div></div>";
		document.getElementById(popupID).innerHTML = productsCountBox_html;
	}
	
	
	
	
	
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос синхронный
        url: "/content/shop/catalogue/ajax_get_products_count.php",
        dataType: "json",//Тип возвращаемого значения
        data: "propucts_request="+JSON.stringify(propucts_request),
        success: function(answer)
        {
			//console.log(answer);
			
            propucts_request.countTotal = parseInt(answer);//Запоминаем общее количество товаров
            
            //Убираем блок "Показать еще" (если есть)
            var div = document.getElementById("showAnother");
            if(div != null)
            {
                div.parentNode.removeChild(div);
            }
            
            if(popupID != undefined)//Если запрос количества был при использовании виджетов свойств - показываем указать количества
            {
                //Создаем код окна в соответствующем блоке свойства. popupID был инициализирован при изменении состояния виджета
                var productsCountBox_html = "<div id=\"productsCountBox\" class=\"productsCountBox productsCountBox_hidden\"> <div class=\"popup_count_div\">Выбрано товаров: "+answer+" </div><a href=\"javascript:void(0);\" onclick=\"onNewPropertiesShow();\">Показать</a></div>";
                document.getElementById(popupID).innerHTML = productsCountBox_html;
                showProductsCount();
            }
			
			
			if( just_start == true )
			{
				just_start = false;
				
				propucts_request.needPagesCount = 1;//Нужна одна страница
				propucts_request.startFrom = 0;//Начать с нулевой страницы
				//propucts_request.innerHTML_mode = "add";//Способ работы с innerHTML блока товаров (add/refresh)
				
				setSortModeInRequestObject();//Устанавливаем способ сортировки
				
				//getProductsHTML();
			}
			
			
			
			<?php
			//Для обоих вариантов поиска в бэкенде
			if( $product_block_type == 2 || $product_block_type == 3 )
			{
				//Если было нажатие кнопки фильтра по наименованию
				?>
				if(just_name_filter == true)
				{
					just_name_filter = false;//Снимаем флаг
					
					propucts_request.needPagesCount = 1;//Нужна одна страница
					propucts_request.startFrom = 0;//Начать с нулевой страницы
					//propucts_request.innerHTML_mode = "refresh";//Способ работы с innerHTML блока товаров (add/refresh)
					
					setSortModeInRequestObject();//Устанавливаем способ сортировки
					
					getProductsHTML();
				}
				<?php
			}
			?>
			
        }
	 });
}
// ----------------------------------------------------------------------------------------------------------
//Функция добавления аргументов в URL, которые соответствуют выставленным фильтрам
var filters_were_changed = false;//Флаг, который управляет добавлением page в url_filters (используется только при изменении вида. В остальных запусках URL_Filters_to_url() аргумент page не подставляется в url_filters, т.к. по логике, для остальных случаев требуется отобразить с первой страницы)
function URL_Filters_to_url(by_restyle = false)
{
	<?php
	//Перед запросом - подставляем аргументы в URL для возможности копирования ссылки, содержащей параметры свойств.
	$host_and_route = parse_url($url, PHP_URL_PATH);//Получаем путь (например, /категория/подкатегория)
	if( !empty($host_and_route) )
	{
		?>
		//Формируем строку из аргументов для URL:
		var url_filters = "";
		
		//Если propucts_request == null - это может быть по нажатию "Сбросить фильтр" - в этом случае - URL должен быть очищен от url_filters полностью
		if( propucts_request != null )
		{
			//По свойствам, включая цену
			for(var i=0; i < properties_list.length; i++)
			{
				var url_arg = "";
				
				switch( parseInt(properties_list[i].property_type_id) )
				{
					case 1:
					case 2:
						//Если выставлено крайнее значение, то, его не добавляем в URL
						if( properties_list[i].min_need != jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "option", "min" ) )
						{
							url_arg = "p_"+properties_list[i].property_id+"_min="+properties_list[i].min_need;
						}
						
						if( properties_list[i].max_need != jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "option", "max" ) )
						{
							if(url_arg != "")
							{
								url_arg = url_arg + "&"
							}
							
							url_arg = url_arg + "p_"+properties_list[i].property_id+"_max="+properties_list[i].max_need;
						}
						break;
					case 4:
						//Добавляем в URL только если выставлено значение
						if( properties_list[i].true_checked == true )
						{
							url_arg = "p_"+properties_list[i].property_id+"_t=1";
						}
						if( properties_list[i].false_checked == true )
						{
							if(url_arg != "")
							{
								url_arg = url_arg + "&"
							}
							
							url_arg = url_arg + "p_"+properties_list[i].property_id+"_f=1";
						}
						break;
					case 5:
						//Добавляем в URL, только если выставлено хотя бы одно значение
						var p_5 = "";//Для значений через запятую
						for(var o=0; o < properties_list[i].list_options.length; o++)
						{
							if(properties_list[i].list_options[o].value == true)
							{
								if(p_5 != "")
								{
									p_5 = p_5 + ",";
								}
								p_5 = p_5 + properties_list[i].list_options[o].id;
							}
						}
						if( p_5 != "" )
						{
							url_arg = "p_"+properties_list[i].property_id+"="+p_5;
						}
						break;
					case 6:
						//Добавляем в URL только если выбрано значение
						if( properties_list[i].current_value != 0 )
						{
							url_arg = "p_"+properties_list[i].property_id+"="+properties_list[i].current_value;
						}
						break;
				}
				
				//Разделение аргументов
				if( url_arg != "" )
				{
					if( url_filters != "" )
					{
						url_filters = url_filters + "&";
					}
					url_filters = url_filters + url_arg;
				}
			}//~for
			
			
			if( url_filters != "" )
			{
				url_filters = "?" + url_filters;
				
				
				is_from_start = false;//Будет переход на URL, ссформированный здесь, т.е. без учета page
				
				//Добавляем еще настроку сортировки и вида каталога. Т.е. если виджеты свойств не выставлены в определенные значения, то, URL не добавляются настройки сортировки и вида
				url_filters = url_filters + "&s=" + propucts_request.page_style;
				url_filters = url_filters + "&sort=" + document.getElementById("sort_select").value;
				
				
				
				<?php
				//Если находимся не на первой странице
				if( isset($_GET["page"]) )
				{
					?>
					//Если идет действие "Изменение вида"
					if( by_restyle )
					{
						//И при этом фильтры не были изменены, то, нужно добавить аргумент page - т.к. по логике пользователю нужно только изменить вид плитки - на той же странице с теми же товарами. Т.е. url_filters будут, но, будут с той же страницей
						if( ! filters_were_changed )
						{
							url_filters = url_filters + "&page=<?php echo (int)$_GET["page"]?>";
						}//А, если фильтры были изменены - то они попали в URL и соответственно переход уже будет без page, т.е. на первую страницу.
					}
					<?php
				}
				?>
			}
		}
		
		

		window.history.pushState("", "", "<?php echo $host_and_route; ?>"+url_filters);
		<?php
	}
	//Далее обычная работа функции->
	?>
}
// ----------------------------------------------------------------------------------------------------------
//Метод выставления виджетов из URL-аргументов (только свойства и цена. Сортировка и вид выставлются отдельно)
function URL_Filters_from_url()
{
	//По массиву свойств - проверяем, есть ли значения из url_filters и если есть - выставляем значение виджетов. Перед выставлением значения - проверяем, есть ли виджет, т.к. с момента получения ссылки, конфигурация каталога могла поменяться
	for(var i=0; i < properties_list.length; i++)
	{		
		switch( parseInt(properties_list[i].property_type_id) )
		{
			case 1:
			case 2:
				if( typeof(properties_list[i].url_filters.min) == "number" )
				{
					if( typeof(jQuery( "#slider-range_"+properties_list[i].property_id )) != "undefined" )
					{
						jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 0, properties_list[i].url_filters.min );
						
						$( "#range_min_"+properties_list[i].property_id ).val( jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 0 ) );
					}
				}
				if( typeof(properties_list[i].url_filters.max) == "number" )
				{
					if( typeof(jQuery( "#slider-range_"+properties_list[i].property_id )) != "undefined" )
					{
						jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 1, properties_list[i].url_filters.max );
						
						$( "#range_max_"+properties_list[i].property_id ).val( jQuery( "#slider-range_"+properties_list[i].property_id ).slider( "values", 1 ) );
					}
				}
				break;
			case 4:
				if( typeof(properties_list[i].url_filters.f) != "undefined" )
				{
					if( typeof(document.getElementById("checkbox_false_"+properties_list[i].property_id)) != "undefined" )
					{
						document.getElementById("checkbox_false_"+properties_list[i].property_id).checked = true;
					}
				}
				if( typeof(properties_list[i].url_filters.t) != "undefined" )
				{
					if( typeof(document.getElementById("checkbox_true_"+properties_list[i].property_id)) != "undefined" )
					{
						document.getElementById("checkbox_true_"+properties_list[i].property_id).checked = true;
					}
				}
				break;
			case 5:
				if( typeof(properties_list[i].url_filters.list) != "undefined" )
				{
					for(var p=0; p < properties_list[i].url_filters.list.length ; p++)
					{
						if( typeof(document.getElementById("list_"+properties_list[i].property_id+"_"+properties_list[i].url_filters.list[p])) != "undefined" )
						{
							document.getElementById("list_"+properties_list[i].property_id+"_"+properties_list[i].url_filters.list[p]).checked = true;
						}
					}
				}
				break;
			/*
			case 6:
				//Инициализация из URL - реализована вместе со специальным поиском
				break;*/
		}
	}//~for
}
// ----------------------------------------------------------------------------------------------------------
//Метод запроса HTML-представления товаров
var is_from_start = false;//Флаг - направлять пользователя на первую страницу после getProductsHTML()
function getProductsHTML()
{	
	<?php
	//Функция URL_Filters предназначена только для 1 типа, т.е. отображение категории для покупателя
	if($product_block_type == 1)
	{
		?>
		URL_Filters_to_url();
		<?php
	}
	?>
	
	

	jQuery.ajax({
		type: "POST",
		async: false,
		url: "/content/users/ajax_set_user_option.php",
		dataType: "json",//Тип возвращаемого значения
		data: "key=propucts_request_<?php echo $category_id; ?>&value="+JSON.stringify(propucts_request),
		success: function(answer)
		{
			// console.log(answer);
			
			if(answer.status == true)
			{
				if(is_from_start)
				{
					//С первой страницы
					<?php
					$search_string_get = "";
					if( isset($_GET["search_string"]) )
					{
						$search_string_get = "?search_string=".trim(htmlspecialchars(strip_tags($_GET['search_string'])));
					}
					?>
					document.location = "/<?php echo $DP_Content->url.$search_string_get; ?>";
				}
				else//Оставляем на текущей странице
				{					
					document.location=document.location;
				}
			}
			else
			{
				alert("Ошибка установки фильтра");
			}
			
		}
	});
}
// ----------------------------------------------------------------------------------------------------------
//Показ товаров при загрузке страницы
function onStartShow()
{
    //Выставляем текущий активный стиль:
    document.getElementById("showRestyle_"+propucts_request.page_style).setAttribute("class", "showRestyle showRestyle_current");

    initProperiesValues();//Инициализируем значения свойств
	filters_were_changed = false;//Флаг - после загрузки страницы, пользователь еще не менял фильтры
}
// ----------------------------------------------------------------------------------------------------------
//Начать отображать товары в соостетствии с новыми настроками свойств
function onNewPropertiesShow()
{
    propucts_request.needPagesCount = 1;//Нужна одна страница
    propucts_request.startFrom = 0;//Начать с нулевой страницы
    //propucts_request.innerHTML_mode = "refresh";//Способ работы с innerHTML блока товаров (add/refresh)
    
    hideProductsCount();//Скрываем окно с количеством товаров
    
	is_from_start = true;//После установки фильра - показ с первой страницы
	
    getProductsHTML();
}
// ----------------------------------------------------------------------------------------------------------
//Отобразить с другим стилем
function showRestyle(style_code)
{
    //Снимаем текущий активный стиль:
    document.getElementById("showRestyle_"+propucts_request.page_style).setAttribute("class", "showRestyle");
    //Ставим новый текущий активный стиль:
    document.getElementById("showRestyle_"+style_code).setAttribute("class", "showRestyle showRestyle_current");

    jQuery.ajax({
        type: "GET",
        async: true, //Запрос синхронный
        url: "/content/shop/catalogue/set_cookie_products_style.php",
        dataType: "json",//Тип возвращаемого значения
        data: "products_style="+style_code,
        success: function(answer)
        {
            //Куки установили, теперь перезапрашиваем количество товаров
            propucts_request.page_style = answer;
            
			
			<?php
			//Функция URL_Filters предназначена только для 1 типа, т.е. отображение категории для покупателя
			if($product_block_type == 1)
			{
				?>
				URL_Filters_to_url(true);//Для учета вида в url_filters
				<?php
			}
			?>
			
            //Нужно страниц:
            //propucts_request.needPagesCount = propucts_request.startFrom + 1;
            //Начать с нулевой страницы:
            //propucts_request.startFrom = 0;
            
            //propucts_request.innerHTML_mode = "refresh";//Способ работы с innerHTML блока товаров (add/refresh)
            
            //getProductsHTML();
			
			document.location=document.location;
        }
	 });
}
// ----------------------------------------------------------------------------------------------------------
//Функция установки способа сортировки в объект запроса
function setSortModeInRequestObject()
{
	var sort_select_value = document.getElementById("sort_select").value;
	
	propucts_request.products_sort_mode = new Object;
	
	//Формируем объект сортировки
	if(sort_select_value == "price_asc")
	{
		propucts_request.products_sort_mode.field = "price";
		propucts_request.products_sort_mode.asc_desc = "asc";
	}
	else if(sort_select_value == "price_desc")
	{
		propucts_request.products_sort_mode.field = "price";
		propucts_request.products_sort_mode.asc_desc = "desc";
	}
	else if(sort_select_value == "name_asc")
	{
		propucts_request.products_sort_mode.field = "name";
		propucts_request.products_sort_mode.asc_desc = "asc";
	}
	else if(sort_select_value == "name_desc")
	{
		propucts_request.products_sort_mode.field = "name";
		propucts_request.products_sort_mode.asc_desc = "desc";
	}
}
// ----------------------------------------------------------------------------------------------------------
//Отобразить с другой сортировкой
function showResort()
{
	//Устанавливаем способ сортировки в объект запроса
	setSortModeInRequestObject();
	propucts_request.needPagesCount = 1;//Нужна одна страница
    propucts_request.startFrom = 0;//Начать с нулевой страницы
    //propucts_request.innerHTML_mode = "refresh";//Способ работы с innerHTML блока товаров (add/refresh)
	getProductsHTML();
}
// ----------------------------------------------------------------------------------------------------------
//Действия при старте страницы
jQuery( window ).load(function() {
    showPropertiesWidgets();//Показать виджеты свойств
	
	<?php
	//Функция URL_Filters предназначена только для 1 типа, т.е. отображение категории для покупателя
	if( $product_block_type == 1 )
	{
		?>
		URL_Filters_from_url();//Инициализация виджетов свойств из URL
		<?php
	}
	?>
	
    onStartShow();
	
	<?php
	//Если был переход со спецпоиска - записываем настройки пользователя - чтобы они были актуальны при переходе на другие страницы
	if( isset( $DP_Content->service_data["sp"] ) )
	{
		?>
		//onNewPropertiesShow();
		<?php
	}
	?>
});
</script>








<?php
// ------------------------------ ДАЛЕЕ ИНИЦИАЛИЗАЦИЯ ВИДЖЕТОВ СВОЙСТВ ------------------------------
?>
<script>
var properties_list = new Array();//Глобальный массив объектов свойств - используется при формировании объекта запроса товаров, чтобы знать, какие свойства есть на данной странице




<?php
// ------------------------------------------------------------------------------------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------------------------------ ОБЕСПЕЧЕНИЕ ВЫВОДА ЦЕН --------------------------------------------------------------------
// -------------------------------------------------------------------------------------------------------------------------------------------------
// ------------------------------------------------------------------------------------------------------------------------------------------------
/**Для цены также используем свойства*/
if($product_block_type == 1)//Вывод категории
{
	//Подстрока для умножение цены на курс валюты склада
	$SQL_currency_rate = '(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )';
	
	//$SQL_currency_rate = 0.016;
	
    $min_price = 0;
    $max_price = 0;
    
    //Получить список магазинов покупателя
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
    
	//Получаем крайние цены одним SQL запросом
	$SQL_all_prices = "";
	$sql_binding_args = array();
	for($i = 0; $i < count($customer_offices); $i++)
	{
		//Запрос только наценки
		$SQL_office_storage_markup_query = 'SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price` * '.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price` * '.$SQL_currency_rate.' < `max_point`';
		
		array_push($sql_binding_args, $customer_offices[$i]);
		array_push($sql_binding_args, $group_id);
		
		if($SQL_all_prices != "")
		{
			$SQL_all_prices = $SQL_all_prices . " UNION ";
		}

		$SQL_all_prices = $SQL_all_prices . 'SELECT `price` * '.$SQL_currency_rate.' + `price` * '.$SQL_currency_rate.' * ('.$SQL_office_storage_markup_query.') AS `price` FROM `shop_storages_data` WHERE `category_id` = ? AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ? )';
		
		array_push($sql_binding_args, $category_id);
		array_push($sql_binding_args, $customer_offices[$i]);
	}

	$SQL_min_max_prices = 'SELECT MIN(`price`) AS `min_price`, MAX(`price`) AS `max_price` FROM ('.$SQL_all_prices.') AS `all_prices`;';
	
	
	$min_max_prices_query = $db_link->prepare($SQL_min_max_prices);
	$min_max_prices_query->execute($sql_binding_args);
	$prices_record = $min_max_prices_query->fetch();
	if($prices_record != false)
	{
		$min_price = (int)$prices_record["min_price"];
		$max_price = (int)$prices_record["max_price"];
		if($prices_record["max_price"] > $max_price)
		{
			$max_price++;
		}
		if($DP_Config->price_rounding == '2')//До 5 руб
		{
			$max_price = $max_price + 5;
		}
		if($DP_Config->price_rounding == '3')//До 10 руб
		{
			$max_price = $max_price + 10;
		}
	}
	
    //echo "MIN: $min_price; MAX: $max_price\n";
    ?>
    //Добавляем свойство в массив javascript
    properties_list[properties_list.length] = new Object;
    properties_list[properties_list.length-1].caption = 'Цена, руб';
    properties_list[properties_list.length-1].property_type_id = 2;
    properties_list[properties_list.length-1].property_id = 'price';
	properties_list[properties_list.length-1].url_filters = new Object;
    properties_list[properties_list.length-1].min_value = <?php echo $min_price; ?>;
    properties_list[properties_list.length-1].max_value = <?php echo $max_price; ?>;
    <?php
	//URL_Filters - Формируем PHP-массив с описанием свойств - такой же точно, как для JavaScript
	if( $is_with_url_filters )
	{
		if( !isset($properties_list) )
		{
			$properties_list = array();//$properties_list здесь объявляется ПЕРВЫЙ РАЗ
		}
	
		//Добавляем свойство "Цена"
		$properties_list[] = array();
		$properties_list[count($properties_list)-1]["caption"] = 'Цена, руб';
		$properties_list[count($properties_list)-1]["property_type_id"] = 2;
		$properties_list[count($properties_list)-1]["property_id"] = 'price';
		$properties_list[count($properties_list)-1]["min_value"] = $min_price;
		$properties_list[count($properties_list)-1]["max_value"] = $max_price;
		
		if( isset($_GET["p_price_min"]) )
		{
			if( (int)$_GET["p_price_min"] > 0 )
			{
				?>
				properties_list[properties_list.length-1].url_filters.min = <?php echo (int)$_GET["p_price_min"]; ?>;
				<?php
				$properties_list[count($properties_list)-1]["min_need"] = (int)$_GET["p_price_min"];
			}
			else
			{
				$properties_list[count($properties_list)-1]["min_need"] = $min_price;
			}
		}
		else
		{
			$properties_list[count($properties_list)-1]["min_need"] = $min_price;
		}
		if( isset($_GET["p_price_max"]) )
		{
			if( (int)$_GET["p_price_max"] > 0 )
			{
				?>
				properties_list[properties_list.length-1].url_filters.max = <?php echo (int)$_GET["p_price_max"]; ?>;
				<?php
				$properties_list[count($properties_list)-1]["max_need"] = (int)$_GET["p_price_max"];
			}
			else
			{
				$properties_list[count($properties_list)-1]["max_need"] = $max_price;
			}
		}
		else
		{
			$properties_list[count($properties_list)-1]["max_need"] = $max_price;
		}
	
	}
}
else if($product_block_type == 4)//Вывод найденных товаров через поисковую строку
{
    $min_price = 0;
    $max_price = 0;
    
	
	//Подстрока для умножение цены на курс валюты склада
	$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";
    
    //Сначала получаем список товаров, которые подходят по запросу:
    $search_string = trim(htmlspecialchars(strip_tags($_GET['search_string'])));
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/text_search_algorithm.php");//ЕДИНЫЙ АЛГОРИТМ ПОИСКА ТОВАРА ПО ТЕКСТОВОЙ СТРОКЕ
    
    //Составим строку с id товаров вида (1,2,3). $products_list - массив с id товаров, который заполнен в скрипте единого алгоритма
    $products_ids_str = "";
    for($i=0; $i < count($products_list); $i++)
    {
        if($products_ids_str != "") $products_ids_str = $products_ids_str.",";
        $products_ids_str = $products_ids_str.$products_list[$i];
    }
	
	if($products_ids_str === "")
	{
		$products_ids_str = "0";
	}
	
    ?>
	propucts_request.products_ids_str = "<?php echo $products_ids_str; ?>";
	<?php
	
    
    //Получить список магазинов покупателя
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
    
	
	
	//Получаем крайние цены одним SQL запросом
	$SQL_all_prices = "";
	$sql_binding_args = array();
	for($i = 0; $i < count($customer_offices); $i++)
	{
		//Запрос только наценки
		$SQL_office_storage_markup_query = 'SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = `shop_storages_data`.`storage_id` AND `group_id` = ? AND `shop_storages_data`.`price` * '.$SQL_currency_rate.' >= `min_point` AND `shop_storages_data`.`price` * '.$SQL_currency_rate.' < `max_point`';
		
		array_push($sql_binding_args, $customer_offices[$i]);
		array_push($sql_binding_args, $group_id);
		
		if($SQL_all_prices != "")
		{
			$SQL_all_prices = $SQL_all_prices . " UNION ";
		}

		$SQL_all_prices = $SQL_all_prices . 'SELECT `price` * '.$SQL_currency_rate.' + `price` * '.$SQL_currency_rate.' * ('.$SQL_office_storage_markup_query.') AS `price` FROM `shop_storages_data` WHERE `product_id` IN ('.$products_ids_str.') AND `storage_id` IN (SELECT DISTINCT(`storage_id`) FROM `shop_offices_storages_map` WHERE `office_id` = ? )';
		
		array_push($sql_binding_args, $customer_offices[$i]);
	}

	$SQL_min_max_prices = 'SELECT MIN(`price`) AS `min_price`, MAX(`price`) AS `max_price` FROM ('.$SQL_all_prices.') AS `all_prices`;';
	
	$min_max_prices_query = $db_link->prepare($SQL_min_max_prices);
	$min_max_prices_query->execute($sql_binding_args);
	$prices_record = $min_max_prices_query->fetch();

	if($prices_record != false)
	{
		$min_price = (int)$prices_record["min_price"];
		$max_price = (int)$prices_record["max_price"];
		if($prices_record["max_price"] > $max_price)
		{
			$max_price++;
		}
		if($DP_Config->price_rounding == '2')//До 5 руб
		{
			$max_price = $max_price + 5;
		}
		if($DP_Config->price_rounding == '3')//До 10 руб
		{
			$max_price = $max_price + 10;
		}
	}
	
    //echo "MIN: $min_price; MAX: $max_price";
    ?>
    //Добавляем свойство в массив javascript
    properties_list[properties_list.length] = new Object;
    properties_list[properties_list.length-1].caption = 'Цена, руб';
    properties_list[properties_list.length-1].property_type_id = 2;
    properties_list[properties_list.length-1].property_id = 'price';
    properties_list[properties_list.length-1].min_value = <?php echo $min_price; ?>;
    properties_list[properties_list.length-1].max_value = <?php echo $max_price; ?>;
	<?php
}
//   ------------------------------------------------------------------------------------------------------------------------------------------------
//  ------------------------------------------------------------------------------------------------------------------------------------------------
// ----------------------------------------------------- ОБЕСПЕЧЕНИЕ ВЫВОДА ЦЕН ------------------------------------------------------------------
//  ------------------------------------------------------------------------------------------------------------------------------------------------
//   ------------------------------------------------------------------------------------------------------------------------------------------------
?>



<?php
//Получаем все свойства категории
if($product_block_type != 4)
{
	$category_properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = :category_id ORDER BY `order` ASC;');
	$category_properties_query->bindValue(':category_id', $category_id);
	$category_properties_query->execute();
    while( $category_property = $category_properties_query->fetch() )
    {
		$property_type_id = $category_property["property_type_id"];
        if($property_type_id == 3)continue;//Пропускаем текстовые свойства, т.к. по ним мы не фильтруем
        $property_id = $category_property["id"];
        $caption = $category_property["value"];
        $list_id = $category_property["list_id"];//Используется только для списков (линейных и древовидных)
        ?>
		//Объект для хранения массивов элементов древовидных списков для ускоренного обращения
		var tree_list_items_map = new Array();
		
        //Добавляем свойство в массив javascript
        properties_list[properties_list.length] = new Object;
        properties_list[properties_list.length-1].caption = '<?php echo $caption; ?>';
        properties_list[properties_list.length-1].property_type_id = <?php echo $property_type_id; ?>;
        properties_list[properties_list.length-1].property_id = <?php echo $property_id; ?>;
		properties_list[properties_list.length-1].url_filters = new Object
        <?php
        if($property_type_id == 5 || $property_type_id == 6)
        {
            ?>
            properties_list[properties_list.length-1].list_id = <?php echo $list_id; ?>;//Используется для списков (линейных и древовидных)
            properties_list[properties_list.length-1].list_options = new Array;//Используется для списков (линейных и древовидных)
            <?php
        }
        
		
		//Для URL_Filters
		if( $is_with_url_filters )
		{
			$properties_list[] = array();
			$properties_list[ count($properties_list) - 1 ]["caption"] = $caption;
			$properties_list[ count($properties_list) - 1 ]["property_type_id"] = $property_type_id;
			$properties_list[ count($properties_list) - 1 ]["property_id"] = $property_id;
			if($property_type_id == 5 || $property_type_id == 6)
			{
				$properties_list[ count($properties_list) - 1 ]["list_id"] = $list_id;
				$properties_list[ count($properties_list) - 1 ]["list_options"] = array();
				
				//Для древовидных списков по умолчанию
				if($property_type_id == 6)
				{
					$properties_list[count($properties_list)-1]["current_level"] = 1;
					$properties_list[count($properties_list)-1]["current_value"] = 0;
				}
			}
		}
		
		
        //Далее в зависимости от типа свойства
        switch($property_type_id)
        {
            case 1:
            case 2:
                if($property_type_id == 1) $table_postfix = "int";
                else $table_postfix = "float";
                //Получаем крайние значения этого свойства
				$max_min_values_query = $db_link->prepare('SELECT MIN(`value`), MAX(`value`) FROM `shop_properties_values_'.$table_postfix.'` WHERE `property_id` = :property_id;');
				$max_min_values_query->bindValue(':property_id', $property_id);
				$max_min_values_query->execute();
                $max_min_values_record = $max_min_values_query->fetch();
                $min_value = (int)$max_min_values_record[0];
                $max_value = (int)$max_min_values_record[1]+1;
                ?>
                //Для объекта в перечне свойст - указываем крайние значения слайдера
                properties_list[properties_list.length-1].min_value = <?php echo $min_value; ?>;
                properties_list[properties_list.length-1].max_value = <?php echo $max_value; ?>;
                <?php
				//Для URL_Filters
				if( $is_with_url_filters )
				{
					$properties_list[ count($properties_list) - 1 ]["min_value"] = $min_value;
					$properties_list[ count($properties_list) - 1 ]["max_value"] = $max_value;
					if( isset($_GET["p_".$property_id."_min"]) )
					{
						?>
						properties_list[properties_list.length-1].url_filters.min = <?php echo (int)$_GET["p_".$property_id."_min"]; ?>;
						<?php
						$properties_list[ count($properties_list) - 1 ]["min_need"] = (int)$_GET["p_".$property_id."_min"];
					}
					else
					{
						$properties_list[ count($properties_list) - 1 ]["min_need"] = $min_value;
					}
					if( isset($_GET["p_".$property_id."_max"]) )
					{
						?>
						properties_list[properties_list.length-1].url_filters.max = <?php echo (int)$_GET["p_".$property_id."_max"]; ?>;
						<?php
						$properties_list[ count($properties_list) - 1 ]["max_need"] = (int)$_GET["p_".$property_id."_max"];
					}
					else
					{
						$properties_list[ count($properties_list) - 1 ]["max_need"] = $max_value;
					}
				}
                break;
            case 4:
                //Получаем количество товаров по каждому значению:
				$match_propucts_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_bool` WHERE `property_id` = :property_id;');
				$match_propucts_query->bindValue(':property_id', $property_id);
				$match_propucts_query->execute();
                $true_count = 0;
                $false_count = 0;
                while($value = $match_propucts_query->fetch())
                {
                    if($value["value"] == 1)
                    {
                        $true_count++;
                    }
                    else
                    {
                        $false_count++;
                    }
                }
                ?>
                //Указываем количество товаров по свойствам
                properties_list[properties_list.length-1].true_count = <?php echo $true_count; ?>;
                properties_list[properties_list.length-1].false_count = <?php echo $false_count; ?>;
                <?php
				//Для URL_Filters
				if( $is_with_url_filters )
				{
					//Да
					if( isset($_GET["p_".$property_id."_t"]) )
					{
						if($_GET["p_".$property_id."_t"] == 1)
						{
							$properties_list[ count($properties_list) - 1 ]["true_checked"] = true;
							?>
							properties_list[properties_list.length-1].url_filters.t = 1;
							<?php
						}
						else
						{
							$properties_list[ count($properties_list) - 1 ]["true_checked"] = false;
						}
					}
					else
					{
						$properties_list[ count($properties_list) - 1 ]["true_checked"] = false;
					}
					
					//Нет
					if( isset($_GET["p_".$property_id."_f"]) )
					{
						if( $_GET["p_".$property_id."_f"] == 1 )
						{
							$properties_list[ count($properties_list) - 1 ]["false_checked"] = true;
							?>
							properties_list[properties_list.length-1].url_filters.f = 1;
							<?php
						}
						else
						{
							$properties_list[ count($properties_list) - 1 ]["false_checked"] = false;
						}
					}
					else
					{
						$properties_list[ count($properties_list) - 1 ]["false_checked"] = false;
					}
				}
                break;
            case 5:
				//Получаем тип списка:
				$list_info_query = $db_link->prepare('SELECT * FROM `shop_line_lists` WHERE `id` = :id;');
				$list_info_query->bindValue(':id', $list_id);
				$list_info_query->execute();
				$list_info_record = $list_info_query->fetch();
				$list_type = $list_info_record["type"];
				$list_auto_sort = $list_info_record["auto_sort"];
				//Получаем элементы списка
				$list_items = array();
				switch($list_auto_sort){
					case 'asc' :
					$list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `value` ASC;');
					break;
					case 'desc' :
					$list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `value` DESC;');
					break;
					default :
					$list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `order`;');
					break;
				}
				
				$list_items_query->bindValue(':line_list_id', $list_id);
				$list_items_query->execute();
				while( $list_item = $list_items_query->fetch() )
				{
					array_push($list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
				}
                ?>
                properties_list[properties_list.length-1].list_type = <?php echo $list_type; ?>;//Указываем тип списка в объекте javascript
                <?php
				//Для URL_Filters
				if( $is_with_url_filters )
				{
					$properties_list[ count($properties_list) - 1 ]["list_type"] = $list_type;
					
					//Для URL_Filters
					if( isset($_GET["p_".$property_id]) )
					{
						$p_values = explode(",", $_GET["p_".$property_id]);
					}
				}
				
                //Получаем количество товаров, у которых есть это свойство
				$match_propucts_query = $db_link->prepare('SELECT `value` FROM `shop_properties_values_list` WHERE `property_id` = :property_id;');
				$match_propucts_query->bindValue(':property_id', $property_id);
				$match_propucts_query->execute();
                $match_propucts_count_map = array();//Массив, в котором [ключ] = id опции списка, а [значение] = количеству повторений этого id, т.е. количеству товаров с таким свойством
                while($value = $match_propucts_query->fetch())
                {
					if(!isset($match_propucts_count_map[(integer)$value["value"]]))
					{
						$match_propucts_count_map[(integer)$value["value"]] = 0;
					}
					
                    $match_propucts_count_map[(integer)$value["value"]]++;
                }
                //По каждому элементу списка
                for($l=0; $l < count($list_items); $l++)
                {
					//Количество подходящих товаров
					if( isset($match_propucts_count_map[(integer)$list_items[$l]["id"]]) )
					{
						$match_count = $match_propucts_count_map[(integer)$list_items[$l]["id"]];
					}
					else
					{
						$match_count = 0;
					}
                    ?>
                    //Добавляем id данной опции в соответствующий массив объектов свойств - чтобы можно было обращаться к значениям свойств
                    properties_list[properties_list.length-1].list_options[properties_list[properties_list.length-1].list_options.length] = new Object;
                    properties_list[properties_list.length-1].list_options[properties_list[properties_list.length-1].list_options.length - 1].id = <?php echo $list_items[$l]["id"]; ?>;
                    properties_list[properties_list.length-1].list_options[properties_list[properties_list.length-1].list_options.length - 1].match_count = <?php echo $match_count; ?>;
                    properties_list[properties_list.length-1].list_options[properties_list[properties_list.length-1].list_options.length - 1].value = '<?php echo $list_items[$l]["value"]; ?>';
                    <?php
					//Для URL_Filters
					if( $is_with_url_filters )
					{
						$properties_list[ count($properties_list) - 1 ]["list_options"][] = array();
						
						$current_list_option = count($properties_list[ count($properties_list) - 1 ]["list_options"]) - 1;
						
						$properties_list[ count($properties_list) - 1 ]["list_options"][$current_list_option]["id"] = $list_items[$l]["id"];
						
						$properties_list[ count($properties_list) - 1 ]["list_options"][$current_list_option]["match_count"] = $match_count;
						
						if( isset($p_values) )
						{
							if( array_search($list_items[$l]["id"], $p_values) !== false )
							{
								$properties_list[ count($properties_list) - 1 ]["list_options"][$current_list_option]["value"] = true;
							}
							else
							{
								$properties_list[ count($properties_list) - 1 ]["list_options"][$current_list_option]["value"] = false;
							}
						}
					}
                }//for($l)
				//Для URL_Filters
				if( $is_with_url_filters )
				{
					if( isset($_GET["p_".$property_id]) )
					{
						?>
						properties_list[properties_list.length-1].url_filters.list = new Array();
						<?php
						$p_values = explode(",", $_GET["p_".$property_id]);
						for( $p_v = 0 ; $p_v < count($p_values); $p_v++ )
						{
							?>
							properties_list[properties_list.length-1].url_filters.list.push(<?php echo (int)$p_values[$p_v]; ?>);
							<?php
						}
					}
				}
                break;
			case 6:
				//Получаем иерархический массив дерева списка
				$needed_tree_list_id = $list_id;//Указываем ID древовидного списка, который требуется получить
				
				$is_by_default = true;
				
				//ПЕРЕХОД СО СПЕЦПОИСКА, ЛИБО ЕСТЬ ЗНАЧЕНИЕ АРГУМЕНТА В URL (Для URL_Filters). Спецпоиски и URL_Filters для свойств типа 6 работают одинаково при инициализации селектов: сразу формируем нужный набор селектов данного древовидного списка
				if( isset( $DP_Content->service_data["sp"] ) || (isset($_GET["p_".$property_id]) && $is_with_url_filters ) )
				{
					
					//Специальный поиск или URL_Filters всегда перебрасывает на первую страницу. При этом объект запроса в этот момент обнуляется. Но, товары нужно отфильтровать по древовидным спискам спецпоиска или URL_Filters. Для этого и создаем $properties_list. А, начиная со второй страницы - работа уже будет в обычном режиме (т.е. в URL будет только page)
					if( !isset($properties_list) )
					{
						$properties_list = array();
					}
					
					if( isset( $DP_Content->service_data["sp_tl_".$list_id] ) || (isset($_GET["p_".$property_id]) && $is_with_url_filters ) )
					{
						$is_by_default = false;
						
						//Получаем значение выбранного элемента древовидного списка
						if( isset( $DP_Content->service_data["sp_tl_".$list_id] ) )
						{
							$sp_tree_list_item = (int)$DP_Content->service_data["sp_tl_".$list_id];
							$url_filters_propery_type_6 = true;
							
							//Для спецпоисков делаем disable для соответствющих древовидных списков
							if( !isset($DP_Content->service_data["properties_ids"]) )
							{
								$DP_Content->service_data["properties_ids"] = array();
							}
							$DP_Content->service_data["properties_ids"][] = $property_id;
						}
						else if( isset($_GET["p_".$property_id]) )
						{
							$url_filters_propery_type_6 = true;
							$sp_tree_list_item = (int)$_GET["p_".$property_id];
						}
						
						
						//Получаем элементы древовидного списка данной ветки
						$sp_tree_list_brunch = array();
						
						//Делаем запрос информации по последнему элементу ветки:
						$sp_tree_list_item_query = $db_link->prepare('SELECT * FROM `shop_tree_lists_items` WHERE `id` = :id;');
						$sp_tree_list_item_query->bindValue(':id', $sp_tree_list_item);
						$sp_tree_list_item_query->execute();
						$sp_tree_list_item_record = $sp_tree_list_item_query->fetch();
						$sp_tree_list_item_level = $sp_tree_list_item_record["level"];
						$sp_tree_list_item_parent = $sp_tree_list_item_record["parent"];
						$sp_tree_list_item_count = $sp_tree_list_item_record["count"];
						
						//Формируем данные для селекторов
						if( !isset($sp_selects) )
						{
							$sp_selects = array();
						}
						$sp_selects["sp_select_".$property_id] = array();//Текущий селект
						$c_parent = $sp_tree_list_item_parent;//Текущий parent
						$c_selected = $sp_tree_list_item;//Текущий выбранный элемент
						for($sp = $sp_tree_list_item_level; $sp > 0; $sp--)
						{
							$bunch_items = array();
							
							$brunch_query = $db_link->prepare('SELECT * FROM `shop_tree_lists_items` WHERE `parent` = :parent AND `tree_list_id` = :tree_list_id;');
							$brunch_query->bindValue(':parent', $c_parent);
							$brunch_query->bindValue(':tree_list_id', $list_id);
							$brunch_query->execute();
							while( $brunch_record = $brunch_query->fetch() )
							{
								$selected = false;
								if( $brunch_record["id"] == $c_selected )
								{
									$selected = true;
								}
								
								array_push($bunch_items, array("id"=>$brunch_record["id"], "value"=>$brunch_record["value"], "selected"=>$selected, "webix_kids"=>$brunch_record["count"]) );
							}
							
							//Добавляем в начало массива:
							array_unshift($sp_selects["sp_select_".$property_id], $bunch_items);
							
							//Для следующей итерации
							$c_selected = $c_parent;
							//И получаем следующий (т.е. предыдущий) parent_id
							$c_parent_query = $db_link->prepare('SELECT `parent` FROM `shop_tree_lists_items` WHERE `id` = :id;');
							$c_parent_query->bindValue(':id', $c_parent);
							$c_parent_query->execute();
							$c_parent_record = $c_parent_query->fetch();
							$c_parent = $c_parent_record["parent"];
						}
						
						//Если у текущего выбранного элемента есть вложенные элементы - добавим и тоже в объект
						if( $sp_tree_list_item_count > 0 )
						{
							$bunch_items = array();
							
							//Получаем вложенные элементы:
							$brunch_query = $db_link->prepare('SELECT * FROM `shop_tree_lists_items` WHERE `parent` = :parent;');
							$brunch_query->bindValue(':parent', $sp_tree_list_item);
							$brunch_query->execute();
							while( $brunch_record = $brunch_query->fetch() )
							{
								array_push($bunch_items, array("id"=>$brunch_record["id"], "value"=>$brunch_record["value"], "selected"=>false, "webix_kids"=>$brunch_record["count"]) );
							}
							
							
							//Добавляем в конец массива:
							array_push($sp_selects["sp_select_".$property_id], $bunch_items);
						}
						
						
						?>
						//Для ускоренного получения текущих значений:
						properties_list[properties_list.length-1].tree_list_id = <?php echo $list_id; ?>;
						properties_list[properties_list.length-1].current_level = <?php echo $sp_tree_list_item_level; ?>;//Текущий уровень списков (по сути, количество отображаемых селектов)
						properties_list[properties_list.length-1].current_value = <?php echo $sp_tree_list_item; ?>;//Текущее выбранное значение. При открытии страницы - "Все"
						<?php
						
						//$properties_list[]["property_type_id"] = 6;
						/*$properties_list[count($properties_list)-1]["property_id"] = $property_id;
						$properties_list[count($properties_list)-1]["tree_list_id"] = $list_id;
						$properties_list[count($properties_list)-1]["current_level"] = $sp_tree_list_item_level;
						$properties_list[count($properties_list)-1]["current_value"] = $sp_tree_list_item;*/
						
						$properties_list[] = array("property_id"=>$property_id, "tree_list_id"=>$list_id, "current_level"=>$sp_tree_list_item_level, "current_value"=>$sp_tree_list_item, "property_type_id"=>6);
					}
				}
				//Обычная работа
				if($is_by_default)
				{
					//Если есть объект запроса - выставляем текущие значения по аналогии с специальным поиском
					if( $propucts_request != null )
					{
						$properties_list = $propucts_request["properties_list"];
						//Получаем значение выбранного элемента древовидного списка
						for( $i=0 ; $i < count($properties_list) ; $i++)
						{
							if( $properties_list[$i]["property_id"] == $property_id  )
							{
								$sp_tree_list_item = $properties_list[$i]["current_value"];
								$current_level = $properties_list[$i]["current_level"];
							}
						}
						
						
						//Получаем элементы древовидного списка данной ветки
						$sp_tree_list_brunch = array();
						
						//Делаем запрос информации по последнему элементу ветки:
						$sp_tree_list_item_query = $db_link->prepare('SELECT * FROM `shop_tree_lists_items` WHERE `id` = ?;');
						$sp_tree_list_item_query->execute( array($sp_tree_list_item) );
						$sp_tree_list_item_record = $sp_tree_list_item_query->fetch();
						$sp_tree_list_item_level = $current_level;
						$sp_tree_list_item_parent = (int)$sp_tree_list_item_record["parent"];
						$sp_tree_list_item_count = (int)$sp_tree_list_item_record["count"];
						
						

						//Формируем данные для селекторов
						if( !isset($sp_selects_2) )
						{
							$sp_selects_2 = array();
						}
						$sp_selects_2["sp_select_".$property_id] = array();//Текущий селект
						
						
						//Если $sp_tree_list_item_count > 0, то, это не последний уровень древовидного списка - нужно буде отобразить еще один селект с элементами следующего уровня (с выбранным значением Все)
						if( $sp_tree_list_item_count > 0 )
						{
							$bunch_items = array();
							
							$brunch_query = $db_link->prepare("SELECT * FROM `shop_tree_lists_items` WHERE `parent` = ?");
							$brunch_query->execute( array($sp_tree_list_item) );
							while( $brunch_record = $brunch_query->fetch() )
							{
								array_push($bunch_items, array("id"=>$brunch_record["id"], "value"=>$brunch_record["value"], "selected"=>false, "webix_kids"=>$brunch_record["count"]) );
							}
							
							//Добавляем в начало массива:
							array_unshift($sp_selects_2["sp_select_".$property_id], $bunch_items);
						}
						
						
						//Заполняем остальные селекты
						$c_parent = $sp_tree_list_item_parent;//Текущий parent
						$c_selected = $sp_tree_list_item;//Текущий выбранный элемент
						for($sp = $sp_tree_list_item_level; $sp > 0; $sp--)
						{
							$bunch_items = array();
							
							$brunch_query = $db_link->prepare('SELECT * FROM `shop_tree_lists_items` WHERE `parent` = ? AND `tree_list_id` = ?;');
							$brunch_query->execute( array($c_parent, $list_id) );
							while( $brunch_record = $brunch_query->fetch() )
							{
								$selected = false;
								if( $brunch_record["id"] == $c_selected )
								{
									$selected = true;
								}
								
								array_push($bunch_items, array("id"=>$brunch_record["id"], "value"=>$brunch_record["value"], "selected"=>$selected, "webix_kids"=>$brunch_record["count"]) );
							}
							
							//Добавляем в начало массива:
							array_unshift($sp_selects_2["sp_select_".$property_id], $bunch_items);
							
							//Для следующей итерации
							$c_selected = $c_parent;
							//И получаем следующий (т.е. предыдущий) parent_id
							$c_parent_query = $db_link->prepare('SELECT `parent` FROM `shop_tree_lists_items` WHERE `id` = ?;');
							$c_parent_query->execute( array($c_parent) );
							$c_parent_record = $c_parent_query->fetch();
							$c_parent = $c_parent_record["parent"];
						}
						?>
						//Для ускоренного получения текущих значений:
						properties_list[properties_list.length-1].tree_list_id = <?php echo $list_id; ?>;
						properties_list[properties_list.length-1].current_level = <?php echo $current_level; ?>;//Текущий уровень списков (по сути, количество отображаемых селектов)
						properties_list[properties_list.length-1].current_value = <?php echo $sp_tree_list_item; ?>;//Текущее выбранное значение. При открытии страницы - "Все"
						<?php
					}
					else
					{
						?>
						jQuery.ajax({
							type: "GET",
							async: true, //Запрос Асинхронный
							url:'/content/shop/catalogue/tree_lists/ajax/ajax_get_brunch_items.php?tree_list_id=<?php echo $list_id; ?>&parent_id=0&int_1='+(properties_list.length-1)+'&int_2=<?php echo $property_id; ?>',
							dataType: "json",//Тип возвращаемого значения
							success: function(data)
							{
								//Получаем селект
								var select = document.getElementById("tree_list_select_" + data.int_2 + "_1");
								
								//Страница еще не успела создать селект
								if(select == null || select == undefined)
								{
									//console.log("НЕТ ЕЩЕ СЕЛЕКТА! :(");
									
									//Добавяем список первого уровня в объект свойства - он потом отобразится при формировании селекта
									properties_list[data.int_1].first_level_items = data.data;
								}
								else//Селект уже есть - заполняем его
								{
									//console.log("СЕЛЕКТ УЖЕ ЕСТЬ! :)");
									
									//Заполняем селект
									for (var i = 0; i < data.data.length; i++)
									{
										var opt = document.createElement('option');
										opt.value = data.data[i].id;
										opt.id = "tree_option_" + data.data[i].id;
										opt.webix_kids = data.data[i].webix_kids;
										opt.innerHTML = data.data[i].value;
										select.appendChild(opt);
									}
								}
							}
						});
						

						
						//Для ускоренного получения текущих значений:
						properties_list[properties_list.length-1].tree_list_id = <?php echo $list_id; ?>;
						properties_list[properties_list.length-1].current_level = 1;//Текущий уровень списков (по сути, количество отображаемых селектов)
						properties_list[properties_list.length-1].current_value = 0;//Текущее выбранное значение. При открытии страницы - "Все"
						<?php
					}
				}
				break;
        }
        ?>
        <?php
    }//for($i) - по всем свойствам
	
	
	if( isset( $DP_Content->service_data["sp"] ) || (isset($url_filters_propery_type_6) && $is_with_url_filters ) )
	{
		if(!isset($sp_selects))
		{
			$sp_selects = array();
		}
		?>
		var sp_selects = <?php echo json_encode($sp_selects); ?>;
		<?php
	}
	if( $propucts_request != null )
	{
		if(!isset($sp_selects_2))
		{
			$sp_selects_2 = array();
		}
		?>
		var sp_selects_2 = <?php echo json_encode($sp_selects_2); ?>;
		<?php
	}
	
	
}//~if($product_block_type != 4)
?>
// ----------------------------------------------------------
//Показать виджеты свойств
function showPropertiesWidgets()
{
    var properties_div = document.getElementById("side_properties_widgets_div");
    properties_div.innerHTML = "<div style=\"text-align:center;padding-top:15px;padding-bottom:15px;\"><button onclick=\"propucts_request=null;is_from_start = true;getProductsHTML();\">Сбросить фильтр</button></div><div class=\"one_property_separator\"></div>";
    
	
	
    for(var i=0; i < properties_list.length; i++)
    {
        var property_html = "";
        var property_id = properties_list[i].property_id;
        
        property_html += "<div class=\"one_property\">";
        property_html += "<h4>"+properties_list[i].caption+"</h4>";
        switch(properties_list[i].property_type_id)
        {
            case 1:
            case 2:
                property_html += "<div class=\"slider_ranges\">";
                    property_html += "<input type=\"text\" id=\"range_min_"+property_id+"\" readonly />";
                    property_html += " — ";
                    property_html += "<input type=\"text\" id=\"range_max_"+property_id+"\" readonly />";
                    property_html += "<div class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"\"></div>";
                property_html += "</div>";
                
                property_html += "<div class=\"slider_container\">";
                    property_html += "<div id=\"slider-range_"+property_id+"\">";
                    property_html += "</div>";
                property_html += "</div>";
                break;
            case 4:
				
				//Выставляем значения фильтра (после загрузки страницы)
				var item_checked_yes = "";
				var item_checked_no = "";
				<?php
				if( $propucts_request != null )
				{
					?>
					for(var p=0; p < propucts_request.properties_list.length; p++)
					{
						if( propucts_request.properties_list[p].property_id == property_id )
						{
							if( propucts_request.properties_list[p].true_checked == true )
							{
								item_checked_yes = " checked=\"checked\" ";
							}
							if( propucts_request.properties_list[p].false_checked == true )
							{
								item_checked_no = " checked=\"checked\" ";
							}
							break;
						}
					}
					<?php
				}
				?>
				
                property_html += "<div class=\"list_div\">";
                    property_html += "<div>";
                        property_html += "<input type=\"checkbox\" id=\"checkbox_true_"+property_id+"\" class=\"css-checkbox\" onchange=\"setProductsCountPopupId('productsCountPopup_"+property_id+"_true'); productsCountRequest();\" "+item_checked_yes+" />";
                        property_html += "<label for=\"checkbox_true_"+property_id+"\" class=\"css-label\">Да</label>";
                        property_html += "<font class=\"match_products_count\"> "+properties_list[i].true_count+"</font>";
                        property_html += "<div class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"_true\"></div>";
                    property_html += "</div>";
                    property_html += "<div>";
                        property_html += "<input type=\"checkbox\" id=\"checkbox_false_"+property_id+"\" class=\"css-checkbox\" onchange=\"setProductsCountPopupId('productsCountPopup_"+property_id+"_false'); productsCountRequest();\" "+item_checked_no+" />";
                        property_html += "<label for=\"checkbox_false_"+property_id+"\" class=\"css-label\">Нет</label>";
                        property_html += "<font class=\"match_products_count\"> "+properties_list[i].false_count+"</font>";
                        property_html += "<div class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"_false\"></div>";
                    property_html += "</div>";
                property_html += "</div>";
                break;
            case 5:
                var printed = 0;//Считаем количество выведенных опций данного списка
				var start_hide = 0;//Флаг "Начали скрывать остальные опции"
                property_html += "<div class=\"list_div\">";
                //Выводим все пункты списка
                for(var l=0; l < properties_list[i].list_options.length; l++)
                {
                    //Скрываем те опции, в которых отсутствуют товары
                    var display_none = "";
                    if(properties_list[i].list_options[l].match_count == 0)
                    {
                        display_none = " style=\"display:none;\"";
                    }
                    else//Считаем количество выведеных опций
                    {
                        printed++;//Эта опция будет выведена
                    }
                    
                    var option_html = "";//HTML для данной опции
                    option_html += "<div"+display_none+">";
                    
					//Выставляем значения фильтра (после загрузки страницы)
					var item_checked = "";
					<?php
					if( $propucts_request != null )
					{
						?>
						for(var p=0; p < propucts_request.properties_list.length; p++)
						{
							if( propucts_request.properties_list[p].property_id == property_id )
							{
								if( propucts_request.properties_list[p].list_options[l] )
								{
									if( propucts_request.properties_list[p].list_options[l].value == true )
									{
										item_checked = " checked=\"checked\" ";
									}
								}
								break;
							}
						}
						<?php
					}
					?>
					
					
					option_html += "<input type=\"checkbox\" id=\"list_"+property_id+"_"+properties_list[i].list_options[l].id+"\" class=\"css-checkbox\" onchange=\"setProductsCountPopupId('productsCountPopup_"+property_id+"_"+properties_list[i].list_options[l].id+"'); productsCountRequest();\" "+item_checked+" />";
                    option_html += "<label for=\"list_"+property_id+"_"+properties_list[i].list_options[l].id+"\" class=\"css-label\">"+properties_list[i].list_options[l].value+"</label>";
                    option_html += "<font class=\"match_products_count\"> "+properties_list[i].list_options[l].match_count+"</font>";
                    option_html += "<div class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"_"+properties_list[i].list_options[l].id+"\"></div>";
                    option_html += "</div>";
                    
                    
                    
                    if(printed == 6 && start_hide == 0)//До этого было выведено 5. Эта шестая - начинаем скрывать
                    {
                        property_html += "<div state=\"hidden\" style=\"display:none\" id=\"other_list_options_"+property_id+"\">";
						start_hide = 1;//Флаг - начали скрывать остальные опции
                    }
                    
                    property_html += option_html;
                    
                    //Если выведенных опций списка больше 5 и это последняя опция - выводим закрывающий div
                    if(l == properties_list[i].list_options.length -1 && printed > 5)
                    {
                        property_html += "</div>";
                    }
                }//for(l)
                if(printed > 5)//Если количество элементов в списке больше 5, то выводим кнопку для открытия/закрытия списка
                {
                    
                    property_html += "<div class=\"show_hidden_div\" style=\"text-align:center\">";
                        property_html += "<a class=\"show_hidden_a\" id=\"show_hidden_a_"+property_id+"\" href=\"javascript:void(0);\" onclick=\"other_list_options_handle("+property_id+");\">Еще варианты</a>";
                    property_html += "</div>";
                    
                    //$javascript_for_print_after .= "\nother_list_options_handle($property_id);\n";//Делаем вызов функции для скрытия блока
                }
                
                property_html += "</div>";
                break;
			case 6:
				property_html += "<div class=\"list_div\">";
				property_html += "<div id=\"tree_list_div_"+property_id+"\" >";
				

				<?php
				if( isset( $DP_Content->service_data["sp"] ) || $is_with_url_filters )
				{
					?>
					var sp_select = undefined;
					if( typeof sp_selects !== 'undefined' )
					{
						var sp_select = sp_selects["sp_select_"+property_id];
					}
					
					var is_by_default = true;
					if( sp_select != undefined )
					{
						//Если длина больше 0, т.е. это свойство было не по умолчанию
						if(sp_select.length > 0)
						{
							is_by_default = false;
						}
					}
					
					if( ! is_by_default )
					{
						for(var s = 0; s < sp_select.length; s++)
						{
							var margin_top = "";
							if( s > 0)
							{
								margin_top = "margin-top:5px;";
							}
							
							
							
							
							var select_disabled = "";
							<?php
							//Делаем недоступным изменения в видежете древовидного списка, если он участвует в спецпоиске
							if( isset($DP_Content->service_data["properties_ids"]) )
							{
								foreach($DP_Content->service_data["properties_ids"] AS $key=>$value)
								{
									?>
									if( parseInt(property_id) == parseInt(<?php echo $value; ?>) )
									{
										select_disabled = "disabled";
									}
									<?php
								}
							}
							?>
							
							
							
							property_html += "<select "+select_disabled+" style=\""+margin_top+"\" class=\"form-control\" id=\"tree_list_select_"+property_id+"_"+(s+1)+"\" onchange=\"onTreeListSelectChange("+property_id+", "+(s+1)+", "+i+");\" >";
							property_html += "<option value=\"0\">Все</option>";
							
							for(var o = 0; o < sp_select[s].length; o++)
							{
								var selected = "";
								if(sp_select[s][o].selected == true)
								{
									selected = " selected = \"selected\" ";
								}
								
								property_html += "<option "+selected+" value=\""+sp_select[s][o].id+"\" id=\"tree_option_"+sp_select[s][o].id+"\" webix_kids=\""+sp_select[s][o].webix_kids+"\">"+sp_select[s][o].value+"</option>";
							}
							
							property_html += "</select>";
						}
					}
					else
					{
						property_html += "<select class=\"form-control\" id=\"tree_list_select_"+property_id+"_1\" onchange=\"onTreeListSelectChange("+property_id+", 1, "+i+");\" >";
						property_html += "<option value=\"0\">Все</option>";
						
						//На тот случай, если данные от сервера пришли быстрее, чем формируется этот селект
						if( properties_list[i].first_level_items != null || properties_list[i].first_level_items != undefined )
						{
							for( var op = 0 ; op < properties_list[i].first_level_items.length ; op++ )
							{
								property_html += "<option value=\""+properties_list[i].first_level_items[op].id+"\" id=\"tree_option_"+properties_list[i].first_level_items[op].id+"\" webix_kids=\""+properties_list[i].first_level_items[op].webix_kids+"\">"+properties_list[i].first_level_items[op].value+"</option>";
							}
						}
						
						property_html += "</select>";
					}
					
					

					<?php
				}
				else//Обычная работа
				{
					?>
					//Пробуем получить значение из объекта
					var sp_select_2 = undefined;
					if( typeof sp_selects_2 !== 'undefined' )
					{
						sp_select_2 = sp_selects_2["sp_select_"+property_id];
					}
					
					//Если значение есть (т.е. есть объект)
					var by_default = true;
					if( sp_select_2 != undefined )
					{
						//Если длина больше 0, т.е. это свойство было не по умолчанию
						if(sp_select_2.length > 0)
						{
							by_default = false;
						}
					}
					
					if( ! by_default )
					{
						for(var s = 0; s < sp_select_2.length; s++)
						{
							var margin_top = "";
							if( s > 0)
							{
								margin_top = "margin-top:5px;";
							}
							
							property_html += "<select style=\""+margin_top+"\" class=\"form-control\" id=\"tree_list_select_"+property_id+"_"+(s+1)+"\" onchange=\"onTreeListSelectChange("+property_id+", "+(s+1)+", "+i+");\" >";
							property_html += "<option value=\"0\">Все</option>";
							
							for(var o = 0; o < sp_select_2[s].length; o++)
							{
								var selected = "";
								if(sp_select_2[s][o].selected == true)
								{
									selected = " selected = \"selected\" ";
								}
								
								property_html += "<option "+selected+" value=\""+sp_select_2[s][o].id+"\" id=\"tree_option_"+sp_select_2[s][o].id+"\" webix_kids=\""+sp_select_2[s][o].webix_kids+"\">"+sp_select_2[s][o].value+"</option>";
							}
							
							property_html += "</select>";
						}
					}
					else//Без объекта
					{
						property_html += "<select class=\"form-control\" id=\"tree_list_select_"+property_id+"_1\" onchange=\"onTreeListSelectChange("+property_id+", 1, "+i+");\" >";
						property_html += "<option value=\"0\">Все</option>";
						
						//На тот случай, если данные от сервера пришли быстрее, чем формируется этот селект
						if( properties_list[i].first_level_items != null || properties_list[i].first_level_items != undefined )
						{
							for( var op = 0 ; op < properties_list[i].first_level_items.length ; op++ )
							{
								property_html += "<option value=\""+properties_list[i].first_level_items[op].id+"\" id=\"tree_option_"+properties_list[i].first_level_items[op].id+"\" webix_kids=\""+properties_list[i].first_level_items[op].webix_kids+"\">"+properties_list[i].first_level_items[op].value+"</option>";
							}
						}
						
						property_html += "</select>";
					}
					<?php
				}
				?>
				property_html += "</div>";
				
				//Индикатор загрузки следующего селекта
				property_html += "<div class=\"text-center\" style=\"display:none;\" id=\"tree_list_loading_gif_"+property_id+"\"><img style=\"margin-top:4px;\" src=\"/content/files/images/ajax-loader-transparent.gif\" class=\"loading_img\" /></div>";
				
				property_html += "<div style=\"margin-top:0;\" class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"\"></div>";
				property_html += "</div>";
				break;
        }
        property_html += "</div>";
        
        if(i != properties_list.length -1)
        {
            property_html += "<div class=\"one_property_separator\"></div>";
        }
        
        properties_div.innerHTML += property_html;//Добавляем HTML в блок свойств
    }//for
    
    
    //Инициализировать слайдеры для типов int и float
    for(var i=0; i < properties_list.length; i++)
    {
        if(properties_list[i].property_type_id != 1 && properties_list[i].property_type_id != 2)continue;
        
        sliderIntFloatInit(properties_list[i]);
    }
    

    properties_div.setAttribute("style", "");
}//~function showPropertiesWidgets()
// ----------------------------------------------------------
//Обработка изменения селектора свойства типа "Древовидный список"
function onTreeListSelectChange(property_id, level, property_index)
{
	//1. Определяем значение выбранного элемента
	var select_value = document.getElementById("tree_list_select_"+property_id+"_"+level).value;
	
	//1.1. Для записи текущего значения данного свойства
	var current_level = level;
	var current_value = select_value;
	
	
	
	//2. Удаляем все селекты после данного
	var next_level = level + 1;
	while(true)
	{
		var next_select = document.getElementById("tree_list_select_"+property_id+"_"+next_level);
		
		if( next_select != null )
		{
			document.getElementById("tree_list_div_"+property_id).removeChild(next_select);
			next_level++;
		}
		else
		{
			break;
		}
	}
	
	//3. Проверяем, выставлено ли значение "Все"
	if(select_value == 0)//Если выбрано значение "Все"
	{
		//Если это значение на селекте не первого уровня
		if( current_level > 1 )
		{
			//То, ставим текущее значение - значение предыдущего селекта
			current_level = current_level - 1;
			current_value = document.getElementById("tree_list_select_"+property_id+"_"+current_level).value;
		}
	}
	
	//4. Указываем текущее значение в объекте свойства - для ускоренной записи в объект запроса
	properties_list[property_index].current_level = current_level;
	properties_list[property_index].current_value = current_value;
	
	
	//5. Делаем запрос количества товаров, соответствующее выставленому селекту (Асинхронно)
	setProductsCountPopupId('productsCountPopup_'+property_id);
	productsCountRequest();
	
	
	
	//6. Выбрано определенное значение (отличное от значения "Все") - асинхронно подгружаем следующий селект
	if(select_value != 0)
	{
		if( parseInt( document.getElementById("tree_option_"+select_value).getAttribute("webix_kids") ) == 0 )
		{
			//Этот селект последний в ветви
			return;
		}
		
		
		//int_1 - property_id
		//int_2 - level+1
		//int_3 - property_index
		
		//Индикация загрузки следующего списка - ON
		document.getElementById("tree_list_loading_gif_"+property_id).setAttribute("style", "display:block;");
		
		jQuery.ajax({
			type: "GET",
			url:'/content/shop/catalogue/tree_lists/ajax/ajax_get_brunch_items.php?tree_list_id='+properties_list[property_index].tree_list_id+'&parent_id='+select_value+'&int_1='+property_id+'&int_2='+(level+1)+'&int_3='+property_index,
			async: true,
			dataType:"json",
			success: function(data)
			{
				//console.log(data);
				
				//Индикация загрузки следующего списка - OFF
				document.getElementById("tree_list_loading_gif_"+data.int_1).setAttribute("style", "display:none;");
				
				
				if(data.data.length > 0)
				{
					//Селект
					var select = document.createElement('select');
					select.setAttribute("style", "margin-top:5px;");
					select.setAttribute("class", "form-control");
					select.setAttribute("id", "tree_list_select_"+data.int_1+"_"+data.int_2);
					select.setAttribute("onchange", "onTreeListSelectChange("+data.int_1+", "+data.int_2+", "+data.int_3+");");
					//Заполняем селект элементами
					var html = "<option value=\"0\">Все</option>";
					for(var i=0; i < data.data.length; i++)
					{
						html += "<option value=\""+data.data[i].id+"\" id=\"tree_option_"+data.data[i].id+"\" webix_kids=\""+data.data[i].webix_kids+"\">"+data.data[i].value+"</option>"
					}
					select.innerHTML = html;
					document.getElementById("tree_list_div_"+data.int_1).appendChild(select);
				}
			}
		});
	}	
}
// ----------------------------------------------------------
//Функция инициализации слайдера
function sliderIntFloatInit(property)
{
	var min_need = property.min_value;
	var max_need = property.max_value;
	<?php
	if( $propucts_request != null )
	{
		?>
		for(var i=0; i < propucts_request.properties_list.length; i++)
		{
			if( propucts_request.properties_list[i].property_id == property.property_id )
			{
				min_need = propucts_request.properties_list[i].min_need;
				max_need = propucts_request.properties_list[i].max_need;
			}
		}
		
		
		<?php
	}
	?>
	
    //Создаем слайдер
    jQuery( "#slider-range_"+property.property_id ).slider({
        range: true,
        min: property.min_value,
        max: property.max_value,
        values: [ min_need, max_need ],
        slide: function( event, ui ) {//Событие - передвижение
            $( "#range_min_"+property.property_id ).val( ui.values[ 0 ]);
            $( "#range_max_"+property.property_id ).val( ui.values[ 1 ] );
        },
        stop: function(){//Событие - отпустили слайдер
            setProductsCountPopupId("productsCountPopup_"+property.property_id);//Установка id контейнера для всплаывающего окна
            productsCountRequest();//Запрос количества товаров
        }
    });
    //Выставляем текущие крайние значение в поля ввода
    $( "#range_min_"+property.property_id ).val( jQuery( "#slider-range_"+property.property_id ).slider( "values", 0 ) );
    $( "#range_max_"+property.property_id ).val( jQuery( "#slider-range_"+property.property_id ).slider( "values", 1 ) );
}
// ----------------------------------------------------------
//Функция предназначена для скрытия/открытия опций списка, если их больше 5
function other_list_options_handle(property_id)
{
    //Реверсируем значение атрибута class
    var other_list_options_div = document.getElementById("other_list_options_"+property_id);
    if(other_list_options_div.getAttribute("state") == "hidden")
    {
        other_list_options_div.setAttribute("state", "shown");
        jQuery('#other_list_options_'+property_id).fadeIn(200, 'swing', function(){});
        document.getElementById("show_hidden_a_"+property_id).innerHTML = "Скрыть";
        // console.log("Открыли");
    }
    else
    {
        other_list_options_div.setAttribute("state", "hidden");
        jQuery('#other_list_options_'+property_id).fadeOut(200, 'swing', function(){});
        document.getElementById("show_hidden_a_"+property_id).innerHTML = "Еще варианты";
        // console.log("Скрыли");
    }
}
</script>
    
    
<!-- START ВЫСПЛЫВАЮЩЕЕ ОКНО ДЛЯ УКАЗАНИЯ КОЛИЧЕСТВА ТОВАРОВ -->
<script>
var productsCountBox = undefined;//Переменная для div окна
var hideTimer = undefined;//Переменная для таймера скрытия
var popupID = undefined;//Переменная для хранения id следующего контейнера, где будет показано всплывающее окно
//--------------------------------
//Показать окно
function showProductsCount()
{
	if(hideTimer != undefined) clearTimeout(hideTimer);//Отключаем предыдущий таймер
    
    productsCountBox = $('#productsCountBox');
	productsCountBox.removeClass('productsCountBox_hidden');
	setTimeout(function () {
	  productsCountBox.removeClass('productsCountBox_visuallyhidden');
	}, 20);
	
	//Устанавливаем таймер для скрытия через 7с
	hideTimer = setTimeout('hideProductsCount()', 7000);
}
//--------------------------------
//Убрать окно
function hideProductsCount()
{
    if(hideTimer != undefined) clearTimeout(hideTimer);//Отключаем предыдущий таймер
    
    productsCountBox = $('#productsCountBox');
	productsCountBox.addClass('productsCountBox_visuallyhidden');
	productsCountBox.one('transitionend', function(e) {
	  productsCountBox.addClass('productsCountBox_hidden');
	});
}
//--------------------------------
//Установка контейнера, в котором будет показано всплывающее окно с количеством товара
function setProductsCountPopupId(next_id)
{
    if(popupID != undefined) document.getElementById(popupID).innerHTML = "";//Предвариельно очищаем старый контейнер от всплываюещего окна
    popupID = next_id;
}
</script>
<!-- END ВЫСПЛЫВАЮЩЕЕ ОКНО ДЛЯ УКАЗАНИЯ КОЛИЧЕСТВА ТОВАРОВ -->










<!-- ПОЛЕ ДЛЯ ВЫВОДА ТОВАРОВ -->
<div class="row" style="margin:0;">
<div class="col-lg-12" id="products_area">

<?php
//ЗДЕСЬ ПОЛНОСТЬЮ ПОВТОРЯЕМ СКРИПТ ИЗ ajax_get_products_list.php (из первоначального асинхронного варианта)

//Указатель валюты
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/general/get_currency_indicator.php");

//Получить список магазинов покупателя
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");



//С какой страницы начать
$startFrom = 0;
if( isset($_GET["page"]) )
{
	if( (int)$_GET["page"] > 0 )
	{
		$startFrom = (int)$_GET["page"] - 1;
	}
}



//Для URL_Filters. Здесь записываем объект запроса в сессию и поиск товаров пойдет по нему
if( $is_with_url_filters == true )
{
	$propucts_request = array();
	
	//$properties_list был создан выше - из URL_Filters
	$propucts_request["properties_list"] = $properties_list;
	
	
	//$productsPerPage = $DP_Config->products_count_for_page;//Количество товаров на страницу
	//$needPagesCount = 1;//Требуемое количество страниц
	//$startFrom = 0;//С какой страницы начать

	//$product_from = $startFrom*$productsPerPage;//С какого продукта начать
	//$product_max_count = $needPagesCount*$productsPerPage;//До какого продукта показывать (НЕ включительно)
	
	
	//Сортировка
	if( $_GET["sort"] == "price_asc" )
	{
		$propucts_request["products_sort_mode"]["field"] = "price";
		$propucts_request["products_sort_mode"]["asc_desc"] = "asc";
	}
	else if( $_GET["sort"] == "price_desc" )
	{
		$propucts_request["products_sort_mode"]["field"] = "price";
		$propucts_request["products_sort_mode"]["asc_desc"] = "desc";
	}
	else if( $_GET["sort"] == "name_asc" )
	{
		$propucts_request["products_sort_mode"]["field"] = "name";
		$propucts_request["products_sort_mode"]["asc_desc"] = "asc";
	}
	else if( $_GET["sort"] == "name_desc" )
	{
		$propucts_request["products_sort_mode"]["field"] = "name";
		$propucts_request["products_sort_mode"]["asc_desc"] = "desc";
	}
	
	
	$propucts_request["category_id"] = $category_id;
	$propucts_request["productsPerPage"] = $DP_Config->products_count_for_page;
	$propucts_request["product_block_type"] = $product_block_type;
	$propucts_request["needPagesCount"] = 1;
	
	
	
	//Если это поиск по наименованию - добавляем products_ids_str - ДЛЯ URL_Filters не актуально
	//$propucts_request["products_ids_str"] = $products_ids_str;
	
	DP_User::set_user_option("propucts_request_".$category_id, json_encode($propucts_request));
}





//Получаем объект запроса
$propucts_request = json_decode(DP_User::get_user_option_by_key("propucts_request_".$category_id), true);
if( $propucts_request != NULL )
{
	$properties_list = $propucts_request["properties_list"];
	$product_block_type = $propucts_request["product_block_type"];//Тип страницы (1 - отображения для покупателя; 2 - для администратора каталога; 3 - для кладовщика; 4 - для покупателя при поиске через текстовую строку)

	$productsPerPage = $DP_Config->products_count_for_page;//Количество товаров на страницу
	$needPagesCount = $propucts_request["needPagesCount"];//Требуемое количество страниц
	//$startFrom = $propucts_request["startFrom"];//С какой страницы начать
	//$page_style = $propucts_request["page_style"];//Стиль отображения

	$product_from = $startFrom*$productsPerPage;//С какого продукта начать
	$product_max_count = $needPagesCount*$productsPerPage;//До какого продукта показывать (НЕ включительно)
}
else//Значит это первый заход пользователя - ставим значения по умолчанию (первая страница без фильтра)
{
	$propucts_request = array();
	
	if( !isset($properties_list) )
	{
		$properties_list = array();//Свойства не учитываем
	}
	else
	{
		//Значит был переход со спецпоиска. Это первая страница. Чтобы покупатель смог дальше переходить по страницам в обычном режиме, но, с выставленными значениями свойств от спецпоиска (древовидные списки) - нужно будет записать настройки
		$propucts_request["properties_list"] = $properties_list;
	}

	$productsPerPage = $DP_Config->products_count_for_page;//Количество товаров на страницу
	$needPagesCount = 1;//Требуемое количество страниц
	//$startFrom = 0;//С какой страницы начать

	$product_from = $startFrom*$productsPerPage;//С какого продукта начать
	$product_max_count = $needPagesCount*$productsPerPage;//До какого продукта показывать (НЕ включительно)
	
	
	//Сортировка по умолчанию - по цене (дешевле)
	$propucts_request["products_sort_mode"]["field"] = "price";
	$propucts_request["products_sort_mode"]["asc_desc"] = "asc";
	
	
	//Если это поиск по наименованию - добавляем products_ids_str
	if( isset($products_ids_str) )
	{
		$propucts_request["products_ids_str"] = $products_ids_str;
	}
	else
	{
		$propucts_request["products_ids_str"] = '';
	}
}






//Для фильтрования товаров по цене (только, если требуется)
$need_price_filter = false;
$price_object = NULL;


$main_class_of_block = "";//Главный класс блока
if( isset($_GET["s"]) )
{
	$page_style = (int)$_GET["s"];
}
else if(isset($_COOKIE["products_style"]))
{
	$page_style = (int)$_COOKIE["products_style"];
}
else
{
	$page_style = NULL;
}
switch($page_style)
{
    case 1:
        $main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-4 col-lg-3";//Плитка
        break;
    case 2:
        $main_class_of_block = "product_div_list_photo col-lg-12";//Список с фото
        break;
    case 3:
        $main_class_of_block = "product_div_list col-lg-12";//Список без фото
        break;
	default:
		$main_class_of_block = "product_div_tile col-xs-12 col-sm-4 col-md-4 col-lg-3";//Плитка
}



//ФОРМИРУЕМ ЗАПРОС НА ПОЛУЧЕНИЕ ОБЪЕКТОВ ТОВАРОВ
/*
В зависимости от типа блока формируем SQL-запрос
*/


//Подстрока для умножение цены на курс валюты склада
$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";


//Страница категории товаров - вывод для покупателя с ценами
if($product_block_type == 1)
{
	//Подключение построение запроса
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_list_mode_1.php");
}
else if( $product_block_type == 4 )
{
	//Подключение построение запроса
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_list_mode_4.php");
}






//Подключаем скрипт генерации объектов товаров единого формата ( $products_objects )
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/generate_products_objects_by_sql.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/helper.php");

if(count($products_objects) == 0)
{
    ?>
    <div style="text-center">Товары не найдены</div>
	
	</div>
	
	</div>
    <?php
	//~ закрывающий </div>: перед products_area <div class="row">
}
else
{
	
	//Выводим карточки товаров
	foreach( $products_objects AS $product_id => $product )
	{
		printProductBlock($product);
	}
	
	//Подключаем запрос для получения общего количеств товаров по фильтрам
	if($product_block_type == 1)
	{
		//Подключение построение запроса
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_count_mode_1.php");
	}
	else if($product_block_type == 4)
	{
		//Подключение построение запроса
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/query_builder/query_products_count_mode_4.php");
	}
	$SQL = 'SELECT COUNT(DISTINCT(`id`)) AS `count_total` FROM ('.$SQL.') AS `all`;';
	$count_total_query = $db_link->prepare($SQL);
	$count_total_query->execute($sql_args_array);
	$count_total_record = $count_total_query->fetch();
	$count_total_record["count_total"];
	?>
	</div>
	</div>
	<?php
	//~ закрывающий </div>: перед products_area <div class="row">
	?>
	
	<div></div>
	
	<div class="row" style="margin:0;">
		<div class="col-lg-12 text-center">
			<!--<ul class="pagination">
			<?php
			//echo pagination($count_total_record["count_total"], $DP_Config->products_count_for_page, 2, $startFrom+1, 'active', $link);
			?>
			</ul>-->
			<div class="btn-group" style="margin-top:10px;">
				<?php
				//Компоненты для URL
				$search_string_get = "";
				if( isset($_GET["search_string"]) )
				{
					$search_string_get = "&search_string=".trim(htmlspecialchars(strip_tags($_GET['search_string'])));
				}
				$url_for_link = "/".$DP_Content->url."?page=";
				?>
			
				<?php
				$current_page = NULL;
				if( isset($_GET["page"]) )
				{
					$current_page = (int)$_GET["page"];
				}
				if( $current_page < 1 )
				{
					$current_page = 1;
				}
				//КНОПКА "ВЛЕВО"
				$to_left_disabled = "";
				if( $current_page == 1 )
				{
					$to_left_disabled = "disabled";
				}
				?>
				<a class="btn btn-default <?php echo $to_left_disabled; ?>" href="<?php echo $url_for_link."1".$search_string_get; ?>">Первая</a>
				<a class="btn btn-default <?php echo $to_left_disabled; ?>" href="<?php echo $url_for_link.($current_page-1).$search_string_get; ?>"><i class="fa fa-chevron-left"></i></a>
				
				
				<?php
				//Определяем количество страниц
				$pages_count = (int)($count_total_record["count_total"]/$DP_Config->products_count_for_page);
				if( ($count_total_record["count_total"]%$DP_Config->products_count_for_page) > 0 )
				{
					$pages_count++;
				}
				
				
				//Выводим кнопки для конкретных страниц (с номерами). Если будет критично для скорости работы - можно чуть доработать цикл - начать не сначала и break после вывода нужных ссылок
				for($i=1; $i <= $pages_count; $i++)
				{
					//Две кнопки до текущей - показываем
					if( ($current_page - $i) > 2  )
					{
						continue;
					}
					
					
					//Две кнопки после текущей - показываем
					if( ($i - $current_page) > 2  )
					{
						break;
					}
					
					
					
					$active = "";
					if($i == $current_page)
					{
						$active = "active";
					}
					?>
					<a class="btn btn-default <?php echo $active; ?>" href="<?php echo $url_for_link.$i.$search_string_get; ?>"><?php echo $i; ?></a>
					<?php
				}
				
				
				//КНОПКА "ВПРАВО"
				$to_right_disabled = "";
				if( ($current_page) == $pages_count )
				{
					$to_right_disabled = "disabled";
				}
				?>
				<a class="btn btn-default <?php echo $to_right_disabled; ?>" href="<?php echo $url_for_link.($current_page+1).$search_string_get; ?>"><i class="fa fa-chevron-right"></i></a>
				<a class="btn btn-default <?php echo $to_right_disabled; ?>" href="<?php echo $url_for_link.$pages_count.$search_string_get; ?>">Последняя</a>
			</div>
			
			
			
			
			
		</div>
	</div>
	
	<?php
	
}


// формируем пагинацию
// $all 		= количество постов в категории (определяем количество постов в базе данных)
// $lim 		= количество постов, размещаемых на одной странице
// $prev 		= количество отображаемых ссылок до и после номера текущей страницы
// $curr_link 	= номер текущей страницы (получаем из URL)
// $curr_css 	= css-стиль для ссылки на "текущую (активную)" страницу
// $link 		= часть адреса, используемый для формирования линков на другие страницы
function pagination($all, $lim, $prev, $curr_link, $curr_css, $link)
{
	global $DP_Content;
	
	$search_string_get = "";
	if( isset($_GET["search_string"]) )
	{
		$search_string_get = "&search_string=".trim(htmlspecialchars(strip_tags($_GET['search_string'])));
	}
	
    $html = '';
	// осуществляем проверку, чтобы выводимые первая и последняя страницы
    // не вышли за границы нумерации
    $first = $curr_link - $prev;
    if ($first < 1) $first = 1;
    $last = $curr_link + $prev;
    if ($last > ceil($all/$lim)) $last = ceil($all/$lim);
	
	
	
    // начало вывода нумерации
    // выводим первую страницу
    $y = 1;
    if ($first > 1) $html .= "<li><a href='/{$DP_Content->url}?page={$y}{$search_string_get}' onclick='go_to_page({$y})'>1</a></li>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
	// $prev
    $y = $first - 1;
    if ($first > $prev) 
	{
        $html .= "<li><a href='/{$DP_Content->url}?page={$y}{$search_string_get}' onclick='go_to_page({$y})'>...</a></li>";
    } 
	else 
	{
        for($i = 2;$i < $first;$i++)
		{
            $html .=  "<li><a href='/{$DP_Content->url}?page={$y}{$search_string_get}' onclick='go_to_page({$y})'>$i</a></li>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) 
		{
			$html .= '<li class="'.$curr_css.'"><a href="javascript:void(0);">'. $i .'</a></li>';
        } 
		else 
		{
            $alink = "<li><a href='/{$DP_Content->url}?page={$i}{$search_string_get}' onclick='go_to_page(";
            if($i != 1) $alink .= "{$i}";
            $alink .= ")'>$i</a></li>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < ceil($all / $lim) && ceil($all / $lim) - $last > 2) $html .=  "<li><a href='/{$DP_Content->url}?page={$y}' onclick='go_to_page({$y})'>...</a></li>";
    // выводим последнюю страницу
    $e = ceil($all / $lim);
    if ($last < ceil($all / $lim)) $html .=  "<li><a href='/{$DP_Content->url}?page={$e}{$search_string_get}' onclick='go_to_page({$e})'>$e</a></li>";
	
	return $html;
}
?>





<?php
//Функция добавления в корзину
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/common_add_to_basket.php");
?>