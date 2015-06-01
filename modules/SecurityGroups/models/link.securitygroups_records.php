<?php return; /* no output */ ?>

detail
	type: link
	table_name: securitygroups_records
	primary_key: [module, record_id, securitygroup_id]
fields
	app.date_modified
	app.deleted
	securitygroup_id
		type: char
		len: 36
	module
		type: module_name
	record
		type: ref
		dynamic_module: module
relationships
	securitygroups_accounts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Accounts
		lhs_bean: SecurityGroup
		rhs_bean: Account
	securitygroups_bugs
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Bugs
		lhs_bean: SecurityGroup
		rhs_bean: Bug
	securitygroups_calls
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Calls
		lhs_bean: SecurityGroup
		rhs_bean: Call
	securitygroups_campaigns
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Campaigns
		lhs_bean: SecurityGroup
		rhs_bean: Campaign
	securitygroups_cases
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Cases
		lhs_bean: SecurityGroup
		rhs_bean: aCase
	securitygroups_contacts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Contacts
		lhs_bean: SecurityGroup
		rhs_bean: Contact
	securitygroups_documents
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Documents
		lhs_bean: SecurityGroup
		rhs_bean: Document
	securitygroups_emails
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Emails
		lhs_bean: SecurityGroup
		rhs_bean: Email
	securitygroups_leads
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Leads
		lhs_bean: SecurityGroup
		rhs_bean: Lead
	securitygroups_meetings
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Meetings
		lhs_bean: SecurityGroup
		rhs_bean: Meeting
	securitygroups_notes
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Notes
		lhs_bean: SecurityGroup
		rhs_bean: Note
	securitygroups_opportunities
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Opportunities
		lhs_bean: SecurityGroup
		rhs_bean: Opportunity
	securitygroups_project
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Project
		lhs_bean: SecurityGroup
		rhs_bean: Project
	securitygroups_project_task
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: ProjectTask
		lhs_bean: SecurityGroup
		rhs_bean: ProjectTask
	securitygroups_prospect_lists
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: ProspectLists
		lhs_bean: SecurityGroup
		rhs_bean: ProspectList
	securitygroups_prospects
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Prospects
		lhs_bean: SecurityGroup
		rhs_bean: Prospect
	securitygroups_tasks
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Tasks
		lhs_bean: SecurityGroup
		rhs_bean: Task
	securitygroups_partners
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Partners
		lhs_bean: SecurityGroup
		rhs_bean: Partner
	securitygroups_assets
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Assets
		lhs_bean: SecurityGroup
		rhs_bean: Asset
	securitygroups_bills
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Bills
		lhs_bean: SecurityGroup
		rhs_bean: Bill
	securitygroups_contract
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Service
		lhs_bean: SecurityGroup
		rhs_bean: Contract
	securitygroups_discounts
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Discounts
		lhs_bean: SecurityGroup
		rhs_bean: Discount
	securitygroups_events
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: EventSessions
		lhs_bean: SecurityGroup
		rhs_bean: EventSession
	securitygroups_invoice
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Invoice
		lhs_bean: SecurityGroup
		rhs_bean: Invoice
	securitygroups_credits
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: CreditNotes
		lhs_bean: SecurityGroup
		rhs_bean: CreditNote
	securitygroups_productcatalog
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: ProductCatalog
		lhs_bean: SecurityGroup
		rhs_bean: Product
	securitygroups_purchaseorders
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: PurchaseOrders
		lhs_bean: SecurityGroup
		rhs_bean: PurchaseOrder
	securitygroups_quotes
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Quotes
		lhs_bean: SecurityGroup
		rhs_bean: Quote
	securitygroups_receiving
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Receiving
		lhs_bean: SecurityGroup
		rhs_bean: Receiving
	securitygroups_salesorders
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: sales_orders
		lhs_bean: SecurityGroup
		rhs_bean: SalesOrder
	securitygroups_shipping
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Shipping
		lhs_bean: SecurityGroup
		rhs_bean: Shipping
	securitygroups_emailtemplates
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: EmailTemplates
		lhs_bean: SecurityGroup
		rhs_bean: EmailTemplate
	securitygroups_reports
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: Reports
		lhs_bean: SecurityGroup
		rhs_bean: Report
	securitygroups_savedsearch
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: securitygroup_id
		join_key_rhs: record_id
		relationship_role_column: module
		relationship_role_column_value: SavedSearch
		lhs_bean: SecurityGroup
		rhs_bean: SavedSearch
