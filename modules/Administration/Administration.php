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

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/


class Administration {
	var $settings;
	var $table_name = "config";
	var $module_dir = 'Administration';


	// longreach - start added
	var $defaultSequences = array(
		'bug_number_sequence' => array(
			'bugs',
			'bug_number',
		),
		'case_number_sequence' => array(
			'cases',
			'case_number',
		),
		'invoice_number_sequence' => array(
			'invoice',
			'invoice_number',
		),
		'purchase_order_sequence' => array(
			'purchase_orders',
			'po_number',
		),
		'bill_number_sequence' => array(
			'bills',
			'bill_number',
		),
		'sales_order_sequence' => array(
			'sales_orders',
			'so_number',
		),
		'quotes_number_sequence' => array(
			'quotes',
			'quote_number',
		),
		'payment_number_sequence' => array(
			'payments',
			'payment_number',
		),
		'receiving_sequence' => array(
			'receiving',
			'receiving_number',
		),
		'shipping_sequence' => array(
			'shipping',
			'shipping_number',
		),
		'mo_service_number_sequence' => array(
			'monthly_services',
			'instance_number',
		),
	);
	// longreach - end added
	
	
	function invalidateCache($category=false, $global=true) {
		//global $admin_invalidate_category;
		if($category) {
			if(isset($this->settings) && count($this->settings)) {
				$l = strlen($category);
				foreach($this->settings as $k=>$v) {
					if(substr($k, 0, $l+1) == $category . '.') {
						unset($this->settings[$k]);
					}
				}
			}
			//$admin_invalidate_category[$category] = 1;
		}
		/*sugar_cache_clear('Administration.settings');
		if(! $category || $category == 'company') // module-level config cache is a bit awkward
			$GLOBALS['forecastsReloadConfig'] = true;
		*/
	}

	// longreach - modified - implemented per-category cache
	function retrieveSettings($category=false, $clean=false) {
		if(! $category) {
			if($clean)
				$this->settings = array();
			return;
		}
		$settings = AppConfig::setting($category);
		foreach($settings as $k => $v) {
			if(! is_array($v)) {
				$this->settings["$category"."_"."$k"] = $v;
            }
		}
		return $this;
	}

	function saveConfig($no_invalidate=false) {
		foreach ($_POST as $key => $val) {
			$prefix = self::get_config_prefix($key);
			if (in_array($prefix[0], ConfigManager::$db_categories)) {
				$this->saveSetting($prefix[0], $prefix[1], $val); 
			}
		}
		//$this->retrieveSettings(false, true);
		if(! $no_invalidate)
			$this->invalidateCache();
	}
	
    function saveSetting($category, $key, $value, $no_invalidate=false) {
    	return AppConfig::set_local("$category.$key", $value);
    }

	static function get_config_prefix($str) {
		$p = strpos($str, '_');
		if($p !== false)
			return array(substr($str, 0, $p), substr($str, $p+1));
		return array('', $str);
	}
	
    //longreach - start added
	
	function getCurrentPrefix($prefix_name, $category = 'company', $format=true)
	{
		return AppConfig::get_sequence_prefix($prefix_name, $category, $format);
	}
	
	function setPrefix($prefix, $prefix_name, $category = 'company')
	{
		return $this->saveSetting($category, $prefix_name, $prefix);
	}
	
	function getCurrentSequenceValue($sequence_name, $category = 'company', $prefetched=false)
	{
		return AppConfig::setting("$category.$sequence_name");
	}
	
	function getNextSequenceValue($sequence_name, $category=null)
	{
		return AppConfig::next_sequence_value($sequence_name, $category);
	}
	
	function setSequence($start_from, $sequence_name, $min = null, $category = 'company')
	{
		if (!$this->getCurrentSequenceValue($sequence_name, $category)) {
			$this->db->query("insert into `config` set `value`='"
								. PearDatabase::quote($start_from) . "', `category`='"
								. PearDatabase::quote($category) . "', `name`='"
								. PearDatabase::quote($sequence_name) . "'",
								true,
								'Error creating sequence');
		} else {
			if (is_null($min)) {
				$this->db->query("update `config` set `value`='" . PearDatabase::quote($start_from)
								. "' where `category`='"
								. PearDatabase::quote($category) . "' and `name`='"
								. PearDatabase::quote($sequence_name) . "'",
								true,
								'Error setting sequence'
								);
			} else {
				$this->db->query("update `config` set `value`='" . PearDatabase::quote($start_from)
								. "' where `category`='"
								. PearDatabase::quote($category) . "' and `name`='"
								. PearDatabase::quote($sequence_name) . "'"
								. " and if('" . PearDatabase::quote($start_from) . "' > '" . PearDatabase::quote($min) . "', 1, 0)",
								true,
								'Error setting sequence'
								);
			}
		}
		
		//return $this->getCurrentSequenceValue($sequence_name, $category);
	}
	
	function getLogoPath()
	{
		return AppConfig::setting('company.logo_file');
	
		/*return $this->db->getOne("select `value` from `config` where `category`='company' and `name`='logo_file'",
									true,
									'Error retrieving prefix'
									);*/
	}

    static function invalidateACLCache()
	{
		AppConfig::invalidate_cache('acl');
	}
	

	function removeSettings($category, $name = null)
	{
		$query = "DELETE FROM config WHERE category = '" . PearDatabase::quote($category) . "'";
		if (isset($name)) $query .= " AND name = '" . PearDatabase::quote($name) . "'";
		$this->db->query($query, true);
	}
	
	function checkDefaultSequences($prefetched=false)
	{
		foreach ($this->defaultSequences as $name => $seq) {
			if (!$this->getCurrentSequenceValue($name, 'company', $prefetched)) {
				$query = "SELECT IFNULL(MAX({$seq[1]}), 0) AS max FROM {$seq[0]} WHERE NOT deleted";
				$res = $this->db->query($query);
				$row = $this->db->fetchByAssoc($res);
				$this->setSequence($row['max']+1, $name);
			}
		}
	}
}
?>
