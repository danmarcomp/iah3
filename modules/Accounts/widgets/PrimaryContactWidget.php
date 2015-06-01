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
require_once('modules/EventSessions/EventSession.php');

class PrimaryContactWidget extends FormTableSection {

	function init($params, $model=null) {
		$cmodel = new ModelDef('Contact');
		parent::init($params, $cmodel);
		if(! $this->id)
			$this->id = 'primary_contact_info';
		if(! $this->vname) {
			$this->vname = 'LBL_PRIMARY_CONTACT';
			$this->vname_module = 'Accounts';
		}
		$layout = empty($params['edit']) ? 'view' : 'edit';
		$sections = AppConfig::setting("views.layout.Contacts.$layout.Standard.sections", array());
		if($sections) {
			foreach($sections as $sect) {
				if(is_array($sect) && array_get_default($sect, 'id') == 'main')
					$this->addElements($sect['elements']);
			}
		}
	}
	
	function translate($module) {
		parent::translate('Contacts');
	}
	
	function getVisible() {
		$visib = parent::getVisible();
		if(! AppConfig::is_B2C())
			$visib = false;
		return $visib;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if ($gen->layout->getEditingLayout()) {
			$params = array();
			$params['body'] =  '<tr><td>' . translate('LBL_CONTACT_INFO_DESCRIPTION', 'Accounts')  . '</td></tr>';
			return $this->renderOuterTable($gen, $parents, $context, $params);
		}

		$lq = new ListQuery('Contact', true);
		$lq->addFields(parent::getRequiredFields());
		$cid = $row_result->getField('primary_contact_id');
		if($cid)
			$contact = $lq->queryRecord($cid);
		if(empty($contact) || $contact->failed) {
			$contact = $lq->getBlankResult();
			$aid = $row_result->getField('assigned_user_id');
			if(! $aid) $aid = AppConfig::current_user_id();
			$contact->assign('assigned_user_id', $aid);
			$contact->assign('last_name', $row_result->getField('name'));
			$lq->populateSecondary($contact);
		}
		$layout = $gen->getFormObject()->style == 'editview' ? 'edit' : 'view';
		$fmt = new FieldFormatter($layout == 'edit' ? 'plain' : 'html', 'view');
		$fmt->primary_key = $contact->primary_key;
		$fmt->module_dirs = $contact->module_dirs;
		if ($contact && $contact->row) {
			$contact->formatted = $fmt->formatRow($contact->fields, $contact->row);
		}
		$prefix = $gen->getFormObject()->getFieldNamePrefix();
		$gen->getFormObject()->setFieldNamePrefix('__primary_contact__');
		$ret = parent::renderHtml($gen, $contact, $parents, $context);
		$gen->getFormObject()->setFieldNamePrefix($prefix);
		return $ret;
    }
	
	function getRequiredFields() {
		return array('primary_contact_id');
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {
		if (!AppConfig::is_B2C())
			return;
		$id = $update->getPrimaryKeyValue();
		$data = array();
		foreach ($input as $k => $v) {
			if (strpos($k, '__primary_contact__') === 0) {
				$data['contact_updates'][str_replace('__primary_contact__', '', $k)] = $v;
			}
		}

		$data['new_contact'] = false;
		if ($update->new_record) {
			$aid = null;
			$cid = $update->getField('primary_contact_id');
		} else {
			$cid = $update->getField('primary_contact_id');
			if (!$cid) {
				$account = ListQuery::quick_fetch('Account', $update->getPrimaryKeyValue(), array('primary_contact_id'));
				if ($account)
					$cid = $account->getField('primary_contact_id');
			}
		}

		if (!$cid) {
			$data['new_contact'] = true;
			$cid = create_guid();
		}
		$data['contact_id'] = $cid;
		$update->set('primary_contact_id', $cid);

		$update->setRelatedData($this->id.'_related_data', $data);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function beforeUpdate(RowUpdate &$update) {
		if (!AppConfig::is_B2C())
			return;
		$data = $update->getRelatedData($this->id.'_related_data');
		require_bean('Account');
		$map = Account::getB2CContactFieldsMap();
		foreach ($map as $cfield => $afield) {
			if (isset($data['contact_updates'][$cfield])) {
				$update->set($afield, $data['contact_updates'][$cfield]);
			}
		}
		global $locale;
		$name = $locale->getLocaleFormattedName(
			$data['contact_updates']['first_name'],
			$data['contact_updates']['last_name'],
			$data['contact_updates']['salutation'],
			AppConfig::setting('company.b2c_name_format')
		);
		$update->set('name', $name);
	}
	
	function afterUpdate(RowUpdate &$update) {
		if (!AppConfig::is_B2C())
			return;
		$data = $update->getRelatedData($this->id.'_related_data');
		if ($data['new_contact']) {
			$contact = RowUpdate::blank_for_model('Contact');
			if ($data['contact_id'])
				$contact->set('id', $data['contact_id']);
		} else {
			$row = ListQuery::quick_fetch('Contact', $data['contact_id']);
			if ($row) {
				$contact = RowUpdate::for_result($row);
			} else {
				$contact = RowUpdate::blank_for_model('Contact');
				$contact->set('id', $data['contact_id']);
			}
		}

        $contact->set($data['contact_updates']);
        $contact->set('primary_account_id', $update->getPrimaryKeyValue());
        $contact->set('primary_contact_for', $update->getPrimaryKeyValue());
		$contact->setRelatedData('no_b2c_update', true);
		$contact->save();

        $update->addUpdateLinks('contacts', $contact->getPrimaryKeyValue());
    }

}
?>
