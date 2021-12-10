<?php

	require_once('DocpartTable.php');
	require_once('DocpartUser_Group.php');
	require_once('DocpartUser_Profile.php');
	class DocpartUser extends DocpartTable
	{
		public $user_id;
		public $reg_variant;
		public $email;
		public $email_confirmed;
		public $email_new;
		public $email_code;
		public $email_code_expired;
		public $email_code_attempts;
		public $email_code_send_lock_expired;
		public $phone;
		public $phone_confirmed;
		public $phone_new;
		public $phone_code;
		public $phone_code_expired;
		public $phone_code_attempts;
		public $phone_code_send_lock_expired;
		public $password;
		public $unlocked;
		public $time_registered;
		public $time_last_visit;
		public $admin_created;
		public $forgot_password_time;
		public $forgot_password_code;
		public $ip_address;
		
		public function printTag($tagName, $file)
		{
			$class_fields = get_class_vars(get_class($this));
			fwrite($file, '<'.$tagName.'>');
			foreach($class_fields as $key => $value)
			{
				fwrite($file, '<'.$key.'>');
				fwrite($file, $this->{$key});
				fwrite($file, '</'.$key.'>');
			}
			
			$where_condition = '`user_id` = '.$this->user_id;
			$groups = new DocpartUser_Group();
			$groups->getDataBySql('*', 'users_groups_bind', $file, 'group', 'groups', $where_condition);
			
			$profile = new DocpartUser_Profile();
			$profile->getDataBySql('*', 'users_profiles', $file, 'profile', 'profile', $where_condition);
			
			fwrite($file, '</'.$tagName.'>');
		}
	}

?>