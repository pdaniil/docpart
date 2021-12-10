<?php


// --------------------------------------------------------------------------------------
//0. Структура результата

$sao_result = array();

// --------------------------------------------------------------------------------------
//1. Получаем логин и пароль

$login = $connection_options["user"];
$password = $connection_options["password"];

// --------------------------------------------------------------------------------------
//2. Отправляем запрос поставщику



try {
    $IP='http://188.235.19.133:8096/ws_online5/services/easkis5_ws_online5';
    $client_main = new SoapClient(null, array('location' => $IP, 'uri' => "http://easkis5_ws_online5/"));

    $nameMethod 	= 'rc_createorderout';
    $login_name 	= $login; // логин для авторизации на сайте eOrder
    $login_password = md5($password); // пароль для авторизации на сайте eOrder

    //Специальные параметры
    $t2_json_params = json_decode($order_item["t2_json_params"], true);

    $brand          = $order_item["t2_manufacturer"];
    $tovoem         = $order_item["t2_article"];
    $tovoem_clear   = $t2_json_params["idtovoemshort"];
    $ntov           = $order_item["t2_name"];
    $pricetov       = $t2_json_params["price"];
    $minpart        = (int)$order_item["t2_min_order"];
    $daydelivery    = (int)$t2_json_params["daydeliverysupplier"];
    $kol            = (int)$order_item["count_need"];
    $idtov          = $t2_json_params["idtov"];

    
$xml_loginpass = 
<<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<loginpass>
	<row>
		<userlogin>$login_name</userlogin>
		<userpass>$login_password</userpass>
	</row>
</loginpass>
XML;


$xml_params = 
<<<XML
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<tovparam>
    <row>
        <nbrand>$brand</nbrand>
        <idtovoem>$tovoem</idtovoem>
        <idtov>$idtov</idtov>
        <ntov>$ntov</ntov>
        <idtovoemshort>$tovoem_clear</idtovoemshort>
        <price>$pricetov</price>
        <daydeliverysupplier>$daydelivery</daydeliverysupplier>
        <minpart>$minpart</minpart>
        <kol>$kol</kol>
    </row>
</tovparam>
XML;

	$xml_output=$client_main->$nameMethod($xml_loginpass, $xml_params);

    $xml = simplexml_load_string($xml_output);
    $json = json_encode($xml);
    $sao_object = json_decode($json, true);

    $sao_result["object"] = $sao_object;
    $sao_output = $sao_object["row"];

	$sao_error = $sao_output["typemessage"];
	if(isset($sao_error) && $sao_error == "error")
	{

	  $sao_result["status"] = false;
      $sao_result["message"] = $sao_output["errmessage"];
      

    } else {

        if(isset($sao_output["idorder"])) {

            //После данного действия SAO-состояние данной позиции должно получить id=2 (Заказано)
            $new_sao_state = 2;
            
            //Определяем, новый статус позиции, который должен быть автоматически назначен для нового состояния			
			$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
			$new_status_query->execute( array($new_sao_state) );
			$new_status_record = $new_status_query->fetch();
            $new_status = $new_status_record["status_id"];
            if($new_status > 0)
            {
                //Отправляем запрос на изменение статуса позиции
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item_id."]&status=".$new_status."&key=".$DP_Config->tech_key);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $curl_result = curl_exec($ch);
                curl_close($ch);
            }

            $json_state_object = json_encode($sao_output);
            
            $SQL = "UPDATE 
                `shop_orders_items` 
            SET 
                `sao_state_object` = ?,
                `sao_message` = ?, 
                `sao_state` = ?, 
                `sao_robot` = ? 
            WHERE 
                `id` = ?;";
                        
            //Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
            if ( ! $db_link->prepare($SQL)->execute( array($json_state_object, "Заказано: ".date("d.m.Y H:i:s", time()), $new_sao_state, '0', $order_item_id) ) ) 
			{
                $error = "Позиция заказана, но произошёл сбой при обновлении sao-статуса!";
                throw new Exception($error);
            }
            
            $sao_result["status"] = true;
            $sao_result['order_items'] = array( $order_item_id );



        } else {

            if(isset($sao_output["idrowbasket"])) {

                $basketKol = $sao_output["kol"];
                $basketPrice = $sao_output["price"];
                $basketMin = $sao_output["minpart"];
                $basketDay = $sao_output["daydeliverysupplier"];

                //Цены, Мин.партии, Дней доставки, Кол-ва
                $reason = "";

                if((int)$basketKol !== $kol) {
                    $reason .= " Кол-ва (Актуальный: ".(int)$basketKol.", Отправленный: ".$kol.")";
                }

                if((int)$basketDay !== $daydelivery) {
                    $reason .= " Дней доставки (Актуальный: ".(int)$basketDay.", Отправленный: ".$daydelivery.")";
                }

                if((int)$basketMin !== $minpart) {
                    $reason .= " Мин.партии (Актуальный: ".(int)$basketMin.", Отправленный: ".$minpart.")";
                }

                if(round((int)$basketPrice, 2) !== round((int)$pricetov, 2)) {
                    $reason .= " Цены (Актуальный: ".round((int)$basketPrice, 2).", Отправленный: ".round((int)$pricetov, 2).")";
                }

                $errorMessage = "Проверка актуальности корзины выявила несоответствия" .$reason;

                $sao_result["status"] = false;
                $sao_result["message"] = $errorMessage;
                $sao_result['error_items'] = array( $order_item_id => $errorMessage);
                

            } else {

                $sao_result["status"] = false;
                $sao_result["message"] = "Неизвестная ошибка запроса.";
                $sao_result['error_items'] = array( $order_item_id => "Неизвестная ошибка запроса.");

            }
            
        }
    }
}

catch (SoapFault $fault) { 
    $sao_result["status"] = false;
    $sao_result["message"] = "Ошибка SOAP запроса.";
    $sao_result['error_items'] = array( $order_item_id => "Ошибка SOAP запроса.");
}
catch (Exception $e) {
    $sao_result["status"] = false;
    $sao_result["message"] = $e->getMessage();
    $sao_result['error_items'] = array( $order_item_id => $e->getMessage());
}

?>