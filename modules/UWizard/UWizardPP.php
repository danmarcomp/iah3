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

class UWizardPP extends UWizard
{
	private $subwizards = array();

	protected function __construct($zipfile, $action)
	{
		parent::__construct($zipfile, $action);
		$this->unzip();
		$dir = self::getUnzipDir($this->manifest);
		$packages = $this->manifest->path('included_packages', array());
		foreach ($packages as $file) {
			$this->subwizards[] = new UWizard($dir . $file, $action);
		}
	}

	private function unzip()
	{
		parent::unzipPackage(false);
	}

	protected function unzipPackage($cleanBackup = true)
	{
		foreach ($this->subwizards as $wiz) {
			$wiz->unzipPackage($cleanBackup);
		}
		return array(
			'status' => 'success',
			'message' => translate('LBL_UNZIPPED_SUCCESS', 'UWizard'),
		);
	}
	
	protected function checkPermissions($path = 'copy')
	{
		$result = array();
		foreach ($this->subwizards as $wiz) {
			$sm = $wiz->getManifest();
			$cond_key = 'package_' . $sm->getType() . '_' . $sm->getId() . '_' . $sm->getVersion();
			$GLOBALS['log']->debug("Checking sub-package $cond_key for install permissions ......");
			if (!empty($_SESSION['UWizard_conditions'][$cond_key])) {
				$GLOBALS['log']->debug("true");
				$res = $wiz->checkPermissions($path);
				if (!empty($res['errors']))
					foreach ($res['errors'] as $error)
						$result['errors'][] = $error;
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		$res = parent::checkPermissions($path);
		if (!empty($res['errors']))
			foreach ($res['errors'] as $error)
				$result['errors'][] = $error;
		if (empty($result['errors']))
			$result = array(
				'status' => 'success',
				'message' => translate('LBL_PERMISSIONS_OK', 'UWizard'),
			);
		else
			$result['status'] = 'fatal';
		return $result;
	}
	
	protected function checkRemovePermissions($path = 'copy')
	{
		$_SESSION['UWizard_conditions'] = self::getPPackConditions($this->manifest);
		$result = array();
		foreach ($this->subwizards as $wiz) {
			$sm = $wiz->getManifest();
			$cond_key = 'package_' . $sm->getType() . '_' . $sm->getId() . '_' . $sm->getVersion();
			$GLOBALS['log']->debug("Checking sub-package $cond_key for remove permissions ......");
			if (!empty($_SESSION['UWizard_conditions'][$cond_key])) {
				$GLOBALS['log']->debug("true");
				$res = $wiz->checkPermissions($path);
				if (!empty($res['errors']))
					foreach ($res['errors'] as $error)
						$result['errors'][] = $error;
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		$res = parent::checkRemovePermissions($path);
		if (!empty($res['errors']))
			foreach ($res['errors'] as $error)
				$result['errors'][] = $error;
		if (empty($result['errors']))
			$result = array(
				'status' => 'success',
				'message' => translate('LBL_PERMISSIONS_OK', 'UWizard'),
			);
		else
			$result['status'] = 'fatal';
		return $result;
	}
	
	protected function remove($from_ppack = false)
	{
		$result = array();
		foreach ($this->subwizards as $wiz) {
			$sm = $wiz->getManifest();
			$cond_key = 'package_' . $sm->getType() . '_' . $sm->getId() . '_' . $sm->getVersion();
			$GLOBALS['log']->debug("Checking sub-package $cond_key for uninstall ......");
			if (!empty($_SESSION['UWizard_conditions'][$cond_key])) {
				$GLOBALS['log']->debug("true");
				$wiz->remove(true);
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		parent::remove(false);
		$result['status'] = 'success';
		$result['final'] = 'true';
		return $result;
	}

	protected function commit($from_ppack = false)
	{
		$result = array();
		foreach ($this->subwizards as $wiz) {
			$sm = $wiz->getManifest();
			$cond_key = 'package_' . $sm->getType() . '_' . $sm->getId() . '_' . $sm->getVersion();
			$GLOBALS['log']->debug("Checking sub-package $cond_key for install ......");
			if (!empty($_SESSION['UWizard_conditions'][$cond_key])) {
				$GLOBALS['log']->debug("true");
				$wiz->commit(true);
			} else {
				$GLOBALS['log']->debug("false");
			}
		}
		$this->commitCustomFields();
		parent::commit(false);
		$result['status'] = 'success';
		$result['final'] = 'true';
		return $result;
	}

	protected function commitCustomFields()
	{
		if (!empty($_SESSION['UWizard_conditions']['custom'])) {
			$custom_fields = $this->manifest->path('custom');
			foreach ($custom_fields as $f) {
				$lq = new ListQuery('DynField');
				$lq->addSimpleFilter('name', $f['name']);
				$lq->addSimpleFilter('custom_module', $f['custom_module']);
				$res = $lq->runQuerySingle();
				if (!$res->failed) {
					$upd = RowUpdate::for_result($res);
				} else {
					$upd = RowUpdate::blank_for_model('DynField');
				}
				$upd->set($f);
				$upd->save();
			}
		}
	}
	
	protected function checkCustomFields()
	{
		if (!empty($_SESSION['UWizard_conditions']['custom'])) {
			$custom_fields = $this->manifest->path('custom');
			foreach ($custom_fields as $f) {
				$lq = new ListQuery('DynField');
				$lq->addSimpleFilter('name', $f['name']);
				$lq->addSimpleFilter('custom_module', $f['custom_module']);
				$res = $lq->runQuerySingle();
				if (!$res->failed) {
					if ($res->getField('data_type') != $f['data_type']) {
						$msg = sprintf(translate('ERR_CUSTOM_CONFLICT'), $f['name'], $f['custom_module']);
						throw new UWizardError($msg);
					}
				}
				
			}
		}
		return array(
			'status' => 'success',
			'message' => translate('LBL_CUSTOM_OK', 'UWizard'),
		);
	}
	
	public function getPrepareSteps($manifest, $confirm)
	{
		$steps = parent::getPrepareSteps($manifest, $confirm);
		if (!empty($_SESSION['UWizard_conditions']['custom'])) {
			$custom_fields = $this->manifest->path('custom');
			if (!empty($custom_fields)) {
				$steps[] = 'checkCustomFields';
			}
		}
		return $steps;
	}
}

