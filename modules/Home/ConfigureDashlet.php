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

global $current_user, $app_strings, $mod_strings, $pageInstance;

if(! empty($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
	$dboard = $pageInstance->get_dashboard();
	if($dboard && ($dashlet = $dboard->load_dashlet_id($id)) ) {
		if(!empty($_REQUEST['configure']) && $_REQUEST['configure']) { // save settings
			$edit_defaults = (array_get_default($_REQUEST, 'edit') == 'page');
			if($dboard->assigned_user_id == $current_user->id)
				$edit_defaults = true;
			else if(! $dboard->user_can_edit())
				$edit_defaults = false;
			if(! empty($_REQUEST['resetDashlet'])) {
				if($edit_defaults) {
					$dboard->reset_dashlet_options($id);
					$dboard->save();
				} else
					$dboard->reset_user_dashlet_options($id);
			} else {
				$new_opts = $dashlet->saveOptions($_REQUEST);
				if($edit_defaults) {
					$dboard->set_dashlet_options($id, $new_opts);
					$dboard->save();
				} else
					$dboard->set_user_dashlet_options($id, $new_opts);
			}

		} else {
            // display options
			/*$json = getJSONobj();
			$data = array('header' => $dashlet->getTitleText() . ' : ' . $mod_strings['LBL_OPTIONS'],
							'body'  => $dashlet->displayOptions());*/

			//echo 'result = ' . $json->encode($data);
            echo $dashlet->displayOptions();
		}
	}
} else {
    echo '0';
}
?>