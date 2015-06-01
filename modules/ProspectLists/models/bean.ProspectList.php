<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ProspectLists/ProspectList.php
	unified_search: false
	duplicate_merge: false
	table_name: prospect_lists
	primary_key: id
	reportable: true
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_NAME
		type: name
		len: 50
		unified_search: true
		required: true
	list_type
		vname: LBL_TYPE
		type: enum
		options: prospect_list_type_dom
		len: 25
		massupdate: false
		required: true
		default: default
	description
		vname: LBL_DESCRIPTION
		type: text
	domain_name
		vname: LBL_DOMAIN
		type: varchar
		len: 255
	dynamic
		vname: LBL_DYNAMIC
		type: bool
		default: 0
		massupdate: true
	total_entries
		vname: LBL_ENTRIES
		type: int
		source
			type: function
			value_function
				- ProspectList
				- get_entry_count
			fields
				- id
				- dynamic
links
	prospects
		relationship: prospect_list_prospects
		vname: LBL_PROSPECTS
	contacts
		relationship: prospect_list_contacts
		vname: LBL_CONTACTS
	leads
		relationship: prospect_list_leads
		vname: LBL_LEADS
	campaigns
		relationship: prospect_list_campaigns
		vname: LBL_CAMPAIGNS
	users
		relationship: prospect_list_users
		vname: LBL_USERS
	app.securitygroups
		relationship: securitygroups_prospect_lists
fields_compat
	entry_count
		type: int
		source: non-db
		vname: LBL_LIST_ENTRIES
	marketing
		vname: LBL_MARKETING_ID
		type: ref
		source: non-db
		bean_name: EmailMarketing
	marketing_name
		vname: LBL_MARKETING_NAME
		type: varchar
		len: 255
		source: non-db
indices
	idx_prospect_list_name
		fields
			- name
