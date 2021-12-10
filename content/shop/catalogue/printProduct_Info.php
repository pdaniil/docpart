<?php
/**
 * Вывести страницу о продукте
 * Используется для вывода страниц для покупателей и кладовщиков через require_once
*/
defined('_ASTEXE_') or die('No access');


/**
 * Общие определения
*/
//Постфиксы таблиц значений свойств - зависят от типа свойства
$property_types_tables = array("1"=>"int", "2"=>"float", "3"=>"text", "4"=>"bool", "5"=>"list", "6"=>"tree_list");


if($product_id == 0)
{
    $product_id = $_REQUEST["product_id"];
}


$products_images_dir = "/content/files/images/products_images/";


$product_query = $db_link->prepare('SELECT `caption`, `category_id` FROM `shop_catalogue_products` WHERE `id` = :id;');
$product_query->bindValue(':id', $product_id);
$product_query->execute();
$product_record = $product_query->fetch();
$category_id = $product_record["category_id"];
?>


<?php
//После вывовода страницы - динамически меняем высоту блока, т.к. он position:relative
?>
<script>
function setBlocksHeight()
{
    //Получаем высоты:
    var product_genaral_info_HEIGHT = jQuery("#product_genaral_info_div").height();
    var product_galery_HEIGHT = jQuery("#main_image").height() + jQuery("#all_product_images_div").height();
    
    if(product_galery_HEIGHT > product_genaral_info_HEIGHT)
    {
        jQuery("#product_info_wrap_div").height(parseInt(product_galery_HEIGHT));
    }
    else
    {
        jQuery("#product_info_wrap_div").height(parseInt(product_genaral_info_HEIGHT));
    }
}

</script>



