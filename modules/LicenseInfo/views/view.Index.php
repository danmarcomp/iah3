<?php return; /* no output */ ?>

detail
    type: view
    icon: License
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				--
					name: license_status
					vname: LBL_LICENSE_STATUS
					colspan: 2
				--
					name: support_info
					vname: LBL_LICENSE_SUPPORT
					colspan: 2
				--
					name: product_list
					vname: LBL_LICENSE_PRODUCTS
					colspan: 2
					hidden: ! bean.product_list
		--
			id: update
			elements
				--
					name: key_form
					vname: LBL_QUICK_UPDATE
					rowspan: 3
				--
					name: vendor_name
					vname: LBL_VENDOR_NAME
				--
					name: vendor_url
					vname: LBL_VENDOR_URL
				--
					name: vendor_address
					vname: LBL_VENDOR_ADDRESS
				--
					name: upload_form
					vname: LBL_UPLOAD_LICENSE
					rowspan: 3
				--
					name: vendor_phone
					vname: LBL_VENDOR_PHONE
				--
					name: vendor_email_sales
					vname: LBL_VENDOR_EMAIL_SALES
				--
					name: vendor_email_support
					vname: LBL_VENDOR_EMAIL_SUPPORT
