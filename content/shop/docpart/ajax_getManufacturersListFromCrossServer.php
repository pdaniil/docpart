<?php
/**
 * Серверный скрипт для получения списка производителей по артикулу от сервера кроссов
*/
header('Content-Type: application/json;charset=utf-8;');
//Конфигурация Treelax
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;//Конфигурация CMS

//Класс для продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartManufacturer.php");


class ManufacturersList//Класс ответа
{
    public $status;//Рузультат работы (1 - успешно, 0 - не успешно)
    public $message;//Сообщение
    public $time;//Время запроса
	public $ProductsManufacturers = array();//Список объектов DocpartManufacturer
    
    public function __construct($query, $DP_Config)
    {
        if(!empty($query["article"]))
		{
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "http://ucats.ru/ucats/crosses/get_parts_by_article.php?article=".$query["article"]."&login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, 0);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); 
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			$curl_result = curl_exec($curl);
			curl_close($curl);
			
			$curl_result = json_decode($curl_result, true);

			if( $curl_result["status"] == "ok" )
			{
				//Фильтруем повторяющихся
				$hashes = array();
				
				for($i=0; $i < count($curl_result["parts"]); $i++)
				{
					$DocpartManufacturer = new DocpartManufacturer($curl_result["parts"][$i]["manufacturer"],
						0,
						$curl_result["parts"][$i]["name"],
						0,
						0,
						true,
						array('type'=>'server')
					);
					
					if($DocpartManufacturer->valid === true){
						//Получаем хеш
						$hash = md5($DocpartManufacturer->manufacturer);
						
						//Поиск хеша
						if (!isset($hashes[$hash])){
							array_push($this->ProductsManufacturers, $DocpartManufacturer);
							$hashes[$hash] = true;
						}
					}
				}
			}
		}
		
		// ----------------------------------------------------------------------------------------------
		
        $this->status = true;
    }//~__construct
}//~class ManufacturersList//Класс ответа


$time_start = microtime(true);
$ManufacturersList = new ManufacturersList(json_decode($_POST["query"], true), $DP_Config);
$time_end = microtime(true);
$ManufacturersList->time = number_format(($time_end - $time_start), 3, '.', '');
exit(json_encode($ManufacturersList));
?>