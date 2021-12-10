<?php
class Order
{
	public $Order = array();
	
	public function __construct($item)
	{
		$jsonReference = json_decode($item["t2_json_params"], true);
		
		$this->Order["OrderReference"] = $jsonReference["orderreference"];
		$this->Order["Quantity"] = $item["count_need"];
		$this->Order["Price"] = $item["t2_price_purchase"];
		$this->Order["CanRepeat"] = false;
		$this->Order["CanRepeatWithIncreaseInPrice"] = 0;
		$this->Order["CanRepeatWithIncreaseInTerm"] = 0;
		$this->Order["Reference"] = "";
		$this->Order["Error"] = "";
		$this->Order["Id"] = "";
	}
}


































?>