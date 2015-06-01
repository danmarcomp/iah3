<?php

require_once 'modules/ProspectLists/ProspectList.php';
require_once 'modules/Campaigns/Campaign.php';

$json_supported_actions['get_subscriptions'] = array('login_required' => true, 'admin_only' => true);
$json_supported_actions['set_subscriptions'] = array('login_required' => true, 'admin_only' => true);

function json_get_subscriptions()/*{{{*/
{
	$target_id = array_get_default($_REQUEST, 'target_id');
	$target_type = array_get_default($_REQUEST, 'target_type');
	if (empty($target_id)) {
		return json_bad_request('no_target_id');
	}
	if ($target_type != 'Contacts' && $target_type != 'Leads') {
		return json_bad_request('bad_target_type');
	}

	$subscriptions = array();

	$seed = new Campaign;
	$campaigns = $seed->get_list("", "campaign_type='NewsLetter'");
	if ($target_type == 'Contacts') {
		$rel_name = 'contacts';
	} else {
		$rel_name = 'leads';
	}

	foreach ($campaigns['list'] as $campaign) {
		$subscriptions[$campaign->id] = array(
			'name' => $campaign->name,
			'subscribed' => false,
			'subscription_id' => $campaign->id,
			'default' => true,
		);
		$campaign->load_relationship('prospectlists');
		$lists = $campaign->prospectlists->getBeans(new ProspectList);
		foreach ($lists as $list) {
			$list->load_relationship($rel_name);
			$ids = $list->$rel_name->get();
			$list->$rel_name->cleanup();
			
			if (!in_array($target_id, $ids, true)) {
				continue;
			}
			if ($list->list_type == 'default' && (!isset($subscriptions[$campaign->id]) || isset($subscriptions[$campaign->id]['default']))) {
				$subscriptions[$campaign->id] = array(
					'name' => $campaign->name,
					'subscribed' => true,
					'subscription_id' => $campaign->id,
				);
			} elseif ($list->list_type == 'exempt') {
				$subscriptions[$campaign->id] = array(
					'name' => $campaign->name,
					'subscribed' => false,
					'subscription_id' => $campaign->id,
				);
			}
			$list->cleanup();
		}
		unset($lists);
		$campaign->prospectlists->cleanup();
		$campaign->cleanup();
	}
	$seed->cleanup();
	unset($seed);
	unset($campaigns);

	$subscriptions = array_values($subscriptions);
	
	json_return_value($subscriptions);
}/*}}}*/

function json_set_subscriptions()/*{{{*/
{
	$target_id = array_get_default($_REQUEST, 'target_id');
	$target_type = array_get_default($_REQUEST, 'target_type');
	if (empty($target_id)) {
		return json_bad_request('no_target_id');
	}
	if ($target_type != 'Contacts' && $target_type != 'Leads') {
		return json_bad_request('bad_target_type');
	}
	if ($target_type == 'Contacts') {
		$rel_name = 'contacts';
	} else {
		$rel_name = 'leads';
	}
	foreach ($_REQUEST['subscriptions'] as $cid => $subs) {
		$camp = new Campaign;
		if ($camp->retrieve($cid) && !$camp->deleted) {
			$camp->load_relationship('prospectlists');
			$lists = $camp->prospectlists->getBeans(new ProspectList);
			foreach ($lists as $list) {
				$list->load_relationship($rel_name);
				if ($list->list_type == 'default') {
					if ($subs) {
						$list->$rel_name->add($target_id);
					} else {
						$list->$rel_name->delete($list->id, $target_id);
					}
				}
				if ($list->list_type == 'exempt') {
					if ($subs) {
						$list->$rel_name->delete($list->id, $target_id);
					} else {
						$list->$rel_name->add($target_id);
					}
				}
				$list->$rel_name->cleanup();
				$list->cleanup();
				unset($list);
			}
			unset($lists);
			$camp->prospectlists->cleanup();
			$camp->cleanup();
			unset($camp);
		}
	}
}/*}}}*/



