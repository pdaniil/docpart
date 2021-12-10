<?php
defined('_ASTEXE_') or die('No access');

//Переменные для подстановки в input модулей поисковых строк
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/search_strs_for_inputs.php");
//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");



// Получаем информацию об офисе
$customer_office_info = array();
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
if($customer_offices[0] > 0){
	$customer_office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
	$customer_office_query->execute(array($customer_offices[0]));
	$customer_office_info = $customer_office_query->fetch(PDO::FETCH_ASSOC);
}



//Получаем данные по валюте магазина
$stmt = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = :iso_code;');
$stmt->bindValue(':iso_code', $DP_Config->shop_currency);
$stmt->execute();
$currency_record = $stmt->fetch(PDO::FETCH_ASSOC);
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}
?>
<!DOCTYPE html>
<html>
<head>
	<base href="/templates/nero/"/>


	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>


    <link href="/favicon.ico" rel="shortcut icon"/>

	
    <!-- CSS -->
	<link href="assets/css/style_all.css?v=<?=(int)$DP_Template->data_value->version;?>" rel="stylesheet" type="text/css" title="default"/>
	
	<link href="css/catalogue/catalogue.css" rel="stylesheet" type="text/css"/>
	<link href="/modules/slider/css/style.css" rel="stylesheet" type="text/css"/>
	
	<link href="css/astself.css" rel="stylesheet" type="text/css"/>

	<?php
	if( ! $DP_Content->main_flag || isset($_COOKIE["session"])){
	?>
	<!-- JS -->
	<script src="assets/js/vendors.js"></script>
	<?php
	}else{
	?>
	<script async rel="preload" src="assets/js/vendors_main.js"></script>
	<?php
	}
	?>

	<docpart type="head" name="head" />
	
	<?php
	if( ! $DP_Content->main_flag ){
	?>
	<link rel="stylesheet" href="css/docpart/style.css" type="text/css" />
	<script src="/lib/jQuery_ui/jquery-ui.js"></script>
	<link href="/lib/jQuery_ui/jquery-ui.css" rel="stylesheet">
	<?php
	}
	?>
	
</head>
<body>


<?php
if(isset($_COOKIE["session"])){
?>
<!-- Preloader -->
<div id="preloader" class="">
    <div id="status">&nbsp;</div>
</div>
<?php
}
?>


<div class="container">
<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах выполнения действий
?>
</div>



<?php
if(!empty($DP_Template->data_value->message_header)){
?>
<div class="alert alert-info" style="background-color: #f5f5f5; border: solid 1px #ddd; margin: 0; border-left: 0; border-right: 0; border-top: 0;">
	<div class="container">
		<h4><strong><i class="fa fa-bullhorn"></i> Объявление</strong></h4>
		<div><?=$DP_Template->data_value->message_header;?></div>
	</div>
</div>
<?php
}
?>



