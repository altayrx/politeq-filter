<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}

class CComponentPopQuests extends \CBitrixComponent
{
    private function handler()
    {
        $this->arResult['ITEMS'] = [];

        $res = \CIblockElement::GetList(
            ['SORT' => 'ASC'],
            Array(
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'ACTIVE' => 'Y',
            ),
            false,
            ['nTopCount' => 10],
            ['ID', 'NAME', 'DETAIL_TEXT']
        );

        while ($ob = $res->fetch()) {
            $this->arResult['ITEMS'][] = $ob;
        }
    }

    public function executeComponent()
    {
        $this->handler();
        $this->includeComponentTemplate();
    }
}
