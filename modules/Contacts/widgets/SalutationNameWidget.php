<?php

require_once('include/layout/forms/FormField.php');

class SalutationNameWidget extends FormField {
	var $salutation_field = 'salutation';

	function init($params=null, $model=null) {
		parent::init($params, $model);
		$this->width = 22;
	}
	
	function getRequiredFields() {
		return array($this->salutation_field, $this->name);
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		global $app_list_strings;
		
		$value = parent::renderHtml($gen, $row_result, $parents, $context);
		if($value) {
			if($this->getEditable($context))
				$value = $gen->getFormObject()->renderField($row_result, $this->salutation_field) . '&nbsp;' . $value;
			else {
				$salut = $row_result->getField($this->salutation_field);
				if($salut) {
					$salut = array_get_default($app_list_strings['salutation_dom'], $salut, $salut) . ' ';
					if(($p = strrpos($value, '&nbsp;')) !== false) {
						// compensate for favorites star
						$value = substr($value, 0, $p + 6) . $salut . substr($value, $p + 6);
					} else
						$value = $salut . $value;
				}
			}
		}
		return $value;
	}
	
}

?>