<?php
class RosskoCheckout
{
	public $Request = array();//Параметры запроса на создание заказа
	
	public $CheckoutDetails; //Детали оплаты, доставки, и тд
	
	public $keys = array(); //Ключи авторизации
	
	public $dileveryOption;  //Ключ(в массиве) способа доставки
	public $paymentOption; // Ключ(в массиве) способа оплаты
	public $fullname; //ФИО
	public $phone; //Телефон
	public $inparts; //Флаг доставки по частям
	
	public $orderRosskoId = ""; //ID созданного заказа Rossko
	
	
	public function setCheckOutOptions($options)
	{
		$this->keys["KEY1"] 	= $options["key1"];
		$this->keys["KEY2"] 	= $options["key2"];
		$this->dileveryOption	= $options["dileveryOption"];
		$this->paymentOption	= $options["paymentOption"];
		$this->fullname 		= $options["fullname"];
		$this->phone 			= $options["phone"];
		$this->inparts 			= $options["inparts"];
		$this->city 			= $options["city"];
		$this->street 			= $options["street"];
	}
	
	//Получение деталей доставки
	public function GetCheckoutDetails($url)
	{
		try
		{
			$SoapClient = new SoapClient($url);
		}
		catch(SoapFault $e)
		{
			throw new Exception($e->getMessage());
		}
		
		$CheckoutDetails = $SoapClient->GetCheckoutDetails($this->keys);
		
		if( ! $CheckoutDetails->CheckoutDetailsResult->success)
		{
			throw new Exception($CheckoutDetails->CheckoutDetailsResult->message);
		}
		else
		{
			$this->CheckoutDetails = $CheckoutDetails->CheckoutDetailsResult;
		}
		
	}
	//Генерирует объект запроса
	public function createRequestObject($order_item)
	{
		$params = json_decode($order_item["t2_json_params"], true);
		
		
		echo var_export($this->CheckoutDetails->DeliveryAddress, true);
		
		
		//Ключи
		$this->Request["KEY1"] = $this->keys["KEY1"];
		$this->Request["KEY2"] = $this->keys["KEY2"];
		//Доставка
		$this->Request["delivery"]["delivery_id"]	= $this->CheckoutDetails->DeliveryType->delivery[$this->dileveryOption]->id;
		//$this->Request["delivery"]["city"] 			= $this->CheckoutDetails->DeliveryAddress->address->city;
		$this->Request["delivery"]["city"] 			= $this->city;
		//$this->Request["delivery"]["street"] 		= $this->CheckoutDetails->DeliveryAddress->address->street;
		$this->Request["delivery"]["street"] 		= $this->street;
		//Оплата
		$this->Request["payment"]["payment_id"]		= $this->CheckoutDetails->PaymentType->payment[$this->paymentOption]->id;
		$this->Request["payment"]["company_name"]	= $this->CheckoutDetails->CompanyList->company->name;
		$this->Request["payment"]["company_requisite"] 		= $this->CheckoutDetails->CompanyList->company->requisite;
		//КОнтактные данные
		$this->Request["contact"]["name"]	= $this->fullname;
		$this->Request["contact"]["phone"] 	= $this->phone;
		//Доставлять заказ по частям (Флаг)
		$this->Request["delivery_parts"]	= $this->inparts;
		//Деталь
		$this->Request["PARTS"]["Part"]["partnumber"]	= $order_item["t2_article"];
		$this->Request["PARTS"]["Part"]["brand"]		= $order_item["t2_manufacturer"];
		$this->Request["PARTS"]["Part"]["stock"] 		= $params["id"];
		$this->Request["PARTS"]["Part"]["count"] 		= $order_item["count_need"];
		
	} //~public function createRequestObject($order_item)
	
	//Создаём заказ
	public function GetCheckout($url)
	{
		try
		{
			$SoapClient = new SoapClient($url);
		}
		catch(SoapFault $e)
		{
			throw new Exception($e->getMessage());
		}
		//Вызываем метод создания заказа
		$CheckoutResult = $SoapClient->GetCheckout($this->Request);
		//Обрабатываем результат
		$result = $CheckoutResult->CheckoutResult;
		
		echo var_export($result, true);
		
		//Ели успешно
		if($result->success)
		{
			if( ! empty($result->ItemsErrorList))
			{
				throw new ExceptionPart($result->ItemsErrorList->ItemError->message, $result->ItemsErrorList->ItemError);
			}
			else //Если деталь заказана, то и заказ оформлен
			{
				$this->orderRosskoId = $result->OrderIDS->id;
			}
		} // ~if($result->success)
		else
		{
			throw new Exception($CheckoutDetails->CheckoutDetailsResult->message);
		}
		
	} // ~public function GetCheckout($url)	
}
?>