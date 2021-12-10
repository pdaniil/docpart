<?php
/**
 * Класс пользователя. Предназначен для получения данных о текущем авторизованном пользователе.
*/

class DP_User
{
    /*Получение ID пользователя*/
    public static function getUserId()
    {
        global $DP_Config;
        global $db_link;
        
		$session = null;
		if( isset($_COOKIE["session"]) )
		{
			$session = $_COOKIE["session"];
		}
		$u_id = null;
		if( isset($_COOKIE["u_id"]) )
		{
			$u_id = $_COOKIE["u_id"];
		}
		
		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `user_id`=?;');
		$check_authentication_query->execute( array($session, $u_id) );
		$session_count = $check_authentication_query->fetchColumn();

        if( $session_count == 0 )
        {
            return 0;
        }
        else if($session_count == 1)
        {
            return $u_id;
        }
        else
        {
            exit();
        }
    }//public static function getUserId()
    
    
    // ---------------------------------------------------------------------------------------------------
    
	/*Получение сессии пользователя (который сейчас сам работает)*/
	public static function getUserSession()
	{
		global $db_link;
		
		$session = null;
		if( isset($_COOKIE["session"]) )
		{
			$session = $_COOKIE["session"];
		}
		$u_id = null;
		if( isset($_COOKIE["u_id"]) )
		{
			$u_id = $_COOKIE["u_id"];
		}
		
		$session_query = $db_link->prepare("SELECT * FROM `sessions` WHERE `session`=? AND `user_id`=?;");
		$session_query->execute( array($session, $u_id) );
		$session_record = $session_query->fetch();
		
		return $session_record;
	}
	
	// ---------------------------------------------------------------------------------------------------
    
    /*Получение профиля пользователя*/
    public static function getUserGroup($user_id = null)
    {
        $user_group = null;
        
        global $DP_Config;
        global $db_link;
        
		if(empty($user_id)) {
			$user_id = DP_User::getUserId();//Получаем ID пользователя
		}
       
        if($user_id == 0)//Если пользователь не авторизован - ставим группу для гостей
        {
		
			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests`=?;');
			$guest_group_query->execute( array(1) );
			$guest_group_record = $guest_group_query->fetch();
            $user_group = $guest_group_record["id"];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
            return $user_group;
        }
          
        //Получаем список групп пользователя
		$groups_query = $db_link->prepare('SELECT `group_id` FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute( array($user_id) );
		$group_record = $groups_query->fetch();
		$user_group = $group_record["group_id"];// <-- ЗАПИСЬ ЗНАЧЕНИЯ

        return $user_group;
    }//public static function getUserProfile()
    
    // ---------------------------------------------------------------------------------------------------
    
    /*Получение профиля пользователя (который сейчас сам работает)*/
    public static function getUserProfile()
    {
        $profile = array();//Ассоциативный массив с данными пользователя
        
        global $DP_Config;
        global $db_link;
        
        $user_id = DP_User::getUserId();//Получаем ID пользователя
        
        if($user_id == 0)//Если пользователь не авторизован - ставим группу для гостей
        {
            $profile["user_id"] = 0;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
			
			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests`=?;');
			$guest_group_query->execute( array(1) );
			$guest_group_record = $guest_group_query->fetch();
            $profile["groups"] = array($guest_group_record["id"]);// <-- ЗАПИСЬ ЗНАЧЕНИЯ
            return $profile;
        }
        
        //Формируем массив с данными по пользователю:
        //ID пользователя:
        $profile["user_id"] = $user_id;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
        
        //Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute( array($user_id) );
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant');//Массив колонок таблицы users, которые нужно указать в ответе
		for( $i=0 ; $i < count($need_cols) ; $i++ )
		{
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}
            
        //По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`=?;');
		$profile_query->execute( array($user_id) );
		while($profile_record = $profile_query->fetch())
		{
			$profile[$profile_record["data_key"]] = $profile_record["data_value"];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}

        //Получаем список групп пользователя
        $profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute( array($user_id) );
		while($group_record = $groups_query->fetch())
		{
			array_push($profile["groups"], $group_record["group_id"]);
		}

        return $profile;
    }//public static function getUserProfile()
    
