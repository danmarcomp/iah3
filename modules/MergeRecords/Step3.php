<?php
if (!defined('sugarEntry') || !sugarEntry)
    die('Not A Valid Entry Point');
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
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once ('XTemplate/xtpl.php');
require_once ('modules/MergeRecords/MergeRecord.php');
require_once ('themes/'.$theme.'/layout_utils.php');

global $app_strings;
global $mod_strings;
global $app_list_strings;
global $current_language;
global $urlPrefix;
global $currentModule;
global $theme;
global $timedate;

//filter condition for fields in vardefs that can participate in merge.
$filter_for_valid_editable_attributes = 
    array( 
         array('type'=>'datetime','source'=>'db'),
         array('type'=>'varchar','source'=>'db'),
         array('type'=>'enum','source'=>'db'),
         array('type'=>'text','source'=>'db'),
         array('type'=>'date','source'=>'db'),
         array('type'=>'time','source'=>'db'),
         array('type'=>'int','source'=>'db'),
         array('type'=>'long','source'=>'db'),
         array('type'=>'double','source'=>'db'),
         array('type'=>'float','source'=>'db'),
         array('type'=>'short','source'=>'db'),
         array('dbType'=>'varchar','source'=>'db'),
         array('dbType'=>'double','source'=>'db'),
         
         array('type'=>'relate'),
    );

$filter_for_valid_related_attributes   = array( array('type'=>'link'),);
$filter_for_invalid_related_attributes   = array(array('type'=>'link','link_type'=>'one'));

//following attributes will be ignored from the merge process.
$invalid_attribute_by_name= array('date_entered'=>'date_entered','date_modified'=>'date_modified','modified_user_id'=>'modified_user_id', 'created_by'=>'created_by','deleted'=>'deleted');

//show the JS confirm popup for unmergeable records
$show_JSnotice=true;

$merge_ids_array = array ();
if (isset($_REQUEST['change_parent']) && $_REQUEST['change_parent']=='1') {
   $show_JSnotice=false;
   $base_id=$_REQUEST['change_parent_id'];
     foreach ($_REQUEST['merged_ids'] as $id) {
        if ($id != $base_id) {
            $merge_ids_array[] = $id;
        }
     }
     //add the existing parent to merged_id array.   
     $merge_ids_array[] = $_REQUEST['record'];
} elseif (isset($_REQUEST['remove']) && $_REQUEST['remove']=='1') {
	$show_JSnotice=false;
    $base_id=$_REQUEST['record'];
    $removed_id= $_REQUEST['remove_id'];
     foreach ($_REQUEST['merged_ids'] as $id) {
        if ($id != $removed_id) {
            $merge_ids_array[] = $id;
        }
     }       
} else {
    $base_id=$_REQUEST['record'];
     foreach ($_REQUEST['mass'] as $id) {
        $merge_ids_array[] = $id;
     }
}

//ACL verification
$ACL_array_complete=build_ACL_array($base_id,$merge_ids_array);

$ACL_array=$ACL_array_complete[0];
//$only=True if the primary is a primary only => disable the "set as primary" button for all the other
$only=$ACL_array_complete[1];
//set the new primary record (changed by the ACL process if neeeded)
$base_id=$ACL_array["primary"];
//if error during the ACL process show and exit

if($ACL_array["error"]){
	if(!empty($_REQUEST['return_action']) && !empty($_REQUEST['return_module']))
	   echo "<script type=\"text/javascript\">alert('".$ACL_array["error"]."'); document.location = \"index.php?module=".$_REQUEST['return_module']."&action=".$_REQUEST['return_action']."\";</script>";
	else
	   echo "<script type=\"text/javascript\">alert('".$ACL_array["error"]."'); document.location = \"index.php\";</script>";
	die();
}

