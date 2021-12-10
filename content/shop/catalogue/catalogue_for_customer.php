<?php
/**
 * Страничный скрипт для вывода каталога для покупателей
*/
defined('_ASTEXE_') or die('No access');


$is_products_mode = true;//Флаг - страница работает в режиме отображения товаров
$category_block_type = 1;//Тип блоков категорий - для вывода покупателю (используется в /content/shop/catalogue/printCategories.php)



if($category_id > 0)
{
    //Есть параметр category_id - нужно понять, является ли он конечным (count = 0)
	$category_record_query = $db_link->prepare('SELECT * FROM `shop_catalogue_categories` WHERE `id` = :id;');
	$category_record_query->bindValue(':id', $category_id);
	$category_record_query->execute();
    $category_record = $category_record_query->fetch();
    
    if($category_record["count"] == 0)//Подкатегорий нет - значит отображаем товары
    {
        $is_products_mode = true;
        $product_block_type = 1;//Параметр для скрипта /content/shop/catalogue/printProducts.php - знать, как выводить товары
    }
    else
    {
        $is_products_mode = false;
    }
}
else
{
    $is_products_mode = false;//Будем выводить категории (причем корневые)
}





if($is_products_mode == false)
{
    require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printCategories.php");
}
else
{
	//В зависимости от выбранного способа отображения каталога
	if( $DP_Config->catalogue_html_way == "async" )
	{
		//Асинхронная загрузка товаров на страницу
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printProducts.php");
	}
	else
	{
		//По умолчанию - пагинация
		require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/printProducts_2.php");
	}
}
?>