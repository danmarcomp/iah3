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


require_once('include/layout/forms/FormButton.php');

class BookingButton extends FormButton {
	var $related_type;
	var $related_id;

	function init($params) {
		parent::init($params);
        //$this->icon = 'theme-icon module-Booking';
        if (! empty($params['date_field']))
            $this->date_field = $params['date_field'];
        if(! $this->vname) {
        	$this->vname = 'LBL_BOOKING_BUTTON_LABEL';
        	$this->vname_module = 'ProjectTask';
        }
        $this->perform = "showBookingDialog(this.form);";
	}
	
	function loadResult(RowResult $rec) {
		$this->related_type = $rec->module_dirs[0];
		$this->related_id = $rec->getField('id');
		require_bean('BookedHours');
		if(! $this->related_type || ! $this->related_id
		|| ! BookedHours::can_book_related_to($this->related_type, $this->related_id))
			$this->hidden = true;
	}
}
?>