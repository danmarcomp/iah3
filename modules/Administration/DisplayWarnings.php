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



function displayAdminError($errorString){
	echo '<p class="error">' . $errorString .'</p>';
}

if(!empty($_SESSION['HomeOnly'])){
	displayAdminError(translate('FATAL_LICENSE_ALTERED', 'Administration'));
}

if(AppConfig::setting('license.msg_all')){
	displayAdminError(AppConfig::setting('license.msg_all'));
}
if(is_admin($current_user)){
if(!empty($_SESSION['COULD_NOT_CONNECT'])){
	displayAdminError(translate('LBL_COULD_NOT_CONNECT', 'Administration') . ' '. $timedate->to_display_date_time($_SESSION['COULD_NOT_CONNECT']));		
}
if(AppConfig::setting('license.msg_admin')){
	displayAdminError(AppConfig::setting('license.msg_admin'));
}
if(! AppConfig('config.installer_locked')){
	displayAdminError(translate('WARN_INSTALLER_LOCKED', 'Administration'));
}

		if(isset($_SESSION['invalid_versions'])){
			$invalid_versions = $_SESSION['invalid_versions'];
			foreach($invalid_versions as $invalid){
				if(isset($invalid['label']))
					$label = translate($invalid['label'], 'Administration');
				else
					$label = $invalid['name'];
				displayAdminError(translate('WARN_UPGRADE', 'Administration'). $label .translate('WARN_UPGRADE2', 'Administration'));
			}
		}
	
		
		if (isset($_SESSION['available_version'])){
			if($_SESSION['available_version'] != $sugar_version)
			{
				displayAdminError(translate('WARN_UPGRADE', 'Administration').$_SESSION['available_version']." : ".$_SESSION['available_version_description']);
			}
		}

		if(isset($_SESSION['administrator_error']))
		{
			// Only print DB errors once otherwise they will still look broken
			// after they are fixed.
			displayAdminError($_SESSION['administrator_error']);
		}

		unset($_SESSION['administrator_error']);
}

?>
