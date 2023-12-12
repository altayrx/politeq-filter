<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class CComponentCatalogEquipment extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['EQUIPMENT'] = Array();

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
                //"active_from" => "DESC",
                "SORT" => "ASC",
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
                'DEPTH_LEVEL' => 1,
            ),
            false,
            Array('IBLOCK_ID', 'ID', 'NAME','CODE')
        );

        $decor = true;
        while ($ob = $res->fetch()) {
            $this->arResult['EQUIPMENT'][$ob['ID']] = $ob;
            $this->arResult['EQUIPMENT'][$ob['ID']]['DECOR'] = $decor;
            $res2 = \CIblockSection::GetList(
                Array(
                    //"active_from" => "DESC",
                    "SORT" => "ASC",
                ),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'SECTION_ID' => $ob['ID'],
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => 2,
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
            $res2 = \CIblockSection::GetList(
                Array(
                    //"active_from" => "DESC",
                    "SORT" => "ASC",
                ),
                Array(
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'UF_CATALOG_GROUP' => $ob['ID'],
                    'ACTIVE' => 'Y',
                    'DEPTH_LEVEL' => 2,
                ),
                false,
                Array('IBLOCK_ID', 'ID', 'NAME', 'PICTURE', 'CODE', 'UF_TEXT_PREVIEW', 'UF_LINK_CHECK')
            );
            if ($res2->selectedRowsCount() > 0) {
                if (empty($this->arResult['EQUIPMENT'][$ob['ID']]['BRANDS'])) {
                    $this->arResult['EQUIPMENT'][$ob['ID']]['BRANDS'] = Array();
                }
                while ($ob2 = $res2->fetch()) {
                    $ob2['IMAGE'] = \CFile::getPath($ob2['PICTURE']);
                    if ((!empty($ob2['UF_LINK_CHECK']) && $ob2['UF_LINK_CHECK'] == 1)) {
                        $ob2['DISABLED'] = true;
                    }
                    $this->arResult['EQUIPMENT'][$ob['ID']]['BRANDS'][] = $ob2;
                }
            }
            $decor = !$decor;
        }
    }

    public function executeComponent()
    {
        $this->handler();
        $this->includeComponentTemplate();
    }
}
