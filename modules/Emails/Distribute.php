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
if(!empty($_SESSION['distribute_where']) && !empty($_REQUEST['distribute_method']) && !empty($_REQUEST['users']) && !empty($_REQUEST['use'])) {
	require_once('modules/Emails/Email.php');
	$focus = new Email();
		
	$emailIds = array();
	// CHECKED ONLY:
	if($_REQUEST['use'] == 'checked') {
		// clean up passed array
		$grabEx = explode('::',$_REQUEST['grabbed']);
		foreach($grabEx as $k => $emailId) {
			if($emailId != "undefined") {
				$emailIds[] = $emailId;
			}
		}
		
		// we have users and the items to distribute	
		if($_REQUEST['distribute_method'] == 'roundRobin') {
			if($focus->distRoundRobin($_REQUEST['users'], $emailIds)) {
				header('Location: index.php?module=Emails&action=index');
			}	
		} elseif($_REQUEST['distribute_method'] == 'leastBusy') {
			if($focus->distLeastBusy($_REQUEST['users'], $emailIds)) {
				header('Location: index.php?module=Emails&action=index');
			}
		} elseif($_REQUEST['distribute_method'] == 'direct') {
			// direct assignment
//			_ppd('count:'.count($_REQUEST['users']));
			if(count($_REQUEST['users']) > 1) {
				// only 1 user allowed in direct assignment
				$error = 1;
			} else {
				$user = $_REQUEST['users'][0];
				if($focus->distDirect($user, $emailIds)) {
					header('Location: index.php?module=Emails&action=index');
				}
			}
			
			header('Location: index.php?module=Emails&action=index&error='.$error);
		}
	} elseif($_REQUEST['use'] == 'all') {
		if($_REQUEST['distribute_method'] == 'direct') {
			// no ALL assignments to 1 user
			header('Location: index.php?module=Emails&action=index&error=2');
		}
		
		// we have the where clause that generated the view above, so use it
		$q = 'SELECT emails.id FROM emails WHERE '.$_SESSION['distribute_where'];
		$q = str_replace('&#039;', '"', $q);
		$r = $focus->db->query($q);
		$count = 0;
		while($a = $focus->db->fetchByAssoc($r)) {
			$emailIds[] = $a['id'];
			$count++;
		}
		// we have users and the items to distribute	
		if($_REQUEST['distribute_method'] == 'roundRobin') {
			if($focus->distRoundRobin($_REQUEST['users'], $emailIds)) {
				header('Location: index.php?module=Emails&action=index');
			}
		} elseif($_REQUEST['distribute_method'] == 'leastBusy') {
			if($focus->distLeastBusy($_REQUEST['users'], $emailIds)) {
				header('Location: index.php?module=Emails&action=index');
			}
		}
		
		if($count < 1) {
			$GLOBALS['log']->info('Emails distribute failed: query returned no results ('.$q.')');
			header('Location: index.php?module=Emails&action=index&error='.$error);
		}
	}

} else {
	// error
	header('Location: index.php?module=Emails&action=index');
}

?>
