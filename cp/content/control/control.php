<?php
/**
 * Главная страница панели управления
*/
defined('_ASTEXE_') or die('No access');


//Определение функции проверки доступа к странице
require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/control_helper.php");

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
$control_panel_content_query = $db_link->prepare("SELECT * FROM `control_items` ORDER BY `order` ASC;");
$control_panel_content_query->execute();
while( $item = $control_panel_content_query->fetch() )
{
	$item["url"] = str_replace( array("<backend>"), $DP_Config->backend_dir, $item["url"]);

	//Добавляем, только, если у пользователя есть доступ
	if( is_anable($item) || $item["show_anyway"] == 1 )
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
	
	if($DP_Template->name == "bootstrap_admin")
	{
		?>
		<div class="col-lg-12">
			<div class="hpanel">
				<div class="panel-heading hbuilt">
					<?php echo $tab["caption"];?>
				</div>
				<div class="panel-body">
					<?php
					for($i=0; $i<count($tab["items"]); $i++)
					{
						//Функция подключена в скрипте шаблона панели управления
						print_backend_button($tab["items"][$i]);
					}//for()
					?>
				</div>
			</div>
		</div>
		<?php
	}
	else if($DP_Template->name == "startbootstrap")
	{
		?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<?php echo $tab["caption"];?>
			</div>
			<div class="panel-body">
				<?php
				for($i=0; $i<count($tab["items"]); $i++)
				{
					//Изображение
					$img = "/".$DP_Config->backend_dir."/templates/".$DP_Template->name."/images/".$tab["items"][$i]["img"];
					if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/".$img))
					{
						$img = "content/control/images/window.png";
					}
					?>
					<a class="panel_a" href="<?php echo $tab["items"][$i]["url"]; ?>">
						<div class="panel_a_img" style="background: url('<?php echo $img; ?>') 0 0 no-repeat;"></div>
						<div class="panel_a_caption"><?php echo $tab["items"][$i]["caption"]; ?></div>
					</a>
					<?php
				}//for()
				?>
			</div>
		</div>
		<?php
	}
}//foreach()
?>