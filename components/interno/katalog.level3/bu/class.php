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

class CComponentCatalogLevel3 extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['FILTER'] = [
            'TYPE' => [],
            'PROP' => [],
        ];
        $this->arResult['TAGS'] = [
            'TAG' => [],
            'MAN' => [],
        ];
        $firstFilter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 40,
            'CODE' => end($this->arParams['PATH']),
        ];
        if (!empty($this->arParams['PARENT_SID'])) {
            $firstFilter['SECTION_ID'] = $this->arParams['PARENT_SID'];
        }
        $this->arResult['SECTION'] = \CIBlockSection::getList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            $firstFilter,
            false,
            ['*', 'UF_*']
        )->fetch();
        $this->arResult['ID'] = $this->arResult['SECTION']['ID'];

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
            //var_dump($sub_seo); die();
            $this->arResult['SUB_SECTION']['SEO'] = $sub_seo->getValues();
        }

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
                case 8://страна
                case 6://теговая
                    $this->arResult['TAGS']['TAG'][] = [
                        'URL' => '/catalog/' . end($this->arParams['PATH']) . '/' . $sub['CODE'] . '/',
                        'NAME' => $sub['NAME'],
                    ];
                    break;
                case 7://производитель
                    $this->arResult['TAGS']['MAN'][] = [
                        'URL' => '/manufacturers/' . $sub['CODE'] . '/' . end($this->arParams['PATH']) . '/',
                        'NAME' => $sub['NAME'],
                    ];
                    break;
                case 9://фильтр
                    break;
            }
            $filterRes = \CIBlockSection::getList(
                [],
                [
                    'ACTIVE' => 'Y',
                    'IBLOCK_ID' => 40,
                    'SECTION_ID' => ($this->arResult['SUB_SECTION']['ID'] ? $this->arResult['SUB_SECTION']['ID'] : $sub['ID']),
                    'UF_TYPE' => 9,
                    'CNT_ACTIVE' => 'Y'
                ],
                true,
                ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'UF_TYPE']
            );
            while ($filterOb = $filterRes->fetch()) {
                if (
                    in_array($filterOb['ID'], array_column($this->arResult['FILTER']['TYPE'], 'ID'))
                    || $filterOb['ELEMENT_CNT'] < 1
                ) {
                    continue;
                }
                $subFilter = [];
                $subFilterRes = \CIBlockSection::getList(
                    [],
                    [
                        'ACTIVE' => 'Y',
                        'IBLOCK_ID' => 40,
                        'SECTION_ID' => $filterOb['ID'],
                        'UF_TYPE' => 9,
                    ],
                    false,
                    ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'UF_TYPE']
                );
                while ($subFilterOb = $subFilterRes->fetch()) {
                    $subFilter[] = [
                        'ID' => $subFilterOb['ID'],
                        'CODE' => $subFilterOb['CODE'],
                        'NAME' => $subFilterOb['NAME'],
                        'UF_TYPE' => $subFilterOb['UF_TYPE'],
                    ];
                }
                $this->arResult['FILTER']['TYPE'][] = [
                    'ID' => $filterOb['ID'],
                    'CODE' => $filterOb['CODE'],
                    'NAME' => $filterOb['NAME'],
                    'SUB' => $subFilter,
                ];
            }
            $this->arResult['SUB_SECTIONS'][] = $sub;
        }

        $this->arResult['SECTION']['ITEMS'] = [];

        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => 40,
            'SECTION_ID' => $this->arResult['SECTION']['ID']
        ];

        if (!empty($this->arParams['FILTER'])) {
            foreach ($this->arParams['FILTER'] as $fk => $fv) {
                $filter[$fk] = $fv;
            }
        }

        $res = \CIBlockElement::getList(
            $this->arResult['PAGER']['SORT_BY'],
            $filter,
            false,
            [
                'iNumPage' => $this->arResult['PAGER']['PAGEN'],
                'nPageSize' => $this->arResult['PAGER']['ITEMS_PER_PAGE'],
            ]
        );
        $this->arResult["NAV_STRING"] = $res->GetPageNavStringEx(
            $navComponentObject,
            "",
            "",
            false,
            null
        );
        $this->arResult['NAV']["PageSize"] = $navComponentObject->arResult["NavPageSize"];
        $this->arResult['NAV']["RecordCount"] = $navComponentObject->arResult["NavRecordCount"];
        $this->arResult['NAV']["PageCount"] = $navComponentObject->arResult["NavPageCount"];
        $this->arResult['NAV']["PageNomer"] = $navComponentObject->arResult["NavPageNomer"];
        $this->arResult['NAV']['ItemsRemain'] = $navComponentObject->arResult["NavRecordCount"] - $navComponentObject->arResult["NavPageNomer"] * $navComponentObject->arResult["NavPageSize"];
        if ($this->arResult['NAV']['ItemsRemain'] < 0) {
            $this->arResult['NAV']['ItemsRemain'] = 0;
        }

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

            /*$props['SPECS_PRIMARY_FORMATED'] = '';
            foreach ($props['SPECS_PRIMARY'] as $spec) {
                $props['SPECS_PRIMARY_FORMATED'] .= $spec[0] . ': ' . $spec[1] . '<br>';
            }*/

            $props['SPECS_PRIMARY_FORMATED'] = '<table class="table-main-specs">';
            foreach ($props['SPECS_PRIMARY'] as $spec) {
                $props['SPECS_PRIMARY_FORMATED'] .= '<tr><td>' . $spec[0] . '</td><td>' . $spec[1] . '</td></tr>';
            }
            $props['SPECS_PRIMARY_FORMATED'] .= '</table>';

            $props['FILE']['VALUE'] = \CFile::getPath($props['FILE']['VALUE']);
            if (!empty($this->arResult['FILTER']['TYPE'])) {
                /*if (in_array($filterOb['ID'], array_column($this->arResult['FILTER']['TYPE'], 'ID'))) {
                    continue;
                }*/
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
                    /*foreach ($props['FULL_SPECS']['VALUE'] as $i => $id) {
                        if (empty($this->arResult['FILTER']['PROP_FULL'][$id])) {
                            $this->arResult['FILTER']['PROP_FULL'][$id] = [
                                'NAME' => \CIBlockElement::getList([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 39, 'ID' => $id], false, false, ['NAME'])->fetch()['NAME'],
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
                    }*/
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
            $this->arResult['SECTION']['ITEMS'][] = array_merge($ob, ['PROPS' => $props]);
        }
        if ($this->arResult['PAGER']['SORT_NAME'] == 'default') {
            usort($this->arResult['SECTION']['ITEMS'], function($a, $b) {
                if (intval($a['SORT']) != intval($b['SORT'])) {
                    return intval($a['SORT']) - intval($b['SORT']);
                } else {
                    return strnatcasecmp($a['NAME'], $b['NAME']);
                }
            });
        }
        global $APPLICATION;
        $seo = new \Bitrix\Iblock\InheritedProperty\SectionValues(40, $this->arResult['ID']);
        $seo = $seo->getValues();
        $this->arResult['SEO'] = $seo;
        //var_dump($this->arResult['SUB_SECTION']['SEO']);die();
        if (!empty($this->arResult['SUB_SECTION']['SEO'])) {
            $seo = $this->arResult['SUB_SECTION']['SEO'];
            $this->arResult['SEO'] = $seo;
            //var_dump($seo);die();
        }

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

    private function pager()
    {
        $this->arResult['PAGER'] = [
            'ITEMS_PER_PAGE' => 30,
            'SORT_BY' => ['SORT' => 'ASC', 'NAME' => 'ASC'],
            'SORT_NAME' => 'default',
            'NAME' => 'По умолчанию',
            'PAGEN' => 1,
        ];

        if (!empty($_COOKIE['itemsPerPage'])) {
            $this->arResult['PAGER']['ITEMS_PER_PAGE'] = $_COOKIE['itemsPerPage'];
        }

        if (!empty($_COOKIE['sortBy'])) {
            switch ($_COOKIE['sortBy']) {
                case 'popular':
                    $this->arResult['PAGER']['SORT_BY'] = ['PROPERTY_POPULAR' => 'DESC', 'SORT' => 'ASC', 'NAME' => 'ASC'];
                    $this->arResult['PAGER']['SORT_NAME'] = 'popular';
                    $this->arResult['PAGER']['NAME'] = 'По популярности';
                    break;
                case 'available':
                    $this->arResult['PAGER']['SORT_BY'] = ['PROPERTY_SUPPLYSTATUS' => 'ASC', 'SORT' => 'ASC', 'NAME' => 'ASC'];
                    $this->arResult['PAGER']['SORT_NAME'] = 'available';
                    $this->arResult['PAGER']['NAME'] = 'По наличию';
                    break;
                default:
                    $this->arResult['PAGER']['SORT_BY'] = ['SORT' => 'ASC', 'NAME' => 'ASC'];
                    $this->arResult['PAGER']['SORT_NAME'] = 'default';
                    $this->arResult['PAGER']['NAME'] = 'По умолчанию';
            }
        }
        if (!empty($_REQUEST['pagen'])) {
            $this->arResult['PAGER']['PAGEN'] = $_REQUEST['pagen'];
        }
    }

    public function executeComponent()
    {
        $this->pager();
        $this->handler();
        $this->includeComponentTemplate();
    }
}
