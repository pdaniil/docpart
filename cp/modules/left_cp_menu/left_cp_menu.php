<?php
defined('_ASTEXE_') or die('No access');
/*
Скрипт модуля для левого меню панели управления.

Меню состоит из следующих частей:
- кнопка на главную страницу панели управления
- категории товаров каталога
- задачи панели управления
*/

//ДЛЯ ВЫВОДА КАТЕГОРИЙ КАТАЛОГА ТОВАРОВ
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/modules/left_cp_menu/catalogue_menu_helper.php");




//ДЛЯ ВЫВОДА ЗАДАЧ ПАНЕЛИ УПРАВЛЕНИЯ
//Определение функции проверки доступа к странице
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/control_helper.php");




?>
<ul class="nav" id="side-menu">
	<?php
	//1. Кнопка главной страницы панели управления.
	?>
	<li>
		<a href="/<?php echo $DP_Config->backend_dir; ?>"> <span class="nav-label">Панель управления</span></a>
	</li>
	
	
	
	
	<?php
	//ВЫВОД КАТЕГОРИЙ ТОВАРОВ - только для для страниц, связанных с каталогом: Редактирование каталога и Кладовщики
	if( isset($module_modes_map[(string)$DP_Content->url]) )
	{
		?>
		<li>
			<a href="javascript:void(0);"><span class="nav-label">Каталог товаров</span><span class="fa arrow"></span> </a>
			<?php
			$catalogue_tree_dump_PHP = json_decode($catalogue_tree_dump_JSON, true);
			
			printCatalogueBranch($catalogue_tree_dump_PHP);
			?>
		</li>
		<?php
	}
	
	



/*ВЫВОД ЗАДАЧ**/
//Для работы с пользователями - для определения доступа к страницам
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");

//Массив для блоков и страниц по блокам
$tabs = array();

//Получаем перечнь групп задач панели управления:
$control_groups_query = $db_link->prepare("SELECT * FROM `control_groups` ORDER BY `order` ASC;");
$control_groups_query->execute();
while( $group = $control_groups_query->fetch() )
{
    $tabs[(string)$group["id"]] = array("caption"=>$group["caption"], "items"=>array());
}


//Получаем перечень всех задач:
$control_panel_content_query = $db_link->prepare("SELECT * FROM `control_items` ORDER BY `order` ASC");
$control_panel_content_query->execute();
while( $item = $control_panel_content_query->fetch() )
{
	$item["url"] = str_replace( array("<backend>"), $DP_Config->backend_dir, $item["url"]);

	//Добавляем, только, если у пользователя есть доступ
	if( is_anable($item) )
	{
		array_push($tabs[(string)$item["items_group"]]["items"], $item);
	}
}



//Выводим перечень задач на страницу:
foreach($tabs as $key => $tab)
{
	//В данном блоке нет доступных страниц
	if(count($tab["items"]) == 0)
	{
		continue;
	}
    ?>
	<li>
		<a href="javascript:void(0);"><span class="nav-label"><?php echo $tab["caption"];?></span><span class="fa arrow"></span> </a>
		<ul class="nav nav-second-level">
	
       
            <?php
            for($i=0; $i<count($tab["items"]); $i++)
            {
    	        ?>
				<li>
					<a href="<?php echo $tab["items"][$i]["url"]; ?>">
						<?php
						if( !empty($tab["items"][$i]["fontawesome_class"]) )
						{
							?>
							<i class="<?php echo $tab["items"][$i]["fontawesome_class"]; ?>"></i> 
							<?php
						}
						?>
						<?php echo $tab["items"][$i]["caption"]; ?>
					</a>
				</li>
    	        <?php
            }//for()
            ?>
		</ul>
    </li>
    <?php
}//foreach()
?>
</ul>
