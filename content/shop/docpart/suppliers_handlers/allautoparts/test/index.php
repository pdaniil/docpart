<?php
/**
* Пример реализации работы с SOAP-сервисами компании Автостелс на языке PHP.
*
* Данный скрипт реализует:
* • работу с сервисом поиска ценовых предложений.
* • размещение предложения в корзине
*
* Системные требования
* •	PHP5
* •	Установленный модуль SOAP (обычно входит в дистрибутив PHP5)
* •	Установленный модуль openssl (обычно входит в дистрибутив PHP5)
* •	Установленный модуль SimpleXML
*
* © Autostels
*/


/**
 * Вспомогательные функции
 */

   /**
    * generateRandom
    *
    * Генерирует случайную строку из чисел заданой длины
    *
    * @param int $maxlen длина строки
    * @return string
    */
   function generateRandom($maxlen = 32) {
      $code = '';
      while (strlen($code) < $maxlen) {
         $code .= mt_rand(0, 9);
      }
      return $code;
   }

	/**
    * validateData
    *
    * Фунцкия производит проверку и подготовку данных для отправки в запрос
    *
    * @param &array $data ссылка на ассоц. массив с данными
    * @param &array $errors ссылка на массив ошибок
    * @return true в случае, если данные корректны, false при ошибке
    */
	function validateData(&$data, &$errors) {
		if (!$data['search_code'])
			$errors[] = 'Необходимо ввести номер для поиска';

		if (!$data['session_id'])
			$errors[] = 'Необходимо указать ID входа для работы с сервисом';

		if ((!$data['session_login'] || !$data['session_password']) && !$data['session_guid'])
			$errors[] = 'Необходимо ввести логин и пароль'.$data['session_guid'];

		$data['instock'] = $data['instock'] ? 1 : 0;
		$data['showcross'] = $data['showcross'] ? 1 : 0;
		$data['periodmin'] = $data['periodmin'] ? (int)$data['periodmin'] : -1;
		$data['periodmax'] = $data['periodmax'] ? (int)$data['periodmax'] : -1;

		return count($errors) ? false : true;
	}

	/**
    * createSearchRequestXML
    *
    * Генерация строки запроса на поиск
    *
    * @param &array $data ссылка на ассоц. массив с данными
    * @return string возвращает строку с XML
    */
	function createSearchRequestXML($data) {
		$session_info = $data['session_guid'] ?
			'SessionGUID="'.$data['session_guid'].'"' :
			'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';

		$xml = '<root>
				  <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.'/>
				  <search>
					 <skeys>
						<skey>'.$data['search_code'].'</skey>
					 </skeys>
					 <instock>'.$data['instock'].'</instock>
					 <showcross>'.$data['showcross'].'</showcross>
					 <periodmin>'.$data['periodmin'].'</periodmin>
					 <periodmax>'.$data['periodmax'].'</periodmax>
				  </search>
				</root>';
		return $xml;
	}

   /**
    * createAddBasketRequestXML
    *
    * Генерация строки запроса на добавление в корзину
    *
    * @param &array $data ссылка на ассоц. массив с данными
    * @return string возвращает строку с XML
    */
   function createAddBasketRequestXML($data) {
      $session_info = $data['session_guid'] ?
			'SessionGUID="'.$data['session_guid'].'"' :
			'UserLogin="'.base64_encode($data['session_login']).'" UserPass="'.base64_encode($data['session_password']).'"';
		$xml = '<root>
                 <SessionInfo ParentID="'.$data['session_id'].'" '.$session_info.' />
                 <rows>
                    <row>
                        <Reference>'.$data['Reference'].'</Reference>
                        <AnalogueCodeAsIs>'.$data['AnalogueCodeAsIs'].'</AnalogueCodeAsIs>
                        <AnalogueManufacturerName>'.$data['AnalogueManufacturerName'].'</AnalogueManufacturerName>
                        <OfferName>'.$data['OfferName'].'</OfferName>
                        <LotBase>'.$data['LotBase'].'</LotBase>
                        <LotType>'.$data['LotType'].'</LotType>
                        <PriceListDiscountCode>'.$data['PriceListDiscountCode'].'</PriceListDiscountCode>
                        <Price>'.$data['Price'].'</Price>
                        <Quantity>'.$data['Quantity'].'</Quantity>
                        <PeriodMin>'.$data['PeriodMin'].'</PeriodMin>
                        <ConstraintPriceUp>-1</ConstraintPriceUp>
                        <ConstraintPeriodMinUp>-1</ConstraintPeriodMinUp>
                    </row>
                 </rows>
				</root>';
		return $xml;
   }

	/**
    * parseSearchResponseXML
    *
    * Разбор ответа сервиса поиска.
    *
	 * Собственно просто преобразует данные из SimpleXMLObject в массив,
    * также добавляет к каждой записи уникальный ReferenceID. В данном примере
    * в этом качестве будет выступать случайным образом сгенерированная строка.
    * В реальном использовании Reference обозначает ID конкретной записи в контексте
    * системы, в которой используются сервисы (например, id из таблицы БД, с которой
    * сопоставлено предложение)
	 *
    * @param SimpleXMLObject XML-объект
    * @return array возвращает массив данных
    */
	function parseSearchResponseXML($xml) {
		$data = array();
		foreach($xml->rows->row as $row) {
			$_row = array();
			foreach($row as $key => $field) {
				$_row[(string)$key] = (string)$field;
			}
         $_row['Reference'] = generateRandom(9);
			$data[] = $_row;
		}
		return $data;
	}


   /**
    * parseAddBasketResponseXML
    *
    * Разбор ответа сервиса добавления в корзину.
    * Ответ содержит набор строк с результатами размещения выбранные позиций
    * В этом примере разбор ответа сводится к простой конвертации результата в массив.
    * Интерпретация и вывод результата происходит в файле /html/result_basket.html
    *
    * @param SimpleXMLObject $xml XML-объект
    * @return array возвращает массив с результатами
    */
   function parseAddBasketResponseXML($xml) {
      $data = array();
		foreach($xml->rows->row as $row) {
			$_row = array();
			foreach($row as $key => $field) {
				$_row[(string)$key] = (string)$field;
			}
			$data[] = $_row;
		}
		return $data;
   }

