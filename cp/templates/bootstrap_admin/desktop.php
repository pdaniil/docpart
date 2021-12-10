<?php
defined('_ASTEXE_') or die('No access');
?>
<!DOCTYPE html>
<html>
<head>
	<base href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta http-equiv="Content-Security-Policy" content="img-src 'self' data:; default-src 'self' *.googleapis.com *.gstatic.com 'unsafe-inline' 'unsafe-eval';">
	
	<script src="vendor/jquery/dist/jquery.min.js"></script>
	<script src="/lib/jquery_browser/jquery.browser.js"></script>
	<script src="/lib/jquery_form/jquery.form.js"></script>
	
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/css/modal_window.css" />
    <docpart type="head" name="head" />
	
	
    <!-- Place favicon.ico and apple-touch-icon.png in the root directory -->
    <!--<link rel="shortcut icon" type="image/ico" href="favicon.ico" />-->

    <!-- Vendor styles -->
    <link rel="stylesheet" href="vendor/fontawesome/css/font-awesome.css" />
    <link rel="stylesheet" href="vendor/metisMenu/dist/metisMenu.css" />
    <link rel="stylesheet" href="vendor/animate.css/animate.css" />
    <link rel="stylesheet" href="vendor/bootstrap/dist/css/bootstrap.css" />
	<link rel="stylesheet" href="vendor/fooTable/css/footable.core.min.css" />


    <!-- App styles -->
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css" />
    <link rel="stylesheet" href="fonts/pe-icon-7-stroke/css/helper.css" />
    <link rel="stylesheet" href="styles/style.css">
	
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/css/astself.css" />
	<link href="/templates/expan/css/catalogue/catalogue.css" rel="stylesheet">
	<link rel="stylesheet" href="/<?php echo $DP_Config->backend_dir; ?>/templates/bootstrap_admin/elFinder/css/theme-bootstrap-libreicons-svg.css" />
	
	
	<!-- Подключаем всплывающие подсказки -->
	<link rel="stylesheet" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/templates/bootstrap_admin/vendor/toastr/build/toastr.min.css" />
	<script src="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/templates/bootstrap_admin/vendor/toastr/build/toastr.min.js"></script>
</head>
<body class="fixed-navbar fixed-sidebar">
<?php
//Функция вывода кнопок для панели управления
function print_backend_button($button_params)
{
	global $DP_Config;
	global $DP_Template;
	
	
	$target = "";
	if( isset($button_params["target"]) )
	{
		if( $button_params["target"] == "_blank" )
		{
			$target = "target=\"_blank\"";
		}
	}
	
	
	$onclick = "";
	if( isset($button_params["onclick"]) )
	{
		$onclick = "onclick=\"".$button_params["onclick"]."\"";
	}
	
	if( $button_params["background_color"] == "" )
	{
		//Изображение
		$img = "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/".$button_params["img"];
		if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$img))
		{
			$img = "content/control/images/window.png";
		}
		?>
		<a class="panel_a" href="<?php echo $button_params["url"];?>" <?php echo $onclick; ?> <?php echo $target; ?>>
			<div class="panel_a_img" style="background: url('<?php echo $img; ?>') 0 0 no-repeat;"></div>
			<div class="panel_a_caption"><?php echo $button_params["caption"];?></div>
		</a>
		<?php
	}
	else
	{
		?>
		<a class="panel_a" href="<?php echo $button_params["url"]; ?>" <?php echo $onclick; ?> <?php echo $target; ?>>
			<div class="panel_a_img" style="background-color: <?php echo $button_params["background_color"]; ?>;width:96px;height:96px;display:table-cell;vertical-align:middle;"><i class="<?php echo $button_params["fontawesome_class"]; ?>" style="color:#FFF;font-size:45px"></i></div>
			<div class="panel_a_caption"><?php echo $button_params["caption"]; ?></div>
		</a>
		<?php
	}
}
?>


<?php
//В зависимости от продукта - формируем названия
$product_name = "Docpart";//По умолчанию
if($DP_Config->product == "cms")
{
	$product_name = "Intask CMS";
}
if($DP_Config->product == "expancart")
{
	$product_name = "Expancart";
}
?>



