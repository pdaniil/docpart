<?php
/**
 * Скрипт для страницы поиска автозапчастей по артикулу, вариант "Группировка по товарам"
 * 
 * Как работает сортировка при первоначальной загрузке страницы:
 * Внутри группы позиции сортируются по цене, затем позиции с доставкой 0-2дн. поднимаются выше остальных.
 * Внешняя сортировка происходит по сроку первых позиций в группе.
 * 
 * При смене сортировки внешняя сортировка происходит по первым позициям в группе.
 * 
*/

defined('_ASTEXE_') or die('No access');
?>



<script>
//АЛГОРИТМ ПОИСКА ЗАПЧАСТЕЙ ПО АРТИКУЛУ
var search_object = new Object;//Объект запроса



//Первым делом указываем артикул и производителя:
search_object.article = "<?php echo $article; ?>";
search_object.searsch_str = "<?php echo $searsch_str; ?>";
search_object.manufacturers = null;//Задаем значение null



//Настройки связанные с этим типом отображения:
var group_day = 2;// Аналоги 0 - 2 дня
var cnt_to_hide = 1;// Количество позиций после которых начинаем сворачивать группы
var flag_one = true;// true - Флаг показывает что клиент первый раз загружает проценку
var flag_time_head = true;// Перемещать ли позиции со сроком 0-2 дня выше остальных позиций в группе при первой загрузке проценки


//ОБЪЕКТ ОПИСАНИЯ ТОВАРОВ ПРИНЯТЫХ ОТ СЕРВЕРА
var Products = new Object;

//Запрошенные товары
Products.Required = new Object;//Объект с запрошенными товарами
Products.Required.Products = new Object;//Структура для объектов товаров
Products.Required.Products.Manufacturers = new Object;//Разделение на производителей
Products.Required.ProductsTypes = new Array();//Список типов товаров

//Товары найденные по наименованию в каталоге или прайс листах
Products.SearchName = new Object;//Объект с найденными товарами
Products.SearchName.Products = new Object;//Структура для объектов товаров
Products.SearchName.Products.Manufacturers = new Object;//Разделение на производителей
Products.SearchName.ProductsTypes = new Array();//Список типов товаров

//Аналоги с быстрой доставкой 0 - 2 дня
Products.Quick_Analogs = new Object;
Products.Quick_Analogs.Products = new Object;
Products.Quick_Analogs.Products.Manufacturers = new Object;
Products.Quick_Analogs.ProductsTypes = new Array();//Список типов товаров

//Остальные Аналоги
Products.Analogs = new Object;
Products.Analogs.Products = new Object;
Products.Analogs.Products.Manufacturers = new Object;
Products.Analogs.ProductsTypes = new Array();//Список типов товаров

//Возможные замены
Products.PossibleReplacement = new Object;
Products.PossibleReplacement.Products = new Object;
Products.PossibleReplacement.Products.Manufacturers = new Object;
Products.PossibleReplacement.ProductsTypes = new Array();//Список типов товаров

//Дополнительный свободный блок для доработок
Products.Spare_Box = new Object;
Products.Spare_Box.Products = new Object;
Products.Spare_Box.Products.Manufacturers = new Object;
Products.Spare_Box.ProductsTypes = new Array();//Список типов товаров

//Индексный список для поиска нужного объекта по его клиентскому ID (AID - All ID). Т.е. каждый объект товара при примеме от сервера получает ID в рамках данной страницы.
//Этот список предназначен для получения объекта товара по его AID:
Products.All = new Array();//Список объектов




// ------------------------------------------------------------------------------------------------------------------------------




//Обработка полученного результата
function onGetStoragesData()
{
	innerSort();//Внутренняя сортировка (Наличие-Срок-Цена) / внешняя вход в внутреннею
	resultReview();//Обновляем отображние результата
}




// ------------------------------------------------------------------------------------------------------------------------------




