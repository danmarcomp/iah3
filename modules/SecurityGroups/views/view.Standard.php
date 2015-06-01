<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements    
                - name
                -
                - assigned_user
                - date_entered
                - noninheritable
                - date_modified
                --
                    name: description
                    colspan: 2
	subpanels
		- users
		- aclroles
