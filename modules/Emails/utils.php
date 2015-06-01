<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


/*********************************************************************************
 * $Id: $
 ********************************************************************************/

require_once 'include/utils/html_utils.php';

function format_reply_subject($subject)
{
    $subject = preg_replace('~^\s*((re|fw):\s*)+~i', '', $subject);
    return 'Re: ' . from_html($subject);
}

function format_reply_body(RowResult $email, $descriptions, $html = false)
{
    global $current_user;

	global $timedate;
    $sent = $email->getField('date_start');
    $date = custom_strftime('%c', strtotime($sent . ' GMT'));

	$header = 
    <<<BODY


-----Original Message-----
From: {$email->getField('from_name')} <{$email->getField('from_addr')}>
Sent: $date
To: {$email->getField('to_addrs')}
Subject: {$email->getField('name')}


BODY;

	// longreach - added - < and > in email addresses get unescaped by HTML editor
	// this can lead to the entire body of a reply or forward being ignored
	$header = htmlentities($header, ENT_COMPAT, 'UTF-8');

    if ($html && !empty($descriptions['html'])) {
		return nl2br($header) . show_inline_images(cleanHTML($descriptions['html'], true, true));
    }  elseif ( ($html && empty($descriptions['html']) ) || (!$html && !empty($descriptions['plain'])) ) {
    	$body = htmlentities($descriptions['plain'], ENT_COMPAT, 'UTF-8');
    	$body = preg_replace('~\t~', '    ', $body);
    	$body = preg_replace('~  ~', '&nbsp; ', $body);
        return nl2br($header . $body);
    } else {
    	$body = htmlspecialchars(html2plaintext($descriptions['html']));
        return nl2br($header . $body);
    }
}

function format_forward_subject($subject)
{
    return 'FW: ' . from_html($subject);
}

function add_email($emails, $add)
{
    if (!empty($add)) {
        if (!empty($emails)) {
            $emails = trim($emails) . '; ';
        }
        $emails = trim($emails) . $add;
    }
    return $emails;
}

function exclude_own_email($emails, $own)
{
	// longreach - start added
	global $current_user;
	$exclude = array();
	if(!empty($current_user->email1))
		$exclude[strtolower($current_user->email1)] = 1;
	if(!empty($current_user->email2))
		$exclude[strtolower($current_user->email2)] = 1;
	$exclude[strtolower($own)] = 1;
	// longreach - end added
	
    $emails = preg_split('~\s*;\s*~', $emails);
    foreach ($emails as $i => $email) {
		// longreach - modified
        if (isset($exclude[strtolower($email)])) unset($emails[$i]);
    }
    return implode('; ', $emails);
}

function display_format_addresses(&$row, $prefix) {
    $arr = parse_addrs($row[$prefix]);
    // support older imported messages where names are only in the *_names field
    $f_names = $prefix.'_names';
    $arr = merge_email_names($arr, $row[$f_names]);
	$zipped = array();
	foreach($arr as $addr)
		$zipped[] = format_email_link($addr['email'], $addr['display'], '', 'tabDetailViewDFLink', true);
	return implode(';<br>', $zipped);
}

function format_email_row($email, $display='') {
	$entry = "<{$email}>";
	if(strlen($display)) {
		if(preg_match('/^["\'](.*?)["\']$/', $display, $m))
			$display = $m[1];
		$entry = str_replace(array(';',','), '', $display).' '.$entry;
	}
	return compact('email', 'display', 'entry');
}

function normalize_emails_array($arr, $exclude_emails=array(), $remove_dups=true, $do_lookup=false) {
	$ret = array();
	$seen = array();
	if(is_array($exclude_emails))
		foreach($exclude_emails as $e) $seen[$e] = 1;
	else if(! empty($exclude_emails))
		$seen[$exclude_emails] = 1;
	foreach($arr as $row) {
		$addr = array_get_default($row, 'email', '');
		$name = array_get_default($row, 'display', '');
		if(! strlen($addr) || (! empty($seen[$addr]) && $remove_dups))
			continue;
		$ret[] = format_email_row($addr, $name);
	}
	if($do_lookup)
		$ret = merge_contact_ids($ret);
	return $ret;
}

