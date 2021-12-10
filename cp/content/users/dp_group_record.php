<?php
/**
 * Класс Записи группы пользователей
*/

class DP_GroupRecord
{
    public $id;//ID группы - ID узла webix
    public $value;//Название группы
    public $count;//Поле count дерева webix
    public $level;//Поле level дерева webix
    public $parent;//id родительской группы
    public $unblocked;//Флаг - Разблокирована
    public $for_guests;//Флаг - Для гостей
    public $for_registrated;//Флаг - Для регистрирующихся
    public $for_backend;//Флаг - Для администраторов бэкэнда
    public $description;//Текстовое описание группы
    
    public $data = array();//Массив с вложенными объектами групп (в таблице БД это поле отсутствует)
    
    public function __construct()
    {
        $this->id = "";//ID узла webix
        $this->value = "";//Название группы
        $this->count = 0;//Поле count дерева webix
        $this->level = 0;//Поле level дерева webix
        $this->parent = 0;//id родительской группы
        $this->unblocked = 1;//Флаг - Разблокирована
        $this->for_guests = 0;//Флаг - Для гостей
        $this->for_registrated = 0;//Флаг - Для регистрирующихся
        $this->for_backend = 0;//Флаг - Для администраторов бэкэнда
        $this->description = "";//Текстовое описание группы
    }
}
?>
