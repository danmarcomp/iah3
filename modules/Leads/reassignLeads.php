<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/utils.php');
require_once('include/formbase.php');
require_once('modules/Leads/Lead.php');

$lead = new Lead;

global $current_user;

$db = & PearDatabase::getInstance();


$LeadId = $_REQUEST["request_data"];
$assignedUserId = $db->quote($_REQUEST["cmbUserList"]);
$ids = explode(",",$LeadId);
$related = !empty($_REQUEST["chkRelatedData"]) ? $_REQUEST['chkRelatedData'] : array();

reassignObjects($lead, $assignedUserId, $ids, $related);

echo "<script language=\"javascript1.2\">
window.opener.location.href=window.opener.location.href;
window.close();
</script>";
?>
