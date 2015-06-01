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

require_once('modules/Reports/utils.php');

function upgrade_report_spec($spec, $is_data=false) {
	static $field_map = array(
		'assigned_user_id' => 'assigned_user',
	);
	static $id_map = array(
		'cas.account_name' => array(
			'field' => 'account',
		),
		'cas.cust_contact_name' => array(
			'field' => 'cust_contact',
		),
		'cont.full_name' => array(
			'field' => 'name',
		),
		'cont.account_name' => array(
			'field' => 'primary_account',
		),
		'inv.account_name' => array(
			'field' => 'billing_account',
		),
		'inv.billing_account_name' => array(
			'field' => 'billing_account',
		),
		'inv.shipping_account_name' => array(
			'field' => 'shipping_account',
		),
		'invo.account_name' => array(
			'field' => 'billing_account',
		),
		'invo.billing_account_name' => array(
			'vname' => 'LBL_BILLING_ACCOUNT',
			'field' => 'billing_account',
		),
		'invo.shipping_account_name' => array(
			'vname' => 'LBL_SHIPPING_ACCOUNT',
			'field' => 'shipping_account',
		),
		'lead.full_name' => array(
			'field' => 'name',
		),
		'oppo.account_name' => array(
			'field' => 'account',
		),
		'proj.account_name' => array(
			'field' => 'account',
		),
	);
	
	$sources = $columns = $filters = $fvals = array();
	$s_spec = maybe_unserialize($spec['sources']);
	$c_spec = maybe_unserialize($spec['report_fields']);
	$t_spec = maybe_unserialize(array_get_default($spec, 'totals'));
	if(! $is_data)
		$f_spec = maybe_unserialize($spec['filters']);
	else
		$f_spec = array();
	$primary = null;
	$ord_spec = maybe_unserialize($spec['sort_order']);
	
	foreach($s_spec as $name => $s) {
		$src = array(
			'name' => $name,
			'display' => '',
		);
		$type = array_get_default($s, 'type');
		foreach(array('module', 'bean_name', 'display', 'parent', 'vname', 'vname_module', 'field_name', 'required') as $f) {
			if(isset($s[$f])) {
				$src[$f] = $s[$f];
				if($src[$f] === 'true') $src[$f] = true;
				else if($src[$f] === 'false') $src[$f] = false;
			}
		}
		if($type == 'union' || $type == 'primary') {
			$src['display'] = 'primary';
			$primary = $name;
		}
		if(isset($src['field_name'])) {
			if($src['field_name'] == 'billing_account_link')
				$src['field_name'] = 'billing_account';
			else if($src['field_name'] == 'shipping_account')
				$src['field_name'] = 'shipping_account';
		}
		$sources[$name] = $src;
	}
	
	if($t_spec)
	foreach($t_spec as $id => $t) {
		$t['total'] = $t['type'];
		unset($t['type']);
		if(preg_match('~^(\w+)\.(\w+)$~', $t['field'], $m)) {
			$t['source'] = $m[1];
			$t['name'] = $m[2];
		} else
			$t['name'] = $t['field'];
		unset($t['field']);
		$c_spec[$id] = $t;
	}
	
	foreach($c_spec as $id => $c) {
		$display = array_get_default($c, 'display');
		if($display == 'query_only')
			continue; // not needed
		$col = array(
			'field' => $c['name'],
		);
		if($display == 'hidden') {
			if($c['name'] == 'id' || $c['name'] == 'currency_id' || $c['name'] == 'exchange_rate')
				continue;
			$col['hidden'] = true;
		}
		if(isset($field_map[$col['field']]))
			$col['field'] = $field_map[$col['field']];
		foreach(array('vname', 'vname_module', 'format', 'total') as $f)
			if(isset($c[$f]))
				$col[$f] = $c[$f];
		if(! empty($c['display_vname']))
			$col['vname'] = $c['display_vname'];
		if(! empty($c['display_name']))
			$col['label'] = $c['display_name'];
		$idl = strtolower($id);
		if(isset($id_map[$idl])) {
			$col = array_merge($col, $id_map[$idl]);
		}
		if($display == 'grouped')
			$col['grouped'] = 1;
		if(preg_match('~^((\w+)\.)?(\w+)!?(\w*):?(\w*)?$~', $id, $m)) {
			$src = array_get_default($m, 2);
			$format = $m[4];
			if($format && empty($col['format']))
				$col['format'] = $format;
		}
		else
			$src = null;
		if(! empty($c['format'])) {
			if($c['format'] == 'dateonly')
				$col['format'] = 'date_only';
			else
				$col['format'] = $c['format'];
		}
		$src = array_get_default($c, 'source', $src);
		if($src) {
			$col['source'] = $src;
			///$col['alias'] = $src .'.'. $col['field'];
		}
		$columns[] = $col;
	}
	
	foreach($f_spec as $f) {
		$id = $f['field'];
		$s = explode('.', $id, 2);
		if(count($s) < 2) continue;
		$fn = $s[1];
		if($fn == 'deleted') continue;
		$nf = array('field' => $fn);
		if($s[0] != $primary)
			$nf['source'] = $s[0];
		$oper = array_get_default($f, 'mode');
		if($oper) {
			switch($oper) {
			case 'equals': $oper = 'eq'; break;
			case 'not_equals': $oper = 'not_eq'; break;
			case 'false': $oper = 'eq'; $f['value'] = '0'; break;
			case 'true': $oper = 'eq'; $f['value'] = '1'; break;
			case 'one_of': $oper = 'eq'; break;
			}
			$nf['operator'] = $oper;
		}
		if($spec['run_method'] != 'interactive' || (isset($f['user_config']) && ! $f['user_config']))
			$nf['hidden'] = true;
		if(isset($f['value']))
			$fvals[$nf['field']] = $f['value'];
		else if(isset($f['values']))
			$fvals[$nf['field']] = $f['values'];
		if(! empty($_REQUEST['debug'])) {
		pr2($f);
		pr2($nf);
		}
		$filters[] = $nf;
	}
	
	$order = array();
	if($ord_spec)
	foreach($ord_spec as $ord) {
		if(preg_match('~^(\w+)\.(\w+)(!\w+)?(:\w+)?$~', $ord, $parts)) {
			$nord = array(
				'field' => $parts[2],
			);
			if($parts[1] != $primary)
				$nord['source'] = $parts[1];
			$order[] = $nord;
		}
		else if(! empty($_REQUEST['debug'])) pr2($ord);
	}
	
	$series = array_get_default($spec, 'chart_series');
	if($series && preg_match('~^(\w+)\.(.*)$~', $series, $m))
		if($m[1] == $primary)
			$series = $m[2];
	
	return array('sources_spec' => $sources, 'columns_spec' => $columns, 'filters_spec' => $filters, 'filter_values' => $fvals, 'sort_order' => $order, 'chart_series' => $series);
}

function test() {
	global $db;
	$q = "SELECT * FROM reports_templates";
	$r = $db->query($q, "Error selecting report templates");
	$test = array();
	while( ($row = $db->fetchByAssoc($r, -1, false)) ) {
		$test[] = $row;
	}
	foreach($test as $t) {
		$nc = upgrade_report_spec($t);
		pr2($nc);
		break;
	}
}

//test();
