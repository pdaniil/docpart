<?php
//Скрипт для вывода содержимого таба "Каталог ТО" (от Ucats)
defined('_ASTEXE_') or die('No access');
?>
<div class="search_tab_clar">Каталог автозапчастей для технического обслуживания. Выберите марку автомобиля:</div>
<div id="tab_to_catalogue">
</div>


<script>
jQuery.ajax({
	type: "GET",
	async: true,
	url: "/content/shop/catalogue/search_tabs/tabs_content/to_catalogue/ajax_get_to_marks.php",
	dataType: "text",//Тип возвращаемого значения
	success: function(answer)
	{
		//console.log(answer);
		
		document.getElementById("tab_to_catalogue").innerHTML = answer;
	}
});
</script>