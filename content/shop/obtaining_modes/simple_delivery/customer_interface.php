<?php
defined('_ASTEXE_') or die('No access');
// Скрипт графического интерфеса покупателя. Используется на странице "Выбора способа получения товара" - frontend

$how_get = array("city"=>"", "street"=>"", "house"=>"", "block"=>"", "flat_office"=>"", "phone"=>"");

// Ранее заполненные данные доставки
if( isset($_COOKIE["how_get_simple_delivery"]) )
{
	$how_get = json_decode($_COOKIE["how_get_simple_delivery"], true);
}
?>
<style>
@media screen and (max-width: 767px) {
	.simple_delivery_table, .simple_delivery_table tbody, .simple_delivery_table tr, .simple_delivery_table th, .simple_delivery_table td{
		display:block;
		border:none !important;
		padding: 5px 0px 0px 0px !important;
	}
}
</style>

<p class="lead">Заполните данные о доставке:</p>

<table class="table simple_delivery_table">
<tr> <td>Город</td> <td><input class="form-control" id="city_input" type="text" value="<?=$how_get['city'];?>"/></td> </tr>
<tr> <td>Улица</td> <td><input class="form-control" id="street_input" type="text" value="<?=$how_get['street'];?>"/></td> </tr>
<tr> <td>Дом</td> <td><input class="form-control" id="house_input" type="text" value="<?=$how_get['house'];?>"/></td> </tr>
<tr> <td>Корпус</td> <td><input class="form-control" id="block_input" type="text" value="<?=$how_get['block'];?>"/></td> </tr>
<tr> <td>Квартира/Офис</td> <td><input class="form-control" id="flat_office_input" type="text" value="<?=$how_get['flat_office'];?>"/></td> </tr>
<tr> <td>Телефон</td> <td><input class="form-control" id="phone_input" type="text" value="<?=$how_get['phone'];?>"/></td> </tr>
</table>

<?php
//Подключаем общий модуль принятия пользовательского соглашения
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/users_agreement_module.php");
?>

<div class="text-center">
	<a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="nextStep();">Продолжить</a>
</div>

<script>
function nextStep()
{
	if( !check_user_agreement() )
	{
		return;
	}
	
	//Записываем параметра способа получения в куки
	//Объект способа доставки
    var how_get = new Object;
		how_get.mode = <?php echo $current_obtain_mode; ?>;
		how_get.city = encodeURIComponent(document.getElementById("city_input").value);
		how_get.street = encodeURIComponent(document.getElementById("street_input").value);
		how_get.house = encodeURIComponent(document.getElementById("house_input").value);
		how_get.block = encodeURIComponent(document.getElementById("block_input").value);
		how_get.flat_office = encodeURIComponent(document.getElementById("flat_office_input").value);
		how_get.phone = encodeURIComponent(document.getElementById("phone_input").value);
	
	
	if(how_get.city == "" || how_get.city == null)
	{
		alert("Поле Город - обязательно для заполнения");
		return;
	}
	if(how_get.street == "" || how_get.street == null)
	{
		alert("Поле Улица - обязательно для заполнения");
		return;
	}
	if(how_get.house == "" || how_get.house == null)
	{
		alert("Поле Дом - обязательно для заполнения");
		return;
	}
	if(how_get.phone == "" || how_get.phone == null)
	{
		alert("Поле Телефон - обязательно для заполнения");
		return;
	}
	
	//Устанавливаем cookie (на полгода)
    var date = new Date(new Date().getTime() + 15552000 * 1000);
    document.cookie = "how_get="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();
	
	//Запишем данные о доставке для отображения при следующем оформлении заказа
    document.cookie = "how_get_simple_delivery="+JSON.stringify(how_get)+"; path=/; expires=" + date.toUTCString();

	location = "/shop/checkout/confirm";
}
</script>