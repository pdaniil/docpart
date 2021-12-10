<?php
class ExceptionPart extends Exception 
{
	private $_errorItem;
		
	public function __construct($message, $errorItem)
	{
		$this->_errorItem = $errorItem;
		
		parent::__construct($message);
	}
	
	public function getErrorItemLog()
	{
		echo date("d-m-Y H:i:s")."\n";
		echo "Деталь с oшибкой заказа\n";
		var_dump($this->_errorItem);
		echo  "==================================================\n";
	}
}

?>