//Метод соединения полученного от сервера результата с общим объектом описания найденных товаров
function bindBunchResult(answer)
{
	//Полученный результат распределяем по структуре результата
	var result_length = answer.Products.length;
    for(var i=0; i < result_length; i++)
    {
        // Преобразование типов
		answer.Products[i]["exist"] = answer.Products[i]["exist"] * 1;
		answer.Products[i]["time_to_exe"] = answer.Products[i]["time_to_exe"] * 1;
		answer.Products[i]["price"] = answer.Products[i]["price"] * 1;
		
		var manufacturer 			= String(answer.Products[i]["manufacturer"]);
		manufacturer = $('<textarea />').html(manufacturer).text();
		
        var article 				= String(answer.Products[i]["article"]);
        var name 					= String(answer.Products[i]["name"]);
        var article_show 			= String(answer.Products[i]["article_show"]);
        var exist 					= answer.Products[i]["exist"];
        var time_to_exe 			= answer.Products[i]["time_to_exe"];
		var price 					= answer.Products[i]["price"];
		var storage 				= answer.Products[i]["storage_id"] * 1;
		var storage_color 			= answer.Products[i]["color"];
		
		var search_name 			= answer.Products[i]["search_name"] * 1;// Флаг товара найденного по наименованию
		
		// Список найденных брендов
		if(arr_manufacturers.indexOf(manufacturer) === -1)
		{
			arr_manufacturers.push(manufacturer);
		}
		
		// Список найденных складов
		if(arr_storages.indexOf(storage) === -1)
		{
			arr_storages.push(storage);
			// Список фона складов
			arr_storages_color[storage] = storage_color;
		}
		
        //Добавляем объект в список всех объектов (AID)
        var AID_Object = new Object;//Учетный объект данного объекта товара, который будет добавлен в список Products.All
        AID_Object.aid = Products.All.length;//AID данного объекта товара
        answer.Products[i].aid = Products.All.length;//AID данного объекта товара
        
		// Если товар найден по наименованию
		if(search_name === 1){
			
			//Если такого производителя еще не было - создаем для него ячейку
			if(Products.SearchName.Products.Manufacturers[manufacturer] == undefined)
			{
				Products.SearchName.Products.Manufacturers[manufacturer] = new Object;
			}
			//Если такого артикула еще не было у данного производителя
			if(Products.SearchName.Products.Manufacturers[manufacturer][article] == undefined)
			{
				Products.SearchName.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
				
				//Создаем новый тип товара и добавляем его в список типов
				var ProductType = new Object;
				ProductType.manufacturer = manufacturer;
				ProductType.article = article;
				ProductType.name = name;
				ProductType.article_show = article_show;
				ProductType.exist = exist;
				ProductType.time_to_exe = time_to_exe;
				ProductType.price = price;
				ProductType.storage = storage;
				
				Products.SearchName.ProductsTypes.push(ProductType);
			}
			
			//Добавляем сам объект товара
			Products.SearchName.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
			
			//Для учетного объекта - указываем, что товар находится в списке аналогов
			AID_Object.isRequired 				= false;
			AID_Object.isSearchName 			= true;
			AID_Object.isQuickAnalogs 			= false;
			AID_Object.isAnalogs 				= false;
			AID_Object.isPossibleReplacement 	= false;
			AID_Object.isSpare_Box			 	= false;
			
			
		}else{
			
			
			//Продукт считает Запрошенным, если совпал Артикул
			if( article == search_object.article && (manufacturer == SelectedManufacturer || SelectedManufacturer == null) )
			{
				//Если такого производителя еще не было - создаем для него ячейку
				if(Products.Required.Products.Manufacturers[manufacturer] == undefined)
				{
					Products.Required.Products.Manufacturers[manufacturer] = new Object;
					Products.Required.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для самих объектов товаров
					
					//Создаем новый тип товара и добавляем его в список типов
					var ProductType = new Object;
					ProductType.manufacturer = manufacturer;
					ProductType.article = article;
					ProductType.name = name;
					ProductType.article_show = article_show;
					ProductType.exist = exist;
					ProductType.time_to_exe = time_to_exe;
					ProductType.price = price;
					ProductType.storage = storage;
					
					Products.Required.ProductsTypes.push(ProductType);
				}
				
				//Добавляем сам объект товара в ячейку
				Products.Required.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
				
				//Для учетного объекта - указываем, что товар находится в списке запрошенных
				AID_Object.isRequired 				= true;//Флаг - Запрошенный
				AID_Object.isSearchName 			= false;
				AID_Object.isQuickAnalogs 			= false;
				AID_Object.isAnalogs 				= false;
				AID_Object.isPossibleReplacement 	= false;
				AID_Object.isSpare_Box			 	= false;
				
			}
			else//Товар распределяем в Аналоги
			{
				if(article == search_object.article){
					// Возможные замены
					
					//Если такого производителя еще не было - создаем для него ячейку
						if(Products.PossibleReplacement.Products.Manufacturers[manufacturer] == undefined)
						{
							Products.PossibleReplacement.Products.Manufacturers[manufacturer] = new Object;
						}
						//Если такого артикула еще не было у данного производителя
						if(Products.PossibleReplacement.Products.Manufacturers[manufacturer][article] == undefined)
						{
							Products.PossibleReplacement.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
							
							//Создаем новый тип товара и добавляем его в список типов
							var ProductType = new Object;
							ProductType.manufacturer = manufacturer;
							ProductType.article = article;
							ProductType.name = name;
							ProductType.article_show = article_show;
							ProductType.exist = exist;
							ProductType.time_to_exe = time_to_exe;
							ProductType.price = price;
							ProductType.storage = storage;
							
							Products.PossibleReplacement.ProductsTypes.push(ProductType);
						}
						
						//Добавляем сам объект товара
						Products.PossibleReplacement.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
						
						//Для учетного объекта - указываем, что товар находится в списке аналогов
						AID_Object.isRequired 				= false;
						AID_Object.isSearchName 			= false;
						AID_Object.isQuickAnalogs 			= false;
						AID_Object.isAnalogs 				= false;
						AID_Object.isPossibleReplacement 	= true;
						AID_Object.isSpare_Box	 			= false;
						
				}else{
					// Аналоги делем на 2 категории:
					// - Те что со сроком 0-2 дня
					// - Остальные
					
					if(time_to_exe <= group_day){
						// быстрые аналоги
						//Если такого производителя еще не было - создаем для него ячейку
						if(Products.Quick_Analogs.Products.Manufacturers[manufacturer] == undefined)
						{
							Products.Quick_Analogs.Products.Manufacturers[manufacturer] = new Object;
						}
						//Если такого артикула еще не было у данного производителя
						if(Products.Quick_Analogs.Products.Manufacturers[manufacturer][article] == undefined)
						{
							Products.Quick_Analogs.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
							
							//Создаем новый тип товара и добавляем его в список типов
							var ProductType = new Object;
							ProductType.manufacturer = manufacturer;
							ProductType.article = article;
							ProductType.name = name;
							ProductType.article_show = article_show;
							ProductType.exist = exist;
							ProductType.time_to_exe = time_to_exe;
							ProductType.price = price;
							ProductType.storage = storage;
							
							Products.Quick_Analogs.ProductsTypes.push(ProductType);
						}
						
						//Добавляем сам объект товара
						Products.Quick_Analogs.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
						
						//Для учетного объекта - указываем, что товар находится в списке аналогов
						AID_Object.isRequired 				= false;
						AID_Object.isSearchName 			= false;
						AID_Object.isQuickAnalogs 			= true;
						AID_Object.isAnalogs 				= false;
						AID_Object.isPossibleReplacement 	= false;
						AID_Object.isSpare_Box	 			= false;
						
					}else{
						
						// Остальные аналоги
						//Если такого производителя еще не было - создаем для него ячейку
						if(Products.Analogs.Products.Manufacturers[manufacturer] == undefined)
						{
							Products.Analogs.Products.Manufacturers[manufacturer] = new Object;
						}
						//Если такого артикула еще не было у данного производителя
						if(Products.Analogs.Products.Manufacturers[manufacturer][article] == undefined)
						{
							Products.Analogs.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
							
							//Создаем новый тип товара и добавляем его в список типов
							var ProductType = new Object;
							ProductType.manufacturer = manufacturer;
							ProductType.article = article;
							ProductType.name = name;
							ProductType.article_show = article_show;
							ProductType.exist = exist;
							ProductType.time_to_exe = time_to_exe;
							ProductType.price = price;
							ProductType.storage = storage;
							
							Products.Analogs.ProductsTypes.push(ProductType);
						}
						
						//Добавляем сам объект товара
						Products.Analogs.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
						
						//Для учетного объекта - указываем, что товар находится в списке аналогов
						AID_Object.isRequired 				= false;
						AID_Object.isSearchName 			= false;						
						AID_Object.isQuickAnalogs 			= false;
						AID_Object.isAnalogs 				= true;
						AID_Object.isPossibleReplacement 	= false;
						AID_Object.isSpare_Box	 			= false;
					}
				}
			}
			
		}
		
		/*
		// Дополнительный свободный блок для доработок
		if(false){
			// Дополнительный свободный блок
			//Если такого производителя еще не было - создаем для него ячейку
			if(Products.Spare_Box.Products.Manufacturers[manufacturer] == undefined)
			{
				Products.Spare_Box.Products.Manufacturers[manufacturer] = new Object;
			}
			//Если такого артикула еще не было у данного производителя
			if(Products.Spare_Box.Products.Manufacturers[manufacturer][article] == undefined)
			{
				Products.Spare_Box.Products.Manufacturers[manufacturer][article] = new Array();//Ячейка для объектов товаров
				
				//Создаем новый тип товара и добавляем его в список типов
				var ProductType = new Object;
				ProductType.manufacturer = manufacturer;
				ProductType.article = article;
				ProductType.name = name;
				ProductType.article_show = article_show;
				ProductType.exist = exist;
				ProductType.time_to_exe = time_to_exe;
				ProductType.price = price;
				ProductType.storage = storage;
				
				Products.Spare_Box.ProductsTypes.push(ProductType);
			}
			
			//Добавляем сам объект товара
			Products.Spare_Box.Products.Manufacturers[manufacturer][article].push(answer.Products[i]);
			
			//Для учетного объекта - указываем, что товар находится в списке аналогов
			AID_Object.isRequired 				= false;
			AID_Object.isSearchName 			= false;						
			AID_Object.isQuickAnalogs 			= false;
			AID_Object.isAnalogs 				= false;
			AID_Object.isPossibleReplacement 	= false;
			AID_Object.isSpare_Box	 			= true;
		}
		*/
		
        //Добавляем учетный объект в список учетных объектов
        Products.All.push(AID_Object);
        
    }//for(i)
		
	// После того как мы опросили данного поставщика и получили текущий список найденных складов и производителей, нужно обновить фильтр складов и брендов
	
	// Бренды
	filter['manufacturer_blok'].list_options = new Array;
	arr_manufacturers.sort(sortFunction);// Сортировка
	for(var i = 0; i < arr_manufacturers.length; i++)
	{
		filter['manufacturer_blok'].list_options[i] = new Object;
		filter['manufacturer_blok'].list_options[i].id = i;
		filter['manufacturer_blok'].list_options[i].value = false;
		filter['manufacturer_blok'].list_options[i].text = arr_manufacturers[i];
		filter['manufacturer_blok'].list_options[i].search = arr_manufacturers[i];
	}
	
	// Склады
	filter['storages_blok'].list_options = new Array;
	arr_storages.sort(sortFunction);// Сортировка
	for(var i = 0; i < arr_storages.length; i++)
	{
		filter['storages_blok'].list_options[i] = new Object;
		filter['storages_blok'].list_options[i].id = i;
		filter['storages_blok'].list_options[i].value = false;
		
		var table = "<table><tr><td><div style=\"width: 30px; height: 10px; border:1px solid #eee;  display: inline-block; margin-right: 5px; margin-left: 3px; background:"+ arr_storages_color[arr_storages[i]] +";\"></div></td><td>"+ all_storages[arr_storages[i]] +"</td></tr></table>";
		
		filter['storages_blok'].list_options[i].text = table;
		filter['storages_blok'].list_options[i].search = String(arr_storages[i]);
	}
	
}




