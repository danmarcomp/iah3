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
class ImportDBBadCases {
	/**
	 * @var ImportDBBadCases
	 */
	private static $instance = null;

	private $cases = array();

	private function __construct() {
		$class = new ReflectionClass($this);
		$methods = $class->getMethods(ReflectionMethod::IS_PRIVATE);
		foreach ($methods as $method) {
			if (strpos($method->name, 'bad') === 0) {
				$this->cases[] = $method->name;
			}
		}
		$this->cases[] = 'checkRequired';
        $this->cases[] = 'checkDuplicates';
	}

	public static function isBadCase(array $args) {
		if (is_null(self::$instance)) {
			self::$instance = new ImportDBBadCases();
		}
		return self::$instance->verifyCases($args);
	}

	private function verifyCases(array $args) {
		$messages = array();
		foreach ($this->cases as $case) {
			$result = call_user_func(array($this, $case), $args);
			if ($result) {
				$messages[] = $result;
			}
		}
		return $messages;
	}

	private function badAssignedUser(array $args) {
		if (!empty($args['row']) && isset($args['row']->fields['assigned_user_id'])) {
			$user_id = $args['row']->getField('assigned_user_id');
			if(empty($user_id))
				return translate('MSG_EMPTY_ASSIGNED_USER', 'ImportDB');
		}
	}
	private function badUser(array $args) {
		if (!empty($args['row']) && $args['row']->model_name == 'User') {
			$user_name = $args['row']->getField('user_name');
			if (empty($user_name))
				return translate('MSG_EMPTY_USER_NAME', 'ImportDB');
		}
		return false;
	}

	private function checkRequired(array $args)
	{
		static $fields_to_skip = array(
				'modified_user_id', 'modified_user', 'assigned_user', 'portal_active', 'email_opt_out', 'invalid_email',
				'recurrence_index', 'email_attachment', 'id_c', 'date_entered', 'date_modified', 'id',
		);
		$missing = array();
		if (!empty($args['row'])) {
			$fields = $args['row']->model->getRequiredFields();
			foreach ($fields as $f) {
				if (in_array($f, $fields_to_skip)) continue;
				$def = $args['row']->model->getFieldDefinition($f);
				if ($def['type'] == 'ref') continue;
				if ($def['type'] == 'base_currency') continue;
				if (! empty($def['auto_increment'])) continue;
				if (!isset($def['default']) && is_null($args['row']->getField($f))) {
					$missing[] = translate($def['vname'], $args['row']->model->getModuleDir());
				}
			}
		}
		if (!empty($missing)) {
			$tpl = translate('MSG_MISSING_REQUIRED', 'ImportDB');
			return sprintf($tpl, join(', ', $missing));
		}
	}

    private function checkDuplicates(array $args) {

		$check = array_get_default($_SESSION['__ImportDB'], 'DUP_CHECK');
		if (empty($check)) {
			$args['row']->check_duplicates = false;
			return null;
		}
		$duplicates = $args['row']->checkDuplicates(false, $check);
        if (! empty($duplicates))
            return translate('MSG_FOUND_DUPLICATE', 'ImportDB');
    }
}
