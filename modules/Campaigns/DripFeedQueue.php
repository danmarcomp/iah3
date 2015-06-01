<?php
require_once('modules/Campaigns/EmailQueue.php');

class DripFeedQueue extends EmailQueue {

    /**
     * Scheduling emails sending
     *
     * @param array $email
     * @return void
     */
    function scheduling($emails) {
        global $db;

        if ($this->campaign_id != null) {
            foreach ($emails as $message_id => $details) {
                if (! empty($details['inbound_email_id'])) {

                    if ($details['all_prospect_lists'] == 1) {
                        $query = $this->genAllProspectListsQuery();
                    } else {
                        $query = $this->genAssociatedProspectListsQuery($message_id);
                    }

                    $result = $db->query($query);

                    while ( ($row = $db->fetchByAssoc($result)) != null ) {
                        $pl_id = $row['prospect_list_id'];
                        $dynamic = ($row['prospect_list_dynamic'] == '1');
                        $this->addTargetsToQueue($details, $message_id, $pl_id, $dynamic);
                    }
                }
            }

            $this->deleteExempts();
        }
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
        $send_date_time = $this->calcStartDate($marketing['dripfeed_delay'], $marketing['dripfeed_delay_unit']);
        
        $select_completed_query = "SELECT related_id FROM dripfeed_emails WHERE campaign_id = '{$this->campaign_id}' AND marketing_id = '{$message_id}'";

		$select_queries = array();
		$target_queries = array();

        if ($dynamic) {
            $where_clauses = DynamicProspects::getWhereClauses($prospect_list_id);


            if (sizeof($where_clauses) > 0) {
                $counter = 0;

                foreach ($where_clauses as $module => $params) {
					$select_query = '';
		            $select_targ_query = '';
                    $table_name = $params['table_name'];

                    $select_query .= " SELECT $current_date, $current_date, '{$current_user->id}', '{$current_user->id}', '{$this->campaign_id}', '{$message_id}', '{$prospect_list_id}', {$table_name}.id, '{$module}', '{$send_date_time}' ";
                    $select_query .= "FROM {$table_name} ";
                    $select_query .= "WHERE " . $params['clauses'];
                    $select_query .= " AND {$table_name}.id NOT IN (" .$select_completed_query. ")";

                    $select_targ_query .= " SELECT $current_date, '{$this->campaign_id}', '{$message_id}', {$table_name}.id ";
                    $select_targ_query .= "FROM {$table_name} ";
                    $select_targ_query .= "WHERE " . $params['clauses'];
					$select_targ_query .= " AND {$table_name}.id NOT IN (" .$select_completed_query. ")";

					$select_queries[] = $select_query;
					$target_queries[] = $select_targ_query;

                    $counter++;
                }
			}
        }
		
		$select_query2 = "FROM prospect_lists_prospects plp ";
		$select_query2 .= "INNER JOIN prospect_list_campaigns plc ON plc.prospect_list_id = plp.prospect_list_id ";
		$select_query2 .= "WHERE plp.prospect_list_id = '{$prospect_list_id}' ";
		$select_query2 .= "AND plc.deleted = 0 ";
		$select_query2 .= "AND plc.campaign_id = '{$this->campaign_id}' ";
		$select_query2 .= "AND plp.deleted = 0 ";
		$select_query2 .= "AND plp.related_id NOT IN (" .$select_completed_query. ")";

		$select_query = " SELECT $current_date, $current_date, '{$current_user->id}', '{$current_user->id}', plc.campaign_id, '{$message_id}', plp.prospect_list_id, plp.related_id, plp.related_type, '{$send_date_time}' ";
		$select_query .= $select_query2;

		$select_targ_query = " SELECT $current_date, plc.campaign_id, '{$message_id}', plp.related_id ";
		$select_targ_query .= $select_query2;

		$select_queries[] = $select_query;
		$target_queries[] = $select_targ_query;

		$select_query = '(' . join(' ) UNION ( ', $select_queries) . ')';
		$select_targ_query = '(' . join(' ) UNION (', $target_queries) . ')';

		$insert_query = "INSERT INTO emailman (date_entered, date_modified, user_id, modified_user_id, campaign_id, marketing_id, list_id, related_id, related_type, send_date_time";
		$insert_query .= ')';
		$insert_query .= $select_query;
		$db->query($insert_query);

		$insert_completed_query = "INSERT INTO dripfeed_emails (date_modified, campaign_id, marketing_id, related_id";
		$insert_completed_query .= ')';
		$insert_completed_query .= $select_targ_query;
		$db->query($insert_completed_query);
    }


    /**
     * Delete all entries from the queue table
     * that belong to the exempt list
     *
     * @return array
     */
    function deleteExempts() {
        global $db;

		$related_ids = parent::deleteExempts();
		if (!empty($related_ids)) {
			$ids = "'" . join("', '", $related_ids) . "'";
	        $delete_query = "DELETE dripfeed_emails.* FROM dripfeed_emails ";
		    $delete_query .= "WHERE dripfeed_emails.campaign_id = '{$this->campaign_id}'";
			$delete_query .= "AND dripfeed_emails.related_id IN ({$ids})";
	        $db->query($delete_query);
		}

        return $related_ids;
    }

    /**
     * Calculate sending start date
     *
     * @param int $delay
     * @param string $unit: delay unit - days or months
     * @return string
     */
    function calcStartDate($delay, $unit) {
        $timestamp = strtotime("now + " .$delay. " " .$unit);
        $start_date = gmdate("Y-m-d H:i:s", $timestamp);

        return $start_date;
    }
}
?>
