<?php return; /* no output */ ?>

list
    show_favorites: true
hooks
    edit
        --
            class_function: add_additional_hiddens
filters
	- tags_filter
basic_filters
	- tags_filter
fields
	kb_tags
		vname: 
		widget: KBTagsWidget
	tags_filter
		vname: 
		widget: KBTagsFilterButton
widgets
	KBTagsWidget
		type: section
		path: modules/KBArticles/widgets/KBTagsWidget.php
	KBTagsFilterButton
		type: form_button
		path: modules/KBArticles/widgets/KBTagsFilterButton.php


