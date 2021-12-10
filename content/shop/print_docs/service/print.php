<?php
header('Content-Type: text/html; charset=utf-8');
//Скрипт для печати документа - вызывается асинхронно со страниц сайта пользователями

//Определяем значение, чтобы исключить вызов формирования HTML в обход данного скрипта
define('_INTASK_', 1);

// ------------------------------------------------------------------------

//Конфигурация CMS
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

// ------------------------------------------------------------------------

//Подключение к БД
try
{
	$db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "No DB connect";
    exit(json_encode($answer));
}
$db_link->query("SET NAMES utf8;");

// ------------------------------------------------------------------------

//Для работы с пользователем
require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();

// ------------------------------------------------------------------------

//Получаем учетную запись документа
$doc_query = $db_link->prepare("SELECT * FROM `shop_print_docs` WHERE `name` = ?;");
$doc_query->execute( array($_GET["doc_name"]) );
$doc_record = $doc_query->fetch();
if( $doc_record == false )
{
	$answer = array();
	$answer["status"] = false;
	$answer["message"] = "Not found";
	exit(json_encode($answer));
}

$parameters_description = json_decode($doc_record["parameters_description"], true);
$parameters_values = json_decode($doc_record["parameters_values"], true);

//Для защиты от XSS
$_GET['order_id'] = (int)$_GET['order_id'];

if( isset( $DP_Config->wholesaler ) )
{
	$doc_query_2 = $db_link->prepare("SELECT * FROM `shop_print_docs_wholesaler` WHERE `doc_name` = ? AND `office_id` = ( SELECT `office_id` FROM `shop_orders` WHERE `id` = ? ) ;");
	$doc_query_2->execute( array($_GET["doc_name"], $_GET['order_id'] ) );
	$doc_record_2 = $doc_query_2->fetch();
	if( $doc_record_2 != false )
	{
		$parameters_values = json_decode($doc_record_2["parameters_values"], true);
	}
}


// ------------------------------------------------------------------------
//ВАЛЮТЫ (для обозначения в документах)

//Получаем основную валюту сайта
$currency_query = $db_link->prepare("SELECT * FROM `shop_currencies` WHERE `iso_code` = ?;");
$currency_query->execute( array($DP_Config->shop_currency) );
$currency = $currency_query->fetch();

//Массив со словесным обозначением 1/100 валюты
$currencies_names_array_cop = array();
$currencies_names_array_cop["RUB"] = array('копейка' ,'копейки' ,'копеек', 1);
$currencies_names_array_cop["BYN"] = array('копейка' ,'копейки' ,'копеек', 1);
$currencies_names_array_cop["USD"] = array('цент' ,'цента' ,'центов', 1);
$currencies_names_array_cop["EUR"] = array('евро цент' ,'евро цента' ,'евро центов', 1);
$currencies_names_array_cop["KZT"] = array('тиын' ,'тиын' ,'тиын', 1);
$currencies_names_array_cop["UAH"] = array('копейка' ,'копейки' ,'копеек', 1);
//...
//Массив со словесным обозначением валюты
$currencies_names_array = array();
$currencies_names_array["RUB"] = array('рубль', 'рубля', 'рублей', 0);
$currencies_names_array["BYN"] = array('белорусский рубль', 'булорусских рубля', 'булорусских рублей', 0);
$currencies_names_array["USD"] = array('доллар', 'доллара', 'долларов', 0);
$currencies_names_array["EUR"] = array('евро', 'евро', 'евро', 0);
$currencies_names_array["KZT"] = array('тенге', 'тенге', 'тенге', 0);
$currencies_names_array["UAH"] = array('гривна', 'гривны', 'гривен', 0);
//...

// ------------------------------------------------------------------------

//Подключаем скрипт для формирования HTML документа (Проверка прав доступа и вся остальная логика в скрипте get_html_*.php)
require_once($_SERVER["DOCUMENT_ROOT"]."/content/shop/print_docs/service/get_html_".$doc_record["name"].".php");

//Если дошли досюда, значит, HTML сформирован - выводим кнопку печати и сам HTML.
?>
<link href="/templates/expan/assets/css/font-awesome.min.css" rel="stylesheet" />
<div class="non_print" style="padding:10px;">
	<button onclick="window.print();return false;" style="font-size:18px;"><svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="print" class="svg-inline--fa fa-print fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="display:inline-block;width:18px;height:18px;"><path fill="currentColor" d="M448 192V77.25c0-8.49-3.37-16.62-9.37-22.63L393.37 9.37c-6-6-14.14-9.37-22.63-9.37H96C78.33 0 64 14.33 64 32v160c-35.35 0-64 28.65-64 64v112c0 8.84 7.16 16 16 16h48v96c0 17.67 14.33 32 32 32h320c17.67 0 32-14.33 32-32v-96h48c8.84 0 16-7.16 16-16V256c0-35.35-28.65-64-64-64zm-64 256H128v-96h256v96zm0-224H128V64h192v48c0 8.84 7.16 16 16 16h48v96zm48 72c-13.25 0-24-10.75-24-24 0-13.26 10.75-24 24-24s24 10.74 24 24c0 13.25-10.75 24-24 24z"></path></svg> Печать...</button>
