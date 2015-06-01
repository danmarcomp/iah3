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
require_once('modules/Directory/DirectoryFilter.php');

class DirectoryViewManager extends ListViewManager {

    /**
     * @var string: Thumbnail, Detailed
     */
    var $view_format;

    /**
     * @var string (user ID)
     */
    var $record_id;

    function __construct($context = 'listview', $is_primary=false) {
        $this->show_create_button = false;
        parent::__construct($context, $is_primary);
    }

    function loadRequest() {
        if (isset($_REQUEST['layout']) && $_REQUEST['layout'] == 'Detailed') {
            $this->view_format = 'Detailed';
        } elseif (isset($_REQUEST['format'])) {
            $this->view_format = $_REQUEST['format'];
        } else {
            $this->view_format = 'Thumbnail';
        }

        if (isset($_REQUEST['record']))
            $this->record_id = $_REQUEST['record'];

        parent::loadRequest();
    }

    function initModuleView($module) {
        $this->module = $module;
        $this->layout_module = $module;
        $this->model_name = 'User';

		if(AppConfig::setting("display.list.{$this->model_name}.disabled"))
			return false;

        // force creation of standard records (like base currency), but only if caching is enabled
        AppConfig::db_check_standard_records($this->model_name, true);

        $layout_name = null;
        if ($this->view_format == 'Detailed') {
            $layout_name = 'Detailed';
        } elseif (! empty($_REQUEST['layout'])) {
            $layout_name = $_REQUEST['layout'];
        }

        $this->model_name = 'Directory';
        $this->loadLayoutInfo($layout_name);
        $this->model_name = 'User';

        return true;
    }

    function getTitle() {
        global $mod_strings;

        $format_params = $this->getFormatParams();
        $title = $mod_strings['LBL_MODULE_TITLE'] .': '. $format_params['title'];

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
            $fmt_params['auto_init_filter'] = false;
            $fmt_params['show_advanced_filter'] = true;

            $fmt = new ListFormatter($this->model_name, null, $this->list_id, $this->context, $fmt_params);
            if (!$this->list_id) $this->list_id = $fmt->list_id;
            $filter_form = new DirectoryFilter($fmt->list_query, 'FilterForm', $this->list_id . '-FilterForm');
            $fmt->setFilterForm($filter_form);
            $hidden = $this->getHiddenFields();
            $fmt->getMassUpdate()->addHiddenFields($hidden);
            $fmt->getFilterForm()->addHiddenFields($hidden);

            $add = $this->getOverrideFilters();
            if($add) $fmt->getFilterForm()->addOverrideFilters($add);
            $fmt->getQuery()->addFilterClauses($this->getAdditionalClauses());
            $fmt->addButtons($this->getButtons());

            $this->formatter =& $fmt;
        }
        
