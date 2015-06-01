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

require_once 'include/layout/forms/FormField.php';
require_once 'include/layout/forms/FilterElement.php';

class DocumentUsersFilter extends FormField implements FilterElement {

    const DEFAULT_VALUE = 0;

    public function getFilterClause($filter) {
        $filter_value = array_get_default($filter, 'only_generic_docs', self::DEFAULT_VALUE);
        $query = '(1 = 1)';

        if ($filter_value == 1)
            $query ="NOT EXISTS (SELECT employees.id FROM document_relations jt0 LEFT JOIN employees ON employees.id=jt0.relation_id AND NOT employees.deleted WHERE NOT employees.deleted AND jt0.document_id={$this->model->table_name}.id AND relation_type='Employees' AND NOT jt0.deleted)";

        return $query;
    }
	
	public function loadFilter(&$filter, $input, $prefix) {
       $filter['only_generic_docs'] = array_get_default($input, 'only_generic_docs');
    }

	public function render($form, $result) {
        $label = translate('LBL_SHOW_ONLY_GENERIC_DOCS', $this->model->module_dir);
        $spec = array('name' => 'only_generic_docs', 'auto_submit' => true, 'show_label' => true, 'label' => $label);
        $value = array_get_default($form->filter, 'only_generic_docs', self::DEFAULT_VALUE);
        return $form->renderCheck($spec, $value);
    }
}
