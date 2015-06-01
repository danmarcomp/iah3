<?php

require_once 'include/ListView/ListFormatter.php';

class ActivityListFieldFormatter extends FieldFormatter {

	function formatRows(array &$model_fields, array &$rows, $union_key=null) {
        $unformatted = $rows;
        $ret = parent::formatRows($model_fields, $rows, $union_key);
        $lang_strings = AppConfig::setting("lang.dashlet_strings.current.ActivityLog.ActivityLogDashlet");

        foreach ($ret as $key => &$row) {
            $template = $this->formatStatus($row, $unformatted[$key], $lang_strings);
            $this->formatActivity($row, $unformatted[$key], $model_fields, $template);
        }

        return $ret;
    }

	function formatStatus(&$row, $unformatted_row, $lang_strings) {
        $status = $unformatted_row['status'];
        $date_start = $unformatted_row['moved_to'];
        $module = $row['module_name'];

        switch ($status) {
            case '';
                $formatted = '';
                break;
			case 'moved':
				$tpl = $lang_strings['LBL_MOVED'];
				$params = array($row['moved_to']);
				break;
            case 'converted':
				$tpl = $lang_strings['LBL_CONVERTED_TO'];
				$params = array($this->formatConverted($row, $unformatted_row));
                break;
            case 'created':
				if (!empty($row['converted_to_id'])) {
					$tpl = $lang_strings['LBL_CONVERTED_FROM'];
					$params  = array($this->formatConverted($row, $unformatted_row));
				} elseif ($date_start){
                    global $timedate;
                    $date_start = $timedate->to_display_date_time($date_start);
                    if ($module == 'ProjectTask')
                        $date_start = $timedate->to_display_date($date_start);
					$tpl = $lang_strings['LBL_SCHEDULED'];
					$params = array($date_start);
                } else {
					$tpl = $lang_strings['LBL_CREATED'];
					$params = array();
                }
				break;
            default:
				$tpl = $lang_strings['LBL_STATUS_DEFAULT'];
				$params = array();
                break;
        }
		return array($tpl, $params);
    }

	function formatConverted(&$row, $unformatted_row) {
        $icon = '';

        if ($this->format == 'html') {
            global $app_list_strings;
            $icon_name = $unformatted_row['converted_to_type'];
            $mod_name = array_get_default($app_list_strings['moduleListSingular'], $icon_name, '');
            $icon = get_image($icon_name, 'title="' . to_html($mod_name) . '" alt="" border=0');
            $icon .= ' ';
        }

        return $icon . $row['converted_to'];
	}

	function formatActivity(&$row, $unformatted_row, $model_fields, $template) {
        $icon = '';

        if ($this->format == 'html') {
            global $app_list_strings;
            $module_field = $model_fields['record_item']['dynamic_module'];
            $icon_name = $unformatted_row[$module_field];
            $mod_name = array_get_default($app_list_strings['moduleListSingular'], $icon_name, '');
            $icon = get_image($icon_name, 'title="' . to_html($mod_name) . '" alt="" border=0');
            $icon .= ' ';
        }

		$params = $template[1];
		array_unshift($params, $icon . $row['record_item']);
		array_push($params, $row['assigned_user']);
		array_unshift($params, $template[0]);
		$row['activity_text'] = call_user_func_array('sprintf', $params);
    }
}