//Javascript confirmation for unmergeable record due to ACL restriction
$rejected=array();
foreach($ACL_array as $id=>$va){
	if($id!="error" && $id!="primary" && !$ACL_array[$id]['view']){
		$rejected[]=$ACL_array[$id]['name'];
	}
}
if(count($rejected)!=0 && $show_JSnotice){
    $confirm='<script type="text/javascript">var va=confirm("'.$GLOBALS['mod_strings']['LBL_NOT_MERGED_MESSAGE'].'\n';
    foreach($rejected as $name){
        $confirm.="- ".$name.'\n';
    }
    if(!empty($_REQUEST['return_action']) && !empty($_REQUEST['return_module']))
        $confirm.=$GLOBALS['mod_strings']['LBL_CLICK_OK'].'"); if(!va){document.location = "index.php?module='.$_REQUEST['return_module'].'&action='.$_REQUEST['return_action'].'";}</script>';
    else
        $confirm.=$GLOBALS['mod_strings']['LBL_CLICK_OK'].'"); if(!va){document.location = "index.php";}</script>';
    echo $confirm;
}
//END of javascript confirmation


$focus = new MergeRecord();
$focus->load_merge_bean($_REQUEST['merge_module'], true, $base_id);

echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MERGE_RECORDS_WITH'].": ".$focus->merge_bean->name, true);

$mergeBeanArray = array ();
$records=1;
//render a column for each record to merge
$merged_ids='';
$merge_records_names=array();
foreach ($merge_ids_array as $id) {
    require_once ($focus->merge_bean_file_path);
    $mergeBeanArray[$id] = new $focus->merge_bean_class();
    $mergeBeanArray[$id]->retrieve($id);
    $merge_records_names[]=$mergeBeanArray[$id]->get_summary_text();
    $records++;
    $merged_ids.="<input type='hidden' name='merged_ids[]' value='$id'>";
}

$col_width=floor(80/$records).'%';
$max_data_length=floor(65/$records);
$xtpl = new XTemplate("modules/MergeRecords/Step3.html");
$xtpl->assign("MOD", $mod_strings);
$xtpl->assign("APP", $app_strings);

$xtpl->assign("ID", $focus->merge_bean->id);
$xtpl->assign("MERGE_MODULE", $focus->merge_module);

$xtpl->assign("MERGED_IDS", $merged_ids);

//set return parameters.
if (!empty ($_REQUEST['return_module'])) {
    $xtpl->assign("RETURN_MODULE", $_REQUEST['return_module']);
}
if (!empty ($_REQUEST['return_action'])) {
    $xtpl->assign("RETURN_ACTION", $_REQUEST['return_action']);
}
if (!empty ($_REQUEST['return_id'])) {
    $xtpl->assign("RETURN_ID", $_REQUEST['return_id']);
}

