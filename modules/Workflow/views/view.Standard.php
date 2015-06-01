<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
		start
            vname: LBL_ACTIVATE_BUTTON_LABEL
            async: false
            onclick: return SUGAR.ui.sendForm(document.forms.DetailForm, {"action":"StartStopWorkflow"}, null);
	sections
		--
			id: main
			elements
                --
                    name : name
                    colspan : 2
                --
                    name : occurs_when
                    colspan: 2
                --
                    name : execute_mode
                    colspan: 2
                --
                    name : trigger_module
                    colspan: 2
                --
                    name : trigger_action
                    colspan: 2
                --
                    name : operation_owner
                    colspan: 2
                --
                    name : status
                    colspan: 2
        - workflow_items
    scripts
        --
            file: "modules/Workflow/workflow.js"
	form_hidden_fields
		workflow_items:

