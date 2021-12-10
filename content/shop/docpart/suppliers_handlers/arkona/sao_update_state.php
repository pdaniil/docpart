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

    $nameMethod 	= 'rc_getstatusorderout';
    $login_name 	= $login; // логин для авторизации на сайте eOrder
    $login_password = md5($password); // пароль для авторизации на сайте eOrder

    //Специальные параметры
    $sao_state_object = json_decode($order_item["sao_state_object"], true);//Параметры для SAO
    $idrowsale = $sao_state_object["idrowsale"];
    
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
<paramorder>
    <row>
        <listidorder>$idrowsale</listidorder>
     </row>
</paramorder>
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

        if(isset($sao_output["idstatuslogistic"])) {

            $sao_output_nstatuslogistic = '';
            $sao_output_idstatuslogistic = (string) $sao_output["idstatuslogistic"];

            if(is_array($sao_output["nstatuslogistic"])) {

                $sao_output_nstatuslogistic = json_decode($sao_output["nstatuslogistic"]);

            } else {
                $sao_output_nstatuslogistic = (string) $sao_output["nstatuslogistic"];
            }

            if(empty($sao_output_nstatuslogistic)) {
                $sao_output_nstatuslogistic = 'Пустой ответ';
            }

            if($sao_output_idstatuslogistic == 630) {

                //Определяем, новый статус позиции, который должен быть автоматически назначен для нового SAO-состояния (8 - ID SAO-состояния "Отказано")
				$new_status_query = $db_link->prepare("SELECT `status_id` FROM `shop_sao_states` WHERE `id` = ?;");
				$new_status_query->execute( array(8) );
				
				
                $new_status_record = $new_status_query->fetch();
                $new_status = $new_status_record["status_id"];
                
                if($new_status > 0)
                {
                    //Отправляем запрос на изменение статуса позиции
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $DP_Config->domain_path."content/shop/protocol/set_order_item_status.php?initiator=2&orders_items=[".$order_item["id"]."]&status=".$new_status."&key=".$DP_Config->tech_key);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    $curl_result = curl_exec($ch);
                    $error = curl_error($ch);
                    curl_close($ch);
                }

                $SQL = "
                    UPDATE 
                        `shop_orders_items`
                    SET 
                        `sao_message` = ?,
                        `sao_state` = ?
                    WHERE 
                        `id` = ?;
                ";
				$binding_args = array( $sao_output_nstatuslogistic."<br/> Код статуса: ".$sao_output_idstatuslogistic."<br/> ".date("d.m.Y H:i:s", time()), 8, $order_item_id );
            } 
			else 
			{

                $SQL = "
                    UPDATE 
                        `shop_orders_items` 
                    SET 
                        `sao_message` = ?
                    WHERE 
                        `id` = ?;
                ";
				$binding_args = array( $sao_output_nstatuslogistic."<br/> Код статуса:".$sao_output_idstatuslogistic."<br/> ".date("d.m.Y H:i:s", time()), $order_item_id );
            }

                        
            //Записываем данные в позицию (строку ответа от поставщика и отображаемый комментарий)
            if ( !  $db_link->prepare($SQL)->execute($binding_args)  ) {
                
                $error = "Произошёл сбой при обновлении sao-статуса!";
                throw new Exception($error);
                
            }
            
            $sao_result["status"] = true;



        } else {

            $sao_result["status"] = false;
	        $sao_result["message"] = "Неизвестная ошибка запроса.";
            
        }
    }
}

catch (SoapFault $fault) { 
    $sao_result["status"] = false;
	$sao_result["message"] = "Ошибка SOAP запроса.";
}
catch (Exception $e) {
    $sao_result["status"] = false;
	$sao_result["message"] = $e->getMessage();
}
    

?>