$temp_field_array = $focus->merge_bean->field_defs;
$field_count = 1;
$json = getJSONobj();
$diff_field_count=0;
foreach ($temp_field_array as $field_array) {

        
    if (show_field($field_array)) {
        
        $select_row_curr_field_value = null;
        $b_values_different = false;
        $section_name='merge_row_similar';
    
        //Prcoess locaton of the field. if values are different show field in first section. else 2nd.
        $select_row_curr_field_value = $focus->merge_bean->$field_array['name'];

		if (isset ($field_array['custom_type']) && $field_array['custom_type'] != '')
            $field_check = $field_array['custom_type'];
        else
            $field_check = $field_array['type'];
        
        if( ($field_check == 'relate' || $field_check == 'link') && empty($field_array['id_name']))
        	continue;
		if(isset($field_array['multi_select_group']) && $field_array['multi_select_group'] != $field_array['name'])
			continue;

		foreach ($merge_ids_array as $id) {
			if ($field_check == 'relate' || $field_check == 'link') {
				$related_name=get_related_name($field_array,$mergeBeanArray[$id]-> $field_array['id_name']);
				if ($select_row_curr_field_value != $related_name) {
					$section_name = 'merge_row_diff';
					$diff_field_count++;
				}
			}
			elseif (($mergeBeanArray[$id]-> $field_array['name']=='' and $select_row_curr_field_value =='') or $mergeBeanArray[$id]-> $field_array['name'] == $select_row_curr_field_value ) {
                $section_name='merge_row_similar';
            } else  {
                $section_name='merge_row_diff';
                $diff_field_count++;
                break; //foreach
            }
        }
        //check for vname in mod strings first, then app, else just display name
        $col_name = $field_array['name'];
        if (isset ($focus->merge_bean_strings[$field_array['vname']]) && $focus->merge_bean_strings[$field_array['vname']] != '')
            $xtpl->assign("FIELD_LABEL", $focus->merge_bean_strings[$field_array['vname']]);
        elseif (isset ($app_strings[$field_array['vname']]) && $app_strings[$field_array['vname']] != '') $xtpl->assign("FIELD_LABEL", $app_strings[$field_array['vname']]);
        else
             $xtpl->assign("FIELD_LABEL", $field_array['name']);
        //if required add signage.
        if (!empty($focus->merge_bean->required_fields[$col_name]) or $col_name=='team_name') {
            $xtpl->assign("REQUIRED_SYMBOL","<span class='required'>".$app_strings['LBL_REQUIRED_SYMBOL']."</span>");
        } else {
            $xtpl->assign("REQUIRED_SYMBOL","");
        }            
        
        $xtpl->assign("CELL_WIDTH", "20%");
        $xtpl->parse("main.".$section_name.".merge_cell_label");
        
        $xtpl->assign("EDIT_FIELD_NAME", $field_array['name']);
        $xtpl->assign("TAB_INDEX", $field_count);

		// longreach - modified
		$input_type = $field_check;
		if(strpos($field_array['name'], 'address_street') > 0) { 
			$input_type = 'text';
		}
        switch ($input_type) {
            case ('name') :
            case ('varchar') :
            case ('phone') :
            case ('num') :
            case ('email') :
            case ('custom_fields') :
            case ('int') :
            case ('float') :
            case ('double') :
            case ('currency') :
            
                $xtpl->assign("EDIT_FIELD_VALUE", $select_row_curr_field_value);
                $xtpl->assign("CELL_WIDTH", $col_width);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_text");
                break;
            case ('text') :
                $xtpl->assign("EDIT_FIELD_VALUE", $select_row_curr_field_value);
                $xtpl->assign("CELL_WIDTH", $col_width);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_textarea");
                break;
            case ('enum') :
                $xtpl->assign("SELECT_OPTIONS", get_select_options_with_id($app_list_strings[$field_array['options']], $select_row_curr_field_value));
                $xtpl->assign("CELL_WIDTH",$col_width);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_dropdown");
                break;
            case ('multienum') :
            	$col_name .= '[]';
            	$xtpl->assign("EDIT_FIELD_NAME", $col_name);
            	$opts = array();
            	if(isset($field_array['multi_select_group'])) {
            		$grp = $field_array['multi_select_group'];
            		foreach($temp_field_array as $fa) {
            			if(isset($fa['multi_select_group']) && $fa['multi_select_group'] == $grp)
            				if(isset($focus->merge_bean->$fa['name']))
            					$opts[] = $focus->merge_bean->$fa['name'];
            		}
            	}
            	$opts = array_unique($opts);
            	sort($opts);
                $xtpl->assign("SELECT_OPTIONS", get_select_options_with_id($app_list_strings[$field_array['options']], $opts));
                $xtpl->assign("CELL_WIDTH",$col_width);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_multisel");
                break;
                //popup fields need to be fixed.., cant automate with vardefs
            case ('relate') :
            case ('link') :
                //get_related_name
                if (empty($select_row_curr_field_value)) {
                    $related_name=get_related_name($field_array,$focus->merge_bean->$field_array['id_name']);
                    if ($related_name !== false ) {
                       $select_row_curr_field_value=$related_name;
                    } 
                }
                $xtpl->assign("POPUP_ID_FIELD", $field_array['id_name']);
                $xtpl->assign("POPUP_NAME_FIELD", $field_array['name']);
                $xtpl->assign("POPUP_NAME_VALUE", $select_row_curr_field_value);
				$xtpl->assign("POPUP_ID_VALUE", $focus->merge_bean-> $field_array['id_name']);
                $xtpl->assign("POPUP_MODULE", $field_array['module']);
                $xtpl->assign("CELL_WIDTH", $col_width);

                $popup_data = array ('call_back_function' => 'set_return', 'form_name' => 'EditView', 'field_to_name_array' => array ('id' => $field_array['id_name'], 'name' => $field_array['name'],),);
                $xtpl->assign('ENCODED_POPUP_DATA', $json->encode($popup_data));

                $xtpl->parse("main.".$section_name.".merge_cell_edit_popup");
                break;
            case ('bool') :
                if ($select_row_curr_field_value == '1' || $select_row_curr_field_value == 'yes' || $select_row_curr_field_value == 'on')
                    $xtpl->assign("EDIT_FIELD_VALUE", " checked");
                else
                    $xtpl->assign("EDIT_FIELD_VALUE", "");

                $xtpl->assign("CELL_WIDTH", $col_width);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_checkbox");
                break;
            case ('date') :
            case ('datetime') :
                $xtpl->assign("CALENDAR_LANG", "en");
                $xtpl->assign("USER_DATEFORMAT", '('.$timedate->get_user_date_format().')');
                $xtpl->assign("CALENDAR_DATEFORMAT", $timedate->get_cal_date_format());
                $xtpl->assign("EDIT_FIELD_VALUE", $select_row_curr_field_value);
                $xtpl->assign("CELL_WIDTH", $col_width);
                $xtpl->assign("THEME", $theme);
                $xtpl->parse("main.".$section_name.".merge_cell_edit_date");
                break;
            default :
                break;
        }

        //render a column for each selected record to merge
        foreach ($merge_ids_array as $id) {
            $xtpl->assign("CELL_WIDTH", $col_width);
            $field_name=null;
            switch ($field_check) {
                case ('bool') :
                    if ($mergeBeanArray[$id]-> $field_array['name'] == '1' || $mergeBeanArray[$id]-> $field_array['name'] == 'yes' || $mergeBeanArray[$id]-> $field_array['name'] == 'on') {
                        $xtpl->assign("FIELD_VALUE", " checked");
                    } else {
                        $xtpl->assign("FIELD_VALUE", "");
                    }
                    $field_name="main.".$section_name.".merge_cell_field_value_checkbox";
                    //$xtpl->parse("main.".$section_name.".merge_cell_field_value_checkbox");
                    break;
                case ('multienum'):
                	$opts = array();
					if(isset($field_array['multi_select_group'])) {
						$grp = $field_array['multi_select_group'];
						foreach($temp_field_array as $fa) {
							if(isset($fa['multi_select_group']) && $fa['multi_select_group'] == $grp)
								if(isset($mergeBeanArray[$id]->$fa['name']))
									$opts[] = $mergeBeanArray[$id]->$fa['name'];
						}
					}
					$opts = array_unique($opts);
					sort($opts);
					$disp_opts = array();
					foreach($opts as $o) if($o)
						$disp_opts[] = $app_list_strings[$field_array['options']][$o];
					display_field_value(implode(', ', $disp_opts));
					$field_name="main.".$section_name.".merge_cell_field_value";
                	break;
                case ('enum') :
                    if ($mergeBeanArray[$id]-> $field_array['name'] != '' and isset($field_array['options']) and isset($app_list_strings[$field_array['options']][$mergeBeanArray[$id]-> $field_array['name']])) {
                        display_field_value( $app_list_strings[$field_array['options']][$mergeBeanArray[$id]-> $field_array['name']]);
                    } else {
                        display_field_value($mergeBeanArray[$id]-> $field_array['name']);
                    }
                    $field_name="main.".$section_name.".merge_cell_field_value";
                    //$xtpl->parse("main.".$section_name.".merge_cell_field_value");
                    break;
                case ('relate') :
                case ('link') :
                    $related_name=false;
                    if (empty($mergeBeanArray[$id]-> $field_array['name']) && !empty($mergeBeanArray[$id]-> $field_array['id_name'])) {
                       $related_name=get_related_name($field_array,$mergeBeanArray[$id]-> $field_array['id_name']);
                       if ($related_name !== false) {
                            $mergeBeanArray[$id]-> $field_array['name']=$related_name;
					   }
                    }
               	    display_field_value($mergeBeanArray[$id]-> $field_array['name']);
                    $field_name="main.".$section_name.".merge_cell_field_value";
                    //$xtpl->parse("main.".$section_name.".merge_cell_field_value");
                    break; 
                default :
                    display_field_value($mergeBeanArray[$id]-> $field_array['name']);
                    //$xtpl->assign("FIELD_VALUE", $mergeBeanArray[$id]-> $field_array['name']);
                    $field_name="main.".$section_name.".merge_cell_field_value";
                    //$xtpl->parse("main.".$section_name.".merge_cell_field_value");
                    break;
            }

            $json_data = array ('field_name' => $col_name, 'field_type' => $field_check,);

            //add an array of fields/values to the json array
            //for setting all the values for merge
            if ($field_check == 'relate' or $field_check == 'link') {
				$temp_array = Array ();
                $json_data['popup_fields'] = Array ($field_array['name'] => $mergeBeanArray[$id]-> $field_array['name'], $field_array['id_name'] => $mergeBeanArray[$id]-> $field_array['id_name'],);
			} else if($field_check == 'multienum') {
				$json_data['field_value'] = $opts;
            } else {
                $json_data['field_value'] = $mergeBeanArray[$id]-> $field_array['name'];
            }
            $encoded_json_data = $json->encode($json_data);
            $xtpl->assign('ENCODED_JSON_DATA', $encoded_json_data);
            $xtpl->parse($field_name);            
        }

        $xtpl->parse("main.".$section_name);
        $field_count ++;
    }
}

