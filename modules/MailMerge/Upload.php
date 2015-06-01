<?php

$pars = array(
	'merge_file',
	'merge_module',
);

$params = array();

$list_id = $_POST['list_id'];

foreach ($pars as $p) {
	$params[$p] = array_get_default($_POST, $p);
}

$json = getJSONObj();
$params = $json->encode($params);

echo <<<EOF
<script type="text/javascript">
var p = SUGAR.ui.PopupManager.last_opened; p.close(); 
sListView.sendMassUpdate('$list_id', 'RTFMerge', '_iah_mail_merge', $params);
</script>

EOF;

