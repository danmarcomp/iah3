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

class WFException extends Exception {

	const ERR_INVALID_TYPE = 'ERR_INVALID_TYPE';
	const ERR_EMPTY_RECIPIENTS = 'ERR_EMPTY_RECIPIENTS';
	const ERR_EMPTY_SUBJECT = 'ERR_EMPTY_SUBJECT';
	const ERR_EMPTY_MESSAGE = 'ERR_EMPTY_MESSAGE';
	const ERR_EMPTY_INVITEES = 'ERR_EMPTY_INVITEES';
	const ERR_INVALID_DATE = 'ERR_INVALID_DATE';
	const ERR_NO_UPDATE_FIELD = 'ERR_NO_UPDATE_FIELD';
	const ERR_NO_UPDATE_DELETED = 'ERR_NO_UPDATE_DELETED';
	const ERR_NO_UPDATE_MODULE = 'ERR_NO_UPDATE_MODULE';
	const ERR_EMPTY_PRIORITY = 'ERR_EMPTY_PRIORITY';
	const ERR_EMPTY_STATUS = 'ERR_EMPTY_STATUS';

	const ERR_EMPTY_FIELD = 'ERR_EMPTY_FIELD';
	const ERR_EMPTY_VALUE = 'ERR_EMPTY_VALUE';

	const ERR_EMPTY_NAME = 'ERR_EMPTY_NAME';
	const ERR_EMPTY_WF_STATUS = 'ERR_EMPTY_WF_STATUS';
	const ERR_EMPTY_EXEC_MODE = 'ERR_EMPTY_EXEC_MODE';
	const ERR_EMPTY_EXEC_MODULE = 'ERR_EMPTY_EXEC_MODULE';
	const ERR_EMPTY_ACTION = 'ERR_EMPTY_ACTION';
	const ERR_EMPTY_OP_MODE = 'ERR_EMPTY_OP_MODE';
	const ERR_EMPTY_OWNER = 'ERR_EMPTY_OWNER';

	protected $code;

	function __construct($code)
	{
		$this->code = $code;
	}


	function getErrorMessage() {
		return translate($this->code, 'Workflow');
	}
}

