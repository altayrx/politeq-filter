<?php
IncludeModuleLangFile(__FILE__);

$aMenu = array(
    "parent_menu" => "global_menu_services",
    "sort"        => 1,
    "url"         => "ps.php",
    "text"        => GetMessage("MENU_MAIN"),
    "title"       => GetMessage("MENU_MAIN_TITLE"),
    //"icon"        => "ps-admin-icon",
    //"page_icon"   => "ps-admin-page-icon",
    "items_id"    => "ps",
    "items"       => []
);
return $aMenu;
