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
                - assigned_user
                --
                    name: description
                    colspan: 3
        --
            id: template_form
			widget: EmailTemplateWidget
