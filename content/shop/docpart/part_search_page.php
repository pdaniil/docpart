<?php
//Скрипт страницы отображения результатов поиска товаров по артикулу
defined('_ASTEXE_') or die('No access');



//Если был переход по старому варианту - делаем перенаправление на новый. Чтобы отключить новый вариант - достаточно только $DP_Config->chpu_search_config["chpu_search_on"] установить в false.
if( $DP_Config->chpu_search_config["chpu_search_on"] == true )
{
	if( isset($_GET["article"]) )
	{
		?>
		<script>
			location = "/<?php echo $DP_Config->chpu_search_config["level_1"]["url"]; ?>/<?php echo $DP_Config->chpu_search_config["level_2"]["mode_1"]["url"]; ?>/<?php echo $_GET["article"]; ?>";
		</script>
		<?php
		exit;
	}
}



// ------------------------------------------------------------------------------------------------
//Получаем артикул:
if( isset($DP_Content->service_data["article"]) )
{
	$article_input = $DP_Content->service_data["article"];
}
else
{
	$article_input = $_GET["article"];//Для сохранения совместимости со старым вариантом
}


//Если был передан производитель черех $_GET (только для старого варианта) - кодируем его. Если это ЧПУ-проценка (новый вариант), то, $_GET["brend"] ниже переинициализируется из переменной $manufacturer, которая уже закодирована
if( isset($_GET["brend"]) )
{
	$_GET["brend"] = htmlentities($_GET["brend"]);
}


//Тип поиска
$search_type = "no_chpu";//По умолчанию тип поиск - без ЧПУ, т.е. старый вариант
if( isset($DP_Content->service_data["search_type"]) )
{
	//ЧПУ-поиск. Могут быть варианты: all_brands_by_article и prices_by_article_and_manufacturer
	$search_type = $DP_Content->service_data["search_type"];
}
//Производитель (при ЧПУ-поиске, если это второй шаг)
if( $search_type == 'prices_by_article_and_manufacturer' )
{
	//Производитель из URL
	$manufacturer = $DP_Content->service_data["manufacturer"];
	
	$use_selected_manufacturer = false;//Флаг - используем выбранного производителя из опций пользователя. В противном случае в переменную $_GET["brend"] подставляем производителя из URL - тогда алгоритм будет выполняться по старому варианту с аргументом $_GET["brend"], т.е. получение списка производителей и затем автоматический выбор.
	
	//Если опций пользователя нет в ЧПУ-втором шаге, это значит, что был переход на эту страницу по прямой ссылке.
	
	/*
	Далее - необходимо получить опции пользователя с выбором производителя.
	Если опции есть, то, делать запрос производителей уже не надо. Делаем сразу опрос поставщиков по ценам.
	Если опций нет, то дальнейшее выполнение скрипта полностью аналогично старому варианту с переданным аргументом $_GET["brend"], т.е. получение списка производителей и автоматический выбор одного из них, если такой есть в списке.*/
	$selected_manufacturer = DP_User::get_user_option_by_key("selected_manufacturer");
	if(!$selected_manufacturer)
	{
		$_GET["brend"] = $manufacturer;
	}
	else
	{
		//Есть какие-то опции - нужно проверить, соответствуют ли они производителю из URL.
		$selected_manufacturer = json_decode($selected_manufacturer, true);
		
		//Если 10 минут еще не истекли
		if( ( time() - (int)$selected_manufacturer["time"] ) < 600 )
		{
			//Если производитель в опциях соответствует тому, что указан в URL
			if( strtolower($selected_manufacturer["SelectedManufacturer"]) == strtolower(html_entity_decode($manufacturer, ENT_QUOTES | ENT_XML1, 'UTF-8')) )
			{
				$use_selected_manufacturer = true;//Используем производителя из опций пользователя, т.е. опрос поставщиков для получения списка производителей уже делать НЕ НАДО (все необходимые JavaScript-переменные будут инициализированы из опций пользователя)
				
				//И, удаляем опцию пользователя (на тот случай, если он в адресной строке заменит только артикул, а производителя оставит - тогда опция уже будет некорректна). Да и БД не будет переполняться
				DP_User::delete_user_option("selected_manufacturer");
			}
			else
			{
				$_GET["brend"] = $manufacturer;
			}
		}
		else
		{
			$_GET["brend"] = $manufacturer;
		}
	}
}


// ------------------------------------------------------------------------------------------------








//Запрашиваемый артикул
$sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");
$article = str_replace($sweep,"", $article_input);
$article = strtoupper($article);

// Поиск по наименованию в каталоге и прайс листах
$name_search_enabled = true;// Настройка поиска: включен / выключен

if($name_search_enabled)
{
	$searsch_str = trim(strip_tags($article_input));
}

// ПИШЕМ СТАТИСТИКУ ЗАПРОСОВ
//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();//ID пользователя

//Запись статистики перенесена в скрипт ajax_asynchron.php
//$insert_query = $db_link->prepare('INSERT INTO `shop_stat_article_queries` (`article`, `ip`, `user_id`, `time`) VALUES (?, ?, ?, ?);');
//$insert_query->execute( array(htmlentities($article), $_SERVER["REMOTE_ADDR"], $user_id, time()) );



/* ********************************* */
/*									 */
/*  НАСТРОЙКА ФИЛЬТРА И ОТОБРАЖЕНИЯ  */
/*									 */
/* ********************************* */

/* Ограничение количества отображаемых позиций (10, 20, 50) */
if( ! isset($_COOKIE["cnt_on_page_settings"]) )
{
	$_COOKIE["cnt_on_page_settings"] = 0;
}
$cnt_on_page_settings = (int)$_COOKIE["cnt_on_page_settings"];
if(empty($cnt_on_page_settings))
{
	$cnt_on_page_settings = 10;// Начальное значение 
}

// Начальное положение фильтра (1 - развернут, 0 - свернут)
$initial_position_filter = 1;

// Отображать строку поиска (1 - да, 0 - нет)
$initial_position_search = 1;

/* ********************************* */



//Получаем данные по валюте магазина
$currency_query = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;');
$currency_query->execute( array($DP_Config->shop_currency) );
$currency_record = $currency_query->fetch();
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no"){$currency_indicator = "";}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after"){$currency_indicator = $currency_sign;}else{$currency_indicator = $currency_record["caption_short"];}

?>














<?php
//Формирум объект описания точек выдачи и складов
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");//Получили $customer_offices

//var_dump($customer_offices);

$office_storage_bunches = array();//Список всех связок всех офисов обслуживания со своими складами. По этому списку будет осуществляться опрос складов

$office_storage_bunches_prices = array();//Такой же точно массив, только для складов типа Docpart-Price - для возможности из одновременного запроса

