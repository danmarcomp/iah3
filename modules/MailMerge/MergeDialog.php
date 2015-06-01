<?php
$run_merge_lbl = translate('LBL_RUN_MERGE');
$note_lbl = translate('LBL_MAIL_MERGE_NOTE');

$module = 'Contacts';
if (isset($_REQUEST['target_module']))
    $module = $_REQUEST['target_module'];

$list_id = array_get_default($_POST, 'list_id');

$html = <<<EOQ
    <div id='display_rtf'>
    <table class="tabForm" width="100%">
    <tr><td class="dataField">
    <p>{$note_lbl}</p>
    <form name="MergeForm" id="MergeForm" method="POST" action="async.php" encoding="multipart/form-data" enctype="multipart/form-data" onsubmit="return check_merge_form(this);">
    <input id='hidden' name='module' value='MailMerge' type='hidden' />
    <input id='hidden' name='action' value='Upload' type='hidden' />    
    <input id='hidden' name='merge_module' value='{$module}' type='hidden' />
    <input id='hidden' name='list_id' value='{$list_id}' type='hidden' />
    <input id='merge_file' name='merge_file' class="input-file" tabindex='0' size='40' value='' type='file' />
    <button onclick="var p = SUGAR.ui.PopupManager.last_opened; p.fetchFormContent(this.form);" type='button' name='RunMerge' class='input-button input-outer'><div class="input-icon icon-accept left"></div><span class="input-label">{$run_merge_lbl}</span></button>
    </form>
    </td></tr></table>
    </div>
EOQ;


global $pageInstance;
$pageInstance->add_js_include('modules/MailMerge/merge.js', null, LOAD_PRIORITY_FOOT);

echo $html;
?>
