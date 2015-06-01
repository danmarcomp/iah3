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

class RelatedRecordsWidget extends FormTableSection {
	var $no_sel_account;
	var $rel_data = array();

    function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'related_records';
		if(! empty($params['no_sel_account']))
			$this->no_sel_account = $params['no_sel_account'];
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();
		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}
		return '';
	}
	
	function getRequiredFields() {
		if($this->no_sel_account) return array();
		return array('primary_account');
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $mod_strings;

		$rel_lead_id = array_get_default($this->rel_data, 'rel_lead_id');
		$acct = array_get_default($this->rel_data, 'account', array());

		$newacct = $acct && empty($acct['disabled']) ? 1 : 0;
		$hideacct = $newacct ? '' : 'style="display: none;"';
        $account_name = $this->renderTextInput($gen, 'account_name', array_get_default($acct, 'name'));
        $account_name_lbl = translate('LBL_LIST_ACCOUNT_NAME', 'Accounts');
        $account_is_supplier = $this->renderCheckBox($gen, 'account_is_supplier', array_get_default($acct, 'is_supplier'));
        $account_is_supplier_lbl = translate('LBL_IS_SUPPLIER', 'Accounts');
        $account_description = $this->renderTextArea($gen, 'account_description', array_get_default($acct, 'description'));
        $account_phone = $this->renderTextInput($gen, 'account_phone_office', array_get_default($acct, 'phone_office'));
        $account_phone_lbl = translate('LBL_LIST_PHONE', 'Accounts');
        $account_site = $this->renderTextInput($gen, 'account_website', array_get_default($acct, 'website'));
        $account_site_lbl = translate('LBL_WEBSITE', 'Accounts');

        $account_note_name = $this->renderTextInput($gen, 'account_notes_name', '');
        $account_note_description = $this->renderTextArea($gen, 'account_notes_description', '');

        $opportunity_name = $this->renderTextInput($gen, 'opportunity_name', '');
        $opportunity_name_lbl = translate('LBL_OPPORTUNITY_NAME', 'Opportunities');
        $opportunity_description = $this->renderTextArea($gen, 'opportunity_description', '');
        $spec = array('name' => 'opportunity_date_closed');
        $opportunity_date = $gen->form_obj->renderDate($spec, '');
        $opportunity_date_lbl = translate('LBL_DATE_CLOSED', 'Opportunities');
        $spec = array('name' => 'opportunity_sales_stage', 'options' => 'sales_stage_dom', 'options_add_blank' => false);
        $opportunity_stage = $gen->form_obj->renderSelect($spec, 'Prospecting');
        $opportunity_stage_lbl = translate('LBL_SALES_STAGE', 'Opportunities');
        $opportunity_amount = $this->renderTextInput($gen, 'opportunity_amount', '', array('type' => 'currency', 'base_field' => 'amount_usdollar'));
        $opportunity_amount_lbl = translate('LBL_LIST_AMOUNT', 'Opportunities');

        $opportunity_note_name = $this->renderTextInput($gen, 'opportunity_notes_name', '');
        $opportunity_note_description = $this->renderTextArea($gen, 'opportunity_notes_description', '');

        $appointment_name = $this->renderTextInput($gen, 'appointment_name', '');
        $appointment_name_lbl = translate('LBL_LIST_SUBJECT', 'Calls');
        $appointment_description = $this->renderTextArea($gen, 'appointment_description', '');
        $spec = array('name' => 'appointment_date_start');
        $appointment_date = $gen->form_obj->renderDateTime($spec, '');
        $appointment_date_lbl = translate('LBL_DATE', 'Calls');

        if(! $this->no_sel_account) {
			$select_account = $gen->form_obj->renderField($row_result, 'primary_account');
			$sel_acc = <<<EOQ
				<div class="dataLabel" id="newaccountdivlink">{$mod_strings['LNK_SELECT_ACCOUNT']}&nbsp;
				{$select_account}<br><b>{$mod_strings['LNK_OR']}</b>
				</div>
EOQ;
		} else
			$sel_acc = '';

        $body = <<<EOQ
        	<input type="hidden" name="rel_lead_id" value="{$rel_lead_id}">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top: 5px" class="tabForm">
            <tbody><tr><td>
            <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tbody><tr><td class="dataLabel"><h4 class="dataLabel">{$this->getLabel()}</h4></td></tr>
EOQ;
if (!AppConfig::is_B2C()) {
        $body .= <<<EOQ
            <tr><td valign="top" align="left" border="0">

			{$sel_acc}
            <h5 class="dataLabel"><label>{$this->renderCheckBox($gen, 'newaccount', $newacct)} {$mod_strings['LNK_NEW_ACCOUNT']}</label></h5>
            <div {$hideacct} id="newaccountdiv">
            <table width="100%" cellspacing="0" cellpadding="0" border="0"><tbody>
            <tr>
            <td width="20%" nowrap="" class="dataLabel">{$account_name_lbl}<span class="requiredStar">*</span></td>
            <td width="80%" nowrap="" class="dataLabel">{$mod_strings['LBL_DESCRIPTION']}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataField">{$account_name}</td>
            <td class="dataField" rowspan="5">{$account_description}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataLabel">{$account_is_supplier_lbl}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataField">{$account_is_supplier}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataLabel">{$account_phone_lbl}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataField">{$account_phone}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataLabel">{$account_site_lbl}</td>
            </tr>
            <tr>
            <td nowrap="" class="dataField">{$account_site}</td>
            </tr>
            </tbody></table>

            <div id="accountnotelink">
            <p><a href="#" onclick="toggleDisplay('accountnote');return false;">{$mod_strings['LNK_NEW_NOTE']}</a></p>
            </div>
            <div style="display:none" id="accountnote">
            <p></p>
            <table cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
            <td class="dataLabel">{$mod_strings['LBL_NOTE_SUBJECT']}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$account_note_name}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_NOTE']}</td>
            </tr>
            <tr>
            <td class="dataField">{$account_note_description}</td>
            </tr>
            </tbody>
            </table>
			</div><br /></div></td></tr>
EOQ;
}

        $body .= <<<EOQ

            <tr><td valign="top" align="left" border="0">
            <h5 class="dataLabel"><label>{$this->renderCheckBox($gen, 'newopp')} {$mod_strings['LNK_NEW_OPPORTUNITY']}</label></h5>
            <div style="display: none;" id="newoppdiv">
            <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
            <td width="20%" class="dataLabel">{$opportunity_name_lbl}<span class="requiredStar">*</span></td>
            <td width="80%" class="dataLabel">{$mod_strings['LBL_DESCRIPTION']}</td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_name}</td>
            <td rowspan="7" class="dataField">{$opportunity_description}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$opportunity_date_lbl}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_date}</td>
            </tr><tr>
            <td class="dataLabel">{$opportunity_stage_lbl}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_stage}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$opportunity_amount_lbl}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_amount}</td>
            </tr></tbody>
            </table>

            <div id="oppnotelink">
            <a href="#" onclick="toggleDisplay('oppnote');return false;">{$mod_strings['LNK_NEW_NOTE']}</a>
            </div>
            <div style="display:none" id="oppnote">
            <p></p>
            <table cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
            <td class="dataLabel">{$mod_strings['LBL_NOTE_SUBJECT']}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_note_name}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$mod_strings['LBL_NOTE']}</td>
            </tr>
            <tr>
            <td class="dataField">{$opportunity_note_description}</td>
            </tr>
            </tbody>
            </table>
            </div><br></div></td></tr>

            <tr><td valign="top" align="left" border="0">
            <h5 class="dataLabel"><label>{$this->renderCheckBox($gen, 'newappointment')} {$mod_strings['LNK_NEW_APPOINTMENT']}</label></h5>
            <div style="display: none;" id="newappointmentdiv">
            <table width="100%" cellspacing="0" cellpadding="0" border="0">
            <tbody><tr>
            <td class="dataLabel"><input type="radio" checked="" class="radio" value="Call" name="appointment"> {$mod_strings['LNK_NEW_CALL']}</td>
            <td width="80%" class="dataLabel">{$mod_strings['LBL_DESCRIPTION']}</td>
            </tr>
            <tr><td class="dataLabel"><input type="radio" class="radio" value="Meeting" name="appointment"> {$mod_strings['LNK_NEW_MEETING']}</td>
            <td class="dataField" rowspan="8">{$appointment_description}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$appointment_name_lbl}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$appointment_name}</td>
            </tr>
            <tr>
            <td class="dataLabel">{$appointment_date_lbl}<span class="requiredStar">*</span></td>
            </tr>
            <tr>
            <td class="dataField">{$appointment_date}</td>
            </tr>
            </tbody>
            </table>
            </div></td></tr>
            </tbody></table>
            </td></tr>
            </tbody></table>
