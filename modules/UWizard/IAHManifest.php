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

require_once 'include/config/format/ConfigParser.php';

class IAHManifestError extends IAHError {}

class IAHManifest
{
	static private $types = array('langpack', 'module', 'patch', 'theme', 'holidays', 'pers_pack');
	private $data = array();
	private $load_error = false;

	public function __construct($filename)
	{
		try {
			$this->data = ConfigParser::load_file($filename);
		} catch (IAHConfigFileError $e) {
			$this->load_error = $e->getMessage();
		}
	}

	public static function allowedTypes()
	{
		return self::$types;
	}

	public function validate()
	{
		if ($this->load_error) {
			return $this->load_error;
		}
		if(! $this->checkVitalInfo($err))
			return $err;
		$type = $this->getType();

		$thisVersion = AppConfig::version();
		$versionChecked = false;
		$versionMatched = true;
		$versions = $this->path('acceptable_sugar_versions.regex_matches');
		$expectedVersions = array();
		if (!empty($versions)) {
			if(! is_array($versions)) $versions = array($versions);
			$expectedVersions = $versions;
			$versionChecked = true;
			$versionMatched = false;
			foreach ($versions as $version) {
				if(preg_match("/^$version\$/", $thisVersion)) {
					$versionMatched = true;
					break;
				}
			}
		}
		$versions = $this->path('acceptable_sugar_versions.exact_matches');
		if (!empty($versions) && (!$versionChecked || !$versionMatched)) {
			if(! is_array($versions)) $versions = array($versions);
			$expectedVersions = array_merge($expectedVersions, $versions);
			$versionChecked = true;
			$versionMatched = false;
			foreach ($versions as $version) {
				if($version === $thisVersion) {
					$versionMatched = true;
					break;
				}
			}
		}

		if ($versionChecked && !$versionMatched) {
			$error = translate('ERR_INVALID_VERSION', 'UWizard');
			$error = str_replace('{CURRENT_VERSION}', $thisVersion, $error);
			$dispVers = array_map(array('IAHManifest', 'makeDisplayableVersion'), $expectedVersions);
			$error = str_replace('{EXPECTED_VERSIONS}', join(', ', $dispVers), $error);
			return $error;
		}

		return '';
	}
	
	static public function makeDisplayableVersion($version) {
		$version = str_replace(array('.*', '.+'), array('*', '+'), $version);
		$version = preg_replace('~\\\\(.)~', '\1', $version);
		$version = preg_replace('~\[(\d)\]~', '\1', $version);
		return $version;
	}
	
	public function checkVitalInfo(&$ret_error) {
		$type = $this->getType();
		if(! $type || ! strlen($this->getId()) || ! strlen($this->getVersion())) {
			$ret_error = 'ERR_EMPTY_TYPE_ID_VERSION';
			return false;
		}
		if (!in_array($type, self::$types)) {
			$ret_error = 'ERR_INVALID_TYPE';
			return false;
		}
		if ($type == 'theme' && !$this->path('theme_id')) {
			$ret_error = 'ERR_NO_THEME_ID';
			return false;
		}
		return true;
	}

	public function loaded()
	{
		return empty($this->load_error) && !empty($this->data);
	}

	public function getType()
	{
		return $this->path('type');
	}

	public function getDate()
	{
		return $this->path('published_date');
	}

	public function getVersion()
	{
		return $this->path('version');
	}

	public function getDescription()
	{
		return $this->path('description');
	}

	public function getAuthor()
	{
		return $this->path('author');
	}

	public function getId()
	{
		return $this->path('id');
	}


	public function getName()
	{
		return $this->path('name');
	}

	public function path($path, $default = null)
	{
		$data = $this->data;
		$parts = explode('.', $path);
		foreach ($parts as $part) {
			if (!is_array($data)) return $default;
			if (!isset($data[$part])) return $default;
			$data = $data[$part];
		}
		return $data;
	}
}

