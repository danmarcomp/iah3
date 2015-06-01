<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */



/**
 * This file is used to control the authentication process. 
 * It will call on the user authenticate and controll redirection 
 * based on the users validation
 *
 */
class SugarAuthenticate{
	var $userAuthenticateClass = 'SugarAuthenticateUser';
	var $authenticationDir = 'SugarAuthenticate';
	
	// longreach - added - prevent automatic header output and exit
	var $no_output = false;
	var $auth_error = '';
	var $invalid_credentials;
	
	/**
	 * Constructs SugarAuthenticate
	 * This will load the user authentication class
	 *
	 * @return SugarAuthenticate
	 */
	function SugarAuthenticate(){
		require_once('modules/Users/authentication/'. $this->authenticationDir . '/'. $this->userAuthenticateClass . '.php');
		$this->userAuthenticate = new $this->userAuthenticateClass();
		
	}
	/**
	 * Authenticates a user based on the username and password
	 * returns true if the user was authenticated false otherwise
	 * it also will load the user into current user if he was authenticated
	 *
	 * @param string $username
	 * @param string $password
	 * @return boolean 
	 */
	function loginAuthenticate($username, $password, $PARAMS=null){
		if(isset($_SESSION['login_error']))
			unset($_SESSION['login_error']);
		
		if ($this->userAuthenticate->loadUserOnLogin($username, $password, $PARAMS)) {
			return $this->postLoginAuthenticate();
		}
		if(strtolower(get_class($this)) != 'sugarauthenticate' && empty($this->userAuthenticate->invalid_credentials)) {
			$sa = new SugarAuthenticate();
			$sa->no_output = $this->no_output;
			if($sa->loginAuthenticate($username, $password, $PARAMS)){
				return true;	
			}
			$this->auth_error = $sa->login_error;
			$this->invalid_credentials = $sa->invalid_credentials;
		} else {
			$this->auth_error = $this->userAuthenticate->auth_error;
			$this->invalid_credentials = $this->userAuthenticate->invalid_credentials;
		}
			
		$_SESSION['login_user_name'] = $username;
		$_SESSION['login_password'] = $password;
		if(! $this->auth_error)
			$this->auth_error = translate('ERR_INVALID_PASSWORD', 'Users');
	
		return false;

	}

	/**
	 * Once a user is authenticated on login this function will be called. Populate the session with what is needed and log anything that needs to be logged
	 *
	 */
	function postLoginAuthenticate(){
		
		//THIS SECTION IS TO ENSURE VERSIONS ARE UPTODATE
		require_once ('modules/Administration/updater_utils.php');
		/*require_once ('modules/Versions/CheckVersions.php');
		$invalid_versions = get_invalid_versions();
		if (!empty ($invalid_versions)) {
			$_SESSION['invalid_versions'] = $invalid_versions;
		}*/
		
		//just do a little house cleaning here 
		foreach(array('login_password', 'login_error', 'login_user_name', 'ACL') as $_f)
			if(isset($_SESSION[$_f])) unset($_SESSION[$_f]);
		
		//set the user theme
		$authenticated_user_theme = isset($_REQUEST['login_theme']) ? $_REQUEST['login_theme']
			: (isset($_COOKIE['ck_login_theme_20']) ? $_COOKIE['ck_login_theme_20']
				: AppConfig::setting('layout.defaults.theme'));
		//set user language
		$authenticated_user_language = isset($_REQUEST['login_language']) ? $_REQUEST['login_language']
			: (isset ($_COOKIE['ck_login_language_20']) ? $_COOKIE['ck_login_language_20']
				: AppConfig::setting('locale.defaults.language'));

		$_SESSION['authenticated_user_theme'] = $authenticated_user_theme;
		$_SESSION['authenticated_user_language'] = $authenticated_user_language;
    
		$GLOBALS['log']->debug("authenticated_user_theme is $authenticated_user_theme");
		$GLOBALS['log']->debug("authenticated_user_language is $authenticated_user_language");

		// Clear all uploaded import files for this user if it exists

		$tmp_file_name = AppConfig::setting('site.paths.import_dir')."IMPORT_".$GLOBALS['current_user']->id;

		if (file_exists($tmp_file_name)) {
			unlink($tmp_file_name);
		}
		
		return true;
	}

	/**
	 * On every page hit this will be called to ensure a user is authenticated 
	 * @return boolean 
	 */
	function sessionAuthenticate(){
		if (isset ($_SESSION['authenticated_user_id'])) {
			$GLOBALS['log']->debug("We have an authenticated user id: ".$_SESSION["authenticated_user_id"]);
			return $this->postSessionAuthenticate();
		}
		return true;
	}


	/**
	 * Called after a session is authenticated - if this returns false the sessionAuthenticate will return false and destroy the session
	 * and it will load the  current user
	 * @return boolean 
	 */

	function postSessionAuthenticate(){
		if (! $this->userAuthenticate->loadUserOnSession($_SESSION['authenticated_user_id'])) {
			$this->auth_error = "Current user session failed validation";
			$GLOBALS['log']->debug($this->auth_error);
			return false;
		}
		
		$GLOBALS['log']->debug('Current user is: '.$GLOBALS['current_user']->user_name);
		return true;
	}


	/**
	 * Called when a user requests to logout
	 *
	 */
	function logout(){
		session_destroy();
		if($this->no_output)
			return true;
		ob_clean();
		header('Location: index.php?module=Users&action=Login');
		sugar_cleanup(true);
	}


	/**
	 * Encodes a users password. This is a static function and can be called at any time.
	 *
	 * @param STRING $password
	 * @return STRING $encoded_password
	 */
	function encodePassword($password){
		return strtolower(md5($password));
	}
	
	/**
	 * If a user may change there password through the Sugar UI
	 *
	 */
	function canChangePassword(){
		return true;
	}
	/**
	 * If a user may change there user name through the Sugar UI
	 *
	 */
	function canChangeUserName(){
		return true;
	}
	



}