<header class="hidden-xs">
    <div class="top-menu-line" style="background: <?=$DP_Template->data_value->top_menu_color;?>;">
		<div class="container">
			<table>
				<tr>
					<td>
						<nav class="navbar navbar-default navbar-header-full yamm navbar-static-top" role="navigation">
							<docpart type="module" name="top_menu" />
						</nav>
					</td>
					<td>
						
						<?php
						//Баланс покупателя
						if(DP_User::getUserId() > 0)
						{
							?>
							<div class="new-header-user-box">
								<a href="/shop/balans" class="user_balance">
									<i><span class="balance_indicator"><?=$currency_indicator;?></span></i>
									<span class="balance_text">
										<?php
										$stmt = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
										$stmt->bindValue(':user_id', DP_User::getUserId());
										$stmt->execute();
										$balance_record = $stmt->fetch(PDO::FETCH_ASSOC);
										$balance = $balance_record["balance"];
										if($balance == "")
										{
											$balance = 0;
										}
										$balance = number_format($balance, 2, '.', ' ');
										echo $balance;
										?>
									</span>
								</a>
							</div>
							<?php
						}
						?>
						
						
						<div class="new-header-user-box dropdown">
							<?php
							$user_tab_caption = "Войти";
							$user_tab_icon = '<i class="fa fa-sign-in" aria-hidden="true"></i> ';
							if( DP_User::getUserId() != 0 )
							{
								$userProfile = DP_User::getUserProfile();
								$user_tab_caption = $userProfile["name"]." ".$userProfile["surname"];
								$user_tab_icon = '<i class="fa fa-user" aria-hidden="true"></i> ';
							}
							?>
							<span class="dropdown-toggle" data-toggle="dropdown">
								<a><?php echo $user_tab_icon . $user_tab_caption; ?></a>
							</span>
							<div class="dropdown-menu dropdown-menu-right dropdown-login-box animated flipCenter">
								<?php
								$login_form_postfix = "header_top_tab";
								require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
								?>
							</div>
						</div>
						
						
						<?php
						if( DP_User::getUserId() == 0 )
						{
						?>
						<div class="new-header-user-box">
							<a href="/users/registration"><i class="fa fa-user-plus" aria-hidden="true"></i> Регистрация</a>
						</div>
						<?php
						}
						?>
						
					</td>
				</tr>
			</table>
		</div>
	</div>
	

	
	<div class="logo-line" style="background: <?=$DP_Template->data_value->header_color;?>;">
		<div class="container">
			<div class="table-group">
				
				<div class="table-control">
					<a class="header-logo" href="<?php echo $DP_Config->domain_path; ?>">
						<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt="logotype"/>
					</a>
				</div>
				
				<div class="table-control text-right">
					<div class="geo-point-box text-left">
						<?php
						$geo_point_class = 'hidden';
						$query_geo = $db_link->prepare("SELECT `activated` FROM `modules` WHERE `id` = ?;");
						$query_geo->execute( array(38) );
						$module_geo = $query_geo->fetch();
						if($module_geo['activated'] == 1)
						{
							$geo_point_class = '';
						}
						?>
						<div class="<?=$geo_point_class;?>">
							<table>
								<tr>
									<td><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td><span><docpart type="module" name="geo_point" /></span></td>
								</tr>
							</table>
						</div>
						<?php
						if($module_geo['activated'] == 0)
						{
						?>
							<table>
								<tr>
									<td><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td><span><?=trim($customer_office_info['city']).'<br/>'.trim($customer_office_info['address']);?></span></td>
								</tr>
							</table>
						<?php
						}
						?>
					</div>
				</div>
				
				<div class="table-control text-right">
					<div class="timetable-box text-left">
					<table>
						<tr>
							<td><i class="fa fa-clock-o" aria-hidden="true"></i></td>
							<td><span><?=trim($customer_office_info['timetable']);?></span></td>
						</tr>
					</table>
					</div>
				</div>
				
				<div class="table-control text-right">
					<div class="header-phone-box"><a href="tel:<?=str_replace(array(' ','-','(',')'),'',$customer_office_info['phone']);?>" class="phone call-me"><?=$customer_office_info['phone'];?></a></div>
					<div class="header-call-box"><a href="/zapros-prodavczu">Заказать обратный звонок</a></div>
				</div>
				
			</div>
		</div>
	</div>
	


	<div class="schearch-line">
		<div class="container">
			<div class="row">
				<div class="col-sm-7 col-md-8">
					<table>
						<tr>
							<td>
								<a class="header-home-btn" href="/"><i class="fa fa-home" aria-hidden="true"></i></a>
								
								<?php
								$stmt = $db_link->prepare('SELECT COUNT(`id`) AS `count_id` FROM `shop_catalogue_categories` WHERE `published_flag` = ? AND `parent` = ?;');
								$stmt->execute(array(1,0));
								$check_categories_exist_record = $stmt->fetch(PDO::FETCH_ASSOC);
								if( $check_categories_exist_record["count_id"] > 0 )
								{
								?>
								<a class="header-cat-btn" onClick="showCatalogMenu();"><i class="fa fa-bars" aria-hidden="true"></i> Каталог <span class="hidden-sm">товаров</span></a>
								<?php
								}
								?>
							</td>
							<td class="search-table-td">
								<div class="header-search-box">
									<div class="dropdown">
										<span class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
										<ul class="dropdown-menu dropdown-menu-left dropdown-login-box animated flipCenter">
											<li><a href="javascript:void(0);" onClick="change_header_search_form(1);">Поиск по артикулу</a></li>
											<li><a href="javascript:void(0);" onClick="change_header_search_form(2);">Поиск по наименованию</a></li>
										</ul>
									</div>
									<?php
									$header_search_form_1_hidden = '';
									$header_search_form_2_hidden = 'hidden';
									if( $DP_Content->content_type == "category" || $DP_Content->url =="shop/search" )
									{
										$header_search_form_1_hidden = 'hidden';
										$header_search_form_2_hidden = '';
									}
									?>
									<form action="/shop/part_search" method="GET" class="header_search_form_1 <?=$header_search_form_1_hidden;?>">
										<div class="input-group">
											<input value="<?=$value_for_input_search;?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
											</span>
										</div>
									</form>
									<form action="/shop/search" method="GET" class="header_search_form_2 <?=$header_search_form_2_hidden;?>">
										<div class="input-group">
											<input value="<?=$value_for_input_search_string;?>" type="text" class="form-control" placeholder="По каталогу наличия" name="search_string" />
											<span class="input-group-btn">
												<button class="btn btn-ar btn-primary" type="submit">Поиск</button>
											</span>
										</div>
									</form>
								</div>
							</td>
						</tr>
					</table>
				</div>
				
				<div class="col-sm-5 col-md-4">
					<div class="menu-box">
						
						<div class="menu-box-item">
							<a title="Заказы" href="/shop/orders" class="orders-i">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#orders-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Сравнения" href="/shop/sravneniya">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#compare-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Закладки" href="/shop/zakladki">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#bookmarks-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Гараж" href="/garazh">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#garage-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Баланс" href="/shop/balans">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#balance-i"></use></svg>
							</a>
						</div>
						
						<div class="menu-box-item">
							<a title="Корзина" href="/shop/cart">
								<svg class="menu-box-icon svg-icon"><use xlink:href="/templates/nero/img/menu-i.svg#cart-i"></use></svg>
								<span class="" id="header_cart_items_count"></span>
							</a>
						</div>
						
					</div>
				</div>
			</div>
		</div>
		<div id="dp_menu">
			<div class="container">
				<div class="vertical-tabs-right">
					<?php
					include($_SERVER["DOCUMENT_ROOT"]."/modules/shop/catalogue/dp_menu.php");
					?>
				</div>
			</div>
		</div>
	</div>
