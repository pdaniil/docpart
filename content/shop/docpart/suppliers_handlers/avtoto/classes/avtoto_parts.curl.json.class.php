<?php

/*
 * Сервис поиска предложений на сайте avtoto.ru
 * Редакция: 2018.09.28
*/

/**
 * Class avtoto_parts_curl_json
 * Сервис поиска предложений на сайте avtoto.ru
 * Запрос cURL, ответ JSON
 * Редакция: 2018.09.28
 */
class avtoto_parts_curl_json {

    private $errors;
    private $params;
    private $start_result;
    private $curl;

    private $response_wait_first_periods = array(
        0.3,
        0.3,
        0.3,
        0.3,
        0.3
    ); //seconds
    private $response_wait_period = 0.5; //seconds

    private $search_extension_time_limit = 10; //seconds

    private $progress_list = array(
        '2' => 'Ожидает оплаты',
        '1' => 'Ожидает обработки',
        '3' => 'Заказано',
        '4' => 'Закуплено',
        '5' => 'В пути',
        '6' => 'На складе',
        '7' => 'Выдано',
        '8' => 'Нет в наличии'
    );


    //------------------------------------------------------------------------
    public function __construct($params = array()) {
        $this->errors = array();
        $this->search_params = array();
        $this->start_result = array();
        $this->curl = new curl_server();

        if ($params) {
            $this->set_params($params);
        }
    }

    //------------------------------------------------------------------------
    public function set_params($params) {
        if (isset($params['user_id']) && (int)$params['user_id'] && isset($params['user_login']) && $params['user_login'] && isset($params['user_password']) && $params['user_password']) {
            $this->params = $params;
        } else {
            $this->errors[] = $this->error('wrong_params');

        }
    }

    //------------------------------------------------------------------------
    public function set_search_extension_time_limit($time_secods) {
        if ((int)$time_secods) {
            $this->search_extension_time_limit = (int)$time_secods;
        }
    }

    //------------------------------------------------------------------------
    public function get_parts($code, $analogs = 'on', $limit = 0) {
        $this->reset_errors();

        if (trim($code)) {
                if ($this->params) {

                    $params = $this->params;
                    $params['search_code'] = trim($code);
                    $params['search_cross'] = $analogs == 'on' || $analogs == 1 ? $analogs : 'off';

                    $result_for_listener = $this->curl->SearchStart($params);
                    if ($result_for_listener) {
                        if (isset($result_for_listener['Info']) && isset($result_for_listener['Info']['Errors']) && $result_for_listener['Info']['Errors']) {
                            $this->errors[] = $result_for_listener['Info']['Errors'];
                        } else {
                            if ((int)$limit) {
                                $result_for_listener['Limit'] = (int)$limit;
                            }
                            return $this->get_results_from_listener($result_for_listener);
                        }
                    }
                }
        } else {
            $this->errors[] = $this->error('error code');
        }
    }

    //------------------------------------------------------------------------
    public function get_parts_brand($code, $brand, $limit = 0, $analogs = 'off') {
        $this->reset_errors();

        if (trim($code)) {
            if ($this->curl) {
                if ($this->params) {

                    $params = $this->params;
                    $params['search_code'] = trim($code);
                    $params['brand'] = trim($brand);
                    $params['search_cross'] = ($analogs == 'on' || (int)$analogs == 1 ? 'on' : 'off');

                    $result_for_listener = $this->curl->SearchStart($params);

                    if ($result_for_listener) {

                        if ((int)$limit) {
                            $result_for_listener['Limit'] = (int)$limit;
                        }

                        return $this->get_results_from_listener($result_for_listener);
                    }
                }
            } else {
                $this->errors[] = $this->error('cannot create client');
            }
        } else {
            $this->errors[] = $this->error('error code');
        }
    }

    //------------------------------------------------------------------------
    public function get_brands_by_code($code) {
        $this->reset_errors();

        if (trim($code)) {
            if ($this->curl) {
                if ($this->params) {
                    $params = $this->params;
                    $params['search_code'] = trim($code);

                    return $this->curl->GetBrandsByCode($params);
                }
            } else {
                $this->errors[] = $this->error('cannot create client');
            }
        } else {
            $this->errors[] = $this->error('error code');
        }
    }

