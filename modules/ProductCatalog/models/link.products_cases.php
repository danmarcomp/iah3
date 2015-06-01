<?php return; /* no output */ ?>

detail
	type: link
	table_name: products_cases
	primary_key
		- product_id
		- case_id
fields
	app.date_modified
	app.deleted
	case
		type: ref
		bean_name: aCase
	product
		type: ref
		bean_name: Product
	quantity
		type: int
		default: 1
indices
	idx_prod_case_prod
		fields
			- product_id
	idx_prod_case_case
		fields
			- case_id
relationships
	products_cases
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: case_id
		lhs_bean: Product
		rhs_bean: aCase