    // ---------------------------------------------------------------------------------------------------
    
    
    /*Получение профиля пользователя по его ID*/
    public static function getUserProfileById($user_id)
    {
        $profile = array();//Ассоциативный массив с данными пользователя
        
        global $DP_Config;
        global $db_link;
        
        if($user_id == 0)//Если пользователь не авторизован - ставим группу для гостей
        {
            $profile["user_id"] = 0;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
			
			$guest_group_query = $db_link->prepare('SELECT * FROM `groups` WHERE `for_guests` = ?;');
			$guest_group_query->execute( array(1) );
			$guest_group_record = $guest_group_query->fetch();
            
            $profile["groups"] = array($guest_group_record["id"]);// <-- ЗАПИСЬ ЗНАЧЕНИЯ
            return $profile;
        }
        
        //Формируем массив с данными по пользователю:
        //ID пользователя:
        $profile["user_id"] = $user_id;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
        
        //Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute( array($user_id) );
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant');//Массив колонок таблицы users, которые нужно указать в ответе
		for( $i=0 ; $i < count($need_cols) ; $i++ )
		{
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}
            
        //По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`= ?;');
		$profile_query->execute( array($user_id) );
		while( $profile_record = $profile_query->fetch() )
		{
			$profile[$profile_record["data_key"]] = $profile_record["data_value"];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}
        
        //Получаем список групп пользователя
        $profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute( array($user_id) );
		while( $group_record = $groups_query->fetch() )
		{
			array_push($profile["groups"], $group_record["group_id"]);
		}

        return $profile;
    }//public static function getUserProfileById()
    
    
    
    // ---------------------------------------------------------------------------------------------------
    
    
    /*Является ли пользователь администратором*/
    public static function isAdmin()
    {
        global $DP_Config;
        global $db_link;
        
		$admin_session = null;
		if( isset($_COOKIE["admin_session"]) )
		{
			$admin_session = $_COOKIE["admin_session"];
		}
		$admin_u_id = null;
		if( isset($_COOKIE["admin_u_id"]) )
		{
			$admin_u_id = $_COOKIE["admin_u_id"];
		}
		
		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `type` = ? AND `user_id` = ?;');
		$check_authentication_query->execute( array($admin_session, 1, $admin_u_id) );
		
		$sessions_count = $check_authentication_query->fetchColumn();

        if( $sessions_count == 0)
        {
            return 0;
        }
        else if( $sessions_count == 1)
        {
            return 1;
        }
        else
        {
            exit();
        }
    }//public static function getUserId()
    
    // ---------------------------------------------------------------------------------------------------
    
    /*Получение ID администратора*/
    public static function getAdminId()
    {
        global $DP_Config;
        global $db_link;
        
		$admin_session = null;
		if( isset($_COOKIE["admin_session"]) )
		{
			$admin_session = $_COOKIE["admin_session"];
		}
		$admin_u_id = null;
		if( isset($_COOKIE["admin_u_id"]) )
		{
			$admin_u_id = $_COOKIE["admin_u_id"];
		}
		
		
		//Сначала получаем количество сессий
		$check_authentication_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `session`=? AND `type` = ? AND `user_id` = ?;');
		$check_authentication_query->execute( array($admin_session, 1, $admin_u_id) );
		
		$sessions_count = $check_authentication_query->fetchColumn();
		
		if($sessions_count == 0)
		{
			return 0;//Сессий нет, это не админ
		}
		else if($sessions_count != 1)
		{
			exit();//Сессий не одна - такого быть не должно
		}
		
		return $admin_u_id;
    }//public static function getAdminId()
    
    
    
    // ---------------------------------------------------------------------------------------------------
    
    
    
