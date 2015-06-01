<?php return; /* no output */ ?>

list
	default_order_by: name
    mass_merge_duplicates: true
    show_favorites: true
    layouts
        Duplicates
            vname: LBL_DUPLICATES
            view_name: Duplicates
            hidden: true
    buttons
		email_contacts
			icon: icon-send
			vname: LBL_EMAIL_MULTI_BUTTON_LABEL
			perform: sListView.emailEntries('{LIST_ID}', 'Contacts', false)
			type: button
		export_vcards
			icon: icon-export
			vname: LBL_EXPORT_VCARDS
			perform: sListView.exportVcards('{LIST_ID}', 'Contacts')
			type: button
			acl: export
		quick_campaign
			icon: theme-icon create-Campaign
			vname: LBL_QCAMPAIGN_BUTTON_LABEL
			perform: sListView.quickCampaign('{LIST_ID}', 'Contacts')
			type: button
		convert_to_target_list
			icon: theme-icon create-ProspectList
			vname: LBL_CONVERT_TO_TARGET_LIST
			perform: sListView.convertToTargetList('{LIST_ID}', 'Contacts')
			type: button
        mail_merge
        	icon: icon-send
            vname: LBL_MAILMERGE
            perform: sListView.mailMerge('{LIST_ID}', 'Contacts')
            type: button
	massupdate_handlers
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
view
	scripts
		--
			file: "modules/Contacts/contacts.js"
edit
	quick_create
		via_ref_input: true
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
    name_email
    	source
    		value_function: join_name_email
    		fields: [first_name, last_name, email1]
    social_icons
        widget: SocialIconsWidget
basic_filters
	- primary_account
filters
	any_phone
		vname: LBL_ANY_PHONE
		type: phone
		fields
			- phone_mobile
			- phone_work
			- phone_other
			- phone_fax
			- assistant_phone
	any_email
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
		vname: LBL_ANY_CITY
		type: varchar
		fields
			- primary_address_city
			- alt_address_city
	address_state
		vname: LBL_ANY_STATE
		type: varchar
		fields
			- primary_address_state
			- alt_address_state
	address_postalcode
		vname: LBL_ANY_POSTAL_CODE
		type: varchar
		fields
			- primary_address_postalcode
			- alt_address_postalcode
	address_country
		vname: LBL_ANY_COUNTRY
		type: varchar
		fields
			- primary_address_country
			- alt_address_country
widgets
	GoogleSyncStatusWidget
		type: section
		path: modules/Contacts/widgets/GoogleSyncStatusWidget.php
	SalutationNameWidget
		type: field
		path: modules/Contacts/widgets/SalutationNameWidget.php
	RelatedRecordsWidget
		type: section
		path: modules/Contacts/widgets/RelatedRecordsWidget.php
