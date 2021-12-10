<?php
/**
 * Скрипт для обработки различных операций над таблицей кроссов
*/
header('Content-Type: application/json;charset=utf-8;');
function prepareString($string)
{
	$sweep=array("#", "`", "\r\n", "\r", "\n", "\t", "'", '"');
	$string = str_replace($sweep,"", $string);
	
	return trim($string);
}

// формируем пагинацию
// $all 		= количество постов в категории (определяем количество постов в базе данных)
// $lim 		= количество постов, размещаемых на одной странице
// $prev 		= количество отображаемых ссылок до и после номера текущей страницы
// $curr_link 	= номер текущей страницы (получаем из URL)
// $curr_css 	= css-стиль для ссылки на "текущую (активную)" страницу
// $link 		= часть адреса, используемый для формирования линков на другие страницы
function pagination($all, $lim, $prev, $curr_link, $curr_css, $link)
{
    $html = '';
	// осуществляем проверку, чтобы выводимые первая и последняя страницы
    // не вышли за границы нумерации
    $first = $curr_link - $prev;
    if ($first < 1) $first = 1;
    $last = $curr_link + $prev;
    if ($last > ceil($all/$lim)) $last = ceil($all/$lim);
 
    // начало вывода нумерации
    // выводим первую страницу
    $y = 1;
    if ($first > 1) $html .= "<a onclick='go_to_page({$y})'>1</a>";
    // Если текущая страница далеко от 1-й (>10), то часть предыдущих страниц
    // скрываем троеточием
    // Если текущая страница имеет номер до 10, то выводим все номера
    // перед заданным диапазоном без скрытия
	// $prev
    $y = $first - 1;
    if ($first > $prev) {
        $html .= "<a onclick='go_to_page({$y})'>...</a>";
    } else {
        for($i = 2;$i < $first;$i++){
            $html .=  "<a onclick='go_to_page({$y})'>$i</a>";
        }
    }
    // отображаем заданный диапазон: текущая страница +-$prev
    for($i = $first;$i < $last + 1;$i++){
        // если выводится текущая страница, то ей назначается особый стиль css
        if($i == $curr_link) {
			$html .= '<a class="'.$curr_css.'">'. $i .'</a>';
        } else {
            $alink = "<a onclick='go_to_page(";
            if($i != 1) $alink .= "{$i}";
            $alink .= ")'>$i</a>";
            $html .= $alink;
        }
    }
    $y = $last + 1;
    // часть страниц скрываем троеточием
    if ($last < ceil($all / $lim) && ceil($all / $lim) - $last > 2) $html .=  "<a onclick='go_to_page({$y})'>...</a>";
    // выводим последнюю страницу
    $e = ceil($all / $lim);
    if ($last < ceil($all / $lim)) $html .=  "<a onclick='go_to_page({$e})'>$e</a>";
	
	return $html;
}





//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
	exit( json_encode($answer) );
}
$db_link->query("SET NAMES utf8;");

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$answer = array('status'=>false);
	exit(json_encode($answer));
}

$answer = array('status'=>false);
$request_object = json_decode($_POST['request_object'], true);

$sweep = array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", "\r\n", "\r", "\n", "\t");

