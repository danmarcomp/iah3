<?php
require_once('include/database/ListQuery.php');
require_once('modules/ProspectLists/DynamicProspects.php');

class EmailQueue {

    /**
     * @var string
     */
    var $campaign_id;

    /**
     * @var bool
     */
    var $test;

    /**
     * @param string $campaign_id
     * @param bool|null $mode
     */
    function __construct($campaign_id, $mode = null) {
        $this->campaign_id = $campaign_id;

        $test = false;
        if ($mode == 'test')
            $test = true;
        $this->test = $test;
    }

    /**
     * Scheduling emails sending
     *
     * @param array $uids
     * @return void
     */
    function scheduling($uids) {
        global $db;

        if ($this->campaign_id != null) {
            $status = $this->getCampaignStatus();

            if ($status != 'Dripfeed') {
                foreach ($uids as $message_id) {
                    $marketing = ListQuery::quick_fetch_row('EmailMarketing', $message_id);

                    //make sure that the marketing message has a mailbox.
                    if (empty($marketing['inbound_email_id']))
                        $this->showMailboxError($marketing);

                    if ($marketing['all_prospect_lists'] == 1) {
                        $query = $this->genAllProspectListsQuery();
                    } else {
                        $query = $this->genAssociatedProspectListsQuery($message_id);
                    }

                    $result = $db->query($query);

                    while ( ($row = $db->fetchByAssoc($result)) != null ) {
                        $pl_id = $row['prospect_list_id'];
                        $dynamic = ($row['prospect_list_dynamic'] == '1');

                        $this->deleteAllMessages($message_id, $pl_id);
                        $this->addTargetsToQueue($marketing, $message_id, $pl_id, $dynamic);
                    }
                }
    
                $this->deleteExempts();
            }

        }
    }

    /**
     * Get data for returning by queue script
     *
     * @param array $input - user input ($_REQUEST)
     * @param bool $from_wizard
     * @return array
     */
    function getReturnData($input, $from_wizard) {
        $return_module = isset($input['return_module']) ? $input['return_module'] : 'Campaigns';
        $return_action = isset($input['return_action']) ? $input['return_action'] : 'DetailView';
        $return_id = $this->campaign_id;

        if ($this->test) {
            //navigate to EmailManDelivery
            $data = array('module' => 'EmailMan', 'action' => 'EmailManDelivery', 'campaign_id' => $return_id,
                'return_module' => $return_module, 'return_action' => $return_action, 'return_record' => $return_id, 'mode' => 'test');

            if ($from_wizard) $data['from_wiz'] = "true";
        } else {
            $layout = '';
            if ($return_action != 'WizardHome')
                $layout = 'Track';
            //navigate back to campaign detail view...
            $data = array('module' => $return_module, 'action' => $return_action, 'record' => $return_id, 'layout' => $layout);
            if ($from_wizard) $data['from'] = 'send';
        }

        return $data;
    }

    /**
     * Get current campaign status
     *
     * @return string
     */
    function getCampaignStatus() {
        $status = '';
        if ($this->campaign_id != null) {
            $campaign = ListQuery::quick_fetch_row('Campaign', $this->campaign_id, array('status'));
            if (! empty($campaign['status']))
                $status = $campaign['status'];
        }

        return $status;
    }
    
    /**
     * Generate query for selecting all campaigns prospect lists
     *
     * @return string
     */
    function genAllProspectListsQuery() {
        $query = "SELECT prospect_lists.id prospect_list_id, prospect_lists.dynamic prospect_list_dynamic FROM prospect_lists ";
        $query .= " INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = prospect_lists.id";
        $query .= " WHERE plc.campaign_id = '{$this->campaign_id}'";
        $query .= " AND prospect_lists.deleted = 0";
        $query .= " AND plc.deleted = 0";

        if ($this->test) {
            $query .= " AND prospect_lists.list_type = 'test'";
        } else {
            $query .= " AND prospect_lists.list_type != 'test' AND prospect_lists.list_type NOT LIKE 'exempt%'";
        }

        return $query;
    }

    /**
     * Generate query for selecting all prospect lists associated
     * with this email marketing message
     *
     * @param string $message_id
     * @return string
     */
    function genAssociatedProspectListsQuery($message_id) {
        $query = "SELECT email_marketing_prospect_lists.*, prospect_lists.dynamic prospect_list_dynamic FROM email_marketing_prospect_lists ";
        $query .= " INNER JOIN prospect_lists on prospect_lists.id = email_marketing_prospect_lists.prospect_list_id";
        $query .= " WHERE prospect_lists.deleted = 0 AND email_marketing_id = '$message_id' AND email_marketing_prospect_lists.deleted = 0";

        if ($this->test) {
            $query .= " AND prospect_lists.list_type = 'test'";
        } else {
            $query .= " AND prospect_lists.list_type != 'test' AND prospect_lists.list_type NOT LIKE 'exempt%'";
        }

        return $query; 
    }

    /**
     * Delete all messages for the current campaign
     * and current email marketing message
     *
     * @param string $message_id
     * @param string $prospect_list_id
     * @return void
     */
    function deleteAllMessages($message_id, $prospect_list_id) {
        global $db;

        $delete_query = "DELETE FROM emailman WHERE campaign_id='{$this->campaign_id}' AND marketing_id='{$message_id}' AND list_id='{$prospect_list_id}'";
        $db->query($delete_query);        
    }