$header_cols= array();
foreach ($merge_ids_array as $id) {
  $td="<td width='$col_width' valign='top' class='dataLabel' align='left'><input type='button' class='button' id='$id' onclick=\"change_primary(this,'{$id}');\" value='{$mod_strings['LBL_CHANGE_PARENT']}'";
  //disabled the Set as primary Button if the record cannot be a primary or if the primary is a primary only. 
  if($ACL_array[$id]["edit"]==false || $only==true){
    $td.=" disabled='disabled'";
  }
  if (count($merge_ids_array) > 1) {
    $td.="&nbsp;|<a id='remove_$id' onclick=\"remove_me(this,'{$id}');\" href='#' class='listViewPaginationLinkS1'>{$mod_strings['LBL_REMOVE_FROM_MERGE']}</a>";
  }
  $td.="</td>";
  $header_cols[]=$td;
}

if ($diff_field_count>0) {
    $xtpl->assign("DIFF_HEADER","<tr height='20'><td colspan=2><strong>{$mod_strings['LBL_DIFF_COL_VALUES']}</strong></td>".implode(' ',$header_cols)."</tr>");
    $xtpl->assign("SIMILAR_HEADER","<tr height='20'><td colspan=20><strong>{$mod_strings['LBL_SAME_COL_VALUES']}</strong></td></tr>");
    $xtpl->assign("GROUP_PARTITION","<tr height=3><td colspan=20' class='listViewHRS1'></td></tr>");
} else {
    $xtpl->assign("SIMILAR_HEADER","<tr height='20'><td colspan=2><strong>{$mod_strings['LBL_SAME_COL_VALUES']}</strong></td>".implode(' ',$header_cols)."</tr>");
}       
$merge_verify=$mod_strings['LBL_DELETE_MESSAGE'].'\\n'; 
foreach ($merge_records_names as $name) {
	$merge_verify.= str_replace('&#039;', "'", $name)."\\n";
}
$merge_verify.='\\n'.$mod_strings['LBL_PROCEED'];
$xtpl->assign("MERGE_VERIFY",$merge_verify);