    /*Получение профиля администратора*/
    public static function getAdminProfile()
    {
        $profile = array();//Ассоциативный массив с данными администратора
        
        global $DP_Config;
        global $db_link;
        
        $user_id = DP_User::getAdminId();//Получаем ID администратора
        
        if($user_id == 0)//Если пользователь не авторизован
        {
            return false;
        }
        
        //Формируем массив с данными по пользователю:
        //ID пользователя:
        $profile["user_id"] = $user_id;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
        
        //Поля из users:
		$user_query = $db_link->prepare('SELECT * FROM `users` WHERE `user_id`=?;');
		$user_query->execute( array($user_id) );
		$user_record = $user_query->fetch();
		$need_cols = array('email', 'email_confirmed', 'email_code_send_lock_expired', 'phone', 'phone_confirmed', 'phone_code_send_lock_expired', 'reg_variant');//Массив колонок таблицы users, которые нужно указать в ответе
		for( $i=0 ; $i < count($need_cols) ; $i++ )
		{
			$profile[$need_cols[$i]] = $user_record[$need_cols[$i]];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}
            
        //По полям профиля:
		$profile_query = $db_link->prepare('SELECT * FROM `users_profiles` WHERE `user_id`= ?;');
		$profile_query->execute( array($user_id) );
		while( $profile_record = $profile_query->fetch() )
		{
			$profile[$profile_record["data_key"]] = $profile_record["data_value"];// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		}
         
        //Получаем список групп пользователя
        $profile["groups"] = array();
		$groups_query = $db_link->prepare('SELECT * FROM `users_groups_bind` WHERE `user_id` = ?;');
		$groups_query->execute( array($user_id) );
		while( $group_record = $groups_query->fetch() )
		{
			array_push($profile["groups"], $group_record["group_id"]);
		}

        return $profile;
    }//public static function getAdminProfile()
	
	// ---------------------------------------------------------------------------------------------------
	
	//Проверка наличия сессии 2fa для администратора
	public static function is2FASessionAdmin()
	{
		global $DP_Config;
        global $db_link;
		
		
		$user_id = DP_User::getAdminId();//Получаем ID администратора
        if($user_id == 0)//Если пользователь не авторизован
        {
            return false;
        }
		
		//Нет сессии в куки
		if( empty($_COOKIE["2fa"]) )
		{
			return false;
		}
		
		$check_2fa_query = $db_link->prepare('SELECT COUNT(*) FROM `sessions` WHERE `user_id` = ? AND `2fa_session` = ?;');
		$check_2fa_query->execute( array($user_id, $_COOKIE["2fa"]) );
		
		
		if( $check_2fa_query->fetchColumn() == 0 )
		{
			return false;
		}
		else//Сессия 2FA есть
		{
			return true;
		}
	}
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Метод получения ВСЕХ пользовательских настроек (из таблицы users_options)*/
	public static function get_user_options()
	{
		global $DP_Config;
        global $db_link;
		
		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId();//Получаем ID пользователя
		$session = DP_User::getUserSession();
		if( $session == false )
		{
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];
		
		$users_options = array();
		$users_options_query = $db_link->prepare("SELECT * FROM `users_options` WHERE `user_id` = ? AND `session_id` = ?;");
		$users_options_query->execute( array($user_id, $session_id) );
		while( $option = $users_options_query->fetch() )
		{
			$users_options[$option["data_key"]] = $option["data_value"];
		}
		
		return $users_options;
	}
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Метод записи пользовательской настройки (в таблицу users_options)*/
	public static function set_user_option($key, $value)
	{
		global $DP_Config;
        global $db_link;
		
		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId();//Получаем ID пользователя
		$session = DP_User::getUserSession();
		if( $session == false )
		{
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];
		
		
		//Сначала проверяем, установлено ли уже значение для данной настройки
		$check_options_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$check_options_exists_query->execute( array($user_id, $session_id, $key) );
		$check_options_exists = $check_options_exists_query->fetchColumn();
		if( $check_options_exists == 0 )
		{
			return $db_link->prepare("INSERT INTO `users_options` (`user_id`, `session_id`, `data_key`, `data_value`) VALUES (?,?,?,?);")->execute( array($user_id, $session_id, $key, $value) );
		}
		else if( $check_options_exists == 1 )
		{
			return $db_link->prepare("UPDATE `users_options` SET `data_value` = ? WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;")->execute( array($value, $user_id, $session_id, $key) );
		}
		else
		{
			return false;
		}
		
	}
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Метод удаления пользовательской настройки (из таблицы users_options)*/
	public static function delete_user_option($key)
	{
		global $DP_Config;
        global $db_link;
		
		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId();//Получаем ID пользователя
		$session = DP_User::getUserSession();
		if( $session == false )
		{
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];
		
		
		//Сначала проверяем, установлено ли уже значение для данной настройки
		$check_options_exists_query = $db_link->prepare("SELECT COUNT(*) FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$check_options_exists_query->execute( array($user_id, $session_id, $key) );
		$check_options_exists = $check_options_exists_query->fetchColumn();
		if( $check_options_exists == 1 )
		{
			return $db_link->prepare("DELETE FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?")->execute( array($user_id, $session_id, $key) );
		}
		
		return true;//Нечего удалять
	}
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Метод получения значения определенной настройки пользователя по ключу (из таблицы users_options)*/
	public static function get_user_option_by_key($key)
	{
		global $DP_Config;
        global $db_link;
		
		//Пользовательские настройки привязываются к зарегистрированному пользователю по user_id и для незарегистрированного пользователя по session_id
		$user_id = DP_User::getUserId();//Получаем ID пользователя
		$session = DP_User::getUserSession();
		if( $session == false )
		{
			//Это может быть, если пользователь удалил куки session
			return false;
		}
		$session_id = $session["id"];
		
		
		$users_options_query = $db_link->prepare("SELECT * FROM `users_options` WHERE `user_id` = ? AND `session_id` = ? AND `data_key` = ?;");
		$users_options_query->execute( array($user_id, $session_id, $key) );
		$option = $users_options_query->fetch();
		if( $option == false )
		{
			return false;//Означает, что данная опция не установлена для пользователя
		}
		else
		{
			return $option["data_value"];
		}
	}
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Проверка профиля на принадлежность к группе Администратор*/
    public static function isAdminGroup()
    {
        $profile = array();//Ассоциативный массив с данными пользователя
        
        global $DP_Config;
        global $db_link;
        
        $user_id = DP_User::getUserId();//Получаем ID пользователя
        
        if($user_id == 0)//Если пользователь не авторизован - ставим группу для гостей
        {
            return false;
        }
        
        //Формируем массив с данными по пользователю:
        //ID пользователя:
        $profile["user_id"] = $user_id;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
        
        //Группа администраторов:
		$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `value` LIKE \'%Администратор%\' ;');
		$group_query->execute();
		$group_admin_id = $group_query->fetchColumn();
		
        //По полям профиля:
		$profile_query = $db_link->prepare('SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` = ?;');
		$profile_query->execute( array($user_id, $group_admin_id) );

		if($profile_query->fetchColumn() > 0) {
		    
		    return true;
		
		}
		
		return false;
    }//public static function getUserProfile()


	/*Проверка профиля на принадлежность к группе Бэкэнд*/
	public static function isBackendGroup()
	{
		$profile = array();//Ассоциативный массив с данными пользователя
		
		global $DP_Config;
		global $db_link;
		
		$user_id = DP_User::getUserId();//Получаем ID пользователя
		
		if($user_id == 0)//Если пользователь не авторизован - ставим группу для гостей
		{
			return false;
		}
		
		//Формируем массив с данными по пользователю:
		//ID пользователя:
		$profile["user_id"] = $user_id;// <-- ЗАПИСЬ ЗНАЧЕНИЯ
		$backend_group = array();
		
		//Группа администраторов:
		$group_backend_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `for_backend` = 1 ;');
		$group_backend_query->execute();
		$group_backend_id = $group_backend_query->fetchColumn();
		
		$backend_group[] = $group_backend_id;
		
		//Группа администраторов:
		$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `parent` = ? ;');
		$group_query->execute(array($group_backend_id));
		while($group_id = $group_query->fetch()) {
				$backend_group[] = $group_id['id'];
		}
		
		$backend_group_str = implode(",", $backend_group);
		
		//По полям профиля:
		$profile_query = $db_link->prepare("SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` IN ($backend_group_str);");
		$profile_query->execute( array($user_id) );

		if($profile_query->fetchColumn() > 0) {
			
			return true;
		
		}
		
		return false;
	}//public static function getUserProfile()
    
    // ---------------------------------------------------------------------------------------------------
	
	
	/*Метод определения доступных видов связи на сайте (отправка писем по SMTP и SMS на телефон)*/
	public static function available_communications()
	{
		global $DP_Config;
        global $db_link;
		
		$result = array();//Ассоциативный массив для ответа.
		
		//Считаем, что способ связи доступен, если он настроен. Но, нужно иметь ввиду, что в данном методе никак не контролируется корректность настроек для SMTP или SMS.
		
		
		//Определяем доступность SMTP
		$result["smtp"] = false;
		if( !empty($DP_Config->from_name) && !empty($DP_Config->from_email) && !empty($DP_Config->smtp_mode) && !empty($DP_Config->smtp_encryption) && !empty($DP_Config->smtp_host) && !empty($DP_Config->smtp_port) && !empty($DP_Config->smtp_username) && !empty($DP_Config->smtp_password) )
		{
			$result["smtp"] = true;
		}
		
		//Определяем доступность SMS
		$result["sms"] = false;
		$check_sms_query = $db_link->prepare("SELECT COUNT(*) FROM `sms_api` WHERE `active` = ?;");
		$check_sms_query->execute( array(1) );
		if( $check_sms_query->fetchColumn() == 1 )
		{
			$result["sms"] = true;
		}
		
		
		//Для удобства использования в скриптах - добавим поле all
		$result["all"] = false;
		if( $result["smtp"] && $result["sms"] )
		{
			$result["all"] = true;
		}
		
		
		return $result;
	}
	
	
	// ---------------------------------------------------------------------------------------------------
	
	/*Вспомогательный метод проверки E-mail и Телефона по регулярному выражению*/
	public static function check_contact_by_regexp($contact, $type)
	{
		global $DP_Config;
        global $db_link;
		
		if( empty($contact) || ($type != 'email' && $type != 'phone') )
		{
			return false;
		}
		
		$regexp_query = $db_link->prepare('SELECT `regexp` FROM `reg_fields` WHERE `name` = ?;');
		$regexp_query->execute( array($type) );		
		$regexp = $regexp_query->fetchColumn();
		preg_match("/".$regexp."/", $contact, $matches);
		if( count($matches) == 1 )
		{
			if( $matches[0] != $contact )
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
		
		return true;
	}
	
	
	// ---------------------------------------------------------------------------------------------------

	/*Проверка пользователя на принадлежность к группе ids в настройках ПУ*/
	public static function inSettingsGroup()
	{
		$profile = array();//Ассоциативный массив с данными пользователя
		
		global $DP_Config;
		global $db_link;
			
		$users_settings_groups_str = $DP_Config->settings_group_ids;
		if(empty($users_settings_groups_str)) {
			return false;
		}
		
		$users_settings_groups = explode(',', $users_settings_groups_str);
		if(is_array($users_settings_groups)){
			foreach($users_settings_groups as &$users_settings_group){
				$users_settings_group = trim($users_settings_group);
			}
		}else{
			$users_settings_groups = array();
		}
		
		if(empty($users_settings_groups)) {
			return false;
		}
		
		$groups_id_settings = array();
		
		foreach($users_settings_groups as $users_settings_group_id){
			$groups_id_settings[] = $users_settings_group_id;
			//Дочерние группы
			$group_query = $db_link->prepare('SELECT `id` FROM `groups` WHERE `parent` = ? ;');
			$group_query->execute(array($users_settings_group_id));
			while($group_id = $group_query->fetch()) {
					$groups_id_settings[] = $group_id['id'];
			}
		}
		
		$groups_id_settings_str = implode(",", $groups_id_settings);
		
		//По полям профиля:
		$profile_query = $db_link->prepare("SELECT COUNT(*) FROM `users_groups_bind` WHERE `user_id`=? AND `group_id` IN ($groups_id_settings_str);");
		$profile_query->execute( array($user_id) );

		if($profile_query->fetchColumn() > 0) {
			return true;
		}
		
		return false;
	}//public static function getUserProfile()
	
	// ---------------------------------------------------------------------------------------------------

	public static function get_user_markup($price, $storage_id = null, $office_id = null, $user_id = null) {

		global $DP_Config;
        global $db_link;

		if(empty($price)) {
			return false;
		}

		$markup = null;

		//Получаем группу пользователя
		$group_id = DP_User::getUserGroup($user_id);

		//1. Формируем массив наценок
		$markups_2 = array();
		$markups_query = $db_link->prepare('SELECT `min_point`, `max_point`, `markup`/100 AS `markup` FROM `shop_offices_storages_map` WHERE `office_id` = ? AND `storage_id` = ? AND `group_id`=? ORDER BY `min_point`;');
		$markups_query->execute( array($office_id, $storage_id, $group_id) );
		while( $markup = $markups_query->fetch() )
		{
			array_push($markups_2, $markup );
		}//for($i)

		//Ищем наценку для этой цены и обрабатываем округление
		foreach( $markups_2 AS $markup_range )
		{
			if( $price >= $markup_range["min_point"] && $price <= $markup_range["max_point"] )
			{
				return (float)($markup_range["markup"]);
			}
		}

		return $markup;
	}

	// ---------------------------------------------------------------------------------------------------

	public static function get_price_purchase_by_cart_product($cart_product_id) {

		global $DP_Config;
        global $db_link;

		$price_purchase = null;

		//1. Получаем запись корзины
		$cart_object = array();
		$cart_object_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `id` = ?;');
		$cart_object_query->execute( array($cart_product_id) );
		
		$cart_object = $cart_object_query->fetch();

		if(!empty($cart_object)) {

			$product_type = $cart_object["product_type"];

			if($product_type == 1) {

				//Получаем детальную запись корзины
				//Получаем складскую запись
				//Получаем закупочную стоимость
				$cart_record_details_query = $db_link->prepare('SELECT * FROM `shop_carts_details` WHERE `cart_record_id` = ?;');
				$cart_record_details_query->execute( array($cart_product_id) );
				$cart_record_detail = $cart_record_details_query->fetch();

				if(!empty($cart_record_detail)) {

					$storage_record_id = $cart_record_detail["storage_record_id"];
					//Получаем цену ЗАКУПА по данной поставке со склада:
					$SQL_currency_rate = "(SELECT `rate` FROM `shop_currencies` WHERE `iso_code` = (SELECT `currency` FROM `shop_storages` WHERE `id` = `shop_storages_data`.`storage_id`) )";
						
					$price_purchase_query = $db_link->prepare('SELECT `price`*'.$SQL_currency_rate.' AS `price` FROM `shop_storages_data` WHERE `id`= ?;');
					$price_purchase_query->execute( array($storage_record_id) );
					$price_purchase_record = $price_purchase_query->fetch();
					$price_purchase = $price_purchase_record["price"];
				}
	
			} else if($product_type == 2) {

				$price_purchase = (isset($cart_object["t2_price_purchase"]) && !empty($cart_object["t2_price_purchase"])) ? $cart_object["t2_price_purchase"] : null;

			}
		}

		return $price_purchase;
	}

	// ---------------------------------------------------------------------------------------------------

    
}//class DP_User
?>