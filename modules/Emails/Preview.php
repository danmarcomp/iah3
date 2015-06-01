<?php

$fs = array(
	'name', 'to_addrs', 'cc_addrs', 'bcc_addrs',
	'description', 'description_html', 'replace_fields',
	'from_name', 'from_addr',
	'parent_type', 'parent_id',
);
foreach($fs as $f)
	$$f = array_get_default($_REQUEST, $f);

require_once('modules/Emails/utils.php');

$to = normalize_emails_array(parse_addrs($to_addrs), null, true, true);
$cc = normalize_emails_array(parse_addrs($cc_addrs), null, true, true);
$bcc = normalize_emails_array(parse_addrs($bcc_addrs), null, true, true);

$parent = $params = array();
if($replace_fields) {
	if($parent_type && $parent_id) {
		$parent[$parent_type] = $parent_id;
		$params['primary'] = $parent_type;
	}
	if($parent_type != 'Contacts' || ! $parent_id) {
		$all_contacts = array_merge($to, $cc, $bcc);
		foreach ($all_contacts as $contact) {
			if (! empty($contact['contact_id'])) {
				$parent['Contacts'] = $contact['contact_id'];
				if(empty($params['primary']))
					$params['primary'] = 'Contacts';
			}
		}
	}

	require_once('modules/EmailTemplates/TemplateParser.php');
	list($name, $description, $description_html) = TemplateParser::parse_generic(
			array($name, $description, $description_html),
			$parent,
			$params
		);
}

$headers = array();
foreach(array('to', 'cc', 'bcc') as $f) {
	if($f == 'to' || $$f) {
		$names = implode(', ', array_column($$f, 'entry'));
		$headers[$f] = $names;
	}
}
$headers['from'] = $from_name . ' <' . $from_addr . '>';
$headers['subject'] = $name;
$title = to_html($name);

$labels = array(
	'to' => 'LBL_TO',
	'cc' => 'LBL_CC',
	'bcc' => 'LBL_BCC',
	'from' => 'LBL_FROM',
	'subject' => 'LBL_SUBJECT',
);

$header_rows = '';
foreach($headers as $h => $hval) {
	$lbl = translate($labels[$h], 'Emails');
	$header_rows .= '<tr style="background-color: #eee"><td width="15%" align="right"><b>' . to_html($lbl) . ':</b></td><td>' . to_html($hval) . '</td></tr>';
}

echo <<<EOF
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$title}</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #fff; color: #222; font-family: Lucida Sans, Helvetica; font-size: 10pt">
<table style="border: 1px solid #999" cellpadding=5 cellspacing=0 width="100%">
<thead>
{$header_rows}
</thead>
<tbody>
<tr><td colspan="2" style="border-top: 1px solid #999; padding: 10px">
{$description_html}
</td></tr>
</tbody>
</table>
EOF;

sugar_cleanup(true);

?>
