<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: primary_contact_info
			vname: LBL_PRIMARY_CONTACT
            widget: PrimaryContactWidget
			hidden: !mode.B2C
			edit: true
		--
			elements
				--
					name: name
					editable: !mode.B2C
				--
					name: phone_office
					hidden: mode.B2C
                --
                    name: is_supplier
                    widget: IsSupplierWidget
				- account_popups
				- website
				--
					name: phone_fax
					hidden: mode.B2C
				- ticker_symbol
				--
					name: phone_alternate
					hidden: mode.B2C
				- member_of
				--
					name: email1
					hidden: mode.B2C
				- employees
				--
					name: email2
					hidden: mode.B2C
				- ownership
				- rating
				- industry
				- sic_code
				- account_type
				- annual_revenue
				- temperature
				- currency
				- partner
				--
					name: assigned_user
					hidden: mode.B2C
		--
			id: customer_info
			vname: LBL_SALES_INFORMATION_TITLE
			toggle_display
				name: is_supplier
				value: 0
			elements
				- first_invoice
				- last_invoice
				- balance
				- default_discount
				- credit_limit
				- default_shipper
				- tax_code
				- tax_information
				- default_terms
		--
			id: supplier_info
			vname: LBL_PURCHASING_INFORMATION_TITLE
			toggle_display
				name: is_supplier
				value: 1
			elements
				- balance_payable
				- default_purchase_discount
				- purchase_credit_limit
				- default_purchase_shipper
				- default_purchase_terms
		--
			id: address_info
			vname: LBL_ADDRESS_INFORMATION
			elements
				--
					name: billing_address
					copy_to: shipping_address
                    copy_to_rel_contacts: true
				--
					name: shipping_address
					copy_to: billing_address
                    copy_to_rel_contacts: true
			hidden: mode.B2C
        --
            id: social_accounts
            widget: SocialAccountsWidget
		--
			id: account_popups
			vname: LBL_ACCOUNT_POPUPS
			toggle_display
				name: account_popups
			columns: 1
			elements
				- account_popup
				- sales_popup
				- service_popup
		--
			id: description_info
			columns: 1
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				- description
