<?php return; /* no output */ ?>

@info
	cannot guess bean name
		- list_id
		- related_id
detail
	type: bean
	bean_file: modules/EmailMan/EmailMan.php
	unified_search: false
	duplicate_merge: false
	comment: Email campaign queue
	table_name: emailman
	primary_key: id
fields
	app.date_entered
	app.date_modified
	app.modified_user
	app.deleted
    id
        type: int
        len: 11
        required: true
        auto_increment: true
	user
		vname: LBL_USER_ID
		type: ref
		reportable: false
		comment: User ID representing assigned-to user
		bean_name: User
		massupdate: false
	campaign
		vname: LBL_LIST_CAMPAIGN
		type: ref
		reportable: false
		comment: ID of related campaign
		bean_name: Campaign
		massupdate: false
	marketing
		vname: LBL_LIST_MESSAGE_NAME
		type: ref
		reportable: false
		comment: ""
		bean_name: EmailMarketing
		massupdate: false
	list
		vname: LBL_LIST_ID
		type: ref
		reportable: false
		comment: Associated list
		bean_name: ""
		massupdate:false
	send_date_time
		vname: LBL_LIST_SEND_DATE_TIME
		type: datetime
		massupdate: false
	in_queue
		vname: LBL_LIST_IN_QUEUE
		type: bool
		comment: Flag indicating if item still in queue
		massupdate: false
	in_queue_date
		vname: LBL_IN_QUEUE_DATE
		type: datetime
		comment: Datetime in which item entered queue
		massupdate: false
	send_attempts
		vname: LBL_LIST_SEND_ATTEMPTS
		type: int
		default: 0
		comment: Number of attempts made to send this item
	related
		vname: LBL_RELATED_ID
		type: ref
		reportable: false
		comment: ID of Sugar object to which this item is related
		dynamic_module: related_type
		massupdate: false
	related_type
		vname: LBL_RELATED_TYPE
		type: varchar
		len: 100
		comment: Descriptor of the Sugar object indicated by related_id
		massupdate: false
		options
			- Contacts
			- Leads
			- Prospects
fields_compat
	recipient_name
		type: varchar
		len: 255
		source: non-db
	recipient_email
		type: varchar
		len: 255
		source: non-db
	message_name
		type: varchar
		len: 255
		source: non-db
	campaign_name
		vname: LBL_LIST_CAMPAIGN
		type: varchar
		len: 50
		source: non-db
indices
	idx_eman_rel
		fields
			- related_type
			- related_id
	idx_eman_list
		fields
			- list_id
			- user_id
			- deleted
	idx_eman_campaign_id
		fields
			- campaign_id
