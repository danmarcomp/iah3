<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
global $mod_strings, $current_user;
if (!is_admin($current_user)) sugar_die("Unauthorized access to administration.");

echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_NOTIFICATION_SETTINGS'], true);
$GLOBALS['log']->info("Mass Emailer(EmailMan) NotificationSettings edit");

$name = '';
if (isset($_REQUEST["id"]))
    $name = $_REQUEST["id"];
$module = '';
if (isset($_REQUEST["mod"]))
    $module = urldecode($_REQUEST["mod"]);

$notification = AppConfig::setting("notification.by_name.{$module}.{$name}");

if (is_array($notification) && sizeof($notification) > 0) {
    $subject = '';
    if (isset($notification['subject']))
        $subject = $notification['subject'];

    $body = '';
    if (isset($notification['body']))
        $body = $notification['body'];

	if (strpos($body, "<html>") === false) {
		$body = nl2br($body);
		$is_html = false;
	} else {
		$body = htmlspecialchars($body);
		$is_html = true;
	}

    $edit_view = renderEditView($name, $module, $subject, $body, $is_html);
    echo $edit_view;

} else {
	header("Location: index.php?action=system&module=EmailMan");
}

function renderEditView($name, $module, $subject, $body, $is_html = false) {
    global $app_strings, $mod_strings;

    require_once("include/layout/forms/EditableForm.php") ;
    $spec = array('name' => 'content');
    if ($is_html)
        $spec['fullPage'] = true;
    $editable_form = new EditableForm('notification', 'DetailForm');
    $editor = $editable_form->renderHtmlEditor($spec, $body);

    $body = <<<EOQ
        <form name="DetailForm" method="POST" action="index.php">
        <input type="hidden" name="module" value="EmailMan" />
        <input type="hidden" name="action" />
        <input type="hidden" name="return_module" value="EmailMan" />
        <input type="hidden" name="return_action" value="system" />
        <input type="hidden" name="id" value="{$name}" />
        <input type="hidden" name="mod" value="{$module}" />
        <input type="hidden" name="is_html" value="{$is_html}" />
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-bottom: 2px;">
                <button class="input-button input-outer" onclick="this.form.action.value='SaveNotify';" type="submit"><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_SAVE_BUTTON_LABEL']}</span></button>
                <button class="input-button input-outer" onclick="this.form.action.value='system'; this.form.submit()" type="button"><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
                </td>
            </tr>
        </table>
        <table class="tabForm" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td class="dataLabel">{$mod_strings['LBL_EVENT']}</td>
                <td class="dataField">{$name}</td>
            </tr>
            <tr>
                <td class="dataLabel">{$app_strings['LBL_SUBJECT']}</td>
                <td class="dataField">
                    <textarea tabindex='90' name='subject' cols="80" rows="3">{$subject}</textarea>
                </td>
            </tr>
            <tr>
                <td valign="top" class="dataLabel"><slot>{$mod_strings['LBL_CONTENT']}</slot></td>
                <td colspan="4" class="dataField">
                    <slot>{$editor}</slot>
                </td>
            </tr>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td style="padding-top: 2px;">
                <button class="input-button input-outer" onclick="this.form.action.value='SaveNotify';" type="submit"><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_SAVE_BUTTON_LABEL']}</span></button>
                <button class="input-button input-outer" onclick="this.form.action.value='system'; this.form.submit()" type="button"><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
                </td>
            </tr>
        </table>
        </form>
EOQ;

    $editable_form->exportIncludes();

    return $body;
}
?>