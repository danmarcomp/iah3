<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
    scripts
        --
            file: "modules/Cases/cases.js"
    form_hooks
    	oninit: "initCaseForm({FORM})"
	sections
		--
            id: main
            elements
                - case_number
                -
                - priority
                - account
                - status
                - cust_contact
                - category
                - cust_phone_no
                - type
                - cust_req_no
                - contract
                -
                - asset
                - arrival_time
                - asset_serial_no
                - travel_time
                - vendor_rma_no
                - date_billed
                - vendor_svcreq_no
                - assigned_user
                --
                    name: name
                    colspan: 2
                --
                    name: description
                    colspan: 2
                --
                    name: resolution
                    colspan: 2
        - required_skills
