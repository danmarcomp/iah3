<?php
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

 * Description: The primary Function of this file is to manage all the data
 * used by other files in this nodule. It should extend the SugarBean which impelments
 * all the basic database operations. Any custom behaviors can be implemented here by
 * implemeting functions available in the SugarBean.
 ********************************************************************************/

require_once('data/SugarBean.php'); 

class CampaignTracker extends SugarBean {
	/* Foreach instance of the bean you will need to access the fields in the table.
	 * So define a variable for each one of them, the varaible name should be same as the field name
	 * Use this module's vardef file as a reference to create these variables.
	 */
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_by;
	var $deleted;
	var $tracker_key;
	var $tracker_url;
	var $tracker_name;
	var $campaign_id;
	var $campaign_name;
	var $message_url;
	var $is_optout;
	
	/* End field definitions*/

	/* variable $table_name is used by SugarBean and methods in this file to constructs queries
	 * set this variables value to the table associated with this bean.
	 */
	var $table_name = 'campaign_trkrs';

	/*This  variable overrides the object_name variable in SugarBean, wher it has a value of null.*/
	var $object_name = 'CampaignTracker';

	/**/
	var $module_dir = 'CampaignTrackers';

	/* This is a legacy variable, set its value to true for new modules*/
	var $new_schema = true;

}
?>
