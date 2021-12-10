<?php
/*
Скрипт вывода всех каталогов Ucats
*/
defined('_ASTEXE_') or die('No access');

//Для главного цвета:
$color_class = "navbar-inverse";

if($DP_Config->ucats_shiny != '' || 
$DP_Config->ucats_disks != '' || 
$DP_Config->ucats_accessories != '' || 
$DP_Config->ucats_to != '' || 
$DP_Config->ucats_oil != '' || 
$DP_Config->ucats_akb != '' || 
$DP_Config->ucats_caps != '' || 
$DP_Config->ucats_bolty != '')
{
?>
	<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
	<h2 class="section-title">Справочники автотоваров</h2>
	<div class="row" style="margin-right: -11px; margin-left: -11px; margin-top: -9px; margin-bottom: -10px;">
		<?php
		if( $DP_Config->ucats_shiny != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/shiny" class="ucats-h-1 new-cat-block-tires">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Шины</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_disks != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/kolesnye-diski" class="ucats-h-1 new-cat-block-disks">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Диски</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_accessories != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/avtoaksessuary" class="ucats-h-1 new-cat-block-accessories">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Аксессуары</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_to != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/katalog-texnicheskogo-obsluzhivaniya" class="ucats-h-1 new-cat-block-to">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Каталог ТО</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_oil != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/avtoximiya" class="ucats-h-1 new-cat-block-oil">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Масла и автохимия</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_akb != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/akkumulyatory" class="ucats-h-1 new-cat-block-akb">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Аккумуляторы</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_caps != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/kolpaki" class="ucats-h-1 new-cat-block-caps">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Колпаки</div>
				</a>
			</div>
			<?php
		}
		if( $DP_Config->ucats_bolty != '' )
		{
			?>
			<div class="col-sm-6 col-md-4 col-lg-3 new-cat-block">
				<a href="/shop/katalogi-ucats/kolesnye-gajki-bolty-prostavki" class="ucats-h-1 new-cat-block-bolts">
					<div class="new-cat-block-text <?php echo $color_class; ?>">Болты, гайки</div>
				</a>
			</div>
			<?php
		}
		?>
	</div>
	</div>
	</div>
<?php
}
?>