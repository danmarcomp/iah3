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
/*********************************************************************************
 * The contents of this file are subject to the CareBrains Public License
 * Version 1.0 ('License'); You may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 * http://www.carebrains.co.jp/CPL .
 * Software distributed under the License is distributed on an 'AS IS' basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 * 
 * The Original Code is CareBrains Open Source.
 * The Initial Developer of the Original Code is CareBrains, Inc.
 * Portions created by CareBrains are Copyright (C) 2005-2006 CareBrains, Inc.
 * All Rights Reserved.
 *
 * The Original Code is: CareBrains Inc.
 * The Initial Developer of the Original Code is CareBrains Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
require_once('include/Dashlets/DashletGeneric.php');
require_once('modules/Threads/Thread.php');

class MyThreadsDashlet extends DashletGeneric { 

	var $listViewModule = 'Forums';
	
    function __construct($id, $def = null) {
        $this->seedBean = new Thread();
        $this->loadLanguage('MyThreadsDashlet', 'Threads'); // load the language strings here
        parent::__construct($id, $def);
        if(empty($def['title'])) $this->title = $this->dashletStrings['LBL_MY_THREADS_TITLE'];
        else $this->title = $def['title'];        
    }
}
?>