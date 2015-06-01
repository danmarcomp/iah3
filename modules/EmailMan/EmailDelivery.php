<?php
require_once('include/SugarPHPMailer.php');
require_once('modules/EmailMan/EmailMan.php');
require_once('modules/ProspectLists/DynamicProspects.php');

class EmailDelivery {

    /**
     * @var string
     */
    var $campaign_id;

    /**
     * @var bool
     */
    var $test;

    /**
     * If set to true email delivery will continue
     * to run until all email have been delivered
     *
     * @var bool
     */
    var $send_all;

    /**
     * @var int
     */
    var $max_emails_per_run;

    /**
     * @var SugarPHPMailer
     */
    var $mailer;

    /**
     * @param string $campaign_id
     * @param string $mode
     * @param bool|null $send_all
     */
    function __construct($campaign_id, $mode, $send_all) {
        $this->campaign_id = $campaign_id;
        $this->send_all = $send_all;
        
        $test = false;
        if ($mode == 'test')
            $test = true;
        $this->test = $test;

        $max_emails_per_run = AppConfig::setting('massemailer.campaign_emails_per_run');

        if (empty($max_emails_per_run))
            $max_emails_per_run = 500;//default

        $this->max_emails_per_run = $max_emails_per_run;
        $this->initMailer();
    }

    /**
     * Initialize php mailer
     *
     * @return void
     */
    function initMailer() {
        $mail = new SugarPHPMailer();
        $mail->InitForSend();
        $mail->From = "no-reply@example.com";
        $mail->FromName = "no-reply";
        $mail->ContentType ="text/html";

        $this->mailer = $mail;
    }

    function process() {
        global $db;
        $emailman = new EmailMan();
        $emails_query = $this->genEmailsQuery();

        do {
            $no_items_in_queue = true;
            $result = $db->limitQuery($emails_query, 0, $this->max_emails_per_run);

            while($row = $db->fetchByAssoc($result)){
                $no_items_in_queue = false;

                foreach($row as $name => $value){
                    $emailman->$name = $value;
                }

                $this->findSuppressionList($emailman, $row['campaign_id']);

                if(! $emailman->sendEmail($this->mailer, $this->test)) {
                    $this->addToLog("FAILURE:");
                } else {
                    $this->addToLog("SUCCESS:");
                }

                $this->addToLog($emailman->toString());

                if($this->mailer->isError())
                    $this->addToLog($this->mailer->ErrorInfo);
            }

            $this->send_all = $this->send_all ? !$no_items_in_queue : $this->send_all;

        } while ($this->send_all == true);

        $this->finish();
    }

    /**
     * Generate query for selecting scheduled emails
     *
     * @return string
     */
    function genEmailsQuery() {
        $select_query = " SELECT *";
        $select_query .= " FROM emailman";
        $select_query .= " WHERE send_date_time <= ". db_convert("'".gmdate('Y-m-d H:i:s')."'" ,"datetime");
        $select_query .= " AND (in_queue ='0' OR ( in_queue ='1' AND in_queue_date <= " .db_convert("'". gmdate('Y-m-d H:i:s', strtotime("-1 day")) ."'" ,"datetime")."))";
        if ($this->campaign_id)
            $select_query.=" AND campaign_id = '{$this->campaign_id}'";
        $select_query .= " AND NOT deleted";
        $select_query .= " ORDER BY campaign_id, user_id, list_id";

        return $select_query;
    }