EOQ;

        return $body;
	}

    /**
     * Render text input
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @param mixed $value
     * @param array $params
     * @return string
     */
    function renderTextInput(HtmlFormGenerator &$gen, $name, $value, $params = array()) {
        $spec['name'] = $name;
        $spec = $spec + $params;

        return $gen->form_obj->renderText($spec, $value);
    }

    /**
     * Render textarea
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @param mixed $value
     * @return string
     */
    function renderTextArea(HtmlFormGenerator &$gen, $name, $value) {
        $spec['name'] = $name;
        return $gen->form_obj->renderTextArea($spec, $value);
    }

    /**
     * Render check box input control
     *
     * @param HtmlFormGenerator $gen
     * @param $name
     * @param int $value
     * @return string
     */
    function renderCheckBox(HtmlFormGenerator &$gen, $name, $value=0) {
        $spec = array('name' => $name);
        $div_id = $name . 'div';
        $spec['onchange'] = "toggleDisplay('".$div_id."');";

        return $gen->form_obj->renderCheck($spec, $value);
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_relates = array();

        $lead_id = array_get_default($input, 'lead_id');
        if($lead_id && ($lead = ListQuery::quick_fetch('Lead', $lead_id))) {
        	$upd_relates['rel_lead_id'] = $lead_id;
        	$acct = $lead->getField('account_name');
        	if($acct) {
        		$upd_relates['account'] = array(
        			'name' => $acct,
        			'phone_office' => $lead->getField('phone_work'),
        			'website' => $lead->getField('website'),
        		);
				if($update->getField('primary_account_id'))
					$upd_relates['account']['disabled'] = 1;
        	}
        } else {
        	if(! empty($input['rel_lead_id']))
				$upd_relates['rel_lead_id'] = $input['rel_lead_id'];
	
			if (! empty($input['newaccount'])) {
				$upd_relates['account'] = self::fill_data('account', $input);
				$upd_relates['account_notes'] = self::fill_notes('account', $input);
			}
	
			if (! empty($input['newopp'])) {
				$upd_relates['opportunity'] = self::fill_data('opportunity', $input);
				$upd_relates['opportunity_notes'] = self::fill_notes('opportunity', $input);
			}
	
			if (! empty($input['newappointment'])) {
				if (! empty($input['appointment'])) {
					$upd_relates['appointment'] = self::fill_data('appointment', $input);
					$upd_relates['appointment']['model'] = $input['appointment'];
				}
			}
		}

        $update->setRelatedData($this->id.'_rows', $upd_relates);
        $this->rel_data = $upd_relates;
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

        $lead_id = array_get_default($row_updates, 'rel_lead_id');
        $account_id = null;
		$partner_id = null;
		$campaign_id = null;
        $lead = null;

        if($lead_id) {
            $lead = ListQuery::quick_fetch('Lead', $lead_id);
            if ($lead) {
				$partner_id = $lead->getField('partner_id');
				$campaign_id = $lead->getField('campaign_id');
			}
        }

        if (isset($row_updates['account']) && sizeof($row_updates['account']) > 0) {
            if ($partner_id)
                $row_updates['account']['partner_id'] = $partner_id;
            $account_id = self::addAccount($update, $row_updates['account'], $row_updates['account_notes'], $lead);
        }

        if(! $account_id) {
        	$account_id = $update->getField('primary_account_id');

            if(AppConfig::is_B2C()) {
                $account_id = $update->getField('primary_contact_for');
            }

            $this->setAccountPrimaryContact($account_id, $update);
        }

        if (isset($row_updates['opportunity']) && sizeof($row_updates['opportunity']) > 0) {
            if ($partner_id)
                $row_updates['opportunity']['partner_id'] = $partner_id;
            $opp_id = self::addOpportunity($update, $row_updates['opportunity'], $row_updates['opportunity_notes'], $account_id, $lead);
        } else {
            $opp_id = null;
        }

        if($lead) {
            $this->moveLeadRelationships($update, $lead_id, $account_id);

            $lead_up = RowUpdate::for_result($lead);
            $lead_up->set('contact_id', $update->getPrimaryKeyValue());
            $lead_up->set('status', 'Converted');
            if($account_id)
                $lead_up->set('account_id', $account_id);
            if($opp_id)
                $lead_up->set('opportunity_id', $opp_id);
			$lead_up->save();
			if ($campaign_id) {
				require_once 'modules/Campaigns/utils.php';
				CampaignActivityTracker::campaign_log_lead_entry($campaign_id, $lead_id, $update->getPrimaryKeyValue(), 'Contacts', $update->getField('email1'), 'contact');
			}
        }

        if (isset($row_updates['appointment']) && sizeof($row_updates['appointment']) > 0) {
            self::addAppointment($update, $row_updates['appointment'], $account_id);
        }
	}

    function saveAfterDuplicates(RowUpdate &$update, $selected = array()) {
        $row_updates = $update->getRelatedData($this->id.'_rows');
        $account_id = null;

        $account_notes = array_get_default($row_updates, 'account_notes', array());
        $opportunity_notes = array_get_default($row_updates, 'opportunity_notes', array());

        if (isset($selected['account_id'])) {
            $account_id = $selected['account_id'];
            $acc_result = ListQuery::quick_fetch('Account', $account_id);
            $acc_upd = RowUpdate::for_result($acc_result);
            self::addAccountLink($update, $acc_upd, $account_notes);
        } elseif (isset($row_updates['account']) && sizeof($row_updates['account']) > 0) {
            $account_id = self::addAccount($update, $row_updates['account'], $account_notes);
        }

        if(! $account_id) {
            $account_id = $update->getField('primary_account_id');

            if(AppConfig::is_B2C()) {
                $account_id = $update->getField('primary_contact_for');
            }
        }

        if (isset($selected['opportunity_id'])) {
            $opp_result = ListQuery::quick_fetch('Opportunity', $selected['opportunity_id']);
            $opp_upd = RowUpdate::for_result($opp_result);
            self::addOpportunityLink($update, $opp_upd, $opportunity_notes, $account_id);
        } elseif (isset($row_updates['opportunity']) && sizeof($row_updates['opportunity']) > 0) {
            self::addOpportunity($update, $row_updates['opportunity'], $opportunity_notes, $account_id);
        }

        if (isset($row_updates['appointment']) && sizeof($row_updates['appointment']) > 0)
            self::addAppointment($update, $row_updates['appointment'], $account_id);
    }

    function moveLeadRelationships(RowUpdate $contact_update, $lead_id, $account_id) {
        $activities = $this->getRelationships('activities', $lead_id);
        $this->reassignLinks($activities, $contact_update, $account_id);
        $history = $this->getRelationships('history', $lead_id);
        $this->reassignLinks($history, $contact_update, $account_id);
    }

    function reassignLinks($links, RowUpdate $to_upd, $account_id) {
        $links_num = sizeof($links);
        $link_models = array();
        if (is_array($links) && $links_num > 0) {

            for ($i = 0; $i < $links_num; $i++) {
                $link_name = $links[$i]['query_source'];

                if ( ! isset($link_models[$link_name])) {
                    $link_def = $to_upd->model->getLinkDefinition($link_name);
                    $link_models[$link_name] = $link_def['bean_name'];
                }

                $link_model_name = $link_models[$link_name];
                $link_model_result = ListQuery::quick_fetch($link_model_name, $links[$i]['id']);

                if (! $link_model_result->failed) {
                    $link_upd = RowUpdate::for_result($link_model_result);
                    $need_save = false;

                    if ($account_id) {
                        $link_upd->set(array('parent_type' => 'Accounts', 'parent_id' => $account_id));
                        $need_save = true;
                    }

                    if ($link_name == 'tasks' || $link_name == 'notes') {
                        $link_upd->set('contact_id', $to_upd->getPrimaryKeyValue());
                        $need_save = true;
                    } else {
                        $link_upd->addUpdateLink('contacts', $to_upd->getPrimaryKeyValue());
                    }

                    if ($need_save)
                        $link_upd->save();
                }
            }

        }
    }

    /**
     * Get relationships data from parent record
     *
     * @param string $rel_name - link name
     * @param string $parent_id
     * @return array
     */
    function getRelationships($rel_name, $parent_id) {
        $query = new ListQuery('Lead', null, array('link_name' => $rel_name));
        $query->setParentKey($parent_id);
        $query->setOrderBy('id');
        $result = $query->fetchAll();
        $relationships = array();

        if (! $result->failed && sizeof($result->rows) > 0) {
            foreach ($result->rows as $rel) {
                array_push($relationships, $rel);
            }
        }

        return $relationships;
    }

    function setAccountPrimaryContact($account_id, RowUpdate &$contact_upd) {
        if ($account_id) {
            $acc_result = ListQuery::quick_fetch('Account', $account_id);
            if ($acc_result) {
                $acc_upd = RowUpdate::for_result($acc_result);
                $acc_upd->set('primary_contact_id', $contact_upd->getPrimaryKeyValue());
                $acc_upd->save();
            }
        }
    }

    /**
     * Add related account
     *
     * @static
     * @param RowUpdate $contact_upd
     * @param array $data
     * @param array $note_data
     * @param RowResult|null $lead
     * @return array|mixed|null
     */
    static function addAccount(RowUpdate &$contact_upd, $data, $note_data, $lead = null) {
        global $current_user;
        $acc_upd = RowUpdate::blank_for_model('Account');

        $addr_fields = array('address_street', 'address_city',
            'address_state', 'address_postalcode', 'address_country');

        for ($i = 0; $i < sizeof($addr_fields); $i++) {
            $contact_field = 'primary_' . $addr_fields[$i];
            $billing =  'billing_' . $addr_fields[$i];
            $shipping =  'shipping_' . $addr_fields[$i];
            
            $data[$billing] = $contact_upd->getField($contact_field);
            $data[$shipping] = $contact_upd->getField($contact_field);
        }

        $data['primary_contact_id'] = $contact_upd->getPrimaryKeyValue();
        $data['assigned_user_id'] = AppConfig::current_user_id();
        $data['currency_id'] = $current_user->getPreference('currency');
        
		$skip = array(
			'id', 'id_c', 'portal_name',
			'date_entered', 'date_modified', 'deleted',
			'modified_user_id', 'assigned_user_id', 'created_by',
			'name', 'temperature', 'phone_fax', 'email1', 'email2',
			'email_opt_out', 'website', 'description', 'invalid_email',
			'partner_id', 'last_activity_date',
		);
		if($lead) {
			$fs_extend = array_intersect($lead->getFieldNames(), $acc_upd->getFieldNames());
			// copy any additional matching fields
			foreach($fs_extend as $k) {
				if(in_array($k, $skip) || isset($data[$k]) || strpos($k, '_address_') !== false) continue;
				$data[$k] = $lead->getField($k);
			}
		}
        
        $acc_upd->set($data);

        if ($acc_upd->save()) {
            self::addAccountLink($contact_upd, $acc_upd, $note_data);
            return $acc_upd->getPrimaryKeyValue();
        }

        return null;
    }

    /**
     * Link contact with account and create notes
     *
     * @param RowUpdate $contact_upd
     * @param RowUpdate $account_upd
     * @param array $note_data
     */
    function addAccountLink(RowUpdate &$contact_upd, RowUpdate &$account_upd, $note_data) {
        $contact_upd->set(array('primary_account_id' => $account_upd->getPrimaryKeyValue()));
        $contact_upd->save();

        if (is_array($note_data) && sizeof($note_data) > 0) {
            $note_id = self::addNote('Accounts', $account_upd->getPrimaryKeyValue(), $note_data);

            if ($note_id)
                $account_upd->addUpdateLink('notes', $note_id);
        }
    }

    /**
     * Add related opportunity
     *
     * @static
     * @param RowUpdate $contact_upd
     * @param array $data
     * @param array $note_data
     * @param string $account_id
     * @param RowResult|null $lead
     * @return array|mixed|null
     */
    static function addOpportunity(RowUpdate &$contact_upd, $data, $note_data, $account_id, $lead = null) {
        global $current_user, $timedate;
        $opp_upd = RowUpdate::blank_for_model('Opportunity');

        if (! $account_id)
        	return false;
        
		$data['account_id'] = $account_id;
        $data['lead_source'] = $contact_upd->getField('lead_source');
        $data['assigned_user_id'] = AppConfig::current_user_id();
        $data['currency_id'] = $current_user->getPreference('currency');
        $data['amount'] = unformat_number($data['amount']);
        $data['date_closed'] = $timedate->to_db_date($data['date_closed'], false);

        if (isset($data['sales_stage'])) {
            global $app_list_strings;
            $sales_stage = $data['sales_stage'];
            $forecast_cats = $app_list_strings['sales_forecast_dom'];
            $probabilities = $app_list_strings['sales_probability_dom'];

            $probability = 0;
            if (isset($probabilities[$sales_stage]))
                $probability = $probabilities[$sales_stage];

            $data['probability'] = $probability;

            if (isset($forecast_cats[$sales_stage]))
                $data['forecast_category'] = $forecast_cats[$sales_stage];

        }
        
		$skip = array(
			'id', 'id_c',
			'date_entered', 'date_modified', 'deleted',
			'modified_user_id', 'assigned_user_id', 'created_by',
			'lead_source', 'description',
			'account_id', 'campaign_id', 'partner_id',
		);
		if($lead) {
			$fs_extend = array_intersect($lead->getFieldNames(), $opp_upd->getFieldNames());
			// copy any additional matching fields
			foreach($fs_extend as $k) {
				if(in_array($k, $skip) || isset($data[$k]) || strpos($k, '_address_') !== false) continue;
				$data[$k] = $lead->getField($k);
			}
		}

        $opp_upd->set($data);

		if (! $opp_upd->validate()) {
			$GLOBALS['log']->error('opp validate failed');
			$GLOBALS['log']->error($opp_upd->errors);
			return null;
		}
        if ($opp_upd->save()) {
            self::addOpportunityLink($contact_upd, $opp_upd, $note_data, $account_id);
            return $opp_upd->getPrimaryKeyValue();
        }

        return null;
    }

    /**
     * Link contact with opportunity and create notes
     *
     * @static
     * @param RowUpdate $contact_upd
     * @param RowUpdate $opportunity_upd
     * @param array $note_data
     * @param string $account_id
     */
    static function addOpportunityLink(RowUpdate &$contact_upd, RowUpdate &$opportunity_upd, $note_data, $account_id) {
        if ($account_id)
            $opportunity_upd->addUpdateLink('accounts', $account_id);

        $opportunity_upd->addUpdateLink('contacts', $contact_upd->getPrimaryKeyValue());

        if (is_array($note_data) && sizeof($note_data) > 0) {
            $note_id = self::addNote('Opportunities', $opportunity_upd->getPrimaryKeyValue(), $note_data);

            if ($note_id)
                $opportunity_upd->addUpdateLink('notes', $note_id);
        }
    }

    /**
     * Add related appointment (Call or Meeting)
     *
     * @static
     * @param RowUpdate $contact_upd
     * @param array $data
     * @param string $account_id
     * @return array|mixed|null
     */
    static function addAppointment(RowUpdate &$contact_upd, $data, $account_id) {
    	global $timedate;
        $app_upd = RowUpdate::for_model($data['model']);
        $app_upd->loadBlankRecord();

        $data['direction'] = 'Outbound';
        $data['status'] = 'Planned';
        $data['duration'] = 60;
        $data['assigned_user_id'] = AppConfig::current_user_id();
        $data['date_start'] = $timedate->to_db($data['date_start']);

        if ($account_id) {
            $data['parent_type'] = 'Accounts';
            $data['parent_id'] = $account_id;
        } else {
            $data['parent_type'] = 'Contacts';
            $data['parent_id'] = $contact_upd->getPrimaryKeyValue();
        }

        $app_upd->set($data);

		if (! $app_upd->validate()) {
			$GLOBALS['log']->error('appointment validate failed');
			$GLOBALS['log']->error($app_upd->errors);
			return null;
		}
        if ($app_upd->save()) {
        	$app_upd->addUpdateLink('users', array('id' => $data['assigned_user_id'], 'accept_status' => 'accept'));
            $app_upd->addUpdateLink('contacts', $contact_upd->getPrimaryKeyValue());
            
            return $app_upd->getPrimaryKeyValue();
        }

        return null;
    }

    /**
     * Add note
     *
     * @static
     * @param string $parent_type
     * @param string $parent_id
     * @param array $data
     * @return array|mixed|null
     */
    static function addNote($parent_type, $parent_id, $data) {
        $note_upd = RowUpdate::for_model('Note');
        $note_upd->loadBlankRecord();

        $data['parent_type'] = $parent_type;
        $data['parent_id'] = $parent_id;
        $data['assigned_user_id'] = AppConfig::current_user_id();

        $note_upd->set($data);

        if ($note_upd->save())
            return $note_upd->getPrimaryKeyValue();

        return null;
    }

    /**
     * Fill related records data from input
     *
     * @static
     * @param string $type - type of related records
     * (account, opportunity, appointment)
     * @param array $input
     * @return array
     */
    static function fill_data($type, $input) {
        $data = array();
        $fields = array(
            'account' => array('name', 'phone_office', 'website', 'description', 'is_supplier'),
            'opportunity' => array('name', 'date_closed', 'sales_stage', 'amount', 'description'),
            'appointment' => array('name', 'date_start', 'description')
        );

        if (! empty($input[$type . '_name'])) {
            for ($i = 0; $i < sizeof($fields[$type]); $i++) {
                $field_name = $fields[$type][$i];
                if (isset($input[$type .'_'. $field_name]))
                    $data[$field_name] = $input[$type .'_'. $field_name];
            }
        }

        return $data;
    }

    /**
     * Fill related record'd notes data
     *
     * @static
     * @param string $type - type of related records
     * (account, opportunity, appointment)
     * @param array $input
     * @return array
     */
    static function fill_notes($type, $input) {
        $notes = array();

        if (! empty($input[$type . '_notes_name'])) {
            $notes['name'] = $input[$type . '_notes_name'];
            if (isset($input[$type . '_notes_description']))
                $notes['description'] = $input[$type . '_notes_description'];
        }

        return $notes;
    }
}
?>
