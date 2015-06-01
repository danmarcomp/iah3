<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
        --
            id: acl_settings
            vname: LBL_ACCESS_SETTINGS
            widget: UserInfoWidget
        --
            id: acl_roles
            vname: LBL_ACL_ROLES
            widget: RolesWidget
    subpanels
        - aclroles
        - securitygroups
