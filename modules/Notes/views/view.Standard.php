<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- parent
                - assigned_user
                - contact
                - portal_flag
                - contact_phone
                - date_entered
                - contact_email
                - date_modified
		--
			id: info
			elements
                --
                    name: name
                    colspan: 2
                --
                	name: filename
                	colspan: 2
                --
                    name: description
                    colspan: 2
    subpanels
        - securitygroups
