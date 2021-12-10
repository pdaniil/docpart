﻿<?php
//Скрипт тестового платежного шлюза - для Docpart Эмулятор

/*
Задачи этого скрипта:
1. Вывод страницы платежного шлюза (в реальной платежной системе - на такой странице клиент вводит данные карты для оплаты)

2. Проведение тестового платежа
*/


//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["result"] = false;
	exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Использовать эту систему могут только пользователи с доступом в ПУ
if( ! DP_User::isAdmin())
{
	exit("Forbidden");
}

//Далее - функционал скрипта.
if( isset($_POST['action']) )
{
	//Проведение тестового платежа
	if( $_POST['action'] == 'pay_execute' )
	{	
		//Здесь платежная система осуществляет платеж.
		
		
		
		//После платежа - клиент перенаправляется на нужный URL
		
		//Если тестируется успешная оплата - клиент перенаправляется на скрипт сайта - notification.php
		if( $_POST['need_result'] == 'success' )
		{
			?>
			
			<form name="success_form" style="display:none" method="post" action="/content/shop/finance/payment_systems/docpart_emulator/notification.php">
				<input type="hidden" name="operation_id" value="<?php echo $_POST['operation_id']; ?>">
				<input type="hidden" name="sum" value="<?php echo $_POST['sum']; ?>">
				<input type="hidden" name="user_id" value="<?php echo $_POST['user_id']; ?>">
			</form>
			<script>
				document.forms["success_form"].submit();
			</script>
			<?php
			exit;
		}
		else
		{
			//В случае неуспешной оплаты - клиент перенаправляется на страницу баланса сайта с сообщением об ошибке
			?>
			<script>
				location='/shop/balans?error_message=<?php echo urlencode('Ошибка проведения платежа - как и требовалось'); ?>';
			</script>
			<?php
			exit;
		}
	}
	//Вывод страницы платежного шлюза
	if( $_POST['action'] == 'pay_page' )
	{
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<base href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/">
			<meta charset="utf-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">

			<title>Тестирование оплаты</title>

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


		<!-- Simple splash screen-->
		<div class="splash"> <div class="color-line"></div><div class="splash-title"><h1>Тестовый платежный шлюз</h1><p>Загрузка фреймворка, пожалуйста, подождите... </p><div class="spinner"> <div class="rect1"></div> <div class="rect2"></div> <div class="rect3"></div> <div class="rect4"></div> <div class="rect5"></div> </div> </div> </div>
		<!--[if lt IE 7]>
		<p class="alert alert-danger">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
		<![endif]-->

		<div class="color-line"></div>

		<div class="login-container">
			<div class="row">
				<div class="col-md-12">
					<div class="text-center m-b-md">
						<h3>Тестовый платежный шлюз</h3>
						<small>Проверьте платежную информацию и проведите тестовый платеж</small>
					</div>
					<div class="hpanel">
						<div class="panel-body">
							<form method="POST">
								<input type="hidden" name="action" value="pay_execute"/>
								<input type="hidden" name="operation_id" value="<?php echo $_POST['operation_id']; ?>"/>
								<input type="hidden" name="sum" value="<?php echo $_POST['sum']; ?>"/>
								
								<div class="form-group">
									<label class="control-label">Производится операция</label>
									<p><?php echo $_POST['operation_description']; ?></p>
								</div>
								
								
								<div class="form-group">
									<label class="control-label">В магазине</label>
									<p><?php echo $_POST['shop_name'].' (ID '.$_POST['shop_id'].')'; ?></p>
								</div>
								
								
								<div class="form-group">
									<label class="control-label">Данные операции</label>
									<p>Операция ID <?php echo $_POST['operation_id']; ?></p>
								</div>
								
								
								<div class="form-group">
									<label class="control-label">Сумма платежа</label>
									<p><?php echo $_POST['sum']; ?></p>
								</div>
								
								
								
								<div class="form-group">
									<label class="control-label">Нужный результат</label>
									<select class="form-control" name="need_result">
										<option value="success">Успешная оплата</option>
										<option value="error">Ошибка оплаты</option>
									</select>
								</div>
								
								
								<button type="submit" class="btn btn-success btn-block">Провести тестовую оплату</button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12 text-center">
					Платформа Docpart<br/> Copyright © 2012 - <?php echo date("Y", time()); ?>, ООО "ИНТАСК"
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
		<?php
	}
}
?>