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


require_once('XTemplate/xtpl.php');
require_once('include/config/LanguageManager.php');
require_once('include/ListView/ListFormatter.php');

if(!is_admin())
	sugar_die("You must be an administrator to access this page.");

$show_module = isset($_REQUEST['show_module']) ? $_REQUEST['show_module'] : '';
$langmgr = new LanguageManager();
if(! $show_module) $langmgr->invalidateCache();
$langs = $langmgr->getSetting('labels');
$language = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
$default_language = isset($_REQUEST['default_language']) ? $_REQUEST['default_language'] : AppConfig::setting('locale.defaults.language');
$default_language_name = $langs[$default_language];

//unset($langs[$default_language]);
array_unshift($langs, '');
$def_lang_opts = get_select_options_with_id($langs, $default_language);
$alt_lang_opts = get_select_options_with_id($langs, $language);

echo get_module_title('LanguagePacks', $mod_strings['LBL_LANGUAGE_FILES_TITLE'], false);

$xtpl = new XTemplate('modules/Administration/LanguageFiles.html');
$xtpl->assign('APP', $app_strings);
$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('DEF_LANGUAGE_OPTS', $def_lang_opts);
$xtpl->assign('ALT_LANGUAGE_OPTS', $alt_lang_opts);
$xtpl->assign('DEFAULT_LANGUAGE', $default_language);
$xtpl->assign('LANGUAGE', $language);
$xtpl->parse('search');
$xtpl->out('search');



// we can't use restore_language_file (in utils.php) because it caches without accounting for the language
function load_mod_language_files($language, $module, &$info) {
	global $langmgr;
	//$info['included_files'][] = $path;
	
	if($langmgr->loadModule($language, $module, true, true)) {
		$ret = array(
			'strings' => $langmgr->standard['strings'][$language][$module],
			'lists' => $langmgr->standard['lists'][$language][$module],
		);
	}
	return $ret;
}


function load_app_language_files($language, &$info) {
	return load_mod_language_files($language, 'app', $info);
}


function module_language_info($m, $language) {
	global $default_language;
	
	$info = array(
		'status' => '',
		'spanClass' => 'todaysTask',
		'missing_fields' => array(),
		'missing_list_fields' => array(),
		'errors' => array(),
		'included_files' => array(),
	);
	
	if($m == '(Application)') {
		$en_fields = load_app_language_files($default_language, $info);
	}
	else {
		$en_fields = load_mod_language_files($default_language, $m, $info);
	}
	$info['default_error_count'] = count($info['errors']);
	if($en_fields === false || empty($en_fields['strings']))
		// unnecessary
		return $info;

	if($m == '(Application)')
		$m_fields = load_app_language_files($language, $info);
	else
		$m_fields = load_mod_language_files($language, $m, $info);
	$info['alt_error_count'] = count($info['errors']) - $info['default_error_count'];
	
	if(empty($m_fields['strings'])) {
		$info['status'] = "Translation file lang.$language.strings.php is missing or empty.";
		$info['spanClass'] = 'error';
		return $info;
	}
	else {
		$m_strings =& $m_fields['strings'];
		foreach($en_fields['strings'] as $k => $v) {
			if(!isset($m_strings[$k]))
				$info['missing_fields'][$k] = $v;
		}
	}
	
	// not all modules use mod_list_strings
	if(! empty($en_fields['lists'])) {
		if(empty($m_fields['lists'])) {
			$info['status'] = "Translation file lang.$language.lists.php is missing or empty.";
			$info['spanClass'] = 'error';
		}
		else {
			$m_list_strings =& $m_fields['lists'];
			foreach($en_fields['lists'] as $k_list => $v_list) {
				if(is_array($v_list))
					foreach($v_list as $k => $v) {
						if(! isset($m_list_strings[$k_list][$k]))
							$info['missing_list_fields'][$k_list][$k] = $v;
					}
				// TODO - what if value is not a list
			}
		}
	}
	$miss_f = count($info['missing_fields']);
	$miss_l = 0;
	foreach($info['missing_list_fields'] as $arr)
		$miss_l += count($arr);
	if($miss_f + $miss_l == 0) {
		$info['status'] = 'OK';
	}
	else {
		if($info['status'])  $info['status'] .= '<br>';
		$info['status'] .= "Missing $miss_f string labels, $miss_l list entries";
	}
	if($info['default_error_count'])
		$info['status'] .= "<br>$default_language language files reported {$info['default_error_count']} warnings or errors.";
	if($info['alt_error_count'])
		$info['status'] .= "<br>$language language files reported {$info['alt_error_count']} warnings or errors.";
	if($info['status'] == 'OK' && !count($info['errors']))
		$info['spanClass'] = 'success';
	return $info;
}