// ------------------------------------------------------------------------------------------------------------------------------




//Отображение/Переотображение результата запроса
function resultReview()
{
	//Общее содержимое
    var products_html = "";
    
    //HTML для блока с заголовками колонок
    var headlines = new Object;
    headlines.manufacturer = new Object;
    headlines.manufacturer.caption = "Производитель";
    headlines.manufacturer.subclass = "";
    headlines.article = new Object;
    headlines.article.caption = "Артикул";
    headlines.article.subclass = "";
    headlines.name = new Object;
    headlines.name.caption = "Наименование";
    headlines.name.subclass = "";
    headlines.exist = new Object;
    headlines.exist.caption = "Наличие";
    headlines.exist.subclass = "";
    headlines.price = new Object;
    headlines.price.caption = "Цена, <?php echo str_replace('"', '\"', $currency_sign); ?>";
    headlines.price.subclass = "";
    headlines.time_to_exe = new Object;
    headlines.time_to_exe.caption = "Срок";
    headlines.time_to_exe.subclass = "";
	if(flag_one != true){
		//Ставим обозначение текущей внутренней сортировки
		if(
			outerSortState.field == innerSortState.field
		){
			headlines[innerSortState.field].caption += " <img src=\"/content/files/images/"+innerSortState.asc_desc+".png\" />";
			headlines[innerSortState.field].subclass += " sorted";
		}
		
		//Ставим обозначение текущей внешней сортировки, т.е. по полям Производитель, Артикул, Наименование
		if( (outerSortState.field != 'exist' && 
			outerSortState.field != 'time_to_exe' && 
			outerSortState.field != 'price') || outerSortState.field != innerSortState.field
		){
			headlines[outerSortState.field].caption += " <img src=\"/content/files/images/"+outerSortState.asc_desc+".png\" />";
			headlines[outerSortState.field].subclass += " sorted";
		}
	}
    
	
    var head_block = '<tr><th class="th_manufacturer'+headlines.manufacturer.subclass+'" onclick="outerSortChange(\'manufacturer\');">'+headlines.manufacturer.caption+'</th><th class="th_article'+headlines.article.subclass+'" onclick="outerSortChange(\'article\');">'+headlines.article.caption+'</th><th class="th_name'+headlines.name.subclass+'" onclick="outerSortChange(\'name\');">'+headlines.name.caption+'</th><th class="th_exist'+headlines.exist.subclass+'" onclick="innerSortChange(\'exist\');">'+headlines.exist.caption+'</th><th class="th_time_to_exe'+headlines.time_to_exe.subclass+'" onclick="innerSortChange(\'time_to_exe\');">'+headlines.time_to_exe.caption+'</th><th class="th_info">Инфо</th><th class="th_price'+headlines.price.subclass+'" onclick="innerSortChange(\'price\');">'+headlines.price.caption+'</th><th class="th_add_to_cart"></th><th class="th_color"></th></tr>';
    
    
    //HTML для запрошенного артикула
    var required_block = "";
	//HTML для найденных по наименованию
    var SearchName_block = "";
	//HTML для быстрых аналогов
	var quick_analogs_block = "";
    //HTML для аналогов
    var analogs_block = "";
	//HTML для блока Возможные замены
	var PossibleReplacement_block = "";
	//HTML для дополнительного блока
	var Spare_Box_block = "";
	
	
	// Найденные бренды после фильтрации = сбрасываем список перед фильтрацией
	arr_manufacturers_posle_filter =  new Array();
	// Найденные склады после фильтрации
	arr_storages_posle_filter =  new Array();
	
	
	// Массив всех найденных позиций после фильтрации. Нужен для формирования фильтра
	var ALL_ProductsObjects = new Array;
    

	// Если текущий выбранный фильтр производители или склады то сбрасываем фильтры диапазонов на предыдущие значения, что бы можно было отменить фильтр убрав checkbox
	if(this_filter == 'manufacturer_blok' || this_filter == 'storages_blok' || this_filter == 'sam_price_time_blok'){
	// Цена
		filter['price_blok'].min_need = filter['price_blok'].old_min_need;
		filter['price_blok'].max_need = filter['price_blok'].old_max_need;
	// Срок
		filter['time_to_exe_blok'].min_need = filter['time_to_exe_blok'].old_min_need;
		filter['time_to_exe_blok'].max_need = filter['time_to_exe_blok'].old_max_need;
	// Наличие
		filter['exist_blok'].min_need = filter['exist_blok'].old_min_need;
		filter['exist_blok'].max_need = filter['exist_blok'].old_max_need;
	}
	
	
	// Ограничение количества отображаемых позиций (10, 20, 50)
	// Счетчик отфильтрованных групп
	var Products_Required_Show_count = 0;
	var Products_SearchName_Show_count = 0;
	var Products_Quick_Analogs_Show_count = 0;
	var Products_Analogs_Show_count = 0;
	var Products_PossibleReplacement_Show_count = 0;
	var Products_Spare_Box_Show_count = 0;
	// Количество групп после фильтрации (нужно для определения кнопки Показать еще)
	var Products_Required_count = 0;
	var Products_SearchName_count = 0;
	var Products_Quick_Analogs_count = 0;
	var Products_Analogs_count = 0;
	var Products_PossibleReplacement_count = 0;
	var Products_Spare_Box_count = 0;
	
	
	
	
	
	
	
	
	// Работаем с Запрошенным
    for(var i=0; i < Products.Required.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
        var Article = Products.Required.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Required_count++;
			if( (cnt_on_page + start_page_Required) <= Products_Required_Show_count ){continue;}
			Products_Required_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				required_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Required.ProductsTypes[i], 'required');
			}
		}
    }// END Работаем с Запрошенным
    
	
	
	
    
	
	
	
	// Работаем с найденными по НАИМЕНОВАНИЮ
    for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
        var Article = Products.SearchName.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];
		
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_SearchName_count++;
			if( (cnt_on_page + start_page_SearchName) <= Products_SearchName_Show_count ){continue;}
			Products_SearchName_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				SearchName_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.SearchName.ProductsTypes[i], 'SearchName');
			}
		}
    }// END
	
	
	
	
	
	
	
	
	// Работаем с Быстрыми Аналогами
	for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Quick_Analogs.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
		
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Quick_Analogs_count++;
			if( (cnt_on_page + start_page_Quick_Analogs) <= Products_Quick_Analogs_Show_count ){continue;}
			Products_Quick_Analogs_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				quick_analogs_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Quick_Analogs.ProductsTypes[i], 'quick_analogs');
			}
		}
    }// END Работаем с Быстрыми Аналогами
	
	
	
	
	
	
	
	
   // Работаем с Аналогами
    for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Analogs.ProductsTypes[i].article;
        //Массив объектов товаров:
        var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
		
		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
		
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Analogs_count++;
			if( (cnt_on_page + start_page_Analogs) <= Products_Analogs_Show_count ){continue;}
			Products_Analogs_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				analogs_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Analogs.ProductsTypes[i], 'analogs');
			}
		}
    }// END Работаем с Аналогами
	
	
	
	
	
	
	
	
	// Работаем с PossibleReplacement
    for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
        var Article = Products.PossibleReplacement.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_PossibleReplacement_count++;
			if( (cnt_on_page + start_page_PossibleReplacement) <= Products_PossibleReplacement_Show_count ){continue;}
			Products_PossibleReplacement_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				PossibleReplacement_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.PossibleReplacement.ProductsTypes[i], 'PossibleReplacement');
			}
		}
    }// END Работаем с PossibleReplacement
	
	
	
	
	
	
	
	
	// Работаем с Spare_Box
    for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
    {
		var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
        var Article = Products.Spare_Box.ProductsTypes[i].article;
		//Массив объектов товаров:
        var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];

		
		// Фильтрация позиций
		if(this_filter != ''){ProductsObjects = filtering_items(ProductsObjects);}
		// Если установлены флаги самых быстрых и дешевых позиций в группе
		if(sam_price_time != ''){ProductsObjects = sam_price_time_fanc(ProductsObjects);}
	
		
		if(ProductsObjects.length > 0){
			// Общий массив позиций, по нему формируются новые значения фильтра
			ALL_ProductsObjects.push(ProductsObjects);
			
			// Если количество отображенных позиций больше или равно количеству которое мы можем отобразить то выходим из цикла что бы не показывать оставшиеся позиции
			Products_Spare_Box_count++;
			if( (cnt_on_page + start_page_Spare_Box) <= Products_Spare_Box_Show_count ){continue;}
			Products_Spare_Box_Show_count++;
			
			// Формируем html позиций блока
			for(var p=0; p < ProductsObjects.length; p++){
				Spare_Box_block += getProductRecordHTML(ProductsObjects[p], p, ProductsObjects.length, Products.Spare_Box.ProductsTypes[i], 'Spare_Box');
			}
		}
    }// END Работаем с Spare_Box
	
	
	
	
	
	
	
	// После обработки всех позиций формируем новый фильтр диапазонов
	// Изменяем крайние значения фильтра, если фильтр диапазона не является текущим.
	if(ALL_ProductsObjects.length > 0){
		// Цена
		if(this_filter !== 'price_blok'){
			filter['price_blok'].min_value = undefined;
			filter['price_blok'].max_value = undefined;
		}
		// Срок
		if(this_filter !== 'time_to_exe_blok'){
			filter['time_to_exe_blok'].min_value = undefined;
			filter['time_to_exe_blok'].max_value = undefined;
		}
		// Наличие
		if(this_filter !== 'exist_blok'){
			filter['exist_blok'].min_value = undefined;
			filter['exist_blok'].max_value = undefined;
		}
	}
	
	
	// Устанавливаем новые значения фильтра
	for(var p=0; p < ALL_ProductsObjects.length; p++){
		var ProductsObjects_array = ALL_ProductsObjects[p];
		for(var i=0; i < ProductsObjects_array.length; i++){
			
			var ProductsObjects = ProductsObjects_array[i];
			
			// Цена
			if(filter['price_blok'].min_value == undefined){
				filter['price_blok'].min_value = ProductsObjects["price"];
			}
			if(filter['price_blok'].max_value == undefined){
				filter['price_blok'].max_value = ProductsObjects["price"];
			}
			
			if(ProductsObjects["price"] < filter['price_blok'].min_value){
				filter['price_blok'].min_value = ProductsObjects["price"];
			}
			if(ProductsObjects["price"] > filter['price_blok'].max_value){
				filter['price_blok'].max_value = ProductsObjects["price"];
			}
			
			// Срок
			if(filter['time_to_exe_blok'].min_value == undefined){
				filter['time_to_exe_blok'].min_value = ProductsObjects["time_to_exe"];
			}
			if(filter['time_to_exe_blok'].max_value == undefined){
				filter['time_to_exe_blok'].max_value = ProductsObjects["time_to_exe"];
			}
			
			if(ProductsObjects["time_to_exe"] < filter['time_to_exe_blok'].min_value){
				filter['time_to_exe_blok'].min_value = ProductsObjects["time_to_exe"];
			}
			if(ProductsObjects["time_to_exe"] > filter['time_to_exe_blok'].max_value){
				filter['time_to_exe_blok'].max_value = ProductsObjects["time_to_exe"];
			}
			
			// Наличие
			if(filter['exist_blok'].min_value == undefined){
				filter['exist_blok'].min_value = ProductsObjects["exist"];
			}
			if(filter['exist_blok'].max_value == undefined){
				filter['exist_blok'].max_value = ProductsObjects["exist"];
			}
			
			if(ProductsObjects["exist"] < filter['exist_blok'].min_value){
				filter['exist_blok'].min_value = ProductsObjects["exist"];
			}
			if(ProductsObjects["exist"] > filter['exist_blok'].max_value){
				filter['exist_blok'].max_value = ProductsObjects["exist"];
			}
			
        }
	}
	
	
	
	
	
	
	// Формируем HTML проценки
	
	if(required_block != ''){
		required_block = '<tr><td colspan="9" class="products_table_block_caption">Запрошенный артикул</td></tr>' + required_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Required\');" id="next_page_Required"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}else{
		required_block = '<tr><td colspan="9" class="products_table_block_caption">Запрошенный артикул не найден</td></tr>';
	}
	
	if(quick_analogs_block != "")
	{
		quick_analogs_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Аналоги с быстрой доставкой</td></tr>' + quick_analogs_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Quick_Analogs\');" id="next_page_Quick_Analogs"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(analogs_block != ''){
		analogs_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Аналоги и заменители</td></tr>' + analogs_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Analogs\');" id="next_page_Analogs"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(SearchName_block != ''){
		SearchName_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Товары найденные по наименованию</td></tr>' + SearchName_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'SearchName\');" id="next_page_SearchName"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(PossibleReplacement_block != ''){
		PossibleReplacement_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Возможные замены</td></tr>' + PossibleReplacement_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'PossibleReplacement\');" id="next_page_PossibleReplacement"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	if(Spare_Box_block != ''){
		Spare_Box_block = '<tr><td style="padding-top: 70px; border-top: 0;" colspan="9" class="products_table_block_caption">Дополнительный блок</td></tr>' + Spare_Box_block + '<tr><td style="text-align:center; padding:0;" class="next_page_box" colspan="9"><a style="margin-top:15px; display:none;" class="btn btn-ar btn-primary" href="javascript:void(0);" onclick="next_page(\'Spare_Box\');" id="next_page_Spare_Box"><i class="fa fa-arrow-down" aria-hidden="true"></i> Показать еще</a></td></tr>';
	}
	
	// Оюъединяем блоки позиций
	products_html = required_block + PossibleReplacement_block + quick_analogs_block + analogs_block + SearchName_block + Spare_Box_block;
	
	if(products_html != ''){
		products_html = '<table id="all_table_products"><thead>' + head_block + '</thead><tbody>' + products_html + '</tbody></table>';
	}
	
    
	
	
	
	
	
	// Если после фильтрации все же не осталось позиций
	if(this_filter != '' && products_html == ''){
		products_html = '<div>Товары не найдены</div>';
	}
    
	// Отображаем проценку
    document.getElementById("products_area").innerHTML = products_html;
	
	// Отображаем фильтр
	showPropertiesWidgets();
	
	// Работаем с кнопками "показать еще" вконце блоков
	var next_page_Required = document.getElementById('next_page_Required');
	var next_page_SearchName = document.getElementById('next_page_SearchName');
	var next_page_Quick_Analogs = document.getElementById('next_page_Quick_Analogs');
	var next_page_Analogs = document.getElementById('next_page_Analogs');
	var next_page_PossibleReplacement = document.getElementById('next_page_PossibleReplacement');
	var next_page_Spare_Box = document.getElementById('next_page_Spare_Box');
	
	if(next_page_Required){
		if((start_page_Required + cnt_on_page) >= Products_Required_Show_count){
			next_page_Required.style.display = 'none';
		}
		if((start_page_Required + cnt_on_page) < Products_Required_count){
			next_page_Required.style.display = 'inline-block';
		}
	}
	
	if(next_page_SearchName){
		if((start_page_SearchName + cnt_on_page) >= Products_SearchName_Show_count){
			next_page_SearchName.style.display = 'none';
		}
		if((start_page_SearchName + cnt_on_page) < Products_SearchName_count){
			next_page_SearchName.style.display = 'inline-block';
		}
	}
	
	if(next_page_Quick_Analogs){
		if((start_page_Quick_Analogs + cnt_on_page) >= Products_Quick_Analogs_Show_count){
			next_page_Quick_Analogs.style.display = 'none';
		}
		if((start_page_Quick_Analogs + cnt_on_page) < Products_Quick_Analogs_count){
			next_page_Quick_Analogs.style.display = 'inline-block';
		}
	}

	if(next_page_Analogs){
		if((start_page_Analogs + cnt_on_page) >= Products_Analogs_Show_count){
			next_page_Analogs.style.display = 'none';
		}
		if((start_page_Analogs + cnt_on_page) < Products_Analogs_count){
			next_page_Analogs.style.display = 'inline-block';
		}
	}
	
	if(next_page_PossibleReplacement){
		if((start_page_PossibleReplacement + cnt_on_page) >= Products_PossibleReplacement_Show_count){
			next_page_PossibleReplacement.style.display = 'none';
		}
		if((start_page_PossibleReplacement + cnt_on_page) < Products_PossibleReplacement_count){
			next_page_PossibleReplacement.style.display = 'inline-block';
		}
	}
	
	if(next_page_Spare_Box){
		if((start_page_Spare_Box + cnt_on_page) >= Products_Spare_Box_Show_count){
			next_page_Spare_Box.style.display = 'none';
		}
		if((start_page_Spare_Box + cnt_on_page) < Products_Spare_Box_count){
			next_page_Spare_Box.style.display = 'inline-block';
		}
	}
}




