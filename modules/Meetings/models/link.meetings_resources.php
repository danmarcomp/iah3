<?php return; /* no output */ ?>

detail
	type: link
	table_name: meetings_resources
	primary_key: [meeting_id, resource_id]
fields
	app.date_modified
	app.deleted
	meeting
		vname: LBL_MEETING_ID
		required: true
		type: ref
		bean_name: Meeting
	resource
		vname: LBL_RESOURCE_ID
		required: true
		type: ref
		bean_name: Resource
indices
	meetings_resources_ix2
		fields
			- resource_id
relationships
	meetings_resources
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: meeting_id
		join_key_rhs: resource_id
		lhs_bean: Meeting
		rhs_bean: Resource
