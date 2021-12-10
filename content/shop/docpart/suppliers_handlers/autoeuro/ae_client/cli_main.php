<?
class AutoeuroClient {
	
	var $version = '1.0.0.0';
	var $code_method = 'base64_';//'rawurl';
	var $server,$client_id,$client_name,$client_pwd;	// из cli_config.php
	var $homedir;
	
	function AutoeuroClient($config) 
	{
		$this->homedir = dirname(__FILE__).'/';
		foreach ($config as $key => $value)
			$this->$key = $value;
	}
//=================================================
	function getData($proc,$parm=false) {
		if(!$parm) $parm = array();
		$command = array('proc_id'=>$proc,'parm'=>$parm);
		$auth = array('client_name'=>$this->client_name,'client_pwd'=>$this->client_pwd);
		$data = array('command'=>$command,'auth'=>$auth);
		$data = $this->sendPost($this->server,$data);
		return $data;
	}
//=================================================
	function sendPost($url,$data) {
		$data = array('postdata'=>serialize($data));
		$data = array_map($this->code_method.'encode',$data);
		$data = http_build_query($data);
		$post = $this->genPost($url,$data);
		$url = parse_url($url);
		$fp = @fsockopen($url['host'], 80, $errno, $errstr, 30); 
		if (!$fp) return false;
		$responce = '';
		fwrite($fp,$post); 
		while ( !feof($fp) )
			$responce .= fgets($fp);
		fclose($fp);
// var_dump('<pre>',$responce);	// (отладка - показать ошибки php в вызываемом модуле)
		$responce = $this->NormalizePostResponce($responce);
		return $responce;
	}
//=================================================
	function genPost($url,$data) {
		$url = parse_url($url);
		$post = 'POST '.@$url['path']." HTTP/1.0\r\n"; 
		$post .= 'Host: '.$url['host']."\r\n"; 
		$post .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$post .= "Accept-Charset: windows-1251\r\n";
		$post .= 'Content-Length: '.strlen($data)."\r\n\r\n";
		$post .= $data;
		return $post;
	}
//=================================================
	function NormalizePostResponce($responce) {
		$responce = explode("\r\n\r\n",$responce);	// отделим header(s)
		$responce = array_pop($responce);	// извлечем данные
		$responce = array_map($this->code_method.'decode',array($responce));
		$responce = unserialize($responce[0]);
		return $responce;
	}
}
?>
