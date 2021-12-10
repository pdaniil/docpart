<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках
//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");
class auto1_by_enclosure
{
	public $result;
	public $Products = array();//Список товаров
	public function __construct($article, $storage_options)
	{
		$this->result = 0;//По умолчанию
		/*****Учетные данные*****/
        $login = $storage_options["login"];
        $password = $storage_options["password"];
		/*****Учетные данные*****/
		//Делаем запрос списка организаций
        $ch = curl_init("https://auto1.by/Articles/GetRequestParameters?number=$article&login=$login&password=$password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $curl_result = curl_exec($ch);
        curl_close($ch);
        $Parameters = new SimpleXMLElement($curl_result);
		/*
		echo '<pre>';
		var_dump($Parameters);
		exit;
		*/
		// цикл по найденным организациям
		$Organizations = (array)$Parameters->Organizations;
		$Organizations = $Organizations['Organization'];
		if(is_array($Organizations)){
			foreach($Organizations as $Organization){
				$OrgName = $Organization->OrgName;// название организации
				$orgId = $Organization->OrgId;// id организации для поиска
				$orderType = $Organization->OrderType;// 1 - основной заказ / 2 - дополнительный заказ
				//Делаем запрос товаров по артикулу
				$ch = curl_init("https://auto1.by/Articles/Search?number=$article&orgId=$orgId&orderType=$orderType&login=$login&password=$password");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				$curl_result = curl_exec($ch);
				curl_close($ch);
				$Search = new SimpleXMLElement($curl_result);
				$Search = (array)$Search;
				$Search = $Search['Item'];
				/*
				echo '<pre>';
				var_dump($Search);
				exit;
				*/
				foreach($Search as $item){
					$item = (array)$item;
					/*
					echo '<pre>';
					var_dump($item);
					exit;
					*/
					$Filial = (array)$item['Filial'];		
					$Filial = (array)$Filial['Store'];
					$Stores = (array)$item['Stores'];
					$Stores = (array)$Stores['Store'];
					$arr = array($Filial, $Stores);
					/*
					echo '<pre>';
					var_dump($Stores);
					exit;
					*/
					foreach($arr as $element){
					foreach($element as $Store){
						$Store = (array)$Store;
						$price = (float)$Store["Price"];
						$exist = $Store["Quantity"];
						$exist = str_replace('<','',$exist);
						$exist = str_replace('>','',$exist);
						$exist = str_replace('=','',$exist);
						$exist = str_replace('+','',$exist);
						$exist = str_replace('-','',$exist);
						$exist = str_replace(' ','',$exist);
						$Rating = (int)$Store["Rating"];
						if($Rating < 40){
							$Rating = 40;
						}
						$time = $Store["DeliveryInfo"];
						$time_now = mktime(0, 0, 0, date("m", time()), date("d", time()), date("Y", time()));
						$time = trim(substr($Store["DeliveryInfo"], 0, strpos($Store["DeliveryInfo"], "	")));
						if(empty($time)){
							$time = date("d.m.Y", time());
						}else{
							$time = $time.'.'.date('Y', time());
						}
						$time_str = $time;
						$time_arrive = strtotime($time);//Время поступления
						if($time_arrive > $time_now)
						{
							$time = $time_arrive - $time_now;//Срок доставки в секундах
							$time = (int)($time/86400);//Срок доставки в днях
							if($time == 0){
								$time = 1;
							}
						}else{
							$time = 0;
						}
						/*
						if(mb_strpos($time, 'дн', 0, 'UTF-8') > 0){
							$time = (int)$time;
						}else if(mb_strpos($time, 'ч', 0, 'UTF-8') > 0){
							$time = (int)$time;
							if($time > 0){
								$time = (int)($time/24);
								if($time <= 0){
									$time = 1;
								}
							}
						}else{
							$time = explode('	',$time);
							$time = trim($time[0]);
							$time_to_exe = 0;
							$time_arrive = strtotime($time);//Время поступления
							if($time_arrive > $time_now)
							{
								$time = $time_arrive - $time_now;//Срок доставки в секундах
								$time = (int)($time_to_exe/86400);//Срок доставки в днях
							}
						}
						*/
						//Наценка
						$markup = $storage_options["markups"][(int)$price];
						if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
						{
							$markup = $storage_options["markups"][count($storage_options["markups"])-1];
						}
						//Создаем объек товара и добавляем его в список:
						$DocpartProduct = new DocpartProduct
						(
							$item["Brand"],
							$item["Article"],
							$item["Designation"],
							$exist,
							$price + $price*$markup,
							$time + $storage_options["additional_time"],
							$time + $storage_options["additional_time"],
							$Store["StoreName"],
							$Store["Multiplicity"],
							$Rating,
							$storage_options["office_id"],
							$storage_options["storage_id"],
							$storage_options["office_caption"],
							$storage_options["color"],
							$storage_options["storage_caption"],
							$price,
							$markup,
							2,0,0,'',null,array("rate"=>$storage_options["rate"])
						);
						if($DocpartProduct->valid == true)
						{
							array_push($this->Products, $DocpartProduct);
						}
					}
					}
				}
			}
		}
        $this->result = 1;
	}//~function __construct($article)
};//~class auto1_by_enclosure
//$ob = new auto1_by_enclosure("OC247", array("login"=>"aga.store@mail.ru", "password"=>"061f"));
$ob = new auto1_by_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
exit(json_encode($ob));
?>