global $beanList;
//Jenny - Bug 8386 - The object_name couldn't be found because it was searching for
// 'Case' instead of 'aCase'.
if ($focus->merge_bean->object_name == 'Case')
    $focus->merge_bean->object_name = 'aCase';

$mod=array_search($focus->merge_bean->object_name,$beanList);
$mod_strings = return_module_language($current_language, $mod);

//add javascript for required fields enforcement.
require_once('include/javascript/javascript.php');
$javascript = new javascript();
$javascript->setFormName('EditView');
$javascript->setSugarBean($focus->merge_bean);
$javascript->addAllFields('');
if (isset($focus->merge_bean->field_defs['team_name'])) {
    $javascript->addFieldGeneric('team_name', 'varchar', $app_strings['LBL_TEAM'] ,'true');    
}
$xtpl->assign("VALIDATION_JS", $javascript->getScript());

$xtpl->parse("main");
$xtpl->out("main");

/*
 * function truncates values to max_data_legth and adds the complete value as hover text.
 */
function display_field_value($value) {
    global $xtpl, $max_data_length, $mod_strings;
    $length= function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length-$max_data_length > 3) {
        if (function_exists('mb_substr')) {
            $xtpl->assign("FIELD_VALUE", mb_substr($value,0,$max_data_length).'...');
        } else {
	        $xtpl->assign("FIELD_VALUE", substr($value,0,$max_data_length).'...');                    
		}
    } else {
        $xtpl->assign("FIELD_VALUE", $value);                        
    }
    $xtpl->assign("HOVER_TEXT", $mod_strings['LBL_MERGE_VALUE_OVER'] .': ' . $value);                    
}
/*
 * implements the rules that decide which fields will participate in a merge.
 */
