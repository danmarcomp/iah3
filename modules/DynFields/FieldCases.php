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


function get_custom_field_widget($type) {
	$cls = get_custom_field_class_name($type);
	if($cls) {
		require_once("modules/DynFields/templates/Fields/$cls.php");
		return new $cls();
	}
}

function get_custom_field_class_name($type) {
	$local_temp = null;
	switch(strtolower($type)){
			case 'textarea':
						return 'TemplateTextArea';
			case 'double':
			case 'float':
						return 'TemplateFloat';
			case 'int':
						return 'TemplateInt';
			case 'date':
						return 'TemplateDate';
			case 'bool':
						return 'TemplateBoolean';
			case 'relate':
						return 'TemplateRelatedTextField';
			case 'enum':
						return 'TemplateEnum';
			case 'multienum':
						return 'TemplateMultiEnum';
			case 'radioenum':
						return 'TemplateRadioEnum';
			case 'email':
						return 'TemplateEmail';
		     case 'url':
						return 'TemplateURL';
			case 'html':
						return 'TemplateHTML';
			case 'char':
			case 'varchar':
			case 'varchar2':
			case 'text':
			default:
						return 'TemplateText';
	}
}

?>
