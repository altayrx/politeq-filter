<?php
class CPolitek extends \CPolitekConfig
{
    private static $_instance = null;

    private function __construct () {}
    private function __clone () {}
    private function __wakeup () {}

    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    // Преобразование значений свойств типа файл из id файла в путь к файлу
    public function files(&$object) {
        foreach (self::$files as $file) {
            if (!empty($object[$file])) {
                if (is_array($object[$file])) {
                    foreach ($object[$file] as &$objFile) {
                        $objFile = \CFile::getPath($objFile);
                    }
                } else {
                    $object[$file] = \CFile::getPath($object[$file]);
                }
            }
        }
    }

    public function getModels($models)
    {
        $out = Array();
        $res = \CIBlockElement::getList(
            Array('SORT' => 'ASC'),
            Array(
                'ACTIVE' => 'Y',
                'ID' => $models,
                'IBLOCK_ID' => 12,
            ),
            false,
            false
        );
        
        while ($ob = $res->getNextElement()) {
            $props = $ob->getFields();
            $props['PROPS'] = $ob->getProperties();
            $props['PICTURE'] = $props['PROPS']['SPEC_PREVIEW']['VALUE'];
            self::getInstance()->files($props);
            $sectionOb = \CIBlockElement::getList(
                Array(),
                Array('IBLOCK_ID' => 3, 'ACTIVE' => 'Y', 'PROPERTY_MODELS' => Array($props['ID'])),
                false,
                Array('IBLOCK_ID', 'ID', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL', 'CODE')
            )->fetch();
            $pathAr = Array();
            $path = \CIBlockSection::GetNavChain(3, $sectionOb['IBLOCK_SECTION_ID'], ['DEPTH_LEVEL', 'CODE', 'NAME'], true);
            //$path = \CIBlockSection::GetNavChain(3, $sectionOb['IBLOCK_SECTION_ID'], ['DEPTH_LEVEL', 'CODE'], true);
            $props['FULL_PATH_ARRAY'] = $path;
            foreach ($path as $p) {
                if ($p['DEPTH_LEVEL'] > 1 && !empty($p['CODE'])) {
                    $pathAr[] = $p['CODE'];
                }
            }
            $sectionOb['DETAIL_PAGE_URL'] = '/catalog/' . implode('/', $pathAr) . '/';
            //$sectionOb['DETAIL_PAGE_URL'] = preg_replace(Array('/\#CODE\#/'), Array($sectionOb['CODE']), $sectionOb['DETAIL_PAGE_URL']);
            $props['DETAIL_PAGE_URL'] = $sectionOb['DETAIL_PAGE_URL'];
            if (!empty($props['DETAIL_PICTURE'])) {
                $props['DETAIL_PAGE_URL'] .= $props['CODE'] . '/';
            } else {
                $props['DETAIL_PAGE_URL'] = '';
            }
            $out[] = $props;
        }

        return $out;
    }
}
