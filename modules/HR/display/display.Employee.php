<?php return; /* no output */ ?>

list
	default_order_by: user.full_name
    show_create_button: false
    no_mass_delete: true
fields
    home_address
        vname: LBL_HOME_ADDRESS
        type: address
        source
            type: address
            prefix: ""
basic_filters
	hide_portal_only
        hidden: true
filters
	hide_portal_only
		default_value: true
		type: flag
		vname: LBL_HIDE_PORTAL_ONLY
		operator: not
		field: user.portal_only
		hide_fields: true
	first_name
        vname: LBL_FIRST_NAME
        type: name
		field: user.first_name
	last_name
        vname: LBL_LAST_NAME
        type: name
		field: user.last_name
	department
        vname: LBL_DEPARTMENT
        type: enum
        options: departments_dom
		operator: =
		field: user.department
	status
        vname: LBL_STATUS
        type: enum
		options_function: [User, get_status_options]
		default_value: NotInactive
		filter_clause_source: [User, get_search_status_where]
		field: user.status
	user_name
        type: varchar
		field: user.user_name
	phone
        type: phone
		vname: LBL_ANY_PHONE
		fields
			- user.phone_home
			- user.phone_work
			- user.phone_fax
			- user.phone_other
	email
        type: email
		vname: LBL_ANY_EMAIL
		fields
			- user.email1
			- user.email2
widgets
	EmployeeWidget
		type: section
		path: modules/HR/widgets/EmployeeWidget.php
