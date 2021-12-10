<?php
/*
	Бекенд - страница для подключения картинок к слайдеру
*/
defined('_ASTEXE_') or die('No access');

//////////////////////////////////////////////////////////////////////////////////////////////////////

// Проверяем была ли отправленна форма
if($_SERVER["REQUEST_METHOD"] === 'POST')
{
	//	POST - ЗАПРОС
	$action = $_POST['action'];
	$id = (int)$_POST['id'];
	
	switch($action)
	{
		case 'up' :
			// картинку вверх
			
			// Получаем orders текущей картинки
			$SQL = "SELECT `orders` FROM `slider_images` WHERE `id` = ?;";
			$query = $db_link->prepare($SQL);
			$query->execute( array($id) );
			$orders = $query->fetch();
			if( $orders != false )
			{
				$orders = (int) $orders['orders'];
			}
			
			if($orders > 1)
			{
				// Получаем orders картинки которая рядом
				$SQL = "SELECT `id` FROM `slider_images` WHERE `orders` = ? - 1;";
				$query = $db_link->prepare($SQL);
				$query->execute( array($orders) );
				$id_2 = $query->fetch();
				if( $id_2 != false )
				{
					$id_2 = (int) $id_2['id'];
				}
				$SQL = "UPDATE `slider_images` SET `orders` = ? - 1 WHERE `id` = ?;";
				$db_link->prepare($SQL)->execute( array($orders, $id) );
				
				$SQL = "UPDATE `slider_images` SET `orders` = $orders WHERE `id` = ?;";
				$db_link->prepare($SQL)->execute( array($orders, $id_2) );
			}
			?>
			<script>
			location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?success_message=Успешно.';
			</script>
			<?php
			exit('Успешно.');
		break;
		case 'do' :
			// картинку вниз
			$SQL = "SELECT `orders` FROM `slider_images` WHERE `id` = ?;";
			$query = $db_link->prepare($SQL);
			$query->execute( array($id) );
			$orders = $query->fetch();
			if( $orders != false )
			{
				$orders = (int) $orders['orders'];
			}
			
			$SQL = "SELECT MAX(`orders`) AS 'orders' FROM `slider_images`;";
			$query = $db_link->prepare($SQL);
			$query->execute();
			$orders_max = $query->fetch();
			if( $orders_max != false )
			{
				$orders_max = (int) $orders_max['orders'];
			}
			
			if($orders < $orders_max)
			{
				$SQL = "SELECT `id` FROM `slider_images` WHERE `orders` = ? + 1;";
				$query = $db_link->prepare($SQL);
				$query->execute( array($orders) );
				$id_2 = $query->fetch();
				if( $id_2 != false )
				{
					$id_2 = (int) $id_2['id'];
				}
				$SQL = "UPDATE `slider_images` SET `orders` = ? + 1 WHERE `id` = ?;";
				$db_link->prepare($SQL)->execute( array($orders, $id) );
				
				$SQL = "UPDATE `slider_images` SET `orders` = $orders WHERE `id` = ?;";
				$db_link->prepare($SQL)->execute( array($orders, $id_2) );
			}
			?>
			<script>
			location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?success_message=Успешно.';
			</script>
			<?php
			exit('Успешно.');
		break;
		case 'del' :
			// удалить картинку
			
			$SQL = "DELETE FROM `slider_images` WHERE `id` = ?;";
			$db_link->prepare($SQL)->execute( array($id) );
			
			// Пересчитаем orders
			$slider_images = array();
			$SQL = "SELECT * FROM `slider_images` ORDER BY `orders` ASC;";
			$query = $db_link->prepare($SQL);
			$query->execute();
			while($row = $query->fetch() )
			{
				$slider_images[] = $row;
			}
			if(!empty($slider_images))
			{
				$i = 1;
				foreach($slider_images as $images)
				{
					$SQL = "UPDATE `slider_images` SET `orders` = ? WHERE `id` = ?;";
					$db_link->prepare($SQL)->execute( array($i, $images['id']) );
					$i++;
				}
			}
			
			?>
			<script>
			location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?success_message=Успешно.';
			</script>
			<?php
			exit('Успешно.');
		break;
		case 'setings':
		// Сохраняем настройки слайдера
			
			$connected = 0;
			if($_POST['connected'] === 'on')
			{
				$connected = 1;
			}
			
			$cnt_img = (int) $_POST['cnt_img'];
			$cnt_img_next = (int) $_POST['cnt_img_next'];
			$time_next = ((int) $_POST['time_next']) * 1000;// Переводим секунды в милисекунды
			
			if($cnt_img == 0){ $cnt_img = 1; }
			if($cnt_img_next == 0){ $cnt_img_next = 1; }
			if($time_next == 0){ $time_next = 1000; }
			
			$SQL = "UPDATE `slider_setings` SET `cnt_img` = ?, `cnt_img_next` = ?, `time_next` = ?, `connected` = ?;";
			$db_link->prepare($SQL)->execute( array($cnt_img, $cnt_img_next, $time_next, $connected) );
			
			?>
			<script>
			location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?success_message=Успешно.';
			</script>
			<?php
			exit('Успешно.');
		break;
		case 'add':
			$href = $_POST['href'];
			$link = $_POST['link'];
			
			// Если переданный href не является файлом выводим ошибку
			if(!file_exists($href) && !file_exists($_SERVER["DOCUMENT_ROOT"].$href) || $href == '')
			{
				$error_message = urlencode("Переданный url картинки не коректен");
				?>
				<script>
				location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir; ?>/content/slider?error_message=<?php echo $error_message; ?>';
				</script>
				<?php
				exit('Переданный url картинки не коректен.');
			}
			
			if(empty($link))
			{
				$link = '';
			}
			
			// Получаем orders - позицию последней картинки
			$orders = 1;
			$SQL = "SELECT MAX(`orders`) AS `orders` FROM `slider_images`;";
			$query = $db_link->prepare($SQL);
			$query->execute();
			$orders = $query->fetch();
			if( $orders != false )
			{
				$orders = (int) $orders['orders'];
				$orders = $orders + 1;
			}
			
			// Записываем картинку в базу.
			$SQL = "INSERT INTO `slider_images` (`href`,`link`,`orders`) VALUES (?, ?, ?);";
			if($db_link->prepare($SQL)->execute( array($href, $link, $orders) ))
			{
				$success_message = urlencode("Картинка добавлена");
				?>
				<script>
				location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?success_message=<?php echo $success_message; ?>';
				</script>
				<?php
				exit('Картинка добавлена.');
			}
			else
			{
				$error_message = urlencode("Неудалось добавить картинку");
				?>
				<script>
				location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?error_message=<?php echo $error_message; ?>';
				</script>
				<?php
				exit('Неудалось добавить картинку.');
			}
		break;
	}
	
	$error_message = urlencode("Неизвестная операция");
	?>
	<script>
	location='<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/content/slider?error_message=<?php echo $error_message; ?>';
	</script>
	<?php
	exit('Неизвестная операция');
}
else
{
	//	GET - ЗАПРОС
	require_once($_SERVER["DOCUMENT_ROOT"]."/content/general/actions_alert.php");//Вывод сообщений о результатах выполнения действий

	// Получаем настройки слайдера
	$slider_setings = array();
	$SQL = "SELECT COUNT(*) FROM `slider_setings`;";
	$query = $db_link->prepare($SQL);
	$query->execute();
	if( $query->fetchColumn() > 0 )
	{
		$SQL = "SELECT * FROM `slider_setings`;";
		$query = $db_link->prepare($SQL);
		$query->execute();
		$slider_setings = $query->fetch();
	}

	// Получаем список картинок слайдера
	$slider_images = array();
	$SQL = "SELECT * FROM `slider_images` ORDER BY `orders` ASC;";
	$query = $db_link->prepare($SQL);
	$query->execute();
	while( $row = $query->fetch() )
	{
		$slider_images[] = $row;
	}
?>
	
	<!-- jQuery and jQuery UI (REQUIRED) -->
	<link rel="stylesheet" type="text/css" media="screen" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css">

	<!-- elFinder CSS (REQUIRED) -->
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/lib/elfinder/css/elfinder.min.css">
	<link rel="stylesheet" type="text/css" media="screen" href="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/lib/elfinder/css/theme.css">

	<!-- elFinder JS (REQUIRED) -->
	<script type="text/javascript" src="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/lib/elfinder/js/elfinder.min.js"></script>

	<!-- elFinder translation (OPTIONAL) -->
	<script type="text/javascript" src="<?php echo $DP_Config->domain_path.$DP_Config->backend_dir;?>/lib/elfinder/js/i18n/elfinder.ru.js"></script>

	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					Добавить картинку
				</div>
				<div class="panel-body">
					<div class="hidden">
					<?php
					require_once($_SERVER["DOCUMENT_ROOT"]."/".$DP_Config->backend_dir."/content/control/get_widget.php");//Скрипт для получения html-кода виджетов различных типов
					$widget_image = get_widget('image', 'href', 'Файл картинки не выбран', '');
					?>
					</div>
					<small>
					<strong>Информация</strong><br/>
					Предварительно необходимо загрузить картинку в раздел панели управления "Файлы", рекомендуем создать в менеджере файлов отдельную директорию для картинок слайдера. После на данной странице слайдера необходимо выбрать файл загруженной картинки, если необходим переход при клике на слайд то необходимо указать адрес страницы перенаправления.
					<br/><br/>
					Для красивого отображения слайдов, все добавленные картинки должны быть одинакового размера. Рекомендуемые размеры картинки составляют 1140px в ширину, высота может быть любой, главное одинаковой.
					<br/><br/>
					Файл загружаемой картинки должен быть в формате .jpg или .png<br/>
					Для корректной работы имя файла должно быть в нижнем регистре и состоять только из латинских букв или цифр без пробелов.
					</small>
					<br/>
					<br/>
					<form method="POST">
						<input type="hidden" name="action" value="add"/>
						<label>Файл картинки <small>(предварительно загруженной в раздел Файлы)</small>:</label><br/>
						<?=$widget_image;?>
						<br/><label>Важно: <small>(что бы выбрать картинку нажмите на нее 2 раза)</small></label>
						<br/>
						<br/>
						<label>URL ссылки, для перенаправления при клике на слайд <small>(не обязательно)</small>:</label><br/>
						<input type="text" name="link" class="form-control" placeholder="Например: /shop/katalog-tovarov"/>
						<br/>
						<input type="submit" class="btn btn-ar btn-primary" value="Добавить выбранную картинку в слайдер"/>
					</form>
				</div>
			</div>
		</div>
		
		
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					Настройки слайдера
				</div>
				<div class="panel-body">
					<form method="POST">
						<input type="hidden" name="action" value="setings"/>
						<label>Количество отображаемых картинок на странице:</label><br/>
						<input class="form-control" type="number" name="cnt_img" placeholder="Например: 1" value="<?=$slider_setings['cnt_img'];?>"/>
						<br/>
						<label>Количество картинок которые нужно пролистать при клике:</label><br/>
						<input class="form-control" type="number" name="cnt_img_next" placeholder="Например: 1" value="<?=$slider_setings['cnt_img_next'];?>"/>
						<br/>
						<label>Время через которое листать картинки (секунды):</label><br/>
						<input class="form-control" type="number" name="time_next" placeholder="Например: 5" value="<?=$slider_setings['time_next'] / 1000;?>"/>
						<br/>
						<br/>
						<input style="width:25px; height:25px;" id="connected" type="checkbox"<?=(!empty($slider_setings['connected']))?' checked':'';?> name="connected"/>
						<label style="cursor:pointer; position: relative; top: -7px;" for="connected">Отображать слайдер</label>
						<br/>
						<br/>
						<input type="submit" class="btn btn-ar btn-primary" value="Сохранить"/>
					</form>
				</div>
			</div>
		</div>
	</div>

	
	
	<div class="panel panel-default">
		<div class="panel-heading">
			Таблица картинок слайдера
		</div>
		<div class="panel-body">
			<?php
			if(empty($slider_images))
			{
				echo '<p>Картинок нет.</p>';
			}
			else
			{
			?>
			<form id="action_form" name="action_form" method="POST">
				<input id="action" type="hidden" name="action" value=""/>
				<input id="id" type="hidden" name="id" value=""/>
			</form>
			<table class="table" style="text-align:left;">
				<thead> 
					<tr> 
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th>&nbsp;</th>
						<th>URL картинки</th>
						<th>URL ссылки</th>
						<th>&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach($slider_images as $img){
				?>
					<tr>
						<td><?=$img['orders'];?></td>
						<td><a onClick="save_action('up', '<?=$img['id'];?>');" href="javascript:void(0);">Вверх</a></td>
						<td><a onClick="save_action('do', '<?=$img['id'];?>');" href="javascript:void(0);">Вниз</a></td>
						<td style="width:40%;"><?=$img['href'];?></td>
						<td style="width:40%;"><?=$img['link'];?></td>
						<td><a class="btn btn-ar btn-primary" onClick="save_action('del', '<?=$img['id'];?>');" href="javascript:void(0);">Удалить</a></td>
					</tr>
				<?php
					}
				?>
				</tbody>
			</table>
			<script>
				//Функция сохранения (отправка формы)
				function save_action(action, id){
					document.getElementById('action').value = action;
					document.getElementById('id').value = id;
					document.forms["action_form"].submit();
				}
			</script>
			<?php
			}
			?>
		</div>
		<div class="panel-footer">
			№ картинки не определяет ее порядок отображения, порядок регулируется кнопками "вверх", "вниз"
		</div>
	</div>

<?php
}// if($_SERVER["REQUEST_METHOD"] === 'POST') else
?>