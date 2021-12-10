<?php
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

if( DP_User::getUserId() == 0 )
{
?>
	<div class="panel-heading">Форма авторизации</div>
	<div class="panel-body no-auth" style="color:#777;">
		<form method="POST" name="auth_form<?php echo $login_form_postfix; ?>">
			<input type="hidden" name="authentication" value="true"/>
			<?php
			if(!isset($login_form_target))
			{
				$login_form_target = "";
			}
			?>
			<input type="hidden" name="target" value="<?php echo $login_form_target; ?>"/>
			<div class="form-group">
				
				
				
				<?php
				//Доступные способы связи
				$display_auth_contact_select = ' style="display:none;" ';//Для видимости селектора - по-умолчанию не видимый
				$auth_contact_select_options = '<option value="phone">Телефон</option> <option value="email">E-mail</option>';//Набор опций для способов аутентификации
				$available_communications = DP_User::available_communications();//Получаем доступные способы связи
				if( $available_communications["all"] )
				{
					$display_auth_contact_select = "";//Селектор делаем видимым, чтобы клиент смог сам выбрать нужный вид контакта для аутентификации
				}
				else if( $available_communications["sms"] )
				{
					$auth_contact_select_options = '<option value="phone">Телефон</option>';//Оставляем только телефон
				}
				else
				{
					$auth_contact_select_options = '<option value="email">E-mail</option>';//Оставляем только E-mail
				}
				?>
				
				<!-- Селектор контакта для аутентификации -->
				<div class="input-group login-input" <?php echo $display_auth_contact_select; ?>>
					<span class="input-group-addon">Вход через</span>
					
					<select name="auth_contact_type" class="form-control" id="auth_contact_select<?php echo $login_form_postfix; ?>" onchange="on_auth_contact_select_changed<?php echo $login_form_postfix; ?>();" style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;">
							<?php echo $auth_contact_select_options; ?>
					</select>
				</div>
				<?php
				//Добавляем перенос после выдимого селектора
				if( $display_auth_contact_select == '' )
				{
					?>
					<br/>
					<?php
				}
				?>
				<!-- Поле для контакта -->
				<div class="input-group login-input">
					<span class="input-group-addon"><i id="contact_type_icon<?php echo $login_form_postfix; ?>" class=""></i></span>
					<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="text" class="form-control" placeholder="" name="auth_contact" id="auth_contact_input<?php echo $login_form_postfix; ?>" />
				</div>
				<script>
				//Обработка выбора контакта
				function on_auth_contact_select_changed<?php echo $login_form_postfix; ?>()
				{
					if( document.getElementById("auth_contact_select<?php echo $login_form_postfix; ?>").value == "email" )
					{
						document.getElementById("contact_type_icon<?php echo $login_form_postfix; ?>").setAttribute('class', 'fa fa-envelope');
						document.getElementById("auth_contact_input<?php echo $login_form_postfix; ?>").setAttribute("placeholder", "Ваш E-mail");
					}
					else
					{
						document.getElementById("contact_type_icon<?php echo $login_form_postfix; ?>").setAttribute('class', 'fa fa-phone');
						document.getElementById("auth_contact_input<?php echo $login_form_postfix; ?>").setAttribute("placeholder", "Ваш телефон");
					}
				}
				on_auth_contact_select_changed<?php echo $login_form_postfix; ?>();
				</script>
				
				
				<br/>
				<div class="input-group login-input">
					<span class="input-group-addon"><i style="padding: 0px 2px 0px 3px;" class="fa fa-lock"></i></span>
					<input style="height: 40px; background-color:#FFF; border: 1px solid #ccc; color: #555;" type="password" class="form-control" placeholder="Пароль" name="password" autocomplete="off" />
				</div>
				<div class="checkbox">
					<input type="checkbox" id="checkbox_remember_<?php echo $login_form_postfix; ?>" name="rememberme" />
					<label for="checkbox_remember_<?php echo $login_form_postfix; ?>">Запомнить меня</label>
				</div>
				<a href="javascript:void(0);" onclick="forms['auth_form<?php echo $login_form_postfix; ?>'].submit();" class="btn btn-ar btn-primary btn_auth" style="color:#FFF;">Войти</a>

				<a href="/users/registration" class="btn btn-ar btn-success btn_reg" style="color:#FFF;">Регистрация</a>
				
				<hr class="dotted margin-10">
				
				<a href="/users/forgot_password" class="btn btn-ar btn-warning btn_forget" style="color:#FFF;">Не помню пароль</a>
				<div class="clearfix"></div>
			</div>
		</form>
	</div>
<?php
}
else
{
?>
	<div class="panel-heading">Личный кабинет</div>
	<div class="panel-body" style="color:#777;">
		<form method="POST" name="auth_form<?php echo $login_form_postfix; ?>">
			<input type="hidden" name="logout" value="true"/>
			<div class="form-group">
				<a href="/users/profile" class="btn btn-ar btn-success btn_profile" style="color:#FFF;">Мои данные</a>

				<hr class="dotted margin-10">
				
				<a href="/shop/orders" class="btn btn-ar btn-warning btn_orders" style="color:#FFF;">Заказы</a>
				<a href="/shop/cart" class="btn btn-ar btn-warning btn_cart" style="color:#FFF;">Корзина</a>
				<a href="/garazh" class="btn btn-ar btn-warning btn_garazh" style="color:#FFF;">Гараж</a>
				<a href="/garazh/bloknot?garage=0" class="btn btn-ar btn-warning btn_bloknot" style="color:#FFF;">Блокнот</a>
				<a href="/shop/balans" class="btn btn-ar btn-warning btn_balans" style="color:#FFF;">Баланс</a>
				
				<hr class="dotted margin-10">
				
				<a href="javascript:void(0);" onclick="forms['auth_form<?php echo $login_form_postfix; ?>'].submit();" class="btn btn-ar btn-danger btn_exit" style="color:#FFF;">Выйти</a>
				
				<div class="clearfix"></div>
			</div>
		</form>
	</div>
<?php
}
?>