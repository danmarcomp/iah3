<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
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
 */



require_once('include/Dashlets/DashletGeneric.php');
require_once('modules/Cases/Case.php');

class MyCasesDashlet extends DashletGeneric { 

    function __construct($id, $def = null) {
        $this->seedBean = new aCase();
        parent::__construct($id, $def);
        if(empty($def['title'])) $this->title = translate('LBL_LIST_MY_CASES', 'Cases');
	}

	function getTitle($text, $is_sample=false) {
		global $current_language;
		// note the space in class name - without it layout gets broken
		// (some themes try to be "smart")
		$lang = return_module_language($current_language, 'Cases');
		$button = '';
		if(! $is_sample && AppConfig::setting('company.case_queue_user')) {
			$button = '<input type="button" class=" button" onclick="window.location.href=\'index.php?module=Cases&action=GetCase&from_home=1\'" value="' . $lang['LBL_GET_CASE'] . '">';
		}
		return parent::getTitle($text . $button, $is_sample);
	}
}
?>