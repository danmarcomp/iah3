<?php return; /* no output */ ?>

detail
	type: link
	table_name: products_suppliers
	primary_key
		- product_id
		- supplier_id
fields
	app.date_modified
	app.deleted
	product
		type: ref
		bean_name: Product
	supplier
		type: ref
		bean_name: Account
	parent_type
		type: varchar
		len: 30
indices
	idx_pro_supp_pro
		fields
			- product_id
	idx_pro_supp_supp
		fields
			- supplier_id
relationships
	products_suppliers
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: supplier_id
		relationship_role_column: parent_type
		relationship_role_column_value: ProductCatalog
		lhs_bean: Product
		rhs_bean: Account
	assemblies_suppliers
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: supplier_id
		relationship_role_column: parent_type
		relationship_role_column_value: Assemblies
		lhs_bean: Assembly
		rhs_bean: Account
	assets_suppliers
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: supplier_id
		relationship_role_column: parent_type
		relationship_role_column_value: Assets
		lhs_bean: Asset
		rhs_bean: Account
	supp_assem_suppliers
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: product_id
		join_key_rhs: supplier_id
		relationship_role_column: parent_type
		relationship_role_column_value: SupportedAssemblies
		lhs_bean: SupportedAssembly
		rhs_bean: Account
