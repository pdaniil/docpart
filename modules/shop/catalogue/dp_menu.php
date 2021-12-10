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

$all_cnt_links = count($catalogue_tree);
$link_cnt_level_all = 0;

$tabs_header = '<div class="vertical-tab-list">
				<ul class="nav">';
getHtml_tabs_header($catalogue_tree);
$tabs_header .= '</ul>
			    </div>';

$tabs_content = '<div class="tab-content" style="position: relative;">'.$tabs_content.'</div>';

echo $tabs_header;
echo $tabs_content;

function get_cnt_links($catalogue_tree)
{
	$cnt = 0;
	for($i = 0; $i < count($catalogue_tree); $i++){
		$cnt++;
		$category = $catalogue_tree[$i];
		$cnt += get_cnt_links($category["data"]);
	}
	return $cnt;
}

function getHtml_tabs_header($catalogue_tree)
{
	global $tabs_header;
	
    //Цикл формирования пунктов 1 уровня
	$cnt_level = count($catalogue_tree);
	for($i = 0; $i < $cnt_level; $i++){
		
		$category = $catalogue_tree[$i];
		
		if($category['published_flag'] == '0'){
			continue;
		}
		
        $id = $category["id"];
		$value = trim($category["value"]);
        $href = "/".$category["url"];
		
		if(!empty($category["image"])){
			$image = "/content/files/images/catalogue_images/".$category["image"];
			if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/catalogue_images/".$category["image"])){
			$image = "/content/files/images/no_image.png";
			}
		}else{
			$image = "/content/files/images/no_image.png";
		}
        
        
		
		
		if($i == 0){
			$class = 'class="active"';
		}else{
			$class = '';
		}
		
		if($category['$count'] > 0 )
        {
            $class_a = 'count_ch';
            $href = 'href="#category_'.$id.'" data-toggle="tab" data-hover="tab"';
        }else{
			$class_a = '';
			$href = 'href="'.$href.'"';
		}
		
        $tabs_header .= '<li '.$class.'><a class="'.$class_a.'" '.$href.'>
		<table>
			<tr>
				<td style="padding-right: 10px;"><img style="max-width: 30px; max-height: 30px; width: auto; height: auto;" src="'.$image.'"/></td>
				<td style="width: 100%;">'.$value.'</td>
			</tr>
		</table>
		</a></li>';
		
		//Формирование блока вложенных подкатегорий
		getHtml_tabs_content($category["data"], $i, $id, $image);
    }
}

function getHtml_tabs_content($catalogue_tree, $i, $id, $image)
{
	global $tabs_content;
	
	if($i == 0){
		$class = 'active';
	}else{
		$class = '';
	}
	
	$tabs_content .= '<div style="overflow: hidden;" class="tab-pane '.$class.'" id="category_'.$id.'">';
    $tabs_content .= getHtmlLink($catalogue_tree);
	/* $tabs_content .= '<div style="background: url('.$image.') no-repeat; background-position: top right; background-size: contain; opacity: 0.5; position: absolute; top: 0; bottom: 0; left: 50%; right: 0; z-index: 1;"></div>'; */
	$tabs_content .= '<div style="background-position: top right; background-size: contain; opacity: 0.5; position: absolute; top: 0; bottom: 0; left: 50%; right: 0; z-index: 1;"></div>';
	$tabs_content .= '</div>';
}

function getHtmlLink($catalogue_tree, $level = 0)
{
	global $all_cnt_links, $link_cnt_level_all;
	
	$html = "";
	
	// Количество ссылок в текущем табе
	if($level === 0){
		$link_cnt_level_all = 0;
		$link_cnt_level = get_cnt_links($catalogue_tree);
		if($link_cnt_level < 40 && $link_cnt_level > 15){
			$show_cnt_links = (int) abs(ceil($link_cnt_level / 2));
		}else if($link_cnt_level > 40){
			$show_cnt_links = (int) abs(ceil($link_cnt_level / 3));
		}else{
			$show_cnt_links = $link_cnt_level;
		}
		
		$html .= '<div class="column_box_line">';
	}
	
	$level++;
    
    //Цикл формирования пунктов данного уровня
	$cnt_level = count($catalogue_tree);
	for($i = 0; $i < $cnt_level; $i++){
    
		$category = $catalogue_tree[$i];
		
		if( ($level === 1) ){
			$html .= '<div class="box_line">';
			$class = 'class="one_line"';
		}else if( ($level === 2) ){
			$class = 'class="two_line"';
		}else{
			$class = 'class="two_line" style="margin-left:'.(15*($level-1)).'px;"';
		}

        //1. Получаем атрибут href:
        $href = "/".$category["url"];
        
        $html .= '<a '.$class.' href="'.$href.'">'.trim($category["value"])."</a>";
        
        //Если пункт содержит вложенные пункты
        if($category['$count'] > 0 )
        {
            $html .= getHtmlLink($category["data"], $level);
        }
		
		if( ($level === 1) ){
			$html .= '</div>';
		}
		
		$link_cnt_level_all++;
		if($link_cnt_level_all >  $show_cnt_links){
			if( ($level === 1) ){
				$link_cnt_level_all = 0;
				$html .= '</div>';
				$html .= '<div class="column_box_line">';
			}
		}
    }
	
	if( ($level === 1) ){
		$html .= '</div>';
	}
	
    return $html;
}


?>
<script>
//После загрузки страницы
$(document).ready(function() {
	//Добавляем блок затемняющего фона
	$('header').after($('<div>', {class: 'fon-catalog'}));
	//Затемнение фона при раскрытии меню каталога
	$('.fon-catalog').on('click', function (e) {
	  $('#dp_menu').css('display', 'none');
	  $(this).css('display', 'none');
	  return false
	});
	//Отображение вложенных пунктов категории на которую наведен курсор
	$('[data-hover="tab"]').mouseenter(function (e) {
	  $(this).tab('show');
	});
});
//Раскрытие меню каталога
function showCatalogMenu(){
	if(document.getElementById('dp_menu')){
		if($('#dp_menu').css('display') == 'block'){
			  $('#dp_menu').css('display', 'none');
			  $('.fon-catalog').css('display', 'none');
		  }else{
			  $('#dp_menu').css('display', 'block');
			  $('.fon-catalog').css('display', 'block');
		  }
		return false;
	}
}
</script>