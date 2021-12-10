<?php
/**
 * Скрипт модуля для баланса покупателя
*/
defined('_ASTEXE_') or die('No access');


//Получаем данные по валюте магазина
$stmt = $db_link->prepare('SELECT * FROM `shop_currencies` WHERE `iso_code` = :iso_code;');
$stmt->bindValue(':iso_code', $DP_Config->shop_currency);
$stmt->execute();
$currency_record = $stmt->fetch(PDO::FETCH_ASSOC);
$currency_sign = $currency_record["sign"];
//Строка для обозначения валюты
if($DP_Config->currency_show_mode == "no")
{
	$currency_indicator = "";
}
else if($DP_Config->currency_show_mode == "sign_before" || $DP_Config->currency_show_mode == "sign_after")
{
	$currency_indicator = $currency_sign;
}
else
{
	$currency_indicator = $currency_record["caption_short"];
}





require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
$user_id = DP_User::getUserId();


if($user_id > 0)
{
    ?>
    <div class="user_balance">
        <p>Баланс
        <?php
		$stmt = $db_link->prepare('SELECT *,( IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=1 AND `active` = 1), 0) - IFNULL((SELECT SUM(`amount`) FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `income`=0 AND `active` = 1),0) ) AS `balance` FROM `shop_users_accounting` WHERE `user_id` = :user_id AND `active` = 1;');
		$stmt->bindValue(':user_id', $user_id);
		$stmt->execute();
		$balance_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $balance = $balance_record["balance"];
        if($balance == "")
        {
            $balance = 0;
        }
		
		
		//Строка с балансом:
		$balance = number_format($balance, 2, '.', '');
		//Индикатор валюты перед ценой
		if($DP_Config->currency_show_mode == "sign_before")
		{
			$balance = $currency_indicator." ".$balance;
		}
		//Индикатор валюты после цены
		else if($DP_Config->currency_show_mode == "sign_after" || $DP_Config->currency_show_mode == "short_name_after")
		{
			$balance = $balance." ".$currency_indicator;
		}
		echo $balance;
		?>
        </p>
    </div>
    <?php
}
?>