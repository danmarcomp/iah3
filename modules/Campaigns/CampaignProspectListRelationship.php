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
 * Portions created by SugarCRM are Copyright (C) 2004-2005 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************
 * $Id: CampaignProspectListRelationship.php,v 1.4 2005/09/21 18:48:07 chris Exp $
 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('data/SugarBean.php');

// Contact is used to store customer information.
class CampaignProspectListRelationship extends SugarBean {
	// Stored fields
	var $id;
	var $prospect_list_id;
	var $campaign_id;
	var $deleted;

	var $table_name = "prospect_list_campaigns";
	var $object_name = "CampaignProspectListRelationship";
	var $column_fields = Array("id"
		,"prospect_list_id"
		,"campaign_id"
		,"deleted"
		);

	var $new_schema = true;

	var $additional_column_fields = Array();

	function CampaignProspectListRelationship() {
		;
		parent::SugarBean();
	}

	function fill_in_additional_detail_fields()
	{

	}
	
	function create_list_query()
	{
		
	}

}



?>
