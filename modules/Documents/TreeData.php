<?php
 if(!defined('sugarEntry'))define('sugarEntry', true);
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
require_once('include/ytree/Node.php');



//function returns an array of objects of Node type.
function get_node_data($params,$get_array=false) {
    $ret=array();
    $click_level=$params['TREE']['depth'];
    $subcat_id=$params['NODES'][$click_level]['id'];
    $cat_id=$params['NODES'][$click_level-1]['id'];
    $href=true;
    if (isset($params['TREE']['caller']) and $params['TREE']['caller']=='Documents' ) {
        $href=false;
    }
	$nodes=get_documents($cat_id,$subcat_id,$href);
	foreach ($nodes as $node) {
		$ret['nodes'][]=$node->get_definition();
	}
	$json = new JSON(JSON_LOOSE_TYPE);
	$str=$json->encode($ret);
	return $str;
}

/*
 *  
 *
 */
 function get_category_nodes($href_string){
    $nodes=array();
    global $mod_strings;
    global $app_list_strings;
    $query="select distinct category_id, subcategory_id from documents where deleted=0 order by category_id, subcategory_id";
    $result=$GLOBALS['db']->query($query);
    $current_cat_id=null;
    $cat_node=null;
    while (($row=$GLOBALS['db']->fetchByAssoc($result))!= null) {

        if (empty($row['category_id'])) {
            $cat_id='null';
            $cat_name=$mod_strings['LBL_CAT_OR_SUBCAT_UNSPEC'];
        } else {
            $cat_id=$row['category_id'];
            $cat_name=$app_list_strings['document_category_dom'][$row['category_id']];
        }            
        if (empty($current_cat_id) or $current_cat_id != $cat_id) {
            $current_cat_id = $cat_id;
            if (!empty($cat_node)) $nodes[]=$cat_node;
            
            $cat_node = new Node($cat_id, $cat_name);
            $cat_node->set_property("href", $href_string);
            $cat_node->expanded = true;
            $cat_node->dynamic_load = false;
        } 

        if (empty($row['subcategory_id'])) {
            $subcat_id='null';
            $subcat_name=$mod_strings['LBL_CAT_OR_SUBCAT_UNSPEC'];
        } else {
            $subcat_id=$row['subcategory_id'];
            $subcat_name=$app_list_strings['document_subcategory_dom'][$row['subcategory_id']];
        }            
        $subcat_node = new Node($subcat_id, $subcat_name);
        $subcat_node->set_property("href", $href_string);
        $subcat_node->expanded = false;
        $subcat_node->dynamic_load = true;
        
        $cat_node->add_node($subcat_node);
    }    
    if (!empty($cat_node)) $nodes[]=$cat_node;

    return $nodes;
 }
 
function get_documents($cat_id, $subcat_id,$href=true) {
    $nodes=array();
    $href_string = "javascript:select_document('doctree')";
    $query="select * from documents where deleted=0";
    if ($cat_id != 'null') {
        $query.=" and category_id='$cat_id'";
    } else {
        $query.=" and category_id is null";
    }
        
    if ($subcat_id != 'null') {
        $query.=" and subcategory_id='$subcat_id'";
    } else {
        $query.=" and subcategory_id is null";
    }
    $result=$GLOBALS['db']->query($query);
    $current_cat_id=null;
    while (($row=$GLOBALS['db']->fetchByAssoc($result))!= null) {
        $node = new Node($row['id'], $row['document_name']);
        if ($href) {
            $node->set_property("href", $href_string);
        }
        $node->expanded = true;
        $node->dynamic_load = false;
        
        $nodes[]=$node;
    }
    return $nodes;
}
?>
