<?php
/*Единый скрипт для генерации объектов товаров на основе унифицированных SQL-запросов

Данный скрипт подключается после запроса в БД и перед вызовом универсальной функции печати объектов товаров
*/


//Для вывода товаров для покупателя
$exist_info_variants = array("exist" => "<span class=\"green\">В наличии</span>", "will_soon"=>"<span class=\"orange\">Скоро будет</span>", "reserved"=>"<span class=\"blue\">В резерве</span>", "absence"=>"<span class=\"red\">Раскуплен</span>");



//Делаем запрос на получение товаров и формируем массив объектов товаров
$products_objects = array();
$stmt = $db_link->prepare($SQL);
$stmt->execute($sql_args_array);
while( $product_record = $stmt->fetch() )
{
	//Если такого товара еще не было - создаем
	if( ! isset($products_objects[$product_record["id"]]))
	{
		//Прямые поля
		$products_objects[$product_record["id"]] = array();
		$products_objects[$product_record["id"]]["id"] = $product_record["id"];
		$products_objects[$product_record["id"]]["caption"] = $product_record["caption"];
		$products_objects[$product_record["id"]]["alias"] = $product_record["alias"];
		$products_objects[$product_record["id"]]["description"] = $product_record["description"];
		$products_objects[$product_record["id"]]["image"] = "/content/files/images/products_images/".$product_record["file_name"];
		$products_objects[$product_record["id"]]["category_id"] = $product_record["category_id"];
		$products_objects[$product_record["id"]]["category_url"] = $product_record["category_url"];
		
		//URL товара
		if($DP_Config->product_url == "id")
        {
			$products_objects[$product_record["id"]]["product_url"] = "/".$products_objects[$product_record["id"]]["category_url"]."/".$product_record["id"];
        }
        else
        {
			$products_objects[$product_record["id"]]["product_url"] = "/".$products_objects[$product_record["id"]]["category_url"]."/".$product_record["alias"];
        }
		
		
		//Стиль для HTML-блока
		$products_objects[$product_record["id"]]["main_class_of_block"] = $main_class_of_block;//!!!!!!!!!!!!!!!!
		//Тип блока (1,2,3,4)
		$products_objects[$product_record["id"]]["product_block_type"] = $product_block_type;//!!!!!!!!!!!!!!!!
		
		//Массивы
		$products_objects[$product_record["id"]]["storage_data"] = array();
		$products_objects[$product_record["id"]]["stickers"] = array();
		
		//ВСПОМОГАТЕЛЬНЫЕ ПОЛЯ ДЛЯ ВЫВОДА ПО 1, 4, 5, 6, 7
		$products_objects[$product_record["id"]]["min_price"] = 0;
		$products_objects[$product_record["id"]]["max_price"] = 0;
		$products_objects[$product_record["id"]]["prioritet1"] = NULL;
		$products_objects[$product_record["id"]]["prioritet2"] = NULL;
		$products_objects[$product_record["id"]]["prioritet3"] = NULL;
		$products_objects[$product_record["id"]]["prioritet4"] = NULL;
		
		
		//Оценки товара:
		$products_objects[$product_record["id"]]["mark"] = $product_record["mark"];//Средня оценка
		$products_objects[$product_record["id"]]["marks_count"] = $product_record["marks_count"];//Общее количество оценок
		$products_objects[$product_record["id"]]["mark_1"] = $product_record["mark_1"];//Количество ценок 1
		$products_objects[$product_record["id"]]["mark_2"] = $product_record["mark_2"];//Количество ценок 2
		$products_objects[$product_record["id"]]["mark_3"] = $product_record["mark_3"];//Количество ценок 3
		$products_objects[$product_record["id"]]["mark_4"] = $product_record["mark_4"];//Количество ценок 4
		$products_objects[$product_record["id"]]["mark_5"] = $product_record["mark_5"];//Количество ценок 5
	}
	
	//Заполняем массив складских записей
	if( isset($product_record["storage_id"]) )//Если есть складская запись. Т.Е. ЭТОТ БЛОК РАБОТАЕТ ТОЛЬКО ДЛЯ 1 и 4
	{
		if( !isset( $products_objects[$product_record["id"]]["storage_data"][$product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"]] ) )//Если такую запись еще не добавляли
		{
			//ОБРАБАТЫВАЕМ ОКРУГЛЕНИЕ ЦЕН
			if($DP_Config->price_rounding == '1')//Без копеечной части
			{
				if( $product_record["customer_price"] != (int)$product_record["customer_price"] )
				{
					$product_record["customer_price"] = (int)$product_record["customer_price"] + 1;
				}
				else
				{
					$product_record["customer_price"] = (int)$product_record["customer_price"];
				}
			}
			else if($DP_Config->price_rounding == '2')//До 5 руб
			{
				$product_record["customer_price"] = (integer)$product_record["customer_price"];
				$price_str = (string)$product_record["customer_price"];
				$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
				if($price_str_last_char > 0 && $price_str_last_char < 5)
				{
					$product_record["customer_price"] = $product_record["customer_price"] + (5 - $price_str_last_char);
				}
				else if($price_str_last_char > 5 && $price_str_last_char <= 9)
				{
					$product_record["customer_price"] = $product_record["customer_price"] + (10 - $price_str_last_char);
				}
			}
			else if($DP_Config->price_rounding == '3')//До 10 руб
			{
				$product_record["customer_price"] = (integer)$product_record["customer_price"];
				$price_str = (string)$product_record["customer_price"];
				$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
				if($price_str_last_char != 0)
				{
					$product_record["customer_price"] = $product_record["customer_price"] + (10 - $price_str_last_char);
				}
			}
			//ЗДЕСЬ МОЖНО ПРИВЕСТИ К НУЖНОМУ ФОРМАТУ (точки, количество знаков после запятой и т.д.)
			//...
			
			

			
			//ВНОСИМ САМУ ЗАПИСЬ МАГАЗИН-СКЛАД-ПОСТАВКА
			$products_objects[$product_record["id"]]["storage_data"][$product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"]] = array("office_id" =>$product_record["office_id"], "storage_id"=>$product_record["storage_id"], "record_id"=>$product_record["storage_record_id"], "customer_price"=>$product_record["customer_price"], "price"=>$product_record["price"], "price_crossed_out"=>$product_record["price_crossed_out"], "price_purchase"=>$product_record["price_purchase"], "arrival_time"=>$product_record["arrival_time"], "exist"=>$product_record["exist"], "reserved"=>$product_record["reserved"], "issued"=>$product_record["issued"], "additional_time"=>$product_record["additional_time"]);
			
			
			//ОБРАБОТКА ВСПОМОГАТЕЛЬНЫХ ПОЛЕЙ
			//Минимальная цена
			if($products_objects[$product_record["id"]]["min_price"] == 0)
			{
				$products_objects[$product_record["id"]]["min_price"] = $product_record["customer_price"];
			}
			else if($product_record["customer_price"] < $products_objects[$product_record["id"]]["min_price"])
			{
				$products_objects[$product_record["id"]]["min_price"] = $product_record["customer_price"];
			}
			//Максимальная цена
			if($products_objects[$product_record["id"]]["max_price"] == 0)
			{
				$products_objects[$product_record["id"]]["max_price"] = $product_record["customer_price"];
			}
			else if($product_record["customer_price"] > $products_objects[$product_record["id"]]["max_price"])
			{
				$products_objects[$product_record["id"]]["max_price"] = $product_record["customer_price"];
			}
			
			
			
			//Обработка приоритетов. ПРЯМАЯ КНОПКА КУПИТЬ ВОЗМОЖНА ТОЛЬКО ПРИ ВСЕХ ОДИНАКОВЫХ ЦЕНАХ
			if($products_objects[$product_record["id"]]["min_price"] == $products_objects[$product_record["id"]]["max_price"])
			{
				if($product_record["exist"] > 0)//Есть наличие
				{
					if( $product_record["arrival_time"] < time() && $product_record["additional_time"] == 0 )//Поставка пришла. т.е. уже в магазине
					{
						//ДОБАВЛЯЕМ ПЕРВЫЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)
						if($products_objects[$product_record["id"]]["prioritet1"] == NULL)
						{
							$products_objects[$product_record["id"]]["prioritet1"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];
						}
					}
					else//Поставка не пришла
					{
						//ДОБАВЛЯЕМ ВТОРОЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)
						if($products_objects[$product_record["id"]]["prioritet2"] == NULL)
						{
							$products_objects[$product_record["id"]]["prioritet2"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];
						}
					}
				}
				else//Наличия нет
				{
					if( $product_record["reserved"] > 0 )//Есть зарезервированный товар
					{
						//ДОБАВЛЯЕМ ТРЕТИЙ ПРИОРИТЕТ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)
						if($products_objects[$product_record["id"]]["prioritet3"] == NULL)
						{
							$products_objects[$product_record["id"]]["prioritet3"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];
						}
					}
					else//Нет даже зарезервированного товара
					{
						//ДОБАВЛЯЕМ ТРЕТИЙ ЧЕТВЕРТЫЙ (ЕСЛИ ЕЩЕ НЕ ДОБАВИЛИ)
						if($products_objects[$product_record["id"]]["prioritet4"] == NULL)
						{
							$products_objects[$product_record["id"]]["prioritet4"] = $product_record["office_id"]."_".$product_record["storage_id"]."_".$product_record["storage_record_id"];
						}
					}
				}
			}
		}
	}
	
	
	//Заполняем стикеры
	if($product_record["sticker_id"] != NULL)
	{
		if( ! isset($products_objects[$product_record["id"]]["stickers"]["s_".$product_record["sticker_id"]])  )
		{
			$products_objects[$product_record["id"]]["stickers"]["s_".$product_record["sticker_id"]] = array("id"=>$product_record["sticker_id"], "value"=>$product_record["sticker_value"], "color_text"=>$product_record["sticker_color_text"], "color_background"=>$product_record["sticker_color_background"], "href"=>$product_record["sticker_href"], "class_css"=>$product_record["sticker_class_css"], "description"=>$product_record["sticker_description"]);
		}
	}
	

	//Кнопка для администратора и кладовщика. А для покупателя - дальше
	switch($product_block_type)
	{
		case 2:
			$products_objects[$product_record["id"]]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"/".$DP_Config->backend_dir."/shop/catalogue/products/product?category_id=".$product_record["category_id"]."&product_id=".$product_record["id"]."\">Редактировать</a>";
			break;
		case 3:
			$products_objects[$product_record["id"]]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"/".$DP_Config->backend_dir."/shop/logistics/stock/product?product_id=".$product_record["id"]."\">Управление</a>";
			break;
	}

}


//После получения всей информации из БД - определяем отображение для покупателя
if( $product_block_type == 1 || $product_block_type == 4 || $product_block_type == 5 || $product_block_type == 6 || $product_block_type == 7 )
{
	foreach( $products_objects AS $product_id => $product )
	{
		$storage_data = $product["storage_data"];
		
		//ЕСЛИ ВСЕ ЦЕНЫ ОДИНАКОВЫЕ, ТО ВЫВОДИМ ПО ПРИОРИТЕТУ
		if( $product["min_price"] == $product["max_price"] )
		{
			if( $product["prioritet1"] != NULL )//ПРИОРИТЕТ 1
			{
				$products_objects[$product_id]["cart_suggestion"] = $product["prioritet1"];//Объект для добавления в корзину. Т.е. ключ в списке всех предложений
				$products_objects[$product_id]["exist_info_variant"] = $exist_info_variants["exist"];//Пометка о наличии
				
				$products_objects[$product_id]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"javascript:void(0);\" onclick=\"purchase_action('".$product["prioritet1"]."');\">Купить</a>";
			}
			else if( $product["prioritet2"] != NULL )//ПРИОРИТЕТ 2
			{
				$products_objects[$product_id]["cart_suggestion"] = $product["prioritet2"];//Объект для добавления в корзину
				$products_objects[$product_id]["exist_info_variant"] = $exist_info_variants["will_soon"];//Пометка о наличии
				
				$products_objects[$product_id]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"javascript:void(0);\" onclick=\"purchase_action('".$product["prioritet2"]."');\">Заказать</a>";
			}
			else if( $product["prioritet3"] != NULL )//ПРИОРИТЕТ 3
			{
				$products_objects[$product_id]["cart_suggestion"] = $product["prioritet3"];//Хотя, в корзину добавлять нечего
				$products_objects[$product_id]["exist_info_variant"] = $exist_info_variants["reserved"];//Пометка о наличии
				
				$products_objects[$product_id]["button"] = "<a class=\" btn btn-ar btn-primary disabled\" href=\"javascript:void(0);\">Купить</a>";
			}
			else//ПРИОРИТЕТ 4
			{
				$products_objects[$product_id]["cart_suggestion"] = $product["prioritet4"];//Хотя, в корзину добавлять нечего
				$products_objects[$product_id]["exist_info_variant"] = $exist_info_variants["absence"];//Пометка о наличии
				
				$products_objects[$product_id]["button"] = "<a class=\" btn btn-ar btn-primary disabled\" href=\"javascript:void(0);\">Купить</a>";
			}
			
			//Далее то, что не зависит от приоритетов:
			//Цена
			$products_objects[$product_id]["price"] = "<font class=\"price\">".$storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"]."</font>";
			//Цена зачеркнутая
			$products_objects[$product_id]["price_crossed_out"] = "";
			if($storage_data[$products_objects[$product_id]["cart_suggestion"]]["price_crossed_out"] > 0)
			{
				$products_objects[$product_id]["price_crossed_out"] = "<font class=\"price\">".$storage_data[$products_objects[$product_id]["cart_suggestion"]]["price_crossed_out"]."</font>";
			}
			//Указатель валюты
			if($DP_Config->currency_show_mode == "sign_before")
			{
				$products_objects[$product_id]["price"] = "<font class=\"currency\">$currency_indicator</font> ".$products_objects[$product_id]["price"];
				
				if($products_objects[$product_id]["price_crossed_out"] != "")
				{
					$products_objects[$product_id]["price_crossed_out"] = "<font class=\"currency\">$currency_indicator</font> ".$products_objects[$product_id]["price_crossed_out"];
				}
			}
			else
			{
				$products_objects[$product_id]["price"] = $products_objects[$product_id]["price"]." <font class=\"currency\">$currency_indicator</font>";
				
				if($products_objects[$product_id]["price_crossed_out"] != "")
				{
					$products_objects[$product_id]["price_crossed_out"] = $products_objects[$product_id]["price_crossed_out"]." <font class=\"currency\">$currency_indicator</font>";
				}
			}
			
			
			//Указываем проверочный хеш для предотвращения подмены данных злоумышленниками через Javascript
			$products_objects[$product_id]["storage_data"][$products_objects[$product_id]["cart_suggestion"]]["check_hash"] = md5($product_id.$storage_data[$products_objects[$product_id]["cart_suggestion"]]["office_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["storage_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["record_id"].$storage_data[$products_objects[$product_id]["cart_suggestion"]]["customer_price"].$DP_Config->tech_key);
		}
		else//ЦЕНЫ РАЗНЫЕ - ВЫВОДИМ КНОПКУ ПОДРОБНОСТИ. СТРОКА С ЦЕНОЙ В ОТДЕЛЬНОМ ПОЛЕ price_from_to. Поле price не заполняется
		{
			$products_objects[$product_id]["button"] = "<a class=\" btn btn-ar btn-primary\" href=\"".$products_objects[$product_id]["product_url"]."\">Подробно</a>";//Переход на страницу товара
			
			//Формируем строку с ценой
			$product["min_price"] = "<font class=\"price\">".$product["min_price"]."</font>";
			$product["max_price"] = "<font class=\"price\">".$product["max_price"]."</font>";
			//Индикатор валюты
			if($DP_Config->currency_show_mode == "sign_before")
			{
				$product["min_price"] = "<font class=\"currency\">$currency_indicator</font> ".$product["min_price"];
				
				$product["max_price"] = "<font class=\"currency\">$currency_indicator</font> ".$product["max_price"];
			}
			else
			{
				$product["min_price"] = $product["min_price"]." <font class=\"currency\">$currency_indicator</font>";
				
				$product["max_price"] = $product["max_price"]." <font class=\"currency\">$currency_indicator</font>";
				
			}
			$products_objects[$product_id]["price_from_to"] = "от ".$product["min_price"]."<br>до ".$product["max_price"];
		}
	}
}
?>