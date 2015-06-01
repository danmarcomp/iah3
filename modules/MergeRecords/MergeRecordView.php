<?php
require_once('include/layout/forms/EditableForm.php');
require_once('include/layout/forms/FormGenerator.php');

class MergeRecordView {

    /**
     * @var string
     */
    private $module;

    /**
     * @var array
     */
    private $mod_strings;

    /**
     * @var string
     */
    private $model_name;

    /**
     * @var array
     */
    private $ids;

    /**
     * @var string
     */
    private $base_record_id;

    /**
     * @var null|RowResult
     */
    private $base_record;

    /**
     * @param string $module
     * @param string $base_id
     * @param array $ids
     */
    public function __construct($module, $base_id, array $ids) {
        $this->module = $module;
        $this->model_name = AppConfig::module_primary_bean($module);
        $this->ids = $ids;
        $this->loadLanguageStrings();
        $this->setBaseRecordId($base_id);
        $this->setBaseRecord();
    }

    /**
     * @param string $value
     */
    public function setBaseRecordId($value) {
        $this->base_record_id = $value;
    }

    public function setBaseRecord() {
        $this->base_record = $this->loadRecord($this->base_record_id);
    }

    public function show() {
        global $mod_strings, $app_strings, $pageInstance;

        $full_acl_array = $this->buildAclArray();
        $this->checkAcl($full_acl_array[0]);

        $records = 1;
        $merged_ids_inputs = '';
        $merge_records_names = array();
        $secondary_records = array();

        foreach ($this->ids as $id) {
            $secondary_records[$id] = $this->loadRecord($id);
            $merge_records_names[] = $secondary_records[$id]->getField('name');
            $merged_ids_inputs .= "<input type='hidden' name='merged_ids[]' value='$id'>";
            $records ++;
        }

        $form = new EditableForm('editview', 'MergeDuplicates');
        $col_width = floor(80 / $records) .'%';
        $data = array('merge_row_similar' => '', 'merge_row_diff' => '');

        foreach ($this->base_record->fields as $name => $def) {

            if ($this->canShowField($def)) {
                $merged_fields = '';

                foreach ($this->ids as $id) {
                    $json_data = array ('field_name' => $name, 'field_type' => $def['type'], 'field_value' => $secondary_records[$id]->getField($name));

                    if ($def['type'] == 'ref') {
                        if (isset($def['source']['prefetched']) && $def['source']['prefetched'] == true) {
                            $json_data['field_name'] = $def['id_name'];
                            $json_data['field_value'] = $secondary_records[$id]->getField($def['id_name']);
                        } else {
                            $json_data['id_value'] = $secondary_records[$id]->getField($def['id_name']);
                        }
                    } elseif ($def['type'] == 'multienum' && is_array($json_data['field_value'])) {
                        $json_data['field_value'] =  implode('^,^', $json_data['field_value']);
                    }

                    $json = getJSONobj();
                    $hover_text = $mod_strings['LBL_MERGE_VALUE_OVER'] .': ' . $secondary_records[$id]->getField($name);
                    $merged_fields .= '<td width="' .$col_width. '" valign="top" class="dataField" ><button title="'.$hover_text.'" onclick=\'copy_value(' .$json->encode($json_data). ');\' class="input-button input-outer" type="button" style="width: 2em"><div class="input-icon icon-prev"></div></button>&nbsp;'. $secondary_records[$id]->getField($name, null, true). '</td>';
                }

                $label = $name;
                if (isset($this->mod_strings[$def['vname']]))
                    $label = $this->mod_strings[$def['vname']];

                $required_symbol = '';
                if (isset($def['required']) && $def['required'] == true)
                    $required_symbol = "<span class='required'>" .$app_strings['LBL_REQUIRED_SYMBOL']. "</span>";

                $label_cell = '<td width="20%"  valign="top" class="dataField" >' .$label. '&nbsp;' .$required_symbol. '</td>';
                $edit_field = '<td width="' .$col_width. '" valign="top" class="dataField">' .$form->renderField($this->base_record, $name). '</td>';

                //Prcoess locaton of the field - if values are different show field in first section else 2nd
                $section_name = $this->getSection($this->base_record->getField($name), $secondary_records, $name);
                $data[$section_name] .= '<tr height="20">' . $label_cell . $edit_field . $merged_fields . '</tr>';
            }
        }

        $header_cols = array();
        //$only = TRUE if the primary is a primary only => disable the "set as primary" button for all the other
        $only = $full_acl_array[1];

        foreach ($this->ids as $id) {
            if ($full_acl_array[0][$id]["edit"] == false || $only == true)
                $b_disable ="disabled='disabled'";
            else
            	$b_disable = '';

            $td="<td width='{$col_width}' valign='top' class='dataLabel' align='left'><button type='button' class='input-button input-outer' id='{$id}' onclick=\"change_primary('{$id}');\" {$b_disable}><span class=\"input-label\">{$mod_strings['LBL_CHANGE_PARENT']}</span></button>";

            //disabled the Set as primary Button if the record cannot be a primary or if the primary is a primary only.

            if (count($this->ids) > 1)
                $td .= "&nbsp;&nbsp;|&nbsp;&nbsp;<a id='remove_{$id}' onclick=\"remove_me('{$id}');\" href='#' class='listViewPaginationLinkS1'>{$mod_strings['LBL_REMOVE_FROM_MERGE']}</a>";

            $td .= "</td>";
            $header_cols[] = $td;
        }

        $headers = array('diff' => '', 'similar' => '', 'group_partition' => '');
        if (! empty($data['merge_row_diff'])) {
            $headers['diff'] = "<tr height='20'><td colspan=2><strong>{$mod_strings['LBL_DIFF_COL_VALUES']}</strong></td>".implode(' ',$header_cols)."</tr>";
            $headers['similar'] = "<tr height='20'><td colspan='20'><strong>{$mod_strings['LBL_SAME_COL_VALUES']}</strong></td></tr>";
            $headers['group_partition'] = "<tr height=3><td colspan='20' class='listViewHRS1'></td></tr>";
        } else {
            $headers['similar'] = "<tr height='20'><td colspan=2><strong>{$mod_strings['LBL_SAME_COL_VALUES']}</strong></td>".implode(' ',$header_cols)."</tr>";
        }

        $merge_verify = $mod_strings['LBL_DELETE_MESSAGE'].'\\n';
        foreach ($merge_records_names as $name) {
            $merge_verify .= str_replace('&#039;', "'", $name)."\\n";
        }
        $merge_verify .= '\\n'.$mod_strings['LBL_PROCEED'];

        $pageInstance->add_js_include('modules/MergeRecords/merge_duplicates.js', null, LOAD_PRIORITY_FOOT);
        $form->exportIncludes();

        echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_MERGE_RECORDS_WITH'].": ".$this->base_record->getField('name'), true);
        echo $this->getTemplate($merged_ids_inputs, $data, $headers, $merge_verify);
    }

