<?php

	class base
	{
		public $data;
		public $dictionary;
		public $sub_tables_param;
		function __construct( $arr = null, $rules = null, $sub_tables_param = null)
		{
			$this->data = $arr;
			$this->dictionary = $rules;
			$this->sub_tables_param = $sub_tables_param;
		}
		
		function destroy()
		{
			$this->data = null;
			$this->dictionary = null;
		}
		
		function putDataIntoTable()
		{
			global $db_link;
			global $DP_Config;
			//Если есть скрипт SQL на TRUNCATE, то вначале выполняем его
			if (!is_null($this->SQL_truncate))
			{
				$res = $db_link->prepare($this->SQL_truncate)->execute();
			
				if (!$res)
					throw new PDOException("Ошибка очистки таблицы ".$this->table_name.".");
			}
			
			//Далее идёт скрипт на INSERT, принцип тот же
			if (!is_null($this->SQL_insert))
			{
				$query = $db_link->prepare($this->SQL_insert);	
				
				
				if (!is_null($this->sub_tables_param))
				{
					echo "<br>";
					var_dump($this->data);
					echo "<br>";
				}
				foreach($this->data as $value)
				{
					$params = [];
					foreach ($this->dictionary["params"] as $param)
					{
						$params[] = $value->{$param};
					}
					
					if (!is_null($this->sub_tables_param))
						$params = array_merge($params, $this->sub_tables_param);
					
					$res = $query->execute( $params );
					
					if (!$res)
						throw new PDOException("Ошибка вставки данных в таблицу ".$this->table_name.".");
				}
			}
			
			//Далее идёт скрипт на UPDATE
			if (!is_null($this->SQL_update))
			{
				$query = $db_link->prepare($this->SQL_update);
			
				foreach($this->data as $value)
				{
					$params = [];
					foreach ($this->dictionary["params"] as $param)
					{
						$params[] = $value->{$param};
					}
					
					$res = $query->execute( $params );
					
					if (!$res)
						throw new PDOException("Ошибка обновления таблицы ".$this->table_name.".");
				}
			}
			
		}
		
	}

?>