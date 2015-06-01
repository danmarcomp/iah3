<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
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
 ********************************************************************************/
/*********************************************************************************

 * Description: Handles the User Preferences and stores them in a seperate table. 
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

// Do not actually declare, use the functions statically
class UserPreference {

	private function __construct() {} // static class

	/**
	 * Get preference by name and category. Lazy loads preferences from the database per category
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param string $name name of the preference to retreive
	 * @param string $category name of the category to retreive, defaults to global scope
	 * @param user $user User object to retrieve, otherwise user current_user
	 * @return mixed the value of the preference (string, array, int etc)
	 */
	static function getPreference($name, $category = 'global', $user = null) {
		global $locale;
		// longreach - start added
		if ($name == 'default_currency_significant_digits') {
			return AppConfig::setting('locale.base_currency.significant_digits');
		}
		// longreach - end added
		
		if(isset($user))
			$user_key = 'by_user.'.$user->id;
		else
			$user_key = 'current_user';

		return AppConfig::setting("userpref.$user_key.$category.$name");
	}

	/**
	 * Set preference by name and category. Saving will be done in utils.php -> sugar_cleanup
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param string $name name of the preference to retreive
	 * @param mixed $value value of the preference to set
	 * @param int @nosession no longer supported
	 * @param string $category name of the category to retreive, defaults to global scope
	 * @param user $user User object to retrieve, otherwise user current_user
	 * 
	 */
	static function setPreference($name, $value, $nosession = 0, $category = 'global', $user = null) {  
		if(isset($user))
			$user_key = 'by_user.'.$user->id;
		else
			$user_key = 'current_user';
		
		AppConfig::set_local("userpref.$user_key.$category.$name", $value);		
	}
	
	/**
	 * Loads preference by category from database. Saving will be done in utils.php -> sugar_cleanup
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param string $category name of the category to retreive, defaults to global scope
	 * @param user $user User object to retrieve, otherwise user current_user
	 * @return bool successful?
	 *  
	 */
	static function loadPreferences($category = 'global', $user = null, $reload=false) {
		if(isset($user)) {
			if($user->object_name != 'User')
				return;
			$user_key = 'by_user.'.$user->id;
		} else
			$user_key = 'current_user';
		
		return AppConfig::setting("userpref.$user_key.$category", false);
	}
	
	/**
	 * Loads users timedate preferences
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param string $category name of the category to retreive, defaults to global scope
	 * @param user $user User object to retrieve, otherwise user current_user
	 * @return array 'date' - date format for user ; 'time' - time format for user  
	 *  
	 */	
	static function getUserDateTimePreferences($user = null) {
		global $timedate;
		
		$timeZone = self::getPreference('timezone', 'global', $user);
		$gmtOffset = $timedate->getTimeZoneOffset($timeZone, true);
		
		$prefDate = array(
			'date' => self::getPreference('default_date_format', 'global', $user),
			'time' => self::getPreference('default_time_format', 'global', $user),
			'userGmt' => "(GMT".($gmtOffset / 60).")",
			'userGmtOffset' => $gmtOffset / 60,
		);
			
		return $prefDate;
	}
	
	/**
	 * Saves all preferences into the database that are in the session. Expensive, this is called by default in 
	 * sugar_cleanup if a setPreference has been called during one round trip.
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param user $user User object to retrieve, otherwise user current_user
	 * @param bool $all save all of the preferences? (Dangerous)
	 * 
	 */
	static function savePreferencesToDB($user = null, $all = false) {
		if($user) $key = "by_user.{$user->id}";
		else if($all) $key = 'all_users';
		else $key = 'current_user';
		AppConfig::save_local("userpref.$key");
	}
	
	/**
	 * Resets preferences for a particular user. If $category is null all user preferences will be reset 
	 * 
	 * @global user will use current_user if no user specificed in $user param
	 * @param string $category category to reset
     * @param user $user User object to retrieve, otherwise user current_user
	 * 
	 */
	static function resetPreferences($category = null, $user = null) {
		/*global $db;
		if(!isset($user)) $user = $GLOBALS['current_user'];
		
		$GLOBALS['log']->debug('Reseting Preferences for user ' . $user->user_name);
		
		$remove_tabs = $this->getPreference('remove_tabs');
		
		$query = "UPDATE user_preferences SET deleted = 1 WHERE assigned_user_id = '" . $user->id . "'";
        if($category) $query .= " AND category = '" . $category . "'"; 
		$db->query($query);
		
		if($user->id == $GLOBALS['current_user']->id) {
			if($category) {
	            unset($_SESSION[$this->user_name."_PREFERENCES"][$category]);
	        }
	        else {
	            unset($_SESSION[$this->user_name."_PREFERENCES"]);
	    		session_destroy();
	            $this->setPreference('remove_tabs', $remove_tabs, 1);
	            header('Location: index.php');
	        }
		}*/
	}
	
	
	// longreach - start added
	static function getDefaultPreferences() {
		return AppConfig::setting('userpref.defaults');
	}
	// longreach - end added

}

?>