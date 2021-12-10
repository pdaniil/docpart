<!DOCTYPE html>
<html>
<head>
	<base href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Page title -->
    <docpart type="head" name="head" />

    <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
    <!--<link rel="shortcut icon" type="image/ico" href="favicon.ico" />-->

    <!-- Vendor styles -->
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.css" />
    <link rel="stylesheet" href="vendor/metisMenu/dist/metisMenu.css" />
    <link rel="stylesheet" href="vendor/animate.css/animate.css" />
    <link rel="stylesheet" href="vendor/bootstrap/dist/css/bootstrap.css" />

    <!-- App styles -->
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css" />
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/helper.css" />
    <link rel="stylesheet" href="styles/style.css">

</head>
<body class="blank">


<?php
//В зависимости от продукта - формируем названия
$product_name = "Docpart";//По умолчанию
$product_description = "<strong>Docpart</strong> - платформа для интернет-магазинов автозапчастей";
if($DP_Config->product == "cms")
{
	$product_name = "Intask CMS";//По умолчанию
	$product_description = "<strong>Intask CMS</strong> - универсальная система управления содержимым";
}
if($DP_Config->product == "expancart")
{
	$product_name = "Expancart";//По умолчанию
	$product_description = "<strong>Expancart</strong> - система управления интернет-магазином";
}
?>



<!-- Simple splash screen-->
<div class="splash"> <div class="color-line"></div><div class="splash-title"><h1><?php echo $product_name; ?> - Панель управления</h1><p>Загрузка фреймворка, пожалуйста, подождите... </p><div class="spinner"> <div class="rect1"></div> <div class="rect2"></div> <div class="rect3"></div> <div class="rect4"></div> <div class="rect5"></div> </div> </div> </div>
<!--[if lt IE 7]>
<p class="alert alert-danger">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

<div class="color-line"></div>

<div class="login-container">
    <div class="row">
        <div class="col-md-12">
            <div class="text-center m-b-md">
                <h3>Панель управления <?php echo $product_name; ?></h3>
                <small>Форма входа</small>
            </div>
            <div class="hpanel">
                <div class="panel-body">
						<div class="wrong_authentication" id="wrong_authentication"></div>
                        <form id="login_form" method="POST">
							<input type="hidden" name="authentication" value="authentication"/>
                            
							
							<div class="form-group">
								<label class="control-label" for="auth_contact_select">Вход через</label>
								<select class="form-control" name="auth_contact_select" id="auth_contact_select" onchange="on_auth_contact_select_changed();">
									<option value="email">E-mail</option>
									<option value="phone">Телефон</option>
								</select>
							</div>
							<div class="form-group">
								<label for="auth_contact_input" class="control-label" id="auth_contact_label"></label>
								<input type="text" placeholder="" title="" value="" name="auth_contact" id="auth_contact_input" class="form-control" />
                            </div>
							<script>
							//Обработка выбора контакта
							function on_auth_contact_select_changed()
							{
								if( document.getElementById("auth_contact_select").value == "email" )
								{
									document.getElementById("auth_contact_label").innerHTML = "E-mail";
									document.getElementById("auth_contact_input").setAttribute("placeholder", "Укажите ваш E-mail");
								}
								else
								{
									document.getElementById("auth_contact_label").innerHTML = "Телефон";
									document.getElementById("auth_contact_input").setAttribute("placeholder", "Укажите ваш телефон");
								}
							}
							on_auth_contact_select_changed();
							</script>
							
							
                            <div class="form-group">
                                <label class="control-label" for="password">Пароль</label>
                                <input type="password" title="Please enter your password" placeholder="Введите пароль" required="" value="" name="password" id="password" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">Войти</button>
                        </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12 text-center">
            <?php echo $product_description; ?><br/> Copyright © 2012 - <?php echo date("Y", time()); ?>, ООО "ИНТАСК"
        </div>
    </div>
</div>


<!-- Vendor scripts -->
<script src="vendor/jquery/dist/jquery.min.js"></script>
<script src="vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="vendor/slimScroll/jquery.slimscroll.min.js"></script>
<script src="vendor/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="vendor/metisMenu/dist/metisMenu.min.js"></script>
<script src="vendor/iCheck/icheck.min.js"></script>
<script src="vendor/sparkline/index.js"></script>

<!-- App scripts -->
<script src="scripts/homer.js"></script>

</body>
</html>