    /**
     * Add targets from prospect list to email queue
     *
     * @param array $marketing
     * @param string $message_id
     * @param string $prospect_list_id
     * @param bool $dynamic
     * @return void
     */
    function addTargetsToQueue($marketing, $message_id, $prospect_list_id, $dynamic) {
        global $db, $current_user, $timedate;
        $current_date = "'".gmdate("Y-m-d H:i:s")."'";

        if ($this->test) {
            $send_date_time = "'".gmdate("Y-m-d H:i:s", mktime(0, 0, 0, 8, 15, 1996))."'";
		} else {
			if (empty($marketing['date_start']))
				$send_date_time = $current_date;
			else
	            $send_date_time = "'" . $marketing['date_start'] . "'";
        }

		$queries = array();

        if ($dynamic) {
            $where_clauses = DynamicProspects::getWhereClauses($prospect_list_id);

            if (sizeof($where_clauses) > 0) {
                $counter = 0;
                foreach ($where_clauses as $module => $params) {
					$select_query = '';
                    $table_name = $params['table_name'];
                    $select_query .= " SELECT $current_date, $current_date, '{$current_user->id}', '{$current_user->id}', '{$this->campaign_id}', '{$message_id}', '{$prospect_list_id}', {$table_name}.id, '{$module}', {$send_date_time} ";
                    $select_query .= "FROM {$table_name} ";
                    if(! empty($params['joins']))
                    	$select_query .= $params['joins'] . ' ';
					$select_query .= "WHERE " . $params['clauses'];
					$queries[] = $select_query;
					$counter++;
                }
            }

		}

		$select_query = " SELECT $current_date, $current_date, '{$current_user->id}', '{$current_user->id}', plc.campaign_id, '{$message_id}', plp.prospect_list_id, plp.related_id, plp.related_type, {$send_date_time} ";
		$select_query .= "FROM prospect_lists_prospects plp ";
		$select_query .= "INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = plp.prospect_list_id ";
		$select_query .= "WHERE plp.prospect_list_id = '{$prospect_list_id}' ";
		$select_query .= "AND plp.deleted = 0 ";
		$select_query .= "AND plc.deleted = 0 ";
		$select_query .= "AND plc.campaign_id = '{$this->campaign_id}'";

		$queries[] = $select_query;

		$select_query = '(' . join(') UNION ( ', $queries) . ')'; 

		$insert_query = "INSERT INTO emailman (date_entered, date_modified, user_id, modified_user_id, campaign_id, marketing_id, list_id, related_id, related_type, send_date_time";
		$insert_query .= ')';
		$insert_query .= $select_query;
		$db->query($insert_query);
    }

    /**
     * Delete all entries from the queue table
     * that belong to the exempt list
     *
     * @return array
     */
    function deleteExempts() {
        global $db;

        if (! $this->test) {
            $select_query = "SELECT prospect_lists.id, prospect_lists.dynamic FROM prospect_lists ";
            $select_query .= " INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = prospect_lists.id";
            $select_query .= " WHERE plc.campaign_id = '{$this->campaign_id}'";
            $select_query .= " AND prospect_lists.list_type = 'exempt'";
            $select_query .= " AND prospect_lists.deleted = 0";
			$select_query .= " AND plc.deleted = 0";

            $result = $db->query($select_query);
            $related_ids = array();

			$queries = array();
            while ( ($row = $db->fetchByAssoc($result)) != null ) {
                if ($row['dynamic'] == '1') {
                    $where_clauses = DynamicProspects::getWhereClauses($row['id']);

                    if (sizeof($where_clauses) > 0) {
                        $counter = 0;
                        foreach ($where_clauses as $module => $params) {
							$select_pr_query = '';
                            $table_name = $params['table_name'];
                            $select_pr_query .= " SELECT id AS related_id ";
                            $select_pr_query .= "FROM {$table_name} ";
							if(! empty($params['joins']))
								$select_pr_query .= $params['joins'] . ' ';
							$select_pr_query .= "WHERE " . $params['clauses'];
							$queries[] = $select_pr_query;
                            $counter ++;
                        }
					}
				}
                $select_pr_query = "SELECT plp.related_id FROM prospect_lists_prospects plp";
				$select_pr_query .= " WHERE plp.prospect_list_id = '{$row['id']}'";
                $select_pr_query .= " AND plp.deleted = 0";
				$queries[] = $select_pr_query;
				$select_pr_query = '(' . join(' ) UNION ( ', $queries) . ' )';

				$pr_result = $db->query($select_pr_query);

				while ( ($pr_row = $db->fetchByAssoc($pr_result)) != null ) {
					$related_ids[] = $pr_row['related_id'];
				}
            }

			if (!empty($related_ids)) {	
			    $ids = "'" . join("', '", $related_ids) . "'";
	            $delete_query = "DELETE emailman.* FROM emailman ";
		        $delete_query .= "WHERE emailman.campaign_id = '{$this->campaign_id}'";
			    $delete_query .= "AND emailman.related_id IN ({$ids})";
				$db->query($delete_query);
			}

            return $related_ids;
        }
    }

    /**
     * @param array $marketing
     * @return void
     */
    function showMailboxError($marketing) {
        global $mod_strings;

        echo "<p>";
        echo "<h4>{$mod_strings['ERR_NO_MAILBOX']}</h4>";
        echo "<br /><a href='index.php?module=EmailMarketing&action=EditView&record=".$marketing['id']."'>".$marketing['name']."</a>";
        echo "</p>";
        sugar_die('');
    }
}
?>
