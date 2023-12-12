<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class CComponentCatalogLevel12 extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['EQUIPMENT'] = Array();

        $this->arResult['TAGS'] = [
            'TAG' => [],
            'MAN' => [],
        ];

        $res = \CIBlock::GetList(
            Array(),
            Array('ID' => $this->arParams['IBLOCK_ID']),
            true
        );
        if($ar_res = $res->Fetch()) {
            $this->arResult['EQUIPMENT_TEXT'] = $ar_res['DESCRIPTION'];
        }
        $res = \CIblockSection::GetList(
            Array(
                "SORT" => "ASC",
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'CODE' => $this->arParams['SECTION_ID'],
                'DEPTH_LEVEL' => $this->arParams['DEPTH_LEVEL'],
                'UF_TYPE' => false,
            ),
            false,
            Array('IBLOCK_ID', 'ID', 'NAME','CODE')
        );

        $firstFilter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 40,
            'CODE' => $this->arParams['SECTION_ID'],
        ];
        $this->arResult['SECTION'] = \CIBlockSection::getList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            $firstFilter,
            false,
            ['*', 'UF_*']
        )->fetch();

        $decor = true;
        if ($ob = $res->fetch()) {
            $this->arResult['ID'] = $ob['ID'];
            $this->arResult['CODE'] = $ob['CODE'];
            $this->arResult['EQUIPMENT'][$ob['ID']] = $ob;
            $this->arResult['EQUIPMENT'][$ob['ID']]['DECOR'] = $decor;
            $res2 = \CIblockSection::GetList(
                Array(
                    "SORT" => "ASC",
                ),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'SECTION_ID' => $ob['ID'],
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => $this->arParams['DEPTH_LEVEL'] + 1,
                    'UF_TYPE' => false,
                ),
                false,
                Array('IBLOCK_ID', 'ID', 'NAME', 'PICTURE', 'CODE', 'UF_TEXT_PREVIEW', 'UF_LINK_CHECK')
            );
            if ($res2->selectedRowsCount() > 0) {
                $this->arResult['EQUIPMENT'][$ob['ID']]['BRANDS'] = Array();
                while ($ob2 = $res2->fetch()) {
                    $ob2['IMAGE'] = \CFile::getPath($ob2['PICTURE']);
                    if ((!empty($ob2['UF_LINK_CHECK']) && $ob2['UF_LINK_CHECK'] == 1)) {
                        $ob2['DISABLED'] = true;
                    }
                    $this->arResult['EQUIPMENT'][$ob['ID']]['BRANDS'][] = $ob2;
                }
            }
            $decor = !$decor;

            /** Mans */
            if (!empty($_REQUEST['MANUFACTURER'])) {
                $this->arResult['SUB_SECTION'] = \CIBlockSection::getList(
                    [],
                    [
                        'ACTIVE' => 'Y',
                        'IBLOCK_ID' => 40,
                        'SECTION_ID' => $this->arResult['ID'],
                        'CODE' => $_REQUEST['MANUFACTURER'],
                    ],
                    false,
                    ['ID']
                )->fetch();
                $sub_seo = new \Bitrix\Iblock\InheritedProperty\SectionValues(40, $this->arResult['SUB_SECTION']['ID']);
                $this->arResult['SUB_SECTION']['SEO'] = $sub_seo->getValues();
            }
            /** manS */

            /** Tags */
            $subSections = \CIBlockSection::getList(
                [],
                [
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => 40,
                    'SECTION_ID' => $this->arResult['ID'],
                ],
                false,
                ['ID', 'NAME', 'UF_*', 'CODE']
            );
            while ($sub = $subSections->fetch()) {
                switch ($sub['UF_TYPE']) {
                    case 6://теговая
                        $this->arResult['TAGS']['TAG'][] = [
                            'URL' => '/catalog/' . end($this->arParams['PATH']) . '/' . $sub['CODE'] . '/',
                            'NAME' => $sub['NAME'],
                        ];
                        break;
                    case 7://производитель
                        $this->arResult['TAGS']['MAN'][] = [
                            //'URL' => '/manufacturers/' . $sub['CODE'] . '/' . end($this->arParams['PATH']) . '/',
                            'URL' => '/manufacturers/' . $sub['CODE'] . '/' . $this->arResult['CODE'] . '/',
                            'NAME' => $sub['NAME'],
                        ];
                        break;
                    case 8://страна
                        break;
                    case 9://фильтр
                        break;
                }
            }
            /** tagS */
        }
        global $APPLICATION;
        $seo = new \Bitrix\Iblock\InheritedProperty\SectionValues(40, $this->arResult['ID']);
        $seo = $seo->getValues();
        $this->arResult['SEO'] = $seo;
        if (isset($seo['SECTION_META_TITLE'])) {
            $APPLICATION->SetPageProperty('TITLE', $seo['SECTION_META_TITLE']);
        }
        if (isset($seo['SECTION_META_KEYWORDS'])) {
            $APPLICATION->SetPageProperty('KEYWORDS', $seo['SECTION_META_KEYWORDS']);
        }
        if (isset($seo['SECTION_META_DESCRIPTION'])) {
            $APPLICATION->SetPageProperty('DESCRIPTION', $seo['SECTION_META_DESCRIPTION']);
        }
    }

    public function executeComponent()
    {
        $this->handler();
        $this->arResult['CATALOG_ROOT'] = '/catalog/';
        $this->includeComponentTemplate();
    }
}
