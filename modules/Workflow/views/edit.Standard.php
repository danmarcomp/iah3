<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
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
        - workflow_items
    scripts
        --
            file: "modules/Workflow/workflow.js"
	form_hidden_fields
		reload : 1
		workflow_items:
		status: inactive
