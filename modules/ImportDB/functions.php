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
define('IMPORTDB_DATA_FILE', AppConfig::upload_dir() . 'importdb.csv');
define('IMPORTDB_PROGRESS_FILE', AppConfig::cache_dir() . 'importdb_progress.log');
define('IMPORTDB_CHUNK_SIZE', 1000);

/**
 * Checks if current logged user is admin
 *
 * @return void
 */
function checkAdmin() {
	global $current_user;

	//Only allow admins to enter this screen
	if (!is_admin($current_user)) {
		$GLOBALS['log']->error("Non-admin user ($current_user->user_name) attempted to enter the ImportDB section");
		session_destroy();
		include('modules/Users/Logout.php');
	}
}

/**
 * @param string $module
 * @param bool $redirect_on_error
 * @return ImportDBImportProfile
 */
function getImportDBProfile($module, $redirect_on_error = true) {
	try {
		$profile = ImportDBProfileManager::getProfile(array_get_default($_REQUEST, 'profile', ''));
		if (!$profile->getAvailableModules($module)) {
			$err = sprintf(translate('MSG_ERR_MODULE_PROFILE', 'ImportDB'), $profile->getTitle(), $module);
			throw new Exception($err);
		}
		return $profile;
	} catch (Exception $e) {
		if ($redirect_on_error) {
			$location = array(
				'module' => $module,
				'action' => 'ImportDB',
				'msg_type' => 'error',
				'msg_text' => $e->getMessage(),
			);
			redirect($location);
		}
	}
	return null;
}

function redirect($location) {
	if (is_array($location)) $location = 'index.php?' . http_build_query($location, '', '&');
	header("Location: $location");
	die;
}

function importDBSelectControl($title, $name, $id, array $options, $selected = null, $required = false) {
	$req_title = translate('LBL_REQUIRED', 'ImportDB');
	$req_mark = $required ? '&nbsp;<span class="required" title="' . $req_title . '">*</span>' : '';
    $selected = formatSelectedValue($selected, $options);

	$out = array();
	if (!empty($title)) $out[] = "<label for='" . $id . "'>{$title}{$req_mark}</label>";
	$out[] = "<select name='{$name}' id='{$id}'>\r\n";

	foreach ($options as $value => $title) {
		$attr = ((!empty($selected) && ($value == $selected || $title == $selected) ) ? 'selected="selected"' : '');
		$out[] = sprintf("\t<option value=\"%s\" %s>%s</option>\r\n", $value, $attr, $title);
	}
	$out[] = "</select>\r\n";
	return implode("\r\n", $out);
}

/**
 *
 * @param mixed $value
 * @param array $options
 * @return string
 */
function formatSelectedValue($value, $options) {
    $selected = $value;

    if ($value == 'User') {
        $selected = 'Assigned to [User Name]';
    } elseif ($value == 'Phone') {
        $selected = 'Office Phone';
    } elseif ($value == 'Name' && isset($options['last_name'])) {
        $selected = 'Last Name';
    }

    return $selected;
}

/**
 * @param ImportDBImportProfile $profile
 * @param string $module
 * @return string
 */
function getImportDBModuleTitle(ImportDBImportProfile &$profile, $module) {
	$ent_title = $profile->getAvailableModules($module);
	if (empty($ent_title)) {
		redirect(
			array(
				'module' => $module,
				'action' => 'ImportDB',
				'step' => 'import',
				'profile' => $profile->getName(),
				'msg_type' => 'error',
				'msg_text' => 'Invalid data type.',
			)
		);
	}
	return $ent_title;
}

/**
 * @param ImportDBImportProfile $profile
 * @param string $module
 * @return void
 */
function checkImportDBFile(ImportDBImportProfile &$profile, $module) {
	if (!is_file(IMPORTDB_DATA_FILE)) {
		redirect(
			array(
				'module' => $module,
				'action' => 'ImportDB',
				'step' => 'import',
				'profile' => $profile->getName(),
				'msg_type' => 'error',
				'msg_text' => 'Invalid file.',
			)
		);
	}
}

/**
 * @param string $module
 * @return string
 */
function back2HomeLink($module) {
	return '<div style="font-size:xx-small;margin-bottom:15px;"><a href="index.php?module=' .
	       $module . '&amp;action=ImportDB">' . translate('LBL_BACK_TO_HOME', 'ImportDB') .
	       '</a></div>';
}

/**
 * @param array $data
 * @return bool
 */
function isDataEmpty(array $data) {
	return (empty($data) || $data == array(null));
}

/**
 * Workaround for broken files where \r is used as line break.
 * Normalizing it to \r\n
 *
 * @return void
 */
function normalizeImportCSV() {
	$tmp_name = IMPORTDB_DATA_FILE . '.tmp';

	$fold = fopen(IMPORTDB_DATA_FILE, 'rb');
	$fnew = fopen($tmp_name, 'w');

	while (!feof($fold)) {
		$string = fgets($fold);
		$string = str_replace("\r\n", "\r", $string);
		$string = str_replace("\r", "\r\n", $string);

		fwrite($fnew, $string);
	}

	fclose($fold);
	fclose($fnew);
	unlink(IMPORTDB_DATA_FILE);
	rename($tmp_name, IMPORTDB_DATA_FILE);
}


function importAddWarning($text)
{
	$warnings = array();
	if (!empty($_SESSION['__ImportDB']['warnings']))
		$warnings = $_SESSION['__ImportDB']['warnings'];
	$warnings[]= $text;
	importSessionSet('__ImportDB.warnings', $warnings);
}

if (file_exists('krumo/class.krumo.php')) {
	/**
	 * Debug output using krumo
	 *
	 * @param mixed $object
	 * @return void
	 */
	function debug_output($object) {
		static $included = false;
		if (!$included) {
			require_once 'krumo/class.krumo.php';
			$included = true;
		}
		krumo($object);
	}
}


function importSessionClose()
{
	$GLOBALS['ImportDB_SESSION_CLOSED'] = true;
	session_write_close();
}

function importSessionResume()
{
	if (!empty($GLOBALS['ImportDB_SESSION_CLOSED'])) {
		unset($GLOBALS['ImportDB_SESSION_CLOSED']);
		session_start();
	}
}

function importSessionSet($key, $value)
{
	if (!empty($GLOBALS['ImportDB_SESSION_CLOSED'])) {
		session_start();
	}
	$parts = explode('.', $key);
	$count = count($parts);
	$var =& $_SESSION;
	for ($i = 0; $i < $count; $i++) {
		$part = $parts[$i];
		if ($i != $count - 1) {
			if (!isset($var[$part]))
				$var[$part] = array();
			elseif (!is_array($var[$part]))
				break;
			$var =& $var[$part];
		} else {
			if ($value === null)
				unset($var[$part]);
			else
				$var[$part] = $value;
		}
	}
	if (!empty($GLOBALS['ImportDB_SESSION_CLOSED'])) {
		session_write_close();
	}
}

function importCheckEncoding(array $updates)
{
	$messages = array();
	foreach ($updates as $k => $v) {
		if (!mb_check_encoding($v,'UTF-8')) {
            $messages[] = sprintf(translate('MSG_NOT_UTF8', 'ImportDB'), $k);
		}
	}
	return $messages;
}

