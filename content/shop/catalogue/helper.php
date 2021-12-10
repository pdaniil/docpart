<?php
/*Скрипт с определениями подключаемых функций*/

/*
Типы блоков:
1 - Отображение блоков товаров одной категории для покупателя
2 - Отображение блоков товаров одной категории для менеджера каталога (редактор справочника)
3 - Отображение блоков товаров одной категории для кладовщика
4 - Отображение блоков товаров для покупателя при поиске по наименованию
5 - Отображение блоков товаров для покупателя: блоки на главной, сопутствующие товары, похожие товары
6 - Отображение блоков товаров для покупателя: страница закладок
7 - Отображение блоков товаров для покупателя: страница сравнений товаров
*/


//Функция вывода блока одного товара (объекта товара)
function printProductBlock($product)
{
	?>
	<div class="<?php echo $product["main_class_of_block"]; ?>">
		
		<?php
		//Изображение выводим только для способов отображения Плитка и Список с фото
		if( strpos( $product["main_class_of_block"], "product_div_tile") !== false  || 
		strpos( $product["main_class_of_block"], "product_div_list_photo") !== false )
		{
			?>
			<div class="product_div_image_wrap">
				<a title="<?php echo $product["caption"]; ?>" href="<?php echo $product["product_url"]; ?>">
					<?php if(isset($product["image"]) && !empty($product["image"])) : ?>
						<img src="<?php echo $product["image"]; ?>" alt="<?php echo $product["caption"]; ?>" border="0"  />
					<?php else : ?>
						<img src="/content/files/images/no_image.png" alt="<?php echo $product["caption"]; ?>" border="0" />
					<?php endif; ?>
				</a>
			</div>
			<?php
		}
		
		
		//Текстовое описание выводим пока только для сопособа отображения "Список с фото"
		if( strpos( $product["main_class_of_block"] , "product_div_list_photo") !== false )
		{
			?>
			<div class="product_div_description">
				<?php echo $product["description"]; ?>
			</div>
			<?php
		}
		?>
		
		
		<div class="product_div_name">
			<a title="<?php echo $product["caption"]; ?>" href="<?php echo $product["product_url"]; ?>">
				<?php echo $product["caption"]; ?>
			</a>
		</div>
		
		
		
		<?php
		//СТИКЕРЫ
		?>
		<div class="stickers">
		<?php
		foreach( $product["stickers"] AS $sticker_id => $sticker )
		{
			$description = "";
			if($sticker["description"] != "")
			{
				$description = " title = '".$sticker["description"]."'";
			}
			?>
			<div <?php echo $description; ?> class="sticker <?php echo $sticker["class_css"]; ?>" style="background-color:<?php echo $sticker["color_background"]; ?>; color: <?php echo $sticker["color_text"]; ?>;">
			
				<?php
				if( $sticker["href"] != "")
				{
					?>
					<a target="_blank" style="color:<?php echo $sticker["color_text"]; ?>; border-bottom:1px dotted <?php echo $sticker["color_text"]; ?>;" href="<?php echo $sticker["href"]; ?>">
					<?php
				}
				?>
					<?php echo $sticker["value"]; ?>
				<?php
				if( $sticker["href"] != "")
				{
					?>
					</a>
					<?php
				}
				?>
			</div>
			<?php
		}
		?>
		</div>
		
		
		
		
		
		<?php
		//Оценки покупателей
		//...
		?>
		<div title="Оценки товара" class="product_div_marks" rel="popover" data-html="true" data-toggle="popover" data-placement="bottom" data-content="<div><i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <?php echo $product["mark_1"]; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product["mark_2"]; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product["mark_3"]; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star-o em-primary'></i>  <?php echo $product["mark_4"]; ?></div> <div><i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i> <i class='fa fa-star em-primary'></i>  <?php echo $product["mark_5"]; ?></div>">
			<?php
			for($i=0; $i < 5; $i++)
			{
				if( ($i + 1) <= $product["mark"] )
				{
					?>
					<i class="fa fa-star em-primary"></i>
					<?php
				}
				else
				{
					?>
					<i class="fa fa-star-o em-primary"></i>
					<?php
				}
			}
			?>
			<span class="product_div_marks_count"><?php echo $product["marks_count"]; ?></span>
		</div>
		
		
		
		
		
		
		
		
		<?php
		//ВЫВОД ДЛЯ ПОКУПАТЕЛЯ
		if( $product["product_block_type"] == 1 || $product["product_block_type"] == 4 || $product["product_block_type"] == 5 || $product["product_block_type"] == 6 || $product["product_block_type"] == 7 )
		{
			//ЦЕНА. Если цена одна для всех товаров, то она в блоке price
			if( isset($product["price"]) )
			{
				if($product["price"] != "")
				{
					?>
					<div class="product_div_price"><?php echo $product["price"]; ?></div>
					<?php
				}
			}
			

			//ЗАЧЕРКНУТАЯ ЦЕНА. Блок с зачеркнутой ценой
			if( isset($product["price_crossed_out"]) )
			{
				if($product["price_crossed_out"] != "")
				{
					?>
					<div class="product_div_price_crossed_out"><?php echo $product["price_crossed_out"]; ?></div>
					<?php
				}
			}
				
			
			//ЦЕНА ОТ И ДО
			if( isset($product["price_from_to"]) )
			{
				if($product["price_from_to"] != "")
				{
					?>
					<div class="product_div_price_from_to"><?php echo $product["price_from_to"]; ?></div>
					<?php
				}
			}
			?>
			
			
			<?php
			//ПОМЕТКА О НАЛИЧИИ
			if(isset($product["exist_info_variant"]))
			{
				?>
				<div class="product_div_exist_info"><?php echo $product["exist_info_variant"]; ?></div>
				<?php
			}
			?>
            
			
			
			
			<?php
			//Закладки
			//Получаем закладки
			$in_bookmarks = false;//Флаг - данный товар добавлен в закладки
			if( isset($_COOKIE["bookmarks"]) )
			{
				$bookmarks = json_decode($_COOKIE["bookmarks"], true);
				if($bookmarks != NULL)//Есть закладки. Определяем - находится ли данный товар в закладках
				{
					if( array_search($product["id"], $bookmarks) !== false )
					{
						$in_bookmarks = true;
					}
				}
			}
			if($in_bookmarks)//Этот товар в закладках
			{
				if($product["product_block_type"] == 6)//Страница закладок
				{
					?>
					<div class="product_div_bookmark">
						<a href="javascript:void(0);" onclick="removeBookmark(<?php echo $product["id"]; ?>, this);" title="Удалить из закладок">
							<i class="fa fa-remove"></i>
							<span>
								Удалить
							</span>
						</a>
					</div>
					<?php
				}
				else//Для остальных страниц - переход на страницу закладок
				{
					?>
					<div class="product_div_bookmark">
						<a href="javascript:void(0);" onclick="location = '/shop/zakladki';" title="Перейти в закладки">
							<i class="fa fa-bookmark"></i>
							<span>
								В закладках
							</span>
						</a>
					</div>
					<?php
				}
			}
			else//Этого товара нет в закладках
			{
				?>
				<div class="product_div_bookmark">
					<a href="javascript:void(0);" onclick="addToBookmarks(<?php echo $product["id"]; ?>, this);" title="Добавить в закладки">
						<i class="fa fa-bookmark-o"></i>
						<span>
							В закладки
						</span>
					</a>
				</div>
				<?php
			}
			?>
			
			
			
			
			
			<?php
			//Ссылка "К сравнению"
			//Получаем товары в сравнениях
			$in_compare = false;//Флаг - данный товар добавлен в сравнения
			if( isset($_COOKIE["compare"]) )
			{
				$compare = json_decode($_COOKIE["compare"], true);
				if($compare != NULL)//Есть сравнения. Определяем - находится ли данный товар в сравнениях
				{
					if( array_search($product["id"], $compare) !== false )
					{
						$in_compare = true;
					}
				}
			}
			
			
			if($in_compare)//Этот товар в сравнениях
			{
				if($product["product_block_type"] == 7)//Страница сравнений
				{
					?>
					<div class="product_div_compare">
						<a href="javascript:void(0);" onclick="removeCompare(<?php echo $product["id"]; ?>, this);" title="Удалить из сравнений">
							<i class="fa fa-remove"></i>
							<span>
								Удалить
							</span>
						</a>
					</div>
					<?php
				}
				else//Для остальных страниц - переход на страницу сравнений
				{
					?>
					<div class="product_div_compare">
						<a href="javascript:void(0);" onclick="location = '/shop/sravneniya'; " title="На страницу сравненения товаров">
							<i class="glyphicon glyphicon-duplicate"></i>
							<span>
								В сравнениях
							</span>
						</a>
					</div>
					<?php
				}
			}
			else
			{
				?>
				<div class="product_div_compare">
					<a href="javascript:void(0);" onclick="addToCompare(<?php echo $product["id"]; ?>, this);" title="Добавить к сравнению">
						<i class="fa fa-copy fa-flip-horizontal"></i>
						<span>
							К сравнению
						</span>
					</a>
				</div>
				<?php
			}
			?>
			
			
			
			
			
			
			
			<?php
			//НЕВИДИМЫЙ БЛОК ДЛЯ ДОБАВЛЕНИЯ В КОРЗИНУ
			if(isset($product["cart_suggestion"]))
			{
				if( isset($product["storage_data"][$product["cart_suggestion"]]) )
				{
					$suggestion = $product["storage_data"][$product["cart_suggestion"]];
					?>
					<div id="product_object_<?php echo $product["id"]; ?>" style="display:none">
						<div
							id = "<?php echo $product["cart_suggestion"]; ?>"
							product_id = "<?php echo $product["id"]; ?>"
							office_id = "<?php echo $suggestion["office_id"]; ?>"
							storage_id = "<?php echo $suggestion["storage_id"]; ?>"
							storage_record_id = "<?php echo $suggestion["record_id"]; ?>"
							price = "<?php echo $suggestion["customer_price"]; ?>"
							check_hash = "<?php echo $suggestion["check_hash"]; ?>"
						></div>
					</div>
					<?php
				}
			}
		}//END ВЫВОД ДЛЯ ПОКУПАТЕЛЯ
		?>
		

		
		
		
		<?php
		/**
		 * Вывод для администратора каталога
		*/
		//Выводим чекбоксы для каждого продукта
		if( $product["product_block_type"] == 2)
		{
			?>
			<div class="product_checkbox_div">
				<input class="product_checkbox" product_id="<?php echo $product["id"]; ?>" type="checkbox" id="product_checkbox_<?php echo $product["id"]; ?>" />
			</div>
			<?php
		}
		?>
		
		
		<div class="main_action_div"><?php echo $product["button"]; ?></div>
		
	</div>
	<?php
	
	/**
	Вывод для кладовщика - сразу после блока товара добавляется контейнер для редактирования складских записей
	*/
	//Выводим виджеты для быстрого редактирования цен. Только при стилях "Список с фото" и "Список без фото"
	if($product["product_block_type"] == 3 && strpos($product["main_class_of_block"], "product_div_tile") === false )
	{
		?>
		<div class="product_price_quick_edit work_quick_edit" product_id="<?php echo $product["id"]; ?>" id="work_quick_edit_<?php echo $product["id"]; ?>"></div>
		<?php
	}
}
?>