<!-- Simple splash screen-->
<div class="splash"> <div class="color-line"></div><div class="splash-title"><h1><?php echo $product_name; ?> - Панель управления</h1><p>Загрузка фреймворка, пожалуйста, подождите... </p><div class="spinner"> <div class="rect1"></div> <div class="rect2"></div> <div class="rect3"></div> <div class="rect4"></div> <div class="rect5"></div> </div> </div> </div>
<!--[if lt IE 7]>
<p class="alert alert-danger">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
<![endif]-->

<!-- Header -->
<div id="header">
    <div class="color-line">
    </div>
	<a href="/<?php echo $DP_Config->backend_dir; ?>">
    <div id="logo" class="light-version">
        <span>
            Панель управления
        </span>
    </div>
	</a>
    <nav role="navigation">
        <div class="header-link hide-menu"><i class="fa fa-bars"></i></div>
		
		
        <div class="small-logo">
            <span class="text-primary">Панель управления</span>
        </div>
        
        <div class="mobile-menu">
            <button type="button" class="navbar-toggle mobile-menu-toggle" data-toggle="collapse" data-target="#mobile-collapse">
                <i class="fa fa-chevron-down"></i>
            </button>
            <div class="collapse mobile-navbar" id="mobile-collapse">
                <ul class="nav navbar-nav">
                    <li>
                        <a class="" href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">Выйти</a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="navbar-right">
            <ul class="nav navbar-nav no-borders">
				
				
				<?php
				//Индикаторы корректности настроек E-mail и Телефона
				
				//Для E-mail
				$email_state = 0;//Индикатор для E-mail-уведомлений. Состояние индикатора: Работает 1, Работает (но, давно не проверялось) 2, Не работает 3, Настройки есть, но не проверялись 4
				if( empty($DP_Config->from_name) || empty($DP_Config->from_email) || empty($DP_Config->smtp_mode) || empty($DP_Config->smtp_encryption) || empty($DP_Config->smtp_host) || empty($DP_Config->smtp_port) || empty($DP_Config->smtp_username) || empty($DP_Config->smtp_password) )
				{
					$email_state = 3;//Настройки не заданы, значит точно не работает
				}
				else//Настройки заданы, далее проверяем отладку
				{
					$email_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
					$email_debug_query->execute( array('email') );
					$email_debug = $email_debug_query->fetch();
					
					if( $email_debug == false )
					{
						$email_state = 4;
					}
					else
					{
						if( $email_debug['status'] == 1 )
						{
							if( time() - $email_debug['time'] < 86400 )
							{
								$email_state = 1;
							}
							else
							{
								$email_state = 2;
							}
						}
						else
						{
							$email_state = 3;//Не работает
						}
					}
				}
				switch($email_state)
				{
					case 1:
						$email_status_text = "E-mail-уведомления<br>настроены корректно";
						$email_a_style = "";
						$email_sign_after = '';
						break;
					case 2:
						$email_status_text = "E-mail-уведомления<br>работают (перепроверьте)";
						$email_a_style = "background-color:#f5f5f5;color:#000;";
						$email_sign_after = '<i class="pe-7s-info"></i>';
						break;
					case 3:
						$email_status_text = "E-mail-уведомления<br>не работают";
						$email_a_style = "background-color:#F00;color:#FFF;";
						$email_sign_after = '<i class="pe-7s-attention"></i>';
						break;
					case 4:
						$email_status_text = "E-mail-уведомления<br>настроены, но не проверены";
						$email_a_style = "background-color:#ffde00;color:#000;";
						$email_sign_after = '<i class="pe-7s-attention"></i>';
						break;
				}
				?>
				<li class="dropdown hidden-sm hidden-xs hidden-md">
                    <a href="/<?php echo $DP_Config->backend_dir; ?>/control/communications" title="Перейти на страницу контроля способов связи" style="<?php echo $email_a_style; ?>border-bottom:1px solid #e4e5e7;">
                        <i class="pe-7s-mail"></i> <div style="font-size:0.5em;display:inline-block;line-height:1em;"><?php echo $email_status_text; ?></div><?echo " ".$email_sign_after; ?>
                    </a>
                </li>
				
				
				<?php
				//Для SMS
				$sms_state = 0;//Индикатор для SMS-уведомлений. Состояние индикатора: Работает 1, Работает (но, давно не проверялось) 2, Не работает 3, Настройки есть, но не проверялись 4
				$check_sms_query = $db_link->prepare("SELECT COUNT(*) FROM `sms_api` WHERE `active` = ?;");
				$check_sms_query->execute( array(1) );
				if( $check_sms_query->fetchColumn() == 0 )
				{
					$sms_state = 3;//Настройки не заданы, значит точно не работает
				}
				else//Настройки заданы, далее проверяем отладку
				{
					$sms_debug_query = $db_link->prepare("SELECT * FROM `debug_results` WHERE `name` = ?;");
					$sms_debug_query->execute( array('sms') );
					$sms_debug = $sms_debug_query->fetch();
					
					if( $sms_debug == false )
					{
						$sms_state = 4;
					}
					else
					{
						if( $sms_debug['status'] == 1 )
						{
							if( time() - $sms_debug['time'] < 86400 )
							{
								$sms_state = 1;
							}
							else
							{
								$sms_state = 2;
							}
						}
						else
						{
							$sms_state = 3;//Не работает
						}
					}
				}
				switch($sms_state)
				{
					case 1:
						$sms_status_text = "SMS-уведомления<br>настроены корректно";
						$sms_a_style = "";
						$sms_sign_after = '';
						break;
					case 2:
						$sms_status_text = "SMS-уведомления<br>работают (перепроверьте)";
						$sms_a_style = "background-color:#f5f5f5;color:#000;";
						$sms_sign_after = '<i class="pe-7s-info"></i>';
						break;
					case 3:
						$sms_status_text = "SMS-уведомления<br>не работают";
						$sms_a_style = "background-color:#F00;color:#FFF;";
						$sms_sign_after = '<i class="pe-7s-attention"></i>';
						break;
					case 4:
						$sms_status_text = "SMS-уведомления<br>настроены, но не проверены";
						$sms_a_style = "background-color:#ffde00;color:#000;";
						$sms_sign_after = '<i class="pe-7s-attention"></i>';
						break;
				}
				?>
				<li class="dropdown hidden-sm hidden-xs hidden-md">
                    <a href="/<?php echo $DP_Config->backend_dir; ?>/control/communications" title="Перейти на страницу контроля способов связи" style="<?php echo $sms_a_style; ?>border-bottom:1px solid #e4e5e7;">
                        <i class="pe-7s-phone"></i> <div style="font-size:0.5em;display:inline-block;line-height:1em;"><?php echo $sms_status_text; ?></div><?echo " ".$sms_sign_after; ?>
                    </a>
                </li>
				
				
				
				
			
				<?php
				//Вывод быстрой навигации по наиболее востребованным функциям
				$control_items_query = $db_link->prepare('SELECT * FROM `control_items` WHERE `id` IN (?,?,?,?,?) ORDER BY `order`;');
				$control_items_query->execute( array(24, 4, 11, 26, 25) );
				$template_classes_fontawesome = array("fas fa-user-alt"=>"pe-7s-user", "fas fa-user-plus"=>"pe-7s-add-user", "fas fa-shopping-bag"=>"pe-7s-shopbag", "fas fa-shapes"=>"pe-7s-keypad", "fas fa-shopping-cart"=>"pe-7s-cart", "fas fa-money-check-alt"=>"pe-7s-credit");//Сопоставление с более подходящими под шаблон пиктограммами
				while( $control_item = $control_items_query->fetch() )
				{
					$control_item["url"] = str_replace( array("<backend>"), $DP_Config->backend_dir, $control_item["url"]);
					
					//Замена fontawesome
					$control_item['fontawesome_class'] = $template_classes_fontawesome[$control_item['fontawesome_class']];
					?>

					<li class="dropdown hidden-xs hidden-sm">
						<a href="<?php echo $control_item['url']; ?>" title="<?php echo $control_item['caption']; ?>">
							<i class="<?php echo $control_item['fontawesome_class']; ?>"></i>
						</a>
					</li>
					
					<?php
				}
				?>
				
				

				

				<!-- START Модуль индикации непросмотренных заказов -->
				<?php
				//Для работы с пользователями
				require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
				$manager_id = DP_User::getAdminId();//ID менежера, который отображает эту страницу
				?>
				<li class="dropdown">
                    <a class="dropdown-toggle label-menu-corner" href="/<?php echo $DP_Config->backend_dir; ?>/shop/orders/orders" title="Заказы">
                        <i class="pe-7s-shopbag"></i>
                        <span class="label label-success" id="not_viewed_orders_count"></span>
                    </a>
                </li>
				<script>
					var current_not_viewed_orders = -1;//Текущее количество непросмотренных заказов
					var title_original = document.title;//Исходное значение заголовка страницы
					//Функция обновления информации по просмотренным заказам
					function update_viewed_info()
					{
						var request_object = new Object;
						request_object.user_id = <?php echo $manager_id; ?>;
						
						jQuery.ajax({
							type: "POST",
							async: true,
							url: "/<?php echo $DP_Config->backend_dir; ?>/content/shop/order_process/ajax_get_orders_info.php",
							dataType: "json",//Тип возвращаемого значения
							data: "request_object="+JSON.stringify(request_object),
							success: function(answer)
							{
								if(answer.status == 1)
								{
									//Первоначальная запись текущего количества заказов
									if( current_not_viewed_orders == -1 )
									{
										current_not_viewed_orders = parseInt(answer.message);
									}
									//----------------------
									//Обработка виджета
									if( parseInt(answer.message) > 0)
									{
										document.getElementById("not_viewed_orders_count").innerHTML = answer.message;
									}
									else
									{
										document.getElementById("not_viewed_orders_count").innerHTML = "";
									}
									//----------------------
									//Обработка добавления новых заказов (т.е. если количество увеличилось во время просмотра страницы)
									if( parseInt(answer.message) > parseInt(current_not_viewed_orders) )
									{
										//Звуковой сигнал//...
										//Фавикон//...
										//Title
										document.title = "Есть новые заказы! "+title_original;
									}//~ if Индикация новых заказов
									else if( parseInt(answer.message) == 0 )//Если новых заказов нет (просмотрены на другой вкладке)
									{
										document.title = title_original;
									}
									//----------------------
									//Записываем новое текущее количество
									current_not_viewed_orders = answer.message;
								}
							}
						});
					}
					update_viewed_info();//Запрос при загрузке страницы
					//Запускаем запросы непросмотренных заказов 1 раз в 10 секунд
					var timerId = setInterval(function() {
						update_viewed_info();
					}, 300000);
				</script>
				<!-- END Модуль индикации непросмотренных заказов -->
				
				
				<li class="dropdown">
                    <a href="<?php echo $DP_Config->domain_path; ?>" target="_blank" title="Перейти на сайт">
                        <i class="pe-7s-play"></i>
                    </a>
                </li>
				
                <li class="dropdown">
                    <a href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">
                        <i class="pe-7s-upload pe-rotate-90"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</div>