    /**
     * Get merge page template
     *
     * @param string $merged_ids_inputs
     * @param array $data
     * @param array $headers
     * @param string $save_message
     * @return string
     */
    private function getTemplate($merged_ids_inputs, $data, $headers, $save_message) {
        global $mod_strings, $app_strings;

        $html = <<<EOQ
            <form name="MergeDuplicates" method="POST" onsubmit="return send_merge_form('{$save_message}');">
            <input type="hidden" name="module" value="MergeRecords">
            <input type="hidden" name="record" value="{$this->base_record_id}">
            <input type="hidden" name="merge_module" value="{$this->module}">
            <input type="hidden" name="action">
            <input type="hidden" name="return_module" value="{$this->module}">
            <input type="hidden" name="return_action" value="index">
            <input type="hidden" name="change_parent" value="0">
            <input type="hidden" name="change_parent_id" value="">
            <input type="hidden" name="remove" value="0">
            <input type="hidden" name="remove_id" value="">
            {$merged_ids_inputs}
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                <td align="left" style="padding-bottom: 2px;">
                <button class="form-button input-outer" onclick="return send_merge_form('{$save_message}');" tabindex="" style="" title="{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_TITLE']}" accessKey="{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_KEY']}">
                    <div class="input-icon left icon-accept"></div><span
                    	class="input-label">{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_LABEL']}</span>
                </button>
                <button onclick="return SUGAR.ui.cancelEdit(document.forms.MergeDuplicates);" tabindex="" style="" class="form-button input-outer" type="button" title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}">
                    <div class="input-icon left icon-cancel"></div><span
                    	class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span>
                </button>
                </td>
                <td align="right" nowrap><span class="required">{$app_strings['LBL_REQUIRED_SYMBOL']}</span> {$app_strings['NTC_REQUIRED']}</td>
            </tr>
            </table>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" >
            <tr height="20"><td>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabform">
            {$headers['diff']}
            {$data['merge_row_diff']}
            {$headers['group_partition']}
            {$headers['similar']}
            {$data['merge_row_similar']}
            </td></tr></table>
            <br/>
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="left" style="padding-bottom: 2px;">
            <button class="form-button input-outer" onclick="return send_merge_form('{$save_message}');" tabindex="" style="" title="{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_TITLE']}" accessKey="{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_KEY']}">
                <div class="input-icon left icon-accept"></div><span
                	class="input-label">{$mod_strings['LBL_SAVE_MERGED_RECORD_BUTTON_LABEL']}</span>
            </button>
            <button onclick="return SUGAR.ui.cancelEdit(document.forms.MergeDuplicates);" tabindex="" style="" class="form-button input-outer" type="button" title="{$app_strings['LBL_CANCEL_BUTTON_TITLE']}" accessKey="{$app_strings['LBL_CANCEL_BUTTON_KEY']}">
                <div class="input-icon left icon-cancel"></div><span
                	class="input-label">{$app_strings['LBL_CANCEL_BUTTON_LABEL']}</span>
            </button>
            </td></tr>
            </table>
            </td></tr>
            </table>
            </form>
EOQ;

        return $html;
    }

