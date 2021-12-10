<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 12.09.17
 * Time: 14:25
 */


class connect
{
    //заголовки для подключения
    private $_headers = array (
        'Authorization: Bearer',
        'Accept: application/json',
        'Content-type: application/json'
    );


    /**
     * Подключение методом GET
     * @param $token
     * @param $url
     * @param null $data
     * @return mixed
     */
    public function get($token, $url, array $data=null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url.($data!=null ? '?'.$this->prepare_get_params($data) : ''));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers($token));
        $out = curl_exec($curl);
        curl_close($curl);
        return $out;
    }

    /**
     * Подключение методом POST
     * @param $token
     * @param $url
     * @param $data
     * @return mixed
     */
    public function post($token, $url, array $data)
    {
        $data = json_encode($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($this->headers($token),['Content-Length: ' . strlen($data)]));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);

        $out = curl_exec($curl);
        curl_close($curl);
        return $out;
    }

    /**
     * Подготовка параметров для GET запроса
     * @param $data
     * @return string
     */
    private function prepare_get_params(array $data)
    {
        return http_build_query($data);
    }

    private function headers($token)
    {
        $headers = $this->_headers;
        $headers[0] = $headers[0].' '.$token;
        return $headers;
    }


}
