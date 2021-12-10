<?php
/**
 * Скрипт для вывода сообщений о результатах действий
 
 Возможные значения классов для блоков:
 
 success
 danger
 purple
 info
 primary
 mint
 pink
 dark
 
 css в astself.css
 
*/
defined('_ASTEXE_') or die('No access');
?>

<?php
//Вывод сообщений
if(!empty($_GET["success_message"]))
{
    ?>
	<div class="col-lg-12" id="success_div">
		<div class="alert alert-success fade in">
			<button class="close" onclick="clearAlert('success_div');" data-dismiss="alert"><span>×</span></button>
			<strong>Успешно!</strong> <?php echo $_GET["success_message"];?>
		</div>
	</div>
    <?php
}
if(!empty($_GET["error_message"]))
{
    ?>
	<div class="col-lg-12" id="danger_div">
		<div class="alert alert-danger fade in">
			<button class="close" onclick="clearAlert('danger_div');" data-dismiss="alert"><span>×</span></button>
			<strong>Ошибка!</strong> <?php echo $_GET["error_message"];?>
		</div>
	</div>
    <?php
}
if(!empty($_GET["warning_message"]))
{
    ?>
	<div class="col-lg-12" id="purple_div">
		<div class="alert alert-purple fade in">
			<button class="close" onclick="clearAlert('purple_div');" data-dismiss="alert"><span>×</span></button>
			<strong>Предупреждение!</strong> <?php echo $_GET["warning_message"];?>
		</div>
	</div>
    <?php
}
if(!empty($_GET["info_message"]))
{
    ?>
	<div class="col-lg-12" id="info_div">
		<div class="alert alert-info fade in">
			<button class="close" onclick="clearAlert('info_div');" data-dismiss="alert"><span>×</span></button>
			<strong>Инфо!</strong> <?php echo $_GET["info_message"];?>
		</div>
	</div>
    <?php
}
?>

<script>
    //Удаляем сообщение
    function clearAlert(alert_div_id)
    {
        var alert_div = document.getElementById(alert_div_id);
        alert_div.parentNode.removeChild(alert_div);
    }
</script>