switch($request_object['action'])
{
	case 'get_table_crosses':
		/*
		$kol - количество записей для вывода
		$art - с какой записи выводить
		$total - всего записей
		$page - текущая страница
		$str_pag - количество страниц для пагинации
		*/
		// Текущая страница
		$page = (int)$request_object['page'];
		if(empty($page))
		{
			$page = 1;
		}
		
		$kol = 50;  //количество записей для вывода
		$art = ($page * $kol) - $kol;//с какой записи выводить
		$article = strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$article = (string) str_replace($sweep, "", $article);
		$manufacturer = strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer'])), 'UTF-8'));
		
		$where = '';
		$binding_values = array();
		if(!empty($article))
		{
			if(!empty($manufacturer))
			{
				$where .= "WHERE (`article` = ? AND `manufacturer_article` = ?) OR (`analog` = ? AND `manufacturer_analog` = ?)";
				
				array_push($binding_values, $article);
				array_push($binding_values, $manufacturer);
				array_push($binding_values, $article);
				array_push($binding_values, $manufacturer);
			}
			else
			{
				$where .= "WHERE (`article` = ?) OR (`analog` = ?)";
				
				array_push($binding_values, $article);
				array_push($binding_values, $article);
			}
		}
		
		// Определяем количество записей в таблице
		$res = $db_link->prepare("SELECT COUNT(*) AS `count` FROM `shop_docpart_articles_analogs_list` $where");
		$res->execute($binding_values);
		$row = $res->fetch();
		$total = (int)$row['count']; // всего записей	
		
		// Количество страниц для пагинации
		$str_pag = ceil($total / $kol);
		
		
		$sql = "SELECT * FROM `shop_docpart_articles_analogs_list` $where ORDER BY `article` LIMIT $art, $kol;";		
		$query = $db_link->prepare($sql);
		$query->execute($binding_values);
		
		$html = '';
		while($rov = $query->fetch() )
		{
			$html .= '
			<tr id="show_line_'. $rov['id'] .'">
				<td>'. $rov['article'] .'</td>
				<td>'. $rov['manufacturer_article'] .'</td>
				<td>'. $rov['analog'] .'</td>
				<td>'. $rov['manufacturer_analog'] .'</td>
				<td>
					<a onclick="crosses_edit('. $rov['id'] .');" class="btn btn-sm btn-primary" title="Редактировать"><i class="fa fa-terminal"></i></a>
					<a onclick="crosses_del('. $rov['id'] .');" class="btn btn-sm btn-primary" title="Удалить"><i class="fa fa-times"></i></a>
				</td>
			</tr>
			
			<tr class="hidden" id="edit_line_'. $rov['id'] .'">
				<td>
					<input class="form-control" type="text" id="article_edit_'. $rov['id'] .'" value="'. $rov['article'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="manufacturer_article_edit_'. $rov['id'] .'" value="'. $rov['manufacturer_article'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="analog_edit_'. $rov['id'] .'" value="'. $rov['analog'] .'"/>
				</td>
				<td>
					<input class="form-control" type="text" id="manufacturer_analog_edit_'. $rov['id'] .'" value="'. $rov['manufacturer_analog'] .'"/>
				</td>
				<td>
					<a onclick="crosses_edit_save('. $rov['id'] .');" class="btn btn-sm btn-primary"><i class="fa fa-floppy-o"></i> Сохранить</a>
					<a onclick="crosses_edit_otmena('. $rov['id'] .');" class="btn btn-sm btn-primary"><i class="fa fa-chevron-left"></i> Отмена</a>
				</td>
			</tr>
			';
		}
		
		if($html != ''){
			$html = '<table class="table table_crosses"><tr><th>Артикул</th><th>Производитель</th><th>Аналог</th><th>Производитель аналога</th><th>Действие</th></tr>'. $html .'</table>';
			
			// формируем пагинацию
			$pagination = pagination($total, $kol, 3, $page, 'pagination_active', '');
			if($pagination != '<a class="pagination_active">1</a>'){
				$pagination = '<div class="panel-footer"><div class="pagination_box">'.$pagination.'</div></div>';
			}else{
				$pagination = '';
			}
			
			$html = '<div class="panel-body">'.$html.'</div>'.$pagination;
		}else{
			$html = '<div class="panel-body">Ничего не найдено</div>';
		}
		
		exit($html);
		break;
	case 'add_crosses':
		$article 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$manufacturer_article 	= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer_article'])), 'UTF-8'));
		$analog 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['analog'])), 'UTF-8'));
		$manufacturer_analog 	= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer_analog'])), 'UTF-8'));
		
		$article 				= str_replace($sweep, "", $article);
		$manufacturer_article 	= prepareString($manufacturer_article);
		$analog 				= str_replace($sweep, "", $analog);
		$manufacturer_analog 	= prepareString($manufacturer_analog);
		
		if(!empty($article) && !empty($manufacturer_article) && !empty($analog) && !empty($manufacturer_analog))
		{
			$sql = "INSERT INTO `shop_docpart_articles_analogs_list` (`article`,`manufacturer_article`,`analog`,`manufacturer_analog`) VALUES (?,?,?,?);";

			if($db_link->prepare($sql)->execute( array($article,$manufacturer_article,$analog,$manufacturer_analog) ))
			{
				$answer['status'] = true;
			}
		}
		break;
	case 'save_crosses':
		$id = (int)$request_object['id'];
		
		$article 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$manufacturer_article 	= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer_article'])), 'UTF-8'));
		$analog 				= strip_tags(mb_strtoupper(trim(urldecode($request_object['analog'])), 'UTF-8'));
		$manufacturer_analog 	= strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer_analog'])), 'UTF-8'));
		
		$article 				= str_replace($sweep, "", $article);
		$manufacturer_article 	= prepareString($manufacturer_article);
		$analog 				= str_replace($sweep, "", $analog);
		$manufacturer_analog 	= prepareString($manufacturer_analog);

		if(!empty($article) && !empty($manufacturer_article) && !empty($analog) && !empty($manufacturer_analog))
		{
			$sql = "UPDATE `shop_docpart_articles_analogs_list` SET `article` = ?, `manufacturer_article` = ?, `analog` = ?, `manufacturer_analog` = ? WHERE `id` = ?;";

			if($db_link->prepare($sql)->execute( array($article, $manufacturer_article, $analog, $manufacturer_analog, $id) ))
			{
				$answer['status'] = true;
			}
		}
		break;
	case 'del_crosses':
		$id = (int)$request_object['id'];
		$sql = "DELETE FROM `shop_docpart_articles_analogs_list` WHERE `id` = ?;";
		if($db_link->prepare($sql)->execute( array($id) ))
		{
			$answer['status'] = true;
		}
		break;
	case 'del_search_crosses':
		$article = strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$article = (string) str_replace($sweep, "", $article);
		$manufacturer = strip_tags(mb_strtoupper(trim(urldecode($request_object['manufacturer'])), 'UTF-8'));
		
		$where = '';
		$binding_values = array();
		if(!empty($article))
		{
			if(!empty($manufacturer))
			{
				$where .= "WHERE (`article` = ? AND `manufacturer_article` = ?) OR (`analog` = ? AND `manufacturer_analog` = ?)";
				
				array_push($binding_values, $article);
				array_push($binding_values, $manufacturer);
				array_push($binding_values, $article);
				array_push($binding_values, $manufacturer);
			}
			else
			{
				$where .= "WHERE (`article` = ?) OR (`analog` = ?)";
				
				array_push($binding_values, $article);
				array_push($binding_values, $article);
			}
		}
		
		$sql = "DELETE FROM `shop_docpart_articles_analogs_list` $where LIMIT 10000;";
		do{
			$query = $db_link->prepare($sql);
			$query->execute($binding_values);
		}while($query->fetchColumn() > 0);
		
		$answer['status'] = true;
		
		break;
	case 'get_search_manufacturer':
		$article = strip_tags(mb_strtoupper(trim(urldecode($request_object['article'])), 'UTF-8'));
		$article = str_replace($sweep, "", $article);
		
		$list_manufacturer = array();
		
		$sql = "SELECT `manufacturer_article` FROM `shop_docpart_articles_analogs_list` WHERE `article` = ?;";
		$query = $db_link->prepare($sql);
		$query->execute( array($article) );
		while($rov = $query->fetch() ){
			if(array_search($rov['manufacturer_article'], $list_manufacturer) === false){
				$list_manufacturer[] = $rov['manufacturer_article'];
			}
		}
		
		$sql = "SELECT `manufacturer_analog` FROM `shop_docpart_articles_analogs_list` WHERE `analog` = ?;";
		$query = $db_link->prepare($sql);
		$query->execute( array($article) );
		while($rov = $query->fetch() ){
			if(array_search($rov['manufacturer_analog'], $list_manufacturer) === false){
				$list_manufacturer[] = $rov['manufacturer_analog'];
			}
		}
		
		sort($list_manufacturer);
		
		$answer['status'] = true;
		$answer['list_manufacturer'] = json_encode($list_manufacturer);
		
		break;
}
exit(json_encode($answer));
?>