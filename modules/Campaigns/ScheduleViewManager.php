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


require_once('include/ListView/ListViewManager.php');

class ScheduleViewManager extends ListViewManager {

    /**
     * @var string
     */
    var $campaign_id;

    /**
     * @var string
     */
    var $mode;

    /**
     * Email marketing module strings
     *
     * @var array
     */
    var $marketing_strings;

    function __construct($context = 'listview', $params=null) {
        $this->show_create_button = false;
        $this->marketing_strings = return_module_language(AppConfig::language(), 'EmailMarketing');
        parent::__construct($context, $params);
    }

    function loadRequest() {
        if (isset($_REQUEST['record']))
            $this->campaign_id = $_REQUEST['record'];
        if (isset($_REQUEST['mode']))
            $this->mode = $_REQUEST['mode'];

        parent::loadRequest();
    }

    function initModuleView($module) {
        $this->module = $module;
        $this->layout_module = $module;

        $this->model_name = 'EmailMarketing';

		if(AppConfig::setting("display.list.{$this->model_name}.disabled"))
			return false;

        // force creation of standard records (like base currency), but only if caching is enabled
        AppConfig::db_check_standard_records($this->model_name, true);

        $this->layout_name = 'Schedule';
        $this->view_name = 'Schedule';
        $this->view_type = 'list';
        $this->filter_name = 'Browse';

        return true;
    }

    function getTitle() {
        global $mod_strings;
        $title = array();

        if ($this->mode == 'test')  {
            $title['main'] = $this->marketing_strings['LBL_MODULE_SEND_TEST'];
            $title['message'] = $this->marketing_strings['LBL_SCHEDULE_MESSAGE_TEST'];
        } else {
            $title['main'] = $this->marketing_strings['LBL_MODULE_SEND_EMAILS'];
            $title['message'] = $this->marketing_strings['LBL_SCHEDULE_MESSAGE_EMAILS'];
        }

        return $title;
    }

    function renderTitle() {
        $title = $this->getTitle();
        echo get_module_title($this->module, $title['main'], $this->context != 'popup');
        echo '<h3>' .$title['message']. ':</h3>';
    }

    /**
     * Get List Formatter object
     *
     * @return ListFormatter
     */
	function &getFormatter() {
        if (!$this->formatter) {
            $fmt_params['show_checks'] = true;
            $fmt_params['show_mass_update'] = false;
            $fmt_params['show_title'] = false;
            $fmt_params['show_advanced_filter'] = false;
            $fmt_params['show_filter'] = false;
            $fmt_params['show_select_all_selector'] = false;

            $fmt = new ListFormatter($this->model_name, null, $this->list_id, $this->context, $fmt_params);

            if (!$this->list_id) $this->list_id = $fmt->list_id;

            $hidden = $this->getHiddenFields();
            $hidden['record'] = $this->campaign_id;
            $hidden['mode'] = $this->mode;
            $hidden['mass'] = '';
            $hidden['return_module'] = $_REQUEST['return_module'];
            $hidden['return_action'] = $_REQUEST['return_action'];
            $hidden['return_record'] = $_REQUEST['return_record'];
            $fmt->getMassUpdate()->addHiddenFields($hidden);
            $fmt->getFilterForm()->addHiddenFields($hidden);

            $add = $this->getOverrideFilters();
            if($add) $fmt->getFilterForm()->addOverrideFilters($add);
            $fmt->addButtons($this->getButtons());

            $this->formatter =& $fmt;
        }
        
        return $this->formatter;        
	}
	
	function render() {
        if($this->layout_perform == 'edit') {
            $this->layoutEditorRender();
            return;
        }

        $fmt =& $this->getFormatter();

        if(! $fmt->loadLayout($this->view_name, $this->view_type, $this->layout_module)) {
            return false;
        }

        //FIXME add records where prospect.list_type='test' for 'test' mode
        //Also need to show Targets list on Schedule ListView form
		$fmt->list_query->addFilterClauses($this->getAdditionalClauses());

        if (!$this->async_list) {
            echo '<div id="' . $this->list_id . '-outer">';
        }

        if($this->show_title) {
            $this->renderTitle();
        }

        $offset = array_get_default($_REQUEST, 'list_offset', 0);
        $order_by = array_get_default($_REQUEST, 'list_order_by', null);
        $this->list_result = $fmt->getQueryResult($offset, null, $order_by);

        $fmt->formatResult($this->list_result);
        $fmt->outputHtml($this->list_result);

        if (!$this->async_list) {
            echo '</div>';
        }

        global $pageInstance;
        $primary = $this->is_primary ? 1 : 0;
        $pageInstance->add_js_literal("sListView.init('{$this->list_id}', $primary);", null, LOAD_PRIORITY_FOOT);
	}

    function getButtons() {
        $buttons = array();

        $form_id = $this->list_id .'-ListUpdate';

        $buttons['create'] = array(
            'label' => translate('LBL_SCHEDULE_BUTTON_LABEL', 'EmailMarketing'),
            'perform' => "var upd_form=SUGAR.ui.getForm('".$form_id."'); var uList=sListView.getUids('".$this->list_id."'); var cnt = uList.getCount(); if (!cnt) {alert(app_string('LBL_LISTVIEW_NO_SELECTED')); return false;} ; return SUGAR.ui.sendForm(upd_form, {'action':'QueueCampaign', 'mass':uList.getList()});",
            'type' => 'submit',
            'params' => array(
                'toplevel' => 1,
            ),
        );

        $buttons['cancel'] = array(
            'label' => translate('LBL_CANCEL_BUTTON_LABEL'),
            'perform' => "return SUGAR.ui.cancelEdit('".$form_id."');",
            'type' => 'submit',
            'params' => array(
                'toplevel' => 1,
            ),
        );

        return $buttons;
    }

	function getAdditionalClauses() {
        $clauses = array(
            "campaign" => array(
                "value" => $this->campaign_id,
                "field" => "campaign_id"
            ),
        );

		if ($this->mode == 'test') {
			$lq = new ListQuery('EmailMarketing', array('~join.email_marketing_id'), array('link_name' => 'prospectlists'));
			$lq->addSimpleFilter('list_type', 'test');
			$lq->addSimpleFilter('campaign_id', $this->campaign_id);
			$res = $lq->runQuery();
			$ids = array();
			foreach ($res->rows as $row) {
				$ids[] = $row['~join.email_marketing_id'];
			}
			$clauses['all_prospects'] = array(
				'operator' => 'or',
				'multiple' => array(
					array(
		                'value' => 1,
				        'field' => 'all_prospect_lists'
					),
				),
			);
			if (!empty($ids)) {
				$clauses['all_prospects']['multiple'][] = array(
					'value' => $ids,
					'field' => 'id',
				);
			}
        }

        return $clauses;
    }
}
?>
