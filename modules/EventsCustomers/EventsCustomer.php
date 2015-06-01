<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


require_once('data/SugarBean.php');
require_once('include/utils.php');

class EventsCustomer extends SugarBean {
	// Stored fields
	var $id;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $session_id;
	var $customer_id;
	var $customer_type;
	var $registered;
	var $attended;

	var $table_name = "events_customers";

	var $object_name = "EventsCustomer";
	var $module_dir = 'EventsCustomers';
	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array();

	function EventType() {
		parent::SugarBean();
	}

	function get_summary_text()
	{
		return "$this->name";
	}

    static function get_set_registered_control($id, $value) {
        $html = EventsCustomer::get_status_control($id, $value, 'registered');

        return $html;
    }

    static function get_set_attended_control($id, $value) {
        $html = EventsCustomer::get_status_control($id, $value, 'attended');

        return $html;
    }

    static function get_status_control($id, $value, $status_field) {
        require_once('include/layout/forms/EditableForm.php');

        $url = 'async.php?module=EventsCustomers&action=SetStatus&cust_id='.$id.'&field='. $status_field;
        $field_id =  $status_field .'_'. $id;
        $onclick = "SUGAR.util.retrieveAndFill('".$url."', null, null, updatePanel, '{$field_id}');";

        $html = EditableForm::get_tag('input', array('type' => 'hidden', 'id' => $field_id, 'value' => $value ? 1 : 0), true);
        $attrs = array('type' => 'button', 'class' => 'input-checkbox input-outer', 'id' => $field_id .'_sel', 'onclick' => $onclick);

        if($value)
            $attrs['class'] .= ' checked';

        $html .= EditableForm::get_tag('button', $attrs);
        $html .= EditableForm::get_tag('div', array('class' => 'input-icon'), true);
        $html .= '</button>';

        return $html;
    }
}

