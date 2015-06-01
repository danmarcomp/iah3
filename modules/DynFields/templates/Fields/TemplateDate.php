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
class TemplateDate extends TemplateText{
	var $data_type = 'date';
    var $dateStrings = array(
    		'-none-'=>'',
            'yesterday'=> '-1 day',
            'today'=>'now',
            'tomorrow'=>'+1 day',
            'next week'=> '+1 week',
            'next monday'=>'next monday + 1 day',
            'next friday'=>'next friday + 1 day',
            'two weeks'=> '+2 weeks',
            'next month'=> '+1 month',
            'first day of next month'=> '+1 month',
            'three months'=> '+3 months',
            'six months'=> '+6 months',
            'next year'=> '+1 year',
        );

	function getFieldMeta()
	{
		$ret = parent::getFieldMeta();
		unset($ret['default']);
		return $ret;
	}



	function get_html_edit(){
		$this->prepare();
		global $theme;
		$xtpl_var = strtoupper($this->name);
		$name = $this->name;
		$html = <<<EOQ
<input name='$name' onblur="parseDate(this, '{CALENDAR_DATEFORMAT}');" id='jscal_field$name' type='text'  size='11' maxlength='10' value='{{$xtpl_var}}' title='{{$xtpl_var}_HELP}'> <img src='themes/$theme/images/jscalendar.gif' alt='Enter Date'  id='jscal_trigger$name' align='absmiddle'> <span class='dateFormat'>{USER_DATEFORMAT}</span>
EOQ;
if(empty($GLOBALS['loading_studio_fields'])){
$html .="
<script>
Calendar.setup ({inputField : 'jscal_field$name', ifFormat : '{CALENDAR_DATEFORMAT}', showsTime : false, button : 'jscal_trigger$name', singleClick : true, step : 1});addToValidate('EditView', '$name', 'date', false,'$name' );</script>
";
}
        
		return $html;
	}
	
	
	
function get_field_def(){
		return array_merge(array('required'=>$this->is_required(),'source'=>'custom_fields', "name"=>$this->name, "vname"=>$this->label,"len"=>$this->max_size,'rname'=>$this->name, 'table'=>$this->bean->table_name . '_cstm','massupdate'=>$this->mass_update,  'custom_type'=>'date', 'type'=>'relate'), $this->get_additional_defs());	
}

function get_db_type(){

    if($GLOBALS['db']->dbType == 'mssql'){    
        return " DATETIME ";
    } else {
        return " DATE ";    	
    }	
}
function get_db_default($modify=false){
		return '';
}
function get_xtpl_edit(){
		global $timedate;
		$name = $this->name;
		$returnXTPL = array();
		if(!empty($this->help)){
		    $returnXTPL[strtoupper($this->name . '_help')] = translate($this->help, $this->bean->module_dir);
		}
		$returnXTPL['USER_DATEFORMAT'] = $timedate->get_user_date_format();
		$returnXTPL['CALENDAR_DATEFORMAT'] = $timedate->get_cal_date_format();
		if(isset($this->bean->$name)){
			$returnXTPL[strtoupper($this->name)] = $this->bean->$name;
		}else{
		    if(empty($this->bean->id) && !empty($this->default_value) && !empty($this->dateStrings[$this->default_value])){
		        $returnXTPL[strtoupper($this->name)] = $GLOBALS['timedate']->to_display_date(date('Y-m-d',strtotime($this->dateStrings[$this->default_value])), false);
		    }
		}
		return $returnXTPL;	
	}
	
	
}


?>
