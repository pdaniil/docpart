<?php
//Страничный скрипт ПУ для настройки платежных систем
defined('_ASTEXE_') or die('No access');

if( isset( $DP_Config->wholesaler ) )
{
	require_once( $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/finance/payment_systems_wholesaler.php' );
}
else
{
	require_once( $_SERVER["DOCUMENT_ROOT"].'/'.$DP_Config->backend_dir.'/content/shop/finance/payment_systems_standart.php' );
}
?>