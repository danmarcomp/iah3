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
require_once('modules/Forecasts/Forecast.php');

class WorksheetViewManager extends ListViewManager {

    /**
     * @var string
     */
    var $forecast_id;
    var $forecast;

    /**
     * @var string:
     * personal, team_individual, team_rollup
     */
    var $type;

    /**
     * Forecast time period start date
     *
     * @var string
     */
    var $period_start;

    /**
     * Forecast time period end date
     *
     * @var string
     */
    var $period_end;

    /**
     * Target user ID
     *
     * @var string
     */
    var $user_id = null;

    function __construct($context = 'listview', $params=null) {
        $this->show_create_button = false;
        parent::__construct($context, $params);
    }

    function loadRequest() {
        if (! empty($_REQUEST['record']))
            $this->loadRecordId($_REQUEST['record']);
        else if(! empty($_REQUEST['type']) && ! empty($_REQUEST['period_start']))
        	$this->loadTypePeriod($_REQUEST['type'], $_REQUEST['period_start'], array_get_default($_REQUEST, 'user_id'));

        $this->addHiddenFields(array(
            'record' => $this->forecast_id
        ));

        parent::loadRequest();
    }

    /**
     * Load forecast record data by ID
     *
     * @return bool
     */
    function loadRecordId($id) {
		$lq = new ListQuery('Forecast');
		$result = $lq->queryRecord($id);

		if ($result && ! $result->failed) {
			require_once('include/layout/FieldFormatter.php');
			$fmt = new FieldFormatter('html', 'editview');
			$result->formatted = $fmt->formatRow($result->fields, $result->row);
			$this->forecast = $result;
			$this->forecast_id = $result->getField('id');
			$this->type = $result->getField('type');
			$this->period_start = $result->getField('start_date');
			$this->period_end = $result->getField('end_date');
			$this->user_id = $result->getField('user_id');
			return true;
		}
		return false;
    }
    
    function loadTypePeriod($type, $period, $user_id=null) {
    	if(! $user_id) $user_id = AppConfig::current_user_id();
    	$forecast = new Forecast();
		$info = $forecast->getPeriodInfo($period);
		$lq = new ListQuery('Forecast', array('id'));
		$lq->addSimpleFilter('type', $type);
		$lq->addSimpleFilter('start_date', $info['start_date']);
		$lq->addSimpleFilter('user_id', $user_id);
		$result = $lq->runQuerySingle();
		if($result && ! $result->failed)
			return $this->loadRecordId($result->getField('id'));
		return false;
    }

    function initModuleView($module) {
    	if(! $this->forecast_id)
    		return false;
    	
        $this->module = $module;
        $this->layout_module = $module;

        $model = null;
        if ($this->type == 'personal') {
            $model = 'Opportunity';
        } elseif ($this->type == 'team_individual' || $this->type == 'team_rollup') {
            $model = 'Forecast';
        }

        $this->model_name = $model;
        if (! $this->model_name)
            return false;

		if(AppConfig::setting("display.list.{$this->model_name}.disabled"))
			return false;

        // force creation of standard records (like base currency), but only if caching is enabled
        AppConfig::db_check_standard_records($this->model_name, true);

        if ($this->type == 'personal') {
            $layout_name = "Personal";
        } else {
            $layout_name = "Team";
        }

        $this->layout_name = $layout_name;
        $this->view_name = $layout_name;
        $this->view_type = 'list';
        $this->filter_name = 'Browse';

        return true;
    }

    function getTitle() {
        global $mod_strings;
        $title = '';

        if ($this->type == 'personal') {
            $title = $mod_strings['LBL_FORECAST_OPPORTUNITIES_FORM_TITLE'];
        } elseif ($this->type == 'team_individual') {
            $title = $mod_strings['LBL_INDIVIDUAL_FORECASTS_FORM_TITLE'];
        } elseif ($this->type == 'team_rollup') {
            $title = $mod_strings['LBL_ROLL_UP_FORECASTS_FORM_TITLE'];
        }

        if ($this->user_id) {
			$user = ListQuery::quick_fetch_row('User', $this->user_id, array('_display'));
			if ($user)
				$prefix = $user['_display'];
			else
				$prefix = '';
        } else {
        	$prefix = $mod_strings['LBL_TOPLEVEL'];
        }

		$forecast = new Forecast();
		$period_name = $forecast->getPeriodName($this->period_start, true);
        $title .= ": $prefix ($period_name)";

        return $title;
    }

    function renderTitle() {
        $title = $this->getTitle();
        echo get_module_title($this->module, $title, $this->context != 'popup');
    }

