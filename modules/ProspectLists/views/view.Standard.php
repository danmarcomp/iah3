<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        export_targets
        	icon: icon-export
            vname: LBL_EXPORT_BUTTON_LABEL
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"ExportTargets"}, null, true);
            async: false
	sections
		--
			id: main
			elements
                - name
                - assigned_user
                - list_type
                --
                    name: domain_name
                    hidden: bean.list_type!=exempt_domain
                - dynamic
                -
                - total_entries
                - date_entered
                -
                - date_modified
                --
                    name: description
                    colspan: 2
    subpanels
        --
            name: prospects
        --
            name: contacts
        --
            name: leads
        --
            name: users
        - securitygroups
