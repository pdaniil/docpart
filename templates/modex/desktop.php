<?php
defined('_ASTEXE_') or die('No access');

//Переменные для подстановки в input модулей поисковых строк
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/search_strs_for_inputs.php");

//Стиль Header
$header_class = "";
$navbar_logo_class = "";//Подкласс для логотипа блока меню. В зависимости от наличия Header.
switch($DP_Template->data_value->header_style)
{
	case "light":
		$header_class = "hidden-xs header-full";
		$navbar_logo_class = " hidden-lg hidden-md hidden-sm";
		break;
	case "dark":
		$header_class = "hidden-xs header-full";
		$navbar_logo_class = " hidden-lg hidden-md hidden-sm";
		break;
	case "no_header":
		$header_class = "hidden-xs header-full hidden-sm hidden-md hidden-lg no_header";
		$navbar_logo_class = "";
		break;
	default:
		$header_class = "hidden-xs header-full";
		$navbar_logo_class = " hidden-lg hidden-md hidden-sm";
}

//Стиль Navbar
$navbar_class = "";
switch($DP_Template->data_value->navbar_style)
{
	case "light":
		$navbar_class = "";
		break;
	case "dark":
		$navbar_class = "navbar-dark";
		break;
	case "inverse":
		$navbar_class = "navbar-inverse";
		break;
	default:
		$navbar_class = "";
}