</header>



<!-- header box for mobile -->
<div class="header-box-mobile hidden-sm hidden-md hidden-lg">
	<nav class="navbar navbar-default navbar-header-full yamm navbar-static-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a id="ar-brand" class="logo_min" href="<?php echo $DP_Config->domain_path; ?>">
					<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt="logotype" />
				</a>
				
				<a class="mobile-box-phone" href="tel:<?=str_replace(array(' ','-','(',')'),'',$customer_office_info['phone']);?>"><?=$customer_office_info['phone'];?></a>
				
				<button type="button" class="navbar-toggle header_fa_user_btn" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2"><i class="fa fa-user"></i></button>
				
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"><i class="fa fa-bars"></i></button>
			</div>
			
			<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
				<docpart type="module" name="top_menu_catalog" />
				<docpart type="module" name="top_menu" />
			</div>
			
			<div class="row">
				<div class="collapse" id="bs-example-navbar-collapse-2">
				<div class="header-user-box">
					<div class="new-header-user-box">
						<?php
						if($module_geo['activated'] == 1)
						{
						?>
						<div class="geo-point-user-box" onclick="openPopupWindow_CityList();">
							<table>
								<tr>
									<td class="geo-td-icon"><i class="fa fa-location-arrow" aria-hidden="true"></i></td>
									<td class="geo-td-text"><span><?=trim($customer_office_info['city']).'<br/>'.trim($customer_office_info['address']);?></span></td>
								</tr>
							</table>
						</div>
						<?php
						}
						//Единый механизм формы авторизации
						$login_form_postfix = "header_top_tab_mob";
						require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
						?>
					</div>
				</div>
				</div>
			</div>
		</div>
	</nav>
	
	<div class="col-xs-12 mobile-search-div">
		<table>
			<tr>
				<td>
					<div class="header-search-box">
						<div class="dropdown">
							<span class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-chevron-down" aria-hidden="true"></i></span>
							<ul class="dropdown-menu dropdown-menu-left dropdown-login-box animated flipCenter">
								<li><a href="javascript:void(0);" onClick="change_header_search_form(1);">Поиск по артикулу</a></li>
								<li><a href="javascript:void(0);" onClick="change_header_search_form(2);">Поиск по наименованию</a></li>
							</ul>
						</div>
						<form action="/shop/part_search" method="GET" class="header_search_form_1 <?=$header_search_form_1_hidden;?>">
							<div class="input-group">
								<input value="<?=$value_for_input_search;?>" type="text" class="form-control" placeholder="Поиск по артикулу" name="article" />
								<span class="input-group-btn">
									<button class="btn btn-ar" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
								</span>
							</div>
						</form>
						<form action="/shop/search" method="GET" class="header_search_form_2 <?=$header_search_form_2_hidden;?>">
							<div class="input-group">
								<input value="<?=$value_for_input_search_string;?>" type="text" class="form-control" placeholder="По каталогу наличия" name="search_string" />
								<span class="input-group-btn">
									<button class="btn btn-ar" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
								</span>
							</div>
						</form>
					</div>
				</td>
				<td>
					<a href="/shop/cart" class="header-cart-box">
						<i class="fa fa-shopping-cart" aria-hidden="true"></i>
						<span class="" id="header_cart_items_count_mobile"></span>
					</a>
				</td>
			</tr>
		</table>
	</div>