    private function loadLanguageStrings() {
        $this->mod_strings = return_module_language(AppConfig::language(), $this->module);
    }

    /**
     * @param string $id
     * @return null|RowResult
     */
    private function loadRecord($id) {
        $lq = new ListQuery($this->model_name, null, array('acl_checks' => array('view', 'edit', 'delete')));
        $lq->addColumnFields();
        $lq->addFields($lq->base_model->getExtraFields());
        $ret = $lq->queryRecord($id);
        if (! $ret || $ret->failed)
            return null;
        $this->formatLoadResult($ret);

        return $ret;
    }

    /**
     * Format result after loading record
     *
     * @param RowResult $result
     */
    private function formatLoadResult(RowResult &$result) {
        $gen = FormGenerator::html_form($this->model_name, new FormLayout(array('type' => 'editview')));
        $gen->formatResult($result);
    }

    /**
     * Check ACL error and new base record ID
     *
     * @param array $acl_array
     */
    private function checkAcl($acl_array) {
        //set the new primary record (changed by the ACL process if neeeded)
        if ($acl_array['primary'] != $this->base_record_id) {
            $this->setBaseRecordId($acl_array['primary']);
            $this->setBaseRecord();
        }

        if($acl_array["error"])
            $this->goBack($acl_array["error"]);
    }

    /**
     * Redirect to base module ListView
     *
     * @param string $message
     */
    private function goBack($message) {
        $GLOBALS['log']->warn($message);
        header("Location: async.php?module={$this->module}&action=index");
        die();
    }

    /**
     * Get field section name (diff or similar)
     *
     * @param mixed $base_value
     * @param array $merged_records
     * @param string $field_name
     * @return string
     */
    function getSection($base_value, $merged_records, $field_name) {
        $section_name = 'merge_row_similar';

        foreach ($this->ids as $id) {
            if ($merged_records[$id]->getField($field_name) == $base_value) {
                $section_name = 'merge_row_similar';
            } else {
                $section_name = 'merge_row_diff';
                break;
            }
        }

        return $section_name;
    }

