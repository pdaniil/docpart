<?php
/**
 * Скрипт для функции - подключается через require_once
*/
defined('_ASTEXE_') or die('No access');


//Рекурсивный метод получения HTML-кода пунктов меню
/*
Метод принимает массив пунктов меню одного уровня.
Сам уровень - это по сути список <ul></ul>, а все его пункты - это содержимое внутри списка, т.е. <li></li>
*/
function getHtmlOfMenu($menu_php, $level = null)
{
    global $isFrontMode;//Режим работы
    global $db_link;
    global $DP_Config;
    global $DP_Content;//Объект материала от ядра
    
    $level++; 
    $html = "";//Начинаем формировать строку с html списка пунктов данного уровня
    
    //Цикл формирования пунктов данного уровня
    for($i=0; $i < count($menu_php); $i++)
    {
        //ИТЕРАЦИЯ ЦИКЛА - ФОРМИРОВАНИЕ ПУНКТА, Т.Е. <li>
        
        //1. Получаем атрибут href:
        $href = "";
        if($menu_php[$i]["link_mode"] == "url")//Содержимое задано напрямую
        {
            $href = $menu_php[$i]["href"];
        }
        else if($menu_php[$i]["link_mode"] == "content")//Содержимое необходимо получить из таблицы content (т.е. это ссылка на материал)
        {
			$stmt = $db_link->prepare('SELECT * FROM `content` WHERE `id` = :id;');
			$stmt->bindValue(':id', $menu_php[$i]["content_id"]);
			$stmt->execute();
			$content_record = $stmt->fetch(PDO::FETCH_ASSOC);
			
            if($content_record != false)
            {
                $href = "/".$content_record["url"];
                if(!$isFrontMode)//Если работаем в режиме бэкэнда - в ссылки добавляем каталог бэкэнда
                {
                    $href = "/".$DP_Config->backend_dir.$href;
                }
            }
            else//Материал отсутствует - возможно он был удален
            {
                $href = "";
            }
        }
        
        //2. Добавляем CLASS ACTIVE для li
        //Адрес ссылки
        $href_value = str_replace(array("index.php", $DP_Config->domain_path, $DP_Config->backend_dir), "", $href);//Подгоняем ссылку под маршрут
        if( strlen($href_value) > 1 )
		{
			if($href_value[0] == "/"  )//Если опять в начале оказался знак "/" - убираем
			{
				$href_value = substr_replace($href_value, "", 0, 1);
				if($href_value[0] == "/")//Если опять в начале оказался знак "/" - убираем
				{
					$href_value = substr_replace($href_value, "", 0, 1);
				}
			}
		}
        if($href_value == $DP_Content->url || ($href_value == "/" && $DP_Content->main_flag))
        {
            if($menu_php[$i]["class_li"] != "") $menu_php[$i]["class_li"] .= " ";
            $menu_php[$i]["class_li"] .= "active";
        }
        
        if($menu_php[$i]['$count'] > 0)
        {
			$href = 'javascript:void(0);" data-toggle="dropdown" aria-expanded="false';
			$menu_php[$i]["class_a"] .= ' dropdown-toggle';
			$menu_php[$i]["class_li"] .= ' dropdown';
			$menu_php[$i]['class_ul'] .= ' dropdown-menu animated-2x animated fadeIn';
			
			if($level > 1){
				$menu_php[$i]["class_li"] .= ' dropdown-submenu';
			}
		}
        
        //3. Атрибуты li
        $class_li = "";
        if($menu_php[$i]["class_li"] != "")
        {
            $class_li = " class=\"".$menu_php[$i]["class_li"]."\"";
        }
        
        $id_li = "";
        if($menu_php[$i]["id_li"] != "")
        {
            $id_li = " id=\"".$menu_php[$i]["id_li"]."\"";
        }
        $html .= "<li".$class_li.$id_li.">";//Начало пункта li
        
        //4. Формируем ссылку (тег a)
        $class_a = "";
        $id_a = "";
        $a_innerhtml = "";
        $target = "";
        $onclick = "";
        
        //target
        if($menu_php[$i]["target"] != "")
        {
            $target = " target= \"".$menu_php[$i]["target"]."\"";
        }
        //onclick
        if($menu_php[$i]["onclick"] != "")
        {
            $onclick = " onclick= \"".$menu_php[$i]["onclick"]."\"";
        }
        //class_a
        if($menu_php[$i]["class_a"] != "")
        {
            $class_a = " class= \"".$menu_php[$i]["class_a"]."\"";
        }
        //id_a
        if($menu_php[$i]["id_a"] != "")
        {
            $id_a = " id= \"".$menu_php[$i]["id_a"]."\"";
        }
        
        $html .= "<a href=\"$href\"".$class_a.$id_a.$target.$onclick.">".$menu_php[$i]["a_innerhtml"]."</a>";
        
        //Если пункт содержит вложенные пункты
        if($menu_php[$i]['$count'] > 0)
        {
            $class_ul = "";
            if($menu_php[$i]['class_ul'] != "")
            {
                $class_ul = " class=\"".$menu_php[$i]['class_ul']."\"";
            }
            
            $id_ul = "";
            if($menu_php[$i]['id_ul'] != "")
            {
                $id_ul = " id=\"".$menu_php[$i]['id_ul']."\"";
            }
            
            $html .= "<ul".$class_ul.$id_ul.">";
            $html .= getHtmlOfMenu($menu_php[$i]["data"], $level);
            $html .= "</ul>";
        }
        
        $html .= "</li>";
    }//~for($i)
    
    return $html;
}//~function getHtmlOfMenu($menu_php)
?>