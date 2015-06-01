<?php return; /* no output */ ?>

detail
	type: link
	table_name: products_warehouses
	primary_key
		- warehouse_id
		- product_id
fields
	app.date_modified
	app.deleted
	app.created_by_user
	product
		type: ref
		bean_name: Product
	warehouse
        vname: LBL_WAREHOUSE
		type: ref
		bean_name: CompanyAddress
	in_stock
        vname: LBL_LIST_IN_STOCK
		type: int
		len: 11
		widget: QuickEditListElt
indices
	idx_product_id
		fields
			- product_id
	idx_warehouse_id
		fields
			- warehouse_id
relationships
	products_warehouses_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: warehouse_id
		lhs_bean: Product
		rhs_bean: CompanyAddress
