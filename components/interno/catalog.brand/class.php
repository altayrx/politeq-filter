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

class CComponentCatalogBrand extends \CBitrixComponent
{
    private function handler()
    {
        $res = \CIblockSection::GetList(
            Array(
                "SORT" => "ASC",
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'CODE' => end($this->arParams['PATH']),
            ),
            false,
            Array('IBLOCK_ID', 'ID', 'CODE', 'NAME', 'DESCRIPTION', 'UF_*')
        );

        if ($ob = $res->fetch()) {
            \CPolitek::getInstance()->files($ob);

            $this->arResult['BRAND'] = $ob;
            if ($ob['UF_PROD']) {
                $prodRes = \CIBlockElement::getList(
                    Array(),
                    Array(
                        'ACTIVE' => 'Y',
                        'IBLOCK_ID' => 37,
                        'ID' => $ob['UF_PROD'],
                    ),
                    false,
                    false,
                    Array('IBLOCK_ID', 'ID', 'NAME', 'PROPERTY_PHOTOS', 'PROPERTY_VIDEOS', 'PROPERTY_FILE')
                );
                if ($prodOb = $prodRes->fetch()) {
                    $this->arResult['PROD'] = $prodOb;
                    foreach ($this->arResult['PROD']['PROPERTY_PHOTOS_VALUE'] as $i => &$photo) {
                        $photo = Array(
                            'PHOTO' => \CFile::getPath($photo),
                            'THUMB' => \CFile::resizeImageGet($photo, Array('width' => 200, 'height' => 91), BX_RESIZE_IMAGE_PROPORTIONAL)['src'],
                            'DESCRIPTION' => $this->arResult['PROD']['PROPERTY_PHOTOS_DESCRIPTION'][$i],
                        );
                    }
                    foreach ($this->arResult['PROD']['PROPERTY_VIDEOS_VALUE'] as $i => &$video) {
                        $video = Array(
                            'VIDEO' => 'https://youtu.be/' . $video,
                            'PREVIEW' => 'https://i.ytimg.com/vi/' . $video . '/sddefault.jpg',
                            'DESCRIPTION' => $this->arResult['PROD']['PROPERTY_VIDEOS_DESCRIPTION'][$i],
                        );
                    }
                    $this->arResult['PROD']['PROPERTY_FILE_VALUE'] = Array(
                        'FILE' => \CFile::getPath($this->arResult['PROD']['PROPERTY_FILE_VALUE']),
                        'DESCRIPTION' => $this->arResult['PROD']['PROPERTY_FILE_DESCRIPTION'],
                    );
                }
            }
            $this->arResult['SERIES'] = Array();
            $serieRes = \CIblockSection::getList(
                Array('SORT' => 'ASC'),
                Array(
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'SECTION_ID' => $ob['ID'],
                ),
                false,
                Array('IBLOCK_ID', 'ID', 'NAME', 'CODE', 'PICTURE', 'DESCRIPTION', 'UF_SPECS', 'UF_FILE', 'UF_FILE_DESCRIPTION', 'UF_TEXT_PREVIEW', 'UF_USAGE', 'UF_TEXT1', 'UF_FEATURES', 'UF_LINK_CHECK', 'UF_SUBTITLE', 'UF_LINK_CUSTOM', 'UF_BOOKLET', 'UF_STARTPRICE', 'UF_SUPPLYSTATUS', 'UF_SUPPLYSTATUSDETAIL')
            );
            if ($serieRes->selectedRowsCount() > 0) {
                while ($serieOb = $serieRes->fetch()) {
                    \CPolitek::getInstance()->files($serieOb);
                    if (!empty($serieOb['UF_USAGE'])) {
                        $serieUsage = Array();
                        $useRes = \CIBlockSection::getList(
                            Array(),
                            Array('IBLOCK_ID' => 36, 'ID' => $serieOb['UF_USAGE'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                            false,
                            Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID')
                        );
                        if ($useRes->selectedRowsCount() > 0) {
                            while ($useOb = $useRes->fetch()) {
                                $secRes = \CIBlockSection::getList(
                                    Array(),
                                    ARray('IBLOCK_ID' => 36, 'ID' => $useOb['ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                                    false,
                                    Array('IBLOCK_ID', 'ID', 'NAME')
                                );
                                if ($secOb = $secRes->fetch()) {
                                    $list = \CIBlockSection::GetNavChain(false, $secOb['ID'], ['ID', 'NAME', 'DEPTH_LEVEL'], true);
                                    foreach ($list as $v) {
                                        if ($v['DEPTH_LEVEL'] == 1) {
                                            $useOb['IBLOCK_SECTION_NAME'] = $v['NAME'];
                                        }
                                    }
                                }
                                $serieUsage[] = $useOb['IBLOCK_SECTION_NAME'];
                            }
                        }
                        $serieOb['USAGE'] = $serieUsage;
                    }
                    //if (empty($serieOb['UF_TEXT1']) && empty($serieOb['UF_FEATURES'])) {
                    if (((empty($serieOb['UF_TEXT1']) && empty($serieOb['UF_FEATURES'])) || (!empty($serieOb['UF_LINK_CHECK']) && $serieOb['UF_LINK_CHECK'] == 1)) && empty($serieOb['UF_LINK_CUSTOM'])) {
                        $serieOb['DISABLED'] = true;
                    }
                    $madeIn = \CIBlockElement::getList(
                        [],
                        ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'SECTION_ID' => $serieOb['ID']],
                        false,
                        ['nTopCount' => 1],
                        ['ID', 'PROPERTY_MADE_IN']
                    )->fetch()['PROPERTY_MADE_IN_VALUE'];
                    if ($madeIn) {
                        $serieOb['MADE_IN'] = $madeIn;
                    }
                    $this->arResult['SERIES'][$serieOb['ID']] = $serieOb;
                }
            }
            if (!empty($ob['UF_USAGE'])) {
                $this->arResult['USAGE'] = Array();
                $this->arResult['USAGE_COUNT'] = 0;
                $useRes = \CIBlockSection::getList(
                    Array(),
                    Array('IBLOCK_ID' => 36, 'ID' => $ob['UF_USAGE'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                    false,
                    Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'NAME', 'DESCRIPTION', 'UF_*', 'DEPTH_LEVEL')
                );
                if ($useRes->selectedRowsCount() > 0) {
                    while ($useOb = $useRes->fetch()) {
                        \CPolitek::getInstance()->files($useOb);
                        $secRes = \CIBlockSection::getList(
                            Array(),
                            Array('IBLOCK_ID' => 36, 'ID' => $useOb['IBLOCK_SECTION_ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                            false,
                            Array('IBLOCK_ID', 'ID', 'NAME')
                        );
                        if ($secOb = $secRes->fetch()) {
                            $useOb['IBLOCK_SECTION_NAME'] = $secOb['NAME'];
                        }
                        if ($useOb['DEPTH_LEVEL'] == 1) {
                            $useOb['IBLOCK_SECTION_NAME'] = $useOb['NAME'];
                        }
                        $this->arResult['USAGE'][] = $useOb;
                        if ($useOb['DEPTH_LEVEL'] != 1) {
                            $this->arResult['USAGE_COUNT']++;
                        }
                    }
                }
            }
        }
        global $APPLICATION;
        $seo = new \Bitrix\Iblock\InheritedProperty\SectionValues(3, $this->arResult['BRAND']['ID']);
        $seo = $seo->getValues();

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
        $this->includeComponentTemplate();
    }
}
