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

global $current_user;



/*
for each dashlet,
start dlmanager 'session' (given unique id)
tell dashlet to process
dashlet registers downloads with the manager (each file separately)
if session has pending requests, keep it around, don't render dashlet yet
associate session id with dashlet instance

keep running downloads
each request has a callback which probably does something to dashlet instance
once a 'session' has finished, run the callback associated with the session (dashlet->render)
*/

require_once('include/DLManager.php');


if(! empty($_REQUEST['id'])) {
	global $pageInstance;
	$sample = ! empty($_REQUEST['sample']);
	$dynamic = ! empty($_REQUEST['dynamic']);
	$refresh = ! empty($_REQUEST['refresh']);
	$dboard = $pageInstance->get_dashboard();

	if($dboard) {
		$downloading = array();
		if(is_array($_REQUEST['id'])) {
			$print_id_tag = true;
			$widths = array_get_default($_REQUEST, 'width', array());
			foreach($_REQUEST['id'] as $i => $id) {
				$width = array_get_default($widths, $i, '');
				ob_start();
				echo '{#-'.$id.'-#}';
				$data = $dboard->process_dashlet($id, $sample, $dynamic, $refresh, $width);
				if(! empty($data)) {
					if(! empty($data['download'])) {
						ob_end_clean();
						$batch_id = DLManager::end_batch();
						$downloading[$batch_id] = $data;
						continue;
					}
					echo $data['display'];
					echo $pageInstance->format_includes('foot', null, true);
					echo $pageInstance->format_includes('end', null, true);
				}
				ob_end_flush();
			}
		} else {
			$width = array_get_default($_REQUEST, 'width', '');
			$data = $dboard->process_dashlet($_REQUEST['id'], $sample, $dynamic, $refresh, $width);
			if(! empty($data)) {
				if(! empty($data['download'])) {
					$batch_id = DLManager::end_batch();
					$downloading[$batch_id] = $data;
				}
				else {
					echo $data['display'];
					echo $pageInstance->format_includes('foot', null, true);
					echo $pageInstance->format_includes('end', null, true);
				}
			}
		}
		
		if($downloading) {
			ob_flush();
			// close session to prevent lock contention from multiple requests
			session_write_close();
		
			while($downloading && $info = DLManager::download_batch()) {
				foreach($info['doneBatches'] as $bid) {
					if(isset($downloading[$bid])) {
						if(! empty($print_id_tag)) {
							echo '{#-'.$downloading[$bid]['id'].'-#}';
						}
						$data = $dboard->finish_display($downloading[$bid]);
						echo $data['display'];
						echo $pageInstance->format_includes('foot', null, true);
						echo $pageInstance->format_includes('end', null, true);
						ob_flush();
					}
				}
			}
		}
		
		sugar_cleanup(true);
	}
}
else {
    header("Location: index.php?action=index&module=Home");
}

?>
