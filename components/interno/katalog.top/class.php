<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class CComponentCatalogTop extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['SECTIONS'] = Array();
        $this->arResult['CATALOG'] = Array();

        $res = \CIblockSection::GetList(
            Array(
                'DEPTH_LEVEL' => 'ASC',
                'SORT' => 'ASC',
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                '<DEPTH_LEVEL' => 4,
                'UF_TYPE' => false,
            ),
            false,
            Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', 'PICTURE', 'DEPTH_LEVEL')
        );

        while ($ob = $res->fetch()) {
            $ob['IMAGE'] = \CFile::getPath($ob['PICTURE']);
            $this->arResult['SECTIONS'][$ob['ID']] = $ob;
        }

        foreach ($this->arResult['SECTIONS'] as $section) {
            if ($section['DEPTH_LEVEL'] == 1) {
                //echo "[{$section['ID']} {$section['NAME']}] ";
                $this->arResult['CATALOG'][$section['ID']] = [
                    'NAME' => $section['NAME'],
                    'URL' => '/catalog/' . $section['CODE'] . '/',
                    'SECTIONS' => [],
                ];
            }
        }
        foreach ($this->arResult['SECTIONS'] as $section) {
            if ($section['DEPTH_LEVEL'] == 2) {
                //echo "[[{$section['ID']} {$section['NAME']}]] ";
                $this->arResult['CATALOG'][$section['IBLOCK_SECTION_ID']]['SECTIONS'][$section['ID']] = [
                    'NAME' => $section['NAME'],
                    'URL' => '/catalog/' . $section['CODE'] . '/',
                    'IMAGE' => $section['IMAGE'],
                    'SECTIONS' => [],
                ];
            }
        }
        foreach ($this->arResult['SECTIONS'] as $section) {
            if ($section['DEPTH_LEVEL'] == 3) {
                //echo $this->arResult['SECTIONS'][$section['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID'], ' | ';
                $this->arResult['CATALOG']
                [$this->arResult['SECTIONS'][$section['IBLOCK_SECTION_ID']]['IBLOCK_SECTION_ID']]
                ['SECTIONS'][$section['IBLOCK_SECTION_ID']]['SECTIONS'][$section['ID']] = [
                    'NAME' => $section['NAME'],
                    'URL' => '/catalog/' . $section['CODE'] . '/',
                    'IMAGE' => $section['IMAGE'],
                ];
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