function assign_email_fields($prefix, $email_array) {
    $fields = array();

    $zipped = zip_addrs($email_array);
	$f_emails = "{$prefix}_emails";
	$f_names = "{$prefix}_names";
	$f_ids = "{$prefix}_ids";
	$fields[$f_emails] = implode('; ', $zipped['emails']);
	$fields[$f_names] = implode('; ', $zipped['names']);
	$fields[$f_ids] = implode('; ', $zipped['ids']);
	$fields[$prefix] = implode('; ', $zipped['entries']);

    return $fields;
}

function parse_addrs($addrs) {
	$return_arr = array();

	$input = from_html($addrs);
	require_once('PEAR.php');
	require_once('Mail/RFC822.php');
	$parsed = Mail_RFC822::parseAddressList($input);
	if(! PEAR::isError($parsed)) {
		foreach($parsed as $row) {
			$display = $row->personal;
			$email = $row->mailbox;
			if(! strlen($email))
				continue;
			if($row->host != 'localhost')
				$email .= '@'.$row->host;
			$return_arr[] = format_email_row($email, $display);
		}
		return $return_arr;
	}

	$addrs1 = explode(';', $input);
	$addrs = array();
	foreach($addrs1 as $addr) {
		// split by commas as well, unless used in a display name
		while(preg_match('/^([^@]+@[^,]+),(.*)$/', $addr, $m)) {
			$addrs[] = $m[1];
			$addr = $m[2];
		}
		if(strlen(trim($addr)))
			$addrs[] = $addr;
	}

	foreach($addrs as $field) {
		$newarr = array();
		$field = trim($field);
		if(! strlen($field))
			continue;
		
		if(preg_match('/^(.*?)<([^>]*)>/', $field, $m)) {
			$display = trim($m[1]);
			$email = trim($m[2]);
		} else {
			$display = '';
			if(preg_match('/^<(.*?)>$/', $field, $m))
				$email = trim($m[1]);
			else
				$email = $field;
		}
		
		if(! strlen($email))
			continue;
		
		$return_arr[] = format_email_row($email, $display);
	}
	return $return_arr;
}

function merge_email_names($addrs, $names) {
	$names = array_map('trim', explode(';', $names));
	$i = 0;
	foreach($addrs as $k => $addr) {
		if(empty($addr['display']) && ! empty($names[$i])) {
			$row = format_email_row($addr['email'], $names[$i]);
			$addrs[$k]['display'] = $row['display'];
			$addrs[$k]['entry'] = $row['entry'];
		}
		$i ++;
	}
	return $addrs;
}

function merge_contact_ids($addrs) {
	global $db;
	$emails = array();
	foreach($addrs as $addr) {
		if(! empty($addr['email']) && empty($addr['contact_id']))
			$emails[] = $db->quote(strtolower($addr['email']));
	}
	$emails = array_unique($emails);
	if(count($emails)) {
		$map = array();
		$filter = " IN ('".implode("','", $emails)."')";
		$query = "SELECT id,email1,email2 FROM contacts WHERE "
			."((email1 IS NOT NULL AND email1 $filter) OR (email2 IS NOT NULL AND email2 $filter))"
			." AND NOT deleted";
		$result = $db->query($query, false);
		while($row = $db->fetchByAssoc($result, -1, false)) {
			$map[strtolower($row['email1'])] = $row['id'];
			$map[strtolower($row['email2'])] = $row['id'];
		}
		foreach($addrs as $k => $addr) {
			if(empty($addr['email']) || ! empty($addr['contact_id']))
				continue;
			$email = strtolower($addr['email']);
			if(isset($map[$email]))
				$addrs[$k]['contact_id'] = $map[$email];
		}
	}
	return $addrs;
}

function zip_addrs($addrs) {
	$entries = $names = $emails = $ids = array();
	foreach($addrs as $a) {
		$display = $email = $entry = $contact_id = '';
		extract($a);
		$names[] = $display;
		$emails[] = $email;
		$entries[] = $entry;
		$ids[] = $contact_id;
	}
	return compact('entries', 'names', 'emails', 'ids');
}

