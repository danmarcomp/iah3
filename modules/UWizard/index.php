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

require_once 'modules/UWizard/UWizard.php';

$perform = array_get_default($_POST, 'perform');
$lang = AppConfig::setting('lang.strings.current.UWizard');

action_restricted_for('demo');
if(empty($_SESSION['LIC_STATUS']) || $_SESSION['LIC_STATUS']['days'] < 0) {
	throw IAHPermissionError::withParams('MSG_LIC_UPGRADES_DISABLED', array('redirect_home' => true));
}

switch ($perform) {
	case 'upload':
		$error = translate(UWizard::upload('upgrade_zip'), 'UWizard');
		if ($error) {
			$message = $error;
			$icon = 'icon-ledred';
			$color = 'red';
		} else {
			$message = translate('LBL_UPLOAD_SUCCESS');
			$icon = 'icon-ledgreen';
			$color = 'green';
		}
		echo <<<HTML
<table width="60%" border="0" cellspacing="0" cellpadding="0" class="tabForm flashMessage" align="center">
	<tr>
	<td class="dataLabel" width="100%" style="text-align: center; padding: 0.5em 0 0.5em 20px">
		<div class="input-icon {$icon}"></div>
		<span style="color:{$color};">{$message}</span><br>
	</td>
	</tr>
</table>
<p> </p>
HTML;
		break;
	default:
		$source = array_get_default($_POST, 'source');
		if (UWizard::isValidPackageArchive($source)) {
			if ($perform == 'prepare_install') {
				UWizard::prepareInstall($source);
				return;
			}
			if ($perform == 'install') {
				UWizard::beginInstall($source);
				return;
			}
			if ($perform == 'prepare_uninstall') {
				UWizard::prepareUninstall($source);
				return;
			}
			if ($perform == 'uninstall') {
				UWizard::beginUninstall($source);
				return;
			}
			if ($perform == 'delete') {
				UWizard::deletePackage($source);
			}
		}
}

unset($_SESSION['UWizard_result']);

?>
<?php echo get_module_title('Administration', to_html($lang['LBL_MODULE_TITLE']), false); ?>
<form method="post" action="index.php" enctype="multipart/form-data" name="the_form">
<input type="hidden" name="module" value="UWizard">
<input type="hidden" name="action" value="index">
<input type="hidden" name="perform" value="upload">

<table cellspacing="0" cellpadding="0" border="0" width="100%" class="tabForm">
<tbody><tr colspan="2"><td>
<table cellspacing="0" cellpadding="0" border="0">
<tbody>
<tr><td><p class="topLabel">
<?php echo $lang['LBL_UPLOAD_PACKAGE']; ?>
</p></td></tr>
<tr valign="middle">
<td>
<input type="file" class="input-file" size="40" name="upgrade_zip" style="margin-right: 5px">
<button class="input-button input-outer" type="submit"><div class="input-icon icon-accept left"></div><span class="input-label"><?php echo $lang['LBL_UPLOAD'];?></span></button>
</td>
<td style="width:50%">&nbsp;</td>
</tr>
<tr><td colspan="3"><div class="fieldDesc" style="margin-top: 0.5em">
<?php echo $lang['LBL_UPLOAD_DESC']; ?></div></td></tr>
</tbody></table></td></tr></tbody></table>
</form>
<?php
$packages = UWizard::listUploadedPackages();
UWizard::renderUploaded($packages);
$packages = UWizard::listInstalledPackages();
UWizard::renderInstalled($packages);

