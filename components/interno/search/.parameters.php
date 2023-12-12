<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("iblock"))
	return;

$arComponentParameters = array(
	"PARAMETERS" => array(
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("IBLOCK_ID"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		),
		"CACHE_TIME" => Array("DEFAULT" => 3600),
		"AJAX_MODE" => array(),
	)
);
