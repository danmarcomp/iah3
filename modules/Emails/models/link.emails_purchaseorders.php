<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_purchaseorders
	primary_key: [email_id, po_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	po
		type: ref
		bean_name: PurchaseOrder
indices
	idx_po_email_po
		fields
			- po_id
relationships
	emails_purchaseorders_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: po_id
		lhs_bean: Email
		rhs_bean: PurchaseOrder
