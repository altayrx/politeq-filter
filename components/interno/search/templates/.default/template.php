<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

//$manfnames = $arResult['MANUFACTURERS'];
?>
<div class="page-inner__container row">
<div class="breadcrumbs section-wrapper">
	<div class="breadcrumbs__inner">
		<div class="breadcrumbs__item"><a href="/">Главная</a></div>
		<div class="breadcrumbs__item breadcrumbs__no-link"><span>Поиск</span></div>
	</div>
</div>
<div class="page-inner__section section-wrapper clearfix search-page">
	<h2 class="page-inner__title section-title">Результаты поиска</h2>
	<div class="page-section__wrapper clearfix">
		<div class="page-section__content clearfix">
			<div class="search-section__content">
				<div class="search-section__count-result">По запросу «<?=$arResult["QUERY"]?>» найдено <?=$arResult['NavRecordCount']?></div>
				<div class="search-section__row clearfix">
					<div class="catalog-inner__product-wrapper catalog_wrap">
						<?if ($arParams['DISPLAY_TOP_PAGER'] == 'Y' && ($arResult['NavRecordCount'] > 0 || $arParams['PAGER_SHOW_ALWAYS'] == 'Y')) {?>
						<div class="catalog-inner__listing-top">
							<div class="listing-top__controls clearfix">
								<?=$arResult["NAV_STRING"]?>
							</div>
						</div>
						<?}?>
						<div class="catalog-inner__product-listing product-list clearfix">
<?if($arResult["ERROR_CODE"]!=0){?><p><?=GetMessage("SEARCH_ERROR")?></p><?}elseif(count($arResult["ITEMS"])>0){?>
<?foreach ($arResult["ITEMS"] as $item) {?>
<?
/*if ($item['ACTIVE']) {
    $item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Снят с реализации'
}*/
?>
							<div class="cat-product__item product-card__item"><!--<?=$item['ACTIVE']?>-->
								<?=($item['PROPS']['gift']['VALUE']?'<div class="product-card__gift"></div>':'')?>
								<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='В наличии'?'<div class="product-card__stock in-stock"></div>':'')?>
								<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Под заказ'?'<div class="product-card__stock out-stock"></div>':'')?>
    							<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Нет в наличии'?'<div class="product-card__stock not-stock"></div>':'')?>
    							<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Снят с реализации'?'<div class="product-card__stock not-stock"></div>':'')?>
								<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Ожидается'?'<div class="product-card__stock exp-stock"></div>':'')?>
								<?=($item['PROPS']['AVAIBLE_STATUS']['VALUE']=='Уточняйте у менеджера'?'<div class="product-card__stock spec-stock"></div>':'')?>
								<a href="/goods/<?=$item['CODE']?>/" class="product-card__img">
									<img
										src="<?=($item['PREVIEW_PICTURE']?(CFile::GetPath($item['PREVIEW_PICTURE'])):$item['DETAIL_PICTURE']?(CFile::GetPath($item['DETAIL_PICTURE'])):'/images/empty.png')?>"
										alt="<?=$manfnames[$item['PROPS']['MANUFACTURER']['VALUE']].' '.$item['NAME']?>">
								</a>
								<a href="/goods/<?=$item['CODE']?>/" class="product-card__link product-card__desc">
									<div class="product-card__title"><?=$manfnames[$item['PROPS']['MANUFACTURER']['VALUE']].' '.$item['NAME']?></div>
									<div class="product-card__text"><?=$item['TEXT']?></div>
								</a>
								<div class="product-card__specials"></div>
								<div class="product-card__info clearfix">
									<a class="product-card__basket" data-id="<?=$item['ID']?>"></a>
									<div class="product-card__price-wr">
										<?//if ($item['PROPS']['price_onrequest']['VALUE']) {?>
										<?if (intval($item['PRICE']) == 0) {?>
											<div class="product-card__price-onrequest">Цена по запросу</div>
										<?} else {?>
											<?if (isset($item['DISCOUNT_PRICE']) && !empty($item['DISCOUNT_PRICE'])) {?>
											<div class="product-card__price"><span><?=$item['PRICE_FORMATED']?></span> руб.</div>
											<div class="product-card__old-price"><span><?=$item['DISCOUNT_PRICE_FORMATED']?></span> руб.</div>
											<?} else {?>
											<div class="product-card__price"><span><?=$item['PRICE_FORMATED']?></span> руб.</div>
											<?}?>
											<?if ($item['PRICE'] >= \CWG::CREDIT_FROM && $item['PRICE']<=\CWG::CREDIT_TO){?><div class="product-card__credit-wrap">В <a href="/credit/" target="_blank">кредит</a> от <span><?=number_format(ceil(($item['PRICE']*3.5142/100) / WGCatalog::ROUND_PRICE) * WGCatalog::ROUND_PRICE, 0, '.', ' ');?></span> р/мес</div><?}?>
										<?}?>
									</div>
									<div class="product-card__compare <?=(!empty($_SESSION['WGCOMPARE'][$item['ID']])?'compare_added':'')?>"  data-id="<?=$item['ID']?>"></div>
									<div class="compare_popup">Добавить в сравнение</div>
								</div>
							</div>
<?}
} else{ShowNote(GetMessage("SEARCH_NOTHING_TO_FOUND"));}?>
						</div>
						<?if ($arParams['DISPLAY_BOTTOM_PAGER'] == 'Y' && ($arResult['NavRecordCount'] > 0 || $arParams['PAGER_SHOW_ALWAYS'] == 'Y')) {?>
						<div class="catalog-inner__listing-btm">
							<div class="product-listing__box-more">
								<script>
									var NavPageNomer = <?=$arResult["CUSTOM_NAV"]->NavPageNomer?>;
									var NavPageCount = <?=$arResult["CUSTOM_NAV"]->NavPageCount?>;
									var NavRecordCount = <?=$arResult["CUSTOM_NAV"]->NavRecordCount?>;
									var NavPageSize = <?=$arResult["CUSTOM_NAV"]->NavPageSize?>;
								</script>
								<?if ($arResult["CUSTOM_NAV"]->NavPageNomer < $arResult["CUSTOM_NAV"]->NavPageCount) {?>
								<div class="product-listing__btn-more product-more">Показать еще 12 товаров</div>
								<?}?>
							</div>
							<div class="listing-btm__controls clearfix">
								<?=$arResult["NAV_STRING"]?>
							</div>
						</div>
						<?}?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div>
