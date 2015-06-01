<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */


require_once('include/layout/forms/FormTableSection.php');
require_once('modules/Campaigns/Charts.php');

class CampaignWidget extends FormTableSection {

    /**
     * Used to build chart
     *
     * @var string
     */
    var $latest_marketing_id = 'all';

    function init($params, $model=null) {
        parent::init($params, $model);
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        if ($this->id == 'roi_chart') {
            return $this->renderRoiChart($row_result);
        } elseif ($this->id == 'track_chart') {
            return $this->renderTrackChart($gen, $row_result);
        } elseif ($this->id == 'diagnostic') {
            return $this->renderDiagnosticView();
        } elseif ($this->id == 'subscriptions') {
            return $this->renderSubscriptionsView($gen, $row_result);
        } else {
            return '';
        }
	}
	
	function getRequiredFields() {
        $fields = array();

		return $fields;
	}
	
    /**
     * Render Track chart
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderTrackChart(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_list_strings, $current_user;

        $record = $row_result->getField('id');
        $type = $row_result->getField('campaign_type');

        $filter_label = '&nbsp;';
        $filter_select = '&nbsp;';

        if ($type == 'NewsLetter') {
            $filter_label = $mod_strings['LBL_FILTER_CHART_BY'] . ':';
            $filter_select = $this->renderFilterSelect($gen, $_REQUEST, $record);
        }

        $chart = $this->getChart($record, 'track');

        $body = <<<EOQ
            <br/>
            <table border='0' width='100%'>
                <tr>
                    <td width="10%">{$filter_label}</td>
                    <td width="70%">{$filter_select}</td>
                    <td width="20%">&nbsp;</td>
                </tr>
                <tr>
                    <td width="10%" >&nbsp;</td>
                    <td><div style="text-align: center">{$chart}</div></td>
                    <td width="20%">&nbsp;</td>
                </tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Render ROI chart
     *
     * @param RowResult $row_result
     * @return string
     */
    function renderRoiChart(RowResult &$row_result) {
        $record = $row_result->getField('id');
        $chart = $this->getChart($record, 'roi');

        $body = <<<EOQ
            <br/>
            <table border='0' width='100%'>
                <tr>
                    <td width="10%" >&nbsp;</td>
                    <td><div style="text-align: center">{$chart}</div></td>
                    <td width="20%">&nbsp;</td>
                </tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Get chart by type
     *
     * @param string $record
     * @param string $type: track or roi
     * @return string
     */
    function getChart($record, $type = 'track') {
        global $app_list_strings, $current_user;

        $seps = array("-", "/");
        $dates = array(date('Y-m-d'), date('Y-m-d'));
        $dateFileNameSafe = str_replace($seps, "_", $dates);
        $chart = new charts();

        if ($type == 'track') {
            $cache_file_name = $current_user->getUserPrivGuid() ."_campaign_response_by_activity_type_". $dateFileNameSafe[0] ."_". $dateFileNameSafe[1]. ".xml";
            $marketing_id = $this->latest_marketing_id;

            if ($marketing_id == 'all')
                $marketing_id = '';

            $chart_image = $chart->campaign_response_by_activity_type($app_list_strings['campainglog_activity_type_dom'], $app_list_strings['campainglog_target_type_dom'], $record, AppConfig::setting('site.paths.xml_dir').$cache_file_name, true, $marketing_id);

        } elseif ($type == 'roi') {
            $cache_file_name_roi = $current_user->getUserPrivGuid() ."_campaign_response_by_roi_". $dateFileNameSafe[0] ."_". $dateFileNameSafe[1] . ".xml";
            $chart_image = $chart->campaign_response_roi($app_list_strings['roi_type_dom'], $app_list_strings['roi_type_dom'], $record, AppConfig::setting('site.paths.xml_dir') . $cache_file_name_roi, true, true);

        } else {
            $chart_image = '&nbsp;';
        }

        return $chart_image;
    }

    /**
     * Render Diagnostic panels
     *
     * @return string
     */
    function renderDiagnosticView() {
        $email_components = $this->getEmailComponents();
        $schedule_components = $this->getScheduleComponents();

        $body = <<<EOQ
            <div id="diagnose" class="contentdiv">
            <table class="h3Row" border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td nowrap="nowrap"><h3>{$email_components['image']} Email Components</h3></td></tr></table>
            <div id="email" >
            {$email_components['conf_msg']}
            {$email_components['mbox_table']}
            </div>
            <p>{$email_components['setup_link']}</p>
            <p>&nbsp;</p>
            <table class="h3Row" border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td nowrap="nowrap"><h3>{$schedule_components['image']}  Scheduler Components</h3></td></tr></table>
            <div id="schedule">
            {$schedule_components['emails_message']}
            </div>
            <div id='submit' style="padding-top: 5px;"><input name="Re-Check" onclick="return SUGAR.ui.sendForm(this);" class='button' value="Re-Check" type="submit" /></div>
            </div>
EOQ;

        return $body;
    }

    /**
     * Get Email Components diagnostic block
     *
     * @return array: ['image', 'setup_link', 'mbox_table', 'conf_msg']
     */
    function getEmailComponents() {
        global $mod_strings, $current_user;

        $email_health = 0;

        $lq = new ListQuery('EmailPOP3');

        $clauses = array(
            "type" => array(
                "value" => 'bounce',
                "field" => "mailbox_type",
            ),
            "active" => array(
                "value" => 1,
                "field" => "active"
            ),
        );

        $lq->addFilterClauses($clauses);
        $result = $lq->fetchAll();

        $mbox_table = "<table border ='0' width='100%'  class='tabDetailView' cellpadding='0' cellspacing='0'>";

        if (! $result->failed) {
            $boxes = $result->rows;

            if (sizeof($boxes) > 0) {

                $mbox_table .= "<tr><td colspan='5' style='text-align: left;'><b>" .sizeof($boxes) ." ". $mod_strings['LBL_MAILBOX_CHECK1_GOOD']." </b>.</td></tr>";
                $mbox_table .= "<tr><td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_MAILBOX_NAME']."</b></td>" .
                    " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_LOGIN']."</b></td>" .
                    " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_MAILBOX']."</b></td>" .
                    " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_SERVER_URL']."</b></td>";

                foreach ($boxes as $id => $details) {
                    $mbox_table .= "<tr><td class=\"tabDetailViewDL2\">" .$details['name']. "</td>";
                    $mbox_table .= "<td class='tabDetailViewDL2'>" .$details['username']. "</td>";
                    $mbox_table .= "<td class='tabDetailViewDL2'>" .$details['imap_folder']. "</td>";
                    $mbox_table .= "<td class='tabDetailViewDL2'>" .$details['host']. "</td>";
                }
            } else {
                $mbox_table .=  "<tr><td colspan='5' class='tabDetailViewDF'><b class='error'>". $mod_strings['LBL_MAILBOX_CHECK1_BAD']."</b></td></tr>";
                $email_health ++;
            }
        }

        $mbox_table .= '</table>';

        //email settings configured
        $conf_msg = "<table border='0' width='100%' class='tabDetailView' cellpadding='0' cellspacing='0'>";

        if ( strstr(AppConfig::setting('notify.from_address'), 'example.com') ){

            //if from address is the default, then set "bad" message and increment health counter
            $conf_msg .= "<tr><td colspan = '5' class='tabDetailViewDF'><b class='error'> ".$mod_strings['LBL_MAILBOX_CHECK2_BAD']." </b></td></td>";
            $email_health ++;

        }else{

            $conf_msg .= "<tr><td colspan = '5' class='tabDetailViewDF'><b> ".$mod_strings['LBL_MAILBOX_CHECK2_GOOD']."</b></td></tr>";
            $conf_msg .= "<tr><td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_WIZ_FROM_NAME']."</b></td>" .
                " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_WIZ_FROM_ADDRESS']."</b></td>" .
                " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_MAIL_SENDTYPE']."</b></td>";

            if(AppConfig::setting('email.send_type') == 'SMTP'){

                $conf_msg .= " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_MAIL_SMTPSERVER']."</b></td>" .
                " <td width='20%' class='tabDetailViewDF'><b>".$mod_strings['LBL_MAIL_SMTPUSER']."</b></td></tr>";

            } else {
                $conf_msg .= "</tr>";
            }

            $conf_msg .= "<tr class='tabDetailViewDL2'><td>" .AppConfig::setting('notify.from_name'). "</td>";
            $conf_msg .= "<td>" .AppConfig::setting('notify.from_address'). "</td>";
            $conf_msg .= "<td>" .AppConfig::setting('email.send_type'). "</td>";

            if(AppConfig::setting('email.send_type') == 'SMTP'){
                $conf_msg .= "<td>" .AppConfig::setting('email.smtp_server'). "</td>";
                $conf_msg .= "<td>" .AppConfig::setting('email.smtp_user'). "</td></tr>";
            } else {
                $conf_msg .= "</tr>";
            }

        }

        $conf_msg .= '</table>';

        $email_setup_wiz_link = '';

        if ($email_health > 0) {

            if (is_admin($current_user)) {
                $email_setup_wiz_link = "<a href='index.php?module=Campaigns&action=WizardEmailSetup'>".$mod_strings['LBL_EMAIL_SETUP_WIZ']."</a>";
            } else {
                $email_setup_wiz_link = $mod_strings['LBL_NON_ADMIN_ERROR_MSG'];
            }

        }

        $email_image = $this->defineDiagnosticImage($email_health, 2);

        return array('image' => $email_image, 'setup_link' => $email_setup_wiz_link, 'mbox_table' => $mbox_table, 'conf_msg' => $conf_msg);
    }

    /**
     * Get Schedule Components diagnostic block
     *
     * @return array: ['image', 'emails_message']
     */
    function getScheduleComponents() {
        require_once('modules/Scheduler/Schedule.php');
        global $mod_strings, $current_user;

        $lq = new ListQuery('Schedule', array('type'));
        $lq->addSimpleFilter('enabled', 1);
        $result = $lq->fetchAll();

        $sched_health = 0;
        $check_sched1 = 'emailmandelivery';
        $check_sched2 = 'processbounces';
        $sched_mes = '';
        $sched_mes_body = '';

        if (! $result->failed) {
            foreach ($result->rows as $id => $details) {
                $sched_mes = 'use';
                $sched_mes_body .= "<tr><td class='tabDetailViewDL2' style='text-align: left;'>" .Schedule::translate_type($details['type']). "</td>";
                $sched_mes_body .= "<td class='tabDetailViewDL2' style='text-align: left;'>{$mod_strings['LBL_ACTIVE']}</td></tr>";

                if($details['type'] == $check_sched1) {
                    $check_sched1 = "found";
                } else {
                    $check_sched2 = "found";
                }
            }
        }

        //determine which table header to use, based on whether or not schedulers were found
        if($sched_mes == 'use') {

            $sched_mes = "<h5>".$mod_strings['LBL_SCHEDULER_CHECK_GOOD']."</h5><br><table class='tabDetailView2' border='0' width='100%' cellpadding='0' cellspacing='1'>";
            $sched_mes .= "<tr><td width='40%' class='tabDetailViewDF2'><b>".$mod_strings['LBL_SCHEDULER_NAME']."</b></td>" .
                " <td width='60%' class='tabDetailViewDF2'><b>".$mod_strings['LBL_SCHEDULER_STATUS']."</b></td></tr>";

        } else {

            $sched_mes = "<table class='tabDetailView2' border='0' width='100%' cellpadding='0' cellspacing='1'>";
            $sched_mes .= "<tr><td colspan ='3' class='tabDetailViewDL2'><font color='red'><b> ".$mod_strings['LBL_SCHEDULER_CHECK_BAD']."</b></font></td></tr>";
        }

        //determine if error messages need to be displayed for schedulers
        if($check_sched2 != 'found') {
            $sched_health ++;
            $sched_mes_body .= "<tr><td colspan ='3' class='tabDetailViewDL2'><font color='red'> ".$mod_strings['LBL_SCHEDULER_CHECK1_BAD']."</font></td></tr>";
        }

        if($check_sched1 != 'found') {
            $sched_health ++;
            $sched_mes_body .= "<tr><td colspan ='3' class='tabDetailViewDL2'><font color='red'>".$mod_strings['LBL_SCHEDULER_CHECK2_BAD']."</font></td></tr>";
        }

        $admin_sched_link = '';

        if ($sched_health > 0){

            if (is_admin($current_user)) {
                $admin_sched_link = "<a href='index.php?module=Scheduler&action=index'>".$mod_strings['LBL_SCHEDULER_LINK']."</a>";
            } else {
             $admin_sched_link = $mod_strings['LBL_NON_ADMIN_ERROR_MSG'];
            }

        }

        $final_sched_msg = $sched_mes . $sched_mes_body . '</table>' . $admin_sched_link;
        $schedule_image = $this->defineDiagnosticImage($sched_health, 2);

        return array('image' => $schedule_image, 'emails_message' => $final_sched_msg);
    }

    /**
     * Render Manage Subscriptions page
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderSubscriptionsView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        require_bean($this->model->name);
        global $mos_strings, $app_strings;
        $input = $_REQUEST;
        $classname = "SUGAR_GRID";
        $record = $row_result->getField('id');
        $focus = new $this->model->name();

        //if subsaction has been set, then process subscriptions
        if(isset($input['subs_action'])) {
			$this->manageSubscriptions($focus, $record, $input);

            if(isset($input['return_module']) && isset($input['return_record']) && isset($input['return_action'])) {
                header("Location: async.php?module=".$input['return_module']."&action=".$input['return_action']."&record=".$input['return_record']);
            }
        }

		$inputs = array();
		$subscriptions_list = $this->constructDDSubscriptionList($gen, $focus, $record, $classname, $inputs);
		extract($inputs);

        $body = <<<EOQ
			<input type="hidden" name="subs_action" value="process" />
			<input type="hidden" name="subscribe_lists" id="ddgrid0_values" value="{$subs_value}" />
			<input type="hidden" name="remove_lists" id="ddgrid1_values" value="{$unsubs_value}" />									        
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tr><td>
                    <table  border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <th align="left" class="dataLabel" colspan="4"><h4 class="">{$app_strings['LBL_MANAGE_SUBSCRIPTIONS_FOR']}{$row_result->getField('name')}</h4></th>
                    </tr>
                    <tr><td>&nbsp;</td></tr>
                    <td>
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width='350'>
                    <tbody><tr><td align="center">{$subscriptions_list}</td></tr></tbody>
                    </table>
                    </td>
                    </tr>
                    </table>
                </td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td>
                    <button type='submit' class='input-button input-outer' onclick='return SUGAR.ui.sendForm(document.forms.DetailForm, {"record_perform":"save"}, null);'><div class="input-icon icon-accept left"></div><span class="input-label">{$app_strings['LBL_SAVE_BUTTON_LABEL']}</span></button>
                    <button title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button input-outer" onclick="return SUGAR.ui.cancelEdit(document.forms.DetailForm);" type="button" name="button"><div class="input-icon icon-cancel left"></div><span class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span></button>
                </td></tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * This method constructs Drag and Drop multiselect box of subscriptions
     * for display in manage subscription form
     *
     * @param HtmlFormGenerator $gen
     * @param SugarBean $focus
     * @param string $record
     * @param string $classname
     * @return string
     */
    function constructDDSubscriptionList(HtmlFormGenerator &$gen, $focus, $record, $classname, &$input_values) {
        $str = '';

        if ($focus->retrieve($record)) {
            require_once('modules/Campaigns/utils.php');
            require_once("include/templates/TemplateDragDropChooser.php");
            global $mod_strings;

            // Lets start by creating the subscription and unsubscription arrays
            $subscription_arrays = CampaignUtils::get_subscription_lists($focus);
            $unsubs_arr = $subscription_arrays['unsubscribed'];
            $subs_arr =  $subscription_arrays['subscribed'];
            $exempt =  $subscription_arrays['exempt'];
            $unsubs_arr_rev = array();
            $subs_arr_rev = array();
			$exempt_rev = array();

			$subs_value="";
			$unsubs_value="";
			$exempt_value="";


            foreach ($unsubs_arr as $key=>$val) {
				$unsubs_arr_rev[] = array($key, $val);
				if (!empty($unsubs_value))
					$unsubs_value .= ", ";
				$unsubs_value .= $key;
			}
            foreach ($subs_arr as $key=>$val) {
				$subs_arr_rev[] = array($key, $val);
				if (!empty($subs_value))
					$subs_value .= ", ";
				$subs_value .= $key;
			}
            foreach ($exempt as $key=>$val) {
				$exempt_rev[] = array($key, '<a href="index.php?module=Campaigns&action=DetailView&record='.$key.'">'.htmlentities($val, ENT_QUOTES, 'UTF-8').'</a>');
				if (!empty($exempt_value))
					$exempt_value .= ", ";
				$exempt_value .= $key;
			}


			$input_values = compact('subs_value', 'unsubs_value', 'exempt_value');

            //now call function that creates javascript for invoking DDChooser object
            $dd_chooser = new TemplateDragDropChooser();
            $dd_chooser->args['classname']  = $classname;
            $dd_chooser->args['columns'] = array(
                array(
                    'title' => $mod_strings['LBL_ALREADY_SUBSCRIBED_HEADER'],
                    'data' => $subs_arr_rev,
                    'name' => 'ddgrid0',
                ),
                array(
                    'title' => $mod_strings['LBL_UNSUBSCRIBED_HEADER'],
                    'data' => $unsubs_arr_rev,
                    'name' => 'ddgrid1',
                ),
                array(
                    'title' => $mod_strings['LBL_OPTED_OUT_HEADER'],
                    'data' => $exempt_rev,
                    'name' => 'optout',
                    'editable' => false,
                    'no_escape_labels' => true,
                ),
            );
            $dd_chooser->args['title']        =  ' ';
            $str = $dd_chooser->displayScriptTags();
            $str .= $dd_chooser->displayDefinitionScript();
            $str .= $dd_chooser->display();
        }

        return $str;
    }

    /**
     * Perform Subscription management work.
     * This method processes selected subscriptions and calls the
     * right methods to subscribe or unsubscribe the user
     *
     * @param SugarBean $focus
     * @param string $record
     * @param array $input - user input ($_REQUEST)
     * @return void
     */
    function manageSubscriptions($focus, $record, $input){
        if ($focus->retrieve($record)) {
            require_once('modules/Campaigns/utils.php');            
            $all_lists = CampaignUtils::get_subscription_lists($focus);

            $sel_subs = array();
            if(! empty($input['subscribe_lists'])) {
                $sel_subs = array_map('trim', explode(",", $input['subscribe_lists']));
                $sel_subs = array_filter($sel_subs);
            }

            $cur_subs = array_keys($all_lists['subscribed']);
            $add_subs = array_diff($sel_subs, $cur_subs);
            $remove_subs = array_diff($cur_subs, $sel_subs);

            foreach($add_subs as $list_id)
                CampaignUtils::subscribe_to_list($focus, $list_id);

            foreach($remove_subs as $list_id)
                CampaignUtils::remove_from_list($focus, $list_id);
        }            
    }

    /**
     * Render filter dropdown for NewsLetters
     *
     * @param HtmlFormGenerator $gen
     * @param array $input - $_REQUEST
     * @param string $record
     * @return null|string
     */
    function renderFilterSelect(HtmlFormGenerator &$gen, $input, $record) {
        $selected_marketing_id = null;
        
        if (!empty($input['mkt_id'])) {
            $selected_marketing_id = $input['mkt_id'];
            $this->latest_marketing_id = $selected_marketing_id;
        }

        $options = array();

        $lq = new ListQuery('EmailMarketing', array('id', 'name', 'date_modified'));
        $lq->addSimpleFilter('campaign_id', $record);
        $lq->setOrderBy('date_modified desc');

        $result = $lq->fetchAll();

        if (! $result->failed) {

            foreach ($result->rows as $id => $details) {
                $options[$id] = $details['name'];
            }
        }

        $spec = array('name' => 'mkt_id', 'options' => $options, 'onchange' => 'return SUGAR.ui.sendForm(document.forms.DetailForm);');
        $filter_select = $gen->form_obj->renderSelect($spec, $selected_marketing_id);

        return $filter_select;
    }

    /**
     * Determines the appropriate image source
     *
     * @param int $num - "health" parameter being tracked whenever there is something wrong (higher number = bad)
     * @param int $total - the total number things being checked
     * @return string
     */
    function defineDiagnosticImage($num, $total) {
        //if health number is equal to total number then all checks failed, set red image
        if ($num == $total) {
            //red
            $info =  get_image_info('red_camp', true, 'border="0"');
        } elseif ($num == 0) {
            //if health number is zero, then all checks passed, set green image
            //green
            $info =  get_image_info('green_camp', true, 'border="0"');
        } else {
            //if health number is between total and num params, then some checks failed but not all, set yellow image
            //yellow
            $info =  get_image_info('yellow_camp', true, 'border="0"');
        }

        return $info['html'];
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {}
}
?>
