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




require_once('modules/Charts/code/Chart_pipeline_by_sales_stage.php');


class MyPipelineBySalesStageDashlet extends Chart_pipeline_by_sales_stage {
	var $is_sidebar = true;
    var $estimContentHeight = 250;    
    var $dashletIcon = 'Forecasts';
	var $default_title = 'LBL_PIPELINE_FORM_TITLE';
    var $default_title_module = 'Home';
    
	function get_footer_text() {
		return translate('LBL_PIPELINE_FORM_TITLE_DESC', 'Charts');
	}
}

?>
