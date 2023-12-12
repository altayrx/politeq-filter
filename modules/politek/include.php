<?php
\CModule::IncludeModule("politek");
global $DBType;

$arClasses = array(
	'CPolitekConfig' => 'classes/general/config.php',
	'CPolitek' => 'classes/general/politek.php',
);

\CModule::AddAutoloadClasses("politek", $arClasses);