/**
 * Основное тело скрипта
 */
 
   //Перехватчик для отображения информации phpinfo
   if(isset($_GET['phpinfo'])) {
      header("Content-Type: text/html; charset=utf-8");
      $url = $_SERVER['REQUEST_URI'];
      $url = explode('?',$url)[0];
      ob_start();
      phpinfo();
      $text = ob_get_clean();
      echo preg_replace('/<h1 class="p">(PHP Version [0-9\.]+)<\/h1>/','<h1 class="p">$1 <br><a href="'.$url.'">&lt;&lt;&lt;Вернуться назад</a></h1>',$text);
      exit;
   }

   //Проверка загружены ли необходимые расширения
   $ext_soap = extension_loaded('soap');
   $ext_openssl = extension_loaded('openssl');
   $ext_SimpleXML = extension_loaded('SimpleXML');
   if (!($ext_soap && $ext_openssl && $ext_SimpleXML)) {
      $errors = array();
      $errors[] = 'Отсутствуют необходимые расширения PHP';
      $errors[] = 'Вы можете посмотреть полную информацию о конфигурации Вашего PHP <a href="?phpinfo">phpinfo();</a>';
      if (!$ext_soap) {
         $errors[] = 'Отсутствует расширение soap необходимое для работы примера.';
         $errors[] = 'Строка extension=php_soap.dll в php.ini на Windows';
         $errors[] = 'Строка extension=soap.so в php.ini на Linux';
      }
      if (!$ext_openssl) {
         $errors[] = 'Отсутствует расширение openssl необходимое для работы примера.';
         $errors[] = 'Строка extension=php_openssl.dll в php.ini на Windows';
         $errors[] = 'На Linux данное расширение использует библиотеку OpenSSL';
         $errors[] = 'и компилируется как часть основного модуля';
         $errors[] = 'Посмотрите как была выполнена компиляция PHP выполнив <a href="?phpinfo">phpinfo();</a> раздел Configure Command';
         $errors[] = 'Возможно вам придется собрать PHP самостоятельно включив этот модуль, или взять другой дистрибутив.';
      }
      if (!$ext_SimpleXML) {
         $errors[] = 'Отсутствует расширение SimpleXML необходимое для работы примера.';
         $errors[] = 'SimpleXML компилируется как часть основного исполняемого модуля PHP';
         $errors[] = 'Посмотрите как была выполнена компиляция PHP выполнив <a href="?phpinfo">phpinfo();</a> раздел Configure Command';
         $errors[] = 'Возможно вам придется собрать PHP самостоятельно включив этот модуль, или взять другой дистрибутив.';
      }
      header("Content-Type: text/html; charset=utf-8");
      //Шапка страницы
      include('html/header.html');
      //Блок ошибок (при их наличии)
		include('html/error.html');
      //Подвал страницы
      include('html/footer.html');
      exit();
   }

   if(isset($_GET['myip'])) {
      header("Content-Type: text/html; charset=utf-8");
      $url = $_SERVER['REQUEST_URI'];
      $url = explode('?',$url)[0];
      require_once("lib/soap_transport.php");
      $SOAP = new soap_transport();
      $errors = array();
      $IP = $SOAP->getIP($errors);
      //Шапка страницы
      include('html/header.html');
      if ($IP) {
         $IP = $IP->GetRequestIPResult;
      }
      echo "<a href=\"$url\">Вернуться назад</a></br>Ваш IP адрес $IP";
      //Подвал страницы
      include('html/footer.html');
      exit;
   }
   
   
	//Обработка входных данных:
	//Значения формы по-умолчанию
	$defaults = array(
		'session_id' => '',
		'session_guid' => '',
		'session_login' => '',
		'session_password' => '',
		'search_code' => 'OC47',
		'instock' => 'ON',
		'showcross' => '',
		'periodmin' => 0,
		'periodmax' => 10,
	);

	//Получение POST данных
	$data = isset($_POST['session_id']) ? array_merge($defaults, $_POST) : $defaults;

   //Поиск:
   $action = isset($_POST['do']) ? $_POST['do'] : FALSE;

	if ($action !== FALSE) {
		//Нажата одна из кнопок на форме
		switch($action) {
			//Нажата кнопка "Поиск"
			case 'search':
				$errors = array();
				$parsed_data = $data;	//Данные из формы копируются в другую переменную, чтобы
												//подготовить их для формирования запроса.
												//Исходные данные будут отображены на форме.

				//Проверка данных
				if (validateData($parsed_data, $errors)) {
					//Подключение класса SOAP-клиента и создание экземпляра
					require_once("lib/soap_transport.php");
					$SOAP = new soap_transport();

					//Генерация запроса
					$requestXMLstring = createSearchRequestXML($parsed_data);

					//Выполнение запроса
					$responceXML = $SOAP->query('SearchOffer', array('SearchParametersXml' => $requestXMLstring), $errors);
               //Пожалуйста обратите внимание что параметр именованный - SearchParametersXml
               //Для разных методов сервисов это имя параметра разное и в документации оно нигде не описано
               //Для того, чтобы узнать имя параметра следует смотреть WSDL схему
               /*
               Вот примерный порядок действий чтобы узнать имя параметра:
               1. Открываем WSDL схему документа броузером, например, Google Chrome
               Для этого открываем URL https://allautoparts.ru/WEBService/SearchService.svc/wsdl?wsdl
               2. Находим строки 
               <xsd:schema targetNamespace="http://tempuri.org/Imports">
                  <xsd:import schemaLocation="https://allautoparts.ru/WEBService/SearchService.svc/wsdl?xsd=xsd0" namespace="http://tempuri.org/"/>
                  <xsd:import schemaLocation="https://allautoparts.ru/WEBService/SearchService.svc/wsdl?xsd=xsd1" namespace="http://schemas.microsoft.com/2003/10/Serialization/"/>
               </xsd:schema>
               3. Открываем url https://allautoparts.ru/WEBService/SearchService.svc/wsdl?xsd=xsd0
               4. Находим строки соответствующие методу, который вызываем и узнаем имя параметра
               <xs:element name="SearchOffer">
                  <xs:complexType>
                     <xs:sequence>
                        <xs:element minOccurs="0" name="SearchParametersXml" nillable="true" type="xs:string"/>
                     </xs:sequence>
                  </xs:complexType>
               </xs:element>               
               */
               

					//Получен ответ
					if ($responceXML) {
						//Установка параметра session_guid, полученного из ответа сервиса.
						//Параметр используется, как замена связке session_login + session_password,
						//и при повторном поиске может быть подставлен в запрос вместо неё
						$attr = $responceXML->rows->attributes();
						$data['session_guid'] = (string)$attr['SessionGUID'];

						//Разбор данных ответа
						$result = parseSearchResponseXML($responceXML);
					}
				}
			break;

         //Нажата кнопка "Добавить в корзину"
         case 'add_basket':
            //Получение POST данных (в примере используется одиночное добавление записей,
            //но метод допускает добавление множества позиций за раз)
            $defaults = array(
               'session_id' => '',
               'session_guid' => '',
               'session_login' => '',
               'session_password' => '',
               'Reference' => '',
               'AnalogueCodeAsIs' => '',
               'AnalogueManufacturerName' => '',
               'OfferName' => '',
               'LotBase' => 1,
               'LotType' => 0,
               'PriceListDiscountCode' => 1,
               'Price' => 0,
               'Quantity' => 1,
               'PeriodMin' => 1,
               'ConstraintPriceUp' => -1,
               'ConstraintPeriodMinUp' => -1,
            );
            $parsed_data = array_merge($defaults, $_POST);

            require_once("lib/soap_transport.php");
            $SOAP = new soap_transport();

            //Генерация запроса
            $requestXMLstring = createAddBasketRequestXML($parsed_data);

            //Выполнение запроса
            $responceXML = $SOAP->query('AddBasket', array('AddBasketXml' => $requestXMLstring), $errors);

            //Разбор данных ответа
            if ($responceXML)
               $basket_result = parseAddBasketResponseXML($responceXML);


         break;

			//Нажата кнопка "Сбросить параметры"
			case 'reset':
				$data = $defaults;
			break;
		}
	}

/**
 * Вывод
 */

	header("Content-Type: text/html; charset=utf-8");

	//Шапка страницы
	include('html/header.html');

	//Форма поиска
	include('html/form.html');

   //Если произведено добавление в корзину
   if ($action == 'add_basket') {
      if (count($errors))
			//Блок ошибок (при их наличии)
			include('html/error.html');
		else		{
			//Блок результатов добавления в корзину
			include('html/result_basket.html');
      }
   }

	//Если производится поиск
	if($action == 'search') {
		if (count($errors))
			//Блок ошибок (при их наличии)
			include('html/error.html');
		else
			//Блок результатов поиска
			include('html/result.html');
	}

	//Подвал страницы
	include('html/footer.html');
?>

