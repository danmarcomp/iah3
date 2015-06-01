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
require_once 'modules/Quotes/products_dialog/ProductsList.php';

class SupportedProductsDialog {

    /**
     * Dialog initiator model name
     *
     * @var string
     */
    protected $initiator_model;

    /**
     * Dialog initiator ID
     *
     * @var string
     */
    protected $initiator_id;

    /**
     * @var string
     */
    protected $list_id;

    /**
     *
     * @param string $initiator_model
     * @param string $initiator_id
     */
    public function __construct($initiator_model, $initiator_id) {
        $this->initiator_model = $initiator_model;
        $this->initiator_id = $initiator_id;
    }

    /**
     * Render Dialog view
     *
     * @return void
     */
    public function render() {
        $this->renderProductsList();
        $this->renderAdditionalOptions();
    }

    /**
     * Render Products list by initiator
     *
     * @return void
     */
    protected function renderProductsList() {
        $products_list = new ProductsList($this->initiator_model, $this->initiator_id);
        $this->list_id = $products_list->getManager()->list_id;
        $products_list->render();
    }

    /**
     * Render additional options like Create Contract and Create Project
     *
     * @return void
     */
    protected function renderAdditionalOptions() {
        $initiator = ListQuery::quick_fetch_row($this->initiator_model, $this->initiator_id, array('billing_account_id'));

        if (! empty($initiator['billing_account_id'])) {
            $account_id = $initiator['billing_account_id'];

            $contrTypeOptions = $this->buildContractTypeOptions();
            $contrOptions = $this->buildObjectOptions('SubContract', $account_id);
            $use_existing = false;
            if (sizeof($contrOptions['keys']) > 0 )
                $use_existing = true;
            $actOptions = $this->buildActionOptions('SubContract', $use_existing);

            $projectOptions = $this->buildObjectOptions('Project', $account_id);
            $use_existing = false;
            if (sizeof($projectOptions['keys']) > 0 )
                $use_existing = true;
            $projectActOptions = $this->buildActionOptions('Project', $use_existing);

            $title = translate('LBL_POPUP_TITLE2');
            $createContractLbl = translate('LBL_CREATE_CONTRACT');
            $contractTypeLbl = translate('LBL_CONTRACT_TYPE');
            $nameLbl = translate('LBL_CONTRACT_NAME');
            $createProjectLbl = translate('LBL_CREATE_PROJECT');
            $proceedButLbl = translate('LBL_PROCEED');
            $cancelButLbl = translate('LBL_CANCEL_BUTTON_LABEL');

            $html = <<<EOQ
                <form id="SupportedProductsForm" action="#">
                <br /><h2>{$title}</h2><br />
                <table border="0" cellpadding="5">
                <tr>
                    <td>
                    <input type="hidden" id="contract_action" name="contract_action" value="create_new" />
                    <button type="button" class="input-select input-outer " id="contract_action-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract_action-input-label" class="input-label">{$createContractLbl}</span></div></button>
                    </td>
                    <td id="contract-cell" style="display: none;">
                    <input type="hidden" name="contract" id="contract" value="{$contrOptions['default_id']}" />
                    <button type="button" class="input-select input-outer " id="contract-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract-input-label" class="input-label">{$contrOptions['default_name']}</span></div></button>
                    </td>
                    <td id="contract_type_label-cell">{$contractTypeLbl}</td>
                    <td id="contract_type-cell">
                    <input type="hidden" name="contract_type" id="contract_type" value="{$contrTypeOptions['default_id']}" />
                    <button type="button" class="input-select input-outer " id="contract_type-input"><div class="input-arrow select-label" style="width: 30em"><span id="contract_type-input-label" class="input-label">{$contrTypeOptions['default_name']}</span></div></button>
                    </td>
                    <td id="contract_name_label-cell">{$nameLbl}</td>
                    <td id="contract_name-cell">
                    <input type="text" size="35" maxlength="150" class="input-text input-outer" value="" name="contract_name" id="contract_name">
                    </td>
                </tr>
                </table>
                <table border="0" cellpadding="5">
                <tr>
                    <td>
                    <input type="hidden" id="project_action" name="project_action" value="create_new" />
                    <button type="button" class="input-select input-outer " id="project_action-input"><div class="input-arrow select-label" style="width: 30em"><span id="project_action-input-label" class="input-label">{$createProjectLbl}</span></div></button>
                    </td>
                    <td id="project-cell" style="display: none;">
                    <input type="hidden" name="project" id="project" value="{$projectOptions['default_id']}" />
                    <button type="button" class="input-select input-outer " id="project-input"><div class="input-arrow select-label" style="width: 30em"><span id="project-input-label" class="input-label">{$projectOptions['default_name']}</span></div></button>
                    </td>
                    <td id="project_name_label-cell">{$nameLbl}</td>
                    <td id="project_name-cell">
                    <input type="text" size="35" maxlength="150" class="input-text input-outer" value="" name="project_name" id="project_name">
                    </td>
                </tr>
                <tr>
                    <td>
                    <input type="button" class="input-button" value="{$proceedButLbl}" onclick="return create_contracts('{$this->list_id}', '{$this->initiator_model}', '{$this->initiator_id}', '{$account_id}');" />
                    <input type="button" class="input-button" value="{$cancelButLbl}" onclick="return SUGAR.ui.PopupManager.close();" />
                    </td>
                </tr>
                </table>
                </form>
EOQ;

            echo $html;

            $json = getJSONobj();
            $actOptions = $json->encode($actOptions);
            $contrOptions = $json->encode($contrOptions);
            $contrTypeOptions = $json->encode($contrTypeOptions);
            $projectActOptions = $json->encode($projectActOptions);
            $projectOptions = $json->encode($projectOptions);

            $ctls = array();
            //Contract Controls
            $ctls[] = "new SUGAR.ui.SelectInput('contract_action-input', {name: 'contract_action', options: $actOptions, onchange: function(k) { contract_action_change(this);}})";
            $ctls[] = "new SUGAR.ui.SelectInput('contract-input', {name: 'contract', options: $contrOptions, onchange: function(k) { }})";
            $ctls[] = "new SUGAR.ui.SelectInput('contract_type-input', {name: 'contract_type', options: $contrTypeOptions, onchange: function(k) { }})";
            $ctls[] = "new SUGAR.ui.TextInput('contract_name-input', {name: 'contract_name', onchange: function(k) { }})";
            //Project Controls
            $ctls[] = "new SUGAR.ui.SelectInput('project_action-input', {name: 'project_action', options: $projectActOptions, onchange: function(k) { contract_action_change(this);}})";
            $ctls[] = "new SUGAR.ui.SelectInput('project-input', {name: 'project', options: $projectOptions, onchange: function(k) { }})";
            $ctls[] = "new SUGAR.ui.TextInput('project_name-input', {name: 'project_name', onchange: function(k) { }})";

            global $pageInstance;
            $pageInstance->add_js_literal('SUGAR.ui.initForm("SupportedProductsForm", ['.implode(', ', $ctls).']);', null, LOAD_PRIORITY_END);
            $pageInstance->add_js_include('modules/Quotes/products_dialog/products_dialog.js', null, LOAD_PRIORITY_END);
        }
    }