    //------------------------------------------------------------------------
    public function add_to_basket($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $add_params['user'] = $this->params;
                    $add_params['parts'] = $parts;
                    return $this->curl->AddToBasket($add_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }

    //------------------------------------------------------------------------
    public function delete_from_basket($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $delete_params['user'] = $this->params;
                    $delete_params['parts'] = $parts;

                    return $this->curl->DeleteFromBasket($delete_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }

    //------------------------------------------------------------------------
    public function update_count_in_basket($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $update_params['user'] = $this->params;
                    $update_params['parts'] = $parts;

                    return $this->curl->UpdateCountInBasket($update_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }


    //------------------------------------------------------------------------
    public function check_availability_in_basket($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $check_params['user'] = $this->params;
                    $check_params['parts'] = $parts;

                    return $this->curl->CheckAvailabilityInBasket($check_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }

    //------------------------------------------------------------------------
    public function add_to_orders_from_basket($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $add_params['user'] = $this->params;
                    $add_params['parts'] = $parts;

                    return $this->curl->AddToOrdersFromBasket($add_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }


    //------------------------------------------------------------------------
    public function get_orders_status($parts) {
        $this->reset_errors();
        if ($this->curl) {
            if ($parts) {

                if ($this->check_parts($parts, __FUNCTION__)) {

                    $get_params['user'] = $this->params;
                    $get_params['parts'] = $parts;

                    return $this->curl->GetOrdersStatus($get_params);
                }
            }
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }


    //------------------------------------------------------------------------
    public function get_stat_search() {
        $this->reset_errors();
        if ($this->curl) {
            return $this->curl->GetStatSearch($this->params);
        } else {
            $this->errors[] = $this->error('cannot create client');
        }
    }


    //------------------------------------------------------------------------
    public function get_progress_text($status_int) {
        return isset($this->progress_list[$status_int]) ? $this->progress_list[$status_int] : '';
    }

    //------------------------------------------------------------------------
    public function get_errors() {
        return $this->errors;
    }

    //------------------------------------------------------------------------
    private function error($key) {
        switch ($key) {
            case 'cannot create client':
                return 'Не получилось соединиться с сервером';
            case 'no result':
                return 'Сервер не ответил';
            case 'wrong params':
                return 'Неверные параметры соединения';
            case 'wrong parts':
                return 'Ошибка данных';
            case 'error code':
                return 'Неверный артикул';
        }
    }

    private function reset_errors() {
        $this->errors = array();
    }

    //------------------------------------------------------------------------
    private function get_results_from_listener($result_for_listener) {

        $start_time = microtime(1);
        $result['Info']['SearchStatus'] = 2; //В обработке

        $sleep_count = 0;

        while (microtime(1) - $start_time < $this->search_extension_time_limit && isset($result['Info']['SearchStatus']) && $result['Info']['SearchStatus'] == 2) {

            $sleep_ms = 1000000 * (float)$this->response_wait_period;
            if (isset($this->response_wait_first_periods[$sleep_count])) {
                $sleep_ms = 1000000 * (float)$this->response_wait_first_periods[$sleep_count];
            }

            usleep($sleep_ms);

            $sleep_count++;
            $result = $this->curl->SearchGetParts2($result_for_listener);

        }

        if ($result === array()) {
            $this->errors[] = $this->error('no result');
        }
        return $result;
    }


    //------------------------------------------------------------------------
    private function check_parts($parts, $mode) {

        if (is_array($parts)) {
            if (!isset($parts[0])) {
                $real_parts[0] = $parts;
            } else {
                $real_parts = $parts;
            }

            unset($parts);
            $errors = array();

            switch ($mode) {

                case 'add_to_basket': {
                    foreach ($real_parts as $i => $part) {
                        if (isset($part['PartId']) && is_numeric((int)$part['PartId']) && isset($part['SearchID']) && (int)$part['SearchID'] && isset($part['RemoteID']) && $part['RemoteID'] && isset($part['Count']) && (int)$part['Count']) {
                            //void
                        } else {
                            $errors[] = $i;
                        }
                    }
                } break;

                case 'delete_from_basket':
                case 'update_count_in_basket':
                case 'add_to_orders_from_basket':
                case 'check_availability_in_basket':
                case 'get_orders_status': {
                    foreach ($real_parts as $i => $part) {
                        if (isset($part['InnerID']) && (int)$part['InnerID'] && isset($part['RemoteID']) && $part['RemoteID']) {
                            //void
                        } else {
                            $errors[] = $i;
                        }
                    }
                } break;
            }

            if ($errors) {
                $this->errors[] = $this->error('wrong parts') . ': ' . implode(', ', $errors);
            } else {
                return true;
            }
        } else {
            $this->errors[] = $this->error('wrong parts');
        }
    }
}

class curl_server {

    private $avtoto_server = 'https://www.avtoto.ru';
    private $json_endpoint = '/?soap_server=json_mode';

    public function __construct() {

    }

    public function __call($method, $request) {
        try {
            if ($method) {
                if (is_array($request) && isset($request[0])) {
                    $post_data = json_encode($request[0]);
                    if ($post_data) {
                        $request_data = array(
                            'action' => $method,
                            'data' => $post_data,
                        );
                        return $this->get_curl_data($request_data);
                    } else {
                        throw new Exception('CURL DATA ERROR: Неверный ответ сервера');
                    }
                } else {
                    throw new Exception('CURL DATA ERROR: Неверно сформирован запрос');
                }
            } else {
                throw new Exception('CURL DATA ERROR: Не указан метод запроса');
            }
        } catch (Exception $e) {
            return array(
                'Info'  => array(
                    'Errors' => array($e->getMessage()),
                )
            );
        }
    }

    private function get_curl_data($postfields) {

        $ch = curl_init($this->avtoto_server . $this->json_endpoint);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        $result = curl_exec($ch);
        $curl_error = curl_error($ch);

        if ($curl_error) {
            throw new Exception('CURL ERROR: ' . $curl_error);
        }

        if (!$result) {
            throw new Exception('CURL ERROR: Не получен ответ с сервера');
        }

        $json = json_decode($result, true);
        if (!$result) {
            throw new Exception('CURL ERROR: Ответ сервера не является JSON строкой');
        }
        return $json;
    }

}

?>