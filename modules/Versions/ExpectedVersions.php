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



$expect_versions = array();

$expect_versions['Custom Labels'] = array('name'=>'Custom Labels', 'db_version' =>'3.0', 'file_version'=>'3.0');
$expect_versions['Chart Data Cache'] = array('name'=>'Chart Data Cache', 'db_version' =>'3.5.1', 'file_version'=>'3.5.1');
$expect_versions['htaccess'] = array('name'=>'htaccess', 'db_version' =>'3.5.1', 'file_version'=>'3.5.1');
$expect_versions['DST Fix'] = array('name'=>'DST Fix', 'db_version' =>'3.5.1b', 'file_version'=>'3.5.1b');
$expect_versions['Rebuild Relationships'] = array('name'=>'Rebuild Relationships', 'db_version' =>'5.3.1', 'file_version'=>'5.3.1');
$expect_versions['Rebuild Extensions'] = array('name'=>'Rebuild Extensions', 'db_version' =>'4.0.0', 'file_version'=>'4.0.0');
$expect_versions['Studio Files'] = array('name'=>'Studio Files', 'db_version' =>'4.5.0', 'file_version'=>'4.5.0');


// longreach - added
$expect_versions['Upgrade Quotes/Invoices'] = array('name'=>'Upgrade Quotes/Invoices', 'db_version' =>'4.2.2', 'file_version'=>'4.2.2');
$expect_versions['Email Bodies for Fulltext Search'] = array('name'=>'Email Bodies for Fulltext Search', 'db_version' =>'5.0.0', 'file_version'=>'5.0.0');
$expect_versions['Products stock'] = array('name'=>'Products stock', 'db_version' =>'5.3.0', 'file_version'=>'5.3.0');
$expect_versions['Bills Associations'] = array('name'=>'Bills Associations', 'db_version' =>'5.3.1', 'file_version'=>'5.3.1');
$expect_versions['Default Software Product'] = array('name'=>'Default Software Product', 'db_version' =>'6.0.2', 'file_version'=>'6.0.2');
$expect_versions['Credit Notes'] = array('name'=>'Credit Notes', 'label' => 'LBL_UPGRADE_CREDIT_NOTES_NAME', 'db_version' =>'6.6.0', 'file_version'=>'6.6.0');
$expect_versions['Customers/Vendors'] = array('name'=>'Customers/Vendors', 'label' => 'LBL_UPGRADE_CUSTOMERS_VENDORS_NAME', 'db_version' =>'6.5.0', 'file_version'=>'6.5.0');
$expect_versions['Recurring Services'] = array('name'=>'Recurring Services', 'db_version' =>'6.7.0', 'file_version'=>'6.7.0');
$expect_versions['Invoice Gross Profit'] = array('name'=>'Invoice Gross Profit', 'db_version' =>'6.7.0', 'file_version'=>'6.7.0');

?>