</div>
<div class="row"></div>
<!-- end header box for mobile -->



<?php
if($DP_Content->main_flag)
{
?>
<div class="slider-line">
<div class="container">
<div class="row">

	<div class="row"></div>
	<div class="col-md-8 col-lg-8">
		<?php
		//Подключение встроенного слайдера с редактором из ПУ
		require_once($_SERVER["DOCUMENT_ROOT"]."/modules/slider/slider.php");
		?>
	</div>
	
	<div class="col-md-4 col-lg-4">
		<div class="row">
			<div class="col-sm-6 col-md-12 col-lg-12">
				<a href="<?=$DP_Template->data_value->banner_url_1;?>" class="cat-tile cat-tile-img-1">
					<span class="cat-tile-text">
					<?=$DP_Template->data_value->banner_name_1;?>
					</span>
				</a>
			</div>
			<div class="col-sm-6 col-md-12  col-lg-12">
				<a href="<?=$DP_Template->data_value->banner_url_2;?>" class="cat-tile cat-tile-img-2">
					<span class="cat-tile-text">
					<?=$DP_Template->data_value->banner_name_2;?>
					</span>
				</a>
			</div>
		</div>
	</div>
	
</div>
</div>
</div>
<?php
}
?>

<?php
if($DP_Content->main_flag)
{
?>
<div class="slider-line tile-line-box" style="background: <?=$DP_Template->data_value->ucats_line_bg;?>;">
<div class="container">
<div class="row">

	<?php
	if($DP_Config->ucats_shiny != '' || 
	$DP_Config->ucats_disks != '' || 
	$DP_Config->ucats_accessories != '' || 
	$DP_Config->ucats_to != '' || 
	$DP_Config->ucats_oil != '' || 
	$DP_Config->ucats_akb != '' || 
	$DP_Config->ucats_caps != '' || 
	$DP_Config->ucats_bolty != '')
	{
	?>
		<div class="tile-line">
		<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12 tile_box">
		
			
			<?php
			if( $DP_Config->ucats_shiny != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/shiny" class="tile tile-img-3">
						<span class="tile-text">
						Шины
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_disks != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolesnye-diski" class="tile tile-img-4">
						<span class="tile-text">
						Диски
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_accessories != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/avtoaksessuary" class="tile tile-img-5">
						<span class="tile-text">
						Аксессуары
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_oil != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/avtoximiya" class="tile tile-img-6">
						<span class="tile-text">
						Автохимия
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_akb != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/akkumulyatory" class="tile tile-img-7">
						<span class="tile-text">
						Аккумуляторы
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_caps != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolpaki" class="tile tile-img-8">
						<span class="tile-text">
						Колпаки
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_bolty != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/kolesnye-gajki-bolty-prostavki" class="tile tile-img-9">
						<span class="tile-text">
						Болты, гайки
						</span>
					</a>
				</div>
				<?php
			}
			if( $DP_Config->ucats_bolty != '' )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-3">
					<a href="/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya" class="tile tile-img-10">
						<span class="tile-text">
						Каталог ТО
						</span>
					</a>
				</div>
				<?php
			}
			/*
			if( true )
			{
				?>
				<div class="col-xs-6 col-sm-4 col-md-4 col-lg-2">
					<a href="/katalog-lamp" class="tile tile-img-11">
						<span class="tile-text">
						Каталог ламп
						</span>
					</a>
				</div>
				<?php
			}
			*/
			?>
		</div>
		</div>
		</div>
		<?php
	}
	?>
