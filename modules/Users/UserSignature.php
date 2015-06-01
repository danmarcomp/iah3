<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description: TODO:  To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once ('log4php/LoggerManager.php');

require_once ('data/SugarBean.php');

// User is used to store customer information.
class UserSignature extends SugarBean {
	var $id;
	var $date_entered;
	var $date_modified;
	var $deleted;
	var $user_id;
	var $name;
	var $signature;
	var $signature_html;
	var $table_name = 'users_signatures';
	var $module_dir = 'Users';
	var $object_name ='UserSignature';
	var $disable_custom_fields = true;

	function UserSignature() {



		
		parent::SugarBean();	
	}
	
	/**
	 * returns the bean name - overrides SugarBean's
	 */
	function get_summary_text() {
		return $this->name;
	}

	/**
	 * Override's SugarBean's
	 */
	function create_export_query($order_by, $where, $show_deleted = 0) {
		return $this->create_list_query($order_by, $where, $show_deleted = 0);
	}
	
	/**
	 * Override's SugarBean's
	 */
	function create_list_query($order_by, $where, $show_deleted = 0) {
		$query = 'SELECT '.$this->table_name.'.* FROM '.$this->table_name.' ';

		if($show_deleted == 0) {
			$where_auto = 'DELETED=0';
		} elseif($show_deleted == 1) {
			$where_auto = 'DELETED=1';
		} else {
			$where_auto = '1=1';
		}

		if($where != "") {
			$query .= 'WHERE ('.$where.') AND '.$where_auto;
		} else {
			$query .= 'WHERE '.$where_auto;
		}

		if(!empty($order_by))
			$query .= ' ORDER BY '.$order_by;
		return $query;
	}

	/**
	 * Override's SugarBean's
	 */
	function get_list_view_data(){
		global $mod_strings;
		global $app_list_strings;
		$temp_array = $this->get_list_view_array();
		$temp_array['MAILBOX_TYPE_NAME']= $app_list_strings['dom_mailbox_type'][$this->mailbox_type];
		return $temp_array;
	}

	/**
	 * Override's SugarBean's
	 */
	function fill_in_additional_list_fields() {
		$this->fill_in_additional_detail_fields();
	}

	/**
	 * Override's SugarBean's
	 */
	function fill_in_additional_detail_fields() {
	}

    static function rewrite_onsubmit(DetailManager $mgr) {
        global $pageInstance;
        $pageInstance->add_js_literal("setSignatureOnSubmitEvent();", null, LOAD_PRIORITY_FOOT);
    }
} // end class definition
?>
