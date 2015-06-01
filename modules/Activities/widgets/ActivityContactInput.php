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


require_once('include/layout/forms/FormField.php');

class ActivityContactInput extends FormField {

	function getRequiredFields() {
		$req = parent::getRequiredFields();
		return $req;
	}
	
	function renderListCell(ListFormatter $fmt, ListResult &$list_result, $row_id) {
        $row = $list_result->getRowResult($row_id);
        $value = '';
        $source = $row->getField('query_source');
		$module = $row->getField('query_module');
		$bean_name = AppConfig::module_primary_bean($module);
        
        if($module == 'Calls' || $module == 'Meetings') {
        	$lq = new ListQuery(strtolower($module).'_contacts', array('contact'));
        	$lq->addSimpleFilter(strtolower($bean_name).'_id', $row->getField($row->primary_key), '=');
        	$result = $lq->runQuerySingle();
        }
        else if($bean_name) {
            $result = ListQuery::quick_fetch($bean_name, $row->getField($row->primary_key), array('contact', 'from_addr', 'from_name'));
		}
		
    	if (! empty($result) && ! $result->failed) {
			if ($result->getField('contact')) {
				$formatter = new FieldFormatter();
				$result->union_key = null;
				$formatter->formatRowResult($result);
				$value = $result->getField('contact', null, true);
			} elseif ($bean_name == 'Email' && ($email = $result->getField('from_addr'))) {
				global $locale;
				$email_from = $result->getField('from_name');
				$value = $locale->getUserFormatEmail($email, $email_from, '', '', array('link_class' => 'listViewExtLink', 'max_length' => 25, 'context' => 'listview', 'use_recip_name' => true));
			}
		}

        return $value;
	}
}
?>