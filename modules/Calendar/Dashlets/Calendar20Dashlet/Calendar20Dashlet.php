<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
 * The contents of this file are subject to the SugarCRM Enterprise End User
 * License Agreement ("License") which can be viewed at
 * http://www.sugarcrm.com/crm/products/sugar-enterprise-eula.html
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * among other things: 1) sublicense, resell, rent, lease, redistribute, assign
 * or otherwise transfer Your rights to the Software, and 2) use the Software
 * for timesharing or service bureau purposes such as hosting the Software for
 * commercial gain and/or for the benefit of a third party.  Use of the Software
 * may be subject to applicable fees and any use of the Software without first
 * paying applicable fees is strictly prohibited.  You do not have the right to
 * remove SugarCRM copyrights from the source code or user interface.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *  (i) the "Powered by SugarCRM" logo and
 *  (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * Your Warranty, Limitations of liability and Indemnity are expressly stated
 * in the License.  Please refer to the License for the specific language
 * governing these rights and limitations under the License.  Portions created
 * by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.; All Rights Reserved.
 */

/*********************************************************************************
* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc. All Rights Reserved.
* Contributor(s): contact@synolia.com - www.synolia.com
********************************************************************************/


require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');
require_once('modules/Calendar/CalendarViewer/CalendarViewer.php');
require_once('modules/Calendar/GridEntry.php');

class Calendar20Dashlet extends Dashlet {
    var $refresh = false;
    var $id;
    var $dashletIcon = 'Calendar';
    var $showListViewLink = true;
    var $showPrintLink = true;
    var $listViewModule = 'Calendar';
    var $listViewACL = 'access';
    var $listViewAction = 'index';
    
    var $estimContentHeight = 250;

    function Calendar20Dashlet($id, $options=null) {
        parent::__construct($id, $options);
        $this->isConfigurable = false;
		$this->hasScript = true;
        $this->isRefreshable = true;
		$this->id = $id;
        if(empty($options['title'])) $this->title = translate('LBL_MODULE_TITLE', 'Calendar');
    }

    function display() {
		global $current_user;
		
		$output = "";
		
		if (!$this->canDisplay()) {
			$output = $this->noDisplay();
		} else {
			CalendarViewer::initRequest(true);
			$view_type = $_REQUEST['view_type'];

			require_once('modules/Calendar/metadata/CalendarViewerDefs.php');
			if(isset($CalendarViewerDefs[$view_type])) {
				$def = $CalendarViewerDefs[$view_type];
				require_once($def['viewer_class_file']);
				$viewer = new $def['viewer_class']($_REQUEST);
				if(isset($def['viewer_tpl']) && !empty($def['viewer_tpl'])) {
					$viewer->templatePath = $def['viewer_tpl']; 
				}
				$viewer->forDashlet = true;
				$viewer->dashletId = $this->id;
				$output .= $viewer->execute();
				$viewer->exportIncludes();
			} 
		}
		
		return parent::display('') . $output;    	
    }
    
    function displayScript() {
    }
    
    function getPrintAction() {
    	return "SUGAR.util.loadUrl('index.php?module=Calendar&action=PDF', null, true)";
    }
    
    function canDisplay() {
    	if(! ACLController::checkAccess('Calendar', 'access'))
    		return false;
    	return parent::canDisplay();
    }
    
	function noDisplay() {
		return '<p style="text-align: center;" class="error dashlet-body">' . translate('MSG_DASHLET_NO_ACCESS') . '</p>';
	}
}
?>