//Для каждого магазина получить список складов (не Treelax складов) и опросить каждый склад
for($i=0; $i < count($customer_offices); $i++)
{
    $offices_storages_map[$customer_offices[$i]] = array();//ID точки обслуживания => список складов
    
    //Получаем список складов для данной точки обслуживания у которых product_type = 2 (т.е. автозапчасти)    
	$storages_query = $db_link->prepare('SELECT DISTINCT(storage_id) AS storage_id, (SELECT `handler_folder` FROM `shop_storages_interfaces_types` WHERE `id` = (SELECT `interface_type` FROM `shop_storages` WHERE `id` = `shop_offices_storages_map`.`storage_id`) ) AS `handler_folder` FROM shop_offices_storages_map WHERE office_id = ? AND storage_id IN (SELECT id FROM shop_storages WHERE interface_type > 1);');
	$storages_query->execute( array($customer_offices[$i]) );
    while( $storage = $storages_query->fetch() )
    {
		//Определяем версию протокола (1 шаг/2 шага)
		$protocol_version = 1;//По умолчанию
		//Если в папке обработчика присутствует скрипт get_manufacturers.php, значит версия протокола - 2 шаговый
		if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/suppliers_handlers/".$storage["handler_folder"]."/get_manufacturers.php") )
		{
			$protocol_version = 2;
		}
		
		//Добавляем связку только, если склад не прайсовый
		if($storage["handler_folder"] != "prices")
		{
			
			// Определим склад каталога товаров
			$treelax_catalogue = false;
			if($storage["handler_folder"] === 'treelax_catalogue'){
				$treelax_catalogue = true;
			}
			
			//API-поставщиков добавляем в основной список
			array_push($office_storage_bunches, array("office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0, "treelax_catalogue" => $treelax_catalogue));
		}
		else
		{
			//Прайсовых поставщиков добавляем во вспомогательный список
			array_push($office_storage_bunches_prices, array("office_id"=>(int)$customer_offices[$i], "storage_id"=>(int)$storage["storage_id"], "sent" => 0, "protocol_version"=>$protocol_version, "manufacturers_sent" => 0));
		}
    }
	
	//После наполнения списка связок, вспомогательный список для прайсовых поствщиков добавляем первым элементом в основной список - для того, чтобы сначала опросить прайс-листы
	/*
	Версия протокола - ставим 3
	Добавляем еще один параметр office_storage_bunches - используется на сервере для понимания, какие связки складов и магазинов опросить
	*/
	if( count($office_storage_bunches_prices) > 0 )
	{
		array_unshift($office_storage_bunches, array("office_id"=>0, "storage_id"=>0, "sent" => 0, "protocol_version"=>3, "manufacturers_sent" => 0, "office_storage_bunches"=>$office_storage_bunches_prices) );
		
		//Обнуляем массив для следующей итерации офисов
		$office_storage_bunches_prices = array();
	}
	
}
?>
<script>
// Функция вывода логов в консоле
function log(text){
	var log_enabled = true;// Включены ли логи
	if(log_enabled){
		console.log(text);
	}
}

var office_storage_bunches = JSON.parse('<?php echo json_encode($office_storage_bunches); ?>');
log('Список складов:');
log(office_storage_bunches);
log('');
</script>


















<?php
/* НАСТРОЙКИ РАБОТЫ АСИНХРОННОГО ОПРОСА ПОСТАВЩИКОВ */

// Получаем список групп складов
$storages_groups = array();

if(!empty($office_storage_bunches)){
	
	// Склады первой группы - то что в базе сайта: прайс листы, каталог товаров
	$storages_arr = array();
	foreach($office_storage_bunches as $item_bunches){
		// Если прайс или treelax_catalogue
		if($item_bunches['protocol_version'] == 3 || $item_bunches['treelax_catalogue'] == true){
			$storages_arr[] = $item_bunches;
		}
	}
	if(!empty($storages_arr)){
		$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
	}
	
	
	
	// Склады пользовательских групп
	$query = $db_link->prepare('SELECT * FROM `shop_storages_groups` ORDER BY `order`;');
	$query->execute();
	while($record = $query->fetch()){
		$storages = explode(',', $record['storages']);
		$storages_arr = array();
		if(!empty($storages)){
			foreach($storages as $storage_id){
				$storage_id = (int) trim($storage_id);
				foreach($office_storage_bunches as $item_bunches){
					// Если id склада есть в группе
					if($item_bunches['storage_id'] == $storage_id){
						$storages_arr[] = $item_bunches;
						break 1;// Выходим на один уровень
					}
				}
			}
		}
		if(!empty($storages_arr)){
			$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
		}
	}

	
	
	// Заполняем последнею группу, в которой будут оставшиеся склады
	$storages_arr = array();
	foreach($office_storage_bunches as $item_bunches){
		if($item_bunches['storage_id'] == 0 || $item_bunches['treelax_catalogue'] == true){
			continue;// Пропускаем потому что эти склады находятся в 0 группе
		}
		
		$storage_id = $item_bunches['storage_id'];
		$flag_none_group = true;
		
		// Ищем id склада в группах
		foreach($storages_groups as $item_group){
			if(!empty($item_group['storages'])){
				foreach($item_group['storages'] as $this_bunches){
					if($this_bunches['storage_id'] == $storage_id){
						$flag_none_group = false;// Склад уже добавлен в группу
						break 2;// Выходим из обоих циклов
					}
				}
			}
		}
		
		// Если id склада нет в группах
		if($flag_none_group == true){
			$storages_arr[] = $item_bunches;
		}
	}
	if(!empty($storages_arr)){
		// Если вне групп слишком много складов то разабьем их на несколько групп
		$cnt = count($storages_arr);
		$cnt_group = 15;// Максимальное количество складов в группе
		if($cnt > $cnt_group){
			$n = 0;
			$k = 0;
			$storages_arr_tmp = array();
			for($i = 0; $i < $cnt; $i++){
				$storages_arr_tmp[$k][] = $storages_arr[$i];
				$n++;
				if($n == $cnt_group){
					$n = 0;
					$k++;
				}
			}
			if(!empty($storages_arr_tmp)){
				foreach($storages_arr_tmp as $storages_arr){
					$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
				}
			}
		}else{
			$storages_groups[] = array('storages' => $storages_arr, 'sent' => 0, 'manufacturers_sent' => 0);
		}
	}
}


?>
<script>
// Список групп складов
var storages_groups = JSON.parse('<?=json_encode($storages_groups);?>');

log('Список групп складов:');
log(storages_groups);
log('');

// ----------------------------------------------------------------------------------------------------------------------------------------------------
//ОБЪЕКТ ДЛЯ ХРАНЕНИЯ СПИСКА ВОЗМОЖНЫХ ВАРИАНТОВ ТОВАРОВ
var ProductsManufacturers = new Array();//Все варианты всех поставщиков
var ProductsManufacturers_Shown = new Object();//Флаги отображенных
var ProductsManufacturers_Shown_Count = 0;//Количество отображенных производителей в таблице

var ProductsManufacturers_All_Asked = false;//Флаг - обозначает, что все поставщики с типом протокола 2 опрошены

<?php
//Если это ЧПУ-второй шаг, и в опциях пользователя есть соответствующий выбранный производитель
if( isset($use_selected_manufacturer) )
{
	if( $use_selected_manufacturer )
	{
		?>
		ProductsManufacturers = <?php echo json_encode($selected_manufacturer["ProductsManufacturers"]); ?>;
		ProductsManufacturers_Shown = <?php echo json_encode($selected_manufacturer["ProductsManufacturers_Shown"]); ?>;
		ProductsManufacturers_Shown_Count = <?php echo $selected_manufacturer["ProductsManufacturers_Shown_Count"]; ?>;
		ProductsManufacturers_All_Asked = true;
		<?php
	}
}
?>

// ----------------------------------------------------------------------------------------------------------------------------------------------------
//Теперь необходимо предоставить покупателю список производителей, у которых встречается данный артикул
var manufacturersListFromPrices = false;//Флаг - Список производителей запросили из прайсов
var has_crosses_query = false;//Флаг - Список производителей запросили с сервера кроссов

function getManufacturersList()
{
	var request_storages = new Array();// Список складов для запроса к скрипту ajax_asynchron.php
	
	if(ProductsManufacturers_All_Asked == false){
		
		// Если не все склады опрошены
		
		// Цикл по группам складов
		for(var g=0; g < storages_groups.length; g++)
		{
			if(storages_groups[g]['manufacturers_sent'] == 1)
			{
				continue;// Опрошенная группа - пропускаем
			}
				
			// Цикл по списку складов в группе
			for(var i=0; i < storages_groups[g]['storages'].length; i++)
			{
				//Поставщика с версией протокола 1 - пропускаем
				if(storages_groups[g]['storages'][i].protocol_version === 1)
				{
					continue;
				}
				
				// Добавляем склад в запрос
				request_storages.push(storages_groups[g]['storages'][i]);
			}
			
			// Если работаем с последней группой складов то в нее нужно добавить опрос сервера кроссов
			if(g == (storages_groups.length -1)){
				if( !has_crosses_query )
				{
					has_crosses_query = true;//Опросили.
					var server = new Object;
					server.protocol_version = 'server';
					request_storages.push(server);
				}
				
				// Флаг - все склады опрошены
				ProductsManufacturers_All_Asked = true;
			}
			
			storages_groups[g]['manufacturers_sent'] = 1;// Отмечаем что группа опрошена и выходим из цикла
			break;
		}
		
		var beforeRequestTime =  new Date().getTime();// Начальное время перед запросом

		// Если есть данные для запроса
		if(request_storages.length > 0){
			
			var request_object = new Object;
				request_object.action = 'get_manufacturers';
				request_object.article = '<?=$article;?>';
				request_object.storages = request_storages;
			
			log('Запрос производителей - группа '+ g +':');
			log(request_object);
			
			jQuery.ajax({
				type: "POST",
				async: true, //Запрос асинхронный
				url: "/content/shop/docpart/ajax_asynchron.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURIComponent(JSON.stringify(request_object)),
				success: function(answer)
				{
					log('Результат группы '+ g +':');
					log(answer);
					
					var afterReuestTime = new Date().getTime();
					var issue = (afterReuestTime - beforeRequestTime)/1000;
					
					log("Время выполнения запроса - " + issue + "c");
					log('');
					
					if(answer.result == 1)//Запрос выполнен успешно
					{
						if(answer.data.length > 0){
							for(var i = 0; i < answer.data.length; i++){
								if(answer.data[i] != null){
									addManufacturersToList(answer.data[i].ProductsManufacturers);//Добавляем результат в общий объект
								}
							}
						}
						manufacturersReview();//Переотображаем таблицу производителей
					}
					
					getManufacturersList();//Делаем следующий запрос
					return;
				},
				error: function (e, ajaxOptions, thrownError){
					log('Результат группы '+ g +':');
					log('Ошибка: '+ e.status +' - '+ thrownError);
					log('');
					log('');
					
					getManufacturersList();//Делаем следующий запрос
					return;
				}
			});
		}else{
			// В группе для опроса были только склады с 1 типом интерфейса поэтому объект пуст и нужно перейти к опросу следующей группы
			getManufacturersList();//Делаем следующий запрос
			return;
		}
		
	}else{
		
		// Все склады опрошены
		
		
		
		// brend **********************************************************
		<?php
		// Если передан бренд то выбираем его автоматически
		if( ! empty($_GET["brend"]) ){
			
			$brend = urldecode($_GET["brend"]);
			$brend = str_replace('"',"'",$brend);
			$brend = trim($brend);
			$brend = mb_strtoupper($brend, "UTF-8");
			
			echo 'var synonym_brend = "";';// Переменная в которой будет наименование бренда как оно должно отображаться на сайте из таблицы синонимов
			
			// Находим если есть правильное наименование бренда из таблицы синонимов по переданному в запросе наименованию
			$synonym_query = $db_link->prepare('SELECT `name` FROM `shop_docpart_manufacturers` WHERE `id` = (SELECT `manufacturer_id` FROM `shop_docpart_manufacturers_synonyms` WHERE `synonym` = ? LIMIT 1);');
			$synonym_query->execute( array( html_entity_decode($brend) ) );
			$synonym_record = $synonym_query->fetch();
			if( $synonym_record != false )
			{
				$synonym_record["name"] = str_replace('"',"'",$synonym_record["name"]);
				echo 'synonym_brend = "'. strtoupper($synonym_record["name"]) .'";';
			}
			
		?>
		var brend =  $('<textarea />').html('<?=$brend;?>').text();
		var ManufacturerSelected_tmp = null;
		for(var i=0; i < ProductsManufacturers.length; i++)
		{
			if(ProductsManufacturers[i].manufacturer_show == brend || ProductsManufacturers[i].manufacturer_show == synonym_brend){
				ManufacturerSelected_tmp = ProductsManufacturers[i].manufacturer_show;
				break;
			}else if(ProductsManufacturers[i].manufacturer == brend){
				ManufacturerSelected_tmp = ProductsManufacturers[i].manufacturer_show;
				break;
			}
		}
		if(ManufacturerSelected_tmp != null){
			onManufacturerSelected(ManufacturerSelected_tmp);
		}else{
			document.getElementById("processing_indicator").innerHTML = "<br/><p style='background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 5px; color: #000;'><i class='fa fa-info-circle'></i> Мы не нашли совпадения в наименовании производителей. Выберите нужного производителя из таблицы.</p><br/>";
			
			if(ProductsManufacturers.length == 0)
			{
				onManufacturerSelected(null);//Указываем его в качестве выбранного
			}else{
				manufacturersReview();
				document.getElementById('table-manufacturers').style.display = 'block';
			}
		}
		
		return;
		<?php
		}
		?>
		//*****************************************************************
		
		
		
		if(ProductsManufacturers.length == 0)
		{
			//Не найден ни один производитель - просто запускаем опрос всех связок, чтобы опросить поставщиков с типом протокола не 2
			onManufacturerSelected(null);//Указываем его в качестве выбранного
		}
		else if(ProductsManufacturers_Shown_Count == 1)//Есть только один производитель (таблицу не показываем)
		{
			onManufacturerSelected(ProductsManufacturers[0].manufacturer_show);//Указываем его в качестве выбранного
		}
		else//Найдено более 1 производителя - отображается таблица и покупатель выбирает из нее
		{
			document.getElementById("processing_indicator").innerHTML = "<br/>";//Просто убираем индикатор загрузки
		}
	}
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------
//Добавления списка производителей после опроса каждого поставщика
function addManufacturersToList(products_manufacturers)
{
	for(var i=0; i < products_manufacturers.length; i++)
	{
		if(products_manufacturers[i]['manufacturer'] != null){
			ProductsManufacturers.push(products_manufacturers[i]);
		}
	}
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------
//Переотображение таблицы производителей после опроса каждого поставщика
function manufacturersReview()
{
	if(ProductsManufacturers.length == 0)
	{
		return;
	}

	ProductsManufacturers_Shown = new Object();//Сбрасываем массив отображенных
	ProductsManufacturers_Shown_Count = 0;//Количество показанных производителей
	var not_name_text = 'Наименование не указано поставщиком';
	
	
	
	// brend **********************************************************
	<?php
	// Если передан бренд то скрываем таблицу брендов от пользователя
	if( ! empty($_GET["brend"]) ){
	?>
	var html = "<div id=\"table-manufacturers\" style=\"display:none;\" class=\"table-responsive\"><table cellpadding=\"1\" cellspacing=\"1\" class=\"table table-condensed table-striped\">";
	<?php
	}else{
	?>
	var html = "<div class=\"table-responsive\"><table cellpadding=\"1\" cellspacing=\"1\" class=\"table table-condensed table-striped\">";
	<?php
	}
	?>
	//*****************************************************************
	
	
	
	html += "<thead><tr> <th>Бренд</th> <th>Артикул</th> <th>Наименование</th> <th></th> </tr></thead><tbody>";
	
	for(var i=0; i < ProductsManufacturers.length; i++)
	{
		//Если это первый такой производитель - создаем для него массив всех объектов
		if( ProductsManufacturers_Shown[ProductsManufacturers[i].manufacturer_show] == undefined )
		{
			ProductsManufacturers_Shown[ProductsManufacturers[i].manufacturer_show] = new Array();
			
			// Если у первого элемента не было указано наименование, возьмем его из последующих
			if(ProductsManufacturers[i].name == null || ProductsManufacturers[i].name == '' || ProductsManufacturers[i].name == false || ProductsManufacturers[i].name == not_name_text || ProductsManufacturers[i].name == 'Деталь')
			{
				for(var j=0; j < ProductsManufacturers.length; j++)
				{
					if(ProductsManufacturers[i].manufacturer_show === ProductsManufacturers[j].manufacturer_show){
						if(ProductsManufacturers[j].name !== null && ProductsManufacturers[j].name !== '' && ProductsManufacturers[j].name !== false && ProductsManufacturers[j].name !== not_name_text && ProductsManufacturers[j].name !== 'Деталь')
						{
							ProductsManufacturers[i].name = ProductsManufacturers[j].name;
							break;
						}
					}
				}
			}
		}
		
		//Добавляем объект (будет потом использоваться при запросах поставщикам)
		//ProductsManufacturers_Shown[ProductsManufacturers[i].manufacturer_show][ProductsManufacturers[i].storage_id] = ProductsManufacturers[i];
		ProductsManufacturers_Shown[ProductsManufacturers[i].manufacturer_show].push(ProductsManufacturers[i]);
		//Если такой уже один отобразили - пропускаем
		if( ProductsManufacturers_Shown[ProductsManufacturers[i].manufacturer_show].length > 1 )
		{
			continue;
		}
		
		var a_tag = "<a href=\"javascript:void(0);\" onclick=\"onManufacturerSelected('"+ProductsManufacturers[i].manufacturer_show.replace(/'/g,"\\'")+"'); \" >";
		
		var button_tag = "<button onclick=\"onManufacturerSelected('"+ProductsManufacturers[i].manufacturer_show.replace(/'/g,"\\'")+"'); \" type=\"button\" class=\"btn btn-ar btn-primary\">";

		if(ProductsManufacturers[i].name == null || ProductsManufacturers[i].name == '' || ProductsManufacturers[i].name == false)
		{
			ProductsManufacturers[i].name = not_name_text;
		}
		
		html += "<tr> <td>" + a_tag + ProductsManufacturers[i].manufacturer_show+"</a></td> <td>" + a_tag + "<?php echo $article; ?></a></td> <td>" + a_tag + ProductsManufacturers[i].name+"</a></td> <td>"+button_tag+"К ценам</button></td> </tr>";
		ProductsManufacturers_Shown_Count ++;//Количество показанных производителей
	}

	
	html += "</tbody></table></div>";
	
	
	// brend **********************************************************
	<?php
	if( ! empty($_GET["brend"]) ){
	?>
	//Таблицу производителей показываем если в ней есть бренды
	if( ProductsManufacturers_Shown_Count > 0 )
	{
		document.getElementById("products_area").innerHTML = html;
	}
	<?php
	}else{
	?>
	//Таблицу производителей показываем только если в ней больше 1 производителя
	if( ProductsManufacturers_Shown_Count > 1 )
	{
		document.getElementById("products_area").innerHTML = html;
	}
	<?php
	}
	?>
	//*****************************************************************
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------
//Обработка выбора производителя
var SelectedManufacturer = null;//Выбранный производитель
function onManufacturerSelected(manufacturer_show)
{	
	<?php
	/*
	Здесь в зависимости от $search_type
	*/
	//Старый вариант. И, такой же точно - ЧПУ-второй шаг. Со старым вариантом - понятно. С ЧПУ-второй шаг: если производитель записан в опции пользователя, то все JavaScript-переменные будут взяты из опций пользователя и при загрузке страницы - сразу пойдет вызов данной функции (onManufacturerSelected(manufacturer_show)). Если опций пользователя нет, то при ЧПУ-втором шаге будет сначала опрос поставщиков для получения списка производителей и затем автоматический выбор одного из них, т.е. также - вызов этой функции.
	if( $search_type == "no_chpu" || ($search_type == "prices_by_article_and_manufacturer" && $use_selected_manufacturer) )
	{
		?>
		if( ! ProductsManufacturers_All_Asked )
		{
			alert("Дождитесь полного формирования списка брендов");
			return;
		}
		
		
		SelectedManufacturer = manufacturer_show;
		
		
		document.getElementById("products_area").innerHTML = "";
		getAnalogsList();//После выбора производителя - Делаем запрос аналогов от сервера кроссов
		<?php
	}
	//ЧПУ - первый шаг. Записываем выбранного производителя в опции пользователя - По прямому выбору пользователя, либо автоматически, если производитель всего один. После того, как производитель записан в опции - следует переход на ЧПУ-второй шаг.
	else if($search_type == "all_brands_by_article" || ($search_type == "prices_by_article_and_manufacturer" && !$use_selected_manufacturer) )
	{
		?>
		if( ! ProductsManufacturers_All_Asked )
		{
			alert("Дождитесь полного формирования списка брендов");
			return;
		}
		
		SelectedManufacturer = manufacturer_show;
		
		var selected_manufacturer = new Object;
		selected_manufacturer["SelectedManufacturer"] = SelectedManufacturer;
		selected_manufacturer["ProductsManufacturers_Shown"] = ProductsManufacturers_Shown;
		selected_manufacturer["ProductsManufacturers"] = ProductsManufacturers;
		selected_manufacturer["ProductsManufacturers_Shown_Count"] = ProductsManufacturers_Shown_Count;
		selected_manufacturer["time"] = '<?php echo time(); ?>';
		
		jQuery.ajax({
			type: "POST",
			async: true, //Запрос асинхронный
			url: "/content/users/ajax_set_user_option.php",
			dataType: "json",//Тип возвращаемого значения
			data: "key=selected_manufacturer&value="+encodeURIComponent(JSON.stringify(selected_manufacturer)),
			success: function(answer)
			{
				var manufacturer_alias = SelectedManufacturer;
				if(manufacturer_alias == null)
				{
					manufacturer_alias = '<?php echo $DP_Config->chpu_search_config["level_2"]["mode_2"]["url"]; ?>';
				}
				manufacturer_alias = manufacturer_alias.split('/').join('<?php echo $DP_Config->chpu_search_config["slash_code"]; ?>');
				
				location='/<?php echo $DP_Config->chpu_search_config["level_1"]["url"]; ?>/'+manufacturer_alias+'/<?php echo $article; ?>';
			}
		});
		
		
		<?php
	}
	?>
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------
//Шаг - подбор аналогов по артикулу или по артикулу-производителю
function getAnalogsList()
{
	// Добавляем в запрос все синонимы выбранного производителя от каждого склада
	search_object.manufacturers = new Array();
	
	if(SelectedManufacturer != null){
		for(var m = 0; m < ProductsManufacturers_Shown[SelectedManufacturer].length; m++)
		{
			// Отфильтровываем строки из прайс листов, так как запрос аналогов нужно делать по производителю который был передан складом
			if(ProductsManufacturers_Shown[SelectedManufacturer][m].params !== null){
				if(ProductsManufacturers_Shown[SelectedManufacturer][m].params.type === 'prices'){
					continue;
				}
			}
			search_object.manufacturers.push(ProductsManufacturers_Shown[SelectedManufacturer][m]);//Добавляем сюда строку именно выбранную
		}
	}
	
	document.getElementById("processing_indicator").innerHTML = "<p>Отправка данных</p><img src=\"/content/files/images/ajax-loader-transparent.gif\" /><br><br>";
	
    jQuery.ajax({
        type: "POST",
        async: true, //Запрос асинхронный
        url: "/content/shop/docpart/ajax_getAnalogsList.php",
        dataType: "json",//Тип возвращаемого значения
        data: "search_object="+encodeURIComponent(JSON.stringify(search_object)),
        success: function(answer)
		{
            log('Кроссы:');
            log(answer);
            log('');
			if(answer.result == 1)//Запрос выполнен успешно
            {
                search_object.analogs = answer.analogs;
            }
            
			document.getElementById("processing_indicator").innerHTML = "<p>Опрос складов</p><img src=\"/content/files/images/ajax-loader-transparent.gif\" /><br><br>";
			
			log('-----------------------------------------------------');
			log('');
			
			startBeforeRequestTime = new Date().getTime();// Время начало опроса складов
			
			getStoragesData();//Запрос данных о наличии товаров на складах
        },
		error: function (e, ajaxOptions, thrownError){
			log('Кроссы:');
			log('Ошибка: '+ e.status +' - '+ thrownError);
			log('');
			
			document.getElementById("processing_indicator").innerHTML = "<p>Опрос складов</p><img src=\"/content/files/images/ajax-loader-transparent.gif\" /><br><br>";
			
			log('-----------------------------------------------------');
			log('');
			
			startBeforeRequestTime = new Date().getTime();// Время начало опроса складов
			
			getStoragesData();//Запрос данных о наличии товаров на складах
		}
    });
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------
var startBeforeRequestTime = 0;// Время начало опроса складов
//Запрос данных о наличии товаров на складах
function getStoragesData()
{
	var request_storages = new Array();// Список складов для запроса к скрипту ajax_asynchron.php
	
	for(var g=0; g < storages_groups.length; g++)
	{
		if(storages_groups[g]['sent'] == 1){
			continue;// Пропускаем опрошенную группу
		}
		
		// Делаем группу опрошенной
		storages_groups[g]['sent'] = 1;
		
		// Клонируем объект search_object потому что он будет у каждого склада свой
		var search_object_clone = new Object;
		for (var key in search_object) {
		  search_object_clone[key] = search_object[key];
		}
		
		for(var i=0; i < storages_groups[g]['storages'].length; i++)
		{
			search_object_clone.office_storage_bunches = new Array();
			search_object_clone.manufacturers = new Array();
			
			//Если это поставщик с версией протокола 2 - создаем для него список нужных производителей
			if( storages_groups[g]['storages'][i].protocol_version == 2 )
			{
				if( ProductsManufacturers.length == 0 )
				{
					// Если запрос производителя не дал результата то пропускаем склад если он api поставщик
					// В каталоге нужно сделать поиск так как запрос может быть по наименованию и могут найтись аналоги
					if(storages_groups[g]['storages'][i].treelax_catalogue === false){
						continue;
					}
				}
				
				if(ProductsManufacturers.length > 0){
					if(SelectedManufacturer != null){
						for(var m = 0; m < ProductsManufacturers_Shown[SelectedManufacturer].length; m++)
						{
							if( parseInt(ProductsManufacturers_Shown[SelectedManufacturer][m].storage_id) == parseInt(storages_groups[g]['storages'][i].storage_id) &&
							parseInt(ProductsManufacturers_Shown[SelectedManufacturer][m].office_id) == parseInt(storages_groups[g]['storages'][i].office_id))
							{
								search_object_clone.manufacturers.push(ProductsManufacturers_Shown[SelectedManufacturer][m]);//Добавляем сюда строку именно из API поставщика
							}
						}
					}
				}
				
				//Если список нужных производителей пуст - значит у данного поставщика нет таких производителей - пропускаем, чтобы не тратить время
				if(search_object_clone.manufacturers.length == 0)
				{
					if(storages_groups[g]['storages'][i].treelax_catalogue === false){
						continue;
					}else{
						if(SelectedManufacturer != null){
							search_object_clone.manufacturers.push(ProductsManufacturers_Shown[SelectedManufacturer][0]);
						}
					}
				}
			}
			else if(storages_groups[g]['storages'][i].protocol_version == 3)//Для прайс-листов
			{
				if(SelectedManufacturer != null){
					for(var m = 0; m < ProductsManufacturers_Shown[SelectedManufacturer].length; m++)
					{
						if( parseInt(ProductsManufacturers_Shown[SelectedManufacturer][m].storage_id) == parseInt(storages_groups[g]['storages'][i].storage_id) &&
						parseInt(ProductsManufacturers_Shown[SelectedManufacturer][m].office_id) == parseInt(storages_groups[g]['storages'][i].office_id))
						{
							// Только найденные бренды от прайсов
							if(ProductsManufacturers_Shown[SelectedManufacturer][m].params){
								if(ProductsManufacturers_Shown[SelectedManufacturer][m].params.type == 'prices'){
									search_object_clone.manufacturers.push(ProductsManufacturers_Shown[SelectedManufacturer][m]);//Добавляем сюда строку именно из прайса
								}
							}
						}
					}
					// Добавляем хоть что то что бы в прайсах не было поиска только по артикулу, так как возможно что то найдется в аналогах.
					if(search_object_clone.manufacturers.length < 1){
						search_object_clone.manufacturers.push(ProductsManufacturers_Shown[SelectedManufacturer][0]);
					}
				}
				
				//В объект запроса добавляем связки складов и магазинов для прайс-листов
				search_object_clone.office_storage_bunches = storages_groups[g]['storages'][i].office_storage_bunches;
			}
			
			// Убираем из запроса аналоги для складов которым они не нужных
			if(storages_groups[g]['storages'][i].protocol_version != 3 && storages_groups[g]['storages'][i].treelax_catalogue === false){
				search_object_clone.analogs = new Array();
			}
			
			// Добавляем к складу список его производителей
			storages_groups[g]['storages'][i]['search_object'] = new Object;
			for (var key in search_object_clone) {
			  storages_groups[g]['storages'][i]['search_object'][key] = search_object_clone[key];
			}
			
			// Добавляем склад в запрос
			request_storages.push(storages_groups[g]['storages'][i]);
		}
		
		// Делаем запрос
		
		// Если есть данные для запроса
		if(request_storages.length > 0){
			
			var request_object = new Object;
				request_object.action = 'get_articles';
				request_object.article = '<?=$article;?>';
				request_object.storages = request_storages;
			
			
			log('Запрос артикула - группа '+ g +':');
			log(request_object);
			
			var beforeRequestTime =  new Date().getTime();
			
			jQuery.ajax({
				type: "POST",
				async: true, //Запрос асинхронный
				url: "/content/shop/docpart/ajax_asynchron.php",
				dataType: "json",//Тип возвращаемого значения
				data: "request_object="+encodeURIComponent(JSON.stringify(request_object)),
				success: function(answer)
				{
					log('Результат группы '+ g +':');
					log(answer);

					var afterReuestTime = new Date().getTime();
					var issue = (afterReuestTime - beforeRequestTime)/1000;

					log("Время выполнения запроса - " + issue + "c");
					log('');
					
					if(answer.result == 1)//Запрос выполнен успешно
					{
						if(answer.data){
							if(answer.data.length > 0){
								var fflag = false;
								for(var i = 0; i < answer.data.length; i++){
									if(answer.data[i] != null){
										if(answer.data[i].Products.length > 0){
											fflag = true;
											bindBunchResult(answer.data[i]);//Добавляем полученный ответ к общему объекту описания
										}
									}
								}
								if(fflag == true){
									// Переотображаем проценку
									onGetStoragesData();
								}
							}
						}
					}
					
					getStoragesData();//Делаем следующий запрос
					return;
				},
				error: function (e, ajaxOptions, thrownError){
					log('Результат группы '+ g +':');
					log('Ошибка: '+ e.status +' - '+ thrownError);
					log('');
					log('');
					
					getStoragesData();//Делаем следующий запрос
					return;
				}
			});
		}else{
			// В группе для опроса не было складов - все склады были типа 2 и для них не было производителей, поэтому их все пропустили
			log('Запрос артикула - группа '+ g +':');
			log(request_storages);
			log('Результат группы '+ g +':');
			log('Группа пропущена по причине отсутствия производителей');
			log('');
			log('');
			getStoragesData();//Делаем следующий запрос
			return;
		}
		
		// Выходим из функции
		return;
	}

	// Цикл полностью выполнился - значит неопрошенных связок не осталось - обозначаем, что все данные загружены
	if(Products.All.length == 0)
	{
		document.getElementById("processing_indicator").innerHTML = "<p>Товары не найдены</p>";
	}
	else
	{
		// Все данные загружены - Просто убираем индикатор загрузки
		document.getElementById("processing_indicator").innerHTML = "";
	}
	
	var afterReuestTime = new Date().getTime();
	var issue = (afterReuestTime - startBeforeRequestTime)/1000;
	log('-----------------------------------------------------');
	log('');
	log("Общее время проценки - " + issue + "c");
	log('');
}
// ---------------------------------------------------------------------------------------------------------------------
</script>


















<?php
// Поиск отображается только в мобильной версии, нужен что бы отобразить поиск выше фильтра.
if($initial_position_search == 1){
	$value_for_input_search = str_replace('"','',$value_for_input_search);
?>
<div class="hidden-md hidden-lg col-md-12 search_limo">
	<div class="panel panel-primary">
		<div class="panel-heading"><i class="fa fa-search" aria-hidden="true"></i> Поиск по артикулу</div>
		<div style="position:relative;" class="panel-body">
			<form role="form" action="/shop/part_search" method="GET">
				<div class="input-group">
					<input value="<?php echo $value_for_input_search; ?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
					<span class="input-group-btn">
						<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
					</span>
				</div>
			</form>
		</div>
	</div>
</div>
<?php
}
?>




<!----- ФИЛЬТР ------>
<?php
// Стили блока фильтра и отображения проценки
if($initial_position_filter == 1){
	$filter_div_a_text = '<i class="fa fa-arrow-circle-up" aria-hidden="true"></i> Свернуть фильтр';
	$initial_position_filter = 0;// Нужно для того что бы при первом отображении правильно сработало
}else{
	$filter_div_a_text = '<i class="fa fa-arrow-circle-down" aria-hidden="true"></i> Развернуть фильтр';
	$initial_position_filter = 1;// Нужно для того что бы при первом отображении правильно сработало
}

// Стили блоков при отображении или скрытии поиска
if($initial_position_search == 1){
	// Отображается поиск
	$filter_div_style_body = '';
	$filter_div_class = 'col-md-3';
}else{
	// скрыт поиск
	$filter_div_class = 'hidden';
	$filter_div_style_body = ' display:none;';
}
?>
<script>
// Свернут или развернут фильтр
var this_position_filter = <?=$initial_position_filter;?>;

// Отображается ли поиск
var this_position_search = <?=$initial_position_search;?>;

// Разворачиваем либо сворачиваем фильтр в зависимости от текущего положения.
function show_filter_clicked(){
	
	var filter_div = document.getElementById('filter_div');
	var filter_position = document.getElementById('filter_position');
	var filter_div_a_text = document.getElementById('filter_div_a_text');
	var filter_div_style_body = document.getElementById('filter_div_style_body');
	
	var procenka_div = document.getElementById('procenka_div');
	
	filter_div.classList.remove('hidden');
	
	if(this_position_filter == 1){
		// Скрываем фильтр
		filter_position.style.display = 'none';
		this_position_filter = 0;
		document.getElementById('footer-filter').style.display = 'none';
		document.getElementById('footer_filter_reset').style.display = 'none';
		
		if(this_position_search == 0){
			// Поиск скрыт
			filter_div.classList.remove('col-md-3');
			filter_div.classList.add('col-md-12');
			
			filter_div_style_body.style.display = 'none';
		}
		
		procenka_div.classList.remove('col-md-9');
		procenka_div.classList.add('col-xs-12');
		
		
		filter_div_a_text.innerHTML = '<i class="fa fa-arrow-circle-down" aria-hidden="true"></i> Развернуть фильтр';
	}else{
		// отображаем фильтр
		filter_position.style.display = 'block';
		this_position_filter = 1;
		document.getElementById('footer-filter').style.display = 'block';
		document.getElementById('footer_filter_reset').style.display = 'block';
		
		if(this_position_search == 0){
			// Поиск скрыт
			filter_div.classList.remove('col-md-12');
			filter_div.classList.add('col-md-3');
			
			filter_div_style_body.style.display = 'block';
		}
		
		
		procenka_div.classList.remove('col-xs-12');
		procenka_div.classList.add('col-md-9');
		
		filter_div_style_body = '';
		filter_div_a_text.innerHTML = '<i class="fa fa-arrow-circle-up" aria-hidden="true"></i> Свернуть фильтр';
	}
}
</script>
<!----- Блока фильтра ------>
<div id="filter_div" class="<?=$filter_div_class;?>">
	<div class="panel panel-primary">
		<div class="panel-heading" style="position:relative;">
			<a id="filter_div_a_text" href="javascript:void(0);" onclick="show_filter_clicked();"><?=$filter_div_a_text;?></a>
		</div>
		<div id="filter_div_style_body" style="position:relative;<?=$filter_div_style_body;?>" class="panel-body">
			
			
			<?php
				//Определяем формат вывода таблицы
				if($DP_Config->products_table_mode == 0)//На выбор покупателя
				{
					//Покупатель ранее ставил куки
					if( !empty($_COOKIE["products_table_mode"]) )
					{
						$table_mode = $_COOKIE["products_table_mode"];
					}
					else//Покупатель еще не ставил куки - отображаем по умолчанию
					{
						$table_mode = 1;
					}
					//Отображаем возможность для настройки покупателем
					$products_table_mode_query = $db_link->prepare('SELECT `options` FROM `config_items` WHERE `name` = ?;');
					$products_table_mode_query->execute( array('products_table_mode') );
					$products_table_mode_record = $products_table_mode_query->fetch();
					$modes = json_decode($products_table_mode_record["options"], true);
					?>
						<div class="input-group" style="width:100%;">
							<select id="products_table_mode_select" onchange="on_products_table_mode_selected();" class="form-control" style="max-height:34px; width:100%;">
							<?php
							for($i=0; $i < count($modes); $i++)
							{
								if($modes[$i]["value"] == 0)continue;//Этот пункт "На выбор покупателя" - пропускаем
								
								?>
								<option value="<?php echo $modes[$i]["value"]; ?>"><?php echo $modes[$i]["caption"]; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<script>
						//Ставим текущий способ отображения:
						document.getElementById("products_table_mode_select").value = '<?php echo $table_mode; ?>';
						
						//Обрабобка селектора
						function on_products_table_mode_selected()
						{
							//Выбанный способ отображения
							var products_table_mode = document.getElementById("products_table_mode_select").value;
							
							//Устанавливаем cookie (на полгода)
							var date = new Date(new Date().getTime() + 15552000 * 1000);
							document.cookie = "products_table_mode="+products_table_mode+"; path=/; expires=" + date.toUTCString();
							
							//Обновляем страницу
							location.reload();
						}
						</script>
					<?php
				}
				else//Указано менеджером
				{
					$table_mode = $DP_Config->products_table_mode;
					
					// Если отображается поиск выравниваем блоки
					if($initial_position_search == 1){
						//Отображаем возможность для настройки покупателем
						$products_table_mode_query = $db_link->prepare('SELECT `options` FROM `config_items` WHERE `name` = ?;');
						$products_table_mode_query->execute( array('products_table_mode') );
						$products_table_mode_record = $products_table_mode_query->fetch();
						$modes = json_decode($products_table_mode_record["options"], true);
						// Для того что бы блок был такой же по высоте что и поиска
					?>
						<div class="input-group" style="width:100%;">
							<?php
								for($i=0; $i < count($modes); $i++)
								{
									if($modes[$i]["value"] != $table_mode)continue;
									?>
									<input disabled style="width:100%; background:#fff;" title="Установлено администратором" value="<?php echo $modes[$i]["caption"]; ?>" type="text" class="form-control"/>
									<?php
								}
							?>
						</div>
					<?php
					}
				}
			?>
			
			<style>hr{margin:15px 0px;}</style>
			<div id="filter_position"></div>
		</div>
		<div id="footer_filter_reset"></div>
		<div id="footer-filter"></div>
	</div>
</div>



<?php
if($initial_position_search == 1){
	$value_for_input_search = str_replace('"','',$value_for_input_search);
?>
<div class="hidden-xs hidden-sm col-md-9 search_limo">
	<div class="panel panel-primary">
		<div class="panel-heading"><i class="fa fa-search" aria-hidden="true"></i> Поиск по артикулу</div>
		<div style="position:relative;" class="panel-body">
			<form role="form" action="/shop/part_search" method="GET">
				<div class="input-group">
					<input value="<?php echo $value_for_input_search; ?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
					<span class="input-group-btn">
						<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
					</span>
				</div>
			</form>
		</div>
	</div>
</div>
<?php
}
?>


<!----- Открытие блока проценки ------>
<div id="procenka_div" class="col-xs-12">












<?php
/* ************************************* */
/* ********    ФИЛЬТР ПОЗИЦИЙ    ******* */
/* ************************************* */

// Выберем в массив названия складов для отображения их в фильтре по складами
$storages_query = $db_link->prepare('SELECT `id`, `short_name` AS `name` FROM `shop_storages`;');
$storages_query->execute();
$all_storages = array();
while( $storage = $storages_query->fetch() )
{
	$all_storages[$storage['id']] = $storage['name'];
}
?>
<script>





// Флаг будет сообщать о том что произошла первоначальная загрузка страницы
var flag_first_loading = true;

// Все склады
var all_storages = JSON.parse('<?php echo json_encode($all_storages); ?>');

var this_filter = '';// Какой именно фильтр выбран

var sam_price = 0;// Самые дешевые
var sam_time = 0;// Самые быстрые поставки
var sam_price_time = '';//Какая из кнопок была выбрана первой sam_price или sam_time

// Бренды
var arr_manufacturers =  new Array();
// Найденные бренды после фильтрации
var arr_manufacturers_posle_filter =  new Array();

// Склады
var arr_storages = new Array();
var arr_storages_posle_filter =  new Array();
// Цвета складов
var arr_storages_color =  new Array();

// Свойства фильтра
var filter =  new Array();

var list_brend_show = false;// Флаг - был ли открыт список производителей перед обновлением фильтра
var list_storages_show = false;// Флаг - был ли открыт список складов перед обновлением фильтра


// Цена
filter['price_blok'] = new Object;
filter['price_blok'].show = 1;// включен или нет
filter['price_blok'].caption = 'Цена товара';
filter['price_blok'].property_type_id = 2;
filter['price_blok'].property_id = 'price';
filter['price_blok'].min_value = undefined;
filter['price_blok'].max_value = undefined;

// Срок
filter['time_to_exe_blok'] = new Object;
filter['time_to_exe_blok'].show = 1;
filter['time_to_exe_blok'].caption = 'Срок поставки, дн.';
filter['time_to_exe_blok'].property_type_id = 2;
filter['time_to_exe_blok'].property_id = 'time_to_exe';
filter['time_to_exe_blok'].min_value = undefined;
filter['time_to_exe_blok'].max_value = undefined;

// Наличие
filter['exist_blok'] = new Object;
filter['exist_blok'].show = 1;
filter['exist_blok'].caption = 'Наличие, шт.';
filter['exist_blok'].property_type_id = 2;
filter['exist_blok'].property_id = 'exist';
filter['exist_blok'].min_value = undefined;
filter['exist_blok'].max_value = undefined;

// Бренды
filter['manufacturer_blok'] = new Object;
filter['manufacturer_blok'].show = 1;
filter['manufacturer_blok'].caption = 'Производитель';
filter['manufacturer_blok'].property_id = 'manufacturer';
filter['manufacturer_blok'].property_type_id = 5;
filter['manufacturer_blok'].list_type = 1;
filter['manufacturer_blok'].list_options = new Array;
filter['manufacturer_blok'].manufacturer_in_filter = new Array;

// Склады
filter['storages_blok'] = new Object;
filter['storages_blok'].show = 1;
filter['storages_blok'].caption = 'Склад магазина';
filter['storages_blok'].property_id = 'storages';
filter['storages_blok'].property_type_id = 5;
filter['storages_blok'].list_type = 1;
filter['storages_blok'].list_options = new Array;
filter['storages_blok'].storages_in_filter = new Array;




/*
var flag_search = new Array();
	flag_search.push('Искомый артикул');
	flag_search.push('Аналоги');
*/
	
	
	
	
var start_page_Required = 0;// Количество отображенных строк (групп) позиций Запрошенный артикул
var start_page_SearchName = 0;// Количество отображенных строк (групп) позиций Найденных по наименованию
var start_page_Quick_Analogs = 0;// Количество отображенных строк (групп) позиций Быстрые аналоги
var start_page_Analogs = 0;// Количество отображенных строк (групп) позиций Аналоги
var start_page_PossibleReplacement = 0;// Количество отображенных строк (групп) позиций PossibleReplacement
var start_page_Spare_Box = 0;// Количество отображенных строк (групп) позиций Spare_Box

var cnt_on_page = <?php echo $cnt_on_page_settings; ?>;//Сколько прибавлять позиций по кнопке "Паказать еще"
//Установка cookie для ограничения количества отображаемых элементов
function set_cnt_on_page_settings(cnt)
{
    //Устанавливаем cookie (на полгода)
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "cnt_on_page_settings="+cnt+"; path=/; expires=" + date.toUTCString();
	
	cnt_on_page = cnt;
	start_page_Required = 0;
	start_page_SearchName = 0;
	start_page_Quick_Analogs = 0;
	start_page_Analogs = 0;
	start_page_PossibleReplacement = 0;
	start_page_Spare_Box = 0;
	resultReview();
}
// Отображение следующей страницы с позиций
function next_page(btn){
	// Увеличиваем счетчик возможного количества отображенных позиций
	switch(btn){
		case 'Required' :
			start_page_Required += cnt_on_page;
		break;
		case 'SearchName' :
			start_page_SearchName += cnt_on_page;
		break;
		case 'Quick_Analogs' :
			start_page_Quick_Analogs += cnt_on_page;
		break;
		case 'Analogs' :
			start_page_Analogs += cnt_on_page;
		case 'PossibleReplacement' :
			start_page_PossibleReplacement += cnt_on_page;
		break;
		case 'Spare_Box' :
			start_page_Spare_Box += cnt_on_page;
		break;
	}
	resultReview();
}	
// Функция сортировки
function sortFunction(a, b){
  if(a < b)return -1
  if(a > b)return 1
  return 0
}
var show_all_position_flag = false;
// Функция раскрытия всех групп
function show_all_position(){
	for(var i = 0; i < wrap_blocks_index.length; i++){
		show_hide_block(i, true);
	}
	show_all_position_flag = !show_all_position_flag;
}
	
//Показать виджеты свойств
function showPropertiesWidgets()
{
	<?php
	
	$show_all_group_html = '';
	if($table_mode != 2){
		$show_all_group_html = '
		<tr> 
			<td>
				<input id="show_all_position" type="checkbox" style="width:20px; height:20px;" onclick="show_all_position()"/>
			</td>
			<td style="padding-left:5px; padding-top:2px;">
				<label for="show_all_position">Развернуть все группы</label>
			</td>
		</tr>';
	}
	
	
	$btn_html = '
	
	<div style="margin:15px 0px 0px 0px;">
		<table style="width:100%; text-align:center;">
		<tr>
			<td>
				<a title="Количество отображаемых позиций в блоке" style="background:none; color:#999; border-color:#ccc;" class="btn btn-sm btn-danger" href="javascript:void(0);" onclick="set_cnt_on_page_settings(10);">10</a>
			</td>
			<td>
				<a title="Количество отображаемых позиций в блоке" style="background:none; color:#999; border-color:#ccc;" class="btn btn-sm btn-danger" href="javascript:void(0);" onclick="set_cnt_on_page_settings(20);">20</a>
			</td>
			<td>
				<a title="Количество отображаемых позиций в блоке" style="background:none; color:#999; border-color:#ccc;" class="btn btn-sm btn-danger" href="javascript:void(0);" onclick="set_cnt_on_page_settings(50);">50</a>
			</td>
		</tr>
		</table>
	</div>
	
	<hr/>
	
	<div id="reset_box"></div>
	
	<div>
		<table>
			<tr>
				<td>
					<input style="width:20px; height:20px;" id="min_price_in_group" type="checkbox" onclick="in_check();"/>
				</td>
				<td style="padding-left:5px;">
					<label for="min_price_in_group">Самые дешевые позиции</label>
				</td>
			</tr>
			<tr>
				<td>
					<input style="width:20px; height:20px;" id="min_time_in_group" type="checkbox" onclick="in_check();"/>
				</td>
				<td style="padding-left:5px;">
					<label for="min_time_in_group">Самые быстрые позиции</label>
				</td>
			</tr>
			'. $show_all_group_html .'
		</table>
	</div>
	
	<hr/>
	
	';
	
	$btn_html = str_replace("\n",'',$btn_html);
	$btn_html = str_replace("\r",'',$btn_html);
	$btn_html = str_replace("\t",'',$btn_html);
	?>
	
	var filter_html = '';
	
	for (filter_block in filter){

		filter_block = filter[filter_block];

		var property_id = filter_block.property_id;
		var property_type_id = filter_block.property_type_id;
		
		if(filter_block.show !== 1){
			filter_html += '<div class="one_property" style="display:none;"';
		}else{
			filter_html += '<div class="one_property">';
		}
		filter_html += '<strong>'+ filter_block.caption +'</strong><br/>';
        
		switch(property_type_id)
        {
            case 1:
            case 2:
			
                filter_html += '<div class="slider_ranges">';
                    filter_html += '<input type="text" onkeyup="return proverka_numeric(this);" onchange="onchange_range_min(\''+ property_id +'\');" id="range_min_'+ property_id +'"  />';
                    filter_html += ' — ';
                    filter_html += '<input type="text" onkeyup="return proverka_numeric(this);" onchange="onchange_range_max(\''+ property_id +'\');" id="range_max_'+ property_id +'"  />';
                    filter_html += '<div class="productsCountPopup" id="productsCountPopup_' +property_id +'"></div>';
                filter_html += '</div>';
                
                filter_html += '<div class="slider_container">';
                    filter_html += '<div id="slider-range_'+ property_id +'">';
                    filter_html += '</div>';
                filter_html += '</div>';
				
                break;
            case 5:
                var printed = 0;//Считаем количество выведенных опций данного списка
				var start_hide = 0;//Флаг "Начали скрывать остальные опции"
                filter_html += "<div class=\"list_div\">";
                //Выводим все пункты списка
                for(var l=0; l < filter_block.list_options.length; l++)
                {
					var in_disabled = '';
					var in_disabled_style = '';
					if(property_id == 'manufacturer'){
						if(arr_manufacturers_posle_filter.length > 0){
							if(arr_manufacturers_posle_filter.indexOf(filter_block.list_options[l].search) === -1){
								in_disabled = ' disabled ';
								in_disabled_style = 'color:#ccc; ';
							}
						}
					}
				
					if(property_id == 'storages'){
						if(arr_storages_posle_filter.length > 0){
							if(arr_storages_posle_filter.indexOf(filter_block.list_options[l].search) === -1){
								in_disabled = ' disabled ';
								in_disabled_style = 'color:#ccc; ';
							}
						}
					}
					
					
					
					//Скрываем те опции, в которых отсутствуют товары
                    var display_none = "";
                    if(filter_block.list_options[l].match_count == 0)
                    {
                        display_none = " display:none;";
                    }
                    else//Считаем количество выведеных опций
                    {
                        printed++;//Эта опция будет выведена
                    }
                    
                    var option_html = "";//HTML для данной опции
                    option_html += "<div style=\""+in_disabled_style+display_none+"\">";
					
					
					if(filter_block.list_options[l].value){
						in_checked = 'checked';
						in_disabled = '';
					}else{
						in_checked = '';
					}
					
					
					
					
					option_html += "<input "+ in_checked + in_disabled +" type=\"checkbox\" id=\"list_"+property_id+"_"+filter_block.list_options[l].id+"\" class=\"css-checkbox\" onchange=\"setProductsCountPopupId('productsCountPopup_"+property_id+"_"+filter_block.list_options[l].id+"'); productsCountRequest('"+property_id+"');\" />";
                    
                    option_html += "<label style=\""+in_disabled_style+"\" for=\"list_"+property_id+"_"+filter_block.list_options[l].id+"\" class=\"css-label\">"+filter_block.list_options[l].text+"</label>";
                    option_html += "<div class=\"productsCountPopup\" id=\"productsCountPopup_"+property_id+"_"+filter_block.list_options[l].id+"\"></div>";
                    option_html += "</div>";
                    
                    
                    
                    if(printed == 6 && start_hide == 0)//До этого было выведено 5. Эта шестая - начинаем скрывать
                    {
                        filter_html += "<div state=\"hidden\" style=\"display:none\" id=\"other_list_options_"+property_id+"\">";
						start_hide = 1;//Флаг - начали скрывать остальные опции
                    }
                    
                    filter_html += option_html;
                    
                    //Если выведенных опций списка больше 5 и это последняя опция - выводим закрывающий div
                    if(l == filter_block.list_options.length -1 && printed > 5)
                    {
                        filter_html += "</div>";
                    }
                }//for(l)
                if(printed > 5)//Если количество элементов в списке больше 5, то выводим кнопку для открытия/закрытия списка
                {
                    
                    filter_html += "<div class=\"show_hidden_div\" style=\"text-align:center\">";
                        filter_html += "<a class=\"show_hidden_a\" id=\"show_hidden_a_"+property_id+"\" href=\"javascript:void(0);\" onclick=\"other_list_options_handle('"+property_id+"');\">Еще варианты</a>";
                    filter_html += "</div>";
                    
                    //$javascript_for_print_after .= "\nother_list_options_handle($property_id);\n";//Делаем вызов функции для скрытия блока
                }
                
                filter_html += "</div>";
                break;
        }
		
		
		filter_html += "</div>";//Добавляем HTML в блок свойств

		if(property_id != 'storages')
        {
            filter_html += "<hr/>";
        }
		
		 filter_html += '</div>';
		
	}
	
	document.getElementById("filter_position").innerHTML = '<?=$btn_html;?>' + filter_html;
	
	

	
	if(this_filter != ''){
		reset_html = "<div style=\"text-align: center; padding: 10px 0px 20px;\"><a class=\"btn btn-ar btn-primary\" style=\"cursor:pointer;\" onclick=\"reset_filter();\">Сбросить фильтры</a></div>";
		reset_html_2 = '<hr/>' + reset_html;
		
		if(sam_price_time != ''){
			if(sam_price == 1){
				document.getElementById('min_price_in_group').checked = true;
			}
			if(sam_time == 1){
				document.getElementById('min_time_in_group').checked = true;
			}
		}
		
	}else{
		reset_html = '';
		reset_html_2 = '';
	}
	
	document.getElementById('reset_box').innerHTML = reset_html;
	document.getElementById('footer_filter_reset').innerHTML = reset_html_2;
	document.getElementById("footer-filter").innerHTML = '<div class="panel-heading" style="position:relative;"><a href="javascript:void(0);" onclick="show_filter_clicked();"><i class="fa fa-arrow-circle-up" aria-hidden="true"></i> Свернуть фильтр</a></div>';
	
    //Инициализировать слайдеры для типов int и float
	for (filter_block in filter){
		

		filter_block = filter[filter_block];
		
		if(filter_block.property_type_id === 1 || filter_block.property_type_id === 2){
			sliderIntFloatInit(filter_block);
		}
    }
    
	// если первоначальная загрузка страницы
	if(flag_first_loading){
		show_filter_clicked();
		flag_first_loading = false;
	}
	
	// Раскрываем список брендов если он был раскрыт перед обновлением
	if(list_brend_show){
		other_list_options_handle('manufacturer');
	}
	// Раскрываем список складов если он был раскрыт перед обновлением
	if(list_storages_show){
		other_list_options_handle('storages');
	}
	
	if(show_all_position_flag){
		document.getElementById('show_all_position').checked = true;
	}
	
	// Делаем фон диапазона в текущий цвет сайта
	if($('#slider-range_price').children(".ui-slider-range") != undefined){
		$('#slider-range_price').children(".ui-slider-range").addClass('btn-ar btn-primary');
		$('#slider-range_time_to_exe').children(".ui-slider-range").addClass('btn-ar btn-primary');
		$('#slider-range_exist').children(".ui-slider-range").addClass('btn-ar btn-primary');
	}
}//~function showPropertiesWidgets()

// Функция определяет выбраны ли фильтры по самой низкой цене и сроку
function in_check(){
	
	if(document.getElementById("min_price_in_group").checked){
		sam_price = 1;
		if(sam_price_time == ''){
			sam_price_time = 'sam_price';
		}
	}else{
		sam_price = 0;
		if(sam_price_time == 'sam_price'){
			sam_price_time = '';
		}
	}
	
	if(document.getElementById("min_time_in_group").checked){
		sam_time = 1;
		if(sam_price_time == ''){
			sam_price_time = 'sam_time';
		}
	}else{
		sam_time = 0;
		if(sam_price_time == 'sam_time'){
			sam_price_time = '';
			if(sam_price == 1){
				sam_price_time = 'sam_price';
			}
		}
	}
	
	productsCountRequest('sam_price_time');
}

//Функция инициализации слайдера
function sliderIntFloatInit(property)
{
    var this_znachenie_min = Math.floor(property.min_value);
	var this_znachenie_max = Math.ceil(property.max_value);

	if(this_filter == property.property_id + '_blok'){
		
		this_znachenie_min = property.min_need;
		this_znachenie_max = property.max_need;

		filter[this_filter].old_min_need = this_znachenie_min;
		filter[this_filter].old_max_need = this_znachenie_max;
		
	}
	
		//Создаем слайдер
		jQuery( "#slider-range_"+property.property_id ).slider({
			range: true,
			min: Math.floor(property.min_value),
			max: Math.ceil(property.max_value),
			values: [this_znachenie_min, this_znachenie_max],
			slide: function( event, ui ) {//Событие - передвижение
				$( "#range_min_"+property.property_id ).val( ui.values[ 0 ]);
				$( "#range_max_"+property.property_id ).val( ui.values[ 1 ] );
			},
			stop: function(){//Событие - отпустили слайдер
				
				productsCountRequest(property.property_id);//Запрос количества товаров
			}
		});
		
		//Выставляем текущие крайние значение в поля ввода
		$( "#range_min_"+property.property_id ).val( jQuery( "#slider-range_"+property.property_id ).slider( "values", 0 ) );
		$( "#range_max_"+property.property_id ).val( jQuery( "#slider-range_"+property.property_id ).slider( "values", 1 ) );
}

// Изменяем слайдер при редактировании инпутов - min
function onchange_range_min(property){
	
	property = filter[property+'_blok'];

	var value1=jQuery("#range_min_"+property.property_id).val();
	var value2=jQuery("#range_max_"+property.property_id).val();
	
	if(parseInt(value1) > parseInt(value2)){
		value1 = value2;
		jQuery("#range_min_"+property.property_id).val(value1);
	}
	jQuery("#slider-range_"+property.property_id).slider("values",0,value1);
	productsCountRequest(property.property_id);
}
// Изменяем слайдер при редактировании инпутов - max
function onchange_range_max(property){
	
	property = filter[property+'_blok'];

	var value1=jQuery("#range_min_"+property.property_id).val();
	var value2=jQuery("#range_max_"+property.property_id).val();
	
	if(parseInt(value2) < parseInt(value1)){
		value2 = value1;
		jQuery("#range_max_"+property.property_id).val(value2);
	}
	jQuery("#slider-range_"+property.property_id).slider("values",1,value2);
	productsCountRequest(property.property_id);
}
// Проверка инпутов слайдера на ввод числа
function proverka_numeric(input){
	input.value = input.value.replace(/[^\d,]/g, '');
}

//Функция предназначена для скрытия/открытия опций списка, если их больше ограниченного числа
function other_list_options_handle(property_id)
{
	//Реверсируем значение атрибута class
    var other_list_options_div = document.getElementById("other_list_options_"+property_id);
	
    if(other_list_options_div.getAttribute("state") == "hidden")
    {
        // Открыли список
		if(property_id == 'manufacturer'){ list_brend_show = true; }
		if(property_id == 'storages'){ list_storages_show = true; }
		
		other_list_options_div.setAttribute("state", "shown");
        jQuery('#other_list_options_'+property_id).fadeIn(200, 'swing', function(){});
        document.getElementById("show_hidden_a_"+property_id).innerHTML = "Скрыть";
    }
    else
    {
		// Скрыли список
		if(property_id == 'manufacturer'){ list_brend_show = false; }
		if(property_id == 'storages'){ list_storages_show = false; }

		other_list_options_div.setAttribute("state", "hidden");
        jQuery('#other_list_options_'+property_id).fadeOut(200, 'swing', function(){});
        document.getElementById("show_hidden_a_"+property_id).innerHTML = "Еще варианты";
    }
}

function setProductsCountPopupId(next_id)
{
    //alert(next_id);
	// Функция используется в разных участках кода. Лучше ее не убирать.
}

//Инициализация значений свойств
function initProperiesValues()
{
    
	for (i in filter){

		switch( parseInt(filter[i].property_type_id) )
        {
            case 1:
            case 2:
                filter[i].min_need = jQuery( "#slider-range_"+filter[i].property_id ).slider( "values", 0 );
                filter[i].max_need = jQuery( "#slider-range_"+filter[i].property_id ).slider( "values", 1 );
                break;
            case 4:
                filter[i].true_checked = document.getElementById("checkbox_true_"+filter[i].property_id).checked;
                filter[i].false_checked = document.getElementById("checkbox_false_"+filter[i].property_id).checked;
                break;
            case 5:
                
				
				
				for(var o=0; o < filter[i].list_options.length; o++)
                {
					filter[i].list_options[o].value = document.getElementById("list_"+filter[i].property_id+"_"+filter[i].list_options[o].id).checked;
                }
				
                break;
        }
		
	}
}

//Запрос количества продуктов, соответствующих указанным требованиям
function productsCountRequest(id)
{
	initProperiesValues();//Инициализируем список свойств выставленными значениями
	
	// Определяем выбранные фильтры
	
	// Бренды
	var manufacturer_in_filter = new Array();
	for(var k = 0; k < filter['manufacturer_blok'].list_options.length; k++){
		if(filter['manufacturer_blok'].list_options[k].value === true){
			manufacturer_in_filter.push(arr_manufacturers[filter['manufacturer_blok'].list_options[k].id]);
		}
	}
	if(manufacturer_in_filter.length > 0){
		filter['manufacturer_blok'].manufacturer_in_filter = manufacturer_in_filter;
	}else{
		filter['manufacturer_blok'].manufacturer_in_filter = arr_manufacturers;
	}
	
	
	
	// Склады
	var storages_in_filter = new Array();
	for(var k = 0; k < filter['storages_blok'].list_options.length; k++){
		if(filter['storages_blok'].list_options[k].value === true){
			storages_in_filter.push(arr_storages[filter['storages_blok'].list_options[k].id]);
		}
	}
	if(storages_in_filter.length > 0){
		filter['storages_blok'].storages_in_filter = storages_in_filter;
	}else{
		filter['storages_blok'].storages_in_filter = arr_storages;
	}
	
	
	
	
	this_filter = id + '_blok';
	
	// Сбрасываем количество отображаемых элементов после применения фильтра
	start_page_Required = 0;
	start_page_SearchName = 0;
	start_page_Quick_Analogs = 0;
	start_page_Analogs = 0;
	start_page_PossibleReplacement = 0;
	start_page_Spare_Box = 0;
	
	// Переотображаем проценку
	resultReview();
}

// Функция сбрасывает фильтр и обновляет таблицу проценки
function reset_filter(){
	
	// Сбрасываем текущие значения
		// Цена
			filter['price_blok'].min_value = undefined;
			filter['price_blok'].max_value = undefined;
			filter['price_blok'].old_min_need = undefined;
			filter['price_blok'].old_max_need = undefined;
		
		// Срок
			filter['time_to_exe_blok'].min_value = undefined;
			filter['time_to_exe_blok'].max_value = undefined;
			filter['time_to_exe_blok'].old_min_need = undefined;
			filter['time_to_exe_blok'].old_max_need = undefined;
		
		// Наличие
			filter['exist_blok'].min_value = undefined;
			filter['exist_blok'].max_value = undefined;
			filter['exist_blok'].old_min_need = undefined;
			filter['exist_blok'].old_max_need = undefined;
			
		filter['manufacturer_blok'].manufacturer_in_filter = arr_manufacturers;
		filter['storages_blok'].storages_in_filter = arr_storages;
			
		for(var o=0; o < filter['manufacturer_blok'].list_options.length; o++)
		{
			filter['manufacturer_blok'].list_options[o].value = false;
		}
		for(var o=0; o < filter['storages_blok'].list_options.length; o++)
		{
			filter['storages_blok'].list_options[o].value = false;
		}
	
	sam_price = 0;	
	sam_time = 0;	
	sam_price_time = '';	
	// Сбрасываем количество отображаемых элементов после применения фильтра
	start_page_Required = 0;
	start_page_SearchName = 0;
	start_page_Quick_Analogs = 0;
	start_page_Analogs = 0;
	start_page_PossibleReplacement = 0;
	start_page_Spare_Box = 0;
	this_filter = '';
	// Сворачиваем группы
	if(show_all_position_flag){
		show_all_position();
	}
	
	resultReview();
}


// Функция производит фильтрацию позиций на основе значений фильтра
function filtering_items(ProductsObjects){
	
	var tmp_arr = new Array();
	for(var p=0; p < ProductsObjects.length; p++){
		
		
		// Фильтруем по фильтрам диапазона
		if(
			(this_filter != 'sam_price_time_blok') &&
			(
			filter[this_filter].min_need < filter[this_filter].old_min_need || 
			filter[this_filter].max_need > filter[this_filter].old_max_need 
			)
		){
			switch(this_filter){
				case 'price_blok' :
					if(
						(ProductsObjects[p].price*1 < filter['price_blok'].min_need) ||
						(ProductsObjects[p].price*1 > filter['price_blok'].max_need)
					){continue;}
				break;
				case 'time_to_exe_blok' :
					if(
						(ProductsObjects[p].time_to_exe < filter['time_to_exe_blok'].min_need) ||
						(ProductsObjects[p].time_to_exe > filter['time_to_exe_blok'].max_need)
					){continue;}
				break;
				case 'exist_blok' :
					if(
						(ProductsObjects[p].exist < filter['exist_blok'].min_need) ||
						(ProductsObjects[p].exist > filter['exist_blok'].max_need)
					){continue;}
				break;
			}
		}else{
			if(
				(filter['price_blok'].min_value == undefined) &&
				(filter['price_blok'].max_value == undefined) &&
				(filter['time_to_exe_blok'].min_value == undefined) &&
				(filter['time_to_exe_blok'].max_value == undefined) &&
				(filter['exist_blok'].min_value == undefined) &&
				(filter['exist_blok'].max_value == undefined)
			){}else{
				if(
				(ProductsObjects[p].price*1 < filter['price_blok'].min_need) || 
				(ProductsObjects[p].price*1 > filter['price_blok'].max_need) || 
				(ProductsObjects[p].time_to_exe*1 < filter['time_to_exe_blok'].min_need) || 
				(ProductsObjects[p].time_to_exe*1 > filter['time_to_exe_blok'].max_need) || 
				(ProductsObjects[p].exist*1 < filter['exist_blok'].min_need) || 
				(ProductsObjects[p].exist*1 > filter['exist_blok'].max_need) 
				){ continue; }
			}
		}
		
		// Найденные бренды после фильтрации
		if(arr_manufacturers_posle_filter.indexOf(ProductsObjects[p].manufacturer) === -1){
			arr_manufacturers_posle_filter.push(ProductsObjects[p].manufacturer);
		}
		
		
		
		// Найденные склады после фильтрации
		if(arr_storages_posle_filter.indexOf(String(ProductsObjects[p].storage_id)) === -1){
			arr_storages_posle_filter.push(String(ProductsObjects[p].storage_id));
		}
		
		// Фильтруем по бренду
		if(filter['manufacturer_blok'].manufacturer_in_filter.indexOf(ProductsObjects[p].manufacturer) === -1){
			continue;
		}
		
		// Фильтруем по складу
		if(filter['storages_blok'].storages_in_filter.indexOf(ProductsObjects[p].storage_id*1) === -1){
			continue;
		}
		
		// Еспи позиция прошла фильтр
		tmp_arr.push(ProductsObjects[p]);
	}
	// Новый массив позиций после фильтрации
	ProductsObjects = tmp_arr;
	
	return ProductsObjects;
}

// Функция определяем самые быстрые и дешевые позиции в объекте
function sam_price_time_fanc(ProductsObjects){
	
	var min_price_in_group = undefined;
	var min_time_in_group  = undefined;
	
	if(sam_price_time == 'sam_price'){
		
		// Находим минимальную цену в группе
		for(var p=0; p < ProductsObjects.length; p++){
			if(min_price_in_group == undefined){
				min_price_in_group = ProductsObjects[p].price;
			}else{
				if(min_price_in_group > ProductsObjects[p].price){
					min_price_in_group = ProductsObjects[p].price;
				}
			}
		}
		
		// Фильтруем по минимальной цене в группе
		var tmp_arr = new Array();
		for(var p=0; p < ProductsObjects.length; p++){
			if(min_price_in_group < ProductsObjects[p].price){
				continue;
			}
			tmp_arr.push(ProductsObjects[p]);
		}
		ProductsObjects = tmp_arr;
		
		// Если так же выбран фильтр по минимальному сроку
		if(sam_time == 1){
			
			// Находим минимальный срок доставки в группе
			for(var p=0; p < ProductsObjects.length; p++){
				if(min_time_in_group == undefined){
					min_time_in_group = ProductsObjects[p].time_to_exe;
				}else{
					if(min_time_in_group > ProductsObjects[p].time_to_exe){
						min_time_in_group = ProductsObjects[p].time_to_exe;
					}
				}
			}
			
			// Фильтруем по минимальному сроку в группе
			var tmp_arr = new Array();
			for(var p=0; p < ProductsObjects.length; p++){
				if(min_time_in_group < ProductsObjects[p].time_to_exe){
					continue;
				}
				tmp_arr.push(ProductsObjects[p]);
			}
			ProductsObjects = tmp_arr;
		}
	}
	
	if(sam_price_time == 'sam_time'){
		
		// Находим минимальный срок доставки в группе
		for(var p=0; p < ProductsObjects.length; p++){
			if(min_time_in_group == undefined){
				min_time_in_group = ProductsObjects[p].time_to_exe;
			}else{
				if(min_time_in_group > ProductsObjects[p].time_to_exe){
					min_time_in_group = ProductsObjects[p].time_to_exe;
				}
			}
		}
		
		// Фильтруем по минимальному сроку в группе
		var tmp_arr = new Array();
		for(var p=0; p < ProductsObjects.length; p++){
			if(min_time_in_group < ProductsObjects[p].time_to_exe){
				continue;
			}
			tmp_arr.push(ProductsObjects[p]);
		}
		ProductsObjects = tmp_arr;
		
		// Если так же выбран фильтр по минимальной цене
		if(sam_price == 1){
			
			// Находим минимальную цену в группе
			for(var p=0; p < ProductsObjects.length; p++){
				if(min_price_in_group == undefined){
					min_price_in_group = ProductsObjects[p].price;
				}else{
					if(min_price_in_group > ProductsObjects[p].price){
						min_price_in_group = ProductsObjects[p].price;
					}
				}
			}
			
			// Фильтруем по минимальной цене в группе
			var tmp_arr = new Array();
			for(var p=0; p < ProductsObjects.length; p++){
				if(min_price_in_group < ProductsObjects[p].price){
					continue;
				}
				tmp_arr.push(ProductsObjects[p]);
			}
			ProductsObjects = tmp_arr;
		}
	}
	
	return ProductsObjects;
}





// Функция отделяет тысячные знаки пробелом. Используется для отображения цены
function digit(str){
    var parts = (str + '').split('.'),
        main = parts[0],
        len = main.length,
        output = '',
        i = len - 1;
    
    while(i >= 0) {
        output = main.charAt(i) + output;
        if ((len - i) % 3 === 0 && i > 0) {
            output = ' ' + output;
        }
        --i;
    }

    if (parts.length > 1) {
        output += '.' + parts[1];
    }
    return output;
}






//Функция добавления требуемого количества
function plusCountNeed(product_record_id, count, min_count)
{
	if(min_count === undefined){
		min_count = 1;
	}
	
	//Текущее количество
	var current_count_need = parseInt(document.getElementById("count_need_"+product_record_id).value) + parseInt(min_count);
	
	//Если максимальное количество на складе 0 то поставим 1
	if(count < 1){
		count = 1;
	}
	
	//Если не привышено максимальное значение то увеличиваем
	if(current_count_need <= count){
		document.getElementById("count_need_"+product_record_id).value = current_count_need;
	}else{
		alert("Превышено наличие на складе");
	}
}
	
//Функция вычитания требуемого количества
function minusCountNeed(product_record_id, count, min_count)
{
	if(min_count === undefined){
		min_count = 1;
	}
	
	//Текущее количество
	var current_count_need = parseInt(document.getElementById("count_need_"+product_record_id).value) - parseInt(min_count);
	
	//Если максимальное количество на складе 0 то поставим 1
	if(count < 1){
		count = 1;
	}
	
	//Если не привышено максимальное значение то увеличиваем
	if(current_count_need >= parseInt(min_count)){
		document.getElementById("count_need_"+product_record_id).value = current_count_need;
	}else{
		alert("Ошибка уменьшения количества");
	}
}
	
//Функция изменения количества при ручном вводе в поле
function onKeyUpCountNeed(product_record_id, count, count_min)
{
	if(count_min === undefined){
		count_min = 1;
	}
	
	//Текущее количество
	var current_count_need = parseInt(document.getElementById("count_need_"+product_record_id).value);
	
	//Если введено допустимое значение
	if((current_count_need <= count && current_count_need >= count_min) && ((getDecimal((current_count_need / count_min))*1) == 0))
	{
		
	}
	else//Просто исправляем обратно
	{
		alert("Введено недопустимое значение");
		document.getElementById("count_need_"+product_record_id).value = count_min;
	}
}

// Возвращает дробную часть
function getDecimal(num) {
	var str = "" + num;
	var zeroPos = str.indexOf(".");
	if (zeroPos == -1) return 0;
	str = str.slice(zeroPos);
	return +str;
}

// На устройствах с небольшим разрешением экрана автоматически сворачиваем фильтр при первой загрузке
if(screen.width < 991){
	show_filter_clicked();
}




/* ************************************* */
/* *************    END    ************* */
/* ************************************* */
</script>



















<?php
//В зависимости от режима - отображаем результат соответствующим скриптом
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/part_search_page_".$table_mode.".php");
?>

<div id="work_area" align="center">
	<?php
	//В зависимости от режима работы скрипта - выбираем исходном сообщение
	if( $search_type == "no_chpu" || $search_type == "all_brands_by_article" )
	{
		$start_message = "Подождите, идет запрос производителей...";
	}
	else if( $search_type == "prices_by_article_and_manufacturer" )
	{
		if( $use_selected_manufacturer )
		{
			$start_message = "Опрос складов...";
		}
		else
		{
			$start_message = "Подождите, идет запрос производителей...";
		}
	}
	?>


    <div id="processing_indicator">
        <p><?php echo $start_message; ?></p><img src="/content/files/images/ajax-loader-transparent.gif" />
    </div>

    
    <div id="products_area">
    </div>
</div>



















<script>
<?php
//ПРИ ЗАГРУЗКЕ СТРАНИЦЫ ПЕРЕДАЕМ ПЕРВУЮ КОМАНДУ
//Для старого варианта. И, такой же точно - ЧПУ-первый шаг
if( $search_type == "no_chpu" || $search_type == "all_brands_by_article" )
{
	//Первая команда: запрос списка производителей от поставщиков
	?>
	getManufacturersList();
	<?php
}
//ЧПУ-второй шаг
else if( $search_type == "prices_by_article_and_manufacturer" )
{
	if( $use_selected_manufacturer )
	{
		//Первая команда: Сразу выбор поставщика (а, JavaScript-переменные уже инициализированы из опций пользователя)
		if( $selected_manufacturer["SelectedManufacturer"] == null )
		{
			?>
			onManufacturerSelected(null);
			<?php
		}
		else
		{
			?>
			onManufacturerSelected('<?php echo str_replace("'","\'",$selected_manufacturer["SelectedManufacturer"]); ?>');
			<?php
		}
	}
	else//Начинаем с начала - с запроса производителей по артикулу, т.к. в опциях пользователя не найден выбранный производитель. При этом, после получения списка прозводителей - он будет выбран автоматически, т.к. он содержится в URL.
	{
		//Первая команда: запрос списка производителей от поставщиков
		?>
		getManufacturersList();
		<?php
	}
}
?>
</script>




<!-------------------------------------------- Start Работа с окнами -------------------------------------------->
<div id="info_windows_area" style="display:none;">
    <div id="dialog">
    </div>
</div>
<script>
//Функция открытия окна
function openInfoWindow(title, text, type, info_object_json)
{
    var window_text = text;
    var window_title = title;
    
    
    //Для специальных окон - в зависимости от типа - формируем окно
    if(type != undefined)
    {
        var info_object = JSON.parse(info_object_json);
        
        console.log(info_object);
        
        switch(type)
        {
            case 1:
                window_title = "Информация о наличии";
                window_text = "<div align=\"center\"><p style=\"color:#AAA\">Вероятность наличия "+info_object.probability+"%</p>";
                window_text += "<img src=\"/lib/TreelaxCharts/sectors.php?number=2&value0="+info_object.probability+"&value1="+(100-info_object.probability)+"&start_angle=20&size=400&inside_size=5&slope=1.1&square=0\" /></div>";
                window_text += "<div>Ожидаемый срок: "+info_object.time_to_exe+" дн. Гарантированный срок: "+info_object.time_to_exe_guaranteed+" дн.</div>";
                window_text += "<div>Количество в наличии: "+info_object.exist+" шт.</div>";
                break;
        }
    }



    //Инициализируем div диалога:
    var dialog = document.getElementById("dialog");
    dialog.innerHTML = window_text;
    $( "#dialog" ).dialog({
        title:window_title,
    });
}

</script>
<!-------------------------------------------- End Работа с окнами -------------------------------------------->




<!----- Закрытие блока проценки ------>
</div>




<?php
if($user_id > 0){
?>
<!---------------------------------------------- ГАРАЖ ---------------------------------------------->
<style>
body {
   padding: 0 !important;
}
#my_modal_box_for_garage .modal {
	z-index:99999999;
}
#my_modal_box_for_garage .modal-header {
  text-align: center;
  font-size: 14px;
  background: #fff;
  color:#000;
  border-bottom: 1px solid #999;
}
#my_modal_box_for_garage .close{
	color:#000;
}
#my_modal_box_for_garage .modal-footer {
	border-top: 1px solid #999;
	text-align: center;
}
</style>
<div id="my_modal_box_for_garage">
  <div class="modal fade" id="modal_garage" role="dialog">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="padding:10px 15px;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <b>Выберите автомобиль, в блокнот которого будет добавлена позиция</b>
        </div>
        <div class="modal-body" style="color:#000; padding:40px 50px;">
			<div id="add_bloknot_content">
				<?php
				$query = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = ?;');
				$query->execute( array($user_id) );
				echo '<select id="garage_auto" class="form-control">';
				echo '<option value="0">Общий блокнот</option>';
				while($car = $query->fetch())
				{
					echo '<option value="'.$car['id'].'">'. $car["mark"]." ".$car["model"]." ".$car["year"]." года - ". $car["caption"] .'</option>';
				}
				echo '</select>';
				?>
			</div>
			<div id="add_bloknot_msg"></div>
        </div>
        <div id="add_bloknot_btn" class="modal-footer">
			<a style="margin-bottom: 5px;" class="btn btn-ar btn-primary" onclick="add_bloknot();">Добавить в блокнот</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
var id_in_bloknot = -1;// id позиции которую будем добавлять в блокнот
// Функция отображения блока добавления позиции в блокнот
function show_add_bloknot(id){
	id_in_bloknot = id;
	$("#modal_garage").modal();
}
// Функция добавления позиции в блокнот гаража
function add_bloknot(){
	if(id_in_bloknot >= 0){
		var n = document.getElementById("garage_auto").options.selectedIndex;
		var val = document.getElementById("garage_auto").options[n].value;
		var aid = id_in_bloknot;
		
		//1. По списку учетных объектов определяем, в где находится объект товара (Запрошенные/Аналоги)
		var AID_Object = Products.All[aid];
		
		////////////////////////////////////////////////////
		<?php
		if($table_mode == 1){
		?>
		
		//2. Получаем сам объект товара
		var Product = new Object;
		if(AID_Object.isRequired == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Required.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
				var Article = Products.Required.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isSearchName == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
				var Article = Products.SearchName.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isAnalogs == true)
		{
			//Ищем объект товара в списке аналогов
			for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
				var Article = Products.Analogs.ProductsTypes[i].article;
				
				//Массив объектов товаров:
				var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isQuickAnalogs == true)
		{
			//Ищем объект товара в списке быстрых аналогов
			for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
				var Article = Products.Quick_Analogs.ProductsTypes[i].article;
				
				//Массив объектов товаров:
				var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isPossibleReplacement == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
				var Article = Products.PossibleReplacement.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}else if(AID_Object.isSpare_Box == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
			{
				var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
				var Article = Products.Spare_Box.ProductsTypes[i].article;
			
				//Массив объектов товаров:
				var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
				for(var p=0; p < ProductsObjects.length; p++)
				{
					if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
					{
						Product = Object.assign({}, ProductsObjects[p]);
						break;
					}
				}
			}
		}
		
		<?php
		}else{
		?>
		
		//2. Получаем сам объект товара
		var Product = new Object;
		if(AID_Object.isRequired == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Required.length; i++)
			{
				if( parseInt(Products.Required[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.Required[i]);
					break;
				}
			}
		}
		else if(AID_Object.isSearchName == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.SearchName.length; i++)
			{
				if( parseInt(Products.SearchName[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.SearchName[i]);
					break;
				}
			}
		}
		else if(AID_Object.isQuickAnalogs == true)
		{
			//Ищем объект товара в списке запрошенных
			for(var i=0; i < Products.Quick_Analogs.length; i++)
			{
				if( parseInt(Products.Quick_Analogs[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.Quick_Analogs[i]);
					break;
				}
			}
		}
		else if (AID_Object.isAnalogs == true)
		{
			//Ищем объект товара в списке аналогов
			for(var i=0; i < Products.Analogs.length; i++)
			{
				if( parseInt(Products.Analogs[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.Analogs[i]);
					break;
				}
			}
		}
		else if (AID_Object.isPossibleReplacement == true)
		{
			//Ищем объект товара в списке аналогов
			for(var i=0; i < Products.PossibleReplacement.length; i++)
			{
				if( parseInt(Products.PossibleReplacement[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.PossibleReplacement[i]);
					break;
				}
			}
		}
		else if (AID_Object.isSpare_Box == true)
		{
			//Ищем объект товара в списке аналогов
			for(var i=0; i < Products.Spare_Box.length; i++)
			{
				if( parseInt(Products.Spare_Box[i].aid) == parseInt(aid))
				{
					Product = Object.assign({}, Products.Spare_Box[i]);
					break;
				}
			}
		}
		
		<?php
		}
		?>
		/////////////////////////////////////////////////
		
		jQuery.ajax({
			type: "POST",
			async: false, //Запрос синхронный
			url: "/content/shop/docpart/garage/ajax_add_to_notepad.php",
			dataType: "json",//Тип возвращаемого значения
			data: "garage="+val+"&product="+encodeURIComponent(JSON.stringify(Product)),
			success: function(answer)
			{
				var icon = '<i style="font-size: 30px; color: green;" class="fa fa-check"></i> ';
				if(answer.status != true){
					icon = '<i style="font-size: 30px; color: red;" class="fa fa-times"></i> ';
				}
				
				document.getElementById('add_bloknot_content').style.display = "none";
				document.getElementById('add_bloknot_btn').style.display = "none";
				document.getElementById('add_bloknot_msg').innerHTML = icon + answer.message;
				
				setTimeout(function(){
					$("#modal_garage").modal('hide');
					
				}, 1200);
				
				setTimeout(function(){
					document.getElementById('add_bloknot_content').style.display = "block";
					document.getElementById('add_bloknot_btn').style.display = "block";
					document.getElementById('add_bloknot_msg').innerHTML = '';
				}, 1500);
			},
			error: function (e, ajaxOptions, thrownError){
				alert('Ошибка');
			}
		});
	}
}
</script>
<!-------------------------------------------- End ГАРАЖ -------------------------------------------->
<?php
}
?>


