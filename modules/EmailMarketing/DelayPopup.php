<?php
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
require_once('include/layout/forms/FormGenerator.php');

$email_id = array_get_default($_REQUEST, 'email_id', '');
$list_id = array_get_default($_REQUEST, 'list_id', '');

$params = array(
    'type' => 'editview',
    'base_module' => 'EmailMarketing',
    'buttons_position' => 'bottom',
    'editable' => true,
);

$params['sections'] = array(
    array(
        'label_position' => 'top',
        'columns' => 2,
        'widths' => array('50%'),
        'elements' => array('dripfeed_delay', 'dripfeed_delay_unit')
    ),
);

$layout = new FormLayout($params);
$form = 'DelayForm';
$panel_id = $list_id . '-outer';
$onsubmit = 'return submitForm("'.$list_id.'");';
$layout->addFormHooks(array('onsubmit' => $onsubmit), false);
$hidden = array('list_id' => $list_id, 'module' => 'EmailMarketing', 'action' => 'SaveDripFeedDelay',
    'email_id' => $email_id, 'in_popup' => 1);
$layout->addFormHiddenFields($hidden);

$buttons = array(
    'save' => array(
        'vname' => 'LBL_SAVE_BUTTON_LABEL',
        'accesskey' => 'LBL_SAVE_BUTTON_KEY',
        'order' => 1,
        'icon' => 'icon-accept',
        'type' => 'button',
        'onclick' => $onsubmit
    ),
    'cancel' => array(
        'type' => 'button',
        'vname' => 'LBL_CANCEL_BUTTON_LABEL',
        'accesskey' => 'LBL_CANCEL_BUTTON_KEY',
        'order' => 5,
        'icon' => 'icon-cancel',
        'onclick' => 'return popup_dialog.close();',
        'params' => array(
            'format' => 'html',
        ),
    ),
);
$layout->addFormButtons($buttons);
$layout->addScriptInclude('modules/EmailMarketing/delay_popup.js');

$model = new ModelDef('EmailMarketing', array('dripfeed_delay', 'dripfeed_delay_unit'));
$lq = new ListQuery($model);
$lq->addFilterPrimaryKey($email_id);
$result = $lq->runQuerySingle();

$gen = FormGenerator::html_form($model, $layout, $form, $form);
$gen->formatResult($result);
$gen->renderForm($result);
$out = $gen->getResult();
$gen->exportIncludes();

echo $out;
?>
