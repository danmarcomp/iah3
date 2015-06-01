<?php return; /* no output */ ?>

list
	buttons
		convert_to_target_list
			icon: theme-icon create-ProspectList
			label: LBL_CONVERT_TO_TARGET_LIST
			perform: sListView.convertToTargetList('{LIST_ID}', 'Prospects')
			type: button
	massupdate_handlers
		--
			name: ConvertToTargetList
			class: ProspectList
			file: modules/ProspectLists/ProspectList.php
filters
	first_name
	last_name
	do_not_call
		operator: =
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
	email_opt_out
		operator: =
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
fields
	primary_address
		vname: LBL_PRIMARY_ADDRESS
		type: address
		source
			type: address
			prefix: primary_
	alt_address
		vname: LBL_ALTERNATE_ADDRESS
		type: address
		source
			type: address
			prefix: alt_
