<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_hidden_fields
        session_id:
	sections
		--
            id: main
            elements
                - name
                - status
                - inbound_email
                - from_name
                - date_start
				--
					name:  template
					widget: EmailTemplateEditCreate
					onchange: hide_show_template_edit()
                - all_prospect_lists
                -
                - set_dripfeed_delay
                -
