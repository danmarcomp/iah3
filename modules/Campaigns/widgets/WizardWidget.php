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
require_once('modules/Campaigns/utils.php');
require_once('modules/ProspectLists/ProspectList.php');
require_once 'modules/EmailMarketing/widgets/EmailTemplateEditCreate.php';

class WizardWidget extends FormTableSection {

    /**
     * Page type
     *
     * @var string
     */
    var $type;

    var $record_id;

    function init($params, $model=null) {
        parent::init($params, $model);
    }

	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $this->record_id = $row_result->getField('id');

        if ($this->id == 'wizard_newsletter') {
            return $this->renderNewsletter($gen, $row_result);
        } elseif ($this->id == 'wizard_marketing') {
            return $this->renderMarketing($gen, $row_result, $parents, $context);
        } else {
            return $this->renderHomeView($row_result);
        }
	}
	
	function getRequiredFields() {
        if ($this->id == 'wizard_marketing') {
            $fields = array('name', 'status', 'inbound_email', 'from_name', 'date_start', 'template', 'all_prospect_lists', 'campaign_id');
        } else {
            $fields = array('campaign_type', 'name', 'assigned_user', 'status', 'start_date', 'end_date', 'frequency',
                'content', 'budget', 'actual_cost', 'expected_revenue', 'expected_cost', 'impressions', 'objective',
                'currency');
        }

		return $fields;
	}

    /**
     * Render Wizard home page
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderHomeView(RowResult &$row_result) {
        $record = $this->record_id;

        if ($record) {
            return $this->renderDetails($row_result);
        } else {
            return $this->renderSelectType();
        }
    }

    /**
     * Render Select Campaign Type block
     *
     * @return string
     */
    function renderSelectType() {
        global $mod_strings;

        $body = <<<EOQ
            <div id='wiz_stage'>
            <table class='tabDetailView2' width="100%" border="0" cellspacing="1" cellpadding="0" >
            <tr><td rowspan='2' width=10% style='vertical-align: top;' class='tabDetailViewDL2'>
            <p><div id='nav'>
            <table border="0" cellspacing="0" cellpadding="0" width="100%" >
            <tr><td class='tabDetailViewDL2' ><div id='nav_step1'>{$mod_strings['LBL_CHOOSE_CAMPAIGN_TYPE']}</div></td></tr>
            </table>
            </div></p>
            </td>
            <td  rowspan='2' width='100%' class='tabForm'>
            <div id="wiz_message"></div>
            <div id=wizard>
            <div id='step1' >
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr><th colspan='2' align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_CHOOSE_CAMPAIGN_TYPE']}</h4></th></tr>
                <tr><td colspan='2' class="dataField"><p>{$mod_strings['LBL_HOME_START_MESSAGE']}</p></td></tr>
                <tr><td width='2%'>&nbsp;</td>
                <td class="dataField">
                <p><label><input type="radio"  id="wizardtype" name="wizardtype" value='1' checked="checked"> {$mod_strings['LBL_NEWSLETTER']}</label></p>
                <p><label><input type="radio"  id="wizardtype" name="wizardtype" value='2'> {$mod_strings['LBL_EMAIL']}</label></p>
                <p><label><input type="radio"  id="wizardtype" name='wizardtype' value='3'> {$mod_strings['LBL_OTHER_TYPE_CAMPAIGN']}</label></p>
                </td></tr>
                </table>
            </div>
            </td></tr>
            </table>
            <div id ='buttons' >
                <table width="100%" border="0" cellspacing="0" cellpadding="0" >
                <tr>
                <td  align="right" width='40%'>&nbsp;</td>
                <td  align="right" width='30%'>
                <table><tr>
                <td><div id="start_button_div"><input id="startbutton" type='submit' title="{$mod_strings['LBL_START']}" class="input-button" name="{$mod_strings['LBL_START']}" onclick="this.form.layout.value='WizardNewsletter';" value="{$mod_strings['LBL_START']}"></div></td>
                </tr></table>
                </td>
                </tr>
                </table>
            </div>
            </div>
EOQ;

        return $body;
    }

    function renderDetails(RowResult &$row_result) {
        global $mod_strings;

        $campaign_tbl = $this->createCampaignSummary($row_result);
        $targets_tbl = $this->createTargetSummary($row_result);
        $trackers_tbl = $this->createTrackerSummary($row_result);

        $record = $this->record_id;
        $campaign_type = $row_result->getField('campaign_type');
        $marketing_tbl = '';

        if ($campaign_type == 'NewsLetter' || $campaign_type =='Email')
            $marketing_tbl = $this->createMarketingSummary($row_result);

        $mrkt_string = $mod_strings['LBL_NAVIGATION_MENU_MARKETING'];
        $mrkt_url = "<a  href='index.php?action=WizardMarketing&module=Campaigns&return_module=Campaigns&return_action=WizardHome";
        $mrkt_url .= "&return_record=" .$record. "&campaign_id=" .$record;
        $mrkt_url .= "'>" .$mrkt_string. "</a>";
        $mrkt_string = $mrkt_url;

        $summ_url = "<a  href='index.php?action=WizardHome&module=Campaigns";
        $summ_url .= "&return_record=" .$record. "&record=" .$record;
        $summ_url .= "'> " .$mod_strings['LBL_NAVIGATION_MENU_SUMMARY']. "</a>";

        $camp_url = "index.php?action=WizardHome&layout=WizardNewsletter&module=Campaigns&return_module=Campaigns&return_action=WizardHome";
        $camp_url .= "&return_record=" .$record. "&record=" .$record. "&direct_step=";

        //Create the html to fill in the wizard steps
        if ($campaign_type == 'NewsLetter') {
            $menu_type = 'newsletter';
            $diagnostic_link = CampaignUtils::diagnose();
        } elseif ($campaign_type == 'Email') {
            $menu_type = 'email';
            $diagnostic_link = CampaignUtils::diagnose();
        } else {
            $menu_type = 'general';
            $diagnostic_link = '';
        }

        $nav_items = $this->createMenuItems($menu_type, $mrkt_string, $camp_url, $summ_url);

        $body = <<<EOQ
            <table class='tabDetailView2' width="100%" border="0" cellspacing="1" cellpadding="0">
            <tr><td rowspan='2' width='10%'  class='tabDetailViewDL2' style='vertical-align: top;' nowrap>
            <div id='nav'>
            {$nav_items}
            </div>
            </td>
            <td class='tabForm' rowspan='2' width='100%'>
            {$diagnostic_link}
            <table  width="100%" border="0" cellspacing="1" cellpadding="0">
            <tr><td>
                <div id='campaign_summary'>{$campaign_tbl}</div>
            </td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td>
                <div id='campaign_trackers'>{$trackers_tbl}</div>
            </td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td>
                <div id='campaign_targets'>{$targets_tbl}</div>
            </td></tr>
            <tr><td>&nbsp;</td></tr>
            <tr><td>
                <div id='campaign_marketing'>{$marketing_tbl}</div>
            </td></tr>
            </table>
            </td></tr>
            </table>
EOQ;

        return $body;
    }

    /**
     * Render Campaign summary table
     *
     * @param RowResult $row_result
     * @return string
     */
    function createCampaignSummary (RowResult $row_result) {
        global $mod_strings;

        $record = $this->record_id;
        $campaign_type = $row_result->getField('campaign_type');

        $fields = array('name', 'assigned_user', 'status', 'start_date', 'end_date');

        if ($campaign_type == 'NewsLetter')
            $fields[] = 'frequency';

        $fields[] = 'content';
        $fields[] = 'budget';
        $fields[] = 'actual_cost';
        $fields[] = 'expected_revenue';
        $fields[] = 'expected_cost';
        $fields[] = 'impressions';
        $fields[] = 'objective';

        //create edit campaign button
        $cmp_input =  "<input id='wiz_next_button' name='SUBMIT'  ";
        $cmp_input .= "onclick=\"this.form.return_module.value='Campaigns';";
        $cmp_input .= "this.form.module.value='Campaigns';";
        $cmp_input .= "this.form.layout.value='WizardNewsletter';";
        $cmp_input .= "this.form.return_action.value='WizardHome';";
        $cmp_input .= "this.form.direct_step.value='1';";
        $cmp_input .= "this.form.record.value='" .$record. "';";
        $cmp_input .= "this.form.return_record.value='" .$record. "';\" ";
        $cmp_input .= "class='input-button' value='" .$mod_strings['LBL_EDIT_EXISTING']. "' type='submit' /> ";

        //create view status button
        if (($campaign_type == 'NewsLetter') || ($campaign_type == 'Email')) {
            $cmp_input .=  " <input id='wiz_status_button' name='SUBMIT'  ";
            $cmp_input .= "onclick=\"this.form.return_module.value='Campaigns';";
            $cmp_input .= "this.form.module.value='Campaigns';";
            $cmp_input .= "this.form.action.value='DetailView';";
            $cmp_input .= "this.form.layout.value='Track';";
            $cmp_input .= "this.form.return_action.value='WizardHome';";
            $cmp_input .= "this.form.record.value='" .$record. "';";
            $cmp_input .= "this.form.return_record.value='" .$record. "';\" ";
            $cmp_input .= "class='input-button' value='".$mod_strings['LBL_TRACK_BUTTON_TITLE']."' type='submit' />";
        }

        //create view roi button
        $cmp_input .=  " <input id='wiz_status_button' name='SUBMIT'  ";
        $cmp_input .= "onclick=\"this.form.return_module.value='Campaigns';";
        $cmp_input .= "this.form.module.value='Campaigns';";
        $cmp_input .= "this.form.action.value='DetailView';";
        $cmp_input .= "this.form.layout.value='Roi';";
        $cmp_input .= "this.form.return_action.value='WizardHome';";
        $cmp_input .= "this.form.record.value='" .$record. "';";
        $cmp_input .= "this.form.return_record.value='" .$record. "';\" ";
        $cmp_input .= "class='input-button' value='" .$mod_strings['LBL_TRACK_ROI_BUTTON_LABEL']. "' type='submit' />";

        //Create Campaign Header
        $cmpgn_tbl = "<p><table class='tabForm' width='100%' border='0' cellspacing='0' cellpadding='0'>";
        $cmpgn_tbl .= "<tr><td class='dataField' align='left'><h4 class='dataLabel'> ".$mod_strings['LBL_LIST_CAMPAIGN_NAME'].'  '. $mod_strings['LBL_WIZ_NEWSLETTER_TITLE_SUMMARY']." </h4></td>";
        $cmpgn_tbl .= "<td align='right'>$cmp_input</td></tr>";

        foreach ($fields as $key) {
            $value = $row_result->getField($key, '', true);

            if ($value != '') {
                $cmpgn_tbl .= "<tr><td class='dataLabel' width='15%'>".$mod_strings[$row_result->fields[$key]['vname']]."</td><td class='dataField'>" .$value. "</td></tr>";
            }
        }

        $cmpgn_tbl .= "</table></p>";

        return $cmpgn_tbl;
    }

    /**
     * Render Target summary table
     *
     * @param RowResult $row_result
     * @return string
     */
    function createTargetSummary(RowResult $row_result) {
        global $mod_strings, $app_list_strings, $image_path, $odd_bg, $even_bg, $hilite_bg;
        $colorclass = '';

        $record = $this->record_id;
        $campaign_type = $row_result->getField('campaign_type');

        //set the title based on campaign type
        $target_title = $mod_strings['LBL_TARGET_LISTS'];
        if ($campaign_type == 'NewsLetter')
            $target_title = $mod_strings['LBL_NAVIGATION_MENU_SUBSCRIPTIONS'];

        $pl_tbl = "<p><table align='center' class='listView' width='100%' border='0' cellspacing='1' cellpadding='1'>";
        $pl_tbl .= "<tr class='tabDetailView'><td colspan='4'><h4> " .$target_title. " </h4></td></tr>";
        $pl_tbl .= "<tr class='listViewHRS1'><td width='50%'><b>" .$mod_strings['LBL_LIST_NAME']. "</b></td><td width='30%'><b>" .$mod_strings['LBL_LIST_TYPE']. "</b></td>";
        $pl_tbl .= "<td width='15%'><b>" .$mod_strings['LBL_TOTAL_ENTRIES']. "</b></td><td width='5%'>&nbsp;</td></tr>";

        $lq = new ListQuery($this->model, array(), array('link_name' => 'prospectlists', 'parent_key' => $this->record_id));
        $result = $lq->fetchAll();

        if (! $result->failed && sizeof($result->rows) > 0) {

            $prospectlist_fields = array('name', 'list_type', 'total_entries');

            foreach($result->rows as $row) {

                if( $colorclass == "class='evenListRowS1' bgColor='$even_bg' ") {
                    $colorclass = "class='oddListRowS1' bgColor='$odd_bg' ";
                    $bgColor=$odd_bg;
                } else {
                    $colorclass = "class='evenListRowS1' bgColor='$even_bg' ";
                    $bgColor = $even_bg;
                }

                $prospect_list = ListQuery::quick_fetch_row('ProspectList', $row['id'], $prospectlist_fields);

                //set the list type if this is a newsletter
                if ($prospect_list) {
                    $list_type = $prospect_list['list_type'];

                    if ($campaign_type == 'NewsLetter') {
                        if (($list_type == 'default') || ($list_type == 'seed'))
                            $list_type = $mod_strings['LBL_SUBSCRIPTION_TYPE_NAME'];
                        if ($list_type == 'exempt')
                            $list_type = $mod_strings['LBL_UNSUBSCRIPTION_TYPE_NAME'];
                        if ($list_type == 'test')
                            $list_type = $mod_strings['LBL_TEST_TYPE_NAME'];
                    } else {
                        $list_type = $app_list_strings['prospect_list_type_dom'][$list_type];
                    }

                    $pl_tbl .= "<tr onmouseover=\"setPointer(this, '', 'over', '$bgColor', '$hilite_bg', '');\" onmouseout=\"setPointer(this, '', 'out', '$bgColor', '$hilite_bg', '');\" onmousedown=\"setPointer(this, '', 'click', '$bgColor', '$hilite_bg', '');\">";
                    $pl_tbl .= "<td class='$colorclass' scope='row' width='50%'><a class='listViewTdLinkS1' href='index.php?action=DetailView&module=ProspectLists&return_module=Campaigns&return_action=WizardHome&return_record=".$record."&record=".$prospect_list['id']."'>";
                    $pl_tbl .=  $prospect_list['name']."</a></td>";
                    $pl_tbl .= "<td $colorclass scope='row' width='30%'>$list_type</td>";
                    $pl_tbl .= "<td $colorclass scope='row' width='15%'>".$prospect_list['total_entries']."</td>";
                    $pl_tbl .= "<td $colorclass scope='row' width='5%' align='right'><a class='listViewTdLinkS1' href='index.php?action=EditView&module=ProspectLists&return_module=Campaigns&return_action=WizardHome&return_record=".$record."&record=".$prospect_list['id']."'>";
                    $pl_tbl .= "<img src='".$image_path."edit_inline.gif' border=0></a>&nbsp;";
                    $pl_tbl .= "<a class='listViewTdLinkS1' href='index.php?action=DetailView&module=ProspectLists&return_module=Campaigns&return_action=WizardHome&return_record=".$record."&record=".$prospect_list['id']."'>";
                    $pl_tbl .= "<img src='".$image_path."view_inline.gif' border=0></a></td>";
                }
            }
        } else {
            $pl_tbl .= "<tr><td class='$colorclass' scope='row' colspan='2'>" .$mod_strings['LBL_NONE']. "</td></tr>";
        }

        $pl_tbl .= "</table></p>";

        return $pl_tbl;
    }

    /**
     * Render Tracker summary table
     *
     * @param RowResult $row_result
     * @return string
     */
    function createTrackerSummary(RowResult $row_result) {
        global $mod_strings, $odd_bg, $even_bg, $hilite_bg;
        $colorclass = '';

        //create tracker table
        $trkr_tbl = "<p><table align='center' class='listView' width='100%' border='0' cellspacing='1' cellpadding='1'>";
        $trkr_tbl .= "<tr class='tabDetailView'><td colspan='6'><h4> ".$mod_strings['LBL_NAVIGATION_MENU_TRACKERS']." </h4></td></tr>";
        $trkr_tbl .= "<tr class='listViewHRS1'><td width='15%'><b>".$mod_strings['LBL_EDIT_TRACKER_NAME']."</b></td><td width='15%'><b>".$mod_strings['LBL_EDIT_TRACKER_URL']."</b></td><td width='15%'><b>".$mod_strings['LBL_EDIT_OPT_OUT']."</b></td></tr>";

        $lq = new ListQuery('CampaignTracker', array());
        $lq->addSimpleFilter('campaign_id', $this->record_id);
        $result = $lq->fetchAll();

        if (! $result->failed && sizeof($result->rows) > 0) {
            $record = $this->record_id;
            $tracker_fields = array('tracker_name', 'is_optout', 'tracker_url');

            foreach($result->rows as $row) {

                if( $colorclass == "class='evenListRowS1' bgColor='$even_bg' ") {
                    $colorclass = "class='oddListRowS1' bgColor='$odd_bg' ";
                    $bgColor = $odd_bg;
                } else {
                    $colorclass = "class='evenListRowS1' bgColor='$even_bg' ";
                    $bgColor = $even_bg;
                }

                $tracker = ListQuery::quick_fetch_row('CampaignTracker', $row['id'], $tracker_fields);

                if (! empty($tracker['tracker_name'])) {
                    $opt = '';
                    if ($tracker['is_optout'])
                        $opt = 'checked';
                    $trkr_tbl .= "<tr onmouseover=\"setPointer(this, '', 'over', '$bgColor', '$hilite_bg', '');\" onmouseout=\"setPointer(this, '', 'out', '$bgColor', '$hilite_bg', '');\" onmousedown=\"setPointer(this, '', 'click', '$bgColor', '$hilite_bg', '');\">";
                    $trkr_tbl .= "<td $colorclass scope='row' ><a class='listViewTdLinkS1' href='index.php?action=DetailView&module=CampaignTrackers&return_module=Campaigns&return_action=WizardHome&return_record=".$record."&record=".$tracker['id']."'>";
                    $trkr_tbl .= $tracker['tracker_name']."</a></td>";
                    $trkr_tbl .= "<td $colorclass scope='row' width='15%'>".$tracker['tracker_url']."</td>";
                    $trkr_tbl .= "<td $colorclass scope='row' width='15%'>&nbsp;&nbsp;<input type='checkbox' class='checkbox' $opt disabled /></td>";
                    $trkr_tbl .= "</tr>";
                }
            }
        } else {
            $trkr_tbl .= "<tr ><td colspan='3'>".$mod_strings['LBL_NONE']."</td>";
        }

        $trkr_tbl .= "</table></p>";

        return $trkr_tbl ;

    }

    /**
     * Render Marketing summery table
     *
     * @param RowResult $row_result
     * @return string
     */
    function createMarketingSummary(RowResult $row_result) {
        global $mod_strings, $odd_bg, $even_bg, $hilite_bg;
        $colorclass = '';
        $record = $this->record_id;

        //create new marketing button input
        $new_mrkt_input =  "<input id='wiz_new_mrkt_button' name='SUBMIT' ";
        $new_mrkt_input .= "onclick=\"this.form.return_module.value='Campaigns';";
        $new_mrkt_input .= "this.form.module.value='Campaigns';";
        $new_mrkt_input .= "this.form.record.value='';";
        $new_mrkt_input .= "this.form.return_module.value='Campaigns';";
        $new_mrkt_input .= "this.form.action.value='WizardMarketing';";
        $new_mrkt_input .= "this.form.return_action.value='WizardHome';";
        $new_mrkt_input .= "this.form.direct_step.value='1';";
        $new_mrkt_input .= "this.form.campaign_id.value='".$record."';";
        $new_mrkt_input .= "this.form.return_record.value='".$record."';\" ";
        $new_mrkt_input .= "class='input-button' value='".$mod_strings['LBL_CREATE_NEW_MARKETING_EMAIL']."' type='submit' />";

        $mrkt_tbl = "<p><table  class='listView' width='100%' border='0' cellspacing='1' cellpadding='1'>";
        $mrkt_tbl .= "<tr class='tabDetailView'><td colspan='3'><h4> ".$mod_strings['LBL_WIZ_MARKETING_TITLE']." </h4></td>" . "<td colspan=2 align='right'>$new_mrkt_input</td></tr>";
        $mrkt_tbl .= "<tr class='listViewHRS1'><td width='15%'><b>".$mod_strings['LBL_MRKT_NAME']."</b></td><td width='15%'><b>".$mod_strings['LBL_FROM_MAILBOX_NAME']."</b></td><td width='15%'><b>".$mod_strings['LBL_STATUS_TEXT']."</b></td><td colspan=2>&nbsp;</td></tr>";

        $lq = new ListQuery($this->model, array(), array('link_name' => 'emailmarketing', 'parent_key' => $this->record_id));
        $result = $lq->fetchAll();

        if (! $result->failed && sizeof($result->rows) > 0) {
            $marketing_fields = array('name', 'from_name', 'status');

            foreach($result->rows as $row) {
                $marketing = ListQuery::quick_fetch_row('EmailMarketing', $row['id'], $marketing_fields);

                if ($marketing) {
                    $onclick = "return SUGAR.ui.sendForm(document.forms.DetailForm, {'action':'QueueCampaign', 'wiz_mass':'".$marketing['id']."', 'record':'".$record."', 'return_record':'".$record."', 'return_action':'WizardHome', 'mode':'test'});";
                    //create send test marketing button input
                    $test_mrkt_input =  "<input id='wiz_new_mrkt_button' name='SUBMIT'";
                    $test_mrkt_input .= "onclick=\"$onclick\"";
                    $test_mrkt_input .= "class='input-button' value='".$mod_strings['LBL_TEST_BUTTON_LABEL']."' type='submit' />";

                    //create send marketing button input
                    $send_mrkt_input =  "<input id='wiz_new_mrkt_button' name='SUBMIT'  ";
                    $onclick = "return SUGAR.ui.sendForm(document.forms.DetailForm, {'action':'QueueCampaign', 'wiz_mass':'".$marketing['id']."', 'record':'".$record."', 'return_record':'".$record."', 'return_action':'WizardHome', 'mode':'send'});";
                    $send_mrkt_input .= "onclick=\"$onclick\"";
                    $send_mrkt_input .= "class='input-button' value='".$mod_strings['LBL_SEND_EMAIL']."' type='submit' />";

                    if ( $colorclass == "class='evenListRowS1' bgColor='$even_bg' ") {
                        $colorclass = "class='oddListRowS1' bgColor='$odd_bg' ";
                        $bgColor = $odd_bg;
                    } else {
                        $colorclass = "class='evenListRowS1' bgColor='$even_bg' ";
                        $bgColor = $even_bg;
                    }

                    if (! empty($marketing['name'])) {
                        $mrkt_tbl .= "<tr onmouseover=\"setPointer(this, '', 'over', '$bgColor', '$hilite_bg', '');\" onmouseout=\"setPointer(this, '', 'out', '$bgColor', '$hilite_bg', '');\" onmousedown=\"setPointer(this, '', 'click', '$bgColor', '$hilite_bg', '');\">";
                        $mrkt_tbl .= "<td class='$colorclass  scope='row' width='40%'><a class='listViewTdLinkS1' href='index.php?action=WizardMarketing&module=Campaigns&return_module=Campaigns&return_action=WizardHome";
                        $mrkt_tbl .= "&return_record=" .$record. "&campaign_id=" .$record."&record=".$marketing['id']."'>".$marketing['name']."</a></td>";
                        $mrkt_tbl .= "<td $colorclass scope='row' width='25%'>".$marketing['from_name']."</td>";
                        $mrkt_tbl .= "<td $colorclass  scope='row' width='15%'>".$marketing['status']."</td>";
                        $mrkt_tbl .= "<td $colorclass  scope='row' width='10%'>$test_mrkt_input</td>";
                        $mrkt_tbl .= "<td $colorclass  scope='row' width='10%'>$send_mrkt_input</td>";
                        $mrkt_tbl .= "</tr>";
                    }
                }
            }
        } else {
            $mrkt_tbl  .= "<tr><td colspan='3'>".$mod_strings['LBL_NONE']."</td></tr>";
        }

        $mrkt_tbl .= "</table></p>";

        return $mrkt_tbl ;
    }

    /**
     * Create Wizard Menu
     *
     * @param string $type
     * @param string $mrkt_string: general, newsletter, email
     * @param string $camp_url
     * @param string $summ_url
     * @param array $steps
     * @return string
     */
    function createMenuItems($type, $mrkt_string, $camp_url, $summ_url, $steps = array()) {
        global $mod_strings;
        $link_in_steps = true;

        if (sizeof($steps) == 0) {
            $link_in_steps = false;
            $steps[$mod_strings['LBL_NAVIGATION_MENU_GEN1']] = 'Header';
            $steps[$mod_strings['LBL_NAVIGATION_MENU_GEN2']] = 'Budget';
            $steps[$mod_strings['LBL_NAVIGATION_MENU_TRACKERS']] = 'Tracker';
        }

        if ($type == 'newsletter') {
            $steps[$mod_strings['LBL_NAVIGATION_MENU_SUBSCRIPTIONS']] = 'TargetLists';
        } else {
            $steps[$mod_strings['LBL_TARGET_LISTS']] = 'NewsLetter';
        }

        $nav_html = '<table border="0" cellspacing="0" cellpadding="0" width="100%" >';
        $i = 0;

        if (isset($steps) && ! empty($steps)){
            $i = 1;

            foreach ($steps as $name => $step) {
                $nav_html .= "<tr><td class='tabDetailViewDL2' nowrap><div id='nav_step$i'>";

                if ($link_in_steps) {
                    $nav_html .= $name;
                } else {
                    $nav_html .= "<a href='" .$camp_url.$i. "'>$name</a>";
                }

                $nav_html .= "</div></td></tr>";
                $i++;
            }
        }

        if ($type == 'newsletter' ||  $type == 'email') {
            $nav_html .= "<td class='tabDetailViewDL2' nowrap><div id='nav_step'".($i+1).">$mrkt_string</div></td></tr>";
            $nav_html .= "<td class='tabDetailViewDL2' nowrap><div id='nav_step'".($i+2).">".$mod_strings['LBL_NAVIGATION_MENU_SEND_EMAIL']."</div></td></tr>";
            $nav_html .= "<td class='tabDetailViewDL2' nowrap><div id='nav_step'".($i+3).">".$summ_url."</div></td></tr>";
        } else {
            $nav_html .= "<td class='tabDetailViewDL2' nowrap><div id='nav_step'".($i+1).">".$summ_url."</div></td></tr>";
        }

        $nav_html .= '</table>';

        return $nav_html;
    }

    /**
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return void
     */
    function renderNewsletter(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_strings;

        $record = $this->record_id;
        $campaign_type = $row_result->getField('campaign_type');

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/wizard.js');
        $layout->addScriptLiteral($this->getNewslettersJs($record), LOAD_PRIORITY_FOOT);

        if( (isset($_REQUEST['wizardtype']) && $_REQUEST['wizardtype'] == 1) || ($campaign_type == 'NewsLetter') ) {
            $this->type = 'newsletter';
            $menu_type = 'newsletter';
            $hide_continue = 'submit';
            $type_hidden = '<input type="hidden" name="campaign_type" value="NewsLetter" />';
        } elseif ( (isset($_REQUEST['wizardtype']) && $_REQUEST['wizardtype'] == 2) || ($campaign_type == 'Email') ) {
            $this->type = 'email';
            $menu_type = 'email';
            $hide_continue = 'submit';
            $type_hidden = '<input type="hidden" name="campaign_type" value="Email" />';
        } else {
            $this->type = 'general';
            $menu_type = 'campaign';
            $hide_continue = 'hidden';
            $type_hidden = '';
        }

        $mrkt_string = $mod_strings['LBL_NAVIGATION_MENU_MARKETING'];

        if( ! empty($record)) {
            $mrkt_url = "<a  href='index.php?action=WizardMarketing&module=Campaigns&return_module=Campaigns&return_action=WizardHome";
            $mrkt_url .= "&return_record=".$record."&campaign_id=".$record;
            $mrkt_url .= "'>". $mrkt_string."</a>";
            $mrkt_string = $mrkt_url;
        }

        $summ_url = $mod_strings['LBL_NAVIGATION_MENU_SUMMARY'];

        if(! empty($record)) {
            $summ_url = "<a  href='index.php?action=WizardHome&module=Campaigns";
            $summ_url .= "&return_record=".$record."&record=".$record;
            $summ_url .= "'> ". $mod_strings['LBL_NAVIGATION_MENU_SUMMARY']."</a>";
        }

        $steps = $this->createSteps($menu_type);
        $nav_items = $this->createMenuItems($menu_type, $mrkt_string, '', $summ_url, $steps);
        $total_steps = sizeof($steps);
        $steps_html = $this->createWizStepDivs($steps, $gen, $row_result);

        $body = <<<EQO
            {$type_hidden}
            <input type="hidden" id="wiz_total_steps" name="totalsteps" value="{$total_steps}" />
            <input type="hidden" id="wiz_current_step" name="currentstep" value='1' />
            <p><div id ='buttons'>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 2px">
                <tr>
                <td align="left" width='30%'>
                <table border="0" cellspacing="0" cellpadding="0" ><tr>
                <td><div id="back_button_div"><input id="wiz_back_button" type='button' title="{$app_strings['LBL_BACK']}" class="input-button" onclick="javascript:navigate('back');"  name="back" value="{$app_strings['LBL_BACK']}" />&nbsp;</div></td>
                <td><div id="cancel_button_div"><input id="wiz_cancel_button" title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="this.form.action.value='WizardHome'; this.form.module.value='Campaigns'; this.form.layout.value='WizardHome'; this.form.record.value='{$record}'; this.form.submit();" type="button" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />&nbsp;</div></td>
                <td><div id="save_button_div">
                <input id="wiz_submit_button" class="input-button" onclick="gatherTrackers();return SUGAR.ui.sendForm(document.forms.DetailForm, {'record_perform':'save', 'next':'WizardMarketing'}, null);"  accesKey="{$app_strings['LBL_SAVE_BUTTON_TITLE']}" type="{$hide_continue}" name="button" value="{$mod_strings['LBL_SAVE_CONTINUE_BUTTON_LABEL']}" />
                <input id="wiz_submit_finish_button" class="input-button" onclick="gatherTrackers();return SUGAR.ui.sendForm(document.forms.DetailForm, {'record_perform':'save','layout':'WizardHome'}, null);"  accesKey="{$app_strings['LBL_SAVE_BUTTON_TITLE']}" type="submit" name="button" value="{$mod_strings['LBL_SAVE_EXIT_BUTTON_LABEL']}" />
                </div></td>
                <td><div id="next_button_div"><input id="wiz_next_button" type='button' title="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" class="input-button" onclick="javascript:navigate('next');" name="button" value="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" />&nbsp;</div></td>
                </tr></table>
                </td>
                <td  align="right" width='70%'><div id='wiz_location_message'></div></td>
                </tr>
            </table>
            </div></p>
            <table class='tabDetailView2' width="100%" border="0" cellspacing="1" cellpadding="0" >
            <tr><td class='tabDetailViewDL2' rowspan='2' width='10%' style='vertical-align: top;' nowrap>
            <div id='nav'>{$nav_items}</div>
            </td>
            <td class='tabForm' rowspan='2' width='100%'>
            <div id="wiz_message"></div>
            <div id="wizard">{$steps_html}</div>
            </td></tr>
            </table>
            <div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>
EQO;

        return $body;
    }

    
    /**
     * Create wizard steps
     *
     * @param string $type
     * @return array
     */
    function createSteps($type) {
        global $mod_strings;
        $steps[$mod_strings['LBL_NAVIGATION_MENU_GEN1']] = 'Header';
        $steps[$mod_strings['LBL_NAVIGATION_MENU_GEN2']] = 'Budget';
        $steps[$mod_strings['LBL_NAVIGATION_MENU_TRACKERS']] = 'Tracker';

        if ($type == 'newsletter') {
            $steps[$mod_strings['LBL_NAVIGATION_MENU_SUBSCRIPTIONS']] = 'TargetList';
        } else {
            $steps[$mod_strings['LBL_TARGET_LISTS']] = 'TargetListForNonNewsLetter';
        }

        return  $steps;
    }

    /**
     * Render wizard steps divs
     *
     * @param array $steps
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function createWizStepDivs($steps, HtmlFormGenerator &$gen, RowResult &$row_result) {
        $step_html = '';

        if (sizeof($steps) > 0) {
            $i = 1;

            foreach ($steps as $name => $step) {
                $step_html .= "<p><div id='step$i'>";
                $step_html .= $this->renderStep($step, $gen, $row_result);
                $step_html .= "</div></p>";
                $i = $i + 1;
            }
        }

        return $step_html;
    }

    /**
     * Render wizard newsletter step
     *
     * @param  $step
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderStep($step, HtmlFormGenerator &$gen, RowResult &$row_result) {

        switch ($step) {
            case "Header":
                $html = $this->renderHeader($gen, $row_result);
                break;
            case "Budget":
                $html = $this->renderBudget($gen, $row_result);
                break;
            case "Tracker":
                $html = $this->renderTracker($gen);
                break;
            case "TargetList":
                $html = $this->renderSubscriptions($gen);
                break;
            case "TargetListForNonNewsLetter":
                $html = $this->renderTargetList($gen);
                break;
            default:
                $html = '';
                break;
        }

        return $html;
    }

    /**
     * Render Campaign Header block
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderHeader(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings, $app_strings;

        $name = $gen->form_obj->renderField($row_result, 'name');
        $assigned_user = $gen->form_obj->renderField($row_result, 'assigned_user');
        $status = $gen->form_obj->renderField($row_result, 'status');
        $start_date = $gen->form_obj->renderField($row_result, 'start_date');
        $end_date = $gen->form_obj->renderField($row_result, 'end_date');
        $type = $gen->form_obj->renderField($row_result, 'campaign_type');
        $frequency = $gen->form_obj->renderField($row_result, 'frequency');
        $content = $gen->form_obj->renderField($row_result, 'content');

        if ($this->type == 'newsletter') {
            $diagnostic_link = CampaignUtils::diagnose();
            $frequency_label = $mod_strings['LBL_CAMPAIGN_FREQUENCY'];
            $type = $mod_strings['LBL_NEWSLETTER'];
        } elseif ($this->type == 'email') {
            $diagnostic_link = CampaignUtils::diagnose();
            $frequency_label = '&nbsp;';
            $type = $mod_strings['LBL_EMAIL'];
            $frequency = '&nbsp;';
        } else {
            $diagnostic_link = '';
            $frequency_label = '&nbsp';
            $frequency = '&nbsp;';
        }

        $body = <<<EOQ
            {$diagnostic_link}
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td  colspan="3"><h3>{$mod_strings['LBL_WIZ_NEWSLETTER_TITLE_STEP1']}</h3></div></td>
                <td colspan="1">&nbsp;</td>
                </tr>
                <tr><td class="datalabel" colspan="3">{$mod_strings['LBL_WIZARD_HEADER_MESSAGE']}<br></td><td>&nbsp;</td></tr>
                <tr><td class="datalabel" colspan="4">&nbsp;</td></tr>
                <tr>
                <td width="17%" class="dataLabel">{$mod_strings['LBL_NAME']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></span sugar='slot'></td>
                <td width="33%" class="dataField">{$name}</td>
                <td width="15%" class="dataLabel">{$app_strings['LBL_ASSIGNED_TO']}</td>
                <td width="35%" class="dataField">{$assigned_user}</td>
                </tr>
                <tr>
                <td width="15%" class="dataLabel">{$mod_strings['LBL_CAMPAIGN_STATUS']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></td>
                <td width="35%" class="dataField">{$status}</td>
                </tr>
                <tr>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_START_DATE']}</td>
                <td class="dataField">{$start_date}</td>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_TYPE']}</td>
                <td>{$type}</td>
                </tr>
                <tr>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_END_DATE']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></</td>
                <td class="dataField">{$end_date}</td>
                <td class="dataLabel">{$frequency_label} </td>
                <td>{$frequency}</td>
                </tr>
                <tr>
                <td width="15%" class="dataLabel">&nbsp;</td>
                <td width="35%" class="dataField">&nbsp;</td>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                <tr>
                </tr>
                <td valign="top" class="dataLabel">{$mod_strings['LBL_CAMPAIGN_CONTENT']}</td>
                <td colspan="4">{$content}</td>
                </tr>
                <tr>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                </tr>
            </table><p>
EOQ;

        return $body;
    }

    /**
     * Render Budget block
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
    function renderBudget(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;

        $budget = $gen->form_obj->renderField($row_result, 'budget');
        $actual_cost = $gen->form_obj->renderField($row_result, 'actual_cost');
        $expected_revenue = $gen->form_obj->renderField($row_result, 'expected_revenue');
        $expected_cost = $gen->form_obj->renderField($row_result, 'expected_cost');
        $currency = $gen->form_obj->renderField($row_result, 'currency');
        $impressions = $gen->form_obj->renderField($row_result, 'impressions');
        $objective = $gen->form_obj->renderField($row_result, 'objective');

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <th colspan="4" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_WIZ_NEWSLETTER_TITLE_STEP2']}</h4></th>
                </tr>
                <tr><td class="datalabel" colspan="3">{$mod_strings['LBL_WIZARD_BUDGET_MESSAGE']}<br></td><td>&nbsp;</td></tr>
                <tr><td class="datalabel" colspan="4">&nbsp;</td></tr>
                <tr>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_BUDGET']}</td>
                <td class="dataField">{$budget}</td>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_ACTUAL_COST']}</td>
                <td class="dataField">{$actual_cost}</td>
                </tr>
                <tr>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_EXPECTED_REVENUE']}</td>
                <td class="dataField">{$expected_revenue}</td>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_EXPECTED_COST']}</td>
                <td class="dataField">{$expected_cost}</td>
                </tr>
                <tr>
                <td class="dataLabel">{$mod_strings['LBL_CURRENCY']}</td>
                <td><span sugar='slot18b'>{$currency}</td>
                <td class="dataLabel">{$mod_strings['LBL_CAMPAIGN_IMPRESSIONS']}</td>
                <td class="dataField">{$impressions}</td></tr>
                <tr>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                </tr>
                <tr>
                <td valign="top" class="dataLabel">{$mod_strings['LBL_CAMPAIGN_OBJECTIVE']}</td>
                <td colspan="4">{$objective}</td>
                </tr>
                <tr>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                <td class="dataLabel">&nbsp;</td>
                <td>&nbsp;</td>
                </tr>
            </table>
EOQ;

        return $body;                
    }

    /**
     * Render Tracker block
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderTracker(HtmlFormGenerator &$gen) {
        global $mod_strings, $image_path;

        $tracker_count = 0;
        $lq = new ListQuery('CampaignTracker', array());
        $lq->addSimpleFilter('campaign_id', $this->record_id);

        $result = $lq->fetchAll();
        if (! $result->failed)
            $tracker_count = sizeof($result->rows);

        $trkr_html ='';

        if ($tracker_count > 0) {
            $count = 0;
            $tracker_fields = array('tracker_name', 'is_optout', 'tracker_url');

            foreach ($result->rows as $row) {
                $tracker = ListQuery::quick_fetch_row('CampaignTracker', $row['id'], $tracker_fields);

                if(! empty($tracker['tracker_name'])) {
                    if ($tracker['is_optout']) {
                        $opt = 'checked';
                    } else {
                        $opt = '';
                    }

                    $trkr_html .= "<div id='existing_trkr".$count."'> <table width='100%' border='0' cellspacing='0' cellpadding='0'>" ;
                    $trkr_html .= "<tr ><td class='evenListRowS1' width='15%'><input name='wiz_step3_is_optout".$count."' title='".$mod_strings['LBL_EDIT_OPT_OUT'] . $count ."' id='existing_is_optout". $count ."' class='checkbox' type='checkbox' $opt  /><input name='wiz_step3_id".$count."' value='".$tracker['id']."' id='existing_tracker_id". $count ."'type='hidden''/></td>";
                    $trkr_html .= "<td class='evenListRowS1' width='40%'> <input id='existing_tracker_name". $count ."' type='text' size='20' maxlength='255' name='wiz_step3_tracker_name". $count ."' title='".$mod_strings['LBL_EDIT_TRACKER_NAME']. $count ."' value='".$tracker['tracker_name']."' ></td>";
                    $trkr_html .= "<td class='evenListRowS1' width='40%'><input type='text' size='60' maxlength='255' name='wiz_step3_tracker_url". $count ."' title='".$mod_strings['LBL_EDIT_TRACKER_URL']. $count ."' id='existing_tracker_url". $count ."' value='".$tracker['tracker_url']."' ></td>";
                    $trkr_html .= "<td class='evenListRowS1'><a href='#' onclick=\"javascript:remove_existing_tracker('existing_trkr".$count."','".$tracker['id']."'); \" >  ";
                    $trkr_html .= "<img src='".$image_path."delete_inline.gif' border='0' alt='rem' align='absmiddle' border='0' height='12' width='12'>". $mod_strings['LBL_REMOVE']."</a></td></tr></table></div>";
                }
                
                $count++;
            }

            $trkr_html .= "<div id='no_trackers'></div>";
        } else {
            $trkr_html .= "<div id='no_trackers'><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td class='evenListRowS1'>".$mod_strings['LBL_NONE']."</td></tr></table></div>";
        }

        $body = <<<EOQ
            <input type="hidden" id="existing_tracker_count" name="existing_tracker_count" value={$tracker_count} />
            <input type="hidden" id="added_tracker_count" name="added_tracker_count" value='' />
            <input type="hidden" id="wiz_list_of_existing_trackers" name="wiz_list_of_existing_trackers" value="" />
            <input type="hidden" id="wiz_list_of_trackers" name="wiz_list_of_trackers" value="" />
            <input type="hidden" id="wiz_remove_tracker_list" name="wiz_remove_tracker_list" value="" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <th colspan="4" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_WIZ_NEWSLETTER_TITLE_STEP3']}</h4></th>
                </tr>
                <tr><td class="datalabel" colspan="3">{$mod_strings['LBL_WIZARD_TRACKER_MESSAGE']}<br></td><td>&nbsp;</td></tr>
                <tr><td class="datalabel" colspan="4">&nbsp;</td></tr>
            </table>
            <div id='tracker_input_div'>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td width="15%" class="dataLabel"><slot>{$mod_strings['LBL_EDIT_TRACKER_NAME']}<span class="required">&nbsp;</span></slot></td>
                <td width="25%" class="dataField"><slot><input id="tracker_name" type="text" size="30" tabindex='1' name="tracker_name" title="{$mod_strings['LBL_EDIT_TRACKER_NAME']}" value="" /></slot></td>
                <td width="25%" class="dataLabel"><slot><input onclick="toggle_tracker_url(this);" name="is_optout" title="{$mod_strings['LBL_EDIT_OPT_OUT']}" id="is_optout" tabindex='2' class="checkbox" type="checkbox" />&nbsp;{$mod_strings['LBL_EDIT_OPT_OUT_']}</slot></td>
                <td width="35%" class="dataField"><slot>&nbsp;</slot></td>
                </tr>
                <tr>
                <td class="dataLabel"><slot>{$mod_strings['LBL_EDIT_TRACKER_URL']}&nbsp;<span class="required"></span></slot></td>
                <td class="dataField" colspan=3><slot><input type="text" size="80" maxlength='255' tabindex='3' name="tracker_url" title="{$mod_strings['LBL_EDIT_TRACKER_URL']}" id="tracker_url" value="" /></slot> <input type='button' value ='{$mod_strings['LBL_ADD_TRACKER']}' class='input-button' onclick='javascript:add_tracker();' /></td>
                </tr>
                <tr><td colspan='4'>&nbsp;</td></tr>
                </table>
            </div>
            <table width='100%' border="0" cellspacing="0" cellpadding="0">
                <tr><td>&nbsp;</td></tr>
                <tr><td class='listView'>
                <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr >
                <td width='15%' class="listViewThS1" nowrap>{$mod_strings['LBL_EDIT_OPT_OUT']}</td>
                <td width='40%' class="listViewThS1">{$mod_strings['LBL_EDIT_TRACKER_NAME']}</td>
                <td width='45%' class="listViewThS1" colspan="2">{$mod_strings['LBL_EDIT_TRACKER_URL']}</td>
                </tr>
                </table>
                <div id='added_trackers'>{$trkr_html}</div>
                </td></tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/tracker.js');

        $js = "var image_path = '".$image_path."'; var EDIT_TRACKER_NAME = '".$mod_strings['LBL_EDIT_TRACKER_NAME']."';
            var EDIT_TRACKER_URL = '".$mod_strings['LBL_EDIT_TRACKER_URL']."'; var EDIT_OPT_OUT = '".$mod_strings['LBL_EDIT_OPT_OUT']."';
            var REMOVE = '".$mod_strings['LBL_REMOVE']."';";

        $layout->addScriptLiteral($js, LOAD_PRIORITY_FOOT);

        return $body;
    }

    /**
     * Render Newsletter Subscriptions block
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderSubscriptions(HtmlFormGenerator &$gen) {
        global $mod_strings, $app_strings, $theme;

        $subscription_id = '';
        $subscription_name = '';
        $unsubscription_id = '';
        $unsubscription_name = '';
        $test_id = '';
        $test_name = '';

        $target_list_ids = $this->getRelatedTargetLists();

        if (sizeof($target_list_ids) > 0) {

            foreach($target_list_ids as $lis_id) {
                $prospect_list = $this->loadTargetList($lis_id['id']);

                if (! empty($prospect_list['list_type'])) {
                    if (($prospect_list['list_type'] == 'default') || ($prospect_list['list_type'] == 'seed')) {
                        $subscription_id = $prospect_list['id'];
                        $subscription_name = $prospect_list['name'];
                    } elseif ($prospect_list['list_type'] == 'exempt') {
                        $unsubscription_id = $prospect_list['id'];
                        $unsubscription_name = $prospect_list['name'];
                    } elseif ($prospect_list['list_type'] == 'test') {
                        $test_id = $prospect_list['id'];
                        $test_name = $prospect_list['name'];
                    }
                }
            }
        }

        $json = getJSONobj();

        $popup_request_data = array (
            'call_back_function' => 'set_return',
            'form_name' => 'DetailForm',
            'field_to_name_array' => array (
                'id' => 'wiz_step3_subscription_name_id',
                'name' => 'wiz_step3_subscription_name',
            ),
        );

        $subscription_data = $json->encode($popup_request_data);

        $popup_request_data = array (
            'call_back_function' => 'set_return',
            'form_name' => 'DetailForm',
            'field_to_name_array' => array (
                'id' => 'wiz_step3_unsubscription_name_id',
                'name' => 'unsubscription_name',
            ),
        );

        $unsubscription_data = $json->encode($popup_request_data);

        $popup_request_data = array (
            'call_back_function' => 'set_return',
            'form_name' => 'DetailForm',
            'field_to_name_array' => array (
                'id' => 'wiz_step3_test_name_id',
                'name' => 'test_name',
            ),
        );

        $test_data = $json->encode($popup_request_data);

        $body = <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <th colspan="4" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_WIZ_NEWSLETTER_TITLE_STEP4']}</h4></th>
            </tr>
            <tr>
            <td class="dataLabel" colspan="4">{$mod_strings['LBL_WIZARD_SUBSCRIPTION_MESSAGE']}<br></td>
            </tr>
            <tr>
            <td class="dataLabel" colspan="4">&nbsp;</td>
            </tr>
            <tr>
            <td class="dataLabel"><span sugar='slot26'><img border="0" src="themes/{$theme}/images/help.gif"  onmouseover="return SUGAR.popups.tooltip('{$mod_strings['LBL_SUBSCRIPTION_TARGET_WIZARD_DESC']}', this);">
            {$mod_strings['LBL_SUBSCRIPTION_LIST_NAME']}</span sugar='slot'>
            </td>
            <td><label><input type='radio' onclick="change_target_list(this,'subs');" name='wiz_subscriptions_def_type' id='wiz_subscriptions_def_type' title="{$mod_strings['LBL_DEFAULT_LOCATION']}" value="1" /> {$mod_strings['LBL_DEFAULT_LOCATION']}</label><br>
                <label><input type='radio' onclick="change_target_list(this,'subs');" name='wiz_subscriptions_def_type' id='wiz_subscriptions_def_type' title="{$mod_strings['LBL_CUSTOM_LOCATION']}" value="2" checked="checked" /> {$mod_strings['LBL_CUSTOM_LOCATION']}</label>
            </td>
            <td class="dataField"  colspan='2'><span sugar='slot26b'>
            <input class="sqsEnabled" autocomplete="off" id="subscription_name" name="wiz_step3_subscription_name" title='{$mod_strings['LBL_SUBSCRIPTION_LIST_NAME']}' type="text" size='35' value="{$subscription_name}" />
            <input id='prospect_list_type_default' name='prospect_list_type_default' type="hidden" value="default" />
            <input id='wiz_step3_subscription_name_id' name='wiz_step3_subscription_list_id' title='Subscription List ID' type="hidden" value='{$subscription_id}' />
            <input title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" tabindex='1' class="input-button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn1 id='wiz_step3_subscription_name_button'
            onclick='open_popup("ProspectLists", 600, 400, "&query=1&list_type=default", true, false,  {$subscription_data}, "single", true);' />
            </span sugar='slot'></td>
            </tr>
            <tr><td colspan='4'>&nbsp;</td></tr>
            <tr>
            <td class="dataLabel"><span sugar='slot27'><img border="0" src="themes/{$theme}/images/help.gif" onmouseover="return SUGAR.popups.tooltip('{$mod_strings['LBL_UNSUBSCRIPTION_TARGET_WIZARD_DESC']}', this);" >
            {$mod_strings['LBL_UNSUBSCRIPTION_LIST_NAME']}</span sugar='slot'>
            </td>
            <td><label><input type='radio' onclick="change_target_list(this,'unsubs');" name='wiz_unsubscriptions_def_type' id='wiz_unsubscriptions_def_type' title="{$mod_strings['LBL_DEFAULT_LOCATION']}" value="1" /> {$mod_strings['LBL_DEFAULT_LOCATION']}</label><br>
            <label><input type='radio' onclick="change_target_list(this,'unsubs');" name='wiz_unsubscriptions_def_type' id='wiz_unsubscriptions_def_type' title="{$mod_strings['LBL_CUSTOM_LOCATION']}" value="2" checked="checked" /> {$mod_strings['LBL_CUSTOM_LOCATION']}</label>
            </td>
            <td class="dataField"  colspan='2'><span sugar='slot27b'>
            <input  class="sqsEnabled" autocomplete="off" id="unsubscription_name" name="wiz_step3_unsubscription_name" title='{$mod_strings['LBL_UNSUBSCRIPTION_LIST_NAME']}' type="text" size='35' value="{$unsubscription_name}" />
            <input id='prospect_list_type_exempt' name='prospect_list_type_exempt' type="hidden" value="exempt" />
            <input id='wiz_step3_unsubscription_name_id' name='wiz_step3_unsubscription_list_id' title='UnSubscription List ID' type="hidden" value='{$unsubscription_id}' />
            <input title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" tabindex='1' class="input-button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn2 id='wiz_step3_unsubscription_name_button'
            onclick='open_popup("ProspectLists", 600, 400, "&query=1&list_type=exempt", true, false,  {$unsubscription_data}, "single", true);' />
            </span sugar='slot'></td>
            </tr>
            <tr><td colspan='4'>&nbsp;</td></tr>
            <tr>
            <td class="dataLabel">
            <span sugar='slot28'><img border="0" src="themes/{$theme}/images/help.gif"  onmouseover="return SUGAR.popups.tooltip('{$mod_strings['LBL_TEST_TARGET_WIZARD_DESC']}', this);">
            {$mod_strings['LBL_TEST_LIST_NAME']}</span sugar='slot'>
            </td>
            <td><label><input type='radio' onclick="change_target_list(this,'test');" name='wiz_test_def_type' id='wiz_test_def_type' title="{$mod_strings['LBL_DEFAULT_LOCATION']}" value="1" /> {$mod_strings['LBL_DEFAULT_LOCATION']}</label><br>
            <label><input type='radio' onclick="change_target_list(this,'test');" name='wiz_test_def_type' id='wiz_test_def_type' title="{$mod_strings['LBL_CUSTOM_LOCATION']}" value="2" checked="checked" /> {$mod_strings['LBL_CUSTOM_LOCATION']}</label>
            </td>
            <td class="dataField"  colspan='2'><span sugar='slot28b'>
            <input class="sqsEnabled" autocomplete="off" id="test_name" name="wiz_step3_test_name" title='{$mod_strings['LBL_TEST_LIST_NAME']}' type="text" size='35' value="{$test_name}" />
            <input id='prospect_list_type_test' name='prospect_list_type_test' type="hidden" value="test" />
            <input id='wiz_step3_test_name_id' name='wiz_step3_test_list_id' title='Test List ID' type="hidden" value='{$test_id}' />
            <input title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SELECT_BUTTON_KEY']}" type="button" tabindex='1' class="input-button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn3 id='wiz_step3_test_name_button'
            onclick='open_popup("ProspectLists", 600, 400, "&query=1&list_type=test", true, false,  {$test_data}, "single", true);' />
            </span sugar='slot'></td>
            </tr>
            <tr>
            <td class="dataLabel">&nbsp;</td>
            <td>&nbsp;</td>
            <td class="dataLabel">&nbsp;</td>
            <td>&nbsp;</td>
            </tr>
            </table>
EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/subscriptions.js');

        $js = "var subscription_id = '".$subscription_id."'; var subscription_name = '".$subscription_name."';
            var unsubscription_id = '".$unsubscription_id."'; var unsubscription_name = '".$unsubscription_name."';
            var test_id = '".$test_id."'; var test_name = '".$test_name."';  var SUBSCRIPTION_LIST = '".$mod_strings['LBL_SUBSCRIPTION_LIST']."';
            var UNSUBSCRIPTION_LIST = '".$mod_strings['LBL_UNSUBSCRIPTION_LIST']."'; var TEST_LIST = '".$mod_strings['LBL_TEST_LIST']."';";

        $layout->addScriptLiteral($js, LOAD_PRIORITY_FOOT);

        return $body;
    }

    /**
     * Render Target List block for non-newsletters campaign
     *
     * @param HtmlFormGenerator $gen
     * @return string
     */
    function renderTargetList(HtmlFormGenerator &$gen) {
        global $mod_strings, $app_strings, $app_list_strings, $image_path;

        $popup_request_data = array (
            'call_back_function' => 'set_return_prospect_list',
            'form_name' => 'DetailForm',
            'field_to_name_array' => array (
                'popup_target_list_id' => 'id',
                'popup_target_list_name' => 'name',
                'popup_target_list_type' => 'list_type'
            ),
        );

        $json = getJSONobj();
        $target_list_data = $json->encode($popup_request_data);

        $target_options = get_select_options_with_id($app_list_strings['prospect_list_type_dom'], 'default');

        $trgt_count = 0;
        $trgt_html = ' ';

        $target_list_ids = $this->getRelatedTargetLists();

        if (sizeof($target_list_ids) > 0) {

            foreach($target_list_ids as $lis_id) {
                $prospect_list = $this->loadTargetList($lis_id['id']);

                $trgt_html .= "<div id='existing_trgt".$trgt_count."'> <table class='tabDetailViewDL2' width='100%'>" ;
                $trgt_html .= "<td width='25%'> <input id='existing_target_name". $trgt_count ."' type='hidden' type='text' size='60' maxlength='255' name='existing_target_name". $trgt_count ."'  value='". $prospect_list['name']."' />". $prospect_list['name']."</td>";
                $trgt_html .= "<td width='25%'><input type='hidden' size='60' maxlength='255' name='existing_tracker_list_type". $trgt_count ."'   id='existing_tracker_list_type". $trgt_count ."' value='".$prospect_list['list_type']."' />".$app_list_strings['prospect_list_type_dom'][$prospect_list['list_type']];
                $trgt_html .= "<input type='hidden' name='added_target_id". $trgt_count ."' id='added_target_id". $trgt_count ."' value='". $prospect_list['id'] ."' /></td>";
                $trgt_html .= "<td><a href='#' onclick=\"javascript:remove_existing_target('existing_trgt".$trgt_count."','".$prospect_list['id']."'); \" >  ";
                $trgt_html .= "<img src='".$image_path."delete_inline.gif' border='0' alt='rem' align='absmiddle' border='0' height='12' width='12'>". $mod_strings['LBL_REMOVE']."</a></td></tr></table></div>";

                $trgt_count++;
            }

            $trgt_html .= "<div id='no_targets'></div>";            
        } else {
            $trgt_html  .= "<div id='no_targets'><table width='100%' border='0' cellspacing='0' cellpadding='0'><tr><td class='evenListRowS1'>".$mod_strings['LBL_NONE']."</td></tr></table></div>";
        }

        $body = <<<EOQ
            <input type="hidden" id="existing_target_count" name="existing_target_count" value="" />
            <input type="hidden" id="added_target_count" name="added_target_count" value='' />
            <input type="hidden" id="wiz_list_of_existing_targets" name="wiz_list_of_existing_targets" value="" />
            <input type="hidden" id="wiz_list_of_targets" name="wiz_list_of_targets" value="" />
            <input type="hidden" id="wiz_remove_target_list" name="wiz_remove_target_list" value="" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <th colspan="5" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_TARGET_LISTS']}</h4></th>
            </tr>
            <tr>
            <td class="dataLabel" colspan="5">{$mod_strings['LBL_WIZARD_TARGET_MESSAGE1']}<br></td>
            </tr>
            <tr><td colspan=5>&nbsp;</td></tr>
            <tr>
            <td class="dataLabel" colspan="4">{$mod_strings['LBL_SELECT_TARGET']}&nbsp;
            <input id="popup_target_list_type" name="popup_target_list_type" type="hidden" value="" />
            <input id="popup_target_list_name" name="popup_target_list_name" type="hidden" value="" />
            <input id="popup_target_list_id" name='popup_target_list_id' title="List ID" type="hidden" value="" />
            <input title="{$app_strings['LBL_SELECT_BUTTON_TITLE']}" type="button" tabindex='1' class="input-button" value='{$app_strings['LBL_SELECT_BUTTON_LABEL']}' name=btn3 id='target_list_button'
            onclick='open_popup("ProspectLists", 600, 400, "", true, false,  {$target_list_data}, "single", true);' />
            </span sugar='slot'>
            </td>
            <td class="dataLabel">&nbsp;</td>
            </tr>
            <tr><td colspan=5>&nbsp;</td></tr>
            <tr>
            <td class="dataLabel" colspan="5">{$mod_strings['LBL_WIZARD_TARGET_MESSAGE2']}<br></td>
            </tr>
            <tr>
            <td width='10%' class="dataLabel">{$mod_strings['LBL_TARGET_NAME']}</td>
            <td width='20%' class="dataLabel">
                <input id="target_list_name" name="target_list_name" type='text' size='40' />
            </td>
            <td width='10%' class="dataLabel">
                <span sugar='slot28'>{$mod_strings['LBL_TARGET_TYPE']}</span sugar='slot'>
            </td>
            <td class="dataField" width='20%' >
                <span sugar='slot28b'>
                <select id="target_list_type" name="target_list_type">{$target_options}</select>
                <input id='target_list_id' name='target_list_id' title='List ID' type="hidden" value=''>
                </span sugar='slot'>
            </td>
            <td width='30%'><input type='button' value ='{$mod_strings['LBL_CREATE_TARGET']}' class='input-button' onclick="add_target('false');" /></td>
            </tr>
            <tr><td colspan=5>&nbsp;</td></tr>
            </table>
            <table width = '100%' class='tabDetailView'>
                <tr><td>&nbsp;</td></tr>
                <tr><td>
                <table bprder=1 width='100%'><tr class='tabDetailView'>
                    <td width='25%'><b>{$mod_strings['LBL_TARGET_NAME']}</b></td>
                    <td width='25%'><b>{$mod_strings['LBL_TARGET_TYPE']}</b></td><td>&nbsp;</td>
                    <td width='25%'><b>&nbsp;</b></td>
                </tr>
                </table>
                <div id='added_targets'>
                    {$trgt_html}
                </div>
                </td></tr>
            </table>

EOQ;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/target_list.js');
        $js = "var image_path = '".$image_path."'; var REMOVE = '".$mod_strings['LBL_REMOVE']."';";
        $layout->addScriptLiteral($js, LOAD_PRIORITY_FOOT);

        return $body;
    }

    /**
     * Render Email Marketing section
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @return string
     */
	function renderMarketing(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		$context['editable'] = true;
        global $mod_strings, $app_strings;

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/Campaigns/wizard.js');
        $layout->addScriptLiteral("showfirst('marketing')", LOAD_PRIORITY_FOOT);

        $campaign_id = $row_result->getField('campaign_id');

        if (! $campaign_id && isset($_REQUEST['campaign_id']))
            $campaign_id = $_REQUEST['campaign_id'];

        $camp_url = "index.php?action=WizardHome&layout=WizardNewsletter&module=Campaigns&return_module=Campaigns&return_action=WizardHome";
        $camp_url .= "&return_record=".$campaign_id."&record=".$campaign_id."&direct_step=";
        $marketing_name = $row_result->getField('name');

        $name_input = $gen->form_obj->renderField($row_result, 'name');
        $status = $gen->form_obj->renderField($row_result, 'status');
        $from_name = $gen->form_obj->renderField($row_result, 'from_name');
		$date_start = $gen->form_obj->renderField($row_result, 'date_start');

		$templateDef = array(
			'name' => 'template',
			'widget' => 'EmailTemplateEditCreate',
			'onchange' => 'hide_show_template_edit()',
		);
		$templateField = new EmailTemplateEditCreate($templateDef, new ModelDef('EmailMarketing'));
		$template = $templateField->renderHtml($gen, $row_result, $parents, $context);
        $all_prospect_lists = $gen->form_obj->renderField($row_result, 'all_prospect_lists', new FormField(array('onchange' => "toggle_message_for(this);")));

		//Mailboxes
		require_once 'modules/Campaigns/Campaign.php';
        $emails = array();
        $mailboxes = Campaign::get_campaign_mailboxes($emails);
        //add empty options.
        $emails[''] = 'nobody@example.com';
        $default_email_address = 'nobody@example.com';
        $inbound_email = $row_result->getField('inbound_email_id');
        $from_emails = "'EMPTY','','".$emails['']."'";

        foreach ($mailboxes as $id => $name) {
            $from_emails .= ',';
            $from_emails .= "'$id', '$name', '$emails[$id]'";

            if ($id == $inbound_email)
                $default_email_address = $emails[$id];
        }

        $layout->addScriptLiteral("var from_emails = new Array({$from_emails});", LOAD_PRIORITY_FOOT);

        $spec = array('name' => 'inbound_email_id', 'options' => $mailboxes, 'onchange' => "set_from_email_and_name(this);");
        $mailbox_select = $gen->form_obj->renderSelect($spec, $inbound_email);

        //Target(Prospect) Lists
        $scope_options = CampaignUtils::get_message_scope_dom($campaign_id);
        $prospectlists = array();

        if ($row_result->getField('all_prospect_lists') == 1) {
            $message_for_disabled = "disabled";
        } else {
            $message_for_disabled = "";
            //get select prospect list.
            $related_pl_ids = $this->getRelatedTargetLists();

            if (sizeof($related_pl_ids) > 0) {
                foreach($related_pl_ids as $list_id) {
                    $prospectlists[] = $list_id['id'];
                }
            }
        }
        if (empty($prospectlists)) $prospectlists = array();
        if (empty($scope_options)) $scope_options = array();
        
        $pl_options = get_select_options_with_id($scope_options, $prospectlists);

        //Final Step
        $prospectlists = $this->getRelatedTargetLists($campaign_id, new ModelDef('Campaign'));
        $pl_count = 0;
        $pl_lists = 0;

        if (sizeof($prospectlists) > 0) {

            foreach ($prospectlists as $prospect_id) {
                $pl = $this->loadTargetList($prospect_id['id']);

                if ($pl && (($pl['list_type'] == 'default') || ($pl['list_type'] == 'seed')) ) {
                    // get count of all attached target types
                    $total_entries = ProspectList::get_entry_count($pl['id'], $pl['dynamic']);
                    $pl_count += $total_entries;
                 }

                 $pl_lists++;
            }

        }

        $warning_message = '&nbsp;';
        $pl_disabled = '';

        //if count is 0, then hide inputs and and print warning message
        if ($pl_count == 0) {
            if ($pl_lists == 0) {
                //print no target list warning
                $warning_message = $mod_strings['LBL_NO_TARGETS_WARNING'];
            } else {
                $campaign = ListQuery::quick_fetch_row('Campaign', $campaign_id, array('campaign_type'));
                //print no entries warning
                if (isset($campaign['campaign_type']) && $campaign['campaign_type'] = 'NewsLetter') {
                    $warning_message = $mod_strings['LBL_NO_SUBS_ENTRIES_WARNING'];
                } else {
                   $warning_message = $mod_strings['LBL_NO_TARGET_ENTRIES_WARNING'];
                }
            }
            //disable the send email options
            $pl_disabled = 'disabled';
        }

        $body = <<<EOQ
            <input type="hidden" id="direct_step" name="direct_step" value='4' />
            <input type="hidden" id="wiz_total_steps" name="totalsteps" value="2" />
            <input type="hidden" id="wiz_current_step" name="currentstep" value='0' />
            <input type="hidden" id="wiz_back" name="wiz_back" value='none' />
            <input type="hidden" id="wiz_next" name="wiz_next" value='2' />
            <input type="hidden" name="campaign_id" value="{$campaign_id}" />
            <p><div id ='buttons' >
                <table width="100%" border="0" cellspacing="0" cellpadding="0" >
                <tr>
                <td align="left" width='30%'>
                <table border="0" cellspacing="0" cellpadding="0" ><tr>
                <tr>
                <td><div id="back_button_div2"><input id="wiz_back_button" type='hidden' title="{$app_strings['LBL_BACK']}" class="input-button" onclick="determine_back();"  name="back" value="{$app_strings['LBL_BACK']}" />&nbsp;</div></td>
                <td><div id="back_button_div"><input id="wiz_back_button" type='button' title="{$app_strings['LBL_BACK']}" class="input-button" onclick="determine_back();"  name="back" value="{$app_strings['LBL_BACK']}" />&nbsp;</div></td>
                <td><div id="cancel_button_div"><input id="wiz_cancel_button" title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}" class="input-button" onclick="this.form.action.value='WizardHome'; this.form.module.value='Campaigns'; this.form.layout.value='WizardHome'; this.form.record.value='{$campaign_id}'; this.form.submit(); " type="button" name="button" value="{$app_strings['LBL_CANCEL_BUTTON_LABEL']}" />&nbsp;</div></td>
                <td><div id="save_button_div"><input id="wiz_submit_button" title="{$app_strings['LBL_SAVE_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_SAVE_BUTTON_KEY']}" class="input-button" onclick="return SUGAR.ui.sendForm(document.forms.DetailForm, {'record_perform':'save'}, null);" type="submit" name="button" value="{$mod_strings['LBL_SAVE_EXIT_BUTTON_LABEL']}" />&nbsp;</div>
                </td>
                <td><div id="next_button_div"><input id="wiz_next_button" type='button' title="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" class="input-button" onclick="javascript:navigate('next');" name="button" value="{$app_strings['LBL_NEXT_BUTTON_LABEL']}" />&nbsp;</div></td>
               </tr>
                </table>
                </td>
                <td  align="right" width='40%'><div id='wiz_location_message'></div></td>
                </tr>
                </table>
            </div></p>
            <table class='tabDetailView2' width="100%" border="0" cellspacing="1" cellpadding="0" >
            <tr>
            <td rowspan='2' width=10% class='tabDetailViewDL2' style='vertical-align: top'>
            <div id='nav' >
                <table border="0" cellspacing="0" cellpadding="0" width="100%" >
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step3'><a  href='{$camp_url}1'>{$mod_strings['LBL_NAVIGATION_MENU_GEN1']}</a></div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step4'><a  href='{$camp_url}2'>{$mod_strings['LBL_NAVIGATION_MENU_GEN2']}</a></div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step5'><a  href='{$camp_url}3'>{$mod_strings['LBL_NAVIGATION_MENU_TRACKERS']}</a></div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step6'><a  href='{$camp_url}4'>{$mod_strings['LBL_NAVIGATION_MENU_SUBSCRIPTIONS']}</a></div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step1'>{$mod_strings['LBL_NAVIGATION_MENU_MARKETING']}</div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div  id='nav_step2'>{$mod_strings['LBL_NAVIGATION_MENU_SEND_EMAIL']}</div></td></tr>
                <tr><td class='tabDetailViewDL2' nowrap><div id='nav_step7'>{$mod_strings['LBL_NAVIGATION_MENU_SUMMARY']}</div></td></tr>
                </table>
            </div>
            </td>
            <td  rowspan='2' width='100%'>
            <div id="wiz_message"></div>
            <div id=wizard>
                <div id='step1' >
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" class='tabForm'>
                    <tr>
                    <th  colspan="4" align="left" class="dataField"><h4 class="dataLabel">{$mod_strings['LBL_WIZ_MARKETING_TITLE']} {$marketing_name}</h4></td>
                    </tr>
                    <tr><td class="dataField" colspan="4">{$mod_strings['LBL_WIZARD_MARKETING_MESSAGE']}<br></td></tr>
                    <tr><td class="dataField" colspan="4">&nbsp;</td></tr>
                    <tr>
                    <td width="15%" class="dataLabel"><slot>{$mod_strings['LBL_MRKT_NAME']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td width="35%" class="dataField"><slot>{$name_input}</slot></td>
                    <td class="dataLabel"><slot>{$mod_strings['LBL_STATUS_TEXT']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td class="dataField"><slot>{$status}</slot></td>
                    </tr>
                    <tr>
                    <td class="dataLabel"><slot>{$mod_strings['LBL_FROM_MAILBOX_NAME']}<span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td class="dataField"><slot>{$mailbox_select}&nbsp;<span id='from_email' style="font-style:italic">{$default_email_address}</span></slot></td>
                    <td class="dataLabel"><slot>{$mod_strings['LBL_FROM_NAME']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td class="datafield"><slot>{$from_name}</slot></td>
                    </tr>
                    <tr>
                    <td class="dataLabel"><slot>{$mod_strings['LBL_START_DATE_TIME']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td class="datafield"><slot>{$date_start}</slot></td>
                    <td class="dataLabel"><slot>{$mod_strings['LBL_TEMPLATE']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td class="datafield" nowrap>{$template}</td>
                    </tr>
                    <tr>
                    <td width="15%" class="dataLabel"><slot>{$mod_strings['LBL_MESSAGE_FOR']} <span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span></slot></td>
                    <td width="35%" class="datafield"><slot>{$all_prospect_lists}&nbsp;{$mod_strings['LBL_ALL_PROSPECT_LISTS']}</slot></td>
                    <td class="dataLabel"><slot>&nbsp;</slot></td>
                    <td><slot>&nbsp;</slot></td>
                    </tr>
                    <tr>
                    <td class="dataLabel"><slot>&nbsp;</slot></td>
                    <td width="35%" class="datafield"><slot><select {$message_for_disabled} tabindex='1' multiple size="5" id="message_for" name='message_for[]'>{$pl_options}</select></slot></td>
                    <td class="dataLabel"><slot>&nbsp;</slot></td>
                    <td><slot>&nbsp;</slot></td>
                    </tr>
                    <tr>
                    <td class="dataLabel"><slot>&nbsp;</slot></td>
                    <td><slot>&nbsp;</slot></td>
                    <td class="dataLabel"><slot>&nbsp;</slot></td>
                    <td><slot>&nbsp;</slot></td>
                    </tr>
                    </table>
                    </div>
                </div>
                <div id='step2'>
                    <table>
                    <td ><h3>{$mod_strings['LBL_WIZ_SENDMAIL_TITLE']} {$marketing_name}</h3></td>
                    <tr><td class="datalabel">{$mod_strings['LBL_WIZARD_SENDMAIL_MESSAGE']}<br></td></tr>
                    <tr><td class="datalabel">&nbsp;</td></tr>
                    <tr><td>
                    <input type="radio"  id="next_step" name="wiz_home_next_step" value='1'checked />{$mod_strings['LBL_SAVE_EXIT_BUTTON_LABEL']}
                    </td></tr>
                    <tr><td>
                    &nbsp;<div id='target_message'><font color='red'><b>{$warning_message}</b></font></div>
                    &nbsp;<div id='send_email1' ><input type="radio" id="next_step" name="wiz_home_next_step" value='2' {$pl_disabled} />{$mod_strings['LBL_SEND_AS_TEST']}</div>
                    </td></tr>
                    <tr><td>
                    &nbsp;<div id='send_email2'><input type="radio" id="next_step" name='wiz_home_next_step' value='3' {$pl_disabled} />{$mod_strings['LBL_SEND_EMAIL']}</div>
                    </td></tr></table>
                </div>
                </td>
                <td width='5%'></td>
            </tr>
            </table>
            </div>
EOQ;

        return $body; 
    }

    /**
     * Get Newsletters additional js
     *
     * @param string $record
     * @return string
     */
    function getNewslettersJs($record) {
        $js = <<<EOQ
            /*
            * this is the custom validation script that will call the right validation for each div
            */
            function validate_wiz_form(step){
               switch (step){
                   case 'step1':
                   if(!validate_step1()){return false;}
                   break;
                   case 'step2':
                   if(!validate_step2()){return false;}
                   break;
                   default://no additional validation needed
               }
               return true;

            }
            showfirst('newsletter');
EOQ;

        $script_to_call = '';
        if (! empty($record)) {
            $script_to_call = " link_navs(1,4);";
            if (! empty($_REQUEST['direct_step']))
                $script_to_call .= '   direct('.$_REQUEST['direct_step'].');';
        }

        $js .= $script_to_call;

        return $js;
    }

    /**
     * Get campaign's related target lists
     *
     * @param string|null $id - parent ID
     * @pram ModelDef $model
     * @return array
     */
    function getRelatedTargetLists($id = null, $model = null) {
        $ids = array();

        if (! $id)
            $id = $this->record_id;

        if (! $model)
            $model = $this->model;

        if ($id) {
            $lq = new ListQuery($model, array(), array('link_name' => 'prospectlists', 'parent_key' => $id));
            $result = $lq->fetchAll();

            if (! $result->failed)
                $ids = $result->rows;
        }

        return $ids;
    }

    /**
     * Load target list
     *
     * @param string $id
     * @return array
     */
    function loadTargetList($id) {
        $prospectlist_fields = array('name', 'list_type', 'total_entries', 'dynamic');
        $prospect_list = ListQuery::quick_fetch_row('ProspectList', $id, $prospectlist_fields);

        return $prospect_list;
    }
    
	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd = array();
        $subscription_keys = array('subscription', 'unsubscription', 'test');

        for ($i = 0; $i < sizeof($subscription_keys); $i++) {
            $key = 'wiz_step3_' .$subscription_keys[$i];

            $id_key = $key . '_list_id';
            if(! empty($input[$id_key])) {
                $upd_key = $subscription_keys[$i] . '_list_id';
                $upd[$upd_key] = $input[$id_key];
            }

            $name_key = $key . '_name';
            if(! empty($input[$name_key])) {
                $upd_key = $subscription_keys[$i] . '_name';
                $upd[$upd_key] = $input[$name_key];
            }
        }

        if (! empty($input['wiz_remove_target_list']))
            $upd['remove_target_list'] = $input['wiz_remove_target_list'];

        if (! empty($input['wiz_list_of_targets']))
            $upd['list_of_targets'] = $input['wiz_list_of_targets'];

        if (! empty($input['wiz_remove_tracker_list']))
            $upd['remove_tracker_list'] = $input['wiz_remove_tracker_list'];

        if (! empty($input['wiz_list_of_existing_trackers']))
            $upd['list_of_existing_trackers'] = $input['wiz_list_of_existing_trackers'];

        if (! empty($input['wiz_list_of_trackers']))
            $upd['list_of_trackers'] = $input['wiz_list_of_trackers'];

        if (! empty($input['message_for']))
            $upd['message_for'] = $input['message_for'];

        $update->setRelatedData($this->id.'_rows', $upd);
    }
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        $row_updates = $update->getRelatedData($this->id.'_rows');
        $campaign_type = $update->getField('campaign_type');

        if ($update->model->name == 'Campaign') {
            if (! $update->new_record) {
                $campaign_id = $update->getField('id', null, true);
            } else {
                $campaign_id = $update->saved['id'];
            }

            //----START ADD SUBSCRIPTIONS / TARGETS
            if ($campaign_type == 'NewsLetter') {
                $pl_list = $this->processSubscriptionsFromRequest($update->getField('name'), $row_updates);
                $existing_pls = $this->getRelatedTargetLists($campaign_id);

                if (sizeof($existing_pls) > 0) {
                    foreach($existing_pls as $existing_id) {

                        if (! in_array($existing_id['id'], $pl_list)) {
                            $update->removeLink('prospectlists', $existing_id['id']);
                        }

                    }
                }

                if (sizeof($pl_list) > 0)
                    $update->addUpdateLinks('prospectlists', $pl_list);

            } else {
                //process target lists if this is not a newsletter
                //remove Target Lists if defined
                if (! empty($row_updates['remove_target_list'])) {
                    $remove_target_strings = explode(",", $row_updates['remove_target_list']);

                    foreach ($remove_target_strings as $remove_id) {
                        if (! empty($remove_id))
                            $update->removeLink('prospectlists', $remove_id);
                    }
                }

                //create new campaign tracker and save if defined
                if (! empty($row_updates['list_of_targets'])) {
                    $target_strings = explode(",", $row_updates['list_of_targets']);

                    foreach ($target_strings as $target_string) {
                        $target_values = explode("@@", $target_string);

                        if (count($target_values) == 3) {
                            $link_id = null;

                            if (! empty($target_values[0])) {
                                //this is a selected target, as the id is already populated, retrieve and link
                                $target = $this->loadTargetList($target_values[0]);
                                if (! empty($target['id'])) $link_id = $target['id'];
                            } else {
                                //this is a new target, as the id is not populated, need to create and link
                                $new_id = $this->createNewSubscription($target_values[1], $target_values[2]);
                                if ($new_id) $link_id = $new_id;
                            }

                            if (! empty($link_id))
                                $update->addUpdateLink('prospectlists', $link_id);
                        }
                    }
                }
            }
            //----END ADD SUBSCRIPTIONS / TARGETS

            //----START ADD TRACKERS
            //remove campaign trackers if defined
            if (! empty($row_updates['remove_tracker_list'])) {
                $remove_tracker_strings = explode(",", $row_updates['remove_tracker_list']);
                foreach ($remove_tracker_strings as $remove_id) {
                        if (! empty($remove_id))
                            $update->removeLink('tracked_urls', $remove_id);
                }
            }

            //save  campaign trackers and save if defined
            if (! empty($row_updates['list_of_existing_trackers'])) {
                $tracker_strings = explode(",", $row_updates['list_of_existing_trackers']);

                $this->updateTracker($update, $campaign_id, $tracker_strings);
            }

            //create new campaign tracker and save if defined
            if (! empty($row_updates['list_of_trackers'])) {
                $tracker_strings = explode(",", $row_updates['list_of_trackers']);
                $this->updateTracker($update, $campaign_id, $tracker_strings, true);
            }
            //----END ADD TRACKERS
            
        } elseif ($update->model->name == 'EmailMarketing') {

            if ($update->getField('all_prospect_lists') == 1) {
                $update->removeAllLinks('prospectlists');
            } else {
                if (! empty($row_updates['message_for'])) {
                    $update->removeAllLinks('prospectlists');
                    $update->addUpdateLinks('prospectlists', $row_updates['message_for']);
                }
            }
        }
    }

    /**
     * This method will process any specified prospect lists and attach them to current campaign
     * If no prospect lists have been specified, then it will create one for you.  A total of 3 prospect lists
     * will be created for you (Subscription, Unsubscription, and test)
     *
     * @param string $campaign_name
     * @param array|null $input_update - data from $_REQUEST
     * @return array
     */
    function processSubscriptionsFromRequest($campaign_name, $input_update) {
        global $mod_strings;
        $pl_list = array();
        $subscription_types = array('subscription', 'unsubscription', 'test');

        for ($i = 0; $i < sizeof($subscription_types); $i++) {
            $type = $subscription_types[$i];
            $id_key = $type .'_list_id';
            $name_key = $type .'_name';
            
            $create_new = true;
            if(! empty($input_update[$id_key])) {
                $subscription = ListQuery::quick_fetch_row('ProspectList', $input_update[$id_key], array('id', 'name'));
 
                //check to see name matches the bean, if not, then the user has chosen to create new bean
                if(isset($subscription['name']) && $subscription['name'] == $input_update[$name_key]) {
                    $pl_list[] = $subscription['id'];
                    $create_new = false;
               }
            }

            //create new bio if one was not retrieved succesfully
            if ($create_new) {
                $name_part = $mod_strings['LBL_SUBSCRIPTION_LIST'];
                $list_type = 'default';

                if ($type == 'unsubscription') {
                    $name_part = $mod_strings['LBL_UNSUBSCRIPTION_LIST'];
                    $list_type = 'exempt';
                } elseif ($type == 'test') {
                    $name_part = $mod_strings['LBL_TEST_LIST'];
                    $list_type = 'test';
                }

                //use default name if one has not been specified
                $name = $campaign_name ." ". $name_part;

                if (! empty($input_update[$name_key]))
                    $name = $input_update[$name_key];

                //if subscription list is not specified then create and attach default one
                $new_id = $this->createNewSubscription($name, $list_type);

                if ($new_id)
                    $pl_list[] = $new_id;
            }
        }

        return $pl_list;
    }

    /**
     * Create new Prospect List
     *
     * @param string $name
     * @param string $type
     * @return array|null
     */
    function createNewSubscription($name, $type) {
        $id = null;
        $model = new ModelDef('ProspectList');
        $fields = array('id', 'name', 'list_type', 'assigned_user');

        $upd = RowUpdate::blank_for_model($model);
        $upd->limitFields($fields);

        $lq = new ListQuery($model, $fields);
        $lq->addPrimaryKey();
        $result = $lq->getBlankResult();

        if($upd->setOriginal($result)) {
            $updated = array('name' => $name, 'list_type' => $type, 'assigned_user_id' => AppConfig::current_user_id());
            $upd->set($updated);
            $upd->save();
            $id = $upd->saved['id'];
        }

        return $id;
    }

    /**
     * Update / Create Campaign Tracker and link with Campaign
     *
     * @param RowUpdate $campaign_update
     * @param string $campaign_id
     * @param array $tracker_strings
     * @param bool $new
     * @return void
     */
    function updateTracker(RowUpdate &$campaign_update, $campaign_id, $tracker_strings, $new = false) {
        $model = new ModelDef('CampaignTracker');
        $fields = array('id', 'tracker_name', 'is_optout', 'tracker_url');

        foreach ($tracker_strings as $trkr_string) {
            $tracker_values = explode("@@", $trkr_string);
            $values_num = sizeof($tracker_values);

            if ($values_num == 3 || $values_num == 4) {
                $upd = RowUpdate::for_model($model);
                $upd->limitFields($fields);

                $lq = new ListQuery($model, $fields);
                $lq->addPrimaryKey();
                $lq->addAclFilter('edit');

                if (! $new) {
                    $lq->addFilterPrimaryKey($tracker_values[0]);
                    $result = $lq->runQuerySingle();
                } else {
                    $upd->new_record = true;
                    $result = $lq->getBlankResult();
                }

                if($upd->setOriginal($result)) {
                    $start_index = 0;
                    if ($values_num == 4) $start_index = 1;
                    
                    $updated = array(
                        'tracker_name' => $tracker_values[$start_index],
                        'is_optout' => $tracker_values[$start_index + 1],
                        'tracker_url' => $tracker_values[$start_index + 2],
                        'campaign_id' => $campaign_id
                    );

                    $upd->set($updated);
                    $upd->save();

                    if ($new) {
                        $tracker_id = $upd->saved['id'];
                    } else {
                        $tracker_id = $upd->getField('id');
                    }

                    //Link Tracker with Campaign
                    $campaign_update->addUpdateLink('tracked_urls', $tracker_id);
                }
            }
        }
    }

}
?>
