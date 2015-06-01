<?php return; /* no output */ ?>

detail
	type: link
	table_name: kb_articles_tags
	primary_key: [article_id, tag_id]
fields
	app.deleted
	app.date_modified
	article
		required: true
		type: ref
		bean_name: KBArticle
	tag
		required: true
		type: ref
		bean_name: KBTag
indices
	idx_kb_articles_tag
		fields
			- tag_id