</div>
</div>
</div>
<?php
}
?>



<div id="sb-site">
<div class="boxed">



<?php
if( ! $DP_Content->main_flag)
{
?>
<div class="main-header">
	<div class="container">
		<div class="row">
			<div class="col-sm-12">
				<h1 class="page-title"><?php echo $DP_Content->value; ?></h1>
			</div>
			<div class="col-sm-12">
				<docpart type="module" name="bread_crumbs" />
			</div>
		</div>
	</div>
</div>
<?php
}
?>



<?php
if( ! isset($product_id) )
{
	$product_id = null;
}
if( $DP_Content->content_type == "category" || $DP_Content->url =="shop/search" || $DP_Content->id == 324 || $DP_Content->id == 326 || $DP_Content->id == 328 || $DP_Content->id == 330 || $DP_Content->id == 332 || $DP_Content->id == 334 || isset( $DP_Content->service_data["sp"] ))
{
	$left_col_class = " class=\"hidden-xs hidden-sm col-md-3\"";
	$right_col_class = " class=\"col-md-9\"";
	$btn_show_hide_left_coll = " class=\"hidden-md hidden-lg\"";
}
else
{
	$left_col_class = " class=\"hidden-xs hidden-sm hidden-md hidden-lg\"";
	$right_col_class = " class=\"col-md-12\"";
	$btn_show_hide_left_coll = " class=\"hidden-xs hidden-sm hidden-md hidden-lg\"";
}

// Для некоторых страниц нужно дополнительно добавить обертку
$add_row_div = 'style="margin:0;"';
if( $product_id > 0 || $DP_Content->content_type == "category" || $DP_Content->id == 298 || $DP_Content->id == 302 || $DP_Content->id == 376 || $DP_Content->id == 385 || $DP_Content->id == 385 || isset($DP_Content->service_data["article_search_chpu"]))
{
	$add_row_div = '';
}
?>

