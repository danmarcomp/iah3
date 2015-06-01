list
	default_order_by: name
    view_in_popup: edit
fields
	address
		vname: LBL_ADDRESS
		type: address
		source
			type: address
			prefix: ""
	list_address
		vname: LBL_ADDRESS
		vname_module: CompanyAddress
		type: location_city
		source
			fields: [address_city, address_state, address_country]
	address_format
		vname: LBL_ADDRESS_FORMAT
		widget: AddressFormatInput
	logo_clear
		vname: LBL_DEFAULT_LOGO
		vdesc: LBL_DEFAULT_LOGO_DESCRIPTION
		type: bool
		editable: true
