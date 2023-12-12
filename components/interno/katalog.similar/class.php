<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}
if (!\Bitrix\Main\Loader::includeModule('politek')) {
    ShowError('Модуль politek не установлен');
    return;
}

class CComponentCatalogSimilar extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['ITEMS'] = [];

        $filter = [
            'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            '!ID' => $this->arParams['CURRENT'],
            'PROPERTY_SERIES_VALUE' => $this->arParams['SIMILAR'],
        ];

        $res = \CIblockElement::GetList(
            ['SORT' => 'ASC',],
            $filter,
            /*[
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                $filter
            ],*/
            false,
            false
            //['nTopCount' => 10],
        );

        while ($ob = $res->getNextElement()) {
            $props = $ob->getProperties();
            $props['SPECS_PRIMARY'] = [];
            foreach ($props['FULL_SPECS']['VALUE'] as $i => $spec) {
                $specRes = \CIblockElement::getList(
                    Array(),
                    Array(
                        'IBLOCK_ID' => 39,
                        'ID' => $spec,
                        '!PROPERTY_PRIMARY' => false,
                    ),
                    false,
                    false,
                    Array('IBLOCK_ID', 'ID', 'NAME', 'DETAIL_PICTURE')
                );
                if ($specOb = $specRes->getNext()) {
                    $props['SPECS_PRIMARY'][] = [$specOb['NAME'], $props['FULL_SPECS']['DESCRIPTION'][$i]];
                }
            }

            $props['SPECS_PRIMARY_FORMATED'] = '<table class="table-main-specs">';
            foreach ($props['SPECS_PRIMARY'] as $spec) {
                $props['SPECS_PRIMARY_FORMATED'] .= '<tr><td>' . $spec[0] . '</td><td>' . $spec[1] . '</td></tr>';
            }
            $props['SPECS_PRIMARY_FORMATED'] .= '</table>';

            $props['FILE']['VALUE'] = \CFile::getPath($props['FILE']['VALUE']);
            if (!empty($this->arResult['FILTER']['TYPE'])) {
                    foreach ($props['SPECS'] as $spec) {
                        $specs = explode('|', preg_replace('/\<br\>/', '|', $props['SPECS']['~VALUE']));
                        foreach ($specs as $val) {
                            $specArr = explode(':', $val);
                            if (!in_array($specArr[0], array_column($this->arResult['FILTER']['PROP'], 'NAME'))) {
                                $this->arResult['FILTER']['PROP'][] = [
                                    'NAME' => $specArr[0],
                                    'VALUES' => [],
                                ];
                            }
                            if (!in_array(trim($specArr[1]), $this->arResult['FILTER']['PROP'][array_search($specArr[0], array_column($this->arResult['FILTER']['PROP'], 'NAME'))]['VALUES'])) {
                                $this->arResult['FILTER']['PROP'][array_search($specArr[0], array_column($this->arResult['FILTER']['PROP'], 'NAME'))]['VALUES'][] = trim($specArr[1]);
                            }
                        }
                    }
                    foreach ($props['FULL_SPECS']['VALUE'] as $i => $id) {
                        $primRes = \CIBlockElement::getList([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 39, 'ID' => $id, '!PROPERTY_PRIMARY' => false], false, false, ['NAME']);
                        if ($primOb = $primRes->fetch()) {
                            if (empty($this->arResult['FILTER']['PROP_FULL'][$id])) {
                                $this->arResult['FILTER']['PROP_FULL'][$id] = [
                                    'NAME' => $primOb['NAME'],
                                    'VALUES' => [],
                                ];
                            } 
                            if (
                                !in_array(
                                    $props['FULL_SPECS']['~DESCRIPTION'][$i],
                                    $this->arResult['FILTER']['PROP_FULL'][$id]['VALUES']
                                )
                                && !empty($props['FULL_SPECS']['~DESCRIPTION'][$i])
                                && $props['FULL_SPECS']['~DESCRIPTION'][$i] != '-'
                            ) {
                                $this->arResult['FILTER']['PROP_FULL'][$id]['VALUES'][] = $props['FULL_SPECS']['~DESCRIPTION'][$i];
                            }
                        }
                    }
                    foreach ($this->arResult['FILTER']['PROP_FULL'] as $i => $filterGroup) {
                        if (empty($filterGroup['VALUES'])) {
                            unset($this->arResult['FILTER']['PROP_FULL'][$i]);
                        }
                    }
            }
            $ob = $ob->getFields();
            if ($this->arResult['PAGER']['SORT_NAME'] == 'default') {
                $sort = json_decode($props['SECTIONS_SORT']['~VALUE'], 1)['VALUE'];
                foreach ($sort['SID'] as $i => $sid) {
                    if ($sid == $this->arResult['ID'] && intval($sort['SORT'][$i]) > 0) {
                        $ob['SORT'] = $sort['SORT'][$i];
                    }
                }
            }
            \CPolitek::getInstance()->files($ob);
            $ob['DETAIL_PAGE_URL'] = '/product/' . $ob['CODE'] . '/';
            if (!empty($props['PRICE_USD']['VALUE']) && floatval($props['PRICE_USD']['VALUE']) > 0) {
                $usdRate = Bitrix\Main\Config\Option::get("politek", "usd_rate");
                $price = number_format(round(floatval($props['PRICE_USD']['VALUE']) * $usdRate), 0, '.', ' ');
                $props['PRICE']['VALUE'] = "от {$price} ₽";
            }
            $this->arResult['ITEMS'][] = array_merge($ob, ['PROPS' => $props]);
        }

    }

    public function executeComponent()
    {
        $this->handler();
        $this->arResult['CATALOG_ROOT'] = '/catalog/';
        $this->includeComponentTemplate();
    }
}
