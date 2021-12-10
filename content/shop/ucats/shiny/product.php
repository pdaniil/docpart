<?php
/*
Скрипт для вывода страницы товара
*/
defined('_ASTEXE_') or die('No access');

$product_id = (int)$_GET["tovar"];
$group = htmlentities($_GET["group"], ENT_QUOTES, "UTF-8");
?>

<?php
//Делаем запрос в веб-сервис Ucats
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $DP_Config->ucats_url."ucats/shiny/get_product.php?login=".$DP_Config->ucats_login."&password=".$DP_Config->ucats_password."&product_id=$product_id&group=".$group);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
$curl_result = curl_exec($curl);
curl_close($curl);
$curl_result = json_decode($curl_result, true);

if($curl_result["status"] != "ok")
{
	var_dump($curl_result);
}
else
{
	$product = $curl_result["product"];
	?>
	<h1><?php echo $product["name"]["value"]; ?></h1>
	
	<div class="ucats_product" id="ucats_product">
		<div class="product_image" id="product_image">
			<img src="<?php echo $product["img"]["value"]; ?>" />
		</div>
		
		<div class="product_info" id="product_info">
			<?php
			foreach($product as $field=>$data)
			{
				//Некоторые поля пропускаем
				if($field == "img" || $field == "id")
				{
					continue;
				}
				?>
				<h3><?php echo $data["caption"]; ?></h3>
				
				<?php
				if($field == "article" || $field == "manufacturer")
				{
					?>
					<br>
					<span onclick="location='/shop/part_search?article=<?php echo $product["article"]["value"]; ?>';" class="article"><?php echo $data["value"]; ?></span>
					<br><br>
					<?php
				}
				else
				{
					?>
					<p><?php echo $data["value"]; ?></p>
					<?php
				}
			}
			?>
		</div>
	</div>
	<script>
	//Получаем высоты:
    var product_image_HEIGHT = jQuery("#product_image").height();
    var product_info_HEIGHT = jQuery("#product_info").height();
    
    if(product_image_HEIGHT > product_info_HEIGHT)
    {
        jQuery("#ucats_product").height(parseInt(product_image_HEIGHT));
    }
    else
    {
        jQuery("#ucats_product").height(parseInt(product_info_HEIGHT+20));
    }
	</script>
	<?php
}
?>