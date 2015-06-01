<?php return; /* no output */ ?>

list
    mass_merge_duplicates: true
filters
	type
	assigned_user
	status
fields
	expense_items
		vname: LBL_EXPENSE_ITEMS
		widget: ExpenseItemsWidget
widgets
	ExpenseItemsWidget
		type: section
		path: modules/ExpenseReports/widgets/ExpenseItemsWidget.php
