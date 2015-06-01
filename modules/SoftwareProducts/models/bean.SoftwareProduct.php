<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SoftwareProducts/SoftwareProduct.php
	unified_search: false
	duplicate_merge: false
	table_name: software_products
	primary_key: id
	reportable: true
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		len: 100
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	description
		vname: LBL_DESCRIPTION
		type: text
		vname_list: LBL_LIST_DESCRIPTION
links
	releases
		vname: LBL_RELEASES
		relationship: product_release
		module: Releases
		bean_name: Release
	bugs
		vname: LBL_BUGS
		relationship: product_bugs
		module: Bugs
		bean_name: Bug
relationships
	product_bugs
		key: id
		target_bean: Bug
		target_key: product_id
		relationship_type: one-to-many