<div class="container">
    <div class="row">
		
		<div <?=$btn_show_hide_left_coll;?>>
			<div class="row" style="margin: 0px 0px 15px 0px;">
			<div class="col-xs-12">
				<a onClick="show_hide_left_coll();" style="text-decoration: none; background-color: #f9f9f9; border: 1px solid #ddd; color: #222; position: relative; padding: 5px 10px;"><i class="fa fa-filter" aria-hidden="true"></i> <span>Отобразить фильтры</span></a>
			</div>
			</div>
			<script>
			function show_hide_left_coll(){
				if ( $('#left_col').hasClass('hidden-xs')) {
					$('#left_col').removeClass('hidden-xs');
					$('#left_col').removeClass('hidden-sm');
				}else{
					$('#left_col').addClass('hidden-sm');
					$('#left_col').addClass('hidden-xs');
				}
			}
			</script>
		</div>
		
		<div <?php echo $left_col_class;?> id="left_col">
			<docpart type="module" name="left_menu" />
		</div>
		
		<div <?php echo $right_col_class;?> id="right_col">
            <div class="row" id="Container">
				<?php
				//Получаем дополнительный текст для URL
				$text_before_main = "";//Если текст нужен до основного содержимого
				$text_after_main = "";//Если текст нужен после основного содержимого
				$url = getPageUrl();
				
				$stmt = $db_link->prepare('SELECT * FROM `text_for_url` WHERE `url` = :url;');
				$stmt->bindValue(':url', $url);
				$stmt->execute();
				$url_text_record = $stmt->fetch(PDO::FETCH_ASSOC);
				
				if( $url_text_record != false )
				{
					if($url_text_record["before_main"] == 1)
					{
						$text_before_main = $url_text_record["content"];
					}
					else
					{
						$text_after_main = $url_text_record["content"];
					}
				}
				
				// Дополнительный текст страницы
				if($text_before_main != ''){
					echo "<div class=\"col-lg-12\"><br/>".$text_before_main."</div>";
				}
				?>
				
				<div class="row" <?=$add_row_div;?>>
				<div class="col-lg-12">	
				<docpart type="main" name="main" />
				</div>
				</div>
				
				<?php
				//Отображаем блок новостей на главной
				if($DP_Content->main_flag){
					
					$news_access = 1;
					$root_content = 311;// id корневого материала раздела Новости
					$news_count = 4;// Количество новостей для отображения
					
					// Проверим включен ли модуль новостей
					$query_news_module = $db_link->prepare("SELECT `activated` FROM `modules` WHERE `id` = ?;");
					$query_news_module->execute( array(49) );
					$news_module_row = $query_news_module->fetch();
					if($news_module_row['activated'] == 0)
					{
						$news_access = 0;
					}
					
					// Проверим что корневой материал новостей опубликован
					$stmt = $db_link->prepare('SELECT `published_flag` FROM `content` WHERE `id` = ?;');
					$stmt->execute(array($root_content));
					$news = $stmt->fetch(PDO::FETCH_ASSOC);
					if($news['published_flag'] == 0){
						$news_access = 0;
					}
					
					if($news_access === 1){
						
						$news_arr = array();
						
						//Получаем новости из БД
						$stmt = $db_link->prepare('SELECT `id`, `value`, `time_created`, `description_tag`, `url` FROM `content` WHERE `parent` = :parent AND `published_flag` = 1 ORDER BY `id` DESC LIMIT :limit;');
						$stmt->bindValue(':parent', (int)$root_content);
						$stmt->bindValue(':limit', (int)$news_count, PDO::PARAM_INT);
						$stmt->execute();
						while($news = $stmt->fetch(PDO::FETCH_ASSOC))
						{
							$news_arr[] = $news;
						}
						
						if(!empty($news_arr)){
							?>
							<div class="col-lg-12">
								<h2 class="section-title" onClick="location='/novosti';">Новости</h2>
							</div>
							
							<div class="news_box col-lg-12">
							<div class="row">
							<?php
							foreach($news_arr as $news){
								$news["img"] = '';
								if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/news/".$news["id"].".jpg")){
									$news["img"] = "/content/files/news/".$news["id"].".jpg";
								}else if(file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/news/".$news["id"].".png")){
									$news["img"] = "/content/files/news/".$news["id"].".png";
								}
							?>
								<div class="col-sm-6 col-md-3">
									<div class="news_item_box">
										<a href="<?php echo "/".$news["url"]; ?>">
											<div>
												<?php
												if($news["img"] == ''){
												?>
												<div class="news_item_img"><i style="color: <?=$DP_Template->data_value->news_color;?>; font-size: 85px; padding-top: 33px;" class="fa fa-picture-o" aria-hidden="true"></i></div>
												<?php
												}else{
												?>
												<div class="news_item_img" style="background:url('<?=$news["img"];?>') no-repeat; background-position: center;"></div>
												<?php
												}
												?>
												<div class="news_item_name"><?php echo $news["value"]; ?></div>
												<div class="news_item_text"><?php echo $news["description_tag"]; ?></div>
												<small class="news_item_clock"><i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo date("d.m.Y", $news["time_created"]); ?></small>
											</div>
										</a>
									</div>
								</div>
							<?php
							}
							?>
							</div>
							</div>
							<?php
						}
					}
				}
				?>
				
				<?php
				// Дополнительный текст страницы
				if($text_after_main != ''){
					echo "<div class=\"col-lg-12\"><br/>".$text_after_main."</div>";
				}
				?>
				
			</div>
		</div>
	</div>
