<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
if (!defined('inScheduler')) die('Unauthorized access');

require_once('modules/Campaigns/DripFeedQueue.php');

$campaigns = getDripFeedCampaigns();
if (sizeof($campaigns) > 0) {
    foreach ($campaigns as $id => $data) {
        $emails = getCampaignEmails($id);
        $queue = new DripFeedQueue($id);
        $queue->scheduling($emails);
    }
}

function getDripFeedCampaigns() {
    $lq = new ListQuery('Campaign', array('id'));

    $clauses = array(
        "status" => array(
            "value" => 'Active',
            "field" => "status"
        ),
        "type" => array(
            "value" => 'Dripfeed',
            "field" => "campaign_type"
        )
    );

    $lq->addFilterClauses($clauses);
    $result = $lq->fetchAll();

    $campaigns = array();
    if (! $result->failed)
        $campaigns = $result->rows;

    return $campaigns;
}

function getCampaignEmails($id) {
    $fields = array('id', 'name', 'inbound_email_id', 'all_prospect_lists',
        'dripfeed_delay', 'dripfeed_delay_unit');

    $lq = new ListQuery('EmailMarketing', $fields);
    $lq->addSimpleFilter('campaign_id', $id);
    $result = $lq->fetchAll();

    $emails = array();
    if (! $result->failed)
        $emails = $result->rows;

    return $emails;
}
?>
