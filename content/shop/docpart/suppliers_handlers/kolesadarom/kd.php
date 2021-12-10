<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 12.09.17
 * Time: 14:09
 */
require('connect.php');

class kd
{
    protected static $URL = array (
        'search' => 'https://apiopt.kolesa-darom.ru/v2/search',
        'order' => 'https://apiopt.kolesa-darom.ru/v2/order',
        'work'=> 'https://apiopt.kolesa-darom.ru/v2/order/work',
        'motion' => 'https://apiopt.kolesa-darom.ru/v2/motion',
        'stores' => 'https://apiopt.kolesa-darom.ru/v2/client/stores',
        'addresses' => 'https://apiopt.kolesa-darom.ru/v2/client/addresses',
        'limit' => 'https://apiopt.kolesa-darom.ru/v2/client/limit',
        'shipping' => 'https://apiopt.kolesa-darom.ru/v2/client/shipping',
    );


    /**
     *   метод поиска товаров
     * @param string $token - токен пользователя, для подключения к api
     * @param array $data - массив с данными для поиска
     * @return mixed
     */
    public static function search($token, array $data)
    {
        return self::connect()->get($token,self::$URL['search'],$data);
    }

    /**
     *  метод для совершения заказа
     * @param string $token - токен пользователя, для подключения к api
     * @param array $data - массив с данными для заказа
     * @return mixed
     */
    public static function order($token, array $data)
    {
        return self::connect()->post($token,self::$URL['order'],$data);
    }

    /**
     *  метод для поставновки заказа в отгрузку из резерва
     * @param string $token - токен пользователя, для подключения к api
     * @param array $data - номер заказа
     * @return mixed
     */
    public static function work($token, array $data)
    {
        return self::connect()->get($token,self::$URL['work'],$data);
    }

    /**
     *  метод для получения информации о заказе
     * @param string $token  - токен пользователя, для подключения к api
     * @param array $data  - номер заказа
     * @return mixed
     */
    public static function motion($token, array $data)
    {
        return self::connect()->get($token,self::$URL['motion'],$data);
    }

    /**
     *  метод для получения списка магазинов в городе, который указан в профиле,
     *  с которых разрешен самовывоз, если разрешен самовывоз пользователю
     * @param string $token - токен пользователя, для подключения к api
     * @return mixed
     */
    public static function stores($token)
    {
        return self::connect()->get($token,self::$URL['stores']);
    }

    /**
     *  метод для получения списка адресов доставки, указанные в профиле
     * @param string $token - токен пользователя, для подключения к api
     * @return mixed
     */
    public static function addresses($token)
    {
        return self::connect()->get($token,self::$URL['addresses']);
    }


    /**
     *  метод для получния лимита, если пользователь работает по отсрочке
     * @param $token - токен пользователя, для подключения к api
     * @return string mixed
     */
    public static function limit($token)
    {
        return self::connect()->get($token,self::$URL['limit']);
    }

    /**
     *  метод для определения доступен самовывоз/доставка
     * @param string $token - токен пользователя, для подключения к api
     * @return mixed
     */
    public static function shipping($token)
    {
        return self::connect()->get($token,self::$URL['shipping']);
    }

    /**
     * метод возвращает класс для соединения с сервером
     * @return connect
     */
    private static function connect()
    {
        return new connect();
    }

}
