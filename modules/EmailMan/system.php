<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
global $current_user, $mod_strings, $pageInstance;

if (!is_admin($current_user)) sugar_die("Unauthorized access to administration.");
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_NOTIFICATION_SETTINGS'], true);
$GLOBALS['log']->info("Mass Emailer(EmailMan) NotificationSettings view");

$notifications = AppConfig::setting('notification.all');
$body = renderBody($notifications);
echo $body;

$pageInstance->add_js_literal(getJavaScript());

function renderBody($notifications) {
    global $mod_strings;

    $body = <<<EOQ
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td class="dataField" colspan="2">
            <button class="input-button input-outer" onclick="restore(); return false;" type="button" name="button"><div class="input-icon icon-delete left"></div><span class="input-label">{$mod_strings['LBL_RESTORE_DEFAULT']}</span></button>
            </td>
        </tr>
EOQ;

    if (is_array($notifications) && sizeof($notifications) > 0) {
        foreach ($notifications as $module => $ids) {

            $body .= "<tr><td class='dataField' style='width:3%; padding:10px;'>" .$module. ":</td></tr>";
            $ids_num = sizeof($ids);
            $module = urlencode($module);

            for ($i = 0; $i < $ids_num; $i++) {
                $body .= "<tr><td>&nbsp;</td><td class='dataLabel'>".($i+1).".&nbsp;<a href='index.php?module=EmailMan&action=EditNotify&mod={$module}&id={$ids[$i]}'>".$ids[$i]."</a></td></tr>";
            }
        }
    }

    $body .= "</table>";

    return $body;
}

function getJavaScript() {
    global $mod_strings;

    $script = <<<EOQ
        <script type="text/javascript">
        var message = '{$mod_strings['NTC_TEMPLATES_RESTORED']}';

        function restore() {
            call_json_method('EmailMan', 'restore_templates', '', 'restore_result', restore_message);
        }
        function restore_message() {
            var result = json_objects['restore_result'];
            if(result && typeof(result) == 'object') {
                if(result.status == 1) {
                    alert(message);
                }
            }
        }
        </script>
EOQ;

    return $script;
}
?>