<?php
/**
 * Плагин двухфакторной аутентификации администраторов бэкэнда
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");//Класс пользователь





// ----------------------------------------

/*
Алгоритм плагина

//Если пользователь авторизован через первый шаг (есть сессия)
	//Есть сессия 2fa
		//Ничего не делаем
	//Нет сессии 2fa
		//Есть GET кода авторизации
			//Проверяем
				//Правильный код
					//Создаем сессию 2fa
					//Редирект на страницу
				//Не правльный код
					//Инкрементируем количество попыток (поля 2fa_attempts)
						//Количество попыток больше 3
							//Удаляем сессию
							//Показываем сообщение, что количество попыток исчерпано
							//Показываем кнопку "Попробовать заново"
						//Количество попыток меньше 3
							//Показываем ошибку
							//Снова выводим форму
		//Нет получения кода авторизации
			//Отправляем код на main_field
			//Выводим форму ввода кода
//Если пользователь не авторизован через первый шаг
	//Ничего не делаем


*/


// ----------------------------------------
if(DP_User::isAdmin() == true)//Если авторизован по первому шагу...
{
	//Проверяем наличие сессии 2fa
	if( ! DP_User::is2FASessionAdmin() )
	{
		//Сессии 2FA нет
		if( !empty($_GET["2fa_code"]) )
		{
			//Проверяем наличие кода в сессии БД
			$code_2fa_query = $db_link->prepare("SELECT * FROM `sessions` WHERE `user_id` = ? AND `session` = ? AND `type` = 1;");
			$code_2fa_query->execute( array(DP_User::getAdminId(), $_COOKIE["admin_session"]) );
			$code_2fa_record = $code_2fa_query->fetch();
			$session_index = $code_2fa_record["id"];
			
			if( $code_2fa_record["2fa_code"] == $_GET["2fa_code"] )
			{
				//Код указан правильно - создаем сессию 2FA и перебрасываем на страницу
				$time = time();
                $session_2fa = md5($time.$DP_Config->secret_succession);//Код сессии 2FA
                
                //Записываем сеcсию 2FA в БД
                $db_link->prepare("UPDATE `sessions` SET `2fa_session` = ?, `2fa_code` = '', `2fa_attempts` = 0 WHERE `id` = ?;")->execute( array($session_2fa, $session_index) );
                
                //Записываем сессию в куки:
                if(!empty($_GET["rememberme"]))
                {
                    $cookietime = time()+9999999;//Запоминаем пользователя на долго
                }
                else
                {
                    $cookietime = 0; // На время работы браузера
                }
                setcookie("2fa", $session_2fa, $cookietime, "/");
                
                header("Location: ".getPageUrl());
			}
			else//Код введен не правильно
			{
				//Инкрементируем количество попыток (поля 2fa_attempts)
				
				$db_link->prepare("UPDATE `sessions` SET `2fa_attempts` = `2fa_attempts`+1 WHERE `id` = ?;")->execute( array($session_index) );
				
				//Получаем количество попыток
				$attempts_query = $db_link->prepare("SELECT `2fa_attempts` FROM `sessions` WHERE `id` = ?;");
				$attempts_query->execute( array($session_index) );
				$attempts_record = $attempts_query->fetch();
				
				if($attempts_record["2fa_attempts"] > 3)//Попытки исчерпаны
				{
					//Удаляем сессию
					$db_link->prepare("DELETE FROM `sessions` WHERE `id` = ?;")->execute( array($session_index) );
					
					//Показываем сообщение, что количество попыток исчерпано
					//Показываем кнопку "Попробовать заново"
					//ДИНАМИЧЕСКИ МЕНЯЕМ ШАБЛОН СТРАНИЦЫ
					$tpl_file_path = "plugins/2fa/forms/template_attempts_expired.php";
					$tpl_file = fopen($tpl_file_path, "r");
					$tpl_file_string = fread($tpl_file, filesize($tpl_file_path));//Строка с html/php кодом страницы шаблона
					fclose($tpl_file);
					$DP_Template->id = 0;//ID шаблона ставим равным 0 !ОБЯЗАТЕЛЬНО, Т.К. ПЛАГИН phone_tablet учитывает это значение
					$DP_Template->html = $tpl_file_string;//Присваиваем содержимое шаблона в HTML-код страницы
					$DP_Template->positions = json_decode("[{\"type\":\"head\",\"name\":\"head\",\"caption\":\"head\"},{\"type\":\"main\",\"name\":\"main\",\"caption\":\"main\"}]", true);//Список позиций шаблона
					//ДИНАМИЧЕСКИ МЕНЯМ ОСНОВНОЕ СОДЕРЖИМОЕ СТРАНИЦЫ
					//ПЕРЕИнициализируем поля объекта DP_Content
					$DP_Content->content_id = 0;
					$DP_Content->content_type = "";
					$DP_Content->title_tag = "Двухфакторная аутентификация";
					$DP_Content->description_tag = "";
					$DP_Content->keywords_tag = "";
					$DP_Content->author_tag = "";
					$DP_Content->content = "";
					$DP_Content->css_js = "";
					$DP_Content->modules_array = array();//Очищаем список модулей
				}
				else
				{
					//Показываем ошибку
					//Снова выводим форму
					$message_to_show = "Код введен не правильно. Попробуйте еще раз";
				
					//ДИНАМИЧЕСКИ МЕНЯЕМ ШАБЛОН СТРАНИЦЫ
					$tpl_file_path = "plugins/2fa/forms/template_form.php";
					$tpl_file = fopen($tpl_file_path, "r");
					$tpl_file_string = fread($tpl_file, filesize($tpl_file_path));//Строка с html/php кодом страницы шаблона
					fclose($tpl_file);
					$DP_Template->id = 0;//ID шаблона ставим равным 0 !ОБЯЗАТЕЛЬНО, Т.К. ПЛАГИН phone_tablet учитывает это значение
					$DP_Template->html = $tpl_file_string;//Присваиваем содержимое шаблона в HTML-код страницы
					$DP_Template->positions = json_decode("[{\"type\":\"head\",\"name\":\"head\",\"caption\":\"head\"},{\"type\":\"main\",\"name\":\"main\",\"caption\":\"main\"}]", true);//Список позиций шаблона
					//ДИНАМИЧЕСКИ МЕНЯМ ОСНОВНОЕ СОДЕРЖИМОЕ СТРАНИЦЫ
					//ПЕРЕИнициализируем поля объекта DP_Content
					$DP_Content->content_id = 0;
					$DP_Content->content_type = "";
					$DP_Content->title_tag = "Двухфакторная аутентификация";
					$DP_Content->description_tag = "";
					$DP_Content->keywords_tag = "";
					$DP_Content->author_tag = "";
					$DP_Content->content = "";
					$DP_Content->css_js = "";
					$DP_Content->modules_array = array();//Очищаем список модулей
				}
			}
			
		}
		else//Нет GET кода авторизации
		{
			//Формируем код
			$code_2fa = rand(100000,999999);
			
			//Записываем его в БД для последующей проверки
			$db_link->prepare("UPDATE `sessions` SET `2fa_code` = ? WHERE `user_id` = ? AND `session` = ?;")->execute( array($code_2fa, DP_User::getAdminId(), $_COOKIE["admin_session"]) );
			
			
			//Определяем тип контакта, который использовался при аутентификации
			$contact_type_query = $db_link->prepare('SELECT `contact_type` FROM `sessions` WHERE `user_id` = ? AND `session` = ? AND `type` = ?;');
			$contact_type_query->execute( array(DP_User::getAdminId(), $_COOKIE["admin_session"], 1) );
			$contact_type_record = $contact_type_query->fetch();
			if( $contact_type_record == false )
			{
				exit;
			}
			$contact_type = $contact_type_record['contact_type'];
			
			//Отправляем код на контакт пользователя
			$persons = array();//Массив получателей
			$persons[] = array( 'type'=>'user_id', 'user_id'=>DP_User::getAdminId() );
			//Массив с переменными для уведомления:
			$notify_vars = array();
			$notify_vars['site_name'] = $DP_Config->site_name;
			$notify_vars['2fa_code'] = $code_2fa;
			//Отправка уведомления по общий интерфейс
			$postdata = http_build_query(
				array(
					'check' => $DP_Config->secret_succession,
					'name' => 'backend_2fa_'.$contact_type,
					'vars' => json_encode($notify_vars),
					'persons' => json_encode($persons)
				)
			);
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $DP_Config->domain_path."content/notifications/send_notify.php");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			$curl_result = curl_exec($curl);
			curl_close($curl);
			$curl_result = json_decode($curl_result, true);
			if($curl_result["status"] == false)
			{
				$message_to_show = "<font style=\"color:#000;font-weight:bold;\">Ошибка 1 отправки кода</font>";
			}
			else
			{
				//Скрипт отправки выдал статус true, теперь НЕОБХОДИМО проверить статус отправки по конкретному контакту
				//Мы указывали единственный контакт, поэтому его и проверяем.
				if( ! $curl_result["persons"][0]['contacts'][$contact_type]['status'] )
				{
					$message_to_show = "<font style=\"color:#000;font-weight:bold;\">Ошибка 2 отправки кода</font>";
				}
				else
				{
					$message_to_show = "Код двухфакторной аутентификации отправлен. Введите его в форму ниже";
				}
			}
			
			//ДИНАМИЧЕСКИ МЕНЯЕМ ШАБЛОН СТРАНИЦЫ
			$tpl_file_path = "plugins/2fa/forms/template_form.php";
			$tpl_file = fopen($tpl_file_path, "r");
			$tpl_file_string = fread($tpl_file, filesize($tpl_file_path));//Строка с html/php кодом страницы шаблона
			fclose($tpl_file);
			$DP_Template->id = 0;//ID шаблона ставим равным 0 !ОБЯЗАТЕЛЬНО, Т.К. ПЛАГИН phone_tablet учитывает это значение
			$DP_Template->html = $tpl_file_string;//Присваиваем содержимое шаблона в HTML-код страницы
			$DP_Template->positions = json_decode("[{\"type\":\"head\",\"name\":\"head\",\"caption\":\"head\"},{\"type\":\"main\",\"name\":\"main\",\"caption\":\"main\"}]", true);//Список позиций шаблона
			//ДИНАМИЧЕСКИ МЕНЯМ ОСНОВНОЕ СОДЕРЖИМОЕ СТРАНИЦЫ
			//ПЕРЕИнициализируем поля объекта DP_Content
			$DP_Content->content_id = 0;
			$DP_Content->content_type = "";
			$DP_Content->title_tag = "Двухфакторная аутентификация";
			$DP_Content->description_tag = "";
			$DP_Content->keywords_tag = "";
			$DP_Content->author_tag = "";
			$DP_Content->content = "";
			$DP_Content->css_js = "";
			$DP_Content->modules_array = array();//Очищаем список модулей
			
		}
	}
}
?>