</div>
<style>
	.next_page
	{
		width:1000%;
		border-top:1px dashed #000;
		margin-top:10px;
		margin-bottom:10px;
		margin-left:0;
		margin-right:0;
	}
	@media print {
	   .non_print { display:none; }
	   
	   .next_page{ page-break-after: always; border:none; } 
	}
</style>
<?php
echo $HTML;

// ------------------------------------------------------------------------





// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// ----------------------- Вспомогательные функции ----------------------
// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
// ----------------------------------------------------------------------
//Возвращает сумму прописью (ДЛЯ ДЕНЕГ)
function num2str($num) 
{
	global $currency, $currencies_names_array_cop, $currencies_names_array;
	
	$nul='ноль';
	$ten=array(
		array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
		array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
	);
	$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
	$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
	$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
	$unit=array( // Units
		$currencies_names_array_cop[$currency["iso_name"]],
		$currencies_names_array[$currency["iso_name"]],
		array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
		array('миллион' ,'миллиона','миллионов' ,0),
		array('миллиард','милиарда','миллиардов',0),
	);
	//
	list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub)>0) {
		foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
			if (!intval($v)) continue;
			$uk = sizeof($unit)-$uk-1; // unit key
			$gender = $unit[$uk][3];
			list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
			// mega-logic
			$out[] = $hundred[$i1]; # 1xx-9xx
			if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
			else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
			// units without rub & kop
			if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
		} //foreach
	}
	else $out[] = $nul;
	$out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
	$out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
	

	return mb_strtoupper_first(trim(preg_replace('/ {2,}/', ' ', join(' ',$out))));
}
// ----------------------------------------------------------------------
//Склоняем словоформу
function morph($n, $f1, $f2, $f5) {
	$n = abs(intval($n)) % 100;
	if ($n>10 && $n<20) return $f5;
	$n = $n % 10;
	if ($n>1 && $n<5) return $f2;
	if ($n==1) return $f1;
	return $f5;
}
// ----------------------------------------------------------------------
//Получение числа прописью (не для денег)
function num2str_simple($num)
{
	$nul='ноль';
	$ten=array(
		array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
		array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
	);
	$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
	$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
	$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
	$unit=array( // Units
		array('' ,'' ,'', 1),
		array('', '', '', 0),
		array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
		array('миллион' ,'миллиона','миллионов' ,0),
		array('миллиард','милиарда','миллиардов',0),
	);
	//
	list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub)>0) {
		foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
			if (!intval($v)) continue;
			$uk = sizeof($unit)-$uk-1; // unit key
			$gender = $unit[$uk][3];
			list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
			// mega-logic
			$out[] = $hundred[$i1]; # 1xx-9xx
			if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
			else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
			// units without rub & kop
			if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
		} //foreach
	}
	else $out[] = $nul;
	$out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
	//$out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
	

	return mb_strtoupper_first(trim(preg_replace('/ {2,}/', ' ', join(' ',$out))));
}
// ----------------------------------------------------------------------
//Получить текстовую строку даты
function get_date($unix_time)
{
	$month = array("","января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");
	
	
	return date("d ", $unix_time).$month[(int)date("n", $unix_time)].date(" Y", $unix_time);
}
// ----------------------------------------------------------------------
//Получить только месяц в тестовом виде
function get_month($unix_time)
{
	$month = array("","января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря");
	
	return $month[(int)date("n", $unix_time)];
}
// ----------------------------------------------------------------------
//Функция получения текстовой строки из профиля пользователя (тип виджета user_profile_json_builder)
function get_user_str_by_user_profile_json_builder($user_id, $user_profile_json_format)
{
	//Незарегистрированный покупатель (Такие документы обычно может выводить только администратор)
	if($user_id == 0)
	{
		return "";
	}
	
	//Профиль пользователя
	$user_profile = DP_User::getUserProfileById($user_id);
	
	//Формат в соответствии с его регистрационным вариантом
	if( !isset($user_profile_json_format["reg_variant_".$user_profile["reg_variant"]]) )
	{
		return "Не задан формат";
	}
	$user_profile_json_format = $user_profile_json_format["reg_variant_".$user_profile["reg_variant"]];
	
	$str = "";
	for( $i=0 ; $i < count($user_profile_json_format) ; $i++ )
	{
		if( $i > 0 )
		{
			$str = $str." ";
		}
		$str = $str.$user_profile[$user_profile_json_format[$i].""];
	}
	
	return $str;
}
// ----------------------------------------------------------------------
//Функция, которая возвращает строку с первым символом в верхнем регистре
function mb_strtoupper_first($str, $encoding = 'UTF8')
{
    return
        mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
        mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
}
// ----------------------------------------------------------------------
function print_price($price)
{
	return number_format($price, 2, '.', ' ');
}
// ----------------------------------------------------------------------
?>