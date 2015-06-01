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
require_once('XTemplate/xtpl.php');

class ImportDBxtpl extends XTemplate {
	public function __construct($file, $module, $parse_message = true, $strings_override = array()) {
		parent::XTemplate($file);
		$mstrings = return_module_language('', 'ImportDB');
		$strings = array_merge($mstrings, $strings_override);
		$this->assign('MOD_STRINGS', $strings);
		$this->assign('MODULE', $module);

		if ($parse_message) $this->parseMessage();
		$profile = getImportDBProfile($module, false);
		if (!empty($profile)) {
			$this->assign('PROFILE', array('name' => $profile->getName(), 'title' => $profile->getTitle()));
		}
	}

	/**
	 * @return void
	 */
	protected function parseMessage() {
		$msg_type = empty($_REQUEST['msg_type']) ? 'info' : $_REQUEST['msg_type'];
		$msg_text = empty($_REQUEST['msg_text']) ? '' : $_REQUEST['msg_text'];

		if (empty($msg_text) && !empty($_SESSION['__ImportDB']['msg_text'])) {
			$msg_type = empty($_SESSION['__ImportDB']['msg_type']) ? 'info' : $_SESSION['__ImportDB']['msg_type'];
			$msg_text = empty($_SESSION['__ImportDB']['msg_text']) ? '' : $_SESSION['__ImportDB']['msg_text'];
			importSessionSet('__ImportDB.msg_type', null);
			importSessionSet('__ImportDB.msg_text', null);
		}


		$display_messages = array();
		if (!empty($msg_text)) {
			$display_messages[] = array($msg_text, $msg_type);
		}
		if (!empty($_SESSION['__ImportDB']['warnings'])) {
			foreach ($_SESSION['__ImportDB']['warnings'] as $warn) {
				$display_messages[] = array($warn, 'warn');
			}
			importSessionSet('__ImportDB.warnings', null);
		}

		if (!empty($display_messages)) {
			$this->assign('MESSAGE', display_flash_messages_default($display_messages, false));
			$this->parse('main.message');
		}
	}
}