</div>



<aside id="footer-widgets" style="background: <?=$DP_Template->data_value->footer_bg;?>;">
    <div class="container">
        <div class="row">
			<div class="col-md-7">
               
				<div class="row">
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-1" />
					</div>
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-2" />
					</div>
					<div class="col-sm-4">
						<docpart type="module" name="footer-menu-3" />
					</div>
				</div>
				
				<h3 class="footer-widget-title">Отправить запрос</h3>
                <p>Если Вы не нашли нужные запчасти, или Вам требуется помощь в подборе,<br/>отправьте нам запрос - мы Вам поможем</p>
				<div class="input-group">
					<a class="btn btn-block btn-ar btn-primary" href="/zapros-prodavczu">Отправить запрос продавцу</a>
                </div>
				
            </div>
            
			<div class="col-md-1"></div>
            
			<div class="col-md-4">
				<div class="row">
					
					<div class="col-sm-6 col-md-12">
						<h3 class="footer-widget-title">Контакты</h3>
						<div><?='г. '.trim($customer_office_info['city']).' ул. '.trim($customer_office_info['address']);?></div>
						<div><?=trim($customer_office_info['phone']);?></div>
						<div><?=trim($customer_office_info['email']);?></div>
					</div>
					<div class="col-sm-6 col-md-12">
						<h3 class="footer-widget-title">Режим работы</h3>
						<div><?=trim($customer_office_info['timetable']);?></div>
					</div>
					
					<?php
					$data_value = (array) $DP_Template->data_value;
					if(!empty($data_value)){
						$pay_arr = array();
						foreach($data_value as $item_data_value_key => $item_data_value){
							if(strpos($item_data_value_key, 'pay_') === 0){
								if($item_data_value === 1){
									$pay_arr[] = str_replace('pay_','',$item_data_value_key);
								}
							}
						}
						if(!empty($pay_arr)){
							?>
							<div class="col-xs-12">
								<h3 class="footer-widget-title">Принимаем к оплате</h3>
								<div style="line-height:1em;">
									<?php
									foreach($pay_arr as $item_pay_name){
									?>
									<div class="footer_pay_box">
										<div class="footer_pay_logo" style="background:url('/content/files/images/icons/pay/<?=$item_pay_name;?>.jpg') no-repeat; background-position:center;"></div>
									</div>
									<?php
									}
									?>
								</div>
							</div>
							<?php
						}
					}
					?>
					
				</div>
            </div>
			
        </div> <!-- row -->
    </div> <!-- container -->
</aside> <!-- footer-widgets -->



<footer id="footer" style="position: relative; background: <?=$DP_Template->data_value->footer_bg;?>;">
	
	<p>&copy; <?php echo date("Y", time()); ?> <?php echo str_replace(array('http://','https://','/'),'',$DP_Config->domain_path); ?></p>
	
	<div style="text-align: left; position: absolute; left: 6px; top: 2px;">
		<a class="hidden-xs" title="Сайт работает на платформе Docpart" target="_blank" href="https://docpart.ru/">
			<img style="background-color: #fff; border-radius: 2px; padding: 0px; position: absolute; top: 5px; left: 0px; height: 35px; border: 1px solid #ccc;" src="img/logo_footer_transparent.png" border="0"/>
		</a>
	</div>

	<div class="icons-holder hidden">

	</div>

	
</footer>

</div> <!-- boxed -->
</div> <!-- sb-site -->

<?php
//Подключение скрипта нижней панели
require_once($_SERVER["DOCUMENT_ROOT"]."/modules/shop/bottom_panel/bottom_panel.php");
?>

<div id="back-top">
    <a href="#header"><i class="fa fa-chevron-up"></i></a>
</div>

<script src="assets/js/styleswitcher.js"></script>
<script>SyntaxHighlighter = {all: function(){return;}};</script>

<script async src="assets/js/app.js"></script>

<?php
if( ! $DP_Content->main_flag ){
?>

<script src="assets/js/DropdownHover.js"></script>
<script src="assets/js/holder.js"></script>
<script src="assets/js/commerce.js"></script>
<script src="assets/js/e-commerce_product.js"></script>

<?php
}
?>

</body>
</html>