    /**
     * For the campaign find the suppression lists
     *
     * @param EmailMan $emailman
     * @param string $campaign_id
     * @return void
     */
    function findSuppressionList(&$emailman, $campaign_id) {
        global $db;

        $plc_query = "SELECT prospect_list_id, prospect_lists.list_type, prospect_lists.domain_name, prospect_lists.dynamic FROM prospect_list_campaigns ";
        $plc_query .= " LEFT JOIN prospect_lists ON prospect_lists.id = prospect_list_campaigns.prospect_list_id";
        $plc_query .= " WHERE ";
        $plc_query .= " campaign_id = '{$campaign_id}' ";
        $plc_query .= " AND prospect_lists.list_type IN ('exempt_address','exempt_domain')";
        $plc_query .= " AND prospect_list_campaigns.deleted = 0";
        $plc_query .= " AND prospect_lists.deleted = 0";

        $emailman->restricted_domains = array();
        $emailman->restricted_addresses = array();

        $result = $db->query($plc_query);

        while ($row = $db->fetchByAssoc($result)){
            if ($row['list_type'] == 'exempt_domain') {
				$domains = explode(',', $row['domain_name']);
				foreach ($domains as $domain) {
					$domain = trim(strtolower($domain));
					if (!empty($domain)) {
		                $emailman->restricted_domains[$domain] = 1;
					}
				}
            } else {
                $dynamic = ($row['dynamic'] == '1');
                $email_query = $this->genSuppressionEmailsQuery($row['prospect_list_id'], $dynamic);

                if ($email_query != '') {
                    $email_query_result = $db->query($email_query);

                    while($email_address = $db->fetchByAssoc($email_query_result)) {
                        $emailman->restricted_addresses[strtolower($email_address['email1'])] = 1;
                    }
                }                    
            }
        }
    }

    /**
     * Find email address of targets in this prospect list
     *
     * @param string $prospect_list_id
     * @param bool $dynamic
     * @return void
     */
	function genSuppressionEmailsQuery($prospect_list_id, $dynamic) {
		$queries = array();
        if ($dynamic) {
            $where_clauses = DynamicProspects::getWhereClauses($prospect_list_id);
            if (sizeof($where_clauses) > 0) {
                $counter = 0;
                foreach ($where_clauses as $module => $params) {
					$table_name = $params['table_name'];
					$queries[] = " SELECT email1 FROM {$table_name} WHERE " . $params['clauses'];
                    $counter ++;
                }
            }
		}

        $queries[] = "SELECT email1 FROM prospects JOIN prospect_lists_prospects ON related_id = prospects.id AND related_type = 'Prospects' AND prospect_list_id = '{$prospect_list_id}' AND prospect_lists_prospects.deleted = 0";
        $queries[] = "SELECT email1 FROM contacts JOIN prospect_lists_prospects ON related_id = contacts.id AND related_type = 'Contacts' AND prospect_list_id = '{$prospect_list_id}' AND prospect_lists_prospects.deleted = 0";
        $queries[] = "SELECT email1 FROM leads JOIN prospect_lists_prospects ON related_id = leads.id AND related_type = 'Leads' AND prospect_list_id = '{$prospect_list_id}' AND prospect_lists_prospects.deleted = 0";
        $queries[] = "SELECT email1 FROM users JOIN prospect_lists_prospects ON related_id = users.id AND related_type = 'Users' AND prospect_list_id = '{$prospect_list_id}' AND prospect_lists_prospects.deleted = 0";
        return join(' UNION ', $queries) ;
    }

    /**
     * Finish delivering process
     *
     * @return void
     */
    function finish() {
        $this->mailer->FinishedSend();
    }

    /**
     * Add message to global log
     *
     * @param string $text - log message
     * @return void
     */
    function addToLog($text){
        if(! empty($_REQUEST['verbose']))
            echo $text . '<br>';
        $GLOBALS['log']->info($text);
    }

    /**
     * Redirect after delivering
     *
     * @param bool $inSheduler
     * @param array $input - user input ($_REQUEST)
     * @return void
     */
    function redirectAfter($inSheduler, $input) {
        if (! $inSheduler && isset($input['return_module']) && isset($input['return_action'])) {
            $return_record = '';

            if (isset($input['return_record'])) {
                $return_record = $input['return_record'];
            } elseif (isset($input['return_id'])) {
                $return_record = $input['return_id'];
            }

            $return_url = "index.php?module={$input['return_module']}&action={$input['return_action']}&record=" . $return_record;
            echo '<script>document.location.href="'.addcslashes($return_url, '"').'";</script>';
        } else {
            /* this will be triggered when manually sending off Email campaigns from the
             * Mass Email Queue Manager.
             */
            if(isset($input['manual']))
                header("Location: index.php?module=EmailMan&action=index");
        }
    }
}
?>
