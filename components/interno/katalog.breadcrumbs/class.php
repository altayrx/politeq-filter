<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class CComponentCatalogBreadcrumbs extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['BREADCRUMBS'] = \CIBlockSection::GetNavChain($this->arParams['IBLOCK_ID'], $this->arParams['SECTION_ID'], array('ID', 'NAME', 'CODE'), true);
        foreach ($this->arResult['BREADCRUMBS'] as $i => &$item) {
            $item['TYPE'] = \CIBlockSection::getList([], ['IBLOCK_ID' => 40, 'ID' => $item['ID']], false, ['UF_TYPE'])->fetch()['UF_TYPE'];
            //echo '<!--', print_r($item, 1), '-->';
            if ($item['TYPE'] == 6) {
                $item['CODE'] = $this->arResult['BREADCRUMBS'][$i-1]['CODE'] . '/' . $item['CODE'];
            }
        }
    }

    public function executeComponent()
    {
        $this->handler();
        $this->arResult['CATALOG_ROOT'] = '/catalog/';
        $this->includeComponentTemplate();
    }
}
