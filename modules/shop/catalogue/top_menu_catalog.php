<?php
defined('_ASTEXE_') or die('No access');


require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS


//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    exit("No DB connect");
}
$db_link->query("SET NAMES utf8;");

// Дерево только отображаемых для клиента категорий
$where_published_flag = true;

require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/dp_category_record.php");//Определение класса записи категории
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/catalogue/get_catalogue_tree.php");//Получение объекта иерархии существующих категорий для вывода в дерево-webix

$catalogue_tree = json_decode($catalogue_tree_dump_JSON, true);

if(!empty($catalogue_tree)){
	echo '
	<ul class="nav navbar-nav nav_cat">
		<li>
			<a href="javascript:void(0);" class="dropdown-cat-btn dropdown-toggle" data-toggle="dropdown">Каталог<span class="hidden-sm"> товаров</span></a>
			<ul class="dropdown-menu keep_open dropdown-menu-left fadeIn">
			'. getHtmlOfTopMenuCatalogue($catalogue_tree) .'
			</ul>
		</li>
	</ul>';
}

function getHtmlOfTopMenuCatalogue($catalogue_tree, $level = 0)
{
	global $DP_Template;
	
	$level++;
    $html = "";
	if($DP_Template->data_value->cnt_category_after_hidden === null){
		$cnt_category_after_hidden = 15;
	}else{
		$cnt_category_after_hidden = (int) $DP_Template->data_value->cnt_category_after_hidden;
	}
    
	
    //Цикл формирования пунктов данного уровня
	$cnt_level = count($catalogue_tree);
	for($i = 0; $i < $cnt_level; $i++){
    
		$category = $catalogue_tree[$i];
	
		if( ($level === 1) && ($i === $cnt_category_after_hidden) && ($cnt_level > $cnt_category_after_hidden) ){
			$html .= '
			<li class="dropdown-submenu">
				<div id="top-menu-catalogue-accordion">
					<div id="top-menu-catalogue-collapseTwo" class="panel-collapse collapse">
						<ul class="open_t dropdown-submenu">';
		}

        //1. Получаем атрибут href:
        $href = "/".$category["url"];
        
        $class_li = '';
		if($category['$count'] > 0){
			$class_li = ' class="dropdown-submenu"';
		}
        $html .= "<li".$class_li.">";//Начало пункта li

		$class_a = '';
		if($category['$count'] > 0){
			 $class_a = ' class="has_children"';
		}
        $html .= '<a'.$class_a.' href="'.$href.'">'.trim($category["value"])."</a>";
        
        //Если пункт содержит вложенные пункты
        if($category['$count'] > 0)
        {
			if(($level % 2) !== 0 && $level > 2){
				$html .= '<ul class="dropdown-menu dropdown-menu-left">';
			}else{
				$html .= '<ul class="dropdown-menu">';
			}
            
            $html .= getHtmlOfTopMenuCatalogue($category["data"], $level);
            $html .= "</ul>";
        }

        $html .= "</li>";
		
		if( ($level === 1) && ($i >= $cnt_category_after_hidden) && ($cnt_level === ($i+1)) ){
			$html .= '
						</ul>
					</div>
					<div class="catalogue-collapse-link-box">
						<a data-toggle="collapse" data-parent="#top-menu-catalogue-accordion" href="#top-menu-catalogue-collapseTwo" class="collapsed">
							Показать все
						</a>
					</div>
				</div>
			</li>';
		}
    }

    return $html;
}
?>