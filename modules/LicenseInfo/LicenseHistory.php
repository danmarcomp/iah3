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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('data/SugarBean.php');

class LicenseHistory extends SugarBean {
	var $id;
	var $name;
	var $type;
	var $license_id;
	var $vendor_id;
	var $vendor_name;
	var $date_entered;
	var $date_purchased;
	var $date_support_start;
	var $date_support_end;
	var $active_limit;
	var $terms_short;
	var $terms_long;
	// runtime
	var $summary;
	var $ext_active_limit;
	var $ext_support_end;
	var $ext_product_list;
	// static
	var $table_name = 'license_history';
	var $object_name = 'LicenseHistory';
	var $new_schema = true;
	var $module_dir = 'LicenseInfo';
	
	var $known_types = array(
		'initial', 'renewal', 'trial', 'extension',
	);


	function retrieve_latest() {
		$query = "SELECT main.id FROM $this->table_name main "
			. "WHERE main.type != 'extension' AND NOT main.deleted "
			. "ORDER BY IFNULL(main.date_loaded, IFNULL("
				. "(SELECT MAX(ext.date_purchased) FROM $this->table_name ext
					WHERE ext.type='extension' AND ext.prev_license_id=main.license_id
					AND NOT ext.deleted)"
			. ", main.date_purchased)) DESC LIMIT 1";
		$result = $this->db->query($query, true, "Error retrieving license information");
		if($row = $this->db->fetchByAssoc($result))
			return $this->retrieve($row['id']);
		return null;
	}
	
	function &get_extensions() {
		$query = "SELECT id FROM $this->table_name WHERE type='extension' AND prev_license_id='{$this->license_id}' AND NOT deleted ORDER BY date_entered";
		$result = $this->db->query($query, true, "Error retrieving license information");
		$exts = array();
		$this->ext_active_limit = $this->active_limit;
		$this->ext_support_end = $this->date_support_end;
		$this->ext_product_list = $this->product_list;
		while($row = $this->db->fetchByAssoc($result)) {
			$ext = new LicenseHistory();
			$ext->retrieve($row['id']);
			$exts[] = $ext;
			if(!empty($ext->active_limit))
				$this->ext_active_limit = $ext->active_limit;
			if(!empty($ext->date_support_end))
				$this->ext_support_end = $ext->date_support_end;
			if(!empty($ext->product_list))
				$this->ext_product_list = $ext->product_list;
		}
		return $exts;
	}
	
	function get_support_status() {
		global $current_language, $timedate;
		$lang = ! empty($current_language) ? $current_language : (
			! empty($_SESSION['authenticated_user_language']) ? $_SESSION['authenticated_user_language'] :
				AppConfig::setting('locale.defaults.language')
		);
		$mod_strings = return_module_language($lang, $this->module_dir);
		$status = array('days' => '', 'colour' => '', 'text' => '', 'warn' => '', 'error' => '');
		$status['trial'] = ($this->type == 'trial');
		$support_end = isset($this->ext_support_end) ? $this->ext_support_end : $this->date_support_end;
		if(empty($this->id) || empty($support_end)) {
			$status['colour'] = 'grey';
			$status['text'] = $mod_strings['LBL_LICENSE_NOSUPPORT'];
		}
		else {
			$limit = isset($this->ext_active_limit) ? $this->ext_active_limit : $this->active_limit;
			$now = strtotime($timedate->get_gmt_db_datetime());
			$support_end_ts = strtotime($timedate->to_db_date($support_end, false));
			$days = round(($support_end_ts - $now) / 3600 / 24);
			$status['days'] = $days;

			if($days <= 0) {
				$status['colour'] = 'red';
				if($status['trial'])
					$status['text'] = sprintf($mod_strings['LBL_LICENSE_TRIAL_EXPIRED'], "<b>$support_end</b>");
				else
					$status['text'] = sprintf($mod_strings['LBL_LICENSE_EXPIRED'], "<b>$support_end</b>");
				$status['error'] = preg_replace('~</?b>~', '', $status['text']);
			}
			else if($status['trial']) {
				$status['colour'] = 'yellow';
				$status['text'] = sprintf($mod_strings['LBL_LICENSE_IS_TRIAL'], "<b>$support_end</b>", "<b>$days</b>");
				$status['warn'] = preg_replace('~</?b>~', '', $status['text']);
			}
			else {
				$status['colour'] = 'green';
				$status['text'] = sprintf($mod_strings['LBL_LICENSE_EXPIRING'], "<b>$support_end</b>", "<b>$days</b>");
				if($days < 30) {
					$status['colour'] = 'yellow';
					$status['warn'] = preg_replace('~</?b>~', '', $status['text']);
				}
			}
		}
		return $status;
	}
	
	function check_valid() {
		if(! in_array($this->type, $this->known_types))
			return false;
		// more checks on integrity of uploaded/retrieved license data?
		return true;
	}
	
	function invalidateSessionCache() {
		AppConfig::set_local('license.update_time', time());
	}
	
	function checkSessionCache() {
		global $license;
		if(! isset($_SESSION['LIC_NAME']) || !isset($_SESSION['LIC_UPDATE'])
				|| $_SESSION['LIC_UPDATE'] < AppConfig::setting('license.update_time', 0)
				|| $_SESSION['LIC_UPDATE'] < time() - 3600*24) {
			$seed = new LicenseHistory();
			if($seed->retrieve_latest() === null) {
				$_SESSION['LIC_NAME'] = '';
				$_SESSION['LIC_USERS'] = '';
				$_SESSION['LIC_STATUS'] = array();
				$_SESSION['LIC_PRODUCTS'] = array('infoathand');
				$_SESSION['LIC_UPDATE'] = '';
			}
			else {
				$exts = $seed->get_extensions();
				$_SESSION['LIC_NAME'] = $seed->licensee;
				$_SESSION['LIC_USERS'] = $seed->ext_active_limit;
				$_SESSION['LIC_STATUS'] = $seed->get_support_status();
				$_SESSION['LIC_PRODUCTS'] = explode('^,^', $seed->ext_product_list);
				$_SESSION['LIC_UPDATE'] = time();
			}
		}
	}

}
?>
