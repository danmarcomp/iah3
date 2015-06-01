<?php

require_once('include/layout/forms/FormField.php');

class IsSupplierWidget extends FormField {

	function init($params=null, $model=null) {
		parent::init($params, $model);
	}
	
	function getRequiredFields() {
		return array($this->name);
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		global $mod_strings;
		$value = parent::renderHtml($gen, $row_result, $parents, $context);

		if($row_result->new_record)
            $value .= '&nbsp;&nbsp;<span class="required">'. $mod_strings['LBL_SUPPLIER_CAUTION'] .'</span>';
		return $value ;
	}
}
?>