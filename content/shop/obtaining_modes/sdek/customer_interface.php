<?php
defined('_ASTEXE_') or die('No access');
// Скрипт графического интерфеса покупателя. Используется на странице "Выбора способа получения товара" - frontend


// Получаем настройки системы
$parameters_values_query = $db_link->prepare('SELECT `parameters_values` FROM `shop_obtaining_modes` WHERE `handler` = ?;');
$parameters_values_query->execute( array('sdek') );
$parameters_values = $parameters_values_query->fetch();
$parameters_values = json_decode($parameters_values["parameters_values"], true);
?>


<script type="text/javascript" src="/content/shop/obtaining_modes/sdek/widget/widjet.js" id="ISDEKscript" ></script>


<script>
var HowGet = new Object;

var widjet = new ISDEKWidjet({
   showWarns: true,
   showErrors: true,
   showLogs: true,
   hideMessages: false,
   path: '/content/shop/obtaining_modes/sdek/widget/scripts/',
   // servicepath: '/content/shop/obtaining_modes/sdek/widget/scripts/service.php', //ссылка на файл service.php на вашем сайте
   // templatepath: '/content/shop/obtaining_modes/sdek/widget/scripts/template.php',
   choose: true,
   popup: true,
   country: 'Россия',
   defaultCity: '<?=$parameters_values['defaultCity']?>',
   cityFrom: '<?=$parameters_values['cityFrom']?>', //Город, откуда будет совершаться доставка (Добавить этот пункт в настройки виджета)
   link: 'sdek_widget',
   hidedress: true,
   hidecash: true,
   hidedelt: true,
   onReady: onReady,
   onChoose: onChoose
});

function onReady() {
	
   $("#sdek_widget").css({width:"100%"});
   
}

function onChoose( wat ) {
	
	HowGet.mode = '<?=$current_obtain_mode; ?>';
	HowGet.point_id = encodeURIComponent(wat.id);
	HowGet.point_name = encodeURIComponent(wat.PVZ.Name);
	HowGet.city = encodeURIComponent(wat.cityName);
	HowGet.address = encodeURIComponent(wat.PVZ.Address);
	HowGet.work_time = encodeURIComponent(wat.PVZ.WorkTime);
	HowGet.phone = encodeURIComponent(wat.PVZ.Phone);
	
	console.log( HowGet );
	console.log( wat );
	
	$("#next_step_hanlder").css({display: 'block'});
}

function nextStep() {
	//Устанавливаем cookie (на полгода)
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "how_get="+JSON.stringify(HowGet)+"; path=/; expires=" + date.toUTCString();
	
	location = "/shop/checkout/confirm";
}
</script>

<style>
.CDEK-widget__choose{
	background: rgba(80, 166, 49, 0.6);
}
</style>

<p class="lead">Выберете точку получения товара:</p>

<div id="sdek_widget" style="height: 600px;" ></div>



<div id="next_step_hanlder" style="display: none; margin: 40px 0px 20px 0px; text-align:center;" >
	<button class="btn btn-ar btn-primary" onclick="nextStep()">Продолжить</button>
</div>