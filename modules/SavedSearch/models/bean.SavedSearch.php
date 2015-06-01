<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SavedSearch/SavedSearch.php
	unified_search: false
	duplicate_merge: false
	table_name: saved_search
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.assigned_user
	app.shared_with
    app.created_by_user
	name
		type: name
		vname: LBL_NAME
		len: 150
		vname_list: LBL_LIST_NAME
	search_module
		type: module_name
		vname: LBL_MODULE
		len: 150
		vname_list: LBL_LIST_MODULE
    filter_name
		type: varchar
		vname: LBL_FILTER_NAME
		len: 150
		reportable: false
	view_name
		type: varchar
		vname: LBL_VIEW_NAME
		len: 150
		reportable: false
	columns_spec
		type: text
		vname: LBL_COLUMNS
		reportable: false
	filters_spec
		type: text
		vname: LBL_FILTERS
		reportable: false
	filter_values
		type: text
		vname: LBL_FILTER_VALUES
		reportable: false
	sort_order
		vname: LBL_SORT_ORDER
		type: varchar
		len: 150
		reportable: false
	description
		type: text
		vname: LBL_DESCRIPTION
links
	app.securitygroups
		relationship: securitygroups_savedsearch
indices
	idx_desc
		fields
			- name
			- deleted
