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
require_once('include/ListView/ListLayoutEditor.php');
require_once('modules/ProspectLists/DynamicProspects.php');

class TargetWidget extends FormTableSection {

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'dynamic_targets';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();

		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result, $context);
		}

        return '';
    }
	
	function getRequiredFields() {
		return array();
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult $row_result, $context) {
        $body =  '<div id="le_section_dynamic_filters">'.$this->renderTargetsSections($gen, $row_result, $context).'</div>';

        return $body;
	}

    /**
     * Render targets sections
     *
     * @param HtmlFormGenerator $gen
     * @param RowResult $row_result
     * @param array $context
     * @return string
     */
    function renderTargetsSections(HtmlFormGenerator &$gen, RowResult $row_result, $context) {
        $targets = $this->getTargets();
        $sections = '';

        foreach ($targets as $name => $params) {
            $model_name = AppConfig::module_primary_bean($params['module']);
            $field_input = $this->getFieldInput($name, $model_name);
            $label =  translate($params['label'], $this->model->module_dir);

            $pl_id = $row_result->getField('id');
            $check_value = 0;
            $tbody_style = 'style="display: none;"';

            $layout_editor = new ListLayoutEditor();
            $layout_editor->initFromModel($model_name);
            $spec = array();
            $values = array();

            if ($pl_id) {
                $related_rec = $this->loadRelatedFilters($pl_id, $params['module']);

                if (! $related_rec->failed) {
                    $filters = $related_rec->getField('filters');
                    $filters = unserialize($filters);

                    if(! empty($filters['filters_spec']) && ! empty($filters['filters_values'])) {
                        $values = $filters['filters_values'];
                        $spec = $filters['filters_spec'];
                    }

                    $check_value = 1;
                    $tbody_style = '';
                }
            }

            if (empty($values))
                $values['filter_owner'] = 'all';

            $layout_editor->loadFilterOptions($spec);
            $layout_editor->loadFilterValues($values);
            $filter_rows = $layout_editor->encodeFilters();

            $dynamic_input = $this->getDynamicInput($name, $filter_rows);
            $checkbox_spec = array('name' => 'add_' . $name, 'onchange' => 'DynamicTargetList.toggleFilters("'.$name.'", this.getValue())');
            $check = $gen->form_obj->renderCheck($checkbox_spec, $check_value);

            $style = '';
            if ($name != 'targets')
                $style = 'style="margin-top: 0.5em;"';

            $sections .= <<<EOQ
                <table width="100%" cellspacing="0" cellpadding="0" border="0" id="le_section_{$name}_filters" class="tabForm" {$style}>
                <thead>
                    <tr>
                    <th align="left" colspan="1" class="dataLabel">
                    <h4 class="dataLabel">{$check}&nbsp;&nbsp;{$label}</h4></th>
                    </tr>
                </thead>
                <tbody id="{$name}-filters" {$tbody_style}>
                    <tr>
                    <td width="100%" colspan="1" class="dataField"><p class="topLabel">{$dynamic_input->getLabel()}</p>{$dynamic_input->renderHtml($gen, $row_result, array(), $context)}</td>
                    </tr>
                    <tr>
                    <td width="100%" colspan="1" class="dataField"><p class="topLabel">{$field_input->getLabel()}</p>{$field_input->renderHtml($gen, $row_result, array(), $context)}</td>
                    </tr>
                </tbody>
                </table>
EOQ;
        }

        return $sections;
    }

    /**
     * Instance dynamic list input for showing filters
     *
     * @param string $type: targets, contacts, leads, users
     * @param array $rows
     * @return DynamicListInput
     */
    function getDynamicInput($type, $rows = array()) {
        $input_params = array(
            'name' => $type . '_filters',
            'class' => 'listLayoutConfig',
            'renderer_class' => 'SUGAR.ui.FilterListRender',
            'require_group_attrib' => true,
            'max_depth' => '3',
            'width' => '550px',
            'label' => translate('LBL_FILTERS', $this->model->module_dir),
            'rows' => $rows,
            'cols' => array(
                array(
                    'name' => 'label',
                    'label' => translate('LBL_FIELD'),
                    'width' => '40%',
                    'render_method' => 'renderName'
                ),
                array(
                    'name' => 'filter',
                    'label' => translate('LBL_DEFAULT_VALUE'),
                    'width' => '60%',
                    'render_method' => 'renderFilter'
                ),
                array(
                    'name' => 'hidden',
                    'label' => translate('LBL_HIDE'),
                    'width' => '5%',
                    'format' => 'check'
                )
            )
        );

        $input = $this->getInputWidget('DynamicListInput', $input_params);

        return $input;
    }

    /**
     * Instance fields select box
     *
     * @param string $type: targets, contacts, leads, users
     * @param string $model
     * @return FieldSelectInput
     */
    function getFieldInput($type, $model) {
        $input_params = array(
            'name' => 'add_'.$type.'_filter',
            'label' =>  translate('LBL_ADD_FILTERS', $this->model->module_dir),
            'list_name' => $type . '_filters',
            'mode' => 'filters',
            'field_model' => $model
        );


        $input = $this->getInputWidget('FieldSelectInput', $input_params);

        return $input;
    }

    /**
     * Instance Input widget
     *
     * @param string $name - widget name
     * @param array $params
     * @return FormElement
     */
    function getInputWidget($name, $params) {
        $wspec = AppConfig::get_widget_spec($name);
        if(isset($wspec['params']))
            $params = array_merge($wspec['params'], $params);
        $input = new $wspec['class']($params, $this->model);
        $input->editable = true;

        return $input;
    }

    /**
     * Get possible targets types
     *
     * @return array
     */
    function getTargets() {
        return DynamicProspects::getTypes();
    }

    /**
     * Load related filters record
     *
     * @param string $prospect_list_id
     * @param string $related_type
     * @return RowResult
     */
    function loadRelatedFilters($prospect_list_id, $related_type) {
        return DynamicProspects::loadFilters($prospect_list_id, $related_type);
    }


	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $targets = $this->getTargets();

        foreach ($targets as $name => $params) {
            if (isset($input['add_' . $name]) && $input['add_' . $name] == 1) {
                $model = AppConfig::setting("modinfo.primary_beans." . $params['module']);
                $update->setRelatedData($this->id.'_'.$name.'_rows', $this->getFilters($name, $model, $input));
            }
        }
	}

	function validateInput(RowUpdate &$update) {
		return true;
	}

	function afterUpdate(RowUpdate &$update) {
        $targets = $this->getTargets();
        $pl_id = $update->getPrimaryKeyValue();

        foreach ($targets as $name => $params) {
            $result = $this->loadRelatedFilters($pl_id, $params['module']);
            $data = array();
            $row_updates = $update->getRelatedData($this->id.'_'.$name.'_rows');
            
            if (is_array($row_updates)) {

                if ($result->failed) {
                    $data = array(
                        'prospect_list_id' => $pl_id,
                        'related_type' => $params['module']
                    );
                    $lq = new ListQuery('dynamic_prospects');
                    $result = $lq->getBlankResult();
                }

                $data['filters'] = serialize($row_updates);
                $upd = new RowUpdate($result);
                $upd->set($data);
                $upd->save();
            } elseif(! $result->failed) {
                $upd = new RowUpdate($result);
                $upd->deleteRow();
            }
        }
	}

    /**
     * Get target's filters
     *
     * @param string $name - filter name
     * @param string $model_name - filter model
     * @param array $input - user input ($_REQUEST)
     * @return array
     */
    function getFilters($name, $model_name, $input) {
        $arrs = array();
        $key = $name.'_filters';

        if(isset($input[$key])) {
            $val = array();
            if(is_string($input[$key])) {
                $json = getJSONobj();
                $val = $json->decode($input[$key]);
                if(! is_array($val))
                    $val = array();
            } else if(is_array($input[$key])) {
                $val = $input[$key];
            }
            $arrs['filters_spec'] = $val;
        }

        $vals = array();
        if(isset($arrs['filters_spec']) && sizeof($arrs['filters_spec']) > 0) {
            $layout_editor = new ListLayoutEditor();
            $model = new ModelDef($model_name);
            $layout_editor->initFromModel($model);
            ListLayoutEditor::clean_filter_input($arrs['filters_spec']);
            $layout_editor->loadFilterOptions($arrs['filters_spec']);
            $layout_editor->filter_form->loadFilter($input, 'editor', 'fvalue_');
            ListLayoutEditor::clean_filter_prefix($arrs['filters_spec']);

            $arrs['filters_values'] = $layout_editor->filter_form->getArray();
            $vals = $arrs;
        }

        return $vals;
    }
}
?>