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
            Array(),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'CODE' => end($this->arParams['PATH']),
            ),
            false,
            false
        );

        if ($ob = $res->getNextElement()) {
            $this->arResult = $ob->getFields();
            $this->arResult['PROPS'] = $ob->getProperties();

            $this->arResult['GALLERY_PHOTO'] = $this->arResult['PROPS']['GALLERY_PHOTO']['VALUE'];
            $this->arResult['GALLERY_PHOTO_DESCRIPTION'] = $this->arResult['PROPS']['GALLERY_PHOTO']['DESCRIPTION'];

            \CPolitek::getInstance()->files($this->arResult);

            $this->arResult['GALLERY_PHOTO'] = array_map(function($value, $description) {
                return Array(
                    'VALUE' => $value,
                    'DESCRIPTION' => $description,
                );
            }, $this->arResult['GALLERY_PHOTO'], $this->arResult['GALLERY_PHOTO_DESCRIPTION']);

            $this->arResult['GALLERY_VIDEO'] = $this->arResult['PROPS']['GALLERY_VIDEO']['VALUE'];
            $this->arResult['GALLERY_VIDEO_DESCRIPTION'] = $this->arResult['PROPS']['GALLERY_VIDEO']['DESCRIPTION'];
            $this->arResult['GALLERY_VIDEO'] = array_map(function($value, $description) {
                return Array(
                    'VALUE' => $value,
                    'DESCRIPTION' => $description,
                );
            }, $this->arResult['GALLERY_VIDEO'], $this->arResult['GALLERY_VIDEO_DESCRIPTION']);

            foreach ($this->arResult['PROPS']['FEATURES']['VALUE'] as &$feature) {
                $tmp = json_decode(htmlspecialchars_decode($feature), 1)['VALUE'];
                $feature = Array(
                    'TITLE' => $tmp['TITLE'],
                    'SUBTITLE' => $tmp['SUBTITLE'],
                    'TEXT' => $tmp['TEXT'],
                );
            }
            $settedFirst = false;
            foreach ($this->arResult['PROPS']['ADVANTAGES']['VALUE'] as &$adv) {
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

            $this->arResult['SPECS_TOP'] = Array();
            $this->arResult['SPECS_BOTTOM'] = Array();
            foreach ($this->arResult['PROPS']['SPECS']['VALUE'] as $i => $spec) {
                $specRes = \CIblockElement::getList(
                    Array('SORT' => 'ASC'),
                    Array(
                        'IBLOCK_ID' => 39,
                        'ID' => $spec,
                        Array(
                            'LOGIC' => 'OR',
                            Array('!PROPERTY_IN_MODEL_TOP' => false),
                            Array('!PROPERTY_IN_MODEL_BOTTOM' => false),
                        ),
                    ),
                    false,
                    false,
                    Array('IBLOCK_ID', 'ID', 'NAME', 'IBLOCK_SECTION_ID', 'PROPERTY_IN_MODEL_TOP', 'PROPERTY_IN_MODEL_BOTTOM', 'SORT')
                );
                while ($specOb = $specRes->getNext()) {
                    if ($specOb['PROPERTY_IN_MODEL_TOP_VALUE']) {
                        $this->arResult['SPECS_TOP'][] = Array(
                            'NAME' => $specOb['NAME'],
//                            'VALUE' => unserialize($this->arResult['PROPS']['SPECS']['~DESCRIPTION'][$i]),
                            'VALUE' => $this->arResult['PROPS']['SPECS']['~DESCRIPTION'][$i],
                        );
                    }
                    if ($specOb['PROPERTY_IN_MODEL_BOTTOM_VALUE']) {
                        if (!isset($this->arResult['SPECS_BOTTOM'][$specOb['IBLOCK_SECTION_ID']])) {
                            $specsSection = \CIBlockSection::getList([], ['IBLOCK_ID' => 39, 'ID' => $specOb['IBLOCK_SECTION_ID']], false, ['IBLOCK_ID', 'ID', 'NAME', 'SORT'])->fetch();
                            $this->arResult['SPECS_BOTTOM'][$specOb['IBLOCK_SECTION_ID']] = Array(
                                'NAME' => $specsSection['NAME'],
                                'SPECS' => Array(),
                                'SORT' => $specsSection['SORT'],
                            );
                        }
                        $this->arResult['SPECS_BOTTOM'][$specOb['IBLOCK_SECTION_ID']]['SPECS'][] = Array(
                            'NAME' => $specOb['NAME'],
                            'SORT' => $specOb['SORT'],
//                            'VALUE' => unserialize($this->arResult['PROPS']['SPECS']['~DESCRIPTION'][$i]),
                            'VALUE' => $this->arResult['PROPS']['SPECS']['~DESCRIPTION'][$i],
                        );
                    }
                }
            }

            usort($this->arResult['SPECS_BOTTOM'], function($a, $b) {
                if (intval($a['SORT']) == intval($b['SORT'])) {
                    return 0;
                }
                return (intval($a['SORT']) < intval($b['SORT'])) ? -1 : 1;
            });
            foreach ($this->arResult['SPECS_BOTTOM'] as &$specsSections) {
                usort($specsSections['SPECS'], function($a, $b) {
                    if (intval($a['SORT']) == intval($b['SORT'])) {
                        return 0;
                    }
                    return (intval($a['SORT']) < intval($b['SORT'])) ? -1 : 1;
                });
            }

            $this->arResult['SERIE_MODELS'] = Array();
            $serieRes = \CIBlockElement::getList(
                Array(),
                Array('IBLOCK_ID' => 3, 'ACTIVE' => 'Y', 'PROPERTY_MODELS' => $this->arResult['ID']),
                false,
                false
            );
            if ($serieOb = $serieRes->getNextElement()) {
                $this->arResult['SERIE_ID'] = $serieOb->getFields()['IBLOCK_SECTION_ID'];
                $this->arResult['SERIE_NAME'] = trim(str_ireplace(Array('СЕРИЯ', 'Серия', 'серия'), '', $serieOb->getFields()['NAME']));
                $modelsRes = \CIBlockElement::getList(
                    Array(),
                    Array('IBLOCK_ID' => 12, 'ID' => $serieOb->getProperties()['MODELS']['VALUE']),
                    false,
                    false,
                    Array('IBLOCK_ID', 'ID', 'CODE', 'NAME', 'DETAIL_PICTURE', 'PROPERTY_LINK')
                );
                while ($modelsOb = $modelsRes->getNext()) {
                    $url = (
                        !empty($modelsOb['PROPERTY_LINK_VALUE']) ? $modelsOb['PROPERTY_LINK_VALUE']
                        : (!empty($modelsOb['DETAIL_PICTURE']) ? $this->arParams['PATH_PREV'] . $modelsOb['CODE'] . '/' : '')
                    );
                    $this->arResult['SERIE_MODELS'][] = Array(
                        'NAME' => $modelsOb['NAME'],
                        'URL' => $url,
                        'TTT' => $modelsOb['DETAIL_PICTURE'],
                    );
                }
                $serieSectionRes = \CIblockSection::GetList(
                    Array(
                        "SORT" => "ASC",
                    ),
                    Array(
                        'IBLOCK_ID' => 3,
                        'ID' => $serieOb->getFields()['IBLOCK_SECTION_ID'],
                    ),
                    false,
                    Array('UF_*')
                );
$this->arResult['U1T'] = $serieSectionRes->selectedRowsCount();
                if ($serieSectionOb = $serieSectionRes->fetch()) {
                    if (!empty($serieSectionOb['UF_USAGE'])) {
                        $this->arResult['USAGE'] = Array();
                        $useRes = \CIBlockSection::getList(
                            Array(),
                            Array('IBLOCK_ID' => 36, 'ID' => $serieSectionOb['UF_USAGE'], 'ACTIVE' => 'Y'),
                            false,
                            Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'NAME', 'DESCRIPTION', 'UF_*', 'DEPTH_LEVEL')
                        );
                        if ($useRes->selectedRowsCount() > 0) {
                            while ($useOb = $useRes->fetch()) {
                                $useOb['UF_IMAGE_PREVIEW'] = \CFile::ResizeImageGet($useOb['UF_IMAGE'], ['width' => 300, 'height' => 200], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                                \CPolitek::getInstance()->files($useOb);
                                $secRes = \CIBlockSection::getList(
                                    Array(),
                                    ARray('IBLOCK_ID' => 36, 'ID' => $useOb['ID'], 'ACTIVE' => 'Y'),
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
                }
            }

            /*if (!empty($this->arResult['PROPS']['USAGE']['VALUE'])) {
                $this->arResult['USAGE'] = Array();
                $useRes = \CIBlockSection::getList(
                    Array(),
                    Array('IBLOCK_ID' => 36, 'ID' => $this->arResult['PROPS']['USAGE']['VALUE'], 'ACTIVE' => 'Y'),
                    false,
                    Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'NAME', 'DESCRIPTION', 'UF_*', 'DEPTH_LEVEL')
                );
                if ($useRes->selectedRowsCount() > 0) {
                    while ($useOb = $useRes->fetch()) {
                        $useOb['UF_IMAGE_PREVIEW'] = \CFile::ResizeImageGet($useOb['UF_IMAGE'], ['width' => 300, 'height' => 200], BX_RESIZE_IMAGE_PROPORTIONAL)['src'];
                        \CPolitek::getInstance()->files($useOb);
                        $secRes = \CIBlockSection::getList(
                            Array(),
                            ARray('IBLOCK_ID' => 36, 'ID' => $useOb['ID'], 'ACTIVE' => 'Y'),
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
            }*/
        $serieRes = \CIblockSection::GetList(
            Array(
                "SORT" => "ASC",
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ID' => $this->arResult['SERIE_ID'],
            ),
            false,
            Array('UF_USAGE')
        );

        }
        $this->arResult['BREADCRUMBS'] = Array(
            Array(
                'NAME' => 'Оборудование',
                'CODE' => 'catalog',
            )
        );
        $list = \CIBlockSection::GetNavChain(false, $this->arResult['SERIE_ID'], ['ID', 'NAME', 'DEPTH_LEVEL', 'CODE', 'UF_SERIE_GAG'], true);
        foreach ($list as $v) {
            $gag = \CIBlockSection::getList([], ['IBLOCK_ID' => 3, 'ID' => $v['ID']], false, ['UF_SERIE_GAG'])->fetch()['UF_SERIE_GAG'];
            if ($v['DEPTH_LEVEL'] > 1 && $v['DEPTH_LEVEL'] < 4 && !$gag) {
                $this->arResult['BREADCRUMBS'][] = $v;
            }
        }
        $this->arResult['BREADCRUMBS'][] = Array(
            'NAME' => $this->arResult['NAME'],
        );
        $codes = Array();
        foreach ($this->arResult['BREADCRUMBS'] as &$breadcrumbs) {
            $codes[] = $breadcrumbs['CODE'];
            $breadcrumbs['URL'] = '/' . implode('/', $codes) . '/';
        }
        $breadcrumbs['LAST'] = true;
        global $APPLICATION;
        $seo = new \Bitrix\Iblock\InheritedProperty\ElementValues(12, $this->arResult['ID']);
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
    }

    public function executeComponent()
    {
        $this->handler();
        $this->includeComponentTemplate();
    }
}
