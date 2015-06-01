<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Accounts/Account.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search: true
	duplicate_merge: true
	optimistic_locking: true
	comment:
		Accounts are organizations or entities that are the target of selling,
		support, and marketing activities, or have already purchased products or
		services
	table_name: accounts
	primary_key: id
	default_order_by: name
	reportable: true
	importable: LBL_ACCOUNTS
hooks
	new_record
		--
			class_function: init_record
	before_save
		--
			class_function: pre_update_balance
			required_fields
				- is_supplier
	after_save
		--
			class_function: copy_address
	notify
		--
			class_function: send_notification
	after_add_link
		--
			class_function: set_contact_primary_acc
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	app.currency
	name
		type: name
		vname: LBL_NAME
		comment: Name of the account
		unified_search: true
		audited: true
		vname_list: LBL_LIST_ACCOUNT_NAME
		required: true
		default: ""
	member_of
		type: ref
		vname: LBL_MEMBER_OF
		id_name: parent_id
		reportable: true
		audited: true
		bean_name: Account
		massupdate: true
	account_type
		vname: LBL_TYPE
		type: enum
		options: account_type_dom
		len: 25
		comment: The account is of this type
		massupdate: true
	industry
		vname: LBL_INDUSTRY
		type: enum
		options: industry_dom
		len: 25
		comment: The account belongs in this industry
		massupdate: true
	annual_revenue
		vname: LBL_ANNUAL_REVENUE
		type: varchar
		len: 25
		comment: Annual revenue for this account
	phone_fax
		vname: LBL_PHONE_FAX
		type: phone
		unified_search: true
		comment: The fax phone number of this account
	billing_address_street
		vname: LBL_BILLING_ADDRESS_STREET
		type: varchar
		len: 150
		comment: The street address used for billing address
	billing_address_city
		vname: LBL_BILLING_ADDRESS_CITY
		vname_list: LBL_LIST_CITY
		type: varchar
		len: 100
		comment: The city used for billing address
	billing_address_state
		vname: LBL_BILLING_ADDRESS_STATE
		vname_list: LBL_LIST_STATE
		type: varchar
		len: 100
		comment: The state used for billing address
	billing_address_postalcode
		vname: LBL_BILLING_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: The postal code used for billing address
	billing_address_country
		vname: LBL_BILLING_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: The country used for the billing address
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Descriptive information about the account
	rating
		vname: LBL_RATING
		type: varchar
		len: 25
		comment:
			An arbitrary rating for this account for use in comparisons
			with others
	phone_office
		vname: LBL_PHONE_OFFICE
		vname_list: LBL_PHONE_OFFICE
		type: phone
		audited: true
		unified_search: true
		comment: The office phone number
	phone_alternate
		vname: LBL_OTHER_PHONE
		type: phone
		unified_search: true
		comment: An alternate phone number
	email1
		vname: LBL_EMAIL
		type: email
		audited: true
		comment: Primary email address
	email2
		vname: LBL_OTHER_EMAIL_ADDRESS
		type: email
		comment: Secondary email address
	website
		vname: LBL_WEBSITE
		type: url
		comment: URL of website for the account
		massupdate: true
	ownership
		vname: LBL_OWNERSHIP
		type: varchar
		len: 100
		comment: ""
	employees
		vname: LBL_EMPLOYEES
		type: number
		len: 10
		comment: Number of employees
	sic_code
		vname: LBL_SIC_CODE
		type: varchar
		len: 10
		comment: SIC code of the account
	ticker_symbol
		vname: LBL_TICKER_SYMBOL
		type: varchar
		len: 10
		comment: The stock trading (ticker) symbol for the account
	shipping_address_street
		vname: LBL_SHIPPING_ADDRESS_STREET
		type: varchar
		len: 150
		comment: The street address used for for shipping purposes
	shipping_address_city
		vname: LBL_SHIPPING_ADDRESS_CITY
		type: varchar
		len: 100
		comment: The city used for the shipping address
	shipping_address_state
		vname: LBL_SHIPPING_ADDRESS_STATE
		type: varchar
		len: 100
		comment: The state used for the shipping address
	shipping_address_postalcode
		vname: LBL_SHIPPING_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: The zip code used for the shipping address
	shipping_address_country
		vname: LBL_SHIPPING_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: The country used for the shipping address
	main_service_contract
		vname: LBL_MAIN_SERVICE_CONTRACT
		type: ref
		importable: false
		bean_name: Contract
		vname_list: LBL_LIST_SERVICE_CONTRACT
		massupdate: false
		editable: false
	is_supplier
		vname: LBL_IS_SUPPLIER
		type: bool
		default: 0
		updateable: false
		massupdate: true
	account_popups
		vname: LBL_ACCOUNT_POPUPS
		type: bool
		default: 0
		massupdate: true
	account_popup
		vname: LBL_ACCOUNT_POPUP
		type: text
		default: ""
	sales_popup
		vname: LBL_SALES_POPUP
		type: text
		default: ""
	service_popup
		vname: LBL_SERVICE_POPUP
		type: text
		default: ""
	balance
		vname: LBL_BALANCE_RECEIVABLE
		vname_list: LBL_LIST_BALANCE
		type: base_currency
		display_currency_id: currency_id
		editable: false
	balance_payable
		vname: LBL_BALANCE_PAYABLE
		vname_list: LBL_LIST_BALANCE_PAYABLE
		type: base_currency
		display_currency_id: currency_id
		editable: false
	credit_limit
		vname: LBL_SALES_CREDIT_LIMIT
		vname_list: LBL_LIST_CREDIT_LIMIT
		type: currency
		audited: true
	purchase_credit_limit
		vname: LBL_PURCHASE_CREDIT_LIMIT
		vname_list: LBL_LIST_PURCHASE_CREDIT_LIMIT
		type: currency
		audited: true
	default_terms
		vname: LBL_DEFAULT_SALES_TERMS
		type: enum
		options: terms_dom
		len: 25
		default: ""
		audited: true
		massupdate: true
	default_purchase_terms
		vname: LBL_DEFAULT_PURCHASE_TERMS
		type: enum
		options: terms_dom
		len: 25
		default: ""
		audited: true
		massupdate: true
	default_discount
		vname: LBL_DEFAULT_SALES_DISCOUNT
		type: ref
		bean_name: Discount
		massupdate: true
	default_purchase_discount
		vname: LBL_DEFAULT_PURCHASE_DISCOUNT
		type: ref
		bean_name: Discount
		massupdate: true
	default_shipper
		vname: LBL_DEFAULT_SALES_SHIPPING_PROVIDER
		type: ref
		bean_name: ShippingProvider
		massupdate: true
	default_purchase_shipper
		vname: LBL_DEFAULT_PURCHASE_SHIPPING_PROVIDER
		type: ref
		bean_name: ShippingProvider
		massupdate: true
	last_activity_date
		vname: LBL_LAST_ACTIVITY
		type: datetime
		editable: false
	tax_information
		vname: LBL_TAX_INFORMATION
		type: varchar
		len: 150
	partner
		vname: LBL_PARTNER
		type: ref
		reportable: true
		bean_name: Partner
		massupdate: true
	invalid_email
		vname: LBL_INVALID_EMAIL
		type: bool
		required: true
		default: 0
		massupdate: true
	email_opt_out
		vname: LBL_EMAIL_OPT_OUT
		type: bool
		dbType: varchar
		len: 3
		required: true
		default: 0
		massupdate: true
	temperature
		vname: LBL_TEMPERATURE
		type: enum
		options: temperature_dom
		len: 40
		massupdate: true
	first_invoice
		type: ref
		vname: LBL_FIRST_SALE
		bean_name: Invoice
		editable: false
		ref_display_name: date_entered
		ref_display_type: datetime
		format: date_only
	last_invoice
		type: ref
		vname: LBL_LAST_SALE
		bean_name: Invoice
		editable: false
		ref_display_name: date_entered
		ref_display_type: datetime
		format: date_only
	tax_code
		type: ref
		vname: LBL_TAX_CODE
		bean_name: TaxCode
		massupdate: true
	primary_contact
		type: ref
		reportable: true
		massupdate: false
		vname: LBL_PRIMARY_CONTACT
		bean_name: Contact
