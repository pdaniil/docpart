<?php 
// header('Content-type: application/json');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках

ini_set('display_errors', 0);

//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
// require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class zap_pro {

    
    public $status;
    public $Products = array();

    public function __construct($article, $manufacturers, $storage_options) {


        $login = $storage_options["login"];
        $password = $storage_options["password"];
        $brand = $manufacturers[0]["manufacturer_show"];

        $url = "https://zap-pro.ru/api/v1.0/getPrice?login={$login}&password={$password}&code={$article}&brand={$brand}";

        $ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.2) Gecko/20100101 Firefox/10.0.2' );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt($ch, CURLOPT_VERBOSE, 1 );
        
        $execute = curl_exec($ch);
        
        // var_dump($execute);

        $brands = simplexml_load_string($execute);

        $brands = json_decode(json_encode((array)$brands), TRUE);

        // var_dump($brands);

        if( $brands["Результат"] != "OK" )
		{
            $this->status = 0;
			return;
		} 
		
		curl_close( $ch );

		if($brands['Результат'] == "OK") {
            $this->result = 1;

            $products =$brands["СписокПозиций"]["Позиция"];

        //    var_dump($products);

            for($i = 0; $i < count($products); $i++) {

                $price = (float)$products[$i]['Цена'];
                    
                //Наценка
    		    $markup = $storage_options["markups"][(int)$price];
    		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
    		    {
    		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
    		    }


                //Создаем объек товара и добавляем его в список:
                $DocpartProduct = new DocpartProduct(
                    $products[$i]['Производитель'],
                    $products[$i]['Артикул'],
                    $products[$i]['Наименование'],
                    $products[$i]['Наличие'],
                    $products[$i]['Цена'] + $products[$i]['Цена']*$markup,
                    $products[$i]['СрокДоставки'] + $storage_options["additional_time"],
                    $products[$i]['СрокДоставки'] + $storage_options["additional_time"],
                    $products[$i]['ПрайсЛист'],
                    0,
                    $storage_options["probability"],
                    $storage_options["office_id"],
                    $storage_options["storage_id"],
                    $storage_options["office_caption"],
                    $storage_options["color"],
                    $storage_options["storage_caption"],
                    $products[$i]['Цена'],
                    $markup,
                    2,
                    0,
                    0,
                    '',
                    NULL,
                    array("rate"=>$storage_options["rate"])
                );

                array_push($this->Products, $DocpartProduct);
            }

        } else {

            $this->result = 0;
			return;
        }

    }
}

//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);

$ob = new zap_pro($_POST["article"], json_decode($_POST["manufacturers"], true), $storage_options);
exit(json_encode($ob));

?>