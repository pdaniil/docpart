<?php
/**
 * Страничный скрипт для вывода функций блока Пользователи
 * Это заглушка для страницы /users
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();
?>


<?php
//Для НЕ зарегистрированных пользователей
if($user_id == 0)
{
    ?>
	<div class="panel panel-primary">
	<?php
	//Единый механизм формы авторизации
	$login_form_postfix = "users_node_page";
	$login_form_target = "users";
	require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
	?>
	</div>
    <?php
}
else//Для зарегистрированных пользователей
{
    ?>
    
    <div class="cat-item">
    	<a href="/users/profile">
    		Мои данные
    	</a>
    </div>
    
    <?php
}
?>