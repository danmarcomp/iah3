<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    mass_merge_duplicates: true
    show_favorites: true
    layouts
        Duplicates
            vname: LBL_DUPLICATES
            view_name: Duplicates
            hidden: true
    massupdate_handlers
		--
			name: ReassignLeads
			class: Lead
			file: modules/Leads/Lead.php
		--
			name: ConvertToTargetList
			class: ProspectList
			file: modules/ProspectLists/ProspectList.php
		--
			name: RTFMerge
			class: RTFMerge
			file: modules/MailMerge/RTFMerge.php
		--
			name: EmailMultiple
			class: Email
			file: modules/Emails/Email.php
		--
			name: QuickCampaign
			class: Campaign
			file: modules/Campaigns/Campaign.php
	buttons
		email_leads
			icon: icon-send
			vname: LBL_EMAIL_MULTI_BUTTON_LABEL
			perform: sListView.emailEntries('{LIST_ID}', 'Leads', false)
			type: button
			params
		quick_campaign
			icon: theme-icon create-Campaign
			vname: LBL_QCAMPAIGN_BUTTON_LABEL
			perform: sListView.quickCampaign('{LIST_ID}', 'Leads')
		convert_to_target_list
			icon: theme-icon create-ProspectList
			vname: LBL_CONVERT_TO_TARGET_LIST
			perform: sListView.convertToTargetList('{LIST_ID}', 'Leads')
			type: button
        mail_merge
        	icon: icon-send
            vname: LBL_MAILMERGE
            perform: sListView.mailMerge('{LIST_ID}', 'Leads')
            type: button
fields
    primary_address
        vname: LBL_PRIMARY_ADDRESS
        type: address
        source
            type: address
            prefix: primary_
            no_account: true
    alt_address
        vname: LBL_ALTERNATE_ADDRESS
        type: address
        source
            type: address
            prefix: alt_
            no_account: true
    social_icons
        widget: SocialIconsWidget
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: status
		operator: not_eq
		value
			- Converted
			- Dead
	phone
		vname: LBL_ANY_PHONE
        type: phone
		fields
			- phone_mobile
			- phone_work
			- phone_other
			- phone_fax
			- phone_home
	email
		vname: LBL_ANY_EMAIL
        type: email
		fields
			- email1
			- email2
	address_street
		vname: LBL_ANY_ADDRESS
        type: varchar
		fields
			- primary_address_street
			- alt_address_street
	address_city
		vname: LBL_CITY
        type: varchar
		fields
			- primary_address_city
			- alt_address_city
	address_state
		vname: LBL_STATE
        type: varchar
		fields
			- primary_address_state
			- alt_address_state
	address_postalcode
		vname: LBL_POSTAL_CODE
        type: varchar
		fields
			- primary_address_postalcode
			- alt_address_postalcode
	address_country
		vname: LBL_COUNTRY
        type: varchar
		fields
			- primary_address_country
			- alt_address_country
	category
		operator: =
		fields
			- category
			- category2
			- category3
			- category4
			- category5
			- category6
			- category7
			- category8
			- category9
			- category10
hooks
	mass_update_fields
		--
			class_function: add_massupdate_fields

