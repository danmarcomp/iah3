<?php

require_once 'include/ListView/ListFormatter.php';

class EmailListFieldFormatter extends FieldFormatter
{
	function formatRows(array &$model_fields, array &$rows, $union_key=null)
	{
		$ret = parent::formatRows($model_fields, $rows, $union_key);
		if($this->format != 'html')
			return $ret;
		foreach ($ret as $k => &$r) {
			if (isset($rows[$k]['isread']) && empty($rows[$k]['isread'])) {
				foreach ($r as $k2 => &$v) {
					$type = $model_fields[$k2]['type'];
					if($type != 'attach_icon' && $type != 'id' && $type != 'module_name' && $k2 != 'isread' && strlen($v)) {
						$v = "<span style=\"font-weight:bold\">$v</span>";
					}
				}
			}
		}
		return $ret;
	}
}

