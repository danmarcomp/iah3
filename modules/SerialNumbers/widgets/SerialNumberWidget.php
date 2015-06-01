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


require_once('include/layout/forms/FormTableSection.php');

class SerialNumberWidget extends FormTableSection {

    /**
     * @var ListResult
     */
    var $list;

    /**
     * @var array
     */
    var $mod_strings;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'serial_numbers';
        $this->mod_strings = return_module_language(AppConfig::language(), 'SerialNumbers');
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

        $this->list = $this->loadList($row_result);
		$lstyle = $gen->getLayout()->getType();

		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}

		return $this->renderHtmlView();
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView() {
        global $gridline;
        //$mod_strings = return_module_language(AppConfig::language(), 'SerialNumbers');

        $body =  <<<EOQ
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView" style="margin-top: 0.5em">
            <tr><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <th align="left" colspan="2" class="tabDetailViewDL"><h4 class="tabDetailViewDL">{$this->mod_strings['LBL_SERIAL_NUMBERS']}</h4></th>
                </tr>
                <tr>
                    <td colspan="2">
                    <table width="100%" border='0' cellspacing='{$gridline}' cellpadding='0'>
                    <tr>
                    <td width="7%" class="tabDetailViewDL" style="text-align: left" NOWRAP>&nbsp;</td>
                    <td width="20%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_ITEM_NAME']}</td>
                    <td width="36%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_NOTES']}</td>
                    <td width="30%" class="tabDetailViewDL" style="text-align: left" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_NO']}</td>
                    <td width="7%" class="tabDetailViewDL" style="text-align: left" NOWRAP>&nbsp;</td>
                    </tr>
EOQ;

        if ($this->list != null) {
            foreach ($this->list->formatted_rows as $id => $row) {
                $body .= <<<EOQ
                    <tr>
                    <td class="tabDetailViewDF" style="text-align: left" NOWRAP>&nbsp;</td>
                    <td class="tabDetailViewDF" NOWRAP>{$row['item_name']}</td>
                    <td class="tabDetailViewDF" NOWRAP>{$row['notes']}</td>
                    <td class="tabDetailViewDF" NOWRAP>{$row['serial_no']}</td>
                    <td class="tabDetailViewDF" style="text-align: left" NOWRAP>&nbsp;</td>
                    </tr>
EOQ;

            }
        }

        $body .= "</table></td></tr></table></td></tr></table>";

        return $body;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        global $app_strings, $app_list_strings, $image_path;

        $body = <<<EOQ
            <input type="hidden" name="serial_updates" value="" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm" style="margin-top: 1em">
            <tr><td>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <th align="left" colspan="2" class="dataLabel"><h4 class="dataLabel">{$this->mod_strings['LBL_SERIAL_NUMBERS']}</h4></th>
                </tr>
                <tr>
                    <td colspan="2">
                    <table width="100%" border='0' cellspacing='0' cellpadding='0' id="instancesTable">
                    <tr>
                    <td width="7%" class="dataLabel" NOWRAP>&nbsp;</td>
                    <td width="20%" class="dataLabel" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_ITEM_NAME']}</td>
                    <td width="35%" class="dataLabel" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_NOTES']}</td>
                    <td width="30%" class="dataLabel" NOWRAP>{$this->mod_strings['LBL_LIST_SERIAL_NO']}</td>
                    <td width="8%" class="dataLabel" NOWRAP>&nbsp;</td>
                    </tr>
                    </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                    <br>
                    <input type='button' id='add_line' name='add_line' tabindex='25' class='input-button' value='{$this->mod_strings['LBL_BUTTON_ADD_LINE']}' onclick='serials_editor.addSerial({});' />
                   </td>
                </tr>
                </table>
            </td></tr>
            </table>
EOQ;

        if ($this->model->name == 'Asset') {
            $asset_id = $row_result->getField('id');
            $assembly_id = '';
        } else  {
            $asset_id = '';
            $assembly_id = $row_result->getField('id');
        }

        $json = getJSONobj();
        $row_remove_text = $app_strings['LNK_REMOVE'];
        $row_remove_img = "<img src='{$image_path}delete_inline.gif' align='absmiddle' alt='$row_remove_text' border='0' />";
        $item_name_opts = $json->encode($app_list_strings['serial_number_item_dom']);
        $save_url = 'index.php?module=SerialNumbers&action=Save&to_js=1';
        $quantity = sizeof($this->list->rows);

        if ($quantity > 0) {
            $serials = array();

            foreach ($this->list->rows as $id => $row) {
                $serials[] = array(
                    'instance_id' => $id,
                    'assembly_id' => $row['assembly_id'],
                    'asset_id' => $row['asset_id'],
                    'serial_no' => $row['serial_no'],
                    'item_name' => $row['item_name'],
                    'notes' => $row['notes'],
                    'deleted' => $row['deleted']
                );
            }

            $serials = $json->encode($serials);
        } else {
            $serials = '[]';
        }

        $js = <<<EOQ
            var row_remove_img = "{$row_remove_img}";
            var item_name_opts = {$item_name_opts};
            var image_path = "{$image_path}";
            var default_limit = 50;
            serials_editor.init("DetailForm", "instancesTable", "{$assembly_id}", "{$asset_id}", "{$this->model->name}", "{$row_result->getField('id')}", "{$save_url}");
            serials_editor.setSerials({$serials}, 0, {$quantity});
EOQ;

        $lang_js = 'LANGUAGE = {"app_strings":{"LNK_REMOVE":"'.$app_strings['LNK_REMOVE'].'"}};';

        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/SerialNumbers/serials.js');
        $layout->addScriptLiteral($lang_js);
        $layout->addScriptLiteral($js);

        return $body;
	}

    /**
     * Load serial numbers list
     *
     * @param RowResult $row_result
     * @return ListResult|null
     */
    function loadList(RowResult &$row_result) {
        $list = null;
        $filter_name = '';

        if ($this->model->name == 'Asset') {
            $filter_name = 'asset_id';
        } elseif ($this->model->name == 'SupportedAssembly') {
            $filter_name = 'assembly_id';
        }

        if ($filter_name != '') {
            $fields = array('id', 'serial_no', 'item_name', 'notes', 'asset_id', 'assembly_id', 'deleted');
            $lq = new ListQuery('SerialNumber', $fields);

            $clauses = array(
                "target" => array(
                    "value" => $row_result->getField('id'),
                    "field" => $filter_name
                ),
            );

            $lq->addFilterClauses($clauses);
            $list = $lq->runQuery();
            if (!$list->failed || isset($list->rows))
                $list = $this->formatList($list);
        }

        return $list;
    }

    /**
     * Format list data
     *
     * @param ListResult $list
     * @return ListResult
     */
    function formatList(ListResult &$list) {
        $fmt = new FieldFormatter('html');
        $fmt->primary_key = $list->primary_key;
        $fmt->module_dirs = $list->module_dirs;
        $list->formatted_rows = $fmt->formatRows($list->fields, $list->rows);

        return $list;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_numbers = array();

        if (! empty($input['serial_updates']))
            $upd_numbers['serials'] = $input['serial_updates'];

        $update->setRelatedData($this->id.'_rows', $upd_numbers);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

        if (isset($row_updates['serials'])) {
            $json = getJSONobj();
            $serials = $json->decode($row_updates['serials']);

            if (is_array($serials) && sizeof($serials) > 0) {
                foreach($serials as $row) {
                    $model = new ModelDef('SerialNumber');
                    $fields = array('id', 'serial_no', 'item_name', 'notes', 'asset_id', 'assembly_id', 'deleted');

                    $upd = RowUpdate::for_model($model);
                    $upd->limitFields($fields);

                    $lq = new ListQuery($model, $fields);
                    $lq->addPrimaryKey();
                    $lq->addAclFilter('edit');

                    if (! empty($row['instance_id'])) {
                        $lq->addFilterPrimaryKey($row['instance_id']);
                        $result = $lq->runQuerySingle();
                    } else {
                        $upd->new_record = true;
                        $result = $lq->getBlankResult();
                    }

                    if (empty($row['item_name']))
                        $row['item_name'] = 'System';

                    if ($update->model_name == 'Asset' && empty($row['asset_id'])) {
                        $row['asset_id'] = $update->getPrimaryKeyValue();
                    } elseif (empty($row['assembly_id'])) {
                        $row['assembly_id'] = $update->getPrimaryKeyValue();
                    }

                    if($upd->setOriginal($result)) {
                        $upd->set($row);
                        $upd->save();
                    }
                }
            }
        }
	}
	
}
?>
