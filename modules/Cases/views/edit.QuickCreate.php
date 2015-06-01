<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Cases/cases.js"
    form_hooks
        oninit: "initCaseForm({FORM})"
    sections
		--
			elements
                - priority
                - status
                - account
                - type
                - contract
                -
                - cust_contact
                - cust_phone_no
                - asset
                - asset_serial_no
                --
                	name: name
                	colspan: 2
                --
                	name: description
                	colspan: 2