links
	activities
		relationship: accounts_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: accounts_history
		module: Activities
		bean_name: ActivityHistory
		layout: AccountHistory
		vname: LBL_HISTORY_SUBPANEL_TITLE
	documents
		relationship: documents_accounts
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	supported_assemblies
		relationship: supp_assembly_account
		module: SupportedAssemblies
		bean_name: SupportedAssembly
		ignore_role: true
		vname: LBL_SUPPORTED_ASSEMBLIES
	assets
		relationship: asset_account
		module: Assets
		bean_name: Asset
		vname: LBL_ASSETS
	partners
		relationship: accounts_partners
		vname: LBL_PARTNERS
		module: Partners
		bean_name: Partner
	converted_lead
		relationship: lead_converted_account
		vname: LBL_CONVERTED_LEAD
		module: Leads
		bean_name: Lead
	threads
		relationship: accounts_threads
		module: Threads
		bean_name: Thread
		vname: LBL_THREADS
	members
		relationship: member_accounts
		module: Accounts
		bean_name: Account
		vname: LBL_MEMBERS
	cases
		relationship: accounts_cases
		module: Cases
		bean_name: aCase
		vname: LBL_CASES
	tasks
		relationship: account_tasks
		module: Tasks
		bean_name: Task
		vname: LBL_TASKS
	notes
		relationship: account_notes
		module: Notes
		bean_name: Note
		vname: LBL_NOTES
	meetings
		relationship: account_meetings
		module: Meetings
		bean_name: Meeting
		vname: LBL_MEETINGS
	calls
		relationship: account_calls
		module: Calls
		bean_name: Call
		vname: LBL_CALLS
	emails
		relationship: emails_accounts_rel
		side: right
		module: Emails
		bean_name: Email
		vname: LBL_EMAILS
	bugs
		relationship: accounts_bugs
		module: Bugs
		bean_name: Bug
		vname: LBL_BUGS
	contacts
		relationship: accounts_contacts
		module: Contacts
		bean_name: Contact
		vname: LBL_CONTACTS
	opportunities
		relationship: accounts_opportunities
		module: Opportunities
		bean_name: Opportunity
		vname: LBL_OPPORTUNITIES
	invoice
		relationship: invoice_billto_accounts
		module: Invoice
		bean_name: Invoice
		ignore_role: true
		vname: LBL_INVOICES
	credits
		relationship: credits_billto_accounts
		module: CreditNotes
		bean_name: CreditNote
		vname: LBL_CREDIT_NOTES
	bills
		relationship: bills_supplier
		module: Bills
		bean_name: Bill
		ignore_role: true
		vname: LBL_BILLS
	invoice_shipto
		relationship: invoice_shipto_accounts
		module: Invoice
		bean_name: Invoice
		vname: LBL_INVOICE_SHIP_TO
	salesorders
		relationship: so_billto_accounts
		module: SalesOrders
		bean_name: SalesOrder
		ignore_role: true
		vname: LBL_SALESORDERS
	salesorders_shipto
		relationship: so_shipto_accounts
		module: SalesOrders
		bean_name: SalesOrder
		ignore_role: true
		vname: LBL_SALESORDERS_SHIP_TO
	purchaseorders
		relationship: purchase_orders_supplier
		module: PurchaseOrders
		bean_name: PurchaseOrder
		ignore_role: true
		vname: LBL_PURCHASEORDERS
	eventsessions
		relationship: events_accounts
		module: EventSessions
		bean_name: EventSession
		vname: LBL_EVENTS
	quotes
		relationship: quotes_billto_accounts
		module: Quotes
		bean_name: Quote
		ignore_role: true
		vname: LBL_QUOTES
	quotes_shipto
		relationship: quotes_shipto_accounts
		module: Quotes
		bean_name: Quote
		vname: LBL_QUOTES_SHIP_TO
	project
		relationship: projects_accounts
		module: Project
		bean_name: Project
		vname: LBL_PROJECTS
	leads
		relationship: account_leads
		module: Leads
		bean_name: Lead
		vname: LBL_LEADS
	expensereports
		relationship: expense_reports_accounts
		module: ExpenseReports
		bean_name: ExpenseReport
		vname: LBL_EXPENSE_REPORTS
	products
		relationship: products_suppliers
		vname: LBL_PRODUCTS
	app.securitygroups
		relationship: securitygroups_accounts
indices
	idx_accnt_id_del
		fields
			- id
			- deleted
	idx_accnt_assigned_del
		fields
			- deleted
			- assigned_user_id
	idx_accnt_parent_id
		fields
			- parent_id
	idx_accnt_mainsvc_id
		fields
			- main_service_contract_id
relationships
	member_accounts
		key: id
		target_bean: Account
		target_key: parent_id
		relationship_type: one-to-many
	account_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Accounts
		relationship_type: one-to-many
	account_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Accounts
		relationship_type: one-to-many
	account_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Accounts
		relationship_type: one-to-many
	account_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Accounts
		relationship_type: one-to-many
	account_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Accounts
		relationship_type: one-to-many
	account_leads
		key: id
		target_bean: Lead
		target_key: account_id
		relationship_type: one-to-many
	accounts_partners
		key: partner_id
		target_bean: Partner
		target_key: id
		relationship_type: one-to-many
	accounts_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: account_meetings
			calls
				relationship: account_calls
			tasks
				relationship: account_tasks
	accounts_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: account_meetings
			calls
				relationship: account_calls
			tasks
				relationship: account_tasks
			notes
				relationship: account_notes
			emails
				relationship: emails_accounts_rel
