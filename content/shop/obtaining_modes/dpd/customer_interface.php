<?php
defined('_ASTEXE_') or die('No access');
// Скрипт графического интерфеса покупателя. Используется на странице "Выбора способа получения товара" - frontend

//Получаем настройки системы DPD
$parameters_values_query = $db_link->prepare('SELECT `parameters_values` FROM `shop_obtaining_modes` WHERE `handler` = ?;');
$parameters_values_query->execute( array('dpd') );
$parameters_values = $parameters_values_query->fetch();
$parameters_values = json_decode($parameters_values["parameters_values"], true);

$icn = trim($parameters_values["icn"]);
$key = trim($parameters_values["key"]);
$sid = trim($parameters_values["sid"]);

if($sid === ''){
	$sid = trim(file_get_contents("https://chooser.dpd.ru/getsid.php?icn=$icn&key=$key"));
	
	if(!empty($sid)){
		$parameters_values["sid"] = $sid;
		$json_parameters_values = json_encode($parameters_values);
		if(!empty($json_parameters_values)){
			$query = $db_link->prepare('UPDATE `shop_obtaining_modes` SET `parameters_values` = ? WHERE `shop_obtaining_modes`.`id` = 4');
			$query->execute( array($json_parameters_values) );
		}
	}
}

// Получаем информацию об офисе
$customer_office_info = array();
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
if($customer_offices[0] > 0){
	$customer_office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
	$customer_office_query->execute(array($customer_offices[0]));
	$customer_office_info = $customer_office_query->fetch(PDO::FETCH_ASSOC);
}

$query_geo = $db_link->prepare("SELECT `activated` FROM `modules` WHERE `id` = ?;");
$query_geo->execute( array(38) );
$module_geo = $query_geo->fetch();
if($module_geo['activated'] == 1)
{
	$search_city = '';
	if( isset($_COOKIE["my_city"]) )
    {
		$stmt = $db_link->prepare('SELECT `value` FROM `shop_geo` WHERE `id` = :id;');
		$stmt->bindValue(':id', $_COOKIE["my_city"]);
		$stmt->execute();
		$city_name_record = $stmt->fetch(PDO::FETCH_ASSOC);
        if( $city_name_record != false )
        {
            $search_city = $city_name_record["value"];
        }
    }
}else{
	$search_city = $customer_office_info['city'];
}
?>


<script type="text/javascript" src="https://chooser.dpd.ru/dpdchooser.js?nocache=1588148419212"></script>

<script>

var chooser = new Object();
var dep = new Object();



function findCity()
{
	var search_city = $("#search_city").val();
	
	if(search_city == ''){
		return;
	}
	
	var $_chooser = new DPDChooser('dpdchooser', {
	type: 'dpdclient', // Обязательный параметр
	
	//Геопозиция:
	address: search_city, //Адрес (Можно передавать сокращенно – только город, а можно с учетом страны, города, улицы)
	
	l: '', //Адрес можно передавать короткими параметрами (город)
	s: '', //Адрес можно передавать короткими параметрами (улица)
	m: '', //Адрес можно передавать короткими параметрами (метро)
	g: '', //Можно вместо адреса передавать координаты: долгота, широта (имеет бОльший приоритет, чем адрес)
	// После загрузки данных происходит центрирование на переданной геопозиции. Если ни один пункт не попадает на карту, то карта отдаляется до 4х раз
	// Если передан большой по площади объект для центрирования (например, вся Москва), то границы карты будут установлены так, чтобы весь объект попал в область видимости.
	i: '', //центрирование по индексу
	viewdistance: 100, // Радиус отображения пунктов в километрах вокруг указанного адреса
	
	
	//Фильтры:
	filter_fromh: 0, // - число время ОТ
	filter_toh: 0, // - число время ДО
	filter_maxweight: 0, // Вес самой тяжелой посылки (кг)
	filter_dimmensionsum: 0, // Сумма габаритов (см)
	filter_dimmensionmax: 0, // Максимальный габарит (см)
	filter_wait: 1, // Ожидание на адресе доставки
	filter_temp: 0, // Температурный режим
	filter_cod: 0, // Наложенный платеж
	filter_give: 0, // Выдача посылок
	filter_take: 0, // Прием посылок
	filter_pvz: 0, // Пункт приема и выдачи посылок
	filter_postamat: 0, // Постамат
	
	//Возможность выбора пункта.
	choose: 1, //Если не передано - виджет работает только для просмотра, без кнопки «Выбрать»

	width: '100%', // Можно указать css ширину контейнера. Пример: 500px, 100%, 50em
	mapHeight: '500', // Можно указать высоту карты в пикселях. Пример: 500.
	//ВНИМАНИЕ!!! Высота касается только карты, списка пунктов и детализации. Это НЕ высота всего контейнера виджета.
	//Задать высоту виджета нельзя, т.к. детализация может "прыгнуть" вниз из-за нехватки места в ширину.
	fullscreen: true, // Задает width = 100% и mapHeight в зависимости от window.innerHeight. Позволяет виджету занять всё пространство контейнера, если достаточно места. Должно быть не менее 400px в высоту и не менее 500px в ширину.

	//Авторизация. 
	sid: '<?php echo $sid; ?>'
	});

	$_chooser.onChoose(function($_dep){
		dep = $_dep;
		
		$("#next_step_hanlder").prop('disabled', false).css({display: "block"});
	});
	

	chooser = $_chooser;
}


function nextStep()
{
	//Объект способа доставки
    var how_get = new Object;
		how_get.mode = <?php echo $current_obtain_mode; ?>;
		how_get.address = encodeURIComponent(dep.addressString);
	
	//Устанавливаем cookie (на полгода)
	var date = new Date(new Date().getTime() + 15552000 * 1000);
	document.cookie = "how_get="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();
	
	location = "/shop/checkout/confirm";
}
</script>

<div>
	<p class="lead">Укажите город или адрес, что бы найти ближайший пункт выдачи, в который будет доставлена посылка</p>
	
	<form action="" class="form-horizontal" onsubmit="return false;">
		<div class="form-group">
			<div class="col-lg-6">
				<input name="search_city" id="search_city" type="text" class="form-control" value="<?=trim($search_city);?>"/>
			</div>
		</div>
		<button type="button" onclick="findCity();" class="btn btn-ar btn-primary">Найти</button>
	</form>
</div>

<div style="margin-bottom:20px;"></div>

<div>
	<div id="dpdchooser" style="height:550px; width: 100%; display: inline-block;">
	</div>
	
	<div id="console">
	</div>
	
	<div style="margin: 40px 0px 20px 0px; text-align:center;" >
		<button style="display: none;" disabled id="next_step_hanlder" class="btn btn-ar btn-primary" onclick="nextStep()">Продолжить</button>
	</div>
</div>


<script>
jQuery(document).ready(function($) {
	setTimeout(findCity, 1000);
});
</script>