// ------------------------------------------------------------------------------------------------------------------------------




// Единая функция формирования HTML-кода для одной записи товара.
function getProductRecordHTML(Product, index, quantity, ProductType, blok)
{
    var manufacturer = "", article_show = "", name = "";

	var time_to_exe = Product.time_to_exe;
	if(Product.time_to_exe != Product.time_to_exe_guaranteed)
	{
		time_to_exe = Product.time_to_exe + "-" + Product.time_to_exe_guaranteed +" дн.";
	}else{
		if(time_to_exe == 0)
		{
			time_to_exe = "На складе";
		}else{
			time_to_exe = time_to_exe + " дн.";
		}
	}
    var color = Product.color;
    
	
	
	
	//Строка для показа цены
	var price = Product.price;
	// Отделяем разрядность
	price = digit(price)
	<?php
	//Индикатор валюты перед ценой
	if($DP_Config->currency_show_mode == "sign_before")
	{
		?>
		price = '<?php echo $currency_indicator; ?> ' + price;
		<?php
	}
	//Индикатор валюты после цены
	else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
	{
		?>
		price = price + ' <?php echo $currency_indicator; ?>';
		<?php
	}
	?>




    //ФОРМИРОВАНИЕ КОЛОНКИ ИНФО
	var info = '';
	
    info += "<a title=\"Фотографии товара\" href=\"https://www.google.ru/search?q="+encodeURIComponent(ProductType.manufacturer)+"+"+ProductType.article+"&newwindow=1&biw=1366&bih=667&tbm=isch&tbo=u&source=univ&sa=X&ved=0CC8QsARqFQoTCMDCoO70jMkCFQGFLAodrT0GFw\" target=\"_blank\"><span><i style=\"font-size: .8em;\" class=\"fa fa-camera\"></i></span></a>";
	
	if(Product.storage_caption != "")
    {
		var storage_caption = '<br>Поставщик: '+Product.storage_caption;
			storage_caption = storage_caption + '<br>Склад: '+all_storages[Product.storage_id];
			storage_caption = storage_caption + '<br>ID: '+Product.storage_id;
    }else{
		var storage_caption = '<br>Склад: '+all_storages[Product.storage_id];
	}
	
	info += "<a title=\"Где забрать\" href=\"javascript:void(0);\"><span onclick=\"openInfoWindow('Где забрать', '"+Product.office_caption+storage_caption+"');\" ><i class=\"fa fa-home\"></i></span></a>";
    
	<?php
	if($user_id){
	?>
	info += "<a href=\"javascript:void(0);\" title=\"Добавить в блокнот\" onclick=\"show_add_bloknot("+Product.aid+");\"><span><i style=\"font-size: .9em;\" class=\"fa fa-car\"></i></span></a>";
	<?php
	}else{
	?>
	info += "<a href=\"javascript:void(0);\" title=\"Добавить в блокнот\" onclick=\"alert('Для добавления позиций в блокнот необходимо авторизоваться на сайте');\"><span><i style=\"font-size: .9em;\" class=\"fa fa-car\"></i></span></a>";
	<?php
	}
	?>
	
	info += "<br/>";
	
	if(Product.min_order > 1)
    {
        info += "<a title=\"Минимальный заказ\" href=\"javascript:void(0);\"><span onclick=\"openInfoWindow('Внимание!', 'Минимальный заказ "+Product.min_order+" шт.');\"><i class=\"fa fa-warning\"></i></span></a>";
    }
	
    if(Product.product_type == 1)//Для товара из каталога Treelax - выводим ссылку на страницу товара
    {
        info += "<a title=\"Открыть карточку товара\" href=\""+Product.url+"\" target=\"_blank\"><i class=\"fa fa-file-image-o\"></i></span></a>";
    }
	
	info = '<span class="info_box">'+ info +'</span>';
    
	
	
	
    //Формирование колонки "Наличие"
    //Объект с описанием наличия - для отображения в информационном окне
    var supply_info_json = "{&quot;exist&quot;:"+Product.exist+",&quot;time_to_exe&quot;:"+Product.time_to_exe+",&quot;time_to_exe_guaranteed&quot;:"+Product.time_to_exe_guaranteed+",&quot;probability&quot;:"+Product.probability+"}";
    //Колонка
    var exist = "<span onclick=\"openInfoWindow(null, null, 1, '"+supply_info_json+"');\">" + Product.exist + "<img src=\"/lib/TreelaxCharts/sectors.php?number=2&value0="+Product.probability+"&value1="+(100-Product.probability)+"&start_angle=30&size=50&inside_size=1&slope=1.1\" /></span>";
    
	
    
	
    // Сворачиваем строки, если их больше чем указано в настройках для блока
    var start_wrap_div = "<tr>";
    var end_wrap_div = "</tr>";
    var show_hide_button = "";//Кнопка "Показать/Скрыть"
	var tmp_cnt_to_hide = cnt_to_hide;
	if(blok == 'required'){
		tmp_cnt_to_hide = 7;
	}
    if(index >= tmp_cnt_to_hide)
    {
        //В зависимости от текущего состояния - задаем значение атрибута style:
        var row_style = "";
        if(wrap_states[wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]] == false)
        {
            row_style = "display:none;";
        }
    
        start_wrap_div = "<tr style=\""+row_style+"\" class=\"hide_row hide_row_"+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+"\">";//Начинаем блок для сворачивания
    }else{
		if(index > 0){
			start_wrap_div = "<tr class=\"hide_row\">";
		}
	}
   
   
    if(index == 0 && quantity > tmp_cnt_to_hide)//Если это первый элемент, но не единственный - приделываем кнопку Показать/Скрыть. Работаем с индексным списком для таких блоков
    {
        //Добавляем ID
        if(wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok] == undefined)
        {
            wrap_blocks_index[wrap_blocks_index.length] = Product.manufacturer+Product.article+"_"+blok;//Добавляем Производитель+Артикул в индексный массив
            wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok] = wrap_blocks_index.length-1;//Добавляем Производитель+Артикул как ключ в ассоциативный массив
            wrap_states[wrap_blocks_index.length-1] = false;//По умолчанию блок закрыт
        }
        
        var show_hide_text = "Показать еще";
        if(wrap_states[wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]] == true)
        {
            show_hide_text = "Скрыть";
        }
        
        show_hide_button = "<div class=\"show_hide_button\" onclick=\"show_hide_block("+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+", false);\"><span style=\"line-height:1.4em;\" id=\"show_hide_button_"+wrap_blocks_assoc[Product.manufacturer+Product.article+"_"+blok]+"\">"+show_hide_text+"</span></div>";
    }
    
    
	
	
    //Колонки Производитель, Артикул, Наименование
    if(index == 0)//Если это первый товар в блоке
    {
		manufacturer = '<span title="'+ ProductType.manufacturer +'">'+ ProductType.manufacturer +'</span>';
        article_show = "<a title='Искать: "+ ProductType.article_show +"' class=\"bread_crumbs_a\" style=\"text-decoration:underline; color:#000;\" href=\"/shop/part_search?article="+Product.article+"\">"+ProductType.article_show+"</a>";
        name = "<span title=\""+Product.name+"\">"+ProductType.name+"</span>";
    }
    
	
	
	
	// Кнопки увеличения количества товара добавляемого в корзину //////////////////////////////////////////////////////////////////////////////////////////////
	var p_min_order = Product.min_order * 1;
	var p_exist = Product.exist * 1;
	
	if(p_min_order == 0){
		p_min_order = 1;
	}
	if(p_exist == 0){
		p_exist = 1;
	}
	
	cart_html = '<div style="text-align: center; line-height: 1em;"><table>';
		cart_html += '<tr><td><a style="font-size: 20px; padding-right: 3px;" href="javascript:void(0);" onclick="addToCart('+Product.aid+');"><span><i class="fa fa-shopping-cart"></i></span></a></td></tr>';
		cart_html += '<tr><td><div style="text-align: center; background: #f9f9f9; border: 1px solid #ddd; padding: 2px 1px; border-radius: 3px; font-size: 11px; margin-bottom: 1px;"><table><tr>';
			cart_html += "<td><a style='width:12px;' onclick=\"minusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\"count_need_minus\" href=\"javascript:void(0);\"><i class=\"fa fa-minus\"></i></a></td>";
			cart_html += "<td><input style=\"text-align:center; width:24px; font-size: 10px; height: 14px; margin: 0px 2px; vertical-align: middle; border: 1px solid #ddd; border-radius: 2px !important; box-shadow: none; -webkit-appearance: none; -moz-appearance: none; appearance: none;\" type=\"text\" value=\""+p_min_order+"\" onChange=\"onKeyUpCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" id=\"count_need_"+Product.aid+"\" /></td>";
			cart_html += "<td><a style='width:12px;' onclick=\"plusCountNeed("+Product.aid+", "+p_exist+", "+p_min_order+");\" class=\"count_need_plus\" href=\"javascript:void(0);\"><i class=\"fa fa-plus\"></i></a></td>";
		cart_html += '</tr></table></div></td></tr>';
	cart_html += '</table></div>';
	//////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
    return start_wrap_div + '<td class="td_manufacturer">'+ manufacturer + show_hide_button +'</td><td class="td_article">'+ article_show +'</td><td class="td_name">'+ name +'</td><td class="td_exist">'+ exist +'</td><td class="td_time_to_exe"><span onclick="openInfoWindow(null, null, 1, \''+ supply_info_json +'\');">'+ time_to_exe +'</span></td><td class="td_info">'+ info +'</td><td class="td_price">'+ price +'</td><td class="td_add_to_cart">'+ cart_html +'</td>'+ '<td class="td_color" style="background:'+color+'"></td>' + end_wrap_div;
}




