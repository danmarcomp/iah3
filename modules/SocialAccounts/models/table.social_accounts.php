<?php return; /* no output */ ?>

detail
	type: table
	table_name: social_accounts
	primary_key: id
fields
    app.id
    app.date_modified
    app.deleted
    type
        type: varchar
        len: 20
        required: true
        comment: Social Account type (Facebook, Twitter, etc)
    login
        type: varchar
        required: true
        len: 255
        comment: Social resource's login
    link_type
        type: varchar
        required: true
        default: profile
        len: 100
        comment: Social resource's link type (profile, group, etc)
	related_type
		vname: LBL_RELATED_TYPE
		required: true
		type: module_name
	related
		vname: LBL_RELATED_ID
		required: true
		type: ref
		dynamic_module: related_type
    params
        type: varchar
        length: 255
indices
	idx_acc_rel
		fields
            - type
			- related_id
			- related_type