function show_field($field_def) {
    global $filter_for_valid_editable_attributes,$invalid_attribute_by_name;  
    //field in invalid attributes list? 
    if (isset($invalid_attribute_by_name[$field_def['name']])) {
       return false;
    }
    //field has 'duplicate_merge property set to disabled?'
    if (isset($field_def['duplicate_merge']) ) {
    	if ($field_def['duplicate_merge']=='disabled' or $field_def['duplicate_merge']==false) {
            return false;
        }
        if ($field_def['duplicate_merge']=='enabled' or $field_def['duplicate_merge']==true) {
            return true;        
        }
    }
    
    //field has auto_increment set to true do not participate in merge.
    //we have a unique index on that field.
    if (isset($field_def['auto_increment']) and $field_def['auto_increment']==true) {
        return false;
    }
    
    //set required attribute values in $field_def
    if (!isset($field_def['source']) or empty($field_def['source'])) {
        $field_def['source']='db';
    }
    
    if (!isset($field_def['dbType']) or empty($field_def['dbType']) and isset($field_def['type'])) {
        $field_def['dbType']=$field_def['type'];
    }
     
    foreach ($filter_for_valid_editable_attributes as $attribute_set) {
        $b_all=false;
        foreach ($attribute_set as $attr=>$value) {
            if (isset($field_def[$attr]) and $field_def[$attr]==$value) {
                $b_all=true;
            } else {
                $b_all=false;
                break;
            }       
        }
        if ($b_all) {
            return true;
        }
    }
    return false;
}
/* if the attribute of type relate and name is empty fetch using the vardef entries.
 * 
 */
function get_related_name($field_def,$id_value) {
    
    if (!empty($field_def['rname']) && !empty($field_def['id_name']) && !empty($field_def['table'])) {
        if (!empty($id_value)) {
          	$query = "select ".$field_def['rname']." from " .$field_def['table'] ." where id='$id_value'";
            $result=$GLOBALS['db']->query($query);
            $row=$GLOBALS['db']->fetchByAssoc($result);
            if (!empty($row[$field_def['rname']])) {
            	return $row[$field_def['rname']];
            }
        }
    }
    return false;
}

function process_ACL(&$merge_ids_array, &$ACL_array){
    $prim_sec_ids=find_primary_secondary($ACL_array);
    //contain the ID of the primary Only record which will be the primary record
    $primary_only=false;
    //contain the ID of the record wanted as primary by the user if can be primary
    $primary_user=false;
    $primary=false;
    //only=true if the primary is a primary only (for disable the set as primary button)
    $only=false;
	//Search primary and Primary_ONLY
    foreach($prim_sec_ids["primary"] as $id=>$va){
		if($va){
			if($ACL_array["primary"]==$id){
			    $primary_user=$id;
			}else{
				$primary=$id;
			}
			//if record is primary only
			if(!$prim_sec_ids["secondary"][$id]){
				//2 primary_ONLY records => error
				if($primary_only!=false){
					$ACL_array["error"]=$GLOBALS['mod_strings']['ERR_ACL_PRIMARY_RESTRICTION'];
					return false;
				}
				$primary_only=$id;
			}
		}
	}
	//IF no Primary => ERROR
	if($primary_only==false && $primary==false && $primary_user==false){
		$ACL_array["error"]=$GLOBALS['mod_strings']['ERR_ACL_NO_PRIMARY'];
        return false;
	}
	else if($primary_only!=false){
		$primary=$primary_only;
		$only=true;
	}
	//Take the record choose by the user as primary if possible. (useful if we come from "search duplicate") 
	else if($primary_user!=false){
		$primary=$primary_user;
	}
    $ACL_array["primary"]=$primary;
    foreach($prim_sec_ids["secondary"] as $id=>$va){
        //if is a secondary which is not the primary
    	if($va && $id!=$primary){
        	$merge_ids_array[]=$id;
        }
        //if is not a secondary and not a primary
        if(!$va && $id!=$primary){

        }
    }
    //if there is no secondary.
    if(!count($merge_ids_array)){
    	$ACL_array["error"]=$GLOBALS['mod_strings']['ERR_ACL_NO_SECONDARY'];
        return false;
    }
    return $only;
}

