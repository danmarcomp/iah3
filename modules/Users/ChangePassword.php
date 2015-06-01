<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
require_once('include/database/ListQuery.php');
global $mod_strings, $app_strings, $current_user;

$record = '';
if (empty($_REQUEST['record'])) showError();

$record = $_REQUEST['record'];

if (! is_admin($current_user)) {
    $user = ListQuery::quick_fetch_row('User', $record, array('id'));

    if ($user != null) {
        if ($user['id'] != AppConfig::current_user_id())
            showError();
    } else {
        showError();
    }
}

$html = <<<EOQ
    <form method='POST' id='PasswordForm' name='PasswordForm' onsubmit='return check_password(this);'>
    <input name='module' type='hidden' value='Users' />
    <input name='action' type='hidden' value='SavePassword' />
    <input name='record' type='hidden' value='{$record}' />
    <input name='in_popup' type='hidden' value='1' />
    <table width='100%' cellspacing='0' cellpadding='1' border='0' class="tabForm">
EOQ;
$check_admin = '0';

if (! is_admin($current_user)) {
    $html .= <<<EOQ
        <tr>
        <td width='40%' class='dataLabel' nowrap>{$mod_strings['LBL_OLD_PASSWORD']}:</td>
        <td width='60%' class='dataField'><input name='old_password' type='password' tabindex='1' size='15' maxlength='15' /></td>
        </tr>
EOQ;

    $check_admin = '1';
}

$html .= <<<EOQ
    <tr>
    <td width='40%' class='dataLabel' nowrap>{$mod_strings['LBL_NEW_PASSWORD']}:</td>
    <td width='60%' class='dataField'><input name='new_password' type='password' tabindex='1' size='20' class="input-text" /></td>
    </tr>
    <tr>
    <td width='40%' class='dataLabel' nowrap>{$mod_strings['LBL_CONFIRM_PASSWORD']}:</td>
    <td width='60%' class='dataField'><input name='confirm_new_password' type='password' tabindex='2' size='20' class="input-text" /></td>
    </tr>
    <tr>
    <td width='40%' class='dataLabel'></td>
    <td width='60%' class='dataField'></td>
    </td>
    </tr>
    <tr>
    <td colspan='2' class='dataLabel' style="text-align: center;">
    <input name='is_admin' type='hidden' value='{$check_admin}' />
    <button class='input-button input-outer' onclick='return check_password(this.form);' type='submit' name='button'><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_SAVE_BUTTON_LABEL']}</span></button>
    &nbsp;&nbsp;<button class='input-button input-outer' onclick='SUGAR.popups.hidePopup(popup_dialog);' type='button' name='cancel'><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
    </td>
    </td>
    </tr>
    </table>
    </form>
EOQ;

echo $html;

function showError() {
    sugar_die("Unauthorized access.");
}
?>
