<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                --
                    name: name
                    colspan: 2
				--
					name: description
					colspan: 2

		--
			id: acl_roles
			title: LBL_LIST_FORM_TITLE
			widget: RolesWidget
    subpanels
    	--
    		name: users
    		hidden: bean.id=default_-role-0000-0000-000000000001
    	--
    		name: securitygroups
    		hidden: bean.id=default_-role-0000-0000-000000000001
