<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Dashboard/Dashboard.php
	unified_search: false
	duplicate_merge: false
	table_name: dashboards
	primary_key: id
	default_order_by: name
	display_name: display_name
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: before_save
	fill_defaults
		--
			class_function: fill_defaults
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
		default: 0
	app.shared_with
	app.created_by_user
	name
		type: name
		len: 100
		vname: LBL_RAW_TITLE
		comment: Name (title) of the dashboard report object
		required: true
	display_name
		type: name
		vname: LBL_TITLE
		vname_list: LBL_LIST_NAME
		required: true
		source
			type: function
			value_function
				- Dashboard
				- get_display_name
			fields
				- name
	description
		type: text
		vname: LBL_DESCRIPTION
		comment: Description of the dashboard object
	content
		type: text
		vname: LBL_CONTENT
		editable: false
	section
		type: enum
		len: 100
		options_function
			- Dashboard
			- get_section_options
		vname: LBL_SECTION
		vname_list: LBL_LIST_SECTION
		required: true
		massupdate: true
	published
		type: bool
		vname: LBL_PUBLISHED
		default: 1
		vname_list: LBL_LIST_PUBLISHED
		massupdate: true
	flat_tab
		type: bool
		vname: LBL_FLAT_TAB
		default: 0
		massupdate: true
	position
		type: enum
		dbType: int
		vname: LBL_POSITION
		options: dashboard_position_dom
		default: 10
		massupdate: false
		vname_list: LBL_LIST_POSITION
		required: true
	tab_order
		type: int
		vname: LBL_TAB_ORDER
		default: 1
		vname_list: LBL_LIST_TAB_ORDER
	options
		type: text
		vname: LBL_OPTIONS
		editable: false
	icon
		type: varchar
		len: 40
		vname: LBL_ICON
links
	app.securitygroups
		relationship: securitygroups_dashboards
relationships
	securitygroups_dashboards
		key: id
		target_bean: SecurityGroup
		target_key: id
		relationship_type: many-to-many
		join
			table: securitygroups_records
			source_key: record_id
			target_key: securitygroup_id
			role_column: module
			role_value: Dashboard
indices
	idx_dashboard_name
		fields
			- name
			- deleted
