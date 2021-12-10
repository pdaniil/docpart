<?php

require_once( 'AutoCodeApi.php' );

$class_dir = "{$_SERVER['DOCUMENT_ROOT']}/content/shop/docpart/";

set_include_path( get_include_path()
    . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/'
	. PATH_SEPARATOR . $class_dir
);

spl_autoload_register( function( $class ) { require_once( "{$class}.php" ); });
?>