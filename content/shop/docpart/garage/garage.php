<?php
defined('_ASTEXE_') or die('No access');
//Скрипт для корневого раздела "Гараж"


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();//ID пользователя


$transmission = array("akpp"=>"АКПП", "mkpp"=>"МКПП", "robot"=>"Робот");


//Получаем id древовидного списка с наименованием Автомобили
$tree_list_cars_id = 0;

$tree_list_cars_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_tree_lists` WHERE `caption` = :caption;');
$tree_list_cars_query->bindValue(':caption', 'Автомобили');
$tree_list_cars_query->execute();
if( $tree_list_cars_query->fetchColumn() > 0 )
{
	$tree_list_cars_query = $db_link->prepare('SELECT `id` FROM `shop_tree_lists` WHERE `caption` = :caption;');
	$tree_list_cars_query->bindValue(':caption', 'Автомобили');
	$tree_list_cars_query->execute();
	
	$tree_list_cars_record = $tree_list_cars_query->fetch();
	$tree_list_cars_id = $tree_list_cars_record["id"];
}


if( $user_id > 0)
{
	if( ! empty( $_POST["action"] ) )
	{
		if( $_POST["action"] == "delete_car" )
		{
			$car_id = (int)$_POST["car_id"];
			
			$delete_query = $db_link->prepare('DELETE FROM `shop_docpart_garage` WHERE `id` = :car_id AND `user_id` = :user_id;');
			$delete_query->bindValue(':car_id', $car_id);
			$delete_query->bindValue(':user_id', $user_id);
			
			
			if( ! $delete_query->execute() )
			{
				$error_message = urlencode("Ошибка удаления автомобиля");
				?>
				<script>
					location="/garazh?error_message=<?php echo $error_message; ?>";
				</script>
				<?php
				exit;
			}
			else
			{
				$success_message = urlencode("Автомобиль успешно удален");
				?>
				<script>
					location="/garazh?success_message=<?php echo $success_message; ?>";
				</script>
				<?php
				exit;
			}
		}
	}
	else//Действий нет - выводим страницу
	{
		?>
		<div class="col-md-12" style="padding-bottom:20px;">
			<a class="btn btn-ar btn-primary" href="/garazh/avtomobil"><i class="fa fa-car"></i> <i class="fa fa-plus"></i>Добавить новый автомобиль</a>
			<a href="/garazh/bloknot?garage=0" class="btn btn-ar btn-primary"><i class="fa fa-pencil-square-o"></i> Общий блокнот</a>
		</div>
		
		<style>
		.btn_margin
		{
			margin-bottom:5px;
		}
		<?php
		if( strtolower($DP_Template->name) == "limo" )
		{
			?>
			.btn-default
			{
				color:#FFF!important;
			}
			<?php
		}
		?>
		</style>

		<div class="col-md-12">
			
			
			<script>
			function delete_car(car_id)
			{
				if( !confirm("Автомобиль будет безвозвратно удален из гаража. Продолжить?") )
				{
					return;
				}
				
				document.getElementById("car_id_to_delete").value = car_id;
				
				document.forms["delete_car_form"].submit();
			}
			</script>
			<form name="delete_car_form" method="POST">
				<input type="hidden" name="action" value="delete_car" />
				<input type="hidden" name="car_id" id="car_id_to_delete" value="" />
			</form>
			
			
			
			
			
			
			
			
			
			<script>
			//Переход на запрос продавцу
			function request_to_seller(car_id)
			{
				//alert(car_id);
				
				//Куки ставим на минуту
				var date = new Date(new Date().getTime() + 60 * 1000);
				document.cookie = "seller_request="+car_id+"; path=/; expires=" + date.toUTCString();
				
				location = "/zapros-prodavczu";
			}
			</script>
			
			
			
			
			<script>
        	//Переход на autoxp
        	function autoxp_redirect(dir)
        	{
        		//Сама проверка
        		jQuery.ajax({
        			type: "GET",
        			async: false, //Запрос синхронный
        			url: "/autoxp_clicks_control.php",
        			dataType: "json",//Тип возвращаемого значения
        			success: function(answer)
        			{
        				if(answer == 0)
        				{
        					alert("Превышен лимит запросов");
        					location.reload();
        				}
        				else
        				{
        					location = dir;
        				}
        			}
        		});
        	}
			</script>
			
			
			
			<?php
			$cars_query = $db_link->prepare('SELECT *, (SELECT `caption` FROM `shop_docpart_cars` WHERE `id` = `shop_docpart_garage`.`mark_id`) AS `mark` FROM `shop_docpart_garage` WHERE `user_id` = :user_id;');
			$cars_query->bindValue(':user_id', $user_id);
			$cars_query->execute();
			while( $car = $cars_query->fetch() )
			{
				?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><?php echo $car["mark"]." ".$car["model"]." ".$car["year"]." года"; ?> - <?php echo $car["caption"]; ?></h3>
					</div>
					<div class="panel-body">
						
						<div class="row">
							<div class="col-md-8">
								<p><b>Кузов: </b><?php echo $car["body_type"]; ?>. <b>Объем двигателя: </b><?php echo $car["engine_value"]; ?>, <?php echo ($car["fuel_type"]=="gas") ? "бензиновый":"дизельный"; ?>. <b>Тип трансмиссии: </b> <?php echo $transmission[$car["transmission"]]; ?>, <b>Расположение руля: </b> <?php echo ($car["wheel"]=="left") ? "левое":"правое"; ?>, <b>Цвет: </b><?php echo $car["color"]; ?></p>
								
								
								<p><b>VIN/FRAME:</b> <?php echo ($car["vin"]!=="") ? $car["vin"]:$car["frame"]; ?>. <b>Страна сборки:</b> <?php echo $car["country"]; ?></p>
								
								<p><b>Примечание: </b> <?php echo $car["note"]; ?></p>
							</div>
							
							<div class="col-md-4 text-center">
								<span class="block_image">
									
									<?php
									$img_src = "";
									$car_id = $car["id"];
									if( $car_id > 0 )
									{
										if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/garage/".$car_id.".jpg") )
										{
											$img_src = "/content/files/images/garage/".$car_id.".jpg?refresh=".time();
										}
										else if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/garage/".$car_id.".png") )
										{
											$img_src = "/content/files/images/garage/".$car_id.".png?refresh=".time();
										}
									}
									?>
								
								
									<img src="<?php echo $img_src; ?>" onerror="this.src='/content/files/images/no_image.png'">
								</span>
							</div>
						</div>
						
						
						
						<div class="row">
							<div class="col-md-12" style="padding:10px;">
								<a class="btn btn-ar btn-primary" href="/garazh/avtomobil?car_id=<?php echo $car_id; ?>"><i class="fa fa-car"></i> <i class="fa fa-pencil"></i> Редактировать</a>
								
								<a class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="delete_car(<?php echo $car_id; ?>);"><i class="fa fa-car"></i> <i class="fa fa-trash"></i>Удалить</a>
								
								
								
								<hr>
								
								<p>Доступные варианты поиска товаров:</p>
								
								<?php
								//Здесь выводим функции для поиска товаров по каталогам
								
								
								
								$to_json = json_decode($car["to_json"], true);
								$car_tree_list_json = json_decode($car["car_tree_list_json"], true);
								
								
								// -------------------------------------------------------------------------------------------
								//1. Каталог ТО
								$to_link = "";
								if( $to_json["to_mark"] > 0 )
								{
									$to_link = "/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya/vybor-modeli?car_id=".$to_json["to_mark"]."&car_name=".urlencode(strtoupper($car["mark"]));
									
									
									if($to_json["to_model"] > 0)
									{
										$car_name = urlencode(strtoupper($car["mark"]));
										$model_caption = urlencode($car["model"]);
										$img = "";
										
										//Получаем список моделей выбранной марки через веб-сервис каталога, чтобы получить необходимые строки
										$curl = curl_init();
										curl_setopt($curl, CURLOPT_URL, "http://ucats.ru/ucats/to/get_car_models.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&car_id=".$to_json["to_mark"]);
										curl_setopt($curl, CURLOPT_HEADER, 0);
										curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
										curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
										curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
										$curl_result = curl_exec($curl);
										curl_close($curl);
										$curl_result = json_decode($curl_result, true);
										
										if($curl_result["status"] == "ok")
										{
											for($i=0; $i < count($curl_result["list"]); $i++)
											{
												if($curl_result["list"][$i]["id"] == $to_json["to_model"])
												{
													$model_caption = urlencode($curl_result["list"][$i]["title"]." ".$curl_result["list"][$i]["content"]);
													$img = urlencode($curl_result["list"][$i]["img"]);
													break;
												}
											}
										}
										
										$to_link = "/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya/vybor-modeli/vybor-komplektacii?car_id=".$to_json["to_mark"]."&model_id_to=".$to_json["to_model"]."&model_caption=$model_caption&car_name=$car_name&img=$img";
										
										
										if($to_json["to_model_types"] > 0)
										{
											$type_id = $to_json["to_model_types"];
											$type_caption = "";
											
											//Получаем список моделей выбранной марки через веб-сервис каталога
											$curl = curl_init();
											curl_setopt($curl, CURLOPT_URL, "http://ucats.ru/ucats/to/get_types.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&model_id=".$to_json["to_model"]);
											curl_setopt($curl, CURLOPT_HEADER, 0);
											curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
											curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
											curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
											$curl_result = curl_exec($curl);
											curl_close($curl);
											$curl_result = json_decode($curl_result, true);

											if($curl_result["status"] == "ok")
											{
												for($i=0; $i < count($curl_result["list"]); $i++)
												{
													if($curl_result["list"][$i]["id"] == $type_id)
													{
														$engine = $curl_result["list"][$i];
														$engine_name = $engine["name"]." ".$engine["engine_model"];
														$engine_horse = $engine["engine_horse"]." л.с.";
														$engine_fuel = $engine["engine"];
														$engine_type_year = $engine["type_year"];
														
														$type_caption = urlencode($engine_name." ".$engine_horse." ".$engine_fuel." ".$engine_type_year);
														break;
													}
												}
											}
											
											
											
											$to_link = "/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya/vybor-modeli/vybor-komplektacii/spisok-zapchastej?car_id=".$to_json["to_mark"]."&model_id_to=".$to_json["to_model"]."&model_caption=$model_caption&car_name=$car_name&type_id=$type_id&type_caption=$type_caption&img=$img";
										}
									}
									
									
									?>
									<a target="_blank" class="btn btn-ar btn-default btn_margin" href="<?php echo $to_link; ?>"><i class="fa fa-wrench"></i> Каталог ТО</a>
									<?php
									
								}
								// -------------------------------------------------------------------------------------------
								//2. Поиск по каталогу AutoXP
								$parts_catalogues_query = $db_link->prepare('SELECT * FROM `shop_docpart_search_tabs` WHERE `name` = :name;');
								$parts_catalogues_query->bindValue(':name', 'parts_catalogues');
								$parts_catalogues_query->execute();
								$parts_catalogues_record = $parts_catalogues_query->fetch();
								$parts_catalogues_parameters = json_decode($parts_catalogues_record["parameters_values"], true);
								
								if( (int)$parts_catalogues_parameters["autoxp_id"] > 0 )
								{
									$autoxp_id = (int)$parts_catalogues_parameters["autoxp_id"];
									
									//Если админ указал такую марку для показа
									if( array_search($car["mark_id"], $parts_catalogues_parameters["autoxp_show_cars"]) !== 0 )
									{
										//Получаем ссылку для AutoXP
										$href_query = $db_link->prepare('SELECT * FROM `shop_docpart_cars_catalogue_links` WHERE `catalogue_id` = 1 AND `car_id` = :car_id;');
										$href_query->bindValue(':car_id', $car["mark_id"]);
										$href_query->execute();
										$href_record = $href_query->fetch();
										
										?>
										<a class="btn btn-ar btn-default btn_margin" href="javascript:void(0);" onclick="autoxp_redirect('<?php echo $href_record["href"].$autoxp_id; ?>');"><i class="fa fa-wrench"></i> Каталог AutoXP</a>
										<?php
									}
								}
								
								// -------------------------------------------------------------------------------------------
								//3. Поиск по каталогу ilcats
								if( (int)$parts_catalogues_parameters["ilcats_clid"] > 0 )
								{
									$ilcats_clid = (int)$parts_catalogues_parameters["ilcats_clid"];
									
									//Если такая марка указана админом, как показывемая
									if( (int)$parts_catalogues_parameters["ilcats_car_".$car["mark_id"]] != 0 )
									{
										//Получаем ссылку для neoriginal
										$href_query = $db_link->prepare('SELECT * FROM `shop_docpart_cars_catalogue_links` WHERE `catalogue_id` = :catalogue_id AND `car_id` = :car_id;');
										$href_query->bindValue(':catalogue_id', 2);
										$href_query->bindValue(':car_id', $car["mark_id"]);
										$href_query->execute();
										$href_record = $href_query->fetch();
										
										$href_record = $href_record["href"];
										
										$href_record = str_replace("<clid>", $ilcats_clid, $href_record);
										$href_record = str_replace("<pid>", $parts_catalogues_parameters["ilcats_car_".$car["mark_id"]], $href_record);
										
										?>
										<a target="_blank" class="btn btn-ar btn-default btn_margin" href="<?php echo $href_record; ?>"><i class="fa fa-wrench"></i> Каталог neoriginal.ru</a>
										<?php
									}
								}
								
								// -------------------------------------------------------------------------------------------
								//4. Поиск по каталогу catalogs-parts.com
								if( $parts_catalogues_parameters["catalogs_parts_com_id"] != "" )
								{
									//Если такая марка указана админом, как показывемая
									if( array_search($car["mark_id"], $parts_catalogues_parameters["catalogs_parts_com_show_cars"]) !== 0 )
									{
										//Получаем ссылку для AutoXP
										$href_query = $db_link->prepare('SELECT * FROM `shop_docpart_cars_catalogue_links` WHERE `catalogue_id` = :catalogue_id AND `car_id` = :car_id;');
										$href_query->bindValue(':catalogue_id', 3);
										$href_query->bindValue(':car_id', $car["mark_id"]);
										$href_query->execute();
										$href_record = $href_query->fetch();
										
										$href_record = $href_record["href"];
										$href_record = str_replace("client:;", "client:".$parts_catalogues_parameters["catalogs_parts_com_id"].";", $href_record);
										
										?>
										<a target="_blank" class="btn btn-ar btn-default btn_margin" href="<?php echo $href_record; ?>"><i class="fa fa-wrench"></i> Каталог catalogs-parts.com</a>
										<?php
									}
									
									//Для поиска по VIN
									if( true )
									{
										//Получаем ссылку для AutoXP
										$href_query = $db_link->prepare('SELECT * FROM `shop_docpart_cars_catalogue_links` WHERE `catalogue_id` = :catalogue_id AND `car_id` = :car_id;');
										$href_query->bindValue(':catalogue_id', 3);
										$href_query->bindValue(':car_id', $car["mark_id"]);
										$href_query->execute();
										$href_record = $href_query->fetch();
										
										$href_record = $href_record["href"];
										$car_subdomain = explode(".", $href_record);
										$car_subdomain = explode("//", $car_subdomain[0]);
										$car_subdomain = $car_subdomain[1];

										switch($car_subdomain){
											case 'kia' :
												$catalog = 'catalog:eur;';
											break;
											default : $catalog = ''; break;
										}

										$href = "http://$car_subdomain.catalogs-parts.com/#{client:".$parts_catalogues_parameters["catalogs_parts_com_id"].";page:vin;lang:ru;".$catalog."vin:".$car["vin"]."}";

										
										?>
										<a target="_blank" class="btn btn-ar btn-default btn_margin" href="<?php echo $href; ?>"><i class="fa fa-wrench"></i> VIN catalogs-parts.com</a>
										<?php
									}
									
									
									//Для aftermarket
									?>
									<a target="_blank" class="btn btn-ar btn-default btn_margin" href="https://aftermarket.catalogs-parts.com/#{client:<?php echo $parts_catalogues_parameters["catalogs_parts_com_id"]; ?>;page:models;lang:ru;catalog:pc}"><i class="fa fa-wrench"></i> Aftermarket</a>
									<?php
								}
								// -------------------------------------------------------------------------------------------
								//6. Запрос продавцу
								?>
								<a target="_blank" class="btn btn-ar btn-default btn_margin" href="javascript:void(0);" onclick="request_to_seller(<?php echo $car["id"]; ?>);"><i class="fa fa-wrench"></i> Запрос продавцу</a>
								<?php
								// -------------------------------------------------------------------------------------------
								//7. Поиск по древовидному списку автомобилей
								?>
								<script>
								<?php
								$max_value = 0;
								//Находим id последнего узла в древовидном списке
								foreach($car_tree_list_json AS $key => $value)
								{
									if($value != 0)
									{
										$max_value = $value;
									}
								}
								?>
								//Переход на поиск в собственном каталоге товаров
								function search_in_own_catalogue_<?php echo $car["id"]; ?>()
								{
									console.log(<?php echo $max_value; ?>);
									
									document.cookie = "sp_tl_<?php echo $tree_list_cars_id; ?>=<?php echo $max_value; ?>; path=/;";
									
									location="/shop/search_products?search_type=garage";
								}
								</script>
								<a class="btn btn-ar btn-default btn_margin" href="javascript:void(0);" onclick="search_in_own_catalogue_<?php echo $car["id"]; ?>();"><i class="fa fa-wrench"></i> Поиск по встроенному каталогу товаров</a>
								<?php
								// -------------------------------------------------------------------------------------------
								?>
								
								
								
								<a href="/garazh/bloknot?garage=<?=$car["id"];?>" class="btn btn-ar btn-default btn_margin"><i class="fa fa-pencil-square-o"></i> Блокнот</a>
								
								
								
							</div>
						</div>
						
						
						
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
else
{
	?>
	<p>Доступ на страницу гаража доступен только для зарегистрированных пользователей</p>
	<div class="panel panel-primary">
	<?php
	//Единый механизм формы авторизации
	$login_form_postfix = "my_orders";
	require($_SERVER["DOCUMENT_ROOT"]."/modules/login/login_form_general.php");
	?>
	</div>
	<?php
}

?>

