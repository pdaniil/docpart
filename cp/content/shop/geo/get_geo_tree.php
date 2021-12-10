<?php
/**
 * Скрипт формирует иерархическое описание созданных географических узлов
 * 
 * Скрипт содержит опеределения:
 * - отдельный метод addNodeToDump() для построения PHP-объекта иерархии узлов на основе класса DP_GeoNode
 * 
 * Скрипт производит:
 * - построение объекта иерархии $tree_dump_JSON - который полностью описывает дамп, выводимый в дерево
*/
defined('_ASTEXE_') or die('No access');



// --------------------------------- Start PHP - метод ---------------------------------
//Метод добавит категорию в массив - когда идет построение иерархии при загрузке страницы
function addNodeToDump($node, $candidate_data, $candidate_id)
{
    //Знаем уровень level
    if($node->parent == $candidate_id)
    {
        array_push($candidate_data, $node);
        return $candidate_data;//Возвращаем массив с добавленным узлом
    }
    else
    {
        for($i=0; $i < count($candidate_data); $i++)
        {
            if($candidate_data[$i]->count == 0)
            {
                continue;
            }
            $current_count = count($candidate_data[$i]->data);//Сколько элементов в массиве до рекурсивного вызова
            $candidate_data[$i]->data = addNodeToDump($node, $candidate_data[$i]->data, $candidate_data[$i]->id);
        }
        return $candidate_data;
    }
}
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ End PHP - метод ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~




// -------- Start ЗАГРУКА ТЕКУЩЕЙ КОНФИГУРАЦИИ ДЕРЕВА --------
//1. Создаем пустые переменные для получения текущей конфигурации
$root_node = new DP_GeoNode;//Корень дерева - объект иерархии - полностью описывает иерархическую структуру
$tree_dump_JSON = json_encode($root_node->data, true);//Строка с JSON-дампом конфигурации (Пустая по умолчанию)

//2. SELECT из таблицы узлов всех записей, упорядочненных по полю level
$all_nodes_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_geo` ORDER BY `level`, `order`;");
$all_nodes_query->execute();
if( $all_nodes_query->fetchColumn() > 0)
{
	$all_nodes_query = $db_link->prepare("SELECT * FROM `shop_geo` ORDER BY `level`, `order`;");
	$all_nodes_query->execute();
	
	
    //Обрабатываем результат запроса по циклу:
    while( $node_record = $all_nodes_query->fetch() )
    {
        //- создаем объект узла
        $current_node = new DP_GeoNode;
        $current_node->id = (integer)$node_record["id"];
        $current_node->count = (integer)$node_record['count'];
        $current_node->level = (integer)$node_record['level'];
        $current_node->value = $node_record["value"];
        $current_node->parent = (integer)$node_record['parent'];
        $current_node->from_server = 1;//Флаг - говорит о том, что данный узел взят с сервера (т.е. уже был создан ранее)
        
        //Добавляем объект узла в объект иерархии
        $root_node->data = addNodeToDump($current_node, $root_node->data, $root_node->id);
    }//~for($i)
    
    //3. Преобразовываем $root_node->data в JSON, добавляем знак $ к названиям некоторых полей и выдаем в javascript
    $tree_dump_JSON = json_encode($root_node->data, true);
    
    $sweep=array('"level"');
    $tree_dump_JSON = str_replace($sweep, '"$level"', $tree_dump_JSON);
    $sweep=array('"parent"');
    $tree_dump_JSON = str_replace($sweep, '"$parent"', $tree_dump_JSON);
    $sweep=array('"count"');
    $tree_dump_JSON = str_replace($sweep, '"$count"', $tree_dump_JSON);
    //var_dump($tree_dump_JSON);
}

// -------- End ЗАГРУКА ТЕКУЩЕЙ КОНФИГУРАЦИИ ДЕРЕВА --------
?>