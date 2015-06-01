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

class EmailContactWidget extends FormField {

	function getRequiredFields() {
		$req = parent::getRequiredFields();
		$req[] = 'from_name';
		$req[] = 'from_addr';
		return $req;
	}
	
	function renderListCell(ListFormatter $fmt, ListResult &$list_result, $row_id) {
		global $locale;
		$row = $list_result->getRowResult($row_id);
		if($row) {
			$contact = $row->getField($this->name, '', true);
			if(! $contact && ($email = $row->getField('from_addr')) ) {
				$email_from = $row->getField('from_name');
				$contact = $locale->getUserFormatEmail($email, $email_from, '', '',
					array('link_class' => 'listViewExtLink', 'max_length' => 25, 'context' => 'listview', 'use_recip_name' => true));
				$is_read = $row->getField('isread');
				if($fmt->context != 'subpanel' && isset($is_read) && ! $is_read)
					$contact = '<span style="font-weight:bold">' . $contact . '</span>';
			}
			return $contact;
		}
	}
	
}

?>
