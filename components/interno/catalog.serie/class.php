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

class CComponentCatalogSerie extends \CBitrixComponent
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
            Array('IBLOCK_ID', 'ID', 'CODE', 'NAME', 'UF_*', 'IBLOCK_SECTION_ID', 'DESCRIPTION')
        );

        if ($ob = $res->fetch()) {
            $this->arResult['PARENT'] = $this->getParentSection($ob['IBLOCK_SECTION_ID']);

            if ($ob['UF_ADVANTAGES']) {
                $this->arResult['UF_ADVANTAGES'] = $this->getAdvantages($ob['UF_ADVANTAGES']);
            }

            $elRes = \CIBlockElement::getList(
                Array('SORT' => 'ASC'),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                    'IBLOCK_SECTION_ID' => $ob['ID'],
                ),
                false,
                false
            );
            if ($elOb = $elRes->getNextElement()) {
                $elObFields = $elOb->getFields();
                $elObProps = $elOb->getProperties();
                if (!empty($elObProps['GAL_PHOTO']['VALUE'])) {
                    $ob['PHOTOGALL'] = Array();
                    foreach ($elObProps['GAL_PHOTO']['VALUE'] as $index => $photogall) {
                        $photogallvalue = \CFile::ResizeImageGet($photogall, ['width' => 1000, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $photogallminivalue = \CFile::ResizeImageGet($photogall, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $ob['PHOTOGALL'][] = Array('VALUE' => $photogallvalue,'MINIVALUE' => $photogallminivalue, 'DESCRIPTION' => $elObProps['GAL_PHOTO']['DESCRIPTION'][$index]);
                    }
                }
                if (!empty($elObProps['GAL_VIDEO']['VALUE'])) {
                    $ob['VIDEOGALL'] = Array();
                    foreach ($elObProps['GAL_VIDEO']['VALUE'] as $index => $videogall) {
                        $videogallvalue = \CFile::ResizeImageGet($videogall, ['width' => 1000, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $videogallminivalue = \CFile::ResizeImageGet($videogall, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $ob['VIDEOGALL'][] = Array('VALUE' => $videogallvalue,'MINIVALUE' => $videogallminivalue, 'DESCRIPTION' => $elObProps['GAL_VIDEO']['DESCRIPTION'][$index]);
                    }
                }
                /*if (!empty($elObProps['IMAGES']['VALUE'])) {
                    $ob['IMAGESGALL'] = Array();
                    foreach ($elObProps['IMAGES']['VALUE'] as $index => $imagesgall) {
                        $imagesgallvalue = \CFile::ResizeImageGet($imagesgall, ['width' => 1000, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $imagesgallminivalue = \CFile::ResizeImageGet($imagesgall, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        $ob['IMAGESGALL'][] = Array('VALUE' => $imagesgallvalue,'MINIVALUE' => $imagesgallminivalue, 'DESCRIPTION' => $elObProps['IMAGES']['DESCRIPTION'][$index]);
                    }
                }*/

                if (!empty($elObProps['MADE_IN']['VALUE'])) {
                    $ob['MADE_IN'] = $elObProps['MADE_IN']['VALUE'];
                }
                if (!empty($elObProps['VIDEO_YT']['VALUE'])) {
                    $ob['VIDEOS'] = Array();
                    foreach ($elObProps['VIDEO_YT']['VALUE'] as $index => $video) {
                        $ob['VIDEOS'][] = Array('VALUE' => $video, 'DESCRIPTION' => $elObProps['VIDEO_YT']['DESCRIPTION'][$index]);
                    }
                }
                if (!empty($elObProps['MODELS']['VALUE'])) {
                    $ob['MODELS'] = Array();
                    $modelRes = \CIBlockElement::getList(
                        Array('ID' => $elObProps['MODELS']['VALUE']),
                        Array(
                            'IBLOCK_ID' => 12,
                            'ACTIVE' => 'Y',
                            'ID' => $elObProps['MODELS']['VALUE'],
                        ),
                        false,
                        false
                    );
                    $props = Array();
                    while ($modelOb = $modelRes->getNextElement()) {
                        $modelObFields = $modelOb->getFields();
                        $modelObProps = $modelOb->getProperties();
                        \CPolitek::getInstance()->files($modelObFields);
                        foreach ($modelObProps as $prop) {
                            if ($prop['CODE'] == 'SPECS') {
                                foreach ($prop['VALUE'] as $i => $spec) {
                                    $specRes = \CIblockElement::getList(
                                        Array(),
                                        Array(
                                            'IBLOCK_ID' => 39,
                                            'ID' => $spec,
                                            '!PROPERTY_IN_SERIES' => false,
                                        ),
                                        false,
                                        false,
                                        Array('IBLOCK_ID', 'ID', 'NAME', 'DETAIL_PICTURE')
                                    );
                                    if ($specOb = $specRes->getNext()) {
                                        if (!isset($props[$spec]) && !empty($prop['~DESCRIPTION'][$i])) {
                                            $props[$spec] = Array(
                                                'NAME' => $specOb['NAME'],
                                                'VALUES' => Array()
                                            );
                                        }
//                                        $props[$spec]['VALUES'][$modelObFields['ID']] = unserialize($prop['~DESCRIPTION'][$i]);
                                        $props[$spec]['VALUES'][$modelObFields['ID']] = $prop['~DESCRIPTION'][$i];
                                    }
                                }
                            }
                        }
                        $url = SELF_HREF . $modelObFields['CODE'] . '/';
                        if (!empty($modelObProps['LINK']['VALUE'])) {
                            $url = $modelObProps['LINK']['VALUE'];
                        }
                        $hideUrl = "N";
                        if (!empty($modelObProps['HIDE_URL']['VALUE'])) {
                            $hideUrl = "Y";
                        }
                        $ob['MODELS'][$modelObFields['ID']] = Array(
                            'NAME' => $modelObFields['NAME'],
                            'URL' => ($modelObFields['DETAIL_PICTURE'] || $modelObProps['LINK']['VALUE'] ? $url : ''),
                            'PREVIEW' => $modelObFields['PREVIEW_PICTURE'],
                            'PSEUDO' => !empty($modelObProps['LINK']['VALUE']),
                            'SORT' => $modelObFields['SORT'],
                            'CODE' => $modelObFields['CODE'],
                            'ID' => $modelObFields['ID'],
                            'HIDE_URL' => $hideUrl,
                        );
                    }
                    $ob['MODELS_PROPS'] = Array();
                    foreach ($props as $prop) {
                        if ($prop != 'N') {
                            $ob['MODELS_PROPS'][] = $prop;
                        }
                    }
                }
            }

            \CPolitek::getInstance()->files($ob);

            $this->arResult['SERIE'] = $ob;

            $this->arResult['BREADCRUMBS'] = Array(
                Array(
                    'NAME' => 'Оборудование',
                    'CODE' => 'catalog',
                )
            );
            $list = \CIBlockSection::GetNavChain(false, $ob['ID'], ['ID', 'NAME', 'DEPTH_LEVEL', 'CODE'], true);
            foreach ($list as $v) {
                if ($v['DEPTH_LEVEL'] > 1) {
                    $this->arResult['BREADCRUMBS'][] = $v;
                }
            }
            $codes = Array();
            foreach ($this->arResult['BREADCRUMBS'] as &$breadcrumbs) {
                $codes[] = $breadcrumbs['CODE'];
                $breadcrumbs['URL'] = '/' . implode('/', $codes) . '/';
            }
            $breadcrumbs['LAST'] = true;

            $neighborsRes = \CIblockSection::GetList(
                Array(
                    "SORT" => "ASC",
                ),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                    '!ID' => $ob['ID'],
                    'SECTION_ID' => $ob['IBLOCK_SECTION_ID'],
                ),
                false,
                Array('IBLOCK_ID', 'ID', 'CODE', 'NAME', 'PICTURE')
            );
            if ($neighborsRes->selectedRowsCount() > 0) {
                $this->arResult['SERIE']['NEIGHBORS'] = Array();
                while ($neighborsOb = $neighborsRes->fetch()) {
                    $neighborsOb['IMAGE'] = \CFile::ResizeImageGet($neighborsOb['PICTURE'], ['width' => 200, 'height' => 200], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                    $neighborsOb['URL'] = $this->arResult['BREADCRUMBS'][count($this->arResult['BREADCRUMBS']) - 2]['URL'] . $neighborsOb['CODE'] . '/';
                    $this->arResult['SERIE']['NEIGHBORS'][] = $neighborsOb;
                }
            }

            if (!empty($ob['UF_USAGE'])) {
                $this->arResult['USAGE'] = Array();
                $useRes = \CIBlockSection::getList(
                    Array(),
                    Array('IBLOCK_ID' => 36, 'ID' => $ob['UF_USAGE'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                    false,
                    Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'NAME', 'DESCRIPTION', 'UF_*', 'DEPTH_LEVEL')
                );
                if ($useRes->selectedRowsCount() > 0) {
                    while ($useOb = $useRes->fetch()) {
                        $useOb['UF_IMAGE_PREVIEW'] = \CFile::ResizeImageGet($useOb['UF_IMAGE'], ['width' => 300, 'height' => 200], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        \CPolitek::getInstance()->files($useOb);
                        $secRes = \CIBlockSection::getList(
                            Array(),
                            ARray('IBLOCK_ID' => 36, 'ID' => $useOb['ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
                            false,
                            Array('IBLOCK_ID', 'ID', 'NAME')
                        );
                        if ($secOb = $secRes->fetch()) {
                            $list = \CIBlockSection::GetNavChain(36, $secOb['ID'], ['ID', 'NAME', 'DEPTH_LEVEL'], true);
                            foreach ($list as $v) {
                                if ($v['DEPTH_LEVEL'] == 1) {
                                    $useOb['IBLOCK_SECTION_NAME'] = $v['NAME'];
                                }
                            }
                        }
                        $this->arResult['USAGE'][] = $useOb;
                    }
                }
                $this->arResult['USAGE_COUNT'] = 0;
                foreach ($this->arResult['USAGE'] as $use) {
                    if ($use['DEPTH_LEVEL'] > 1) {
                        $this->arResult['USAGE_COUNT']++;
                    }
                }
            }

            //подсерии
            $subRes = \CIblockSection::GetList(
                Array(
                    "SORT" => "ASC",
                ),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                    'SECTION_ID' => $this->arResult['SERIE']['ID'],
                ),
                false,
                Array('IBLOCK_ID', 'ID', 'NAME', 'CODE', 'PICTURE', 'SORT', 'DESCRIPTION', 'UF_SPECS', 'UF_FILE', 'UF_FILE_DESCRIPTION', 'UF_TEXT_PREVIEW', 'UF_USAGE')
            );
            if ($subRes->selectedRowsCount() > 0) {
                $this->arResult['SERIE']['SUBSERIES'] = Array();
                while ($subOb = $subRes->fetch()) {
                    \CPolitek::getInstance()->files($subOb);
                    if (!empty($subOb['UF_USAGE'])) {
                        $serieUsage = Array();
                        $useRes = \CIBlockSection::getList(
                            Array(),
                            Array('IBLOCK_ID' => 36, 'ID' => $subOb['UF_USAGE'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'),
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
                        $subOb['USAGE'] = $serieUsage;
                    }
                    $this->arResult['SERIE']['SUBSERIES'][$subOb['ID']] = $subOb;
                }
            }
        } else {
        }
        global $APPLICATION;
        /*if (empty($this->arResult['SERIE']['SUBSERIES'])) {
            $seo = new \Bitrix\Iblock\InheritedProperty\ElementValues(3, $elObFields['ID']);
            $seo = $seo->getValues();
            if (isset($seo['ELEMENT_META_TITLE'])) {
                $APPLICATION->SetPageProperty('TITLE', $seo['ELEMENT_META_TITLE']);
            }
            if (isset($seo['ELEMENT_META_KEYWORDS'])) {
                $APPLICATION->SetPageProperty('KEYWORDS', $seo['ELEMENT_META_KEYWORDS']);
            }
            if (isset($seo['ELEMENT_META_DESCRIPTION'])) {
                $APPLICATION->SetPageProperty('DESCRIPTION', $seo['ELEMENT_META_DESCRIPTION']);
            }
        } else {*/
            $seo = new \Bitrix\Iblock\InheritedProperty\SectionValues(3, $ob['ID']);
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
        //}
    }

    private function getParentSection($id)
    {
        $res = \CIBlockSection::getList(
            Array(),
            Array('IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ID' => $id),
            false,
            Array('NAME', 'UF_SUBTITLE', 'UF_TEXT1', 'UF_TEXT2', 'UF_LOGO', 'UF_FILE', 'UF_FILE_DESCRIPTION')
        );
        if ($ob = $res->fetch()) {
            if ($ob['UF_LOGO']) {
                $ob['UF_LOGO'] = \CFile::getPath($ob['UF_LOGO']);
            }
            if ($ob['UF_FILE']) {
                $ob['UF_FILE'] = \CFile::getPath($ob['UF_FILE']);
            }
            return $ob;
        }
    }

    private function getAdvantages($advs)
    {
        $settedFirst = false;
        foreach ($advs as &$adv) {
            $advRes = \CIblockElement::getList(
                Array(),
                Array('IBLOCK_ID' => 38, 'ID' => $adv),
                false,
                false,
                Array('IBLOCK_ID', 'ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'PROPERTY_TITLE', 'PROPERTY_SUBTITLE')
            );
            while ($advOb = $advRes->getNext()) {
                \CPolitek::getInstance()->files($advOb);
                if (!$settedFirst) {
                    $advOb['FIRST'] = true;
                    $settedFirst = true;
                }
                $adv = $advOb;
            }
        }
        return $advs;
    }

    private function groupModels()
    {
        $this->arResult['SERIE']['GROUPS'] = array();
        //foreach ($this->arResult['SERIE']['NEIGHBORS'] as $item) {
        foreach ($this->arResult['SERIE']['MODELS'] as $i => $item) {
            $itemNew['name'] = $item['NAME'];
            $itemNew['code'] = $item['CODE'];
            $itemNew['imgUrl'] = $item['PREVIEW'];
            //$itemNew['url'] = $this->arResult['BREADCRUMBS'][count($this->arResult['BREADCRUMBS']) - 2]['URL'] . $this->arResult['SERIE']['NEIGHBORS'][$i]['CODE'] . '/';
            //$itemNew['url'] = $this->arResult['BREADCRUMBS'][count($this->arResult['BREADCRUMBS']) - 2]['URL'] . $item['CODE'] . '/';
            $itemNew['url'] = current(
                array_filter(
                    $this->arResult['SERIE']['NEIGHBORS'],
                    function ($element) use ($item) {
                        return
                            preg_match("/" . $item['CODE'] . "\/$/", $element['URL'])
                            || preg_match("/" . preg_replace("/-/", "", $item['CODE']) . "\/$/", preg_replace("/-/", "", $element['URL']));
                    }
                )
            )['URL'];
            if (!$itemNew['url']) {
                $itemNew['url'] = SELF_HREF;
            }
            $group = trim(preg_replace("/[0-9]+.*/", "", $item['NAME']));
            if (!isset($this->arResult['SERIE']['GROUPS'][$group])) {
                $this->arResult['SERIE']['GROUPS'][$group] = array(
                    'id' => $group,
                    'models' => array()
                );
            }
            $this->arResult['SERIE']['GROUPS'][$group]['models'][] = $itemNew;
        }
        $this->arResult['SERIE']['GROUPS'] = array_values($this->arResult['SERIE']['GROUPS']);
    }

    private function mix4Carousel()
    {
        $this->arResult['SERIE']['CAROUSEL'] = Array();
        foreach ($this->arResult['SERIE']['MODELS'] as $model) {
            if (!$model['PSEUDO'] || $model['HIDE_URL'] == 'Y') {
                /*$serieSection = \CIblockElement::getList(
                    [],
                    ['IBLOCK_ID' => 3, 'PROPERTY_MODELS' => [$model['ID']]],
                    false,
                    false,
                    ['ID', 'IBLOCK_SECTION_ID']
                )->fetch()['IBLOCK_SECTION_ID'];
                $serieCode = \CIblockSection::getList(
                    [],
                    ['IBLOCK_ID' => 3, 'ID' => $serieSection],
                    false,
                    false,
                    ['CODE']
                )->fetch()['CODE'];*/
                $model['UR'] = '';
                //$model['UR'] = $serieSection . '|' . $serieCode . '|' . $model['ID'];
                //$model['UR'] = preg_replace("/(.*\/)[^\/]+\//", "$1", SELF_HREF) . $serieCode . '/';
            }
            $this->arResult['SERIE']['CAROUSEL'][] = $model;
        }
        foreach ($this->arResult['SERIE']['SUBSERIES'] as $serie) {
            $serie['URL'] = SELF_HREF . $serie['CODE'] . '/';
            $serie['PREVIEW'] = $serie['PICTURE'];
            $this->arResult['SERIE']['CAROUSEL'][] = $serie;
        }
        usort($this->arResult['SERIE']['CAROUSEL'], function($a, $b) {
            return intval($a['SORT']) - intval($b['SORT']);
        });
    }

    public function executeComponent()
    {
        $this->handler();
        $this->groupModels();
        $this->mix4Carousel();
        $this->includeComponentTemplate();
    }
}
