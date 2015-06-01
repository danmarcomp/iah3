<?php

require_once 'include/ListView/ListFormatter.php';

class ForecastListFieldFormatter extends FieldFormatter
{
	function formatRows(array &$model_fields, array &$rows, $union_key=null)
	{
		$ret = parent::formatRows($model_fields, $rows, $union_key);
		if($this->format != 'html')
			return $ret;
		foreach ($rows as $id => $row) {
			if(empty($row['user_id'])) continue;
			$icon_name = $row['user_id'] == $_REQUEST['user_id'] ? 'User' : 'Users';
			$type = $row['user_id'] == $_REQUEST['user_id'] ? 'personal' : $_REQUEST['type'];
			$icon = get_image($GLOBALS['image_path'] . $icon_name, 'border=0');
			$link = "index.php?module=Forecasts&action=WorksheetView&record=" . $row['id'];
			$ret[$id]['user'] = $icon . "&nbsp;<a href=\"$link\">" . $row['user'] . "</a>";
		}
		return $ret;
	}
}


