<?php

	require_once 'modules/Campaigns/Campaign.php';

	global $app_strings, $mod_strings;
	$emails = array();
	$mailboxes = array_merge(array('' => ''), Campaign::get_campaign_mailboxes($emails));
	$mailboxes = get_select_options_with_id($mailboxes, '');
	$templates = array('' => '');
	
	require_once 'include/database/ListQuery.php';
	$lq = new ListQuery('EmailTemplate');
	$lq->addAclFilter('list');
	$result = $lq->runQuery();
	foreach($result->getRowIndexes() as $idx) {
		$row = $result->getRowResult($idx);
		$templates[$row->getField('id')] = $row->getField('name');
	}
	$templates = get_select_options_with_id($templates, '');

	$json = getJSONObj();
	$list_id = $json->encode($_REQUEST['list_id']);

	echo <<<HTML
<div style="padding: 10px">
<form method="POST" action="index.php" >
<input type="hidden" name="module" value="Campaigns">
<input type="hidden" name="action" value="QuickCampaign">
<input type="hidden" name="do_campaign" value="1">
<table cellspacing="0" cellpadding="0">
<tr><td class="dataLabel">{$mod_strings['LBL_QC_TEMPLATE']}</td>
<td class="dataField"><select name="template_id">
{$templates}
</select>
</td>
<td class="dataLabel">{$mod_strings['LBL_QC_MAILBOX']}</td>
<td class="dataField">
<select name="mailbox_id">
{$mailboxes}
</select>
</td></tr>
<tr><td class="dataLabel" colspan="4">
<button class="input-button input-outer" onclick="quick_campaign_send(this.form); return false;" type="button"><div class="input-icon icon-accept left"></div><span class="input-label">{$mod_strings['LBL_RUN_QCAMPAIGN_BUTTON_LABEL']}</span></button>
</td></tr></table>
</form>
</div>
<script type="text/javascript">
function quick_campaign_send(form) {
	if (form.template_id.value == '') {
		alert('{$mod_strings['ERR_NO_QCAMPAIGN_TEMPLATE']}');
		return false;
	}
	if (form.mailbox_id.value == '') {
		alert('{$mod_strings['ERR_NO_QCAMPAIGN_MAILBOX']}');
		return false;
	}
	var p = SUGAR.ui.PopupManager.last_opened;
	var form_params = {
		mailbox_id: form.mailbox_id.value,
		template_id: form.template_id.value
	};
	sListView.sendMassUpdate($list_id, 'QuickCampaign', null, form_params);
	p.close();
	return false;
}
</script>
HTML;

