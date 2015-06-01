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

require_once('modules/Contacts/widgets/RelatedRecordsWidget.php');
$base_model = array_get_default($_REQUEST, 'base_model');
$selected_contact_id = array_get_default($_REQUEST, 'selected_contact_id');
$selected_account_id = array_get_default($_REQUEST, 'selected_account_id');
$selected_opportunity_id = array_get_default($_REQUEST, 'selected_opportunity_id');

$input = $_REQUEST;
$selected = array();
$contact_id = null;
$contact_update = null;

if ($base_model == 'Opportunity') {
    if (isset($input['newaccount']))
        unset($input['newaccount']);
}

if (empty($input['create_account']) && $selected_account_id)
    $selected['account_id'] = $selected_account_id;

if (empty($input['create_opportunity']) && $selected_opportunity_id)
    $selected['opportunity_id'] = $selected_opportunity_id;

if (empty($input['create_contact']) && $selected_contact_id) {
    $contact_id = $selected_contact_id;
} elseif (! empty($input['primary_contact_id']) || ! empty($input['primary_contact_for'])) {

    $contact_id = (! empty($input['primary_contact_id'])) ? $input['primary_contact_id'] : $input['primary_contact_for'];

} elseif (! empty($input['account_id'])) {

    $account_result = ListQuery::quick_fetch('Account', $input['account_id']);
    $contact_id = $account_result->getField('primary_contact_id');

} elseif ($base_model == 'Contact') {
    $contact_update = RowUpdate::blank_for_model('Contact');
    $contact_update->loadRequest();

    if ($contact_update->save())
        $contact_id = $contact_update->getPrimaryKeyValue();
}

if (! $contact_update && $contact_id) {
    $contact_result = ListQuery::quick_fetch('Contact', $contact_id, true);
    $contact_update = RowUpdate::for_result($contact_result);
}

if ($contact_update) {
    $widget = new RelatedRecordsWidget();
    $widget->init(array());
    $widget->loadUpdateRequest($contact_update, $input);
    $widget->saveAfterDuplicates($contact_update, $selected);
}

if ($contact_id) {
    return array('perform', array('module' => 'Contacts', 'action' => 'DetailView', 'record' => $contact_id, 'record_perform' => 'view', 'layout' => 'Standard'));
} else {
    return array('perform', array('module' => 'Contacts', 'action' => 'index'));
}
?>