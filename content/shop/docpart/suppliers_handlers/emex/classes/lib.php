<?php
$pathClasses		= "{$_SERVER['DOCUMENT_ROOT']}/content/shop/docpart/suppliers_handlers/emex/classes/";//Директория  подключения классов

set_include_path(
	get_include_path() . 
	PATH_SEPARATOR . $pathClasses
); //Пути для загрузки классов 

//Загрузка классов
function classLoader( $class ) {
	
	require_once("{$class}.php");
	
}


//Подключение класса для работы с пользователем
require_once("{$_SERVER['DOCUMENT_ROOT']}/content/users/dp_user.php");
//Автоматическая загрузка классов
spl_autoload_register('classLoader');
?>