    /**
     * @return array
     */
    private function buildAclArray() {
        $acl_array = array();
        $only = array();
        $base_model_data = $this->base_record;

        if ($base_model_data) {
            $acl_array["error"] = false;
            //Set the ACL_array for the records set as primary by the user.
            $acl_array["primary"] = $this->base_record_id;
            $acl_array[$this->base_record_id]["name"] = $base_model_data->getField('name');
            $acl_array[$this->base_record_id]["view"] = ($base_model_data->getAclAllowed('view') || $base_model_data->acl['view']);
            $acl_array[$this->base_record_id]["edit"] = ($base_model_data->getAclAllowed('edit') || $base_model_data->acl['edit']);
            $acl_array[$this->base_record_id]["delete"] = ($base_model_data->getAclAllowed('delete') || $base_model_data->acl['delete']);

            if(! $acl_array[$this->base_record_id]["delete"] && ! $acl_array[$this->base_record_id]["edit"]) {
                //view set to false if ACL view access = false or if you cannot edit and delete the record.
                //The record will not be show for the merge
                $acl_array[$this->base_record_id]["view"] = false;
            }

            if (! $acl_array[$this->base_record_id]["view"])
                $acl_array["error"] = $GLOBALS['mod_strings']['LBL_NOT_MERGED_MESSAGE'];

            //Set the ACL_array for all the other records set as secondary by the user.
            for ($i = 0; $i < sizeof($this->ids); $i++) {
                $merge_model_data = $this->loadRecord($this->ids[$i]);
                $acl_array[$this->ids[$i]]["name"] = $merge_model_data->getField('name');
                $acl_array[$this->ids[$i]]["view"] = ($merge_model_data->getAclAllowed('view') || $merge_model_data->acl['view']);
                $acl_array[$this->ids[$i]]["edit"] = ($merge_model_data->getAclAllowed('edit') || $merge_model_data->acl['edit']);
                $acl_array[$this->ids[$i]]["delete"] = ($merge_model_data->getAclAllowed('delete') || $merge_model_data->acl['delete']);

                if(! $acl_array[$this->ids[$i]]["delete"] && ! $acl_array[$this->ids[$i]]["edit"])
                    $acl_array[$this->ids[$i]]["view"] = false;

                if (empty($acl_array["error"]) && ! $acl_array[$this->ids[$i]]["view"])
                    $acl_array["error"] = $GLOBALS['mod_strings']['LBL_NOT_MERGED_MESSAGE'];
            }

            array_splice($this->ids, 0);
            $only = $this->processAcl($this->ids, $acl_array);
        }

        return array($acl_array, $only);
    }

    /**
     * @param array $merge_ids_array
     * @param array $acl_array
     * @return bool
     */
    private function processAcl(&$merge_ids_array, &$acl_array){
        $prim_sec_ids = $this->findPrimarySecondary($acl_array);
        //contain the ID of the primary Only record which will be the primary record
        $primary_only = false;
        //contain the ID of the record wanted as primary by the user if can be primary
        $primary_user = false;
        $primary = false;
        //only=true if the primary is a primary only (for disable the set as primary button)
        $only = false;

        //Search primary and Primary_ONLY
        foreach ($prim_sec_ids["primary"] as $id => $va) {
            if ($va){
                if ($acl_array["primary"] == $id) {
                    $primary_user = $id;
                } else {
                    $primary = $id;
                }
                //if record is primary only
                if (! $prim_sec_ids["secondary"][$id]) {
                    //2 primary_ONLY records => error
                    if ($primary_only != false) {
                        $acl_array["error"] = $GLOBALS['mod_strings']['ERR_ACL_PRIMARY_RESTRICTION'];
                        return false;
                    }
                    $primary_only = $id;
                }
            }
        }

        //IF no Primary => ERROR
        if ($primary_only == false && $primary == false && $primary_user == false) {
            $acl_array["error"] = $GLOBALS['mod_strings']['ERR_ACL_NO_PRIMARY'];
            return false;
        } elseif ($primary_only != false){
            $primary = $primary_only;
            $only = true;
        } elseif($primary_user != false){
            //Take the record choose by the user as primary if possible. (useful if we come from "search duplicate")
            $primary = $primary_user;
        }

        $acl_array["primary"] = $primary;

        foreach ($prim_sec_ids["secondary"] as $id => $va) {
            //if is a secondary which is not the primary
            if ($va && $id != $primary) {
                $merge_ids_array[] = $id;
            }

            //if is not a secondary and not a primary
            if (! $va && $id != $primary) {}
        }

        //if there is no secondary.
        if (! count($merge_ids_array)) {
            $acl_array["error"] = $GLOBALS['mod_strings']['ERR_ACL_NO_SECONDARY'];
            return false;
        }

        return $only;
    }

