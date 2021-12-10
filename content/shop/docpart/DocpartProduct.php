<?php
/**
 * Определение класс товара, выдаваемого через поиск по артикулу
 * 
 * ДАННЫЙ ОБЪЕКТ может использоваться, как для product_type = 1, так и для product_type = 2
 * 
*/

require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");

class DocpartProduct
{
    //ПОЛЯ ПРОДУКТА
    public $manufacturer;//Производитель
    public $article;//Чистый артикул - используется техникой
    public $article_show;//Артикул для отображения
    public $name;//Наименование
    public $exist;//Количество в наличии
    public $price;//Цена
    public $time_to_exe;//Срок доставки
    public $time_to_exe_guaranteed;//Срок доставки гарантированный
    public $storage;//Склад поставщика
    public $min_order;//Минимальный заказ
    public $probability;//Вероятность заказа
    public $office_id;//ID точки обслуживания
    public $storage_id;//ID склада
    public $office_caption;//Название точки обслуживания (`caption` из таблицы shop_offices)
    public $color;//Цвет
    
    //Инициализируется только для менеджеров
    public $storage_caption;//Название склада (`name` из таблицы shop_storages)
    
    //Здесь также указываем закупочную цену и наценку
    public $price_purchase;
    public $markup;
    
    
    //ДЛЯ ВОЗМОЖНОСТИ ВЫДАЧИ ТОВАРОВ КАТАЛОГА ЧЕРЕЗ СТРОКУ ПОИСКА ПО АРТИКУЛУ
    public $product_type;//Тип продукта (Treelax/Docpart)
    public $product_id;//ID продукта в каталоге
    public $storage_record_id;//ID записи поставки на складе
    public $product_url;//URL продукта
    
	//Новый параметр для записи некоторых технических даных, например полей для SAO. У каждого поставщика свой вариант
	public $json_params;
	
	public $valid;//Флаг корректности данных продукта
    
	public $check_hash;//Хеш для предотвращения подмены данных злоумышленниками через JavaScript
	
	public $search_name;//Флаг показывает что товар был найден по наименованию в каталоге или прайс листе
	
	//$rest_params - Это аргумент, который можно использовать для передачи любых параметров - чтобы не масштабировать конструктор
	
	
    public function __construct($manufacturer,
        $article,
        $name,
        $exist,
        $price,
        $time_to_exe,
        $time_to_exe_guaranteed,
        $storage,
        $min_order,
        $probability,
        $office_id,
        $storage_id,
        $office_caption,
        $color,
        $storage_caption,
        $price_purchase,
        $markup,
        $product_type,
        $product_id,
        $storage_record_id,
        $url,
		$json_params = '',
		$rest_params = NULL
    )
    {
		$DP_Config = new DP_Config;//Конфигурация CMS
		
		// Если товар найден по наименованию
		if( isset($rest_params['search_name']) )
		{
			if($rest_params['search_name'] === 1)
			{
				$this->search_name = 1;
			}
		}
		
		//Сразу переводим цены в валюту сайта
		if($rest_params == null)
		{
			$rest_params = array("rate"=>1);//Если данный параметр не передан - считаем курс валюты = 1
		}
		else
		{
			if( empty($rest_params['rate']) )
			{
				$rest_params['rate'] = 1;
			}
		}
		$price = $price * $rest_params["rate"];
		$price_purchase = $price_purchase * $rest_params["rate"];
		
		//Инициализация полей
        $this->manufacturer = htmlentities(mb_strtoupper(trim($manufacturer), "UTF-8"), ENT_QUOTES, "UTF-8");
        $this->article_show = htmlentities($article);
		$this->article = mb_strtoupper(preg_replace("/[^a-zA-Z0-9А-Яа-яёЁ]+/ui", "", $article), "UTF-8");
        $this->name = htmlentities(str_replace(array("\"", "\\", "'", "\n", "\r", "\t"), "", $name), ENT_QUOTES, "UTF-8");
        $this->exist = (int) str_replace(array(" ", "+", ">", "<", "ш", "т", "."), "", $exist);
        $this->price = number_format($price, 2, '.', '');
        $this->time_to_exe = (int)$time_to_exe;
        $this->time_to_exe_guaranteed = (int)$time_to_exe_guaranteed;
        $this->storage = htmlentities($storage);
        $this->min_order = (int)$min_order;
		if( $this->min_order == 0 )
		{
			$this->min_order = 1;
		}
        $this->probability = (int)$probability;
        $this->office_id = $office_id;
        $this->storage_id = $storage_id;
        $this->office_caption = htmlentities($office_caption);
        $this->color = $color;
        $this->storage_caption = htmlentities($storage_caption);
        $this->price_purchase = number_format($price_purchase, 2, '.', '');
        $this->markup = (int)($markup*100);
        $this->product_type = $product_type;
        $this->product_id = $product_id;
        $this->storage_record_id = $storage_record_id;
        $this->url = $url;
		$this->json_params = $json_params;
		
		
		
		//Проверяем корректность данных
		if($this->manufacturer == "" || 
		$this->article == "" || 
		$this->exist <= 0 || 
		$this->price <= 0)
		{
			$this->valid = false;
		}
		else
		{
			$this->valid = true;
		}
		
    }
}
?>