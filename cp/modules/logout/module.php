<?php
/**
 * Модуль формы выхода из бэкэнда
*/
defined('_ASTEXE_') or die('No access');
?>

<div id="logout_form_container">
    <?php
    //Получаем данные пользователя
    $admin_profile = DP_User::getAdminProfile();
    ?>
    
    <div>Здравствуйте, <?php echo $admin_profile["name"] ?>!</div>
    <form id="logout_form" method="POST" name="logout_form">
        <input type="hidden" name="logout" value="logout" />
        <a href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">Выйти</a>
    </form>
</div>