function get_raw_message_filename($messageId, $create = true)
{
	require_once('include/utils/file_utils.php');
	$hash = sha1($messageId);
	$dir = 'raw_email/' . substr($hash, 0, 1) . '/' . substr($hash, 1, 1) . '/' . substr($hash, 2, 1) . '/';
	if ($create) {
		$dirname = create_files_directory($dir);
	} else {
		$dirname = AppConfig::files_dir() . $dir;
	}
	$destination = $dirname . '/' . $hash;
	return $destination;
}


function lookup_email_addresses($addresses, $fetch_accounts)
{
	global $db;
	static $cache;
	$addresses = array_filter($addresses);
	$ret = array();
	$acct_ids = array();
	foreach ($addresses as $k => $address) {
		if (isset($cache[$address])) {
			$ret[$address] = $cache[$address];
			unset($addresses[$k]);
		}
	}
	if (empty($addresses))
		return $ret;
	$acct_emails = array();
	$quoted = array_map(array($db, 'quote'), $addresses);
	$in = "('" . join("', '", $quoted) . "')";
	$meta = array(
		'contacts' => array('Contacts', 'primary_account_id'),
		'users' => array('Users', "''", ),
		'leads' => array('Leads', "''",),
	);
	$queries = array();
	foreach ($meta as $table => $m) {
		$queries[] = "SELECT id, '{$m[0]}' type, LOWER('{$m[0]}') rel_name, phone_work, IF(email1 IN $in, email1, NULL) email1, IF(email2 IN $in, email2, NULL) email2, CONCAT(first_name, ' ',  last_name) name, {$m[1]} account_id FROM {$table} WHERE (email1 IN $in OR email2 IN $in) AND deleted=0";
	}
	$query = '(' . join (') UNION (', $queries) . ')';
	$res = $db->query($query, true, "Error looking up email addresses");

	while ($row = $db->fetchByAssoc($res)) {
		$row['email1'] = strtolower($row['email1']);
		$row['email2'] = strtolower($row['email2']);
		if ($fetch_accounts && !empty($row['account_id'])) {
			$acct_ids[] = $row['account_id'];
		}
		if (!empty($row['email1'])) {
			$ret[$row['email1']][$row['type']][] = $row;
			if ($fetch_accounts && !empty($row['account_id']))
				$acct_emails[$row['account_id']][] = $row['email1'];
		}
		if (!empty($row['email2'])) {
			$ret[$row['email2']][$row['type']][] = $row;
			if ($fetch_accounts && !empty($row['account_id']))
				$acct_emails[$row['account_id']][] = $row['email2'];
		}
	}

	if ($fetch_accounts) {
		$in_ids = '';
		if (!empty($acct_ids)) {
			$in_ids = " OR id IN ('" . join("', '", $acct_ids) . "')";
		}
		$query = "SELECT id, 'Accounts' type, 'accounts' rel_name, name, email1, email2 FROM accounts WHERE (email1 IN $in OR email2 IN $in $in_ids) AND deleted = 0";
		$res = $db->query($query, true, "Error looking up email addresses");

		while ($row = $db->fetchByAssoc($res)) {
			$row['email1'] = strtolower($row['email1']);
			$row['email2'] = strtolower($row['email2']);
			if (!empty($row['email1']))
				$ret[$row['email1']]['Accounts'][] = $row;
			if (!empty($row['email2']))
				$ret[$row['email2']]['Accounts'][] = $row;
			if (isset($acct_emails[$row['id']])) {
				foreach ($acct_emails[$row['id']] as $e) {
					$ret[$e]['Accounts'][] = $row;
				}
			}
		}
	}

	foreach ($ret as $address => $data) {
		if (!isset($cache[$address])) {
			$cache[$address] = $data;
		}
	}

	$reordered = array();
	foreach ($addresses as $address) {
		if (isset($ret[$address]))
			$reordered[$address] = $ret[$address];
	}
	return $reordered;
}


