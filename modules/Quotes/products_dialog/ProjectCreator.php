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
require_once('modules/Project/Project.php');

class ProjectCreator {

    const ACTION_NEW = 'create_new';

    const ACTION_EXISTING = 'use_existing';

    const ACTION_NOT_CREATE = 'not_link';

    const PROJECT_PHASE = 'Active - In Progress';

    /**
     * @var string:
     * 1. create_new
     * 2. use_existing
     * 3. not_link
     */
    private $action;

    /**
     * New Project name
     *
     * @var string
     */
    private $name;

    /**
     * Existing Project ID
     *
     * @var string
     */
    private $existing_id;

    /**
     * Related account ID
     *
     * @var string
     */
    private $account_id;

    /**
     * Parent object type
     *
     * @var string
     */
    private $parent_type;

    /**
     * Parent object ID
     *
     * @var string
     */
    private $parent_id;

    public function __construct() {
        $this->loadRequest();
    }

    /**
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
            'project_action' => 'action',
            'project_name' => 'name',
            'project_id' => 'existing_id',
            'account_id' => 'account_id',
            'parent_type' => 'parent_type',
            'parent_id' => 'parent_id',
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
        $project = null;

        $new_project = RowUpdate::blank_for_model('Project');
        $new_project->set('name', $this->name);
        $new_project->set('project_phase', self::PROJECT_PHASE);
        $new_project->set('assigned_user_id', AppConfig::current_user_id());
        $new_project->set('amount', 0);
        $new_project->set('amount_usdollar', 0);
        Project::init_record($new_project, array('account_id' => $this->account_id));

        if ($new_project->save()) {
            if ($this->parent_type == 'Invoice')
                $new_project->addUpdateLink('invoices', $this->parent_id);

            $project = $new_project;
        }

        return $project;
    }

    /**
     * Load existing SubContract
     *
     * @return null|RowUpdate
     */
    private function loadExisting() {
        $row = ListQuery::quick_fetch('Project', $this->existing_id);
        if (! $row->failed) {
            return new RowUpdate($row);
        } else {
            return null;
        }
    }
}
?>