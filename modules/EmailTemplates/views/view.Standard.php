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
                - date_modified
                - assigned_user
                - date_entered
                - subject
                - description
                --
                    name: body_html
                    colspan: 2
        --
            id: template_additional
            widget: EmailTemplateWidget
    subpanels
        - securitygroups