// ------------------------------------------------------------------------------------------------------------------------------




//Индексный и ассоциативный массивы для блоков скрытия "Остальных товаров"
var wrap_blocks_assoc = new Array();//Производитель+Артикул_+blok указывает на индекс
var wrap_blocks_index = new Array();//Индекс указывает на Производитель+Артикул_+blok
var wrap_states = new Array();//Индекс указывает на флаг Открыт(true)/Закрыт(false)
//Скрываем / Открываем блок. immediately - флаг. Скрыть сразу, т.е. без анимации
function show_hide_block(id, immediately)
{
    var show_hide_button = document.getElementById("show_hide_button_"+id);
    
	if(show_hide_button == undefined){
		return;
	}
	
    //Обращаем состояние блока
    if(wrap_states[id] == false)
    {
        //Открываем
        wrap_states[id] = true;
        if(immediately)
        {
			$(".hide_row_"+id).css('display', 'table-row');
        }
        else
        {
            $(".hide_row_"+id).show("slow");
        }
        show_hide_button.innerHTML = "Скрыть";
    }
    else//Скрываем
    {
        wrap_states[id] = false;
        if(immediately)
        {
            $(".hide_row_"+id).css('display', 'none');
        }
        else
        {
            $(".hide_row_"+id).hide(300);
        }
        show_hide_button.innerHTML = "Показать еще";
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




//ВНУТРЕННЯЯ СОРТИРОВКА
var innerSortState = new Object;//Объект описания внутренней сортировки
innerSortState.field = "price";
innerSortState.asc_desc = "asc";
function innerSort()
{
    //Сортируем каждый набор видов товаров по полю (Запрошенный)
    for(var i=0; i < Products.Required.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
        var Article = Products.Required.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.Required.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= 2 && ProductsObjects[p].time_to_exe_guaranteed <= 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > 2 || ProductsObjects[p].time_to_exe_guaranteed > 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Required.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Required.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Required.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Required.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.Required.ProductsTypes[i].exist = exist;
		Products.Required.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Required.ProductsTypes[i].price = price;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров в группе Найденных по наименованию
    for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
        var Article = Products.SearchName.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.SearchName.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.SearchName.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.SearchName.ProductsTypes[i].exist = exist;
		Products.SearchName.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.SearchName.ProductsTypes[i].price = price;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (Быстрые Аналоги)
    for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Quick_Analogs.ProductsTypes[i].article;
        Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= 2 && ProductsObjects[p].time_to_exe_guaranteed <= 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > 2 || ProductsObjects[p].time_to_exe_guaranteed > 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.Quick_Analogs.ProductsTypes[i].exist = exist;
		Products.Quick_Analogs.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Quick_Analogs.ProductsTypes[i].price = price;
    }
    
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
    //Сортируем каждый набор видов товаров по полю (Аналоги)
	for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
        var Article = Products.Analogs.ProductsTypes[i].article;
        Products.Analogs.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= 2 && ProductsObjects[p].time_to_exe_guaranteed <= 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > 2 || ProductsObjects[p].time_to_exe_guaranteed > 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Analogs.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Analogs.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.Analogs.ProductsTypes[i].exist = exist;
		Products.Analogs.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Analogs.ProductsTypes[i].price = price;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (PossibleReplacement)
    for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
        var Article = Products.PossibleReplacement.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		
		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= 2 && ProductsObjects[p].time_to_exe_guaranteed <= 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > 2 || ProductsObjects[p].time_to_exe_guaranteed > 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.PossibleReplacement.ProductsTypes[i].exist = exist;
		Products.PossibleReplacement.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.PossibleReplacement.ProductsTypes[i].price = price;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	//Сортируем каждый набор видов товаров по полю (Spare_Box)
    for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
    {
        var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
        var Article = Products.Spare_Box.ProductsTypes[i].article;
        // Сортируем группу позиций
        Products.Spare_Box.Products.Manufacturers[Manufacturer][Article].sort(compareNumbers);
		

		// Для того что бы показать товары с доставкой 0-2 дня - вверху списка, после сортировки позиций по цене сформируем новый массив продуктов куда в начало запишем позиции с быстрой доставкой а затем остальные, таким образом они так же останутся отсортированными по цене
		if(flag_time_head && flag_one){
			// новый массив с запрошенным артикулом
			var array_products = new Array();
			
			//Массив объектов товаров:
			var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
			
			// Сначала добавляем в массив товары с доставкой 0-2 дн.
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe <= 2 && ProductsObjects[p].time_to_exe_guaranteed <= 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Теперь добавляем в массив все остальные товары
			for(var p=0; p < ProductsObjects.length; p++)
			{
				if(ProductsObjects[p].time_to_exe > 2 || ProductsObjects[p].time_to_exe_guaranteed > 2)
				{
					array_products.push(ProductsObjects[p]);
				}
			}
			
			// Сохраняем
			Products.Spare_Box.Products.Manufacturers[Manufacturer][Article] = array_products;
		}
		
		// После того как отсортировали позиции внутри группы, нужно отсортировать сами группы
		// Записываем текущие значения свойств первой позиции в группе что затем произвести внешнею сортировку
		var exist = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].exist * 1;
		var time_to_exe = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].time_to_exe * 1;
		var price = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article][0].price * 1;
		
		Products.Spare_Box.ProductsTypes[i].exist = exist;
		Products.Spare_Box.ProductsTypes[i].time_to_exe = time_to_exe;
		Products.Spare_Box.ProductsTypes[i].price = price;
    }
	
	
	
	////////////////////////////////////////////////////////////////////////////
	
	
	
	// Производим внешнюю сортировку с учетом внутренней что бы отсортировать группы
	if(flag_one == true){
		outerSortState.field = 'time_to_exe';
	}else{
		outerSortState.field = innerSortState.field;
	}
	outerSortState.asc_desc = innerSortState.asc_desc;
	outerSort();
}