if($language) {

	$all_modules = array_merge($moduleList, $modInvisList, $modInvisListActivities);
	$all_modules[] = '(Application)';
	sort($all_modules);
	
	if($show_module && in_array($show_module, $all_modules)) {
		require_once('include/config/format/ConfigWriter.php');
		$writer = new ConfigWriter();
		$info = module_language_info($show_module, $language);
		$rows = $list_rows = array();
		$strings_var = ($show_module == '(Application)' ? 'app_strings' : 'mod_strings');
		$lists_var = ($show_module == '(Application)' ? 'app_list_strings' : 'mod_list_strings');
		ksort($info['missing_fields']);
		foreach($info['missing_fields'] as $k => $v)
			$rows[] = array('field' => $k, 'value' => '&lsquo;'.to_html($v).'&rsquo;');
		$add_code = $writer->encodeToString($info['missing_fields']);
		ksort($info['missing_list_fields']);

		foreach($info['missing_list_fields'] as $lk => $trans) {
			//ksort($trans);
			foreach($trans as $k => $v) {
				if(! strlen($k)) $k = '&lsquo;&rsquo;';
				$list_rows[] = array('field' => "$lk / $k", 'value' => '&lsquo;'.to_html($v).'&rsquo;');
			}
		}
		$add_code_list = $writer->encodeToString($info['missing_list_fields']);
		
		if($info['errors']) {
			echo '<p>';
			echo get_form_header($mod_strings['LBL_ERRORS_REPORTED_TITLE'], '', false);
			foreach($info['errors'] as $err) {
				foreach($err as $f=>$k)
					$xtpl->assign(strtoupper($f), $k);
				$xtpl->parse('error_list.row');
			}
			$xtpl->parse('error_list');
			$xtpl->out('error_list');
		}
		
		if(! count($rows) && ! count($list_rows)) {
			echo get_form_header($mod_strings['LBL_MISSING_FIELDS_TITLE'] . ": $show_module ($language)", '', false);
			echo '<h4>'.$mod_strings['LBL_NO_MISSING_FIELDS'].'</h4>';
		}
		if(count($rows)) {
			echo get_form_header($mod_strings['LBL_MISSING_FIELDS_TITLE'] . ": $show_module ($language)", '', false);
			$lr = new ListResult();
			$lr->rows = $rows;
			$lr->formatted_rows = $rows;
			$lr->fields = array(
				'field' => array(
					'name' => 'field',
					'type' => 'varchar',
				),
				'value' => array(
					'name' => 'value',
					'type' => 'varchar',
				),
			);
			$l = new ListFormatter();
			$l->show_mass_update = false;
			$l->show_navigation = false;
			$l->show_checks = false;
			$l->columns = array(
				array(
					'field' => 'field',
					'vname' => 'LBL_LIST_FIELD_NAME',
					'width' => 30,
				),
				array(
					'field' => 'value',
					'vname' => 'LBL_LIST_FIELD_VALUE',
					'width' => 60,
				)
			);
			$l->outputHtml($lr);

			if($show_module == '(Application)')
				$langfile2 = "include/language/lang.$language.strings.php";
			else
				$langfile2 = "modules/$show_module/language/lang.$language.strings.php";
			echo '<br><h4>'.str_replace('_FILENAME_', $langfile2, $mod_strings['LBL_SAMPLE_CODE']).'</h4>';
			echo '<textarea rows="15" cols="60">'.to_html($add_code).'</textarea>';
		}
		if(count($list_rows)) {
			echo get_form_header($mod_strings['LBL_MISSING_LISTS_TITLE'] . ": $show_module ($language)", '', false);
			$lr = new ListResult();
			$lr->rows = $list_rows;
			$lr->formatted_rows = $list_rows;
			$lr->fields = array(
				'field' => array(
					'name' => 'field',
					'type' => 'varchar',
				),
				'value' => array(
					'name' => 'value',
					'type' => 'varchar',
				),
			);
			$l = new ListFormatter();
			$l->show_mass_update = false;
			$l->show_navigation = false;
			$l->show_checks = false;
			$l->columns = array(
				array(
					'field' => 'field',
					'vname' => 'LBL_LIST_FIELD_NAME',
					'width' => 30,
				),
				array(
					'field' => 'value',
					'vname' => 'LBL_LIST_FIELD_VALUE',
					'width' => 60,
				)
			);
			$l->outputHtml($lr);

			if($show_module == '(Application)')
				$langfile2 = "include/language/lang.$language.lists.php";
			else
				$langfile2 = "modules/$show_module/language/lang.$language.lists.php";
			echo '<br><h4>'.str_replace('_FILENAME_', $langfile2, $mod_strings['LBL_SAMPLE_CODE']).'</h4>';
			echo '<textarea rows="15" cols="60">'.to_html($add_code_list).'</textarea>';
		}
	}
	else {		
		$mod_info = array();
		foreach($all_modules as $m) {
			$info = module_language_info($m, $language);
			if($info['status']) {
				$params = array(
					'module' => 'Administration',
					'action' => 'LanguageFiles',
					'default_language' => $default_language,
					'language' => $language,
					'show_module' => $m,
				);
				$mod_info[] = array(
					'module_name' => '<a class="listViewNameLink" href="?'.encode_query_string($params).'">'.$m.'</a>',
					'status' => '<span class="'.$info['spanClass'].'">'.$info['status'].'</span>',
				);
			}
		}
		
		echo '<p>';
		echo get_form_header($mod_strings['LBL_MODULE_LANGUAGE_FILES_TITLE'] . ": $language", '', false);
		require_once('include/ListView/ListFormatter.php');
		$lr = new ListResult();
		$lr->rows = $mod_info;
		$lr->formatted_rows = $mod_info;
		$lr->fields = array(
			'module_name' => array(
				'name' => 'module_name',
				'type' => 'varchar',
			),
			'status' => array(
				'name' => 'status',
				'type' => 'varchar',
			),
		);
		$l = new ListFormatter();
		$l->show_mass_update = false;
		$l->show_navigation = false;
		$l->show_checks = false;
		$l->columns = array(
			array(
				'field' => 'module_name',
				'vname' => 'LBL_LIST_MODULE_NAME',
				'width' => 30,
			),
			array(
				'field' => 'status',
				'vname' => 'LBL_LIST_STATUS',
				'width' => 60,
			)
		);
		$l->outputHtml($lr);
	}

}

?>
