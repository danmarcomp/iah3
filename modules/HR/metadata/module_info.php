<?php return; /* no output */ ?>

detail
	primary_bean: Employee
	tab_visibility: normal
	default_group: LBL_TABGROUP_PROJECT_MANAGEMENT
web_actions
    DependantDelete
    DependantDetail
    DependantEdit
    DependantSave
    EditLeave
    SaveLeave
acl
    fixed
        edit: owner
        export: admin
        delete: disabled
	editable: false
