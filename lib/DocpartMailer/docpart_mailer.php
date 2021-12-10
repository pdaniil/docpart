<?php
/**
 * Класс расширения для PHPMailer
*/

require_once $_SERVER['DOCUMENT_ROOT']."/lib/PHPMailer/PHPMailerAutoload.php";
require_once $_SERVER['DOCUMENT_ROOT']."/config.php";


class DocpartMailer extends PHPMailer
{
	var $priority = 3;//приоритет почты: 1 – высоко, 3 – нормально, 5 – низко
	var $to_name;//Имя получателя
	var $to_email;//адрес получателя
	var $From = null;//адрес, с которого послается письмо
	var $FromName = null;//имя отправителя
	var $Sender = null;
	
	function DocpartMailer()
	{
		$DP_Config = new DP_Config;
		
		// Берем из файла config.php массив $site
		
		if((boolean)$DP_Config->smtp_mode == true)
		{
			$this->Host = ($DP_Config->smtp_encryption == 'ssl' ? 'ssl://':'').$DP_Config->smtp_host;
			$this->Port = $DP_Config->smtp_port;
			if($DP_Config->smtp_username != '')
			{
				$this->SMTPAuth  = true;
				$this->Username  = $DP_Config->smtp_username;
				$this->Password  =  $DP_Config->smtp_password;
			}
			$this->Mailer = "smtp";
		}
		if(!$this->From)
		{
			$this->From = $DP_Config->from_email;
		}
		if(!$this->FromName)
		{
			$this-> FromName = $DP_Config->from_name;
		}
		if(!$this->Sender)
		{
			$this->Sender = $DP_Config->from_email;
		}
		$this->Priority = $this->priority;
	}//function DocpartMailer()
}//class DocpartMailer extends PHPMailer
?>