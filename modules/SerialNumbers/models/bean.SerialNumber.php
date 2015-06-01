<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/SerialNumbers/SerialNumber.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: serial_numbers
	primary_key: id
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
	app.deleted
	serial_no
		vname: LBL_SERIAL_NO
		type: varchar
		len: 100
		audited: true
	item_name
		vname: LBL_SERIAL_ITEM_NAME
		type: enum
		options: serial_number_item_dom
		len: 100
		audited: false
		massupdate: true
	notes
		vname: LBL_SERIAL_NOTES
		type: varchar
		len: 255
		audited: false
	asset
		vname: LBL_SUPPORTED_PRODUCT
		type: ref
		reportable: false
		bean_name: Asset
		massupdate: true
	assembly
		vname: LBL_SUPPORTED_ASSEMBLY
		type: ref
		reportable: false
		bean_name: Assembly
		massupdate: true
indices
	serial_numbers_serial
		fields
			- serial_no
	serial_numbers_asset
		fields
			- asset_id
	serial_numbers_assembly
		fields
			- assembly_id
relationships
	asset_serial_numbers
		key: asset_id
		target_bean: Asset
		target_key: id
		relationship_type: one-to-many
	assembly_serial_numbers
		key: assembly_id
		target_bean: SupportedAssembly
		target_key: id
		relationship_type: one-to-many
