<?php
//Серверный скрипт для получения текстовой информации по онлайн-кассе. Информацию выдаем в текстовом виде, поэтому, без json_encode

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	exit( "No DB connect" );
}
$db_link->query("SET NAMES utf8;");


//Для работы с пользователями
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");


//Проверяем доступ в панель управления
if( ! DP_User::isAdmin())
{
	exit("Нет доступа");
}


//Здесь можно проверить, является ли данный пользователь продавцом какого-нибудь магазина, к которому относится данный ККТ. Но, это уже нестандартный вариант работы, поэтому, настраивается персонально под каждый сайт.
//...



if( (int)$_POST["kkt_device_id"] <= 0 )
{
	exit("Не задан ID интересуемого ККТ");
}


//Получаем детальную информацию по кассе.
$kkt_device_query = $db_link->prepare("SELECT *, (SELECT `description` FROM `shop_kkt_interfaces_types` WHERE `handler` = `shop_kkt_devices`.`handler`) AS `interface_description` FROM `shop_kkt_devices` WHERE `id` = ?;");
$kkt_device_query->execute( array($_POST["kkt_device_id"]) );
$kkt_device_record = $kkt_device_query->fetch();


if( $kkt_device_record == false )
{
	exit("ККТ не найден");
}

?>
<div class="col-md-12">
	<div class="table-responsive">
		<table class="table table-striped">
			<tbody>
				<tr>
					<td>
						<span class="text-success font-bold">ID</span>
					</td>
					<td><?php echo $kkt_device_record["id"]; ?></td>
				</tr>
				<tr>
					<td>
						<span class="text-success font-bold">Наименование</span>
					</td>
					<td><?php echo $kkt_device_record["name"]; ?></td>
				</tr>
				<tr>
					<td>
						<span class="text-success font-bold">Интерфейс реальной ККТ</span>
					</td>
					<td>
						<?php
						if( $kkt_device_record["interface_description"] == "" )
						{
							?>
							Реальная касса не подключена. Чеки с сайта не отправляются
							<?php
						}
						else
						{
							echo $kkt_device_record["interface_description"];
						}
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	
	
	<?php
	//Получаем детальную информацию о подключении ККТ к реальной кассе
	if( $kkt_device_record["handler"] != "" )
	{
		//Получаем инфо по данному типу интерфейса
		$kkt_interface_type_query = $db_link->prepare("SELECT * FROM `shop_kkt_interfaces_types` WHERE `handler` = ?;");
		$kkt_interface_type_query->execute( array($kkt_device_record["handler"]) );
		$kkt_interface_type_record = $kkt_interface_type_query->fetch();
		
		if( $kkt_interface_type_record == false )
		{
			?>
			<div class="col-lg-12">
				<div class="alert alert-danger fade in">
					<strong>Ошибка!</strong> Не найдена запись типа интерфейса. Обратитесь к разработчику
				</div>
			</div>
			<?php
		}
		else
		{
			?>
			<h5>Параметры подключения к реальной кассе</h5>
			<div class="table-responsive">
				<table class="table table-striped">
					<tbody>
						<?php
						$connection_options = json_decode($kkt_interface_type_record["connection_options"], true);
						
						$connection_values = json_decode($kkt_device_record["handler_options_values"], true);
						
						foreach($connection_options AS $option)
						{
							?>
							<tr>
								<td>
									<span class="text-success font-bold"><?php echo $option["caption"]; ?></span>
								</td>
								<td>
									<?php
									if( isset($connection_values[$option["name"]]) )
									{
										if($option["visible"] == true)
										{
											echo $connection_values[$option["name"]];
										}
										else
										{
											?>
											Значение скрыто от просмотра через панель управления сайта
											<?php
										}
									}
									else
									{
										?>
										Значение не задано
										<?php
									}
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
	else
	{
		?>
		<div class="col-lg-12">
			<div class="alert alert-info fade in">
				<strong>Информация!</strong> Для подключения кассового оборудования к Вашему сайту, обратитесь к разработчикам
			</div>
		</div>
		<?php
	}
	?>
	
</div>