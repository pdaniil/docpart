<?php
//Скрипт для вывода интерфейса покупателя при выборе способа получения.

//Получаем настройки системы Boxberry
$parameters_values_query = $db_link->prepare('SELECT `parameters_values` FROM `shop_obtaining_modes` WHERE `handler` = ?;');
$parameters_values_query->execute( array('boxberry') );
$parameters_values = $parameters_values_query->fetch();
$parameters_values = json_decode($parameters_values["parameters_values"], true);
$api_key = $parameters_values["api_key"];
?>

<script type="text/javascript"src="https://points.boxberry.ru/js/boxberry.js" /></script>

<div id="boxberry_map"></div>

<script>
function callback_function(result){
	
	console.log(result);
	
	//Объект способа доставки
    var how_get = new Object;
		how_get.mode = <?php echo $current_obtain_mode; ?>;
		how_get.type = "pickup";
		how_get.city = encodeURIComponent(result['name']);
		how_get.address = encodeURIComponent(result['address']);
		how_get.id = encodeURIComponent(result['id']);
		
	//Устанавливаем cookie (на полгода)
		var date = new Date(new Date().getTime() + 15552000 * 1000);
		document.cookie = "how_get="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();
	
		location = "/shop/checkout/confirm";
}

boxberry.openOnPage('boxberry_map');
boxberry.open('callback_function', '<?=$api_key;?>','','', 199.5, 1000, 199.5, 1, 1, 1);
</script>