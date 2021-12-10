<?php
/**
 * Модуль входа на сайт
*/
defined('_ASTEXE_') or die('No access');

require_once("content/users/dp_user.php");
?>
<div id="open_login">
    <span>Вход на сайт</span>
</div>
<div id="module_login_box">
    <div id="module_login" style="display: none">
        <?php
        if(DP_User::getUserId() == 0)
        {
            ?>
            <form method="POST">
                <input type="hidden" name="authentication" value="true"/>
                <div class="wrong_authentication" id="wrong_authentication">
                </div>
                <input class="login_input" type="text" name="login" value="" placeholder="Логин"/>
                <input class="login_input" type="password" name="password" value="" placeholder="Пароль"/>
                <div id="remember_me_div">
                    Запомнить меня <input type="checkbox" name="rememberme" value="rememberme"/>
                </div>
                <button type="submit" class="btn">Войти</button>
            </form>
            <script>
                document.getElementById("open_login").innerHTML = "<span>Вход на сайт</span>";
            </script>
            
            <a href="/users/registration" class="btn btn-success">Регистрация</a> 
            <a href="/users/forgot_password" class="btn">Не помню пароль</a>
            
            <?php
        }
        else
        {
            ?>
                <div id="greeting">
                    Приветствуем Вас!
                </div>
                <div id="self_data_control">
                    <a href="/users/profile" class="btn btn-success">Мои данные</a> 
                    <a href="/users/editform" class="btn">Изменить данные</a>
                </div>
                <form method="POST">
                    <input type="hidden" name="logout" value="true"/>
                    <button type="submit" class="btn">Выйти</button>
                </form>
                <script>
					<?php
					//Выводим имя пользователя
					$user_profile = DP_User::getUserProfile();
					$user_name_show = '';
					if( isset( $user_profile["name"] ) )
					{
						$user_name_show = $user_profile["name"];
					}
					if( isset($user_profile["surname"]) )
					{
						if( $user_name_show != '' )
						{
							$user_name_show = $user_name_show.' ';
						}
						$user_name_show = $user_name_show.$user_profile["surname"];
					}
					if( $user_name_show == '' )
					{
						$user_name_show = 'Имя не указано';
					}
					?>
                    document.getElementById("open_login").innerHTML = "<span><?php echo $user_name_show; ?></span>";
                </script>
            <?php
        }
        ?>
    </div>
</div>


<script>
$("#open_login").click(function(){
    	if ( $("#module_login").css('display') == 'none' ) 
    	{
    	    $("#module_login").show(400);
    	}
    	else
    	{
    	    $("#module_login").hide(200);
    	}
	});
</script>