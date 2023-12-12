<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php";
IncludeModuleLangFile(__FILE__);
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php";

/*CJSCore::RegisterExt('ps', array(
	'css' => '/local/admin/ps.css',
));
CJSCore::Init(array("ps"));*/

?><form method="post" action="" enctype="multipart/form-data">
<?
$aTabs = array(
	array("DIV" => "config", "TAB" => GetMessage("TAB_COMMON"), "ICON" => "ps", "TITLE" => GetMessage("TITLE_COMMON"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();

$tabControl->BeginNextTab();
?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=GetMessage("TITLE_CURRENCY_RATE")?>:</td>
	<td width="60%"><input type='text' name='extra_name' value='<?//=$_REQUEST['extra_name']?>' /></td>
</tr>
<?
$tabControl->End();
/*$message = new CAdminMessage([
	'MESSAGE' => '* Внимание!',
	'TYPE' => 'OK',
	'DETAILS' => GetMessage('ALERT_COMMON'),
	'HTML' => true
]);
echo $message->Show();*/
?></form>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
