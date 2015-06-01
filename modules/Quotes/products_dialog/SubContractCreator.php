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
require_once 'include/database/ListQuery.php';
require_once 'include/database/RowUpdate.php';

class SubContractCreator {

    const ACTION_NEW = 'create_new';

    const ACTION_EXISTING = 'use_existing';

    const ACTION_NOT_CREATE = 'not_link';

    const CONTRACT_STATUS = 'Active';

    /**
     * @var string:
     * 1. create_new
     * 2. use_existing
     * 3. not_link
     */
    private $action;

    /**
     * New Contract type
     *
     * @var string
     */
    private $type;

    /**
     * New Contract name
     *
     * @var string
     */
    private $name;

    /**
     * Existing Contract ID
     *
     * @var string
     */
    private $existing_id;

    /**
     * Related account ID (main contract Account)
     *
     * @var string
     */
    private $account_id;

    public function __construct() {
        $this->loadRequest();
    }

    /**
     * Do action on SubContract
     *
     * @return null|RowUpdate
     */
    public function doAction() {
        switch ($this->action) {
            case self::ACTION_NEW:
                return $this->createNew();
                break;
            case self::ACTION_EXISTING:
                return $this->loadExisting();
                break;
            case self::ACTION_NOT_CREATE:
            default:
                return null;
                break;
        }
    }

    /**
     * Load data from request
     *
     * @return void
     */
    private function loadRequest() {
        $fields = array(
            'contract_action' => 'action',
            'contract_type' => 'type',
            'contract_name' => 'name',
            'contract_id' => 'existing_id',
            'account_id' => 'account_id',
        );

        $input = $_REQUEST;

        foreach ($fields as $input_param => $field) {
            $this->$field = array_get_default($input, $input_param, '');
        }
     }

    /**
     * Create new SubContract
     *
     * @return null|RowUpdate
     */
    private function createNew() {
        $mainId = $this->getMainId();
        $subcontract = null;

        if ($mainId) {
            $new_subcontract = RowUpdate::blank_for_model('SubContract');
            $new_subcontract->set('main_contract_id', $mainId);
            $new_subcontract->set('name',  $this->name);
            $new_subcontract->set('status', self::CONTRACT_STATUS);
            $new_subcontract->set('contract_type_id', $this->type);
            if ($new_subcontract->save())
                $subcontract = $new_subcontract;
        }

        return $subcontract;
    }

    /**
     * Get Main Contract ID
     *
     * @return null|string
     */
    private function getMainId() {
        $lq = new ListQuery('Contract');
        $lq->addSimpleFilter('account_id', $this->account_id);
        $result = $lq->runQuery(0, 1);
        $id = null;

        if ($result->getResultCount()) {
            $idx = $result->getRowIndexes();
            $row = $result->getRowResult($idx[0]);
            $id = $row->getField('id');
        } else {
            $main = $this->createMain();
            $id = $main->getPrimaryKeyValue();
        }

        return $id;
    }

    /**
     * Create new Main Contract
     *
     * @return null|RowUpdate
     */
    private function createMain() {
        require_once('modules/Service/Contract.php');
        $contract = RowUpdate::blank_for_model('Contract');
        $contract->set('account_id', $this->account_id);
        $contract->set('status', self::CONTRACT_STATUS);
        Contract::set_number($contract);

        if ($contract->save()) {
            return $contract;
        } else {
            return null;
        }
    }

    /**
     * Load existing SubContract
     *
     * @return null|RowUpdate
     */
    private function loadExisting() {
        $row = ListQuery::quick_fetch('SubContract', $this->existing_id);
        if (! $row->failed) {
            return new RowUpdate($row);
        } else {
            return null;
        }
    }
}
?>