        return $this->formatter;        
	}
	
	
	function getNavParams() {
		$p = parent::getNavParams();
        $format_params = $this->getFormatParams();
        $p['limit'] = $format_params['rows'] * $format_params['cols'];
        return $p;
	}
	

	function renderResult(ListFormatter &$fmt, ListResult &$result) {
		if($this->format != 'html') return;
        $fmt->autoSuppressColumns();
        $fmt->loadMassUpdate();
        $mu_form = $fmt->massupdate->getFormBody($this->list_result, true);
        $fmt->outputTitleHeader();
        $this->outputBody($fmt, $this->list_result);
        echo $mu_form['body'];
	}
	

    /**
     * Show list body
     *
     * @param ListFormatter $fmt
     * @param ListResult $listResult
     * @return void
     */
    function outputBody(ListFormatter &$fmt, ListResult &$listResult) {
        $fmt->outputCustomOffsetPopup();

        echo '<table id="' . $fmt->list_id . '-main" class="' . $fmt->table_class . '" cellpadding="0" cellspacing="0" border="0" width="' . $fmt->table_width . '">';

        $fmt->have_rows = (!$listResult->total_counted || $listResult->total_count);
        $fmt->calcColSpan();
        $fmt->outputNavigation($listResult);

        if (! $fmt->show_compact_empty || $fmt->have_rows) {
            echo '<tbody>';
            echo $this->renderRow($this->list_result);
            echo '</tbody>';

        }

        echo '</table>';
    }

    /**
     * Render table row
     *
     * @param ListResult $listResult
     * @return string
     */
    function renderRow(ListResult &$listResult) {
        global $mod_strings;
        $format_params = $this->getFormatParams();
        $layout_cols = $format_params['cols'];
        $html = "";
        $counter = $layout_cols;
        $cell_width = intval(100 / $layout_cols) . '%';
        $vrows = 0;

        foreach($listResult->formatted_rows as $id => &$row) {
            if($counter >= $layout_cols) {
            	$row_cls = ($vrows % 2) ? 'evenListRowS1' : 'oddListRowS1';
            	if($vrows) $html .= '</tr>';
                $html .= "<tr class='$row_cls'>";
                $counter = 0;
                $vrows ++;
            }

            $user_data = $row;
            $user_data['cell_width'] = $cell_width;

            $image_path = CacheManager::get_location('images/directory/', true);
            if (isset($listResult->rows[$id]['photo_filename']))
                $image_path .= $listResult->rows[$id]['photo_filename'];
            if(! file_exists($image_path) || is_dir($image_path)) {
            	$image_path = 'include/images/iah/unknown.png';
            }
            $user_data['image_url'] = $image_path;

            $online = User::is_online($id);
            $user_data['online_status_text'] = $mod_strings[$online ? 'LBL_ONLINE' : 'LBL_NOT_ONLINE'];
            $user_data['online_status_image'] = '<div class="input-icon icon-led' . ($online ? 'green': 'red') . '" title="' . $user_data['online_status_text'] . '"></div>';

            if ($this->view_format == 'Detailed') {
                $html .= $this->renderDetailedCell($user_data, $listResult->fields);
            } else {
                $html .= $this->renderThumbnailCell($user_data);                
            }

            $counter++;
        }

        if($vrows && $counter) {
            for(; $counter < $layout_cols; $counter++)
                $html .= "<td>&nbsp;</td>";
            $html .= "</tr>";
        }

        return $html;
    }

    /**
     * Render table cell for Thumbnail format
     *
     * @param array $user_data
     * @return string
     */
    function renderThumbnailCell($user_data) {
        $cell = <<<EOQ
            <td width="{$user_data['cell_width']}" style="padding: 0; vertical-align: top" align="center">
             <table cellpadding="0" cellspacing="0" border="0" width="100%" class="tabForm cardForm">
             <tr valign="middle"><td class="dataLabel" height="180">
                <div style="width: 75px; border: 1px solid black; margin: 0 auto 6pt auto">
                <a href="index.php?module=Directory&action=index&format=Detailed&record={$user_data['id']}">
                <img border="0" src="{$user_data['image_url']}" width="75" height="90" style="vertical-align: bottom" /></a>
                </div>
                <div style="text-align: center">
                <b>{$user_data['full_name']} {$user_data['online_status_image']}</b>
                <p class="listViewAddLine">{$user_data['email1']}</p>
                <p class="listViewAddLine">{$user_data['phone_work']}</p>
                </div>
            </td></tr></table>
            </td>
EOQ;

        return $cell; 
    }

    /**
     * Render table cell for Detailed (Business Card) format
     *
     * @param array $user_data
     * @param array $fields
     * @return string
     */
    function renderDetailedCell($user_data, $fields) {
        $cell = <<<EOQ
            <td width="{$user_data['cell_width']}" style="padding: 0" align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="100%" class="tabForm cardForm">
                <tr valign="middle">
                    <td class="dataLabel" width="92" valign="top">
                        <div style="margin-left: 5px; margin-top: 5px; margin-right: 10px; border: 1px solid black; width: 75px">
                        <img src="{$user_data['image_url']}" width="75" height="90" style="vertical-align: bottom" />
                        </div>
                    </td>
                    <td class="dataField">
                        <table width="100%" height="190" cellpadding="0" cellspacing="0" border="0">
                        <tr><td class="dataLabel" colspan="2">
                            <h5 class="dataLabel">{$user_data['full_name']} ({$user_data['user_name']})</h5>
                        </td></tr>
                        <tr><td width="30%" class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'department')}&nbsp;</td>
                        <td class="dataField">{$user_data['department']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'title')}&nbsp;</td>
                        <td class="dataField">{$user_data['title']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'location')}&nbsp;</td>
                        <td class="dataField">{$user_data['location']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'email1')}&nbsp;</td>
                        <td class="dataField">{$user_data['email1']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'email2')}&nbsp;</td>
                        <td class="dataField">{$user_data['email2']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'phone_work')}&nbsp;</td>
                        <td class="dataField">{$user_data['phone_work']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'phone_fax')}&nbsp;</td>
                        <td class="dataField">{$user_data['phone_fax']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'phone_mobile')}&nbsp;</td>
                        <td class="dataField">{$user_data['phone_mobile']}</td></tr>
                        <tr><td class="dataLabel" nowrap>{$this->getFieldLabel($fields, 'sms_address')}&nbsp;</td>
                        <td class="dataField"><a href="mailto:{$user_data['sms_address']}">{$user_data['sms_address']}</a></td></tr>
                        </table>
                    </td>
                </tr>
                </table>
            </td>
EOQ;

        return $cell;
    }

    /**
     * Get translated field label
     *
     * @param array $fields
     * @param string $name
     * @return mixed
     */
    function getFieldLabel($fields, $name) {
        $vname = '';
        if (isset($fields[$name]['vname']))
            $vname = $fields[$name]['vname'];
        $mod = 'Users';
        $label = preg_replace('~:\s*$~', '', translate($vname, $mod));

        return $label;
    }

    /**
     * Get view format additional params
     *
     * @return array: ['cols', 'rows', 'title']
     */
    function getFormatParams() {
        global $mod_strings;

        $all_formats = array(
            'Thumbnail' => array('cols' => 4, 'rows' => 4, 'title' => $mod_strings['LNK_THUMBNAIL_VIEW']),
            'Detailed' => array('cols' => 2, 'rows' => 3, 'title' => $mod_strings['LNK_DETAILED_VIEW']),
        );

        return $all_formats[$this->view_format];
    }

    /**
     * Get additional filter clauses
     *
     * @return array
     */
    function getAdditionalClauses() {
        $clauses = array(
            "status" => array(
                "value" => "Inactive",
                "field" => "status",
                "operator" => "not_eq"
            ),
        );

        if (! empty($this->record_id)) {
            $clauses['record'] = array(
                "value" => $this->record_id,
                "field" => "id",
            );
        }

        return $clauses;
    }
}
?>