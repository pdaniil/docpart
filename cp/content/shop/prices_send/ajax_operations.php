<?php
set_time_limit(600);
header('Content-Type: application/json;charset=utf-8;');
//Соединение с БД
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result["status"] = false;
	$result["message"] = "DB connect error";
	$result["code"] = 502;
	exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

$sql = "SET SESSION SQL_BIG_SELECTS = 1";
$query = $db_link->prepare($sql);
$query->execute();

//Проверяем право менеджера
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
if( ! DP_User::isAdmin())
{
	$ansver = array('status'=>false);
	exit(json_encode($ansver));
}





/*
$f = fopen('log.txt', 'w');
fwrite($f, $_POST['request_object']);
*/
/*
$_POST['request_object'] = '{"group_id_my_list_emails":"1","offices":"2","arr_storages":[17],"arr_category":[86,120,116,113,112,109,108,100,99,87,90,91,97,121,110,107,106,104,98,103,114,119,117,115,62,63,64,65,66,80,122,81,83,82,84,85,74,111,105,101,73,78,102],"action":"create_prices"}';
*/




$ansver = array('status'=>false);
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action'])
{
	case 'send_prices':
		
		$send_result = true;
		
		//Почтовый обработчик
		//require_once($_SERVER["DOCUMENT_ROOT"]."/lib/DocpartMailer/docpart_mailer_distribution.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/lib/DocpartMailer/docpart_mailer.php");
		
		$subject = $DP_Config->site_name." прайс лист";
		$body = "<p>Прайс лист ".$DP_Config->site_name." на ".date('d-m-Y', time())."</p>";
		
		//$body .= '<p>Отказаться от рассылке можно в <a href="http://yamato.kg/users/editform" target="_blank">личном кабинете</a></p>';
		
		$new_name_file = "prices_".date("d_m_Y", time()).".csv";//Имя файла, которое будет указано в письме
		
		
		
		$users_list = $request_object['users_list'];
		$emails_list = explode(',', $request_object['emails_list']);
		$group_id_my_list_emails = (int)$request_object['group_id_my_list_emails'];
		
		if(is_array($users_list) && !empty($users_list))
		{
			foreach($users_list as $user)
			{
				$sql = "SELECT `group_id` FROM `users_groups_bind` WHERE `user_id` = ? LIMIT 1;";
				$query = $db_link->prepare($sql);
				$query->execute( array($user) );
				$rov = $query->fetch();
				$group_id = $rov['group_id'];
				
				$sql = "SELECT `user_id`, `email` AS `email` FROM `users` WHERE `user_id` = ?";
				
				$query = $db_link->prepare($sql);
				
				$query->execute( array($user) );
				while($rov = $query->fetch() )
				{
					$user_id = (int)$rov['user_id'];
					$email = trim($rov['email']);
					
					if(!empty($group_id) && !empty($email))
					{
						$file = $_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/prices_$group_id.csv";
						
						if(file_exists($file))
						{
							$docpartMailer = new DocpartMailer();//Объект обработчика
							$docpartMailer->Subject = $subject;//Тема письма
							$docpartMailer->Body = $body;//Текст письма
							$docpartMailer->CharSet="UTF-8";
							$docpartMailer->addAddress($email, $email);// Добавляем адрес в список получателей

							$docpartMailer->addAttachment($file, $new_name_file);// файл
							
							$docpartMailer->IsSMTP();
							$docpartMailer->IsHTML(true);
							if(!$docpartMailer->Send())
							{
								//Обработать ошибку отправки
								$send_result = false;
							}
						}
					}
				}
			}
		}
		
		
		if(!empty($emails_list))
		{
			foreach($emails_list as $email)
			{
				$email = trim($email);
				$group_id = $group_id_my_list_emails;
				
				if(!empty($group_id) && !empty($email))
				{
					$file = $_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/prices_$group_id.csv";
					
					if(file_exists($file))
					{
						$docpartMailer = new DocpartMailer();//Объект обработчика
						$docpartMailer->Subject = $subject;//Тема письма
						$docpartMailer->Body = $body;//Текст письма
						$docpartMailer->CharSet="UTF-8";
						$docpartMailer->addAddress($email, $email);// Добавляем адрес в список получателей

						$docpartMailer->addAttachment($file, $new_name_file);// файл
						
						$docpartMailer->IsSMTP();
						$docpartMailer->IsHTML(true);
						if(!$docpartMailer->Send())
						{
							//Обработать ошибку отправки
							$send_result = false;
						}
					}
				}
			}
		}
		
		if($send_result)
		{
			$ansver = array('status'=>true);
		}
		else
		{
			$ansver = array('status'=>false);
		}
		
		break;
	case 'check_office_storages_map':
		$offices = (int)$request_object['offices'];
		$arr_storages = $request_object['arr_storages'];
		
		$storages_not_linked_str = "";
		
		foreach( $arr_storages AS $storage_id )
		{
			$check_office_storages_map_query = $db_link->prepare("SELECT COUNT(*) FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ?;");
			$check_office_storages_map_query->execute( array($offices, $storage_id) );
			if( $check_office_storages_map_query->fetchColumn() == 0 )
			{
				$storage_name_query = $db_link->prepare("SELECT `name` FROM `shop_storages` WHERE `id` = ?;");
				$storage_name_query->execute( array($storage_id) );
				$storage_name_record = $storage_name_query->fetch();
				
				if($storages_not_linked_str != "")
				{
					$storages_not_linked_str = $storages_not_linked_str.", ";
				}
				
				$storages_not_linked_str = $storages_not_linked_str.$storage_name_record["name"];
			}
		}
		
		if($storages_not_linked_str == "")
		{
			$ansver = array('status'=>true);
		}
		else
		{
			$ansver = array('status'=>false, "message"=>$storages_not_linked_str);
		}
		
		break;
	case 'create_prices':
		
		$offices = (int)$request_object['offices'];
		$arr_storages = $request_object['arr_storages'];
		$arr_category = $request_object['arr_category'];
		if( isset($request_object['storages']) )
		{
			$storages = (int)$request_object['storages'];
		}
		else
		{
			$storages = 0;
		}
		//$storages = (int)$request_object['storages'];
		$users_list = null;
		if( isset($request_object['users_list']) )
		{
			$users_list = $request_object['users_list'];
		}
		if( isset($request_object['emails_list']) )
		{
			$emails_list = explode(',', $request_object['emails_list']);
		}
		$group_id_my_list_emails = (int)$request_object['group_id_my_list_emails'];
		
		$min_price = 0;// Минимальная цена для выгрузки в прайс лист
		$max_price = 0;// Максимальня цена для выгрузки в прайс лист (0 - не используется)
		$min_time_to_exe = 0;
		$max_time_to_exe = 0;
		
		$groups = array();// список групп для наценки
		
		// В цикле проходим по списку выбранных пользователей что бы определить список групп от которых будем брать наценки
		if(is_array($users_list) && !empty($users_list))
		{
			$user_id_sql = '';
			foreach($users_list as $user)
			{
				if($user_id_sql != '')
				{
					$user_id_sql .= ', ';
				}
				$user_id_sql .= (int)$user;
			}
			$sql = "SELECT DISTINCT `group_id` FROM `users_groups_bind` WHERE `user_id` IN ($user_id_sql);";
			$query = $db_link->prepare($sql);
			$query->execute();
			//echo $sql;
			
			while( $rov = $query->fetch() )
			{
				$groups[] = $rov['group_id'];
			}
		}
		
		if(!empty($group_id_my_list_emails))
		{
			if(array_search($group_id_my_list_emails, $groups) === false)
			{
				$groups[] = $group_id_my_list_emails;
			}
		}
		
		if(!empty($groups))
		{
			// Формируем прайсы для каждой группы
			foreach($groups as $group)
			{
				// Порядок колонок в выгрузке
				$manufacturer = 1;
				$article = 2;
				$name = 3;
				$exist = 4;
				$time_to_exe = 5;
				$price = 6;
				$min_order = 7;
				
				// Определяем максимальное число колонок в файле, так как формат выгрузки может быть настроен так что будут пустые колонки
				$max_col = $manufacturer;
				if($max_col < $article) $max_col = $article;
				if($max_col < $name) $max_col = $name;
				if($max_col < $exist) $max_col = $exist;
				if($max_col < $price) $max_col = $price;
				if($max_col < $time_to_exe) $max_col = $time_to_exe;
				if($max_col < $min_order) $max_col = $min_order;
				
				$arr_col_num = array(
										$manufacturer => "manufacturer",
										$article => "article",
										$name => "name",
										$exist => "exist",
										$price => "price",
										$time_to_exe => "time_to_exe",
										$min_order => "min_order"
				);
				
				//Создаем директорию для хранения файлов прайс-листов
				if( ! file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/") )
				{
					mkdir($_SERVER["DOCUMENT_ROOT"]."/content/files/Documents/prices_tmp/");
				}
				
				
				// Файл в который выгружаем данные
				$file = fopen($_SERVER["DOCUMENT_ROOT"].'/content/files/Documents/prices_tmp/prices_'.$group.'.csv', 'w');
				
				// Выводим содержимое файла
				$str = '';
				for($i=1; $i<=$max_col; $i++)
				{
					if(!empty($arr_col_num[$i]))
					{
						switch($arr_col_num[$i])
						{
							case 'manufacturer':
								$str .= 'Производитель';
							break;
							case 'article':
								$str .= 'Артикул';
							break;
							case 'name':
								$str .= 'Наименование';
							break;
							case 'exist':
								$str .= 'Наличие';
							break;
							case 'price':
								$str .= 'Цена';
							break;
							case 'time_to_exe':
								$str .= 'Срок поставки, дн.';
							break;
							case 'min_order':
								$str .= 'Мин. партия';
							break;
						}
						if($str != '' && $i < $max_col)$str .= ';';
					}
					else
					{
						$str .= ';';
					}
				}
				
				$str = iconv('UTF-8', 'windows-1251', $str);
				
				if(!empty($str))
				{
					// Записали заголовок
					$str .= "\r\n";
					fwrite($file, $str);
				}
					
				//----------------------------------------------------
				
				//ДАЛЕЕ РАБОТАЕМ ПО СКЛАДАМ С ТИПОМ DOCPART PRICE
				
				foreach( $arr_storages AS $storage_id )
				{
					//Отсеиваем склады с типом, не равным Docpart Price
					$storage_query = $db_link->prepare("SELECT * FROM `shop_storages` WHERE `id` = ?;");
					$storage_query->execute( array($storage_id) );
					$storage_record = $storage_query->fetch();
					if($storage_record["interface_type"] != 2)
					{
						continue;
					}
					
					$storage_connection_options = json_decode($storage_record["connection_options"], true);
					
					//price_id для данного склада
					$price_id = $storage_connection_options["price_id"];
					
					//Валюта склада
					$currency = $storage_record["currency"];
					
					//Курс валюты
					if( $currency == $DP_Config->shop_currency )
					{
						$currency_rate = 1;
					}
					else
					{
						$currency_rate_query = $db_link->prepare("SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = ?;");
						$currency_rate_query->execute( array($currency) );
						$currency_rate_record = $currency_rate_query->fetch();
						$currency_rate = $currency_rate_record["rate"];
					}
					
					
					//Получаем дополнительный срок доставки
					$additional_time_query = $db_link->prepare("SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? LIMIT 1;");
					$additional_time_query->execute( array($offices, $storage_id) );
					$additional_time_record = $additional_time_query->fetch();
					$additional_time = (int)($additional_time_record["additional_time"]/24);
					
					
					
					//1. Есть $offices (номер магазина - может быть только один)
					//2. $storage_id - номер склада
					//3. Есть $group - номер группы покупателя
					
					//Можем получить: срок доставки, наценку, *валюту склада, *курс валюты к основной валюте сайта, *price_id для склада
					
					$SQL_products = "SELECT
						*,
						(SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= `shop_docpart_prices_data`.`price` AND `max_point` > `shop_docpart_prices_data`.`price`) AS `markup`
					FROM
						`shop_docpart_prices_data`
					WHERE
						`price_id` = ?;";
					
					$products_query = $db_link->prepare($SQL_products);
					$products_query->execute( array($offices, $storage_id, $group, $price_id) );
					while( $item = $products_query->fetch() )
					{
						// >>>
						
						
						if(empty($item['price']))
						{
							continue;
						}
						$str = '';
						for($i=1; $i<=$max_col; $i++)
						{
							if(!empty($arr_col_num[$i]))
							{
								if($arr_col_num[$i] == 'price')
								{
									//Переводим в валюту сайта
									$item[$arr_col_num[$i]] = (float)($item[$arr_col_num[$i]] * $currency_rate);
									
									//Наценка
									$item[$arr_col_num[$i]] = $item[$arr_col_num[$i]] + ($item[$arr_col_num[$i]] * $item["markup"]);
									
									//Округление цены
									$work_price = $item[$arr_col_num[$i]];
									if($DP_Config->price_rounding == '1')//Без копеечной части
									{
										if($work_price > (int)$work_price)
										{
											$work_price = (int)$work_price+1;
										}
										else
										{
											$work_price = (int)$work_price;
										}
									}
									else if($DP_Config->price_rounding == '2')//До 5 руб
									{
										$work_price = (integer)$work_price;
										$price_str = (string)$work_price;
										$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
										if($price_str_last_char > 0 && $price_str_last_char < 5)
										{
											$work_price = $work_price + (5 - $price_str_last_char);
										}
										else if($price_str_last_char > 5 && $price_str_last_char <= 9)
										{
											$work_price = $work_price + (10 - $price_str_last_char);
										}
									}
									else if($DP_Config->price_rounding == '3')//До 10 руб
									{
										$work_price = (integer)$work_price;
										$price_str = (string)$work_price;
										$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
										if($price_str_last_char != 0)
										{
											$work_price = $work_price + (10 - $price_str_last_char);
										}
									}
									$item[$arr_col_num[$i]] = $work_price;
	
									$str .= '="'.(float)number_format($item[$arr_col_num[$i]],2,'.','').'"';
								}
								else if($arr_col_num[$i] == 'time_to_exe')
								{
									if($item[$arr_col_num[$i]] == 0)
									{
										$str .= '="'.$additional_time.'"';//'В наличии';
									}
									else
									{
										$str .= '="'.$item[$arr_col_num[$i]] + $additional_time.'"';// . ' дн.';
									}
								}
								else
								{
									if($arr_col_num[$i] == 'name')
									{
										$str_name = str_replace(';',' ',$item[$arr_col_num[$i]]);
										$str_name = str_replace("\r",'',$str_name);
										$str_name = str_replace("\n",'',$str_name);
										$str .= '="'.trim($str_name).'"';
									}
									else if($arr_col_num[$i] == 'min_order')
									{
										if(empty($item[$arr_col_num[$i]]))
										{
											$item[$arr_col_num[$i]] = 1;
										}
										$str .= '="'.$item[$arr_col_num[$i]].'"';
									}
									else
									{
										$str .= '="'.$item[$arr_col_num[$i]].'"';
									}
								}
								
								if($i < $max_col)$str .= ';';
							}
							else
							{
								$str .= ';';
							}
						}
						
						$str = iconv('UTF-8', 'windows-1251', $str);
						
						if(!empty($str))
						{
							$str .= "\r\n";
							fwrite($file, $str);
						}
						
					}
				}
				
				
				
				
				
				//----------------------------------------------------
				//ДАЛЕЕ РАБОТАЕМ ПО СКЛАДАМ С ТИПОМ TREELAX БД
				
				
				
				if(!empty($arr_category) && is_array($arr_category))
				{					
					$category_id_sql = '';
					foreach($arr_category as $category)
					{
						if($category_id_sql != '')
						{
							$category_id_sql .= ', ';
						}
						$category_id_sql .= (int)$category;
					}
					
					
					
					foreach( $arr_storages AS $storage_id )
					{
						//Отсеиваем склады с типом, не равным Docpart Price
						$storage_query = $db_link->prepare("SELECT * FROM `shop_storages` WHERE `id` = ?;");
						$storage_query->execute( array($storage_id) );
						$storage_record = $storage_query->fetch();
						if($storage_record["interface_type"] != 1)
						{
							continue;
						}
						
						$storage_connection_options = json_decode($storage_record["connection_options"], true);
						
						//Валюта склада
						$currency = $storage_record["currency"];
						
						//Курс валюты
						if( $currency == $DP_Config->shop_currency )
						{
							$currency_rate = 1;
						}
						else
						{
							$currency_rate_query = $db_link->prepare("SELECT `rate` FROM `shop_currencies` WHERE `id` = ?;");
							$currency_rate_query->execute( array($currency) );
							$currency_rate_record = $currency_rate_query->fetch();
							$currency_rate = $currency_rate_record["rate"];
						}
						
						
						//Получаем дополнительный срок доставки
						$additional_time_query = $db_link->prepare("SELECT `additional_time` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? LIMIT 1;");
						$additional_time_query->execute( array($offices, $storage_id) );
						$additional_time_record = $additional_time_query->fetch();
						$additional_time = (int)($additional_time_record["additional_time"]/24);
						
						
						
						$SQL_products = "SELECT 
								*,
								(SELECT `caption` FROM `shop_catalogue_products` WHERE `id` = `shop_storages_data`.`product_id`) AS `name`,
								(SELECT `value` FROM `shop_properties_values_text` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `value` LIKE 'Артикул' AND `property_type_id` = 3 AND `category_id` = `shop_storages_data`.`category_id` LIMIT 1) LIMIT 1) AS `article`,
								
								
								(SELECT `value` FROM `shop_line_lists_items` WHERE `id` = (SELECT `value` FROM `shop_properties_values_list` WHERE `product_id` = `shop_storages_data`.`product_id` AND `property_id` = (SELECT `id` FROM `shop_categories_properties_map` WHERE `category_id` = `shop_storages_data`.`category_id` AND `value` LIKE 'Производитель' AND `property_type_id` = 5 LIMIT 1) LIMIT 1) LIMIT 1) AS `manufacturer`,
								
								
								(SELECT `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id` = ? AND `min_point` <= `shop_storages_data`.`price` AND `max_point` > `shop_storages_data`.`price`) AS `markup`
								
							FROM 
								`shop_storages_data`
							WHERE 
								`storage_id` = ? AND `category_id` IN ($category_id_sql);";
								
							//echo $SQL_products;
							
						$products_query = $db_link->prepare($SQL_products);
						$products_query->execute( array($offices, $storage_id, $group, $storage_id) );
						while( $item = $products_query->fetch() )
						{
							// >>>
							
							$item['article'] = trim($item['article']);
							$item['manufacturer'] = trim($item['manufacturer']);
							
							if(empty($item['price']))
							{
								continue;
							}
							
							$str = '';
							for($i=1; $i<=$max_col; $i++)
							{
								if(!empty($arr_col_num[$i]))
								{
									if($arr_col_num[$i] == 'price')
									{
										//Переводим в валюту сайта
										$item[$arr_col_num[$i]] = (float)($item[$arr_col_num[$i]] * $currency_rate);
										
										//Наценка
										$item[$arr_col_num[$i]] = $item[$arr_col_num[$i]] + ($item[$arr_col_num[$i]] * $item["markup"]);
										
										//Округление цены
										$work_price = $item[$arr_col_num[$i]];
										if($DP_Config->price_rounding == '1')//Без копеечной части
										{
											if($work_price > (int)$work_price)
											{
												$work_price = (int)$work_price+1;
											}
											else
											{
												$work_price = (int)$work_price;
											}
										}
										else if($DP_Config->price_rounding == '2')//До 5 руб
										{
											$work_price = (integer)$work_price;
											$price_str = (string)$work_price;
											$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
											if($price_str_last_char > 0 && $price_str_last_char < 5)
											{
												$work_price = $work_price + (5 - $price_str_last_char);
											}
											else if($price_str_last_char > 5 && $price_str_last_char <= 9)
											{
												$work_price = $work_price + (10 - $price_str_last_char);
											}
										}
										else if($DP_Config->price_rounding == '3')//До 10 руб
										{
											$work_price = (integer)$work_price;
											$price_str = (string)$work_price;
											$price_str_last_char = (integer)$price_str[strlen($price_str)-1];
											if($price_str_last_char != 0)
											{
												$work_price = $work_price + (10 - $price_str_last_char);
											}
										}
										$item[$arr_col_num[$i]] = $work_price;
		
										$str .= '="'.(float)number_format($item[$arr_col_num[$i]],2,'.','').'"';
									}
									else if($arr_col_num[$i] == 'time_to_exe')
									{										
										if( $item["arrival_time"] < time() )
										{
											$str .= '="'.$additional_time.'"';//'В наличии';
										}
										else
										{
											$str .= '="'.(int)(($item["arrival_time"] - time() )/60/60/24) + $additional_time.'"';// . ' дн.';
										}
									}
									else
									{
										if($arr_col_num[$i] == 'name')
										{
											$str_name = str_replace(';',' ',$item[$arr_col_num[$i]]);
											$str_name = str_replace("\r",'',$str_name);
											$str_name = str_replace("\n",'',$str_name);
											$str .= '="'.trim($str_name).'"';
										}
										else if($arr_col_num[$i] == 'min_order')
										{
											if(empty($item[$arr_col_num[$i]]))
											{
												$item[$arr_col_num[$i]] = 1;
											}
											$str .= '="'.$item[$arr_col_num[$i]].'"';
										}
										else if($arr_col_num[$i] == 'article')
										{
											$article_str = $item[$arr_col_num[$i]];
											$sweep=array(" ", "-", "_", "`", "/", "'", '"', "\\", ".", ",", "#", ";", "\r\n", "\r", "\n", "\t");
											$article_str = str_replace($sweep,"", $article_str);
											$article_str = strtoupper($article_str);
											$str .= '="'.$article_str.'"';
										}
										else if($arr_col_num[$i] == 'manufacturer')
										{
											$manufacturer_str = $item[$arr_col_num[$i]];
											$sweep=array(";", "\r\n", "\r", "\n", "\t");
											$manufacturer_str = str_replace($sweep,"", $manufacturer_str);
											$manufacturer_str = strtoupper($manufacturer_str);
											$str .= '="'.$manufacturer_str.'"';
										}
										else
										{
											$str .= '="'.$item[$arr_col_num[$i]].'"';
										}
									}
									
									if($i < $max_col)$str .= ';';
								}
								else
								{
									$str .= ';';
								}
							}
							
							$str = iconv('UTF-8', 'windows-1251', $str);
							
							if(!empty($str))
							{
								$str .= "\r\n";
								fwrite($file, $str);
							}
						}
					}
				}
			}
			
			//Если не было ошибок
			if( true )
			{
				$ansver['status'] = true;
			}
		}
		
		break;
}
exit(json_encode($ansver));
?>