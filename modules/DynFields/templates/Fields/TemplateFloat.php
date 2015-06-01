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
class TemplateFloat extends TemplateText{
    function get_html_edit(){
        $this->prepare();
        return "<input type='text' name='". $this->name. "' id='".$this->name."' size='".$this->size."' title='{" . strtoupper($this->name) ."_HELP}' maxlength='".$this->max_size."' value='{". strtoupper($this->name). "}'>";
    }

	function getFieldMeta()
	{
		$ret = parent::getFieldMeta();
		$ret['decimal_places'] = $this->ext1;
		return $ret;
	}	

    function get_field_def(){
        return array_merge(array('required'=>$this->is_required(),'source'=>'custom_fields', "name"=>$this->name, "vname"=>$this->label,"type"=>'float','massupdate'=>$this->mass_update, "len"=>$this->max_size,'rname'=>$this->name, 'table'=>$this->bean->table_name . '_cstm',  'custom_type'=>'float', 'type'=>'relate', 'decimal_places'=>$this->ext1), $this->get_additional_defs());
    }

    function get_db_type(){
		if ($GLOBALS['db']->dbType=='mysql') {

    	    $type = " FLOAT";
        	if(!empty($this->max_size)){
	            $type.= "(". $this->max_size;
    	        if(!empty($this->ext1)){
        	        $type .= ", " . $this->ext1;
              
            	}

        	$type .= ')';
    		}
		}
		elseif ($GLOBALS['db']->dbType=='mssql')
		{
			$type = " decimal";
        	if(!empty($this->max_size)){
	            $type.= "(". $this->max_size;
    	    if(!empty($this->ext1)){
        	    $type .= ", " . $this->ext1 . ")";
            }else
            {
            	$type .= ',4)';
    		}
		}else{
			$type= " decimal(11,4) ";
        }

		return $type;	
			
		}
    	else if ($GLOBALS['db']->dbType=='oci8') {
			$type= " NUMBER(30,6) ";
    	}
    	
    	/**
		 * FOR ORACLE 
    	 * return " NUMBER($this->max_size, $this->ext1)";
     	 */
    	return $type;
	}
}

?>
