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
require_once('include/database/RowUpdate.php');

$model = new ModelDef('UserSignature');
$fields = array('name', 'user_id', 'signature', 'signature_html');

$upd = RowUpdate::for_model($model);
$upd->limitFields($fields);
$upd->loadRequest('', true, true);
$inputParams = $upd->getInput();

$lq = new ListQuery($model, $fields);
$lq->addPrimaryKey();
$lq->addAclFilter('edit');

if (! empty($_REQUEST['record'])) {
    $lq->addFilterPrimaryKey($_REQUEST['record']);
	$result = $lq->runQuerySingle();
	$method = 'replaceOption';
} else {
    $upd->new_record = true;
    $result = $lq->getBlankResult();
	$method = 'addOption';
}

if($upd->setOriginal($result)) {
    $upd->set($inputParams);
    $upd->save();
}

$json = getJSONObj();
$id = $json->encode($upd->getPrimaryKeyValue());
$name = $json->encode($upd->getField('name'));
echo <<<JS
<script type="text/javascript">
	var signatureList = SUGAR.ui.getFormInput(document.DetailForm, 'signature_id');
	signatureList.menu.getOptions().$method($id, $name);
	signatureList.setValue($id);
</script>
JS;
