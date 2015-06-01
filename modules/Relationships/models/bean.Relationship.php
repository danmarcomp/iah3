<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Relationships/Relationship.php
	unified_search: false
	duplicate_merge: false
	table_name: relationships
	primary_key: id
fields
	app.id
	app.deleted
	relationship_name
		vname: LBL_RELATIONSHIP_NAME
		type: varchar
		required: true
		len: 150
	lhs_module
		vname: LBL_LHS_MODULE
		type: varchar
		required: true
		len: 100
	lhs_table
		vname: LBL_LHS_TABLE
		type: varchar
		required: true
		len: 64
	lhs_key
		vname: LBL_LHS_KEY
		type: varchar
		required: true
		len: 64
	rhs_module
		vname: LBL_RHS_MODULE
		type: varchar
		required: true
		len: 100
	rhs_table
		vname: LBL_RHS_TABLE
		type: varchar
		required: true
		len: 64
	rhs_key
		vname: LBL_RHS_KEY
		type: varchar
		required: true
		len: 64
	join_table
		vname: LBL_JOIN_TABLE
		type: varchar
		len: 64
	join_key_lhs
		vname: LBL_JOIN_KEY_LHS
		type: varchar
		len: 64
	join_key_rhs
		vname: LBL_JOIN_KEY_RHS
		type: varchar
		len: 64
	relationship_type
		vname: LBL_RELATIONSHIP_TYPE
		type: varchar
		len: 64
	relationship_role_column
		vname: LBL_RELATIONSHIP_ROLE_COLUMN
		type: varchar
		len: 64
	relationship_role_column_value
		vname: LBL_RELATIONSHIP_ROLE_COLUMN_VALUE
		type: varchar
		len: 50
	reverse
		vname: LBL_REVERSE
		type: bool
		default: 0
indices
	idx_rel_name
		fields
			- relationship_name
