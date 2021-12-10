<?php
/**
 * Страничный скрипт для вывода корневой страницы новостей
 * 
 * Скрипт значет об ID страницы новостей по $DP_Content->id
 * 
*/
defined('_ASTEXE_') or die('No access');


//Получаем количество новостей
$stmt = $db_link->prepare('SELECT COUNT(*) FROM `content` WHERE `parent` = :parent ORDER BY `id` DESC;');
$stmt->bindValue(':parent', $DP_Content->id);
$stmt->execute();
$news_num_rows = $stmt->fetchColumn();

//Получаем новости из БД
$stmt = $db_link->prepare('SELECT `value`, `time_created`, `description_tag`, `url` FROM `content` WHERE `parent` = :parent ORDER BY `id` DESC;');
$stmt->bindValue(':parent', $DP_Content->id);
$stmt->execute();



//ОБЕСПЕЧИВАЕМ ПОСТРАНИЧНЫЙ ВЫВОД:
//---------------------------------------------------------------------------------------------->
//Определяем количество страниц для вывода:
$p = $DP_Config->list_page_limit;//Штук на страницу
$count_pages = (int)( $news_num_rows / $p);//Количество страниц
if($news_num_rows % $p)//Если остались еще элементы
{
	$count_pages++;
}
//Определяем, с какой страницы начать вывод:
$s_page = 0;
if(!empty($_GET['s_page']))
{
    $s_page = $_GET['s_page'];
}
//----------------------------------------------------------------------------------------------|


$news_counter_total = 0;
$news_counter_printed = 0;
while($news = $stmt->fetch(PDO::FETCH_ASSOC) )
{
    $news_counter_total++;
    
    //Пропускаем нужное количество блоков в соответствии с номером требуемой страницы
    if($news_counter_total <= $s_page*$p)
    {
        continue;
    }
    
    
    ?>
    <div class="news_block">
        <h4><a href="<?php echo "/".$news["url"]; ?>"><?php echo $news["value"]; ?></a></h4>
        <p><?php echo $news["description_tag"]; ?></p>
        <p><?php echo date("d.m.Y", $news["time_created"]); ?></p>
    </div>
    <?php
    $news_counter_printed++;
    
    if($news_counter_printed >= $p)
    {
        break;
    }
}
?>
<div align="center" style="padding:10px 0">
<?php
    // Start ВЫВОДИМ ВИДЖЕТЫ ДЛЯ ВЫБОРА СТРАНИЦ СПИСКА
	if($news_num_rows!=0)echo "Страницы: ";
	//Выводим номера страниц для перелистывания
	for($i=0; $i < $count_pages; $i++)
	{
	    if($i == $s_page)
	    {
	        echo "<font class=\"current_page\">$i</font> ";//Текущая страница
	    }
	    else
	    {
	        echo "<a href=\"?s_page=$i\">$i</a> ";
	    }
	}
	if($news_num_rows!=0)echo "<br><br>";
	// End ВЫВОДИМ ВИДЖЕТЫ ДЛЯ ВЫБОРА СТРАНИЦ СПИСКА
?>
</div>