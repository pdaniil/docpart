<?php
/**
 * Этот скрипт содержит класс, предназначенный для преобразования PHP массивов в
 * XML формат. Поддерживаются многомерные массивы.
 * 
 * Пример использования:
 * 
 *
 * @author Стаценко Владимир http://www.simplecoding.org <vova_33@gala.net>
 * @version 0.1
 */

/**
 * Этот класс предназначен для преобразования PHP массива в XML формат
 */
class Array2XML {
	
	private $writer;
	private $version = '1.0';
	private $encoding = 'UTF-8';
	public $rootName = 'root';
	
	//конструктор
	function __construct() {
		$this->writer = new XMLWriter();
	}
	
	/**
	 * Преобразование PHP массива в XML формат.
	 * Если исходный массив пуст, то XML файл будет содержать только корневой тег.
	 *
	 * @param $data - PHP массив
	 * @return строка в XML формате
	 */
	public function convert($data) {
		$this->writer->openMemory();
		$this->writer->startDocument($this->version, $this->encoding);
		$this->writer->startElement($this->rootName);
		if (is_array($data)) {
			$this->getXML($data);
		}
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}
	
	/**
	 * Установка версии XML
	 *
	 * @param $version - строка с номером версии
	 */
	public function setVersion($version) {
		$this->version = $version;
	}
	
	/**
	 * Установка кодировки
	 *
	 * @param $version - строка с названием кодировки
	 */
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	/**
	 * Установка имени корневого тега
	 *
	 * @param $version - строка с названием корневого тега
	 */
	public function setRootName($rootName) {
		$this->rootName = $rootName;
	}
	
	/*
	 * Этот метод преобразует данные массива в XML строку.
	 * Если массив многомерный, то метод вызывается рекурсивно.
	 */
	private function getXML($data) {
		foreach ($data as $key => $val) 
		{
			//Если Ключ - число
			if (is_numeric($key))
			{
				$key = 'key'.$key;
				
				
				//Добавлено, для отсеивания тегов типа keyN, т.е. чтобы сразу выдавать теги с нужным именем
				foreach($val as $sub_key => $sub_val)
				{
					$key = $sub_key;
					$val = $sub_val;
				}
				
				
			}
			//Если содержимое - массив - начинаем писать элемент, как массив
			if (is_array($val)) 
			{
				$this->writer->startElement($key);
					$this->getXML($val);
				$this->writer->endElement();
			}
			else//Если не массив
			{
				if( substr($key, 0, 1) == "#" )//Если это атрибут - пишем атрибут
				{
					$this->writer->writeAttribute(str_replace("#", "", $key), $val);
				}
				else if( !is_numeric($key) )//Если ключ - не число - пишем простой элемент
				{
					$this->writer->writeElement($key, $val);
				}
				else//Если число - значит пишем просто текст
				{
					$this->writer->text($val);
				}
			}
		}
	}
}
//end of Array2XML.php