<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
            id: main
            elements
                - name
                -
                - transaction_type
                - currency
                - account
                - amount
                - subcontract
                -
                - related
                - 
                --
                    name: description
                    colspan: 2
        --
            id: prepaid_info
            widget: PrepaidInfoWidget
