<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/iFrames/iFrame.php
	unified_search: false
	duplicate_merge: false
	table_name: iframes
	primary_key: id
	default_order_by: name
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.created_by_user
	name
		vname: LBL_LIST_NAME
		type: name
		len: 255
		required: true
		unified_search: true
	url
		vname: LBL_LIST_URL
		type: url
		required: true
		massupdate: true
	type
		vname: LBL_LIST_TYPE
		type: enum
		len: 20
		options: iframe_type_dom
		required: true
		massupdate: true
	placement
		vname: LBL_LIST_PLACEMENT
		type: enum
		len: 20
		options: placement_dom
		required: true
		massupdate: true
	status
		vname: LBL_LIST_STATUS
		type: bool
		required: true
		massupdate: true
indices
	idx_cont_name
		fields
			- name
			- deleted
