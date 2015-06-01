<?php return; /* no output */ ?>

list
	default_order_by: name
    mass_merge_duplicates: true
    show_favorites: true
	layouts
		Customers
			vname: LBL_CUSTOMERS
			override_filters
				is_supplier: 0
				account_type: Customer
		Suppliers
			vname: LBL_SUPPLIERS
			view_name: Suppliers
			override_filters
				is_supplier: 1
        Duplicates
            vname: LBL_DUPLICATES
            view_name: Duplicates
            hidden: true
    buttons
		print_statements
			icon: icon-print
			vname: LBL_STATEMENTS_BUTTON_LABEL
			type: button
			perform: sListView.sendMassUpdate('{LIST_ID}', 'PrintStatements', true, null, false)
		email_accounts
			icon: icon-send
			vname: LBL_EMAIL_MULTI_BUTTON_LABEL
			type: button
			perform: sListView.emailEntries('{LIST_ID}', 'Accounts', false)
		email_statements
			icon: icon-send
			vname: LBL_EMAIL_STATEMENTS_BUTTON_LABEL
			type: button
			perform: sListView.emailEntries('{LIST_ID}', 'Accounts', true)
	massupdate_handlers
		--
			name: StatementPDF
			class: StatementPDF
			file: modules/Accounts/PDFStatement.php
		--
			name: ReassignAccounts
			class: Account
			file: modules/Accounts/Account.php
		--
			name: EmailMultiple
			class: Email
			file: modules/Emails/Email.php
		--
			name: SendPDFStatements
			class: Email
			file: modules/Emails/Email.php
view
	layouts
		Standard
			vname: LBL_LAYOUT_STANDARD
		Sales
			vname: LBL_LAYOUT_SALES
edit
	quick_create
		via_ref_input: true
hooks
	view
		--
			class_function: add_view_popups
			required_fields: [account_popup, account_popups]
	mass_update_fields
		--
			class_function: add_massupdate_fields

basic_filters
	nonzero
		# priority:
auto_filters
	is_supplier
    account_type
filters
	section
		ignore: true
		name: section
		vname: LBL_CUST_OR_SUPP
		default_value: all
		type: section
		options
			all
				vname: LBL_CUST_AND_SUPP
			customers
				vname: LBL_CUSTOMERS
				field: is_supplier
				operator: false
			suppliers
				vname: LBL_SUPPLIERS
				field: is_supplier
				operator: true
	nonzero
		default_value: false
		type: flag
		vname: LBL_NONZERO_BALANCE
		operator: non_zero
		field: balance
	any_phone
		vname: LBL_ANY_PHONE
		type: phone
		fields
			- phone_office
			- phone_fax
			- phone_alternate
	any_email
		vname: LBL_ANY_EMAIL
		type: email
		fields
			- email1
			- email2
	address_street
		type: varchar
		vname: LBL_ANY_ADDRESS
		fields
			- billing_address_street
			- shipping_address_street
	address_city
		type: varchar
		vname: LBL_CITY
		fields
			- billing_address_city
			- shipping_address_city
	address_state
		type: varchar
		vname: LBL_STATE
		fields
			- billing_address_state
			- shipping_address_state
	address_postalcode
		type: varchar
		vname: LBL_POSTAL_CODE
		fields
			- billing_address_postalcode
			- shipping_address_postalcode
	address_country
		type: varchar
		vname: LBL_COUNTRY
		fields
			- billing_address_country
			- shipping_address_country
	annual_revenue
		type: number
fields
	billing_address
		vname: LBL_BILLING_ADDRESS
		type: address
		source
			type: address
			prefix: billing_
	shipping_address
		vname: LBL_SHIPPING_ADDRESS
		type: address
		source
			type: address
			prefix: shipping_
	list_location
		vname: LBL_LIST_LOCATION
		type: location_city
		source
			fields: [billing_address_city, billing_address_state, billing_address_country]
    social_icons
        widget: SocialIconsWidget
widgets
	PrimaryContactWidget
		type: section
		path: modules/Accounts/widgets/PrimaryContactWidget.php
    IsSupplierWidget
        type: field
        path: modules/Accounts/widgets/IsSupplierWidget.php


