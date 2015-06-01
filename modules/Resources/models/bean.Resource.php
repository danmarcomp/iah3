<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Resources/Resource.php
	unified_search: false
	duplicate_merge: false
	table_name: resources
	primary_key: id
	reportable: true
fields
	app.id
	app.assigned_user
	app.deleted
	name
		vname: LBL_RESOURCE_NAME
		type: name
		len: 80
		required: true
		vname_list: LBL_LIST_RESOURCE_NAME
	type
		vname: LBL_RESOURCE_TYPE
		type: enum
		options: resource_type_dom
		len: 80
		required: true
		massupdate: false
		vname_list: LBL_LIST_RESOURCE_TYPE
	location
		vname: LBL_LOCATION
		type: varchar
		len: 80
	has_tv
		vname: LBL_HAS_TV
		type: bool
		massupdate: true
	has_dvd
		vname: LBL_HAS_DVD
		type: bool
		massupdate: true
	has_vcr
		vname: LBL_HAS_VCR
		type: bool
		massupdate: true
	has_projector
		vname: LBL_HAS_PROJECTOR
		type: bool
		massupdate: true
	has_screen
		vname: LBL_HAS_SCREEN
		type: bool
		massupdate: true
	has_pc
		vname: LBL_HAS_PC
		type: bool
		massupdate: true
	has_conf_phone
		vname: LBL_HAS_CONFERENCE_PHONE
		type: bool
		massupdate: true
	description
		vname: LBL_DESC
		type: text
links
	meetings
		relationship: meetings_resources
		vname: LBL_MEETINGS
