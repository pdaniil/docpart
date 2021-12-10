<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");

//ЛОГ - ПОДКЛЮЧЕНИЕ КЛАССА
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartSuppliersAPI_Debug.php");

class faeton37_enclosure
{
	public $result;
	
	public $Products = array();//Список товаров
	
	public $url;
	
	//Технические данные - последние данные по артикулу, производителю и наименованию.
	//Если в текущей строке эти поля пустые, то объект создается на основе последних данных:
	public $last_article = "";
	public $last_manufacturer = "";
	public $last_name = "";
	
	public $storage_options;
	
	public function __construct($article, $storage_options)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$this->result = 0;//По умолчанию
        
		$this->storage_options = $storage_options;
		
		/*****Учетные данные*****/
        $login = $this->storage_options["login"];
		$password = $this->storage_options["password"];
		/*****Учетные данные*****/
		
		$this->url = "https://www.faeton37.ru";
		
		//Авторизуемся:
		if($this->login($login,$password))
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message('$this->login() вернул true');
			}
		}
		else
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка", "Не удалось авторизоваться" );
			}
			return;
		}
		
		
		//Далее делаем запрос по артикулу
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url."/search.html?article=".$article);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		//запрещаем делать запрос с помощью POST и соответственно разрешаем с помощью GET
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		//отсылаем серверу COOKIE полученные от него при авторизации
		curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$result = curl_exec($ch);
		curl_close($ch);
		
		$dom = new domDocument;
		@$dom->loadHTML($result);
		$dom->preserveWhiteSpace = false;
		$tables = $dom->getElementsByTagName('table');
		
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("CURL-запрос по артикулу ".$article, $this->url."/search.html?article=".$article, htmlentities($result), print_r($tables, true) );
		}
		
		//echo "Количество таблиц: ".$tables->length."<br>";
		
		if($tables->length == 0)
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "На HTML-странице нет таблиц. Парсить нечего" );
			}
			
			return;
		}
		else if($tables->length > 1)//На странице есть таблицы - парсим
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "На HTML-странице несколько таблиц. Формат страницы не известен" );
			}
			
			return;
		}
		else//Есть одна таблица - с ней и работаем
		{
			//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_simple_message("На HTML-странице есть одна таблица - парсим");
			}
			
			$table = $tables->item(0);
			$rows = $table->getElementsByTagName('tr');

			foreach ($rows as $row) 
			{
				$cols = $row->getElementsByTagName('td');//Получаем массив с объектами td
				if($cols->length < 3) continue;//Это заголовки таблиц
				
				//Здесь определяем тип результата (выбор производителя/таблица товаров)
				if($cols->length < 5)
				{
					//ЛОГ - [СООБЩЕНИЕ] (простое сообщение в лог)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_simple_message("Количество колонок в таблице соответсвует таблице брендов. Далее идут запросы по брендам в цикле");
					}
					
					//перебор производителей
					$a = $cols->item(0)->getElementsByTagName('a');//Получаем масси с объектами a
					if($a->length == 0)continue;
					$href = $a->item(0)->getAttribute('href');
					
					//Делаем запросы по производителям
					//Далее делаем запрос по артикулу
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $this->url.$href);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
					//запрещаем делать запрос с помощью POST и соответственно разрешаем с помощью GET
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
					//отсылаем серверу COOKIE полученные от него при авторизации
					curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
					curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
					
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
					
					$result_g = curl_exec($ch);
					
					curl_close($ch);
					
					$dom_g = new domDocument;
					@$dom_g->loadHTML($result_g);
					$dom_g->preserveWhiteSpace = false;
					$tables_g = $dom_g->getElementsByTagName('table');
					
					//ЛОГ [API-запрос] (вся информация о запросе)
					if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
					{
						$DocpartSuppliersAPI_Debug->log_api_request("Получение остатков с уточнением производителя", $this->url.$href, htmlentities($result_g), print_r($tables_g, true) );
					}
					
					if($tables_g->length == 0)
					{
						//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
						if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
						{
							$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "На HTML-странице нет таблиц. Парсить нечего" );
						}
						
						return;
					}
					else if($tables_g->length > 1)//На странице есть таблицы - парсим
					{
						//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
						if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
						{
							$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "На HTML-странице несколько таблиц. Формат страницы не известен" );
						}
						
						return;
					}
					else//Есть одна таблица - с ней и работаем
					{
						$table_g = $tables_g->item(0);
						$rows_g = $table_g->getElementsByTagName('tr');
						foreach ($rows_g as $row_g) 
						{
							$cols_g = $row_g->getElementsByTagName('td');//Получаем массив с объектами td
							if($cols_g->length < 5) continue;//Это заголовки таблиц
							//Вывод товаров
							$this->parseProductsTr($cols_g);
						}
					}
				}
				else
				{
					//Вывод товаров
					$this->parseProductsTr($cols);
				}
			}
		}
		
		
		//Обнуляем технические данные:
		$this->url = "";
		$this->last_article = "";
		$this->last_manufacturer = "";
		$this->last_name = "";
		
		//ЛОГ [РЕЗУЛЬТИРУЮЩИЙ ОБЪЕКТ - ОСТАТКИ]
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_supplier_handler_result("Список остатков", print_r($this->Products, true) );
		}
		
		$this->result = 1;
	}//~function __construct($article)
	// ------------------------------------------------------------------------------------------
	//Функция авторизации
	function login($login, $password)
	{
		//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
		$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
		
		$ch = curl_init();
		/*if(strtolower((substr($this->url,0,5))=='https')) 
		{ 	// если соединяемся с https
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		*/
		curl_setopt($ch, CURLOPT_URL, $this->url);
		// откуда пришли на эту страницу
		curl_setopt($ch, CURLOPT_REFERER, $this->url);
		// cURL будет выводить подробные сообщения о всех производимых действиях
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,"userlogin=".$login."&userpassword=".$password."&loginform=1&remember-me=1&remember=1");
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		//сохранять полученные COOKIE в файл
		curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		$result=curl_exec($ch);
		
		//ЛОГ [API-запрос] (вся информация о запросе)
		if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
		{
			$DocpartSuppliersAPI_Debug->log_api_request("Запрос на авторизацию", $this->url."<br>Метод POST<br>Поля: "."userlogin=".$login."&userpassword=".$password."&loginform=1&remember-me=1&remember=1", htmlentities($result), "Обработка далее" );
		}
		
		if( curl_errno($ch) )
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Ошибка в CURL-запросе на авторизацию", print_r(curl_error($ch), true) );
			}
			
			curl_close($ch);
			return false;
		}
		
		// Убеждаемся что произошло перенаправление после авторизации
		//if(strpos($result,"Location: home.php")===false) die('Login incorrect');
		if(strstr($result, "указанные Вами логин") === false)
		{
			curl_close($ch);
			return true;
		}
		else
		{
			//ЛОГ - [СООБЩЕНИЕ С ОШИБКОЙ]
			if($DocpartSuppliersAPI_Debug->suppliers_api_debug)
			{
				$DocpartSuppliersAPI_Debug->log_error("Есть ошибка", "Не правильные логин и/или пароль" );
			}
			
			curl_close($ch);
			return false;
		}
		//return $result;
	}//~function login($login, $password)
	// ------------------------------------------------------------------------------------------
	//Парсить таблицу товаров и сразу создавать объекты товаров
	//В данный метод поступает объект с одной строкой таблицы
	function parseProductsTr($cols)
	{
		// -----
		$manufacturer = "";//Производитель
		$article = "";//Артикул
		$name = "";//Наименование
		//Проверяем по артикулу - если есть английские буквы или цифры
		if(preg_match('@[A-z0-9]@u',$cols->item(1)->textContent))
		{
			$manufacturer = $cols->item(0)->textContent;
			$this->last_manufacturer = $cols->item(0)->textContent;
			
			$article = $cols->item(1)->textContent;
			$this->last_article = $cols->item(1)->textContent;
			
			$name = $cols->item(2)->textContent;
			$this->last_name = $cols->item(2)->textContent;
		}
		else
		{
			$manufacturer = $this->last_manufacturer;
			$article = $this->last_article;
			$name = $this->last_name;
		}
		// -----
		//Наличие
		$exist = str_replace(array('>','<','='), "", $cols->item(5)->textContent);
		// -----
		//Срок доставки
		$time_html = explode("/",str_replace(' ','',$cols->item(7)->textContent));
		// -----
		//Направление
		$storage = $cols->item(10)->textContent;
		// -----
		//Минимальный заказ
		$min_order = $cols->item(6)->textContent;
		
		
		$price = str_replace(' ','',$cols->item(4)->textContent);
		//Наценка
		$markup = $this->storage_options["markups"][(int)$price];
		if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		{
			$markup = $this->storage_options["markups"][count($this->storage_options["markups"])-1];
		}
		
		
		//Создаем объек товара и добавляем его в список:
		$DocpartProduct = new DocpartProduct($manufacturer,
			$article,
			$name,
			$exist,
			$price + $price*$markup,
			$time_html[0] + $this->storage_options["additional_time"],
			$time_html[1] + $this->storage_options["additional_time"],
			$storage,
			$min_order,
			$this->storage_options["probability"],
			$this->storage_options["office_id"],
			$this->storage_options["storage_id"],
			$this->storage_options["office_caption"],
			$this->storage_options["color"],
			$this->storage_options["storage_caption"],
			(float)$price,
			$markup,
			2,0,0,'',null,array("rate"=>$this->storage_options["rate"])
			);
		
		if($DocpartProduct->valid == true)
		{
			array_push($this->Products, $DocpartProduct);
		}
	}
	// ------------------------------------------------------------------------------------------
};//~class faeton37_enclosure


//Настройки подключения к складу
$storage_options = json_decode($_POST["storage_options"], true);
//ЛОГ - СОЗДАНИЕ ОБЪЕКТА
$DocpartSuppliersAPI_Debug = DocpartSuppliersAPI_Debug::getInstance();
//ЛОГ - ИНИЦИАЛИЗАЦИЯ ПАРАМЕТРОВ ОБЪЕКТА
$DocpartSuppliersAPI_Debug->init_object( array("storage_id"=>$storage_options["storage_id"], "api_script_name"=>__FILE__, "api_type"=>"CURL-HTTP-HTML (парсинг страницы)") );
//ЛОГ - СОЗДАНИЕ ФАЙЛА ЛОГА
$DocpartSuppliersAPI_Debug->start_log();


$ob = new faeton37_enclosure($_POST["article"], $storage_options);
exit(str_replace(array('\t','\r','\n'), '', json_encode($ob)));
?>