<?php
/**
 * Скрипт для обработки данных в личном кабинете Росско
*/

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


$result = array(
    'status'  => true,
    'message' => '',
    'html'    => '',
    'data'    => array()
);

//Получаем данные
$request_object = json_decode($_POST['request_object'], true);

switch($request_object['action']) {
    case 'get_data':
        
        try
        {
            if(!(isset($request_object["key1"]) && !empty($request_object["key1"]))) {
                throw new Exception("Отсутствует key1 для подключения к Rossko.");
            }
            if(!(isset($request_object["key2"]) && !empty($request_object["key2"]))) {
                throw new Exception("Отсутствует key2 для подключения к Rossko.");
            }
            
            /*****Учетные данные*****/
            $KEY1 = $request_object["key1"];
            $KEY2 = $request_object["key2"];
            /*****Учетные данные*****/
            
            //Создание объекта клиента
            $connect = array(
                'wsdl'    => 'http://api.rossko.ru/service/v2.1/GetCheckoutDetails',
                'options' => array(
                    'connection_timeout' => 1,
                    'trace' => true
                )
            );
            
            $param = array(
                'KEY1' => $KEY1,
                'KEY2' => $KEY2
            );
        
        	$query  = new SoapClient($connect['wsdl'], $connect['options']);
        	$query_result = $query->GetCheckoutDetails($param);
        	
        	$result['data'] = $query_result;
        	
        }
        catch (SoapFault $e)//Не можем создать клиент SOAP
        {
            $result['status'] = false;
        	$result['message'] = "Ошибка soap: " . $e->getMessage();
        }
        catch (Exception $e)//Не можем создать клиент SOAP
        {
            $result['status'] = false;
        	$result['message'] = "Ошибка: " . $e->getMessage();
        }
        
        //Формируем ответ для вывода
        if($result['status']) {
            
            $connection_options = isset($request_object["connection_options"]) ? $request_object["connection_options"] : array();
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Rossko Личный кабинет
        		</div>
        		<div class=\"panel-body\">";

            if(!empty($result['data'])) {
                
                $rossko_data = $result['data']->CheckoutDetailsResult;
                
                //Список компаний
                $company = array();
                if(!is_array($rossko_data->CompanyList->company)) {
                    $company[] = $rossko_data->CompanyList->company;
                } else {
                    $company = $rossko_data->CompanyList->company;
                }
                if(!empty($company)) {
                    $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Компания</label>
                		        <div class=\"col-lg-6\">
                		            <select class=\"form-control\" id=\"requisite_id\">";
                		            
                		            foreach($company as $company_item) {
                    		              $is_selected = false;
                    		              $short_requisite = mb_substr($company_item->requisite, 0, 50, 'UTF-8');
                    		              $short_requisite = $short_requisite == "" ? "Реквизиты не заданы. Нужно указать реквизиты в ЛК"  : $short_requisite . "...";
                    		              if(!empty($connection_options) && isset($connection_options['requisite_id']) && $connection_options['requisite_id'] == $company_item->id) $is_selected = true;
                    		              $selected = $is_selected ? 'selected' : '' ;
                    		              $result['html'] .= "<option value=\"" . $company_item->id . "\" " . $selected . " title=\"(Реквизиты: " . $short_requisite . ")\">" . $company_item->name . "</option>";
                    		        }
                    		        
                   $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
                }
                
                //Тип доставки
                $delivery = array();
                if(!is_array($rossko_data->DeliveryType->delivery)) {
                    $delivery[] = $rossko_data->DeliveryType->delivery;
                } else {
                    $delivery = $rossko_data->DeliveryType->delivery;
                }
                if(!empty($delivery)) {
                    $result['html'] .= "<div class=\"form-group\">
                            <label class=\"col-lg-6 control-label\">Тип доставки</label>
            		        <div class=\"col-lg-6\">
            		            <select class=\"form-control\" id=\"delivery_id\">";
            		            
            		          foreach($delivery as $delivery_item) {
            		              $is_selected = false;
            		              if(!empty($connection_options) && isset($connection_options['delivery_id']) && $connection_options['delivery_id'] == $delivery_item->id) $is_selected = true;
            		              $selected = $is_selected ? 'selected' : '' ;
            		              $result['html'] .= "<option value=\"" . $delivery_item->id . "\" " . $selected . ">" . $delivery_item->name . "</option>";
            		          }  
            		                
            		 $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>";
                }
                
                //Адрес доставки
                $address = array();
                if(!is_array($rossko_data->DeliveryAddress->address)) {
                    $address[] = $rossko_data->DeliveryAddress->address;
                } else {
                    $address = $rossko_data->DeliveryAddress->address;
                }
                if(!empty($address)) {
                    $result['html'] .= "<div class=\"form-group\">
                                    <label class=\"col-lg-6 control-label\">Адрес доставки - Город</label>
                    		        <div class=\"col-lg-6\">
                    		             <select class=\"form-control\" id=\"address_id\">";
                    		             
                    		              foreach($address as $address_item) {
                    		                  $delivery_item_name = $address_item->city ." ". $address_item->street ." ". $address_item->house ." ". $address_item->office;
                        		              $is_selected = false;
                        		              if(!empty($connection_options) && isset($connection_options['address_id']) && $connection_options['address_id'] == $address_item->id) $is_selected = true;
                        		              $selected = $is_selected ? 'selected' : '' ;
                        		              $result['html'] .= "<option value=\"" . $address_item->id . "\" " . $selected . ">" . $delivery_item_name . "</option>";
                        		          } 
                        		
                    $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>"; 
        		        
                }

                //Тип оплаты
                $payment = array();
                if(!is_array($rossko_data->PaymentType->payment)) {
                    $payment[] = $rossko_data->PaymentType->payment;
                } else {
                    $payment = $rossko_data->PaymentType->payment;
                }
                if(!empty($payment)) {
                    $result['html'] .= "<div class=\"form-group\">
                            <label class=\"col-lg-6 control-label\">Тип оплаты</label>
            		        <div class=\"col-lg-6\">
            		            <select class=\"form-control\" id=\"payment_id\">";
            		            
            		          foreach($payment as $payment_item) {
            		              $is_selected = false;
            		              if(!empty($connection_options) && isset($connection_options['payment_id']) && $connection_options['payment_id'] == $payment_item->id) $is_selected = true;
            		              $selected = $is_selected ? 'selected' : '' ;
            		              $result['html'] .= "<option value=\"" . $payment_item->id . "\" " . $selected . ">" . $payment_item->name . "</option>";
            		          }  
            		                
            		 $result['html'] .= "</select>
            		        </div>
        		        </div>
        		        <div class=\"hr-line-dashed col-lg-12\"></div>";
                }
                
                //ФИО покупателя
                $name = '';
                if(!empty($connection_options) && isset($connection_options['delivery_name'])) $name = $connection_options['delivery_name'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">ФИО покупателя</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"delivery_name\" value=\"" . $name . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            		        
            	//Контактный номер
                $phone = '';
                if(!empty($connection_options) && isset($connection_options['delivery_phone'])) $phone = $connection_options['delivery_phone'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Контактный номер</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"delivery_phone\" value=\"" . $phone . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            		        
                
                //Контактный номер
                $comment = '';
                if(!empty($connection_options) && isset($connection_options['delivery_comment'])) $comment = $connection_options['delivery_comment'];

                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Комментарий к заказу для оператора (необяз.)</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"text\" id=\"delivery_comment\" value=\"" . $comment . "\" />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";
            		        
            		        
                //Доставлять заказ по частям или нет
                $delivery_parts = false;
                if(!empty($connection_options) && isset($connection_options['delivery_parts'])) $delivery_parts = $connection_options['delivery_parts'];

                $checked = $delivery_parts ? "checked" : "";
                $result['html'] .= "<div class=\"form-group\">
                                <label class=\"col-lg-6 control-label\">Доставлять заказ по частям или нет</label>
                		        <div class=\"col-lg-6\">
                		            <input class=\"form-control\" type=\"checkbox\" id=\"delivery_parts\" $checked />
                		        </div>
                		    </div>
            		        <div class=\"hr-line-dashed col-lg-12\"></div>";

            }
        		        
        		        
        	$result['html'] .= "</div></div>";
            
        } else {
            
            $result['html'] = "<div class=\"hpanel\">
        		<div class=\"panel-heading hbuilt\">
        			Настройки Rossko Личный кабинет
        		</div>
        		<div class=\"panel-body\"><div class=\"alert alert-danger\">" . $result['message'] . "</div></div>
        	</div>";
        	
        }
    
    break;
    
    default:
        $result['status'] = false;
        $result['message'] = 'Неизвестное действие';
}




exit(json_encode($result));