    /**
     * Return an array containing all the ID records in Primary and Secondary.
     * Set to true if can be a primary or a secondary.
     *
     * @param array $acl_array
     * @return array
     */
    private function findPrimarySecondary(&$acl_array){
        //flags stay to false if there is no primary or no secondary => error
        $flag1 = false;
        $flag2 = false;
        $return = array("primary" => false, "secondary" => false);

        foreach($acl_array as $id => $value) {
            if($id != "error" && $id != "primary") {
                $return["primary"][$id] = false;
                $return["secondary"][$id] = false;

                if($acl_array[$id]["edit"] == true && $acl_array[$id]["view"] == true){
                    $return["primary"][$id] = true;
                    $flag1 = true;
                }

                if($acl_array[$id]["delete"] == true && $acl_array[$id]["view"] == true){
                    $return["secondary"][$id] = true;
                    $flag2 = true;
                }
            }
        }

        if($flag1 == false){
            $acl_array["error"] = $GLOBALS['mod_strings']['ERR_ACL_NO_PRIMARY'];
        }

        if($flag2 == false){
            $acl_array["error"] = $GLOBALS['mod_strings']['ERR_ACL_NO_SECONDARY'];
        }

        return $return;
    }

    /**
     * Implements the rules that decide which fields will participate in a merge
     *
     * @param $field_def
     * @return bool
     */
    private function canShowField($field_def) {
        //filter condition for fields in vardefs that can participate in merge.
        $filter_for_valid_editable_attributes = array (
            array('type' => 'name', 'source' => array('type' => 'db')),
            array('type' => 'datetime', 'source' => array('type' => 'db')),
            array('type' => 'varchar', 'source' => array('type' => 'db')),
            array('type' => 'enum', 'source' => array('type' => 'db')),
            array('type' => 'text', 'source' => array('type' => 'db')),
            array('type' => 'date', 'source' => array('type' => 'db')),
            array('type' => 'time', 'source' => array('type' => 'db')),
            array('type' => 'int', 'source' => array('type' => 'db')),
            array('type' => 'long', 'source' => array('type' => 'db')),
            array('type' => 'double', 'source' => array('type' => 'db')),
            array('type' => 'float', 'source' => array('type' => 'db')),
            array('type' => 'short', 'source' => array('type' => 'db')),
            array('type' => 'ref'),
            array('type' => 'multienum'),
            array('dbType' => 'varchar', 'source' => array('type' => 'db')),
            array('dbType' => 'double', 'source' => array('type' => 'db'))
        );

        //following attributes will be ignored from the merge process.
        $invalid_attribute_by_name= array('date_entered' => 'date_entered', 'date_modified' => 'date_modified', 'modified_user_id' => 'modified_user_id',
            'modified_user' => 'modified_user', 'created_by_user' => 'created_by_user', 'created_by' => 'created_by', 'deleted' => 'deleted', 'exchange_rate' => 'exchange_rate');

        //field in invalid attributes list?
        if (isset($invalid_attribute_by_name[$field_def['name']]))
            return false;

        if (isset($field_def['link']) || (isset($field_def['editable']) && $field_def['editable'] == false)
            || (isset($field_def['updateable']) && $field_def['updateable'] == false)
            || (! empty($field_def['multi_select_group']) && ! isset($field_def['multi_select_count'])) ) {

            return false;
        }

        //field has 'duplicate_merge property set to disabled?'
        if (isset($field_def['duplicate_merge']) ) {
            if ($field_def['duplicate_merge'] == 'disabled' || $field_def['duplicate_merge'] == false) {
                return false;
            } elseif ($field_def['duplicate_merge'] == 'enabled' || $field_def['duplicate_merge'] == true) {
                return true;
            }
        }

        //field has auto_increment set to true do not participate in merge.
        //we have a unique index on that field.
        if (isset($field_def['auto_increment']) && $field_def['auto_increment'] == true)
            return false;

        //set required attribute values in $field_def
        if (! isset($field_def['source']) || empty($field_def['source']))
            $field_def['source'] = 'db';

        if (! isset($field_def['dbType']) || empty($field_def['dbType']) && isset($field_def['type']))
            $field_def['dbType'] = $field_def['type'];

        foreach ($filter_for_valid_editable_attributes as $attribute_set) {
            $b_all = false;

            foreach ($attribute_set as $attr => $value) {
                if (isset($field_def[$attr]) && $field_def[$attr] == $value) {
                    $b_all = true;
                } else {
                    $b_all = false;
                    break;
                }
            }
            if ($b_all)
                return true;
        }

        return false;
    }
}
