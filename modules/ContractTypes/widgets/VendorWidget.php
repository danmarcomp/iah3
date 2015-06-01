<?php

require_once('include/layout/forms/FormField.php');

class VendorWidget extends FormField {

	function init($params=null, $model=null) {
		parent::init($params, $model);
	}
	
	function getRequiredFields() {
		return array($this->name);
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
        $spec = $row_result->fields['vendor_name'];

        $new_spec = array(
            'type' => 'ref',
            'bean_name' => 'Account',
            'module_dir' => 'Accounts',
            'id_name' => 'vendor_id',
            'add_filters' => array(array('param' => 'is_supplier', 'value' => 1))
        );

        $row_result->fields['vendor_name'] = array_merge($spec, $new_spec);

        if (! $row_result->new_record)
            $row_result->row['vendor_id'] = 'temp';

		return parent::renderHtml($gen, $row_result, $parents, $context);
	}
}
?>