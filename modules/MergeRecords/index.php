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
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
$merge_module = array_get_default($_REQUEST, 'merge_module', '');
$record = array_get_default($_REQUEST, 'record', '');
$base_id = null;

$merge_ids = array_get_default($_REQUEST, 'merged_ids', array());
$final_merge_ids = array();

$change_parent = array_get_default($_REQUEST, 'change_parent', '0');
$change_parent_id = array_get_default($_REQUEST, 'change_parent_id', '');

$remove = array_get_default($_REQUEST, 'remove', '0');
$remove_id = array_get_default($_REQUEST, 'remove_id', '');

if ($change_parent == '1' && ! empty($change_parent_id)) {
    $base_id = $change_parent_id;

    foreach ($merge_ids as $id) {
        if ($id != $base_id)
            $final_merge_ids[] = $id;
    }

    //add the existing parent to merged_id array.
    $final_merge_ids[] = $record;

} elseif ($remove == '1' && ! empty($remove_id)) {
    $base_id = $record;

    foreach ($merge_ids as $id) {
        if ($id != $remove_id)
            $final_merge_ids[] = $id;
    }

} else {
    $uids = array_get_default($_REQUEST, 'list_uids', '');
    $uids = implode(';', array_unique(array_filter(explode(';', $uids))));
    $uids = explode(';', $uids);

    if (sizeof($uids) > 0) {
        $base_id = $uids[0];

        foreach ($uids as $id) {
            if ($id != $base_id)
                $final_merge_ids[] = $id;
        }
    }
}

require('modules/MergeRecords/MergeRecordView.php');
$view = new MergeRecordView($merge_module, $base_id, $final_merge_ids);
$view->show();

?>