// ------------------------------------------------------------------------------------------------------------------------------




// Сортировка числительных значений внутри группы
function compareNumbers(x, y)
{
    if(innerSortState.asc_desc == "asc")
    {
        if(parseFloat(x[innerSortState.field]) > parseFloat(y[innerSortState.field]))
        {
            return 1;
        }
        else if(parseFloat(x[innerSortState.field]) < parseFloat(y[innerSortState.field]))
        {
            return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(innerSortState.field == 'price'){
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}else{
					return 0;
				}
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}else{
					return 0;
				}
			}
        }
    }
    else
    {
        if(parseFloat(x[innerSortState.field]) < parseFloat(y[innerSortState.field]))
        {
            return 1;
        }
        else if(parseFloat(x[innerSortState.field]) > parseFloat(y[innerSortState.field]))
        {
            return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(innerSortState.field == 'price'){
					
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
				
			}else{
				
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
			}
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




// Сортировка числительных значений между группами (для внешней сортировки)
function compareNumbers_2(x, y)
{
	if(outerSortState.asc_desc == "asc")
    {
        if(parseFloat(x[outerSortState.field]) > parseFloat(y[outerSortState.field]))
        {
            return 1;
        }
        else if(parseFloat(x[outerSortState.field]) < parseFloat(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(outerSortState.field == 'price'){
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}else{
					return 0;
				}
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}else{
					return 0;
				}
			}
        }
    }
    else
    {
        if(parseFloat(x[outerSortState.field]) < parseFloat(y[outerSortState.field]))
        {
            return 1;
        }
        else if(parseFloat(x[outerSortState.field]) > parseFloat(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            // При равных значениях сортируем либо по цене либо по сроку
			if(outerSortState.field == 'price'){
				
				if(parseFloat(x['time_to_exe']) > parseFloat(y['time_to_exe']))
				{
					return 1;
				}
				else if(parseFloat(x['time_to_exe']) < parseFloat(y['time_to_exe']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
				
			}else{
				if(parseFloat(x['price']) > parseFloat(y['price']))
				{
					return 1;
				}
				else if(parseFloat(x['price']) < parseFloat(y['price']))
				{
					return -1;
				}
				else
				{
					return 0;
				}
			}
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




//Смена состояния внутренней сортировки
function innerSortChange(field)
{
	if(flag_one == true && field == 'price'){
		flag_one = false;
		innerSortState.asc_desc = "asc";
		innerSortState.field = field;
	}else{
		//Если тоже поле - меняем только направление
		if(innerSortState.field == field)
		{
			if(innerSortState.asc_desc == "asc")
			{
				innerSortState.asc_desc = "desc";
			}
			else
			{
				innerSortState.asc_desc = "asc";
			}
		}
		else//Если поле другое - ставим это поле и направление asc
		{
			flag_one = false;
			innerSortState.asc_desc = "asc";
			innerSortState.field = field;
		}
    }
   
    //Производим саму сортировку
    innerSort()
	
    //Обновляем отображние результата
	if(this_filter != ''){
		productsCountRequest(this_filter.replace('_blok', ''));
	}else{
		resultReview();
	}
}




// ------------------------------------------------------------------------------------------------------------------------------




//ВНЕШНЯЯ СОРТИРОВКА
var outerSortState = new Object;//Объект описания внешней сортировки
function outerSort()
{
	if(outerSortState.field == "exist" || outerSortState.field == "time_to_exe" || outerSortState.field == "price"){
		Products.Required.ProductsTypes.sort(compareNumbers_2);
		Products.SearchName.ProductsTypes.sort(compareNumbers_2);
		Products.Quick_Analogs.ProductsTypes.sort(compareNumbers_2);
		Products.Analogs.ProductsTypes.sort(compareNumbers_2);
		Products.PossibleReplacement.ProductsTypes.sort(compareNumbers_2);
		Products.Spare_Box.ProductsTypes.sort(compareNumbers_2);
	}else{
		Products.Required.ProductsTypes.sort(compareStrings);
		Products.SearchName.ProductsTypes.sort(compareStrings);
		Products.Quick_Analogs.ProductsTypes.sort(compareStrings);
		Products.Analogs.ProductsTypes.sort(compareStrings);
		Products.PossibleReplacement.ProductsTypes.sort(compareStrings);
		Products.Spare_Box.ProductsTypes.sort(compareStrings);
	}
	
	/*
	// При первоначальной загрузке аналоги сортируем по бренду
	if(flag_one == true){
		outerSortState.field = 'manufacturer';
		outerSortState.asc_desc = 'asc';
		Products.Analogs.ProductsTypes.sort(compareStrings);
	}
	if(flag_one == true){
		outerSortState.field = 'price';
		outerSortState.asc_desc = 'asc';
		Products.Quick_Analogs.ProductsTypes.sort(compareNumbers_2);
	}
	*/
}




// ------------------------------------------------------------------------------------------------------------------------------




//Функция сравнения строковых значений
function compareStrings(x, y)
{
    if(outerSortState.asc_desc == "asc")
    {
        if(String(x[outerSortState.field]) > String(y[outerSortState.field]))
        {
            return 1;
        }
        else if(String(x[outerSortState.field]) < String(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
    else
    {
        if(String(x[outerSortState.field]) < String(y[outerSortState.field]))
        {
            return 1;
        }
        else if(String(x[outerSortState.field]) > String(y[outerSortState.field]))
        {
            return -1;
        }
        else
        {
            return 0;
        }
    }
}




// ------------------------------------------------------------------------------------------------------------------------------




//Смена ВНЕШНЕЙ сортировки
function outerSortChange(field)
{
    if(flag_one == true){
		flag_one = false;
		outerSortState.asc_desc = "asc";
		outerSortState.field = field;
	}else{
		//Если тоже поле - меняем только направление
		if(outerSortState.field == field)
		{
			if(outerSortState.asc_desc == "asc")
			{
				outerSortState.asc_desc = "desc";
			}
			else
			{
				outerSortState.asc_desc = "asc";
			}
		}
		else//Если поле другое - ставим это поле и направление asc
		{
			outerSortState.field = field;
			outerSortState.asc_desc = "asc";
		}
    }
	
    //Производим саму сортировку
    outerSort();
	
    //Обновляем отображние результата
	if(this_filter != ''){
		productsCountRequest(this_filter.replace('_blok', ''));
	}else{
		resultReview();
	}
}




// ------------------------------------------------------------------------------------------------------------------------------




//Добавление в корзину
function addToCart(aid)
{
    //1. По списку учетных объектов определяем, в где находится объект товара (Запрошенные/Аналоги)
    var AID_Object = Products.All[aid];
    
    //2. Получаем сам объект товара
    var Product = new Object;
    if(AID_Object.isRequired == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Required.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Required.ProductsTypes[i].manufacturer;
            var Article = Products.Required.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.Required.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isSearchName == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.SearchName.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.SearchName.ProductsTypes[i].manufacturer;
            var Article = Products.SearchName.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.SearchName.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isAnalogs == true)
    {
        //Ищем объект товара в списке аналогов
        for(var i=0; i < Products.Analogs.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Analogs.ProductsTypes[i].manufacturer;
            var Article = Products.Analogs.ProductsTypes[i].article;
            
            //Массив объектов товаров:
            var ProductsObjects = Products.Analogs.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isQuickAnalogs == true)
	{
		//Ищем объект товара в списке быстрых аналогов
        for(var i=0; i < Products.Quick_Analogs.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Quick_Analogs.ProductsTypes[i].manufacturer;
            var Article = Products.Quick_Analogs.ProductsTypes[i].article;
            
            //Массив объектов товаров:
            var ProductsObjects = Products.Quick_Analogs.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
	}else if(AID_Object.isPossibleReplacement == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.PossibleReplacement.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.PossibleReplacement.ProductsTypes[i].manufacturer;
            var Article = Products.PossibleReplacement.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.PossibleReplacement.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }else if(AID_Object.isSpare_Box == true)
    {
        //Ищем объект товара в списке запрошенных
        for(var i=0; i < Products.Spare_Box.ProductsTypes.length; i++)
        {
            var Manufacturer = Products.Spare_Box.ProductsTypes[i].manufacturer;
            var Article = Products.Spare_Box.ProductsTypes[i].article;
        
            //Массив объектов товаров:
            var ProductsObjects = Products.Spare_Box.Products.Manufacturers[Manufacturer][Article];
            for(var p=0; p < ProductsObjects.length; p++)
            {
                if(parseInt(ProductsObjects[p].aid) == parseInt(aid))
                {
					Product = Object.assign({}, ProductsObjects[p]);
                    break;
                }
            }
        }
    }
    
	//2.1 Добавляем пометку о количестве
	if(document.getElementById("count_need_"+aid)){
		var count_need = parseInt(document.getElementById("count_need_"+aid).value);
		Product['count_need'] = count_need;
	}else{
		Product['count_need'] = Product['min_order'];
	}

	//2.2 Заменяем синоним на имя производителя переданное поставщиком
	if(Product['manufacturer_transferred']){
		var manufacturer_tmp = Product['manufacturer'];
		Product['manufacturer'] = Product['manufacturer_transferred'];
		Product['manufacturer_transferred'] = manufacturer_tmp;
	}

    //3. Данные в корзину можно класть сразу целым перечнем - поэтому приводим к массиву
    var product_objects = new Array;
    product_objects.push(Product);
    
	//log('Объект добавленного в корзину товара:');
	//log(Product);
	
    //4. Добавляем его в корзину
    jQuery.ajax({
        type: "POST",
        async: false, //Запрос синхронный
        url: "/content/shop/order_process/ajax_add_to_basket.php",
        dataType: "json",//Тип возвращаемого значения
        data: "product_objects="+encodeURIComponent(JSON.stringify(product_objects)),
        success: function(answer)
        {
            if(answer.status == true)
            {
				updateCartInfo();//Обновление корзины снизу
				showAdded();//Показываем лэйбл снизу
            }
            else
            {
                if(answer.code == "already")
                {
                    alert("Товар уже был добавлен ранее");
                }
                else
                {
                    alert(answer.message);
                }
            }
        }
    });
}//~function addToCart(aid)
</script>