<!-- Navigation -->
<aside id="menu">
    <div id="navigation">
        <div class="profile-picture">
            <div class="stats-label text-color">
                <?php
				//Блок слева - профиль пользователя и форма выхода
				//Получаем данные пользователя
				$admin_profile = DP_User::getAdminProfile();
				?>
				<form id="logout_form" method="POST" name="logout_form">
					<input type="hidden" name="logout" value="logout" />
				</form>
				<span class="font-extra-bold font-uppercase"><?php echo $admin_profile["name"]." ".$admin_profile["surname"]; ?></span>

                <div class="dropdown">
					<a class="dropdown-toggle" href="#" data-toggle="dropdown">
                        <small class="text-muted">Личный кабинет <b class="caret"></b></small>
                    </a>
					
                    <ul class="dropdown-menu animated flipInX m-t-xs">
                        <!--
						<li><a href="contacts.html">Contacts</a></li>
                        <li><a href="profile.html">Profile</a></li>
                        <li><a href="analytics.html">Analytics</a></li>
                        <li class="divider"></li>
						-->
                        <li><a href="javascript:void(0);" onclick="document.forms['logout_form'].submit();">Выйти</a></li>
                    </ul>
                </div>
            </div>
        </div>
		
		
		<div class="text-center" style="padding:5px;border-top:1px solid #e4e5e7;">
			<?php
			$edit_mode = null;
			if( isset($_COOKIE["edit_mode"]) )
			{
				$edit_mode = $_COOKIE["edit_mode"];
			}
			switch($edit_mode)
			{
				case "frontend":
					$is_frontend = 1;
					break;
				case "backend":
					$is_frontend = 0;
					break;
				default:
					$is_frontend = 1;
					break;
			}
			if($is_frontend)
			{
				?>
				Режим редактирования: <b>Фронтэнд</b> <img style="height:15px; border-radius:10px" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/earth.png" />
				<?php
			}
			else
			{
				?>
				Режим редактирования: <b>Бэкэнд</b> <img style="height:15px; border-radius:10px" src="/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/shield.png" />
				<?php
			}
			?>
		</div>
		
		<docpart type="module" name="left_cp_menu" />
    </div>
