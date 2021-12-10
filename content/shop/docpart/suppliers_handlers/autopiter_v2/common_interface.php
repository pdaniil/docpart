<?php
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);//Предотвратить вывод сообщений об ошибках


//Класс продукта
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/docpart/DocpartProduct.php");


class autopiter_enclosure
{
	public $result;
	public $client = null;
	
	public $Products = array();//Список товаров
	
	public function __construct($article, $storage_options)
	{
		$this->result = 0;//По умолчанию
		
		$this->connect($storage_options["user"], $storage_options["password"]);//Соединяемся с сервером SOAP
		
		$soap_result = $this->getPriceByNum($article);//Выполняем процедуру получения товаров по артикулу
		
		if($soap_result == false)
		{
			$this->result = 0;
			return;
		}
		
		//Наполняем массивы запрошенного артикула и аналогов:
		for($i=0; $i < count($soap_result); $i++)
		{
		    //Наценка
		    $markup = $storage_options["markups"][(int)$soap_result[$i]->SalePrice];
		    if($markup == NULL)//Если цена выше, чем максимальная точка диапазона - наценка определяется последним элементов в массиве
		    {
		        $markup = $storage_options["markups"][count($storage_options["markups"])-1];
		    }
		    
			if($soap_result[$i]->NumberOfAvailable == NULL)
			{
				$soap_result[$i]->NumberOfAvailable = 100;
			}
		    
			//Создаем объек товара и добавляем его в список:
			$DocpartProduct = new DocpartProduct($soap_result[$i]->CatalogName,
                $soap_result[$i]->Number,
                $soap_result[$i]->Name,
                $soap_result[$i]->NumberOfAvailable,
                $soap_result[$i]->SalePrice + $soap_result[$i]->SalePrice*$markup,
                $soap_result[$i]->NumberOfDaysSupply + $storage_options["additional_time"],
                $soap_result[$i]->NumberOfDaysSupply + $storage_options["additional_time"],
                NULL,
                $soap_result[$i]->MinNumberOfSales,
                100 - (int)$soap_result[$i]->RealTimeInProc,
                $storage_options["office_id"],
                $storage_options["storage_id"],
                $storage_options["office_caption"],
                $storage_options["color"],
                $storage_options["storage_caption"],
                $soap_result[$i]->SalePrice,
                $markup,
                2,0,0,'',null,array("rate"=>$storage_options["rate"])
                );
                
            if($DocpartProduct->valid == true)
			{
				array_push($this->Products, $DocpartProduct);
			}
			
		}
		
		$this->result = 1;
	}//~function __construct($article)
	
	
	
	//Метод соединения с SOAP - сервером
	public function connect($user, $password) 
	{

		$this->client = new SoapClient('http://service.autopiter.ru/v2/price?WSDL', 
								 array('soap_version' => SOAP_1_2, 
									   'encoding'=>'UTF-8')); 
		$result = $this->client->IsAuthorization(); 
		
		// Авторизуемся 
		if (!$result->IsAuthorizationResult) 
		{ 
			$result = $this->client->Authorization(array( 
						   'UserID' => $user, 
						   'Password' => $password, 
						   'Save' => true 
						   )); 			   
		} 
	}//~public function connect() 
	
	
	//Выполнение процедуры SOAP
	public function getPriceByNum($detailNum) 
	{ 
		// Загружаем каталоги с деталями 
		$catalogObj = $this->client->FindCatalog(array('Number' => $detailNum)); 
		
		if (!$catalogObj->FindCatalogResult) 
		{ 
			return false; 
		} 

		$itemCatalog = $catalogObj->FindCatalogResult->SearchCatalogModel; 
		
		//var_dump($itemCatalog);
		//exit;
		
		if (is_array($itemCatalog))
		{ 
			//echo "array";
			//$itemCatalog = $itemCatalog[0];
			$result = array();
			for($i=0; $i < count($itemCatalog); $i++)
			{
				$item = $itemCatalog[$i];
				
				try
				{ 
					$details = $this->client->GetPriceId(array ('ArticleId' => $item->ArticleId, 
													  'Currency' => 'РУБ', 
													  'SearchCross' => 1)); 
				}
				catch (Exception $e)
				{ 
					//echo 'exception'; 
					//var_dump($e);
					return false; 
				} 
				if (empty($details->GetPriceIdResult))
				{ 
					continue;
				} 
				
				if(is_array($details->GetPriceIdResult->PriceSearchModel))
				{
					$result = array_merge($result, $details->GetPriceIdResult->PriceSearchModel);
				}
			}
			//var_dump($result);
			//exit;
			return $result;
		}
		else
		{
			
			try 
			{ 
				$details = $this->client->GetPriceId(array ('ArticleId' => $itemCatalog->ArticleId, 
												  'Currency' => 'РУБ', 
												  'SearchCross' => 1)); 
			}
			catch (Exception $e)
			{ 
				//echo 'exception'; 
				//var_dump($e);
				return false; 
			} 
			if (empty($details->GetPriceIdResult))
			{ 
				return false; 
			} 
			
			//var_dump($details->GetPriceIdResult->BasePriceForClient);
			//exit;
			
			
			if(!is_array($details->GetPriceIdResult->PriceSearchModel))
			{
				$details->GetPriceIdResult->PriceSearchModel = array($details->GetPriceIdResult->PriceSearchModel);
			}
			return $details->GetPriceIdResult->PriceSearchModel;
		}
	}//~public function getPriceByNum($detailNum = '50610TA0A10')
};//~class autopiter_enclosure



$ob = new autopiter_enclosure($_POST["article"], json_decode($_POST["storage_options"], true));
//$ob = new autopiter_enclosure("OC247", array("user"=>"215324", "password"=>"ivirir15"));

//$ob = new autopiter_enclosure("st5512007n", array("user"=>"215324", "password"=>"ivirir15"));

$ob->client = 0;

exit(json_encode($ob));
?>