<?php
if( $isFrontMode )
{
	?>
	<div class="container">
		<div class="row">
			<div class="col-md-6">
				
				<?php
				$images_list = array();
				$images_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_products_images` WHERE `product_id` = :product_id;');
				$images_query->bindValue(':product_id', $product_id);
				$images_query->execute();
				$count_rows = $images_query->fetchColumn();

				if($count_rows > 0)//Есть изображение
				{
					$images_query = $db_link->prepare('SELECT `id`,`file_name` FROM `shop_products_images` WHERE `product_id` = :product_id;');
					$images_query->bindValue(':product_id', $product_id);
					$images_query->execute();
					
					while( $image = $images_query->fetch() )
					{
						$src = $products_images_dir.$image["file_name"];
						array_push($images_list, $src);
					}
				}
				else//Изображений нет
				{
					array_push($images_list, "/content/files/images/no_image.png");
				}
				?>
				
				
				
				<ul class="bxslider">
					<?php
					for($i=0; $i < count($images_list); $i++)
					{
						?>
						<li><img onerror="this.src='/content/files/images/no_image.png'" src="<?php echo $images_list[$i]; ?>" /></li>
						<?php
					}
					?>
				</ul>

				<div id="bx-pager">
					<?php
					for($i=0; $i < count($images_list); $i++)
					{
						?>
						<a data-slide-index="<?php echo $i; ?>" href=""><img onerror="this.src='/content/files/images/no_image.png'" style="width:56px;height:56px;" src="<?php echo $images_list[$i]; ?>" /></a>
						<?php
					}
					?>
				</div>
			</div>
			<div class="col-md-6">
				<?php
				$text_content_query = $db_link->prepare('SELECT `content` FROM `shop_products_text` WHERE `product_id` = :product_id;');
				$text_content_query->bindValue(':product_id', $product_id);
				$text_content_query->execute();
				$text_content_record = $text_content_query->fetch();
				if( $text_content_record != false )
				{
					$text_content = $text_content_record["content"];
				}
				?>
			
				<h3 class="no-margin-top">Описание</h3>
				<?php
				echo $text_content;
				?>
				<h4>Свойства</h4>
				<ul class="unstyled">
				<?php
				//Получаем основные свойства товара по category_id
				$properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = :category_id ORDER BY `order` ASC;');
				$properties_query->bindValue(':category_id', $category_id);
				$properties_query->execute();
				while($property_record = $properties_query->fetch())
				{
					$property_id = $property_record["id"];
					$list_id = $property_record["list_id"];//ID списка - если свойство списковое
					?>
					<li><strong><?php echo $property_record["value"]; ?> </strong>
						<?php
						//Получаем значение данного свойства для товара:
						$table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
						$property_value_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
						$property_value_query->bindValue(':product_id', $product_id);
						$property_value_query->bindValue(':property_id', $property_id);
						$property_value_query->execute();
						$property_value_query_count = $property_value_query->fetchColumn();

						if($property_value_query_count > 0)
						{
							$property_value_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
							$property_value_query->bindValue(':product_id', $product_id);
							$property_value_query->bindValue(':property_id', $property_id);
							$property_value_query->execute();
							
							//Задаем значение
							switch($property_record["property_type_id"])
							{
								case 1:
								case 2:
								case 3:
								case 4:
									$property_value_record = $property_value_query->fetch();
									echo $property_value_record["value"];
									break;
								case 5:
									//Свойство списковое - значений может быть несколько
									$list_property_items = array();
									while($property_value_record = $property_value_query->fetch())
									{
										array_push($list_property_items, (integer)$property_value_record["value"]);
									}
									//Теперь получаем названия значений свойств из линейных списков
									$line_list_items = array();
									
									$line_list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `order`;');
									$line_list_items_query->bindValue(':line_list_id', $list_id);
									$line_list_items_query->execute();
									while( $list_item = $line_list_items_query->fetch() )
									{
										array_push($line_list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
									}
									$line_list_values_text = "";//Текстовая строка для вывода значений линейного списка
									for($L=0; $L < count($line_list_items); $L++)
									{
										if(array_search((integer)$line_list_items[$L]["id"], $list_property_items) !== false)
										{
											if($line_list_values_text != "") $line_list_values_text .= ", ";
											$line_list_values_text .= $line_list_items[$L]["value"];
										}
									}
									echo $line_list_values_text;
									break;
								case 6:
									//Свойство типа "Древовидный список" - значений может быть несколько
									$list_property_items = array();
									while($property_value_record = $property_value_query->fetch())
									{
										array_push($list_property_items, (int)$property_value_record["value"]);
									}
									//Переводим в строку:
									$list_property_items = json_encode($list_property_items);
									$list_property_items = str_replace( array("[", "]") , "", $list_property_items);
									
									//Теперь формируем строку для отображения значения данного свойства
									//Данное свойство отображаем в виде цепочек для каждой ветви
									$property_variants = array();//Массив для всех цепочек данного свойства
									
									$tree_items_query = $db_link->prepare('SELECT `id`, `value`, `level`, `count`, `parent` FROM `shop_tree_lists_items` WHERE `id` IN ('.$list_property_items.') ORDER BY `level` DESC, `order` ASC;');
									$tree_items_query->execute();
									while( $tree_item = $tree_items_query->fetch() )
									{
										$has_added = false;
										for( $pv = 0 ; $pv < count($property_variants); $pv++)
										{
											if($property_variants[$pv][ 0 ]["parent"] == $tree_item["id"])
											{
												array_unshift($property_variants[$pv], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
												
												$has_added = true;
											}
										}
										if( ! $has_added )
										{
											array_unshift($property_variants, array());
											
											array_unshift($property_variants[ 0 ], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
										}
									}
									//Если есть цепочки
									if( count(property_variants) > 0 )
									{
										echo "<br>";
									}
									for( $pv = 0 ; $pv < count($property_variants); $pv++)
									{
										if($pv > 0)
										{
											echo "<br>";
										}
										for( $pv_i = 0 ; $pv_i < count($property_variants[$pv]); $pv_i++)
										{
											if($pv_i > 0)
											{
												echo " ";
											}
											echo $property_variants[$pv][$pv_i]["value"];
										}
									}
									break;
							}
						}//~if() - есть значение свойства
						?>
					</li>
					<?php
				}
				?> 
				</ul>

			</div>
		</div>
	</div>
	<?php
}
else
{
	?>
	<div class="product_info_wrap" id="product_info_wrap_div">

		<div class="product_galery" id="product_galery_div">
			<div class="main_image" id="main_image_div">
				<?php
				$images_query = $db_link->prepare('SELECT `id`,`file_name` FROM `shop_products_images` WHERE `product_id` = :product_id;');
				$images_query->bindValue(':product_id', $product_id);
				$images_query->execute();
				$image_main = $images_query->fetch();
				if($image_main != false)//Есть изображение
				{
					$src = $products_images_dir.$image_main["file_name"];
					$current_image_id = $image_main["id"];//Для текущего изображения
					//Указатель в ноль:
					$images_query->execute();
					$image_main = $images_query->fetch();
				}
				else//Изображений нет
				{
					$src = "/content/files/images/no_image.png";
					$current_image_id = 0;//Для текущего изображения
				}
				?>
				<img onload="setBlocksHeight();" id="main_image" src="<?php echo $src; ?>" />
			</div>
			
			<div class="all_product_images" id="all_product_images_div">
				<?php
				while($image = $images_query->fetch())
				{
					$sub_class = "other_image";
					if($i==0) $sub_class = "current_image";
					?>
					<img onload="setBlocksHeight();" onclick="selectImage(<?php echo $image["id"]; ?>,'<?php echo $products_images_dir.$image["file_name"]; ?>');" id="product_image_select_<?php echo $image["id"]; ?>" class="product_image_select <?php echo $sub_class; ?>" src="<?php echo $products_images_dir.$image["file_name"]; ?>" />
					<?php
				}
				?>
			</div>
		</div>
		
		
		<div class="product_genaral_info" id="product_genaral_info_div">
			<?php
			$text_content = "Текстовое описание";
			$text_content_query = $db_link->prepare('SELECT `content` FROM `shop_products_text` WHERE `product_id` = :product_id;');
			$text_content_query->bindValue(':product_id', $product_id);
			$text_content_query->execute();
			$text_content_record = $text_content_query->fetch();
			if($text_content_record != false)
			{
				$text_content = $text_content_record["content"];
			}
			
			
			//ВЫВОДИМ СВОЙСТВА ТОВАРА
			?>
			<table>
			<?php
			//Получаем основные свойства товара по category_id
			$properties_query = $db_link->prepare('SELECT * FROM `shop_categories_properties_map` WHERE `category_id` = :category_id ORDER BY `order` ASC;');
			$properties_query->bindValue(':category_id', $category_id);
			$properties_query->execute();
			while($property_record = $properties_query->fetch())
			{
				$property_id = $property_record["id"];
				$list_id = $property_record["list_id"];//ID списка - если свойство списковое
				?>
				<tr>
					<td style="vertical-align:top;"><font style="font-weight:bold; margin-right:10px;"><?php echo $property_record["value"]; ?></font></td>
					<td style="vertical-align:top;">
					<?php
					//Получаем значение данного свойства для товара:
					$table_postfix = $property_types_tables[(string)$property_record["property_type_id"]];//Постфикс таблицы
					$property_value_query = $db_link->prepare('SELECT COUNT(*) FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
					$property_value_query->bindValue(':product_id', $product_id);
					$property_value_query->bindValue(':property_id', $property_id);
					$property_value_query->execute();
					$property_value_query_count_rows = $property_value_query->fetchColumn();

					if($property_value_query_count_rows > 0)
					{
						$property_value_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_properties_values_'.$table_postfix.'` WHERE `product_id` = :product_id AND `property_id` = :property_id;');
						$property_value_query->bindValue(':product_id', $product_id);
						$property_value_query->bindValue(':property_id', $property_id);
						$property_value_query->execute();
						
						//Задаем значение
						switch($property_record["property_type_id"])
						{
							case 1:
							case 2:
							case 3:
							case 4:
								$property_value_record = $property_value_query->fetch();
								echo $property_value_record["value"];
								break;
							case 5:
								//Свойство списковое - значений может быть несколько
								$list_property_items = array();
								while($property_value_record = $property_value_query->fetch())
								{
									array_push($list_property_items, (integer)$property_value_record["value"]);
								}
								//Теперь получаем названия значений свойств из линейных списков
								$line_list_items = array();
								
								$line_list_items_query = $db_link->prepare('SELECT `id`, `value` FROM `shop_line_lists_items` WHERE `line_list_id` = :line_list_id ORDER BY `order`;');
								$line_list_items_query->bindValue(':line_list_id', $list_id);
								$line_list_items_query->execute();
								while( $list_item = $line_list_items_query->fetch() )
								{
									array_push($line_list_items, array("id"=>$list_item["id"], "value"=>$list_item["value"]) );
								}
								$line_list_values_text = "";//Текстовая строка для вывода значений линейного списка
								for($L=0; $L < count($line_list_items); $L++)
								{
									if(array_search((integer)$line_list_items[$L]["id"], $list_property_items) !== false)
									{
										if($line_list_values_text != "") $line_list_values_text .= ", ";
										$line_list_values_text .= $line_list_items[$L]["value"];
									}
								}
								echo $line_list_values_text;
								break;
							case 6:
								//Свойство типа "Древовидный список" - значений может быть несколько
								$list_property_items = array();
								while($property_value_record = $property_value_query->fetch())
								{
									array_push($list_property_items, (int)$property_value_record["value"]);
								}
								//Переводим в строку:
								$list_property_items = json_encode($list_property_items);
								$list_property_items = str_replace( array("[", "]") , "", $list_property_items);
								
								//Теперь формируем строку для отображения значения данного свойства
								//Данное свойство отображаем в виде цепочек для каждой ветви
								$property_variants = array();//Массив для всех цепочек данного свойства
								
								$tree_items_query = $db_link->prepare('SELECT `id`, `value`, `level`, `count`, `parent` FROM `shop_tree_lists_items` WHERE `id` IN ('.$list_property_items.') ORDER BY `level` DESC, `order` ASC;');
								$tree_items_query->execute();
								while( $tree_item = $tree_items_query->fetch() )
								{
									$has_added = false;
									for( $pv = 0 ; $pv < count($property_variants); $pv++)
									{
										if($property_variants[$pv][ 0 ]["parent"] == $tree_item["id"])
										{
											array_unshift($property_variants[$pv], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
											
											$has_added = true;
										}
									}
									if( ! $has_added )
									{
										array_unshift($property_variants, array());
										
										array_unshift($property_variants[ 0 ], array("value"=>$tree_item["value"], "level"=>$tree_item["level"], "count"=>$tree_item["count"], "parent"=>$tree_item["parent"], "id"=>$tree_item["id"]) );
									}
								}
								//Если есть цепочки
								if( count(property_variants) > 0 )
								{
									echo "<br>";
								}
								for( $pv = 0 ; $pv < count($property_variants); $pv++)
								{
									if($pv > 0)
									{
										echo "<br>";
									}
									for( $pv_i = 0 ; $pv_i < count($property_variants[$pv]); $pv_i++)
									{
										if($pv_i > 0)
										{
											echo " ";
										}
										echo $property_variants[$pv][$pv_i]["value"];
									}
								}
								break;
						}
					}//~if() - есть значение свойства
					?>
					</td>
				</tr>
				<?php
			}
			?>
			</table>
			<?php
			
			echo $text_content;
			?>
		</div>
	</div>
	<script>
	var current_image_id = <?php echo $current_image_id; ?>;
	function selectImage(id, image_url)
	{
		if(current_image_id == id)
		{
			return;
		}
		
		//Предыдущий возвращаем в нормальное состояние
		document.getElementById("product_image_select_"+current_image_id).setAttribute("class", "product_image_select other_image");
		
		//Ставим текущий новый
		document.getElementById("product_image_select_"+id).setAttribute("class", "product_image_select current_image");
		
		//Запоминаем текущее изображение
		current_image_id = id;
		
		//Обновляем выбранное изображение
		document.getElementById("main_image").setAttribute("src", image_url);
	}
	</script>
	<?php
}
?>