</aside>

<!-- Main Wrapper -->
<div id="wrapper">
	
	<div class="normalheader transition animated fadeIn">
		<div class="hpanel">
			<div class="panel-body">
				<a class="small-header-action" href="">
					<div class="clip-header">
						<i class="fa fa-arrow-up"></i>
					</div>
				</a>

				<div id="hbreadcrumb" class="pull-right m-t-lg">
					<docpart type="module" name="breadcrumb" />
				</div>
				<h2 class="font-light m-b-xs">
					<?php echo $DP_Content->value; ?>
				</h2>
				<small><?php echo $DP_Content->description; ?></small>
			</div>
		</div>
	</div>
	
	
	
	
    <div class="content animate-panel">
		<div class="row">
			<docpart type="main" name="main" />
		</div>
    </div>

    <!-- Right sidebar -->
    <div id="right-sidebar" class="animated fadeInRight">
		
		<!--
		<div class="p-m">
			<button id="sidebar-close" class="right-sidebar-toggle sidebar-button btn btn-default m-b-md"><i class="pe pe-7s-close"></i>
            </button>
        </div>
		-->
		
		
		
		<div class="row">
			<div class="col-lg-12 text-left" style="margin:7px;">
				<button id="sidebar-close" class="right-sidebar-toggle sidebar-button btn btn-default ">
					<i class="pe pe-7s-close"></i>
				</button>
			</div>
		
			<docpart type="module" name="left_cp_menu1" />
		</div>
    </div>

    <!-- Footer-->
    <footer class="footer">
        <span class="pull-right">
            Панель управления сайтом
        </span>
        Copyright &copy <?php echo date("Y", time()); ?>, <?php echo $DP_Config->site_name; ?>
    </footer>