// Получаем информацию об офисе
$customer_office_info = array();
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/order_process/get_customer_offices.php");
if($customer_offices[0] > 0){
	$customer_office_query = $db_link->prepare('SELECT * FROM `shop_offices` WHERE `id` = ?;');
	$customer_office_query->execute(array($customer_offices[0]));
	$customer_office_info = $customer_office_query->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
	<base href="/templates/modex/"/>


	<meta charset="UTF-8"/>

	<?php if(preg_match("/katalog-laximo?/i", $DP_Content->url)) { ?>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<?php } else { ?>
		<meta name="viewport" content="width=device-width, initial-scale=1"/>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
	<?php } ?>

    <link href="/favicon.ico" rel="shortcut icon"/>

    <!-- CSS -->
	<link href="assets/css/preload.css" rel="stylesheet" type="text/css"/>
	<link href="assets/css/vendors.css" rel="stylesheet" type="text/css"/>
	<link href="assets/css/syntaxhighlighter/shCore.css" rel="stylesheet" type="text/css"/>
	<link href="/lib/jQuery_ui/jquery-ui.css" rel="stylesheet" type="text/css"/>

	<link href="assets/css/style_color.css?v=<?=(int)$DP_Template->data_value->version;?>" rel="stylesheet" type="text/css" title="default"/>
	<link href="assets/css/width-<?php echo $DP_Template->data_value->container_type; ?>.css" rel="stylesheet" type="text/css" title="default"/>

	<link href="/templates/modex/css/catalogue/catalogue.css" rel="stylesheet" type="text/css"/>
	<link href="/modules/slider/css/style.css" rel="stylesheet" type="text/css"/>
	
	<link href="css/astself.css" rel="stylesheet" type="text/css"/>


	<!-- JS -->
	<script src="assets/js/vendors.js"></script>
	<script src="/lib/jQuery_ui/jquery-ui.js"></script>


	<docpart type="head" name="head" />
	<link rel="stylesheet" href="/templates/<?php echo $DP_Template->name; ?>/css/docpart/style.css" type="text/css" />
	<script src="/lib/jQuery_ui/jquery-ui.js"></script>
	<link href="/lib/jQuery_ui/jquery-ui.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=PT+Sans:regular,italic,bold,bolditalic" rel="stylesheet" type="text/css" />

	<?php if(!preg_match("/katalog-laximo?/i", $DP_Content->url)) { ?>
		<script src="/content/laximo/com_guayaquil/assets/colorbox/jquery.colorbox.js" type="text/javascript"></script>
	<?php } ?>
	
</head>
<body>



<?php if(!preg_match("/katalog-laximo?/i", $DP_Content->url)) { ?>
	<!-- Preloader -->
	<div id="preloader" class="">
		<div id="status">&nbsp;</div>
	</div>
<?php } ?>


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



<div class="header-top-line">
	<div class="container">
		<div class="row">
			<div class="col-xs-6">
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
					<!-- Модуль географического узла -->
					<docpart type="module" name="geo_point" />
				</div>
				<?php
				if($module_geo['activated'] == 0)
				{
					// Если модуль выключен то отображаем данные из настроек магазина
				?>
					<i style="font-size:13px;" class="fa fa-map-marker hidden" aria-hidden="true"></i>
					<span><?='г. '.trim($customer_office_info['city']).' ул. '.trim($customer_office_info['address']);?></span>
				<?php
				}
				?>
			</div>

			<div class="col-xs-6 text-right">
				<span><?=trim($customer_office_info['phone']);?></span>
				<i style="margin:0px 15px;" class="fa fa-exchange hidden-xs" aria-hidden="true"></i>
				<span class="hidden-xs"><?=trim($customer_office_info['email']);?></span>
			</div>
		</div>
	</div>
</div>



<div id="header-box">
<header id="header-full-top" class="<?php echo $header_class; ?>">
    <div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="table-group">
					<div class="table-control">
						<a class="header-logo" href="<?php echo $DP_Config->domain_path; ?>">
							<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt=""/>
						</a>
					</div>
					<div class="table-control header-search-box">
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
						<script>
							//Функция изменяет форму поиска
							function change_header_search_form(id){
								if(id == 1){
									if($('.header_search_form_1').hasClass('hidden')){
										$('.header_search_form_1').removeClass('hidden');
										if( ! $('.header_search_form_2').hasClass('hidden')){
											$('.header_search_form_2').addClass('hidden');
										}
									}
								}else if(id == 2){
									if($('.header_search_form_2').hasClass('hidden')){
										$('.header_search_form_2').removeClass('hidden');
										if( ! $('.header_search_form_1').hasClass('hidden')){
											$('.header_search_form_1').addClass('hidden');
										}
									}
								}
								return false;
							}
						</script>
					</div>
					<div class="table-control header-user-box">
						<div class="new-header-user-box">
							<a href="/shop/cart" class="header-cart-box">
								<span id="header_cart_items_count"></span>
								<span id="header_cart_items_sum"></span>
								<i class="fa fa-shopping-cart"></i>
							</a>
							<script>
								//Функция обновления информации по корзине
								function updateCartInfoHeader(){
									jQuery.ajax({
										type: "POST",
										async: true,
										url: "/content/shop/order_process/ajax_get_cart_info.php",
										dataType: "json",
										success: function(answer){
											
											//answer.cart_items_count = 3;
											//answer.cart_items_sum = 300;
											
											document.getElementById("header_cart_items_count").innerHTML = answer.cart_items_count;
											document.getElementById("header_cart_items_sum").innerHTML = answer.cart_items_sum;
											if( answer.cart_items_count == 0 ){
												//document.getElementById("header_cart_items_count").setAttribute("class", "hidden badge badge-default badge-round ");//Указатель количества
											}
											else{
												//document.getElementById("header_cart_items_count").setAttribute("class", "badge badge-primary badge-round");//Указатель количества
											}}
									});
								}

								updateCartInfoHeader();//После загрузки страницы обновляем модуль корзин
								//Функция показа лэйбла "Добавлено"
								//function showAdded(){return false;}//Расскомментировать если убрана нижняя панель
							</script>
						</div>
						<div class="new-header-user-box dropdown">
							<?php
							require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
							$user_tab_caption = "Личный кабинет";
							if( DP_User::getUserId() != 0 )
							{
								$userProfile = DP_User::getUserProfile();
								$user_tab_caption = '';
								if( isset( $user_profile["name"] ) )
								{
									$user_tab_caption = $user_profile["name"];
								}
								if( isset($user_profile["surname"]) )
								{
									if( $user_tab_caption != '' )
									{
										$user_tab_caption = $user_tab_caption.' ';
									}
									$user_tab_caption = $user_tab_caption.$user_profile["surname"];
								}
								if( $user_tab_caption == '' )
								{
									$user_tab_caption = 'Имя не указано';
								}
							}
							?>
							<span class="dropdown-toggle" data-toggle="dropdown">
								<i class="fa fa-chevron-down" aria-hidden="true"></i>
								<span><?php echo $user_tab_caption; ?></span>
								<i class="fa fa-user" aria-hidden="true"></i>
							</span>
							<div class="dropdown-menu dropdown-menu-right dropdown-login-box animated flipCenter">
								<?php
								//Единый механизм формы авторизации
								$login_form_postfix = "header_top_tab";
								require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>
</header>



<nav class="navbar navbar-default navbar-header-full <?php echo $navbar_class; ?> yamm navbar-static-top" role="navigation" id="header">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1"><i class="fa fa-bars"></i></button>
			
            <a id="ar-brand" class="logo_min<?php echo $navbar_logo_class; ?>" href="<?php echo $DP_Config->domain_path; ?>">
				<img src="<?=$DP_Template->data_value->logo_file;?>?v=<?=(int)$DP_Template->data_value->version;?>" alt=""/>
			</a>
			
			<button type="button" class="navbar-toggle header_fa_user_btn" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2"><i class="fa fa-user"></i></button>
        </div> <!-- navbar-header -->
		
        <!-- Collect the nav links, forms, and other content for toggling -->

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <docpart type="module" name="top_menu_catalog" />
            <docpart type="module" name="top_menu" />
        </div><!-- navbar-collapse -->
		<button class="no_header_fa_user_btn" type="button" data-toggle="collapse" data-target="#bs-example-navbar-collapse-2"><i class="fa fa-user"></i></button>
		<div class="row">
			<div class="collapse " id="bs-example-navbar-collapse-2">
			<div class="header-user-box">
			<div class="new-header-user-box">
			<?php
			//Единый механизм формы авторизации
			$login_form_postfix = "header_top_tab_mob";
			require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
			?>
			</div>
			</div>
			</div>
        </div><!-- navbar-collapse -->
		
    </div><!-- container -->
</nav>
</div>



<!-- search box for mobile -->
<div class="col-xs-12 hidden-sm hidden-md hidden-lg" style="margin-top: 20px;">
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
</div>



<div id="sb-site">
<div class="boxed">



<?php
if( ! $DP_Content->main_flag)
{
?>
<header class="main-header">
	<div class="container">
		<div class="row">
			<div class="col-sm-6">
				<h1 class="page-title"><?php echo $DP_Content->value; ?></h1>
			</div>
			<div class="col-sm-6">
				<docpart type="module" name="bread_crumbs" />
			</div>
		</div>
	</div>
</header>
<?php
}
else
{
?>
<div class="container">
	<?php
	//Подключение встроенного слайдера с редактором из ПУ
	require_once($_SERVER["DOCUMENT_ROOT"]."/modules/slider/slider.php");
	?>
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
				echo "<div class=\"col-lg-12\">".$text_before_main."</div>";
				?>
				<div class="row" <?=$add_row_div;?>>
				<div class="col-lg-12">	
				<docpart type="main" name="main" />
				</div>
				</div>
				<?php
				echo "<div class=\"col-lg-12\">".$text_after_main."</div>";
				
				
				
				//Отображаем блок новостей на главной
				if($DP_Content->main_flag){
					$root_content = 311;// id корневого материала раздела Новости
					$news_count = 4;// Количество новостей для отображения
					
					$news_arr = array();
					
					//Получаем новости из БД
					$stmt = $db_link->prepare('SELECT `id`, `value`, `time_created`, `description_tag`, `url` FROM `content` WHERE `parent` = :parent ORDER BY `id` DESC LIMIT :limit;');
					$stmt->bindValue(':parent', (int)$root_content);
					$stmt->bindValue(':limit', (int)$news_count, PDO::PARAM_INT);
					$stmt->execute();
					while($news = $stmt->fetch(PDO::FETCH_ASSOC))
					{
						$news_arr[] = $news;
					}
					
					if(!empty($news_arr)){
						?>
						<div class="news_box col-lg-12">
						<h2 class="section-title" onClick="location='/novosti';">Новости</h2>
						<div class="row">
						<?php
						foreach($news_arr as $news){
						?>
							<div class="col-sm-6 col-md-3">
								<div class="news_item_box">
									<a href="<?php echo "/".$news["url"]; ?>">
										<div>
											<i class="fa fa-link" aria-hidden="true"></i>
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
				?>
			</div>
		</div>
	</div>
</div>





<aside id="footer-widgets">
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
										<div class="footer_pay_logo" style="background:url('/content/files/images/icons/pay/<?=$item_pay_name;?>.jpg') no-repeat; background-size:contain; background-position:center;"></div>
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





<footer id="footer" style="position: relative;">
	
	<p>&copy; <?php echo date("Y", time()); ?> <?php echo str_replace(array('http://','https://','/'),'',$DP_Config->site_name); ?></p>
	
	<div style="text-align: left; position: absolute; left: 6px; top: 2px;">
		<script src="https://yastatic.net/es5-shims/0.0.2/es5-shims.min.js"></script>
		<script src="https://yastatic.net/share2/share.js"></script>
		<div class="ya-share2" data-services="vkontakte,facebook,odnoklassniki,moimir,twitter,viber,whatsapp" data-limit="0"></div>
		
		<a class="hidden-xs" title="Сайт работает на платформе Docpart" target="_blank" href="https://docpart.ru/">
			<img style="background-color: #fff; border-radius: 2px; padding: 0px; position: absolute; top: 5px; left: 40px; height: 35px; border: 1px solid #ccc;" src="https://docpart.ru/content/files/images/Logo_footer_transparent.png" border="0"/>
		</a>
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

<!--<script src="assets/js/syntaxhighlighter/shCore.js"></script>
<script src="assets/js/syntaxhighlighter/shBrushXml.js"></script>
<script src="assets/js/syntaxhighlighter/shBrushJScript.js"></script>-->
<script>SyntaxHighlighter = {all: function(){return;}};</script>

<script src="assets/js/DropdownHover.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/holder.js"></script>
<script src="assets/js/commerce.js"></script>
<script src="assets/js/e-commerce_product.js"></script>

</body>
</html>