<?php
/**
 * Плагин аутентификации администраторов бэкэнда
*/
defined('_ASTEXE_') or die('No access');

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");//Класс пользователь




//Авторизован ли пользователь, как администратор
if(DP_User::isAdmin() == 0)//Если не авторизован...
{
    if(!empty($_POST["authentication"]))//...но пытается авторизоваться
    {
        if( !empty($_POST["auth_contact"]) && !empty($_POST["password"]) && !empty($_POST["auth_contact_select"]) )
        {
			$auth_contact = $_POST["auth_contact"];
			$contact_type = $_POST["auth_contact_select"];
			$password = $_POST["password"];
            $auth_result = false;//Результат аутентификации
            
			
			//$contact_type используется в SQL-запросах. Проверяем значение
			if( $contact_type != 'email' && $contact_type != 'phone' )
			{
				exit;
			}
			
			
            //Если логин и пароль не правильны или этот пользователь не входит в группу Администраторы, то выводим сообщение "Не правильные логин и пароль"
            //Проверка логина и пароль
			$check_user_query = $db_link->prepare('SELECT * FROM `users` WHERE `'.$contact_type.'`=? AND `'.$contact_type.'_confirmed`=? AND `password`=? AND `unlocked`=?;');
			$check_user_query->execute( array(htmlentities($auth_contact), 1, md5($password.$DP_Config->secret_succession), 1) );
			$user_record = $check_user_query->fetch();//Запись пользователя
            if($user_record == false)
            {
                $auth_result = false;
            }
            else//Логин и пароль есть такие, теперь нужно проверить группу
            {
                $user_id = $user_record["user_id"];
                
                //Получаем список групп, допущенных к управлению бэкэндом
                $backend_groups_list = array();//Список групп, допущенных до бэкэнда
                $backend_groups_list = getBackendGroups(NULL, $backend_groups_list);//ПОЛУЧАЕМ СПИСОК ГРУПП, ДОПУЩЕННЫХ К БЭКЭНДУ
                
                //Получаем список групп, к котором относится данный пользователь
                $user_groups_list = array();
				
				$user_groups_list_query = $db_link->prepare("SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;");
				$user_groups_list_query->execute( array($user_id) );
                while($user_group_record = $user_groups_list_query->fetch() )
                {
                    array_push($user_groups_list, $user_group_record["group_id"]);
                }
                
                //Теперь ищем первое совпадение элементов списка backend_groups_list и user_groups_list
                //Если есть совпадение - есть допуск, если совпадения нет $auth_result = false;
                $access_denied = true;//Доступ запрещен
                for($i = 0 ; $i < count($backend_groups_list); $i++)
                {
                    for($j = 0 ; $j < count($user_groups_list) ; $j++)
                    {
                        if($backend_groups_list[$i] == $user_groups_list[$j])
                        {
                            //!!!Есть допуск!
                            $access_denied = false;
                            break;
                        }
                    }
                    if(!$access_denied)break;
                }//~for($i)
                
                //После всех проверок ставим результат аутентификации
                if($access_denied)
                {
                    $auth_result = false;
                }
                else
                {
                    $auth_result = true;
                }
            }
            
			
            
            //ЗДЕСЬ ИДЕТ ПРОВЕРКА ФЛАГА $auth_result...
            if($auth_result == false)
            {
                //ЗАПРЕЩЕНО!!!
                //ДИНАМИЧЕСКИ МЕНЯЕМ ШАБЛОН СТРАНИЦЫ - ФОРМА ВХОДА С СООБЩЕНИЕ О НЕПРАВИЛНЫХ УЧЕТНЫХ ДАННЫХ
                //Путь с файлу шаблона
                $tpl_file_path = "plugins/authentication/login_form/template.php";
                $tpl_file = fopen($tpl_file_path, "r");
                $tpl_file_string = fread($tpl_file, filesize($tpl_file_path));//Строка с html/php кодом страницы шаблона
                fclose($tpl_file);
                $DP_Template->id = 0;//ID шаблона ставим равным 0 !ОБЯЗАТЕЛЬНО, Т.К. ПЛАГИН phone_tablet учитывает это значение
                $DP_Template->html = $tpl_file_string;//Присваиваем содержимое шаблона в HTML-код страницы
                $DP_Template->positions = json_decode("[{\"type\":\"head\",\"name\":\"head\",\"caption\":\"head\"},{\"type\":\"main\",\"name\":\"main\",\"caption\":\"main\"}]", true);//Список позиций шаблона
                
                //ДИНАМИЧЕСКИЙ МЕНЯМ ОСНОВНОЕ СОДЕРЖИМОЕ СТРАНИЦЫ
                //ПЕРЕИнициализируем поля объекта DP_Content
                $DP_Content->content_id = 0;
                $DP_Content->content_type = "";
                $DP_Content->title_tag = "Форма входа";
                $DP_Content->description_tag = "";
                $DP_Content->keywords_tag = "";
                $DP_Content->author_tag = "";
                //Путь с файлу содержимого
                $form_file_path = "plugins/authentication/login_form/form.php";
                $form_file = fopen($form_file_path, "r");
                $form_file_string = fread($form_file, filesize($form_file_path));//Строка с html/php кодом формы
                fclose($form_file);
                $DP_Content->content = $form_file_string."\n<script>document.getElementById(\"wrong_authentication\").innerHTML = \"Не правильные логин и пароль\";</script>";
                $DP_Content->css_js = "";
                $DP_Content->modules_array = array();//Очищаем список модулей
				$DP_Template->html = str_replace("<div class=\"wrong_authentication\" id=\"wrong_authentication\"></div>","Не правильные логин и/или пароль",$DP_Template->html);
            }
            else if($auth_result == true)//Успешная аутентификация
            {
                $time = time();
                
                $session_succession = md5($auth_contact.$time.$DP_Config->secret_succession);//Код сессии - собираем его из логина, текущего дампа времени и секретной последовательности
                
				
				//Сначала очищаем старые неактивные сессии данного пользователя
				$last_activiti_time_to_del = time()-2592000;//До этого времени - удалять (30 суток)
				//Пользовательские настройки
				$db_link->prepare("DELETE FROM `users_options` WHERE `session_id` IN (SELECT `id` FROM `sessions` WHERE `user_id` = ? AND `last_activiti_time` < ?);")->execute( array($user_id, $last_activiti_time_to_del) );
				//Сами сессии
				$db_link->prepare("DELETE FROM `sessions` WHERE `user_id` = ? AND `last_activiti_time` < ?;")->execute( array($user_id, $last_activiti_time_to_del) );
				
				
				//Ключ защиты от CSRF-атак:
				$csrf_guard_key = sha1( $DP_Config->secret_succession . $session_succession . $_SERVER["REMOTE_ADDR"] . $_SERVER["HTTP_USER_AGENT"] );
				
                //Записываем сеcсию в БД
                $db_link->prepare("INSERT INTO `sessions` (`session`, `user_id`, `time`, `data`, `type`, `contact_type`, `csrf_guard_key`) VALUES (?,?,?,?,?,?,?);")->execute( array($session_succession, $user_id, $time, '', 1, $contact_type,$csrf_guard_key) );
				
                
                //Записываем сессию в куки:
                if(!empty($_POST["rememberme"]))
                {
                    $cookietime = time()+9999999;//Запоминаем пользователя на долго
                }
                else
                {
                    $cookietime = 0; // На время работы браузера
                }
                setcookie("admin_session", $session_succession, $cookietime, "/", '',false,true);
                setcookie("admin_u_id", $user_id, $cookietime, "/", '',false,true);
                
                header("Location: ".getPageUrl());
            }
        }
        else
        {
            exit();//Нет логина и пароля
        }
    }//if(!empty($_POST["authentication"])) - попытка аутентификации
    else//...Пользователь не авторизован и не пытается авторизоваться - выводим форму входа
    {
        //ДИНАМИЧЕСКИ МЕНЯЕМ ШАБЛОН СТРАНИЦЫ - ОБЫЧНАЯ ФОРМА ВХОДА
        //Путь с файлу шаблона
        $tpl_file_path = "plugins/authentication/login_form/template.php";
        $tpl_file = fopen($tpl_file_path, "r");
        $tpl_file_string = fread($tpl_file, filesize($tpl_file_path));//Строка с html/php кодом страницы шаблона
        fclose($tpl_file);
        $DP_Template->id = 0;//ID шаблона ставим равным 0 !ОБЯЗАТЕЛЬНО, Т.К. ПЛАГИН phone_tablet учитывает это значение
        $DP_Template->html = $tpl_file_string;//Присваиваем содержимое шаблона в HTML-код страницы
        $DP_Template->positions = json_decode("[{\"type\":\"head\",\"name\":\"head\",\"caption\":\"head\"},{\"type\":\"main\",\"name\":\"main\",\"caption\":\"main\"}]", true);//Список позиций шаблона
        
        //ДИНАМИЧЕСКИЙ МЕНЯМ ОСНОВНОЕ СОДЕРЖИМОЕ СТРАНИЦЫ
        //ПЕРЕИнициализируем поля объекта DP_Content
        $DP_Content->content_id = 0;
        $DP_Content->content_type = "";
        $DP_Content->title_tag = "Форма входа";
        $DP_Content->description_tag = "";
        $DP_Content->keywords_tag = "";
        $DP_Content->author_tag = "";
        //Путь с файлу содержимого
        $form_file_path = "plugins/authentication/login_form/form.php";
        $form_file = fopen($form_file_path, "r");
        $form_file_string = fread($form_file, filesize($form_file_path));//Строка с html/php кодом формы
        fclose($form_file);
        $DP_Content->content = $form_file_string;
        $DP_Content->css_js = "";
        $DP_Content->modules_array = array();//Очищаем список модулей данного материала
        $DP_Module_array = array();//Очищаем список объектов "Модуль"
    }//else
}//if(DP_Admin::getAdminId() == 0)
else//Если авторизован...
{
    //...и пытается выйти
    if(!empty($_POST["logout"]))
    {
		if($_POST["logout"] == "logout")
		{
			$db_link->prepare("DELETE FROM `sessions` WHERE `session`=? AND `user_id`=?;")->execute( array($_COOKIE["admin_session"], $_COOKIE["admin_u_id"]) );
		
			setcookie("admin_session", '', time() - 10000, "/", '',false,true);
			setcookie("admin_u_id", '', time() - 10000, "/", '',false,true);
			
			header("Location: ".getPageUrl());
		}
		else
		{
			exit;
		}
    }
	else
	{
		//Авторизован и не пытается выйти - ставим время активности сессии и время последнего визита
		//В учетную запись пользователя (старый вариант)
		$stmt = $db_link->prepare('UPDATE `users` SET `time_last_visit`= ? WHERE `user_id`=?;')->execute( array(time(), DP_User::getAdminId()) );
		
		//В учетную запись сессии (на разных устройствах у пользователя разные сессии). Время последней активности для сессии нужно для функции очистки старых неактивных сессий
		$db_link->prepare("UPDATE `sessions` SET `last_activiti_time` = ? WHERE `session` = ? AND `user_id` = ?;")->execute( array(time(), $_COOKIE["admin_session"], DP_User::getAdminId()) );
	}
}




//ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
//Рекурсивная функция получения линейного списка групп для бэкэнда
function getBackendGroups($parent_group_id, $backend_groups_list)
{
    global $db_link;
    global $DP_Config;
    
    //Первый вызов метода - получаем верхнюю группу бэкэнда
    if($parent_group_id == NULL)
    {
		$group_for_backend_query = $db_link->prepare("SELECT * FROM `groups` WHERE `for_backend` = 1;");
		$group_for_backend_query->execute();
        $group_for_backend_record = $group_for_backend_query->fetch();
        array_push($backend_groups_list, $group_for_backend_record["id"]);//Добавляем основную группу для бэкэнда
        
        if($group_for_backend_record["count"] == 0)
        {
            return $backend_groups_list;
        }
        else
        {
            return getBackendGroups($group_for_backend_record["id"], $backend_groups_list);//Рекурсивный вызов для вложенных
        }
    }
    else//Был рекурсивный вызов - добавляем влоеженные группы
    {
		$groups_query = $db_link->prepare("SELECT * FROM `groups` WHERE `parent` = ?;");
		$groups_query->execute( array($parent_group_id) );
        while(  $group_record = $groups_query->fetch() )
        {
            array_push($backend_groups_list, $group_record["id"]);//Добавляем вложенную группу
            
            if($group_record["count"] > 0)
            {
                $backend_groups_list = getBackendGroups($group_record["id"], $backend_groups_list);//Рекурсивный вызов для вложенных
            }
        }
    }
    
    return $backend_groups_list;//Возвращаем рекурсивно заполненный список групп
}
?>