function build_ACL_array($base_id,&$merge_ids_array){
	global $current_user;
	$ACL_array["error"]=false;
    $focus = new MergeRecord();
    $focus->load_merge_bean($_REQUEST['merge_module'], true, $base_id);
    //Set the ACL_array for the records set as primary by the user.
    $ACL_array["primary"]=$base_id;
    $ACL_array[$base_id]["name"]=$focus->merge_bean->name;
    $ACL_array[$base_id]["view"]=ACLController::checkAccess($focus->merge_bean->module_dir,'view', $focus->merge_bean->isOwner($current_user->id));
    $ACL_array[$base_id]["edit"]=ACLController::checkAccess($focus->merge_bean->module_dir,'edit', $focus->merge_bean->isOwner($current_user->id));
    $ACL_array[$base_id]["delete"]=ACLController::checkAccess($focus->merge_bean->module_dir,'delete', $focus->merge_bean->isOwner($current_user->id));
    if(!$ACL_array[$base_id]["delete"] && !$ACL_array[$focus->merge_bean->id]["edit"]){
        //view set to false if ACL view access = false or if you cannot edit and delete the record.
        //The record will not be show for the merge
    	$ACL_array[$base_id]["view"]=false;
    }
    //Set the ACL_array for all the other records set as secondary by the user.
    foreach($merge_ids_array as $k=>$id){
        $mergeBeanArray[$id] = new $focus->merge_bean_class();
        $mergeBeanArray[$id]->retrieve($id);
    	$ACL_array[$id]["name"]=$mergeBeanArray[$id]->name;
        $ACL_array[$id]["view"]=ACLController::checkAccess($focus->merge_bean->module_dir,'view', $mergeBeanArray[$id]->isOwner($current_user->id));
        $ACL_array[$id]["edit"]=ACLController::checkAccess($focus->merge_bean->module_dir,'edit', $mergeBeanArray[$id]->isOwner($current_user->id));
        $ACL_array[$id]["delete"]=ACLController::checkAccess($focus->merge_bean->module_dir,'delete', $mergeBeanArray[$id]->isOwner($current_user->id));
        if(!$ACL_array[$id]["delete"] && !$ACL_array[$id]["edit"]){
            $ACL_array[$id]["view"]=false;
        }	
    }
    array_splice($merge_ids_array,0);
    $only=process_ACL($merge_ids_array, $ACL_array);
    return array($ACL_array, $only);
	
}
//this function return an array containing all the ID records in Primary and Secondary. set to true if can be a primary or a secondary.  
function find_primary_secondary(&$ACL_array){
	//flags stay to false if there is no primary or no secondary => error
	$flag1=false;
	$flag2=false;
	$return=array("primary"=>false,"secondary"=>false);
	foreach($ACL_array as $id=>$value){
		if($id!="error" && $id!="primary"){
			$return["primary"][$id]=false;
			$return["secondary"][$id]=false;
			if($ACL_array[$id]["edit"]==true && $ACL_array[$id]["view"]==true){
			    $return["primary"][$id]=true;
			    $flag1=true;
			}
			if($ACL_array[$id]["delete"]==true && $ACL_array[$id]["view"]==true){
				$return["secondary"][$id]=true;
                $flag2=true;
			}
		}
	}
	if($flag1==false){
		$ACL_array["error"]=$GLOBALS['mod_strings']['ERR_ACL_NO_PRIMARY'];
	}
	if($flag2==false){
		$ACL_array["error"]=$GLOBALS['mod_strings']['ERR_ACL_NO_SECONDARY'];
	}
	return $return;
	
}

?>
