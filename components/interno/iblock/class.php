<?php
use \Bitrix\Iblock\Component\ElementList;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class ComponentIBlock extends \CBitrixComponent
{
    private function iBlockElementsList()
    {
        if (!$this->arParams['LIST_NAME']) {
            $this->arParams['LIST_NAME'] = 'ELEMENTS';
        }
        $res = CIblockElement::GetList(
            Array(
                "active_from" => "DESC",
                "SORT" => "ASC"
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
            ),
            false,
            false
        );

        $this->arResult[$this->arParams['LIST_NAME']] = Array();
        while ($ob = $res->getNextElement()) {
            $this->arResult[$this->arParams['LIST_NAME']][$ob['ID']] = $ob->GetFields();
            $this->arResult[$this->arParams['LIST_NAME']][$ob['ID']]['PROPS'] = $ob->GetProperties();
        }
    }

    private function iBlockElement()
    {
        $res = CIblockElement::GetList(
            Array(
                "active_from" => "DESC",
                "SORT" => "ASC"
            ),
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
            ),
            false,
            false
        );

        while ($ob = $res->getNextElement()) {
            $this->arResult = $ob->GetFields();
            $this->arResult['PROPS'] = $ob->GetProperties();
        }
    }

    public function executeComponent()
    {
        switch ($this->arParams['TYPE']) {
            case 'list':
                $this->iBlockElementsList();
                break;
            case 'element':
                $this->iBlockElement();
                break;
        }
        $this->includeComponentTemplate();
    }
}
