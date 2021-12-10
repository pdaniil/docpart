<?php

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

//Класс бренда
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
//require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class auto1_by_enclosure
{
	public $status;
	public $ProductsManufacturers = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		$this->status = 0;//По умолчанию
		
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
		
		$header = array("Accept: application/json", "User-Agent: Server");

		$ch = curl_init("https://auto1.by/WebApi/GetRequestParameters?login=$login&password=$password");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $curl_result = curl_exec($ch);
		curl_close($ch);
		$curl_result = json_decode($curl_result, 1);

		// цикл по найденным организациям
		$Organizations = $curl_result['Organizations'];

		$curly_list = array();
		$result = array();
		$mh = curl_multi_init();

		foreach($Organizations as $Organization)
		{
			$orgId = $Organization['OrgId'];
			$orderType = $Organization['OrderType'];

			$query = array(
				'pattern' => $article,
				'orgId' => $orgId,
				'orderType' => $orderType,
				'login' => $login,
				'password' => $password,
			);

			$url = 'https://auto1.by/WebApi/GetBrands?' . http_build_query($query);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$curl_result = curl_exec($ch);
			curl_close($ch);

			$result = array_unique(array_merge($result, array_map( function($v) { return $v['Name']; } , json_decode($curl_result, 1))));
		}

		foreach ($result as $value) 
		{
			$DocpartManufacturer = new DocpartManufacturer
			(
				$value,
			    0,
				'Наименование не указано поставщиком', // В версии API от 13.05.2021, не передает назваие товара
				$storage_options["office_id"],
				$storage_options["storage_id"],
				true,
				array()
			);
			array_push($this->ProductsManufacturers, $DocpartManufacturer);
		}

		$this->status = true;
	}
}

$ob = new auto1_by_enclosure( $_POST["article"], json_decode($_POST["storage_options"], true) );
exit(json_encode($ob));
?>
