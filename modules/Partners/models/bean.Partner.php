<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Partners/Partner.php
	audit_enabled: true
	unified_search: true
	duplicate_merge: false
	table_name: partners
	primary_key: id
	default_order_by: name
	reportable: true
fields
	app.id
	app.assigned_user
	app.created_by_user
	app.date_entered
	app.deleted
	app.date_modified
	app.modified_user
	description
		vname: LBL_DESCRIPTION
		type: text
	name
		vname: LBL_NAME
		type: name
		len: 60
		required: true
		unified_search: true
		vname_list: LBL_LIST_NAME
	date_start
		vname: LBL_DATE_START
		type: date
		vname_list: LBL_LIST_DATE_START
		massupdate: true
	date_end
		vname: LBL_DATE_END
		type: date
		vname_list: LBL_LIST_DATE_END
		massupdate: true
	code
		vname: LBL_CODE
		type: item_number
		dbType: int
		required: true
		disable_num_format: true
		unified_search: true
		vname_list: LBL_LIST_CODE
	lead_exclusivity
		vname: LBL_LEAD_EXCLUSIVITY
		type: int
	lead_revenue_sharing
		vname: LBL_LEAD_SHARING
		type: int
	commission_rate
		vname: LBL_COMMISSION_RATE
		type: double
		validation
			type: range
			min: 0
			max: 100
	related_account
		type: ref
		vname: LBL_RELATED_ACCOUNT
		massupdate: false
		reportable: false
		bean_name: Account
links
	related_account
		relationship: partner_related_account
		module: Accounts
		bean_name: Account
		vname: LBL_RELATED_ACCOUNT
	invoice
		relationship: invoice_partners
		module: Invoice
		bean_name: Invoice
		ignore_role: true
		vname: LBL_INVOICE
	opportunities
		relationship: opportunities_partners
		module: Opportunities
		bean_name: Opportunity
		ignore_role: true
		vname: LBL_OPPORTUNITIES
	contacts
		relationship: contacts_partner
		module: Contacts
		bean_name: Contact
		vname: LBL_CONTACTS
	accounts
		relationship: accounts_partners
		module: Accounts
		bean_name: Account
		ignore_role: true
		vname: LBL_ACCOUNTS
	leads
		relationship: leads_partners
		module: Leads
		bean_name: Lead
		ignore_role: true
		vname: LBL_LEADS
	app.securitygroups
		relationship: securitygroups_partners
fields_compat
	assigned_user_name
		id_name: assigned_user_id
		type: assigned_user_name
		table: users
		source: non-db
		reportable: false
		massupdate: false
		required: true
	related_account_name
		rname: name
		id_name: related_account_id
		vname: LBL_RELATED_ACCOUNT
		join_name: account
		type: relate
		link: related_account
		table: accounts
		isnull: true
		module: Accounts
		dbType: varchar
		len: 255
		source: non-db
		unified_search: false
		massupdate: false
indices
	partners_name
		fields
			- name
relationships
	invoice_partners
		key: id
		target_bean: Invoice
		target_key: partner_id
		relationship_type: one-to-many
	opportunities_partners
		key: id
		target_bean: Opportunity
		target_key: partner_id
		relationship_type: one-to-many
	accounts_partners
		key: id
		target_bean: Account
		target_key: partner_id
		relationship_type: one-to-many
	leads_partners
		key: id
		target_bean: Lead
		target_key: partner_id
		relationship_type: one-to-many
	partner_related_account
		key: related_account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-one
