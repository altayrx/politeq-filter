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

class CComponentCatalogDetail extends \CBitrixComponent
{
    private function handler()
    {
        $res = \CIblockElement::GetList(
            [],
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'CODE' => end($this->arParams['PATH']),
            ),
            false,
            ['nTopCount' => 1]
        );

        if ($ob = $res->getNextElement()) {
            $product = $ob->getFields();
            \CPolitek::getInstance()->files($product);
            $product['PROPS'] = $ob->getProperties();

            if (!empty($product['PROPS']['GAL_PHOTO']['VALUE'])) {
                $product['PHOTOGALL'] = Array();
                foreach ($product['PROPS']['GAL_PHOTO']['VALUE'] as $index => $photogall) {
                    $photogallvalue = \CFile::ResizeImageGet($photogall, ['width' => 1000, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                    $photogallminivalue = \CFile::ResizeImageGet($photogall, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                    $product['PHOTOGALL'][] = Array('VALUE' => $photogallvalue,'MINIVALUE' => $photogallminivalue, 'DESCRIPTION' => $product['PROPS']['GAL_PHOTO']['DESCRIPTION'][$index]);
                }
            }
            if (!empty($product['PROPS']['GAL_VIDEO']['VALUE'])) {
                $product['VIDEOGALL'] = Array();
                foreach ($product['PROPS']['GAL_VIDEO']['VALUE'] as $index => $videogall) {
                    $videogallvalue = \CFile::ResizeImageGet($videogall, ['width' => 1000, 'height' => 1000], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                    $videogallminivalue = \CFile::ResizeImageGet($videogall, ['width' => 300, 'height' => 300], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                    $product['VIDEOGALL'][] = Array('VALUE' => $videogallvalue,'MINIVALUE' => $videogallminivalue, 'DESCRIPTION' => $product['PROPS']['GAL_VIDEO']['DESCRIPTION'][$index]);
                }
            }

            $product['MODELS'] = [];
            $product['MODELS_PROPS'] = [];
            $modelsRes = \CIBlockElement::getList(
                ['SORT' => 'ASC'],
                //['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'SECTION_ID' => $product['IBLOCK_SECTION_ID']],
                ['ACTIVE' => 'Y', 'IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'SECTION_CODE' => $this->arParams['PATH'][count($this->arParams['PATH']) - 2]],
                false,
                false/*,
                ['ID', 'NAME', 'PROPERTY_SPECS_FULL']*/
            );
            $product['MODELS_TEST'] = ['init'];
            $props = [];
            while ($modelsOb = $modelsRes->getNextElement()) {
                $modelsObFields = $modelsOb->getFields();
                $modelsObFields['URL'] = '/catalog/' . $this->arParams['PATH'][count($this->arParams['PATH']) - 2] . '/' . $modelsObFields['CODE'] . '/';
                $modelsObProps = $modelsOb->getProperties();
                //$product['MODELS'][$modelsObFields['ID']] = $modelsObProps;
                //$product['MODELS'][$modelsObFields['ID']] = $modelsObProps['SPECS_FULL'];
                foreach ($modelsObProps['SPECS_FULL']['VALUE'] as $i => $spec) {
                    $product['MODELS_TEST'][] = [$i, $spec];
                    $specRes = \CIblockElement::getList(
                        Array(),
                        Array(
                            'IBLOCK_ID' => 39,
                            'ID' => $spec,
                            'ACTIVE' => 'Y',
                            //'!PROPERTY_IN_SERIES' => false,
                        ),
                        false,
                        false,
                        Array('IBLOCK_ID', 'ID', 'NAME', 'DETAIL_PICTURE')
                    );
                    //$product['MODELS_TEST'][] = $specRes->selectedRowsCount();
                    if ($specOb = $specRes->getNext()) {
                        if (!isset($props[$spec]) && !empty($modelsObProps['SPECS_FULL']['~DESCRIPTION'][$i])) {
                            $props[$spec] = Array(
                                'NAME' => $specOb['NAME'],
                                'VALUES' => Array()
                            );
                        }
                        if (1 == 1 && $modelsObFields['ID'] == $product['ID']) {
                            $props[$spec]['VALUES'][0] = unserialize($modelsObProps['SPECS_FULL']['~DESCRIPTION'][$i]);
                        } else {
                            $props[$spec]['VALUES'][$modelsObFields['ID']] = unserialize($modelsObProps['SPECS_FULL']['~DESCRIPTION'][$i]);
                        }
                    }
                }
                /*foreach ($props as $prop) {
                    if ($prop != 'N') {
                        $product['MODELS_PROPS'][] = $prop;
                    }
                }*/
                if (1 == 1 && $modelsObFields['ID'] == $product['ID']) {
                    $product['MODELS'][0] = $modelsObFields;
                } else {
                    $product['MODELS'][$modelsObFields['ID']] = $modelsObFields;
                }
            }
            $currentProduct = $product['MODELS'][0];
            unset($product['MODELS'][0]);
            $product['MODELS'] = [$currentProduct] + $product['MODELS'];
            $product['MODELS_PROPS'] = $props;

            $this->arResult = $product;
            $PROPERTY_VALUE = \CIBlockElement::GetPropertyValues(40, array('ID' => $this->arResult['ID']), false, array('ID' => array(300)))->fetch()[300] + 1;
            \CIBlockElement::SetPropertyValuesEx($this->arResult['ID'], 40, array(300 => $PROPERTY_VALUE));
            //$this->arResult['NAME'] .= '[' . json_encode($PROPERTY_VALUE) . ']';
            if ($this->arResult['PROPS']['QUANTITY']['VALUE'] && !$this->arResult['PROPS']['QUANTITY_TYPE']['VALUE']) {
                $this->arResult['PROPS']['QUANTITY_TYPE']['VALUE'] = 'Доступно к продаже:';
            }
            /*$this->arResult['BREADCRUMBS'] = Array(
                Array(
                    'NAME' => 'Оборудование',
                    'CODE' => 'catalog',
                )
            );*/
            $sectionID = \CIBlockSection::getList(
                [],
                ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'CODE' => $this->arParams['PATH'][count($this->arParams['PATH']) - 2]],
                false,
                ['ID']
            )->fetch()['ID'];
            //$list = \CIBlockSection::GetNavChain(false, $product['IBLOCK_SECTION_ID'], ['ID', 'NAME', 'DEPTH_LEVEL', 'CODE'], true);
            $list = \CIBlockSection::GetNavChain(false, $sectionID, ['ID', 'NAME', 'DEPTH_LEVEL', 'CODE'], true);
            foreach ($list as $v) {
                if ($v['DEPTH_LEVEL'] > 1) {
                    $this->arResult['BREADCRUMBS'][] = $v;
                }
            }
            $codes = Array();
            $this->arResult['TEST_TEST'] = [$product['IBLOCK_SECTION_ID'], $list];
            foreach ($this->arResult['BREADCRUMBS'] as &$breadcrumbs) {
                $codes = [$breadcrumbs['CODE']];
                $breadcrumbs['URL'] = '/catalog/' . implode('/', $codes) . '/';
            }
            $this->arResult['BREADCRUMBS'][] = Array(
                'NAME' => $this->arResult['NAME'],
                'CODE' => $this->arResult['CODE'],
                'URL' => $this->arResult['BREADCRUMBS']['URL'] . $this->arResult['CODE'] . '/',
                'LAST' => true,
            );
            //$breadcrumbs['LAST'] = true;
            global $APPLICATION;
            $seo = new \Bitrix\Iblock\InheritedProperty\ElementValues(40, $this->arResult['ID']);
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

            return;

/**/

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
                                        $props[$spec]['VALUES'][$modelObFields['ID']] = unserialize($prop['~DESCRIPTION'][$i]);
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
        $this->arResult['MODELZ'] = [];
        $modelsRes = \CIBlockElement::getList(
            ['SORT' => 'ASC'],
            ['ACTIVE' => 'Y', 'IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'NAME' => explode(' ', $this->arResult['NAME'])[0] . '%'],
            false,
            false,
            ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'IBLOCK_SECTION_ID']
        );

        while ($modelsOb = $modelsRes->fetch()) {
            $sectionCODE = \CIBlockSection::getList(
                [],
                ['IBLOCK_ID' => $this->arParams['IBLOCK_ID'], 'ID' => $modelsOb['IBLOCK_SECTION_ID']],
                false,
                ['CODE']
            )->fetch()['CODE'];

            $modelsOb['SID'] = $modelsOb['SECTION_ID'];
            $modelsOb['URL'] = '/catalog/' . $sectionCODE . '/' . $modelsOb['CODE'] . '/';
            if ($this->arResult['ID'] == $modelsOb['ID']) {
                $this->arResult['MODELZ'][0] = $modelsOb;
            } else {
                $this->arResult['MODELZ'][$modelsOb['ID']] = $modelsOb;
            }
        }

        $currentProduct = $this->arResult['MODELZ'][0];
        unset($this->arResult['MODELZ'][0]);
        $this->arResult['MODELZ'] = [$currentProduct] + $this->arResult['MODELZ'];

        $this->arResult['GROUPS'] = [];
        $curBrand = explode(' ', $this->arResult['NAME']);
        foreach ($this->arResult['MODELZ'] as $i => $item) {
            $brand = explode(' ', $item['NAME']);
            if ($curBrand[0] != $brand[0]) {
                continue;
            }
            $itemNew['name'] = $item['NAME'];
            $itemNew['code'] = $item['CODE'];
            $itemNew['imgUrl'] = \CFile::getPath($item['PREVIEW_PICTURE']);
            $itemNew['url'] = $item['URL'];
            $itemNew['sid'] = $item['SID'];
            if (!$itemNew['url']) {
                $itemNew['url'] = SELF_HREF;
            }
            $group = trim(preg_replace("/[0-9]+.*/", "", $item['NAME']));
            if (!isset($this->arResult['GROUPS'][$group])) {
                $this->arResult['GROUPS'][$group] = array(
                    'id' => $group,
                    'models' => array()
                );
            }
            $this->arResult['GROUPS'][$group]['models'][] = $itemNew;
        }
        $this->arResult['GROUPS'] = array_values($this->arResult['GROUPS']);
        if (count($this->arResult['GROUPS']) < 2) {
            unset($this->arResult['GROUPS']);
        }
    }

    private function mix4Carousel()
    {
        $this->arResult['SERIE']['CAROUSEL'] = Array();
        foreach ($this->arResult['SERIE']['MODELS'] as $model) {
            if (!$model['PSEUDO'] || $model['HIDE_URL'] == 'Y') {
                $model['UR'] = '';
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
        //$this->mix4Carousel();
        $this->includeComponentTemplate();
    }
}
