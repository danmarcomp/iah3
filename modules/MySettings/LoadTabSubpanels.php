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



/**
 * Created on Jul 17, 2006
 * Ajax Procedure for loading all subpanels for a certain subpanel tab.
 */

require_once('include/DetailView/DetailView.php');
$detailView = new DetailView();

require_once('include/modules.php');
$class = $beanList[$_REQUEST['loadModule']];

require_once($beanFiles[$class]);
$focus = new $class();
$focus->id = $_REQUEST['record'];

require_once('include/SubPanel/SubPanelTiles.php');
$subpanel = new SubPanelTiles($focus, $_REQUEST['loadModule']);

if(!function_exists('get_form_header')) {
    global $theme;
    require_once('themes/'.$theme.'/layout_utils.php');
}
echo $subpanel->display(false);
?>
