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
require_once('modules/DynFields/templates/Fields/TemplateText.php');
class TemplateURL extends TemplateText{
    function get_html_edit(){
        $this->prepare();
        return "<input type='text' name='". $this->name. "' id='".$this->name."' size='".$this->size."' title='{" . strtoupper($this->name) ."_HELP}' value='{". strtoupper($this->name). "}'>";
    }
    
    function get_html_detail(){
        $xtpl_var = strtoupper($this->name);
        return "<a href='{" . $xtpl_var . "}' class='listViewTdLinkS1' target='_blank'>{" . $xtpl_var . "}</a>";
    }
    
    function get_xtpl_detail(){
        $value = parent::get_xtpl_detail();
        if(!empty($value) && substr_count($value, '://') == 0 && substr($value ,0,8) != 'index.php'){
            $value = 'http://' . $value;
        }
        return $value;
    }



   

}


?>
