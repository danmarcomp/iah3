<?php return; /* no output */ ?>

detail
	type: link
	table_name: products_assemblies
	primary_key
		- products_id
		- assembly_id
fields
	app.created_by_user
	app.date_modified
	app.deleted
	assembly_id
		type: char
		len: 36
		required: true
	products_id
		type: char
		len: 36
		required: true
	quantity
        vname: LBL_LIST_QUANTITY
		type: float
		dbType: double
		required: true
		default: 1
	discount_value
        vname: LBL_DISCOUNT_VALUE
		type: float
		dbType: double
		default: 0
	discount
        vname: LBL_DISCOUNT_STANDARD
		type: ref
		default: ""
		bean_name: Discount
	discount_name
		type: varchar
		required: true
		default: ""
		len: 80
	discount_type
        vname: LBL_DISCOUNT_TYPE
		type: enum
        options: assembly_discount_type_dom
        options_add_blank: true
		len: 20
		required: false
		default: ""
indices
	idx_pro_pro_ass
		fields
			- products_id
	idx_ass_pro_ass
		fields
			- assembly_id
	idx_products_ass
		fields
			- products_id
			- assembly_id
			- deleted
relationships
	products_assemblies
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: products_id
		join_key_rhs: assembly_id
		lhs_bean: Product
		rhs_bean: Assembly
