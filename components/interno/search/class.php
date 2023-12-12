<?php
use \Bitrix\Iblock\Component\ElementList;
use Bitrix\Main\Grid\Declension;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    ShowError('Модуль iblock не установлен');
    return;
}
if (!\Bitrix\Main\Loader::includeModule('search')) {
    ShowError('Модуль поиска не установлен');
    return;
}

class CWGSearch extends \CBitrixComponent
{
    public $arResult = Array();

    protected function checkParams()
    {
        $this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);
        return true;
    }

    private $sectionsGlobalLimit = 1000;
    private $sectionsLimit = 8;
    private $goodsLimit = 1500;
    private $page = 1;

    private static $statusesWeights = Array(
        13 => 1,//<span>В наличии</span> на складе в РФ
        14 => 2,//<span>В наличии</span> на складе производителя
        15 => 3,//<span>Под заказ</span>
        16 => 4,//<span>Короткий срок поставки</span>
        17 => 5,//<span>Готов к отгрузке</span>
        18 => 6,//<span>На складе</span>
    );

    private static $qArr = [];

    private function test()
    {
        $q = explode(" ", $this->arParams['SEARCH_QUERY']);
        array_walk($q, function(&$qi) {
            $qi = mb_strtolower($qi);
        });
        global $DB;

        $tmp_str = [];

        $r_add = [];
        $replcements = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/data/replace.txt');
        $replcements = explode("\n", $replcements);
        array_walk($replcements, function(&$r) {
            if (!empty($r)) {
                $r = explode(">", $r);
            }
        });

        foreach ($replcements as $r) {
            if (!empty($r) && count($r) == 2) {
                if (preg_match("/^\~/", $r[0])) {
                    $r[0] = preg_replace("/^\~/", "", $r[0]);
                    foreach ($q as $qi) {
                        $qs = stemming($qi);
                        $rs = stemming($r[0]);
                        foreach ($qs as $qsi => $qsv) {
                            foreach ($rs as $rsi => $rsv) {
                                if ($qsi == $rsi) {
                                    $r_add[] = $r[1];
                                    unset($q[array_search($r[0], $q)]);
                                    break 3;
                                }
                            }
                        }
                    }
                } else {
                    if (in_array(mb_strtolower($r[0]), $q)) {
                        $r_add[] = $r[1];
                        unset($q[array_search($r[0], $q)]);
                    }
                }
            }
        }
        if (!empty($r_add)) {
            $q = array_merge($q, $r_add);
        }
        self::$qArr = $q;

        /**
         * + НАЗВАНИЕ/ТИП ОБОРУДОВАНИЯ/БРЕНД
         * - страна/ПРОИЗВОДИТЕЛЬ/название раздела/название подраздела
         */
        foreach ($q as $q_str) {
            if (mb_strlen($q_str) > 0) {
                $q_str_s = stemming(preg_replace("/[\.,\/]/", "", $q_str));
                if (count($q_str_s) == 1 && isset($q_str_s[$q_str])) {
                    $tmp_str[] = '(e.NAME LIKE "%' . $q_str . '%" OR REPLACE(REPLACE(e.NAME, " ", ""), "-", "") LIKE "%' . preg_replace("/[- \.]/", "", $q_str) . '%" OR s.PROPERTY_280 LIKE "%' . $q_str . '%" OR s.PROPERTY_285 LIKE "%' . $q_str . '%" OR s.PROPERTY_301 LIKE "%' . $q_str . '%" OR ss.NAME LIKE "%' . $q_str . '%" OR ss2.NAME LIKE "%' . $q_str . '%" )';
                } else {
                    $s_add = '';
                    foreach ($q_str_s as $q_str_s_i => $$q_str_s_v) {
                        $s_add .= ' OR e.NAME LIKE "%' . $q_str_s_i . '%" OR REPLACE(REPLACE(e.NAME, " ", ""), "-", "") LIKE "%' . preg_replace("/[- \.]/", "", $q_str) . '%" OR s.PROPERTY_280 LIKE "%' . $q_str_s_i . '%" OR s.PROPERTY_285 LIKE "%' . $q_str_s_i . '%" OR s.PROPERTY_301 LIKE "%' . $q_str_s_i . '%" OR ss.NAME LIKE "%' . $q_str_s_i . '%"  OR ss2.NAME LIKE "%' . $q_str_s_i . '%" ';
                    }
                    $tmp_str[] = '(
                    e.NAME LIKE "%' . $q_str . '%" OR REPLACE(REPLACE(e.NAME, " ", ""), "-", "") LIKE "%' . preg_replace("/[- \.]/", "", $q_str) . '%" OR s.PROPERTY_280 LIKE "%' . $q_str . '%" OR s.PROPERTY_285 LIKE "%' . $q_str . '%" OR s.PROPERTY_301 LIKE "%' . $q_str . '%" OR ss.NAME LIKE "%' . $q_str . '%" OR ss2.NAME LIKE "%' . $q_str . '%"
                    ' . $s_add . '
                    )';
                }
            }
        }
        $tmp_str = implode (' AND ', $tmp_str);
        if (mb_strlen($tmp_str) > 12) {
            $sql = "SELECT
DISTINCT e.ID, e.CODE, CONCAT_WS(' ', s.PROPERTY_301, e.NAME) AS NAME, s.PROPERTY_305 AS AVAILABLE, s.PROPERTY_284 AS PRICE, s.PROPERTY_307 AS PRICE_USD
FROM b_iblock_element AS e

LEFT JOIN b_iblock_element_prop_s40 AS s
ON s.IBLOCK_ELEMENT_ID=e.ID

LEFT JOIN b_iblock_section_element as se
ON e.ID = se.IBLOCK_ELEMENT_ID

LEFT JOIN b_iblock_section as ss
-- ON ss.ID = se.IBLOCK_SECTION_ID
ON ss.ID = s.PROPERTY_306

LEFT JOIN b_iblock_section as ss2
ON ss2.ID = ss.IBLOCK_SECTION_ID

WHERE
    e.ACTIVE = 'Y' AND
    ss.ACTIVE = 'Y' AND
    ss2.ACTIVE = 'Y' AND
    e.IBLOCK_ID = 40
    AND ss.IBLOCK_ID = 40
    AND ss2.IBLOCK_ID = 40
    AND ({$tmp_str})
ORDER BY
--    s.PROPERTY_305,
    CASE WHEN e.NAME LIKE '%taisun%' THEN 0 ELSE 1 END
LIMIT 9000
;";
            $this->arResult["S"] = $sql;
            if (!empty($r_add)) {
                $this->arResult["REPLACEMENTS"] = $r_add;
            }

            $res = $DB->query($sql, __LINE__);
            $ids = Array();
            while ($ob = $res->fetch()) {
                //$ids[] = Array(
                $idss = Array(
                    'ID' => $ob['ID'],
                    'NAME' => $ob['NAME'],
                    'AVAILABLE' => $ob['AVAILABLE'],
                    'URL' => '/product/' . $ob['CODE'] . '/',
                    'PRICE' => $ob['PRICE'],
                    'PRICE_USD' => $ob['PRICE_USD'],
                );
                yield $idss;
            }
        }
        return $ids;
    }

    private function multisearch()
    {
        global $DB;
        $this->arResult["ITEMS"] = self::test();
        if (empty($this->arResult["ITEMS"])) {
            $arLang = \CSearchLanguage::GuessLanguage($this->arParams['SEARCH_QUERY']);
            if(is_array($arLang) && $arLang["from"] != $arLang["to"]) {
                $this->arParams["~ORIGINAL_QUERY"] = $this->arParams['SEARCH_QUERY'];
                $this->arParams["ORIGINAL_QUERY"] = htmlspecialcharsex($this->arParams['SEARCH_QUERY']);
    
                $this->arParams["~SEARCH_QUERY"] = \CSearchLanguage::ConvertKeyboardLayout($this->arParams["~ORIGINAL_QUERY"], $arLang["from"], $arLang["to"]);
                $this->arParams["SEARCH_QUERY"] = htmlspecialcharsex($this->arParams["~SEARCH_QUERY"]);
            } else {
                $this->arParams['~SEARCH_QUERY'] = $this->arParams['SEARCH_QUERY'];
                $this->arParams['SEARCH_QUERY'] = htmlspecialcharsex($this->arParams['SEARCH_QUERY']);
            }
            $this->arResult['TRANSFORM_QUERY'] = $this->arParams['SEARCH_QUERY'];
            $this->arResult["ITEMS"] = self::test();
        }
        if (!empty($this->arResult["ITEMS"])) {
            $this->arResult["TOTAL_COUNT"] = count($this->arResult["ITEMS"]);
            foreach ($this->arResult["ITEMS"] as $product) {
                /*$cache = new CPHPCache();
                $cache_id = 'srchprod' . $product['ID'];
                if ($cache->InitCache(3000 + intval($product['ID']) / 333, $cache_id, 'srchprod')) {
                    $res = $cache->GetVars();
                    $item = $res[$cache_id];
                } else {*/

                    $sql = "SELECT
e.ACTIVE, e.PREVIEW_PICTURE, e.DETAIL_PICTURE, GROUP_CONCAT(se.IBLOCK_SECTION_ID) AS IBLOCK_SECTION_ID
FROM b_iblock_element AS e
LEFT JOIN b_iblock_section_element AS se
ON se.IBLOCK_ELEMENT_ID = e.ID
LEFT JOIN b_uts_iblock_40_section AS u
ON u.VALUE_ID = se.IBLOCK_SECTION_ID
WHERE
    e.IBLOCK_ID = 40
    AND e.ID=" . $product['ID'] . "
    AND u.UF_TYPE IS NULL;";

                    $res = $DB->query($sql);
                    if ($item = $res->fetch()) {
                        if ($item['PREVIEW_PICTURE']) {
                            $item['IMAGE'] = \CFile::ResizeImageGet(
                                $item['PREVIEW_PICTURE'],
                                ['width' => 230, 'height' => 110],
                                BX_RESIZE_IMAGE_PROPORTIONAL,
                                false,
                                false,
                            80)['src'];
                        } else if ($item['DETAIL_PICTURE']) {
                            $item['IMAGE'] = \CFile::ResizeImageGet(
                                $item['DETAIL_PICTURE'],
                                ['width' => 230, 'height' => 110],
                                BX_RESIZE_IMAGE_PROPORTIONAL,
                                false,
                                false,
                            80)['src'];
                        }
                        unset($item['PREVIEW_PICTURE']);
                        unset($item['DETAIL_PICTURE']);
                        $item['PRICE'] = $product['PRICE'];
                        $item['PRICE_USD'] = $product['PRICE_USD'];
                        if (!empty($item['PRICE_USD']) && floatval($item['PRICE_USD']) > 0) {
                            $usdRate = Bitrix\Main\Config\Option::get("politek", "usd_rate");
                            $price = number_format(round(floatval($item['PRICE_USD']) * $usdRate), 0, '.', ' ');
                            $item['PRICE'] = "от {$price} ₽";
                        }
                        //unset($item['SCALED_PRICE_1']);
                        //$item['PRICE_FORMATED'] = number_format(round($item['PRICE']), 0, '.', ' ');

                        $item['URL'] = $product['URL'];
                        $item['NAME'] = $product['NAME'];
                        $item['AVAILABLE'] = $product['AVAILABLE'];
                        $item['IBLOCK_SECTION_ID'] = explode(',', $item['IBLOCK_SECTION_ID']);
                        /*if ($item['PRICE'] == 0) {
                            $item['AVAILABLE'] = '89';
                        }
                        if ($item['ACTIVE'] != 'Y') {
                            $item['AVAILABLE'] = '910';
                            $item['PRICE'] = 0;
                        }*/
                    /*}
                    $cache->StartDataCache();
                    $cache->EndDataCache(array($cache_id => $item));*/
                }

                if ($item) {
                    $this->multi($item);
                }
            }
        } else {
            $this->arResult["ITEMS"] = Array();
        }
        $this->arResult["ITEMS"] = null;
        $this->arResult['QUERY'] = $_GET['q'];
    }

    private function multi($item)
    {
        foreach ($item['IBLOCK_SECTION_ID'] as $item_sid) {
            if (!isset($this->arResult["SECTIONS"][$item_sid])) {
                if (count($this->arResult["SECTIONS"]) >= $this->sectionsGlobalLimit) {
                    return;
                }
                $this->arResult["SECTIONS"][$item_sid] = \CIBlockSection::getList(
                    ['NAME' => 'ASC'],
                    ['IBLOCK_ID' => 40, 'ID' => $item_sid],
                    false,
                    ['ID', 'NAME', 'CODE', 'LEFT_MARGIN', 'RIGHT_MARGIN']
                )->fetch();
                $this->arResult["SECTIONS"][$item_sid]['CNT'] = 0;
                $this->arResult["SECTIONS"][$item_sid]['ITEMS'] = Array();
            }
            $this->arResult["SECTIONS"][$item_sid]['ITEMS'][] = $item;
            $this->arResult["SECTIONS"][$item_sid]['CNT']++;
        }
    }

    private function sectoinsSort()
    {
        foreach ($this->arResult['SECTIONS'] as &$section) {
            $section['SSORT'] = false;
            $section['dbg'] = '';
            foreach (self::$qArr as $q_str) {
                if (mb_strlen($q_str) > 0) {
                    $q_str_s = stemming(preg_replace("/[\.,\/]/", "", $q_str));
                    if (count($q_str_s) == 1 && isset($q_str_s[$q_str])) {
                        if (preg_match("/" . $q_str . "/ui", $section['NAME'])) {
                            $section['dbg'] .= '[1]';
                            $section['SSORT'] = true;
                        }
                    } else {
                        $s_add = '';
                        foreach ($q_str_s as $q_str_s_i => $$q_str_s_v) {
                            //$section['dbg'] .= $q_str_s_i;
                            if (preg_match("/" . $q_str_s_i . "/ui", $section['NAME'])) {
                                $section['dbg'] .= '[2]';
                                $section['SSORT'] = true;
                            } else {
                                $section['dbg'] .= "[2-{$q_str_s_i}]";
                            }
                        }
                        //$section['dbg'] .= $q_str;
                        if (preg_match("/" . $q_str . "/ui", $section['NAME'])) {
                            $section['dbg'] .= '[3]';
                            $section['SSORT'] = true;
                        } else {
                            $section['dbg'] .= "[3-{$q_str}]";
                        }
                    }
                }
            }
            //$section['NAME'] .= ($section['SSORT'] ? '[+]' : '[-]');
            //$section['NAME'] .= ' [' . $section['dbg'] . ']';
        }

        usort($this->arResult['SECTIONS'], function($a, $b) {
            if ($a['SSORT'] && !$b['SSORT']) {
                return -1;
            } else if (!$a['SSORT'] && $b['SSORT']) {
                return 1;
            } else {
                return ($a['CNT'] < $b['CNT']) ? 1 : -1;
            }
        });
        //foreach ($this->arResult['SECTIONS'] as &$items) {
            /*usort($items['ITEMS'], function ($a, $b) use ($statusesWeights) {
                return ($statusesWeights[$a['AVAILABLE']] < $statusesWeights[$b['AVAILABLE']]) ? -1 : 1;
            });*/
            /*usort($items['ITEMS'], function ($a, $b) use ($statusesWeights) {
                if ($statusesWeights[$a['AVAILABLE']] == $statusesWeights[$b['AVAILABLE']]) {
                    return (
                        (stripos($statusesWeights[$a['NAME']], 'taisun') === false ? 0 : 1) - (stripos($statusesWeights[$b['NAME']], 'taisun') === false ? 1 : 0)
                    );
                }
            });*/
        //}
    }

    public function executeComponent()
    {
        /*$cache = new CPHPCache();
        $cache_id = 'srch' . md5(json_encode($this->arParams));
        if ($cache->InitCache(3600, $cache_id, 'srch')) {
            $res = $cache->GetVars();
            $this->arResult = $res[$cache_id];
        } else {*/
            /*$this->arResult["ZIP"] = \CIBlockSection::getList(
                [],
                ['IBLOCK_ID' => 17, 'ID' => '5025'],
                false,
                ['LEFT_MARGIN', 'RIGHT_MARGIN']
            )->fetch();*/
            $this->arResult["SECTIONS"] = Array();
            //$this->arResult["MORE_SECTIONS"] = Array();
            $this->arResult['MANUFACTURERS'] = Array();
            if (!$this->checkParams()) {
                $this->showErrors();
                return;
            }
            if (!empty($this->arParams['SECTION'])) {
                if ($this->arParams['SECTION'] == 1) {
                    $this->goodsLimit = 32;
                } else {
                    $this->goodsLimit = 16;
                }
            }
            if (!empty($this->arParams['PAGE'])) {
                $this->page = (intval($this->arParams['PAGE']) - 1) * $this->goodsLimit;
            }
            $this->arResult['GOODS_LIMIT'] = $this->goodsLimit;
            //if (!empty($this->arParams['MULTI']) && $this->arParams['MULTI'] == 'Y') {
                $this->multisearch();
                $this->sectoinsSort();
                //unset($this->arResult['MORE_SECTIONS']);
            //} else {
            //    $this->search();
            //}
            /*$cache->StartDataCache();
            $cache->EndDataCache(array($cache_id => $this->arResult));
        }*/
        $this->includeComponentTemplate();
    }
}
