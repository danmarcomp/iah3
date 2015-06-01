<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

action_restricted_for('demo');

global $current_user;
if(! AppConfig::is_admin())
	sugar_die("You are not an administrator.");

if(empty($_POST['confirm'])) {
	echo "<p>This procedure will erase each user's tab preferences, setting them back to the default values and displaying all tabs to which the user has access.</p>";
	echo '<form action="index.php" method="POST" autocomplete="off">';
	echo '<input type="hidden" name="module" value="Administration">';
	echo '<input type="hidden" name="action" value="ClearUserTabPrefs">';
	echo '<input type="hidden" name="confirm" value="1">';
	echo '<button type="submit" class="input-button input-outer"><div class="input-icon icon-accept left"></div><span class="input-label">Confirm</span></button>';
	echo ' <button type="button" class="input-button input-outer" onclick="this.form.action.value=\'Maintain\'; this.form.submit();"><div class="input-icon icon-cancel left"></div><span class="input-label">Cancel</span></button>';
	echo '</form>';
	return;
}

echo "<p>Clearing all user tab preferences..</p>";

$query = "SELECT id, user_name FROM users WHERE NOT deleted";
$seed = new User();
$result = $seed->db->query($query, true, "Error retrieving user list");
$users = array();
while($row = $seed->db->fetchByAssoc($result)) {
	$users[$row['id']] = $row;
}

foreach($users as $row) {
	$lq = new ListQuery('user_preferences');
	$lq->addSimpleFilter('category', 'global');
	$lq->addSimpleFilter('assigned_user_id', $row['id']);
	$rows = $lq->fetchAll();
	if($rows && $rows->getResultCount()) {
		$result = $rows->getRowResult(0);
		$prefs = unserialize(base64_decode($result->getField('contents')));
		unset($prefs['display_tabs']);
		unset($prefs['hide_tabs']);
		unset($prefs['remove_tabs']);
		$upd = RowUpdate::for_result($result);
		$upd->set('contents', base64_encode(serialize($prefs)));
		$upd->save();

		$sess_prefs = $row['user_name'].'_PREFERENCES';
		if(isset($_SESSION[$sess_prefs])) unset($_SESSION[$sess_prefs]);
	}
}

AppConfig::invalidate_cache('userpref');

echo "<p>Done.</p>";

echo '<button type="submit" class="input-button input-outer" onclick="SUGAR.util.loadUrl(\'index.php?module=Administration&action=index\');"><div class="input-icon icon-return"></div><span class="input-label">Return to Administration</span></button>';

