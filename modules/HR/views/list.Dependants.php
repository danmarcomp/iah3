<?php return; /* no output */ ?>

detail
	type: list
layout
    buttons
        create_task
            widget: SubpanelCreateButton
            module: HR
            action: DependantEdit
            id_field: employee_id
            vname: LBL_CREATE_BUTTON_LABEL
            icon: icon-edit
            layout: Dependant
            params
                toplevel: true
    columns
        - name
        - dob
        - relationship