    /**
     * Build Contract Type options
     *
     * @return array:
     * 1. keys
     * 2. values
     * 3. default_id
     * 4. default_name
     */
    protected function buildContractTypeOptions() {
        $result = ListQuery::quick_fetch_all('ContractType');
        $contrTypeOptions = array('keys' => array(), 'values' => array(),
            'width' => '50em', 'default_id' => '', 'default_name' => '');
        $setDefault = false;

        foreach ($result->getRows() as $row) {
        	$contrTypeOptions['keys'][] = $row['id'];
        	$contrTypeOptions['values'][] = $row['name'];

            if (! $setDefault) {
                $contrTypeOptions['default_id'] = $row['id'];
                $contrTypeOptions['default_name'] = $row['name'];
                $setDefault = true;
        	}
        }

        return $contrTypeOptions;
    }

    /**
     * Build options list by object
     *
     * @param string $object - 'SubContract' or 'Project'
     * @param $account_id
     * @return array:
     * 1. keys
     * 2. values
     * 3. default_id
     * 4. default_name
     */
    protected function buildObjectOptions($object, $account_id) {
        $contrOptions = array('keys' => array(), 'values' => array(),
            'width' => '50em', 'default_id' => '', 'default_name' => '');

        $lq = new ListQuery($object);
        if ($object == 'SubContract') {
            $lq->addField('main_contract');
            $lq->addField('name');
            $lq->where = "main_contract.account_id = '{$account_id}'";
        } else {
            $lq->addSimpleFilter('account_id', $account_id);
        }
        $result = $lq->runQuery();
        $setDefault = false;

        if ($result->getResultCount()) {
        	foreach ($result->getRows() as $row) {
        		$contrOptions['keys'][] = $row['id'];
        		$contrOptions['values'][] = $row['name'];

        		if (! $setDefault) {
                    $contrOptions['default_id'] = $row['id'];
                    $contrOptions['default_name'] = $row['name'];
                    $setDefault = true;
        		}

        	}
        }

        return $contrOptions;
    }

    /**
     * Build Actions on object options
     *
     * @param string $object: 'SubContract' or 'Project'
     * @param bool $use_existing - add use existing option or not
     * @return array:
     * 1. keys
     * 2. values
     */
    protected function buildActionOptions($object = 'Contract', $use_existing = false) {
        $actOptions = array('keys' => array(), 'values' => array(), 'width' => '30em');
        $new_lbl = $existing_lbl = $not_link_lbl = '';

        if ($object == 'SubContract') {
            $new_lbl = 'LBL_CREATE_CONTRACT';
            $existing_lbl = 'LBL_SELECT_CONTRACT';
            $not_link_lbl = 'LBL_NO_CONTRACT';
        } elseif ($object == 'Project') {
            $new_lbl = 'LBL_CREATE_PROJECT';
            $existing_lbl = 'LBL_SELECT_PROJECT';
            $not_link_lbl = 'LBL_NO_PROJECT';
        }

        if ($object != '' && ($new_lbl != '' && $existing_lbl != '' && $not_link_lbl != '') ) {
            $actOptions['keys'][] = 'create_new';
            $actOptions['values'][] = translate($new_lbl);

            if ($use_existing) {
                $actOptions['keys'][] = 'use_existing';
                $actOptions['values'][] = translate($existing_lbl);
            }

            $actOptions['keys'][] = 'not_link';
            $actOptions['values'][] = translate($not_link_lbl);
        }

        return $actOptions;
    }
}
?>