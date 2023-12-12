<?php
Class politek extends CModule
{
	var $MODULE_ID = "politek";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function politek()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");
		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		$this->MODULE_NAME = "Модуль Politek";
		$this->MODULE_DESCRIPTION = "Модуль Politek";
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		// Install events
		RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "politek", "CPolitek", "onBeforeElementUpdateHandler");
		RegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile("Установка модуля politek", $DOCUMENT_ROOT."/local/modules/wg/install/step.php");
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "politek", "CPolitek", "onBeforeElementUpdateHandler");
		UnRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile("Деинсталляция модуля politek", $DOCUMENT_ROOT."/local/modules/wg/install/unstep.php");
		return true;
	}
}
