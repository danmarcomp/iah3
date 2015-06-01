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


class AuthenticationController {
	var $loggedIn = false; //if a user has attempted to login
	var $authenticated = false;
	var $loginSuccess = false;// if a user has successfully logged in

	var $invalid_credentials;	
	var $auth_error;

	/**
	 * Creates an instance of the authentication controller and loads it
	 *
	 * @param STRING $type - the authentication Controller - default to SugarAuthenticate
	 * @return AuthenticationController - 
	 */
	function AuthenticationController($type=null) {
		if(! $type || ! file_exists('modules/Users/authentication/'.$type.'/' . $type . '.php'))
			$type = AppConfig::setting('site.login.authentication_class', 'SugarAuthenticate');
		
		if($type == 'SugarAuthenticate' && AppConfig::setting('ldap.enabled') && empty($_SESSION['sugar_user'])) {
			$type = 'LDAPAuthenticate';
		}
		
		require_once ('modules/Users/authentication/'.$type.'/' . $type . '.php');
		$this->authController = new $type();
	}


	/**
	 * Returns an instance of the authentication controller
	 *
	 * @param STRING $type this is the type of authetnication you want to use default is SugarAuthenticate
	 * @return an instance of the authetnciation controller
	 */
	function &getInstance($type=null, $reload=false){
		static $authcontroller;
		if(empty($authcontroller) || $reload){
			$authcontroller = new AuthenticationController($type);
		}
		return $authcontroller;
	}

	/**
	 * This function is called when a user initially tries to login. 
	 * It will return true if the user successfully logs in or false otherwise.
	 *
	 * @param STRING $username
	 * @param STRING $password
	 * @param ARRAY $PARAMS
	 * @return boolean
	 */
	function login($username, $password, $PARAMS = array ()) {
		$_SESSION['loginAttempts'] = array_get_default($_SESSION, 'loginAttempts', 0) + 1;
		unset($GLOBALS['login_error']);
		
		if($this->loggedIn) return $this->loginSuccess;
		
		$this->loginSuccess = $this->authController->loginAuthenticate($username, $password, $PARAMS);
		$this->loggedIn = true;
		$this->auth_error = $this->authController->auth_error;
		$this->invalid_credentials = $this->authController->invalid_credentials;
		
		if($this->loginSuccess){
			//Ensure the user is authorized
			checkAuthUserStatus();
			
			loginLicense();
			if(!empty($GLOBALS['login_error'])){
				if(isset($_SESSION['authenticated_user_id']))
					unset($_SESSION['authenticated_user_id']);
				$GLOBALS['log']->fatal('FAILED LOGIN: potential hack attempt');
				$this->loginSuccess = false;
				return false;
			}			
		}else{
			$GLOBALS['log']->fatal('FAILED LOGIN:attempts[' .$_SESSION['loginAttempts'] .'] - '. $username);
		}
		return $this->loginSuccess;
	}
	
	
	// longreach - added
	function rpcLogin($username, $password, $PARAMS=array()) {
		$PARAMS['rpc'] = true;
		$this->authController->no_output = true;
		return $this->login($username, $password, $PARAMS);
	}
	

	/**
	 * This is called on every page hit. 
	 * It returns true if the current session is authenticated or false otherwise
	 * @return booelan
	 */
	function sessionAuthenticate() {
		if(! $this->authenticated) {
			$this->authenticated = $this->authController->sessionAuthenticate();
			if($this->authenticated) {
				if(!isset($_SESSION['userStats']['pages'])){
					$_SESSION['userStats']['loginTime'] = time();
					$_SESSION['userStats']['pages'] = 0;
				}
				$_SESSION['userStats']['lastTime'] = time();
				$_SESSION['userStats']['pages']++;
				if(array_get_default($_SESSION, 'login_type') == 'portal') {
					$GLOBALS['log']->debug('Use of portal login session in normal application disallowed');
					$this->authenticated = false;
				}
			}
		}
		return $this->authenticated;
	}
	
	
	// longreach - added
	function rpcSessionAuthenticate() {
		if(!$this->authenticated){
			$this->authController->no_output = true;
			$this->authenticated = $this->authController->sessionAuthenticate();
			$this->auth_error = $this->authController->auth_error;
		}
		return $this->authenticated;
	}
	

	/**
	 * Called when a user requests to logout. Should invalidate the session and redirect
	 * to the login page.
	 *
	 */
	function logout(){
		$this->authController->logout();
	}
	
	
	function getAuthType() {
		if($this->authController) return get_class($this->authController);
		return '';
	}

}
?>
