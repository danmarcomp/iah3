<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
	sections
		--
			id: main
			elements
                - name
                - full_number
                - assigned_user
                - receiving_stage
                - packing_slip_num
                - date_entered
                - po
                - date_modified
                - warehouse
                - supplier
                - #credit_memo (FIXME issue with po_id)
                -
				--
					name: description
					colspan: 2

		- line_items
    subpanels
        - history
        - securitygroups
