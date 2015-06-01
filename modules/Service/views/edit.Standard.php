<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    scripts
        --
            file: "modules/Service/contract.js"
	form_buttons
    form_hooks
        oninit: "initServiceForm({FORM})"
    sections
		--
			id: main
			elements
                - contract_no
                -
                - account
                -
                - is_active
                -
                --
                	name: description
                	colspan: 2
