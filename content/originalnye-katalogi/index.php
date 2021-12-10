<?

$_GET["vin"] = htmlentities($_GET["vin"]);

$scriptStartTime = microtime();
ob_start();
session_start();

header("Content-Type: text/html; charset=utf-8");
error_reporting(E_ALL);

//Значение параметра partInfo по умолчанию
//partInfo default value
$partInfoValue = 1; 


if (file_exists('underConstruction.php')) {include('underConstruction.php');}
if ($IlcatsInjections=file_exists('IlcatsInjections.php')) {$IlcatsInjection='Index1'; include('IlcatsInjections.php');}

if (file_exists('IlcatsInjections2.php')) include_once('IlcatsInjections2.php');

//print_r(dirname(__FILE__));
include_once(dirname(__FILE__).'/../content/originalnye-katalogi/settings.php');

include_once(dirname(__FILE__).'/../content/originalnye-katalogi/API.v2/PHP/Functions.Common.php');
include_once(dirname(__FILE__).'/../content/originalnye-katalogi/API.v2/PHP/Functions.Blocks.php');

if ($IlcatsInjections=file_exists('IlcatsInjections.php')) {$IlcatsInjection='Index1.2'; include('IlcatsInjections.php');}

if (!$_GET['function']) {$_GET['function']='defaultFunction';}
if (!$_GET['language'])	$_GET['language']=$_COOKIE['language'] ? $_COOKIE['language'] : "ru";
$vinTmp = (empty($_GET["vin"]) ? array() : array("vin" => $_GET["vin"]));



if ($_GET['brand']) $data=getApiData($_GET);
else $data=getApiData(array_merge(array("function"=>"catalogsList","brand"=>'cataloglist',"apiVersion"=>'2.0',"shopClientId"=>$_GET["clid"], "catalogId"=>$_GET["pid"],"shopid"=>$_GET["shopid"],"language"=>$_GET["language"]),$vinTmp));

if ($_GET["debughash"]) ShowApiAnswer($data, $_GET["debughash"]);



if ($_GET['Ajax']==1){
	$_GET['filterData']=base64_decode($_GET['filterData']);
	$Answer['filterData']=$_GET['filterData'];
	$Answer['PageSelector']=$data['data'][1]['format']($data['data'][1]);
	$Answer['Tiles']=$data['data'][2]['format']($data['data'][2]);

	//print_r($Answer);
	exit(json_encode($Answer));
}

if ($IlcatsInjections=file_exists('IlcatsInjections.php')) {$IlcatsInjection='Index1.5'; include('IlcatsInjections.php');}

$SiteLabels=$data['siteLabels'];
if ($data['mainMenu']) $Page['MainMenu']=MainMenu($data['mainMenu']);
if ($data['availableLanguages']) $Page['Languages']=Languages($data['availableLanguages'],$apiActiveLanguages); //else $Page['Languages']="No 'availableLanguages'";
if ($data['data'])
	foreach ($data["data"] as $Data)
		$Page['Content'][]=($Data['caption'] ? "<h2>{$Data['caption']}</h2>" : "").$Data['format']($Data, $SiteLabels);
else {
	if ($data["errors"])
		$Page['Content'][]="<div class='ApiError'>".ImplodeIfArray($data["errors"], '<br>')."</div>";
	else $Page['Content'][]="Wrong answer";
}




?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
	<meta http-equiv='X-UA-Compatible' content='IE=edge'>
	<meta name="viewport" content="width=device-width, initial-scale=0.7">
<?
	echo   "<meta name='description' content='{$data["metas"]["description"]}'>
    		<meta name='keyword' content='".ImplodeIfArray($data["metas"]["keywords"], ', ')."'>
    		<title>{$data["metas"]["title"]}</title>";
?>
	<script type='text/javascript' src='<? echo apiStaticContentHost; ?>/API.v2/JS/JQuery-3.1.0.min.js'></script>
	<script type='text/javascript' src='<? echo apiStaticContentHost; ?>/API.v2/JS/JQueryUI-1.12.0/JQueryUI.min.js'></script>
	<link type='text/css' rel='stylesheet' href='<? echo apiStaticContentHost; ?>/API.v2/JS/JQueryUI-1.12.0/JQueryUI.css'>
	<script type="text/javascript" src="<? echo apiStaticContentHost; ?>/API.v2/JS/jquery.scrollTo.190301.min.js"></script>
	<script type="text/javascript" src="<? echo apiStaticContentHost; ?>/API.v2/JS/jquery.pep.js"></script>
<?

	echo "
	<link type='text/css' rel='stylesheet' href='".apiStaticContentHost."/fonts/ProximaNova/Font.css'>	
	<link type='text/css' rel='stylesheet' href='".apiStaticContentHost."/API.v2/CSS/Template2.190228.css'>
	<link type='text/css' rel='stylesheet' href='//www.ilcats.ru/getCss.php?clid=15691&host=incar62.ru'>
	<script type='text/javascript' src='".apiStaticContentHost."/API.v2/JS/Common2.190228.js'></script>";


if ($IlcatsInjections) {$IlcatsInjection='Index2'; include('IlcatsInjections.php');}
?>
</head>
<body class="<? echo $_GET['brand']  ?>">
<? if ($IlcatsInjections) {$IlcatsInjection='Counters'; include('IlcatsInjections.php');}	   ?>
<div class='PageHeader'>
		<?  echo "<div class='Top'>{$Page['MainMenu']}{$Page['Languages']}</div>", VinForm($data['vinSearchParameters']); ?>
	</div>
	<div id='Body' class='<? echo $data['data'][0]['format']; ?>Body'>
	<? if ($IlcatsInjections) {$IlcatsInjection='Advert1'; include('IlcatsInjections.php');} ?>
	<h1><? echo $data["stageName"]; ?></h1>
		<?
			if ($data['data'][0]['format']=='ifImage'){
				$TempPageContent[0]=$Page['Content'][0];
				array_shift($Page['Content']);
				$TempPageContent[1]="<div class='Info'>".ImplodeIfArray($Page['Content'])."</div>";
				$Page['Content']="<div class='ifImage'>".ImplodeIfArray($TempPageContent)."</div>";
			}
			echo ImplodeIfArray($Page['Content']);

		if ($IlcatsInjections) {$IlcatsInjection='Advert2'; include('IlcatsInjections.php');} ?>
	</div>
	<div id='Dialog'></div>
	<footer>
		<? echo "<div>{$data['siteLabels']['advertLinkUrl']}</div>";
		if ($IlcatsInjections) {$IlcatsInjection='Index3'; include('IlcatsInjections.php');}
		echo $ErrorFound;
		?>
	</footer>

</body>
<?
echo "<!-- {$data['serverInfo']['dataGenerateTime']} ".(microtime()-$scriptStartTime)."-->";
?>

</html>
<?
ob_end_flush();

?>