<?php
/**
 * Скрипт для обработки различных операций над группами складов
*/

//Соединение с БД
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

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$answer = array('status'=>false);
	exit(json_encode($answer));
}

$answer = array('status'=>false);
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action'])
{
	case 'get_table':
		// Получаем список групп и формируем html представление таблицы групп
		$groups = array();
		$groups_query = $db_link->prepare("SELECT * FROM `shop_storages_groups` ORDER BY `order`;");
		$groups_query->execute();
		while($group = $groups_query->fetch())
		{
			$groups[] = array(
							'id' => $group['id'], 
							'name' => $group['name'], 
							'storages' => explode(',', $group['storages']), 
							'order' => $group['order']
						);
		}
		
		// Получаем все API склады
		$storages = array();
		$storages_query = $db_link->prepare("SELECT `id`, `name` FROM `shop_storages` WHERE `interface_type` NOT IN(1, 2, 6);");
		$storages_query->execute();
		while($storage = $storages_query->fetch())
		{
			$storages[(int)$storage['id']] = $storage['name'];
		}
		
		// Формируем HTML
		$html = '';
		
		if(!empty($groups)){
			foreach($groups as $group){
				$html .= '
				<div class="col-lg-12">
					<div class="hpanel">
						<div class="panel-heading hbuilt" style="position:relative;">
							'. $group['name'] .'
						</div>
						<div class="panel-body">
							<table class="table">
								<tr>
									<th>ID</th>
									<th>Наименование</th>
								</tr>
				';
								foreach($group['storages'] as $storage){
									$storage = (int)trim($storage);
									$html .= '<tr>
												<td>'. $storage .'</td>
												<td style="width:100%;">'. $storages[$storage] .'</td>
											</tr>';
									unset($storages[$storage]);
								}
				$html .= '
							</table>
						</div>
						<div class="panel-footer text-right">
							<img id="img_add" style="height: 31px; margin-right: 5px;" class="hidden" src="/content/files/images/ajax-loader-transparent.gif"/><a id="btn_add" onclick="del('. $group['id'] .');" class="btn btn-ar btn-primary"><i class="fa fa-times"></i> Удалить</a>
						</div>
					</div>
				</div>
				';
			}
		}
		
		// Если остались склады то добавляем их в последнею группу
		if(!empty($storages)){
			$html .= '
			<div class="col-lg-12">
				<div class="hpanel">
					<div class="panel-heading hbuilt" style="position:relative;">
						Последняя группа складов
					</div>
					<div class="panel-body">
						<table class="table">
							<tr>
								<th>ID</th>
								<th>Наименование</th>
							</tr>
			';
							foreach($storages as $storage_id => $storage_name){
								$html .= '<tr>
											<td>'. $storage_id .'</td>
											<td style="width:100%;">'. $storage_name .'</td>
										</tr>';
							}
			$html .= '
						</table>
					</div>
				</div>
			</div>
			';
		}
		
		if($html == ''){
			$html = '<div class="panel-body"></div>';
		}
		
		exit($html);
		break;
	case 'get_storages':
		// Заполняем список не распределенных на группы складов
		$storages_query = $db_link->prepare("SELECT GROUP_CONCAT(`storages`) AS 'storages' FROM `shop_storages_groups`;");
		$storages_query->execute();
		$storages = $storages_query->fetch();
		$storages = $storages['storages'];
		
		if(empty($storages)){
			$storages = 0;
		}
		
		$storages_list = array();
		$storages_query = $db_link->prepare("SELECT `id`, `name`, `interface_type` FROM `shop_storages` WHERE `id` NOT IN($storages) AND `interface_type` NOT IN(1, 2, 6) ORDER BY `name`;");
		$storages_query->execute();
		while($storage = $storages_query->fetch())
		{
			$storages_list[] = array('id' => $storage['id'], 'name' => $storage["name"]);
		}
		$answer['status'] = true;
		$answer['storages_list'] = $storages_list;
		break;
	case 'add_group':
		$name = urldecode($request_object['name']);
		$name = trim(strip_tags($name));
		$storages = $request_object['storages'];
		
		if(empty($name)){
			break;
		}
		
		if(!is_array($storages)){
			break;
		}
		
		foreach($storages as $storage){
			if((int)$storage <= 0){
				break 2;
			}
		}
		
		$order_query = $db_link->prepare("SELECT MAX(`order`) AS 'order' FROM `shop_storages_groups`;");
		$order_query->execute();
		$order = $order_query->fetch();
		$order = (int)$order['order'] + 1;
		
		if( $db_link->prepare("INSERT INTO `shop_storages_groups` (`name`,`storages`,`order`) VALUES (?, ?, ?);")->execute( array($name, implode(',', $storages), $order) ) )
		{
			$answer['status'] = true;
		}
		break;
	case 'del':
		$id = (int)$request_object['id'];
		if( $db_link->prepare("DELETE FROM `shop_storages_groups` WHERE `id` = ?;")->execute( array($id) ) )
		{
			$answer['status'] = true;
		}
		break;
}
header('Content-Type: application/json;charset=utf-8;');
exit(json_encode($answer));
?>