<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/utils.php');
require_once('include/formbase.php');
require_once('modules/Accounts/Account.php');

$accountId = $_REQUEST["request_data"];
$assignedUserId = $_REQUEST["cmbUserList"];
$ids = explode(",",$accountId);
$related = !empty($_REQUEST["chkRelatedData"]) ? $_REQUEST['chkRelatedData'] : array();
$account = new Account;
reassignObjects($account, $assignedUserId, $ids, $related);

echo "<script language=\"javascript1.2\">
window.opener.location.href=window.opener.location.href;
window.close();
</script>";
?>