    /**
     * Get List Formatter object
     *
     * @return ListFormatter
     */
	function &getFormatter() {
        if (!$this->formatter) {
            $fmt_params['show_checks'] = false;
            $fmt_params['show_mass_update'] = false;
            $fmt_params['show_title'] = false;
            $fmt_params['show_advanced_filter'] = false;

            if ($this->user_id || $this->forecast_id) {
                $fmt_params['show_filter'] = false;
            } else {
                $fmt_params['show_filter'] = true;
            }


            $fmt = new ListFormatter($this->model_name, null, $this->list_id, $this->context, $fmt_params);
            if (! $this->list_id) $this->list_id = $fmt->list_id;

            $fmt->setFilterForm(new FilterForm(new ListQuery('Forecast'), 'FilterForm', $this->list_id . '-FilterForm'));
            $fmt->filter_form->show_clear = false;

			$hidden = $this->getHiddenFields();
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

		$hidden = array(
			'type' => $this->type,
			'period_start' => $this->period_start,
		);
		$fmt->getMassUpdate()->addHiddenFields($hidden);

		$f = new Forecast;	
		if ($this->user_id || $this->forecast_id)
			$fmt->setTitle($this->printBreadcrumbs($this->user_id, $this->type != 'personal' || !$f->is_team_leader($this->user_id)));

        if(! $fmt->loadLayout($this->view_name, $this->view_type, $this->layout_module)) {
            return false;
        }
        if($this->filter_name)
            $fmt->getFilterForm()->setDefaultLayout($this->filter_name);

        //FIXME hardcoded for now because I don't know how to set filters values
        $_REQUEST['query'] = 1;
        $_REQUEST['period_start'] = $this->period_start;
        $_REQUEST['type'] = $this->type;

        $fmt->loadRequestFilter();
        $fmt->list_query->addFilterClauses($this->getAdditionalClauses());

        if (!$this->async_list) {
            echo '<div id="' . $this->list_id . '-outer">';
        }

        if($this->show_title) {
            $this->renderTitle();
        }

        $offset = array_get_default($_REQUEST, 'list_offset', 0);
        $order_by = array_get_default($_REQUEST, 'list_order_by', null);
        $this->overrideTypeFilter($fmt);
        $this->list_result = $fmt->getQueryResult($offset, null, $order_by);
        
		if ($this->debug)
			pr2($this->list_result->query, 'list query', true);

        $this->overrideTypeFilter($fmt, true);

        $fmt->formatResult($this->list_result);
        $fmt->outputHtml($this->list_result);

        echo $this->renderCommitPanel();

        if (!$this->async_list) {
            echo '</div>';
        }


        global $pageInstance;
        $primary = $this->is_primary ? 1 : 0;
        $pageInstance->add_js_literal("sListView.init('{$this->list_id}', $primary);", null, LOAD_PRIORITY_FOOT);
	}

    /**
     * Render html for Forecast Commit / Sales Quota panel
     *
     * @return string
     */
    function renderCommitPanel() {
        global $mod_strings;

        $forecast_bean = new Forecast();
        
		$period_name = $forecast_bean->getPeriodName($this->period_start, true);

		if ($this->type == 'personal') {
			$prompt = $mod_strings['LBL_PERSONAL_FORECAST_PROMPT'];
			$forecast_label =  $mod_strings['LBL_CURRENT_PERSONAL_FORECAST'];
			$quota_label = $mod_strings['LBL_CURRENT_PERSONAL_QUOTA'];
		} else {
			$prompt = $mod_strings['LBL_TEAM_FORECAST_PROMPT'];
			$forecast_label =  $mod_strings['LBL_CURRENT_TEAM_FORECAST'];
			$quota_label = $mod_strings['LBL_CURRENT_TEAM_QUOTA'];
		}

		$cparams = array('use_currency_decimals' => false, 'decimals' => 0, 'convert' => true, 'currency_symbol' => true);
		$last_forecast = currency_format_number($this->forecast->getField('forecast'), $cparams);
		$last_quote = currency_format_number($this->forecast->getField('quota'), $cparams);
		$cparams['currency_symbol'] = false;
		$total = currency_format_number($this->forecast->getField('total'), $cparams);

		$panel_type = 'commit';
		$commit_field = 'forecast';
		$title = $mod_strings['LBL_FORECAST_COMMIT_FORM_TITLE'];
		$button_label = $mod_strings['LBL_COMMIT_BUTTON_LABEL'];

		if (isset($this->user_id) && $this->user_id != AppConfig::current_user_id()) {
			if ($type == 'team_rollup') {
				$prompt = $mod_strings['LBL_TEAM_QUOTA_FIELD'];
				$panel_type = 'team_quota';
			} else {
				$prompt = $mod_strings['LBL_QUOTA_FIELD'];
				$panel_type = 'quota';
			}
			
			$commit_field = 'quota';
			$title = $mod_strings['LBL_SALES_QUOTA_FORM_TITLE'];
			$button_label = $mod_strings['LBL_BUTTON_SET_QUOTA_LABEL'];
		}

		$panel = '<p>&nbsp;</p>';
		$panel .= get_form_header($title, '', false);
		$sep = translate('LBL_SEPARATOR', 'app');

		$panel .= <<<EOQ
			<form name="CommitView" method="POST" onsubmit="return SUGAR.ui.sendForm(this);">
				<input type="hidden" name="module" value="Forecasts" />
				<input type="hidden" name="record" value="{$this->forecast->getField('id')}" />
				<input type="hidden" name="period_start" value="{$this->period_start}" />
				<input type="hidden" name="type" value="{$this->type}" />
				<input type="hidden" name="user_id" value="{$this->user_id}" />
				<input type="hidden" name="action" value="SaveCommit" />
			<table width="100%" border="0" cellspacing="0" cellpadding="0"  class="tabDetailView">
			<tr>
				<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_SUBJECT']}</td>
				<td class="tabDetailViewDF" noWrap>{$period_name}</td>
				<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_START_DATE']}</td>
				<td class="tabDetailViewDF" noWrap>{$this->forecast->getField('start_date', '', true)}</td>
				<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_END_DATE']}</td>
				<td class="tabDetailViewDF" noWrap>{$this->forecast->getField('end_date', '', true)}</td>
			</tr>
EOQ;

		if ($panel_type == 'commit') {
			$panel .= <<<EOQ
				<tr>
					<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_COMMIT_DATE']}</td>
					<td class="tabDetailViewDF" noWrap>{$this->forecast->getField('commit_date', '', true)}</td>
					<td class="tabDetailViewDL" noWrap width="15%">{$forecast_label}</td>
					<td class="tabDetailViewDF" noWrap>{$last_forecast}</td>
					<td class="tabDetailViewDL" noWrap width="15%">{$quota_label}</td>
					<td class="tabDetailViewDF" noWrap>{$last_quote}</td>
				</tr>
EOQ;
		}else {
			$panel .= <<<EOQ
				<tr>
					<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_QUOTA_DATE']}</td>
					<td class="tabDetailViewDF" noWrap>{$this->forecast->getField('quota_date', '', true)}</td>
					<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_LAST_QUOTA']}</td>
					<td class="tabDetailViewDF" noWrap>{$last_quote}</td>
					<td class="tabDetailViewDL" noWrap width="15%">&nbsp;</td>
					<td class="tabDetailViewDF" noWrap>&nbsp;</td>
				</tr><tr>
					<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_LAST_FORECAST_DATE']}</td>
					<td class="tabDetailViewDF" noWrap>{$this->forecast->getField('commit_date', '', true)}</td>
					<td class="tabDetailViewDL" noWrap width="15%">{$mod_strings['LBL_LAST_FORECAST']}</td>
					<td class="tabDetailViewDF" noWrap>{$last_forecast}</td>
					<td class="tabDetailViewDL" noWrap width="15%">&nbsp;</td>
					<td class="tabDetailViewDF" noWrap>&nbsp;</td>
				</tr>
EOQ;
		}
		
		$panel .= <<<EOQ
			</table>
			<table width="100%" border="0" cellspacing="0" cellpadding="0"  class="tabForm" style="margin-top: 0.5em">
				<tr>
					<td class="dataLabel" noWrap>{$prompt}{$sep}
						&nbsp;<input type=text size="20" name="{$commit_field}" class="input-text input-outer" value="{$total}" onchange="validateNumberInput(this, {blankAllowed: true, decimalPlaces:0});" />
						&nbsp;<button class="input-button input-outer" type="submit"><div class="input-icon icon-accept left"></div><span class="input-label">{$button_label}</span></button>
					</td>
				</tr>
			</table>
			</form>
EOQ;

        return $panel;
    }

    /**
     * Load Forecast record by user ID, type and period start date
     *
     * @param string $user_id
     * @param string $type
     * @return RowResult
     */
    function loadForecast($user_id, $type) {
        $fields = array('user', 'start_date', 'end_date', 'type',
            'commit_date', 'quota_date', 'forecast', 'quota', 'total');
        $lq = new ListQuery('Forecast', $fields);

        $clauses = array(
            "user" => array(
                "value" => $user_id,
                "field" => "user_id",
                "operator" => "eq"
            ),
            "type" => array(
                "value" => $type,
                "field" => "type"
            ),
            "start_date" => array(
                "value" => $this->period_start,
                "field" => "start_date"
            )
        );

        $lq->addFilterClauses($clauses);
        $forecast = $lq->runQuerySingle();

        if (! $forecast->failed) {
            $fmt = new FieldFormatter('html', 'editview');
            $forecast->formatted = $fmt->formatRow($lq->getFields(), $forecast->row);
        }

        return $forecast;
    }

    /**
     * Get additional filter clauses depends on forecast type
     *
     * @return array
     */
    function getAdditionalClauses() {
        $clauses = array();

        if ($this->user_id) {
            $user_id = $this->user_id;
        } else {
            $user_id = AppConfig::current_user_id();
        }

		if ($this->type == 'personal') {
            $clauses = array(
                "closed1" => array(
                    "value" => $this->period_start,
                    "field" => "date_closed",
                    "operator" => "gte"
                ),
                "closed2" => array(
                    "value" => $this->period_end,
                    "field" => "date_closed",
                    "operator" => "lte"
                ),
                "category" => array(
                    "value" => "Omitted",
                    "field" => "forecast_category",
                    "operator" => "not_eq"
                ),
                "user_id" => array(
                    "value" => $user_id,
                    "field" => "assigned_user_id",
                    "operator" => "eq"
                )
            );
        } elseif ($this->type == 'team_individual') {
			$team = User::get_team_list($user_id);

            $clauses = array(
                "user" => array(
                    "value" => $team,
                    "field" => "user_id",
                    "operator" => "in"
                ),
                "type" => array(
                    "value" => "personal",
                    "field" => "type"
                )
            );
        } elseif ($this->type == 'team_rollup') {
            $team = User::get_team_list($user_id, false, false);

            if (sizeof($team) > 0) {
                $clause1 = array(
                    array(
                        "value" => $team,
                        "field" => "user_id",
                        "operator" => "in"
                    ),
                    array(
                        "value" => 'team_rollup',
                        "field" => "type",
                    )
                );

                $multi_clause['team1'] = array(
                    'type' => 'filtergroup',
                    'operator' => 'AND',
                    'multiple' => $clause1
                );
            }
            
            $clause2 = array(
                array(
                    "value" => $user_id,
                    "field" => "user_id",
                    "operator" => "eq"
                ),
                array(
                    "value" => 'personal',
                    "field" => "type",
                )
            );

            $multi_clause['team2'] = array(
                'type' => 'filtergroup',
                'operator' => 'AND',
                'multiple' => $clause2
            );

            $clauses['team_rollup'] = array(
                'type' => 'filtergroup',
                'operator' => 'OR',
                'multiple' => $multi_clause
            );
        }
        
        return $clauses;
    }

    /**
     * Override type value for team forecasts
     *
     * @param ListFormatter $formatter
     * @param bool $reset
     * @return void
     */
    function overrideTypeFilter(&$formatter, $reset = false) {
        if ($reset) {
            $formatter->getFilterForm()->filter['type'] = $this->type;
        } elseif ($this->type == 'team_individual') {
            $formatter->getFilterForm()->filter['type'] = 'personal';
        } elseif ($this->type == 'team_rollup') {
            unset($formatter->getFilterForm()->filter['type']);
        }
    }
	
	function printBreadcrumbs($user_id, $suppress_last = false)
	{
		global $current_user;
		global $mod_strings;
		$ret = '';
		$user = new User;
		$user->retrieve($user_id);
		$leaders = array_reverse($user->getTeamLeaders());
		$lq = new ListQuery('User', array('id', '_display'));
		$lq->addFilterPrimaryKey($leaders);
		$names = array();
		foreach($lq->fetchAllRows() as $row) {
			$names[$row['id']] = $row['_display'];
		}
		$print = false;
		$n = count($leaders);
		foreach ($leaders as $i => $id) {
			if ($id == $current_user->id) {
				if($ret) $ret .= ' &laquo; ';
				$ret .= '<a href="index.php?module=Forecasts&action=WorksheetView&period_start=' . $this->period_start . '&type=team_rollup">' . $mod_strings['LBL_TOPLEVEL'] . '</a>';
				$print = true;
			}
			if ($ret) $ret .= ' &laquo; ';
			$name = array_get_default($names, $id, '?');
			if ((!$suppress_last || $i+1 < $n) && $print) {
				$ret .= '<a href="index.php?action=WorksheetView&module=Forecasts&user_id=' . $id . '&period_start=' . $this->period_start . '&type=team_rollup">' . $name . '</a>';
			} else {
				$ret .= $name;
			}
		}
		if ($ret) {
			$ret = $mod_strings['LBL_REPORTING_LINE'] . ' ' . $ret;
		}
		return $ret;
	}
}
?>
