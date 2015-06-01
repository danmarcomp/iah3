<?php return; /* no output */ ?>

LBL_SEARCH_FORM_TITLE: Opportunity Search
LBL_VIEW_FORM_TITLE: Opportunity View
LBL_LIST_FORM_TITLE: Opportunity List
LBL_OPPORTUNITY_NAME: Opportunity Name
LBL_OPPORTUNITY: Opportunity
LBL_NAME: Opportunity Name
LBL_INVITEE: Contacts
LBL_LIST_OPPORTUNITY_NAME: Opportunity
LBL_LIST_ACCOUNT_NAME: Account Name
LBL_LIST_AMOUNT: Amount
LBL_LIST_DATE_CLOSED: Exp. Close
LBL_LIST_SALES_STAGE: Sales Stage
LBL_ACCOUNT_ID: Account ID
LBL_CURRENCY_ID: Currency ID
LBL_TEAM_ID: Team ID
db_sales_stage: LBL_LIST_SALES_STAGE
db_name: LBL_NAME
db_amount: LBL_LIST_AMOUNT
db_date_closed: LBL_LIST_DATE_CLOSED
UPDATE: Opportunity - Currency Update
UPDATE_DOLLARAMOUNTS: Update U.S. Dollar Amounts
UPDATE_VERIFY: Verify Amounts
UPDATE_VERIFY_TXT:
	Verifies that the amount values in opportunities are valid decimal numbers with only numeric characters(0-9) and decimals(.)
UPDATE_FIX: Fix Amounts
UPDATE_FIX_TXT:
	Attempts to fix any invalid amounts by creating a valid decimal from the current amount. This will backup any amounts it modifies into a database field amount_backup. If you run this and notice bugs, do not rerun this without restoring from the backup as it may overwrite the backup with new invalid data.
UPDATE_DOLLARAMOUNTS_TXT:
	Update the U.S. Dollar amounts for opportunities based on the current set currency rates. This value is used to calculate Graphs and List View Currency Amounts.
UPDATE_CREATE_CURRENCY: Creating New Currency
UPDATE_VERIFY_FAIL: Record Failed Verification
UPDATE_VERIFY_CURAMOUNT: Current Amount
UPDATE_VERIFY_FIX: Running Fix would give
UPDATE_INCLUDE_CLOSE: Include Closed Records
UPDATE_VERIFY_NEWAMOUNT: New Amount
UPDATE_VERIFY_NEWCURRENCY: New Currency
UPDATE_DONE: Done
UPDATE_BUG_COUNT: Bugs Found and Attempted to Resolve
UPDATE_BUGFOUND_COUNT: Bugs Found
UPDATE_COUNT: Records Updated
UPDATE_RESTORE_COUNT: Record Amounts Restored
UPDATE_RESTORE: Restore Amounts
UPDATE_RESTORE_TXT: Restores amount values from the backups created during Fix.
UPDATE_FAIL: "Could not update - "
UPDATE_NULL_VALUE: Amount is NULL setting it to 0 -
UPDATE_MERGE: Merge Currencies
UPDATE_MERGE_TXT:
	Merge multiple currencies into a single currency. If you notice that there are multiple currency records for the same currency, you may choose to merge them together. This will also merge the currencies for all other modules.
LBL_ACCOUNT_NAME: Account Name
LBL_AMOUNT: Amount
LBL_CURRENCY: Currency
LBL_DATE_CLOSED: Expected Close Date
LBL_TYPE: Type
LBL_NEXT_STEP: Next Step
LBL_LEAD_SOURCE: Lead Source
LBL_SALES_STAGE: Sales Stage
LBL_PROBABILITY: Probability
LBL_DESCRIPTION: Description
LBL_DUPLICATE: Possible Duplicate Opportunity
MSG_SHOW_DUPLICATES:
    Creating this opportunity may potentially create a duplicate opportunity. You may either click on Save to continue creating this new opportunity with the previously entered data or you may click Cancel.
MSG_DUPLICATE:
	Creating this opportunity may potentially create a duplicate opportunity. You may either select an opportunity from the list below or you may click on Save to continue creating a new opportunity with the previously entered data.
LBL_SAVE_MODULE: Save Opportunity
LBL_NEW_FORM_TITLE: Create Opportunity
LNK_NEW_OPPORTUNITY: Create Opportunity
LNK_OPPORTUNITY_LIST: Opportunities
ERR_DELETE_RECORD: A record number must be specified to delete the opportunity.
LBL_TOP_OPPORTUNITIES: My Top Open Opportunities
NTC_REMOVE_OPP_CONFIRMATION: Are you sure you want to remove this contact from this opportunity?
OPPORTUNITY_REMOVE_PROJECT_CONFIRM: Are you sure you want to remove this opportunity from this project?
LBL_AMOUNT_BACKUP: Amount Backup
LBL_DEFAULT_SUBPANEL_TITLE: Opportunities
LBL_ACTIVITIES_SUBPANEL_TITLE: Activities
LBL_HISTORY_SUBPANEL_TITLE: History
LBL_RAW_AMOUNT: Raw Amount
LBL_LEADS_SUBPANEL_TITLE: Leads
LBL_CONTACTS_SUBPANEL_TITLE: Contacts
LBL_PROJECTS_SUBPANEL_TITLE: Projects
LBL_ASSIGNED_TO_NAME: Assigned User Name
LBL_LIST_ASSIGNED_TO_NAME: Assigned User
LBL_SECURITYGROUPS_SUBPANEL_TITLE: Teams
LBL_FORECAST_CATEGORY: Forecast Category
LBL_LIST_PROBABILITY: Prob. (%)
LBL_OPPS_DASHLET: A customizable view into Opportunities
LBL_FROM_DATE_CLOSED: Closing After Date
LBL_TO_DATE_CLOSED: Closing Before Date
LBL_CONVERTED_LEAD: Converted Lead
LBL_NOT_CLOSED: All Not Closed
LBL_SALES_ORDERS_SUBPANEL_TITLE: Sales Orders
LBL_SALES_ORDERS: Sales Orders
LBL_PARTNER: Partner
LBL_PARTNER_NAME: Partner Name
LBL_INVOICES: Invoices
LBL_THREADS_SUBPANEL_TITLE: Threads
LBL_THREADS: Threads
LBL_ADDITIONAL_DETAILS: Opportunity Details
LBL_EXPENSES_SUBPANEL_TITLE: Expense Reports
LBL_EXPENSE_REPORTS: Expense Reports
LBL_DO_CONVERT: Convert
LBL_WEIGHTED_AMOUNT: Weighted Amount
LBL_CONV_QUOTE: Create Quote
