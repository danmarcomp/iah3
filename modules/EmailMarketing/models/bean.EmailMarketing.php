<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EmailMarketing/EmailMarketing.php
	unified_search: false
	duplicate_merge: false
	table_name: email_marketing
	primary_key: id
	reportable: true
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
		len: 150
		required: true
	from_addr
		vname: LBL_FROM_ADDR
		type: varchar
		len: 100
	from_name
		vname: LBL_FROM_NAME
		type: varchar
		len: 100
	inbound_email
		vname: LBL_FROM_MAILBOX_NAME
		type: ref
		required: true
		bean_name: EmailPOP3
		massupdate: true
	date_start
		vname: LBL_DATE_START
		type: datetime
		massupdate: true
	template
		vname: LBL_TEMPLATE
		type: ref
		required: true
		bean_name: EmailTemplate
		massupdate: true
	status
		vname: LBL_STATUS
		type: enum
		len: 25
		required: true
		options: email_marketing_status_dom
		default: active
		massupdate: true
	campaign
		vname: LBL_CAMPAIGN_ID
		type: ref
		isnull: true
		reportable: false
		bean_name: Campaign
		massupdate: true
	all_prospect_lists
		vname: LBL_ALL_PROSPECT_LISTS
		type: bool
		default: 0
		massupdate: true
	dripfeed_delay
		type: int
		width: 8
		default: 0
	dripfeed_delay_unit
		type: enum
		options: dripfeed_delay_unit_dom
		options_add_blank: false
		len: 25
		default: days
		massupdate: true
	total_entries
		vname: LBL_ENTRIES
		type: int
		source
			type: function
			value_function
				- EmailMarketing
				- get_entry_count
			fields
				- id
				- campaign_id
				- all_prospect_lists
links
	prospectlists
		relationship: email_marketing_prospect_lists
		vname: LBL_PROSPECT_LISTS
	emailtemplate
		relationship: email_template_email_marketings
		vname: LBL_TEMPLATE
fields_compat
	template_name
		rname: name
		id_name: template_id
		vname: LBL_TEMPLATE_NAME
		type: relate
		table: email_templates
		isnull: true
		module: EmailTemplates
		dbType: varchar
		link: emailtemplate
		len: 255
		source: non-db
	prospect_list_name
		vname: LBL_PROSPECT_LIST_NAME
		type: varchar
		len: 100
		source: non-db
indices
	idx_emmkt_name
		fields
			- name
	idx_emmkit_del
		fields
			- deleted
relationships
	email_template_email_marketings
		key: template_id
		target_bean: EmailTemplate
		target_key: id
		relationship_type: one-to-many
