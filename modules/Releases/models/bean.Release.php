<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Releases/Release.php
	unified_search: false
	duplicate_merge: false
	table_name: releases
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		len: 50
		required: true
		unified_search: true
	list_order
		vname: LBL_LIST_ORDER
		type: int
		len: 4
	status
		vname: LBL_STATUS
		type: enum
		options: release_status_dom
		len: 25
		required: true
		default: Active
		massupdate: true
	product
		vname: LBL_PRODUCT
		type: ref
		required: true
		massupdate: false
		bean_name: SoftwareProduct
		unified_search: true
links
	product_link
		relationship: product_release
		vname: LBL_PRODUCT
		link_type: one
		module: SoftwareProducts
		bean_name: SoftwareProduct
		massupdate: false
fields_compat
	product_name
		rname: name
		vname: LBL_PRODUCT
		type: relate
		link: product_link
		source: non-db
		massupdate: false
indices
	idx_releases
		fields
			- name
			- deleted
relationships
	product_release
		key: product_id
		target_bean: SoftwareProduct
		target_key: id
		relationship_type: one-to-many
