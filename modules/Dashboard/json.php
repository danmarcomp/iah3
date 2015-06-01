<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

function json_get_dashboard_details() {
	// Get access to the global objects
	global $db, $current_user;
	// Initialize the object list
	$arCases = $arActivites = $arInvoices = array();
	// First of all, get all active cases
	$strQuery = "
		SELECT
			id,
			name,
			status,
			priority,
			case_number,
			description,
			UNIX_TIMESTAMP(date_entered) as date_entered
		FROM
			cases
		WHERE
			account_id IN {$_SESSION['viewable']['accounts']}
		AND
			deleted  = 0
		ORDER BY
			date_entered DESC
		LIMIT 0, 2
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	while (($arRow = $db->fetchByAssoc($objResult)) != null) {
		// Add the case record
		$arCases[] = $arRow;
	}
	// First of all, get all active cases
	$strQuery = "
		SELECT
			B.name,
			UNIX_TIMESTAMP(CONCAT_WS(' ', B.date_start, B.time_start)) as time_start,
			B.quantity,
			CONCAT_WS(' ', U.first_name, U.last_name) as user_name,
			B.related_type,
			CONCAT_WS('', C.name, T.name) as related_item
		FROM
			booked_hours B
		LEFT JOIN
			users U
		ON
			B.assigned_user_id = U.id
		LEFT OUTER JOIN
			cases C
		ON
			B.related_id = C.id
		AND
			B.related_type = 'Cases'
		LEFT OUTER JOIN
			project_task T
		ON
			B.related_id = T.id
		AND
			B.related_type = 'ProjectTask'
		WHERE
			B.deleted  = 0
		AND
			B.account_id IN {$_SESSION['viewable']['accounts']}
		ORDER BY
			time_start DESC
		LIMIT 0, 2
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	while (($arRow = $db->fetchByAssoc($objResult)) != null) {
		// Add the case record
		$arActivites[] = $arRow;
	}
	// First of all, get all active cases
	$strQuery = "
		SELECT
			prefix,
			invoice_number,
			name,
			amount_due,
			UNIX_TIMESTAMP(CONCAT_WS(' ', due_date, '00:00:00')) as due_date
		FROM
			invoice
		AND
			amount_due > 0
		WHERE
			billing_account_id IN {$_SESSION['viewable']['accounts']}
		AND
			deleted  = 0
		ORDER BY
			due_date DESC
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	while (($arRow = $db->fetchByAssoc($objResult)) != null) {
		// Add the case record
		$arInvoices[] = $arRow;
	}
	// Initialize the count array
	$arCounts = array (
		'cases' => 0,
		'quotes' => 0,
		'invoices' => 0,
		'bugs' => 0,
	);
	// First of all, get all active cases
	$strQuery = "
		SELECT
			count(*) as record_count
		FROM
			cases
		WHERE
			account_id IN {$_SESSION['viewable']['accounts']}
		AND
			deleted  = 0
		AND
			status NOT LIKE '%Closed%'
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	if ($arRow = $db->fetchByAssoc($objResult)) {
		// Store the number of cases
		$arCounts['cases'] = $arRow['record_count'];
	}
	// First of all, get all active cases
	$strQuery = "
		SELECT
			count(*) as record_count
		FROM
			bugs B,
			accounts_bugs A
		WHERE
			A.account_id IN {$_SESSION['viewable']['accounts']}
		AND
			A.bug_id = B.id
		AND
			B.deleted  = 0
		AND
			B.status != 'Closed'
		AND
			B.status != 'Rejected'
	";
	
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	if ($arRow = $db->fetchByAssoc($objResult)) {
		// Store the number of cases
		$arCounts['bugs'] = $arRow['record_count'];
	}
	// First of all, get all active cases
	$strQuery = "
		SELECT
			count(*) as record_count
		FROM
			invoice
		WHERE
			billing_account_id IN {$_SESSION['viewable']['accounts']}
		AND
			deleted  = 0
		AND
			amount_due > 0
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	if ($arRow = $db->fetchByAssoc($objResult)) {
		// Store the number of cases
		$arCounts['invoices'] = $arRow['record_count'];
	}
	// First of all, get all active cases
	$strQuery = "
		SELECT
			count(*) as record_count
		FROM
			quotes
		WHERE
			account_id IN {$_SESSION['viewable']['accounts']}
		AND
			deleted  = 0
		AND
			quote_stage NOT LIKE '%Closed%'
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	if ($arRow = $db->fetchByAssoc($objResult)) {
		// Store the number of cases
		$arCounts['invoices'] = $arRow['record_count'];
	}
	// Generate the return array
	$arReturn = array (
		'cases' => $arCases,
		'activities' => $arActivites,
		'invoices' => $arInvoices,
		'counts' => $arCounts,
	);
	// Return the result
	json_return_value($arReturn);
}

$json_supported_actions['get_dashboard_details'] = array('login_required' => 'portal');

?>