</div>

<!-- Vendor scripts -->
<script src="vendor/jquery-ui/jquery-ui.min.js"></script>
<script src="vendor/slimScroll/jquery.slimscroll.min.js"></script>
<script src="vendor/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="vendor/jquery-flot/jquery.flot.js"></script>
<script src="vendor/jquery-flot/jquery.flot.resize.js"></script>
<script src="vendor/jquery-flot/jquery.flot.pie.js"></script>
<script src="vendor/flot.curvedlines/curvedLines.js"></script>
<script src="vendor/jquery.flot.spline/index.js"></script>
<script src="vendor/metisMenu/dist/metisMenu.min.js"></script>
<script src="vendor/iCheck/icheck.min.js"></script>
<script src="vendor/peity/jquery.peity.min.js"></script>
<script src="vendor/sparkline/index.js"></script>
<script src="vendor/fooTable/dist/footable.all.min.js"></script>


<!-- App scripts -->
<script src="scripts/homer.js"></script>
<script src="scripts/charts.js"></script>


<script>
	if(webix !== undefined)
	{
		webix.Touch.disable();
	}
</script>




<script>
// -------------------------------------------------
//Настройка высплывающих подсказок
toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": false,
  "progressBar": false,
  "positionClass": "toast-top-center",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "10000",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
}
// -------------------------------------------------
//Показать подсказку
function show_hint(hint_text)
{
	toastr.info(hint_text);
}
// -------------------------------------------------
</script>

<?php
//Подключение модального окна для создания кассовых чеков
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/shop/kkt/check_create_modal_window.php");
?>
</body>
</html>