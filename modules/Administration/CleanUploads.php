<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
action_restricted_for('demo');

$perform = array_get_default($_REQUEST, 'perform', '');
$do_delete = array_get_default($_REQUEST, 'do_delete', '');
$show_files = array_get_default($_REQUEST, 'show_files', '');
$del_notes = array_get_default($_REQUEST, 'del_notes', '');

if(empty($_REQUEST['sugar_body_only'])) {
	echo get_form_header(translate('LBL_CLEAN_UPLOADS'), '', false);

	echo '<div class="form-bottom opaque" style="padding: 5px" id="task-main">';
}

function list_all_files($dirs, $skip_dirs=null) {
	if(! is_array($dirs))
		$dirs = array();
	if(! is_array($skip_dirs))
		$skip_dirs = array();
	$ret = array();
	$scan = $dirs;
	do {
		$nextdir = array_pop($scan);
		if(! is_dir($nextdir))
			continue;
		foreach(scandir($nextdir) as $p) {
			if($p == '.' || $p == '..' || $p == '.DS_Store' || $p == '.svn' || $p == 'index.html')
				continue;
			$fullp = $nextdir . DIRECTORY_SEPARATOR . $p;
			if(is_dir($fullp)) {
				if(in_array($fullp, $skip_dirs))
					continue;
				$scan[] = $fullp;
			} else
				$ret[] = $fullp;
		}
	} while(count($scan));
	rsort($ret);
	return $ret;
}



if($perform) {
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	
	$queries = array(
		"(SELECT id,filename,'notes' modname FROM notes WHERE NOT deleted AND IFNULL(filename,'') != '')",
		"(SELECT id,filename,'document_revisions' modname FROM document_revisions WHERE NOT deleted AND IFNULL(filename,'') != '')",
		"(SELECT id,image_url filename,'products' modname FROM products WHERE NOT deleted AND IFNULL(image_url,'') != '')",
		"(SELECT id,thumbnail_url filename,'products' modname FROM products WHERE NOT deleted AND IFNULL(thumbnail_url,'') != '')",
	);
	
	$query = implode(' UNION ', $queries);
	$result = $db->query($query);
	
	$cache_dir = realpath(AppConfig::files_dir());
	$cache_dir_len = strlen($cache_dir);
	
	$ok_files = array();
	$bad_ids = array();
	while( ($row = $db->fetchByAssoc($result, -1, false)) ) {
		$path = stripslashes($row['filename']);
		if($row['modname'] == 'products') {
			if( ($p = strpos($path, '/upload/')) )
				$path = substr($path, $p+8);
		}
		else if(strpos($path, '/') === false)
			$path = $row['id'].$path;
		if(substr($path, 0, 6) == 'email/')
			$path = "$cache_dir/$path";
		else
			$path = "$cache_dir/upload/$path";
		$path = realpath($path);
		if($path !== false)
			$ok_files[$path] = 1;
		else
			$bad_ids[$row['modname']][] = $row['id'];
	}
	
	$test = array();
	$test[] = AppConfig::setting('company.logo_file');
	$test[] = AppConfig::setting('site.logo_file');
	foreach($test as $t) {
		if($t) {
			$path = realpath($t);
			if($path !== false)
				$ok_files[$path] = 1;
		}
	}
	
	if(isset($bad_ids['notes'])) {
		$del_ids = $bad_ids['notes'];
		echo '<p>' . str_replace('{N}', count($del_ids), translate('LBL_REMOVE_NOTES_N')) . '</p>';
		if($del_notes) {
			$batches = array_chunk($del_ids, 40);
			foreach($batches as $ids) {
				$ids = "'".implode("','", $ids) ."'";
				$query = "UPDATE notes SET deleted=1, date_modified=NOW() WHERE id IN ($ids)";
				$db->query($query);
			}
			echo '<p>' . translate('LBL_REMOVE_DONE') . '</p>';
		}
	}
	
	$clean_dirs = array($cache_dir . DIRECTORY_SEPARATOR . 'email', $cache_dir . DIRECTORY_SEPARATOR . 'upload');
	$all_files = list_all_files($clean_dirs, array(realpath($cache_dir . '/upgrades')));
	
	$del_files = array();
	
	foreach($all_files as $path) {
		if(! isset($ok_files[$path]))
			$del_files[] = $path;
	}
	
	/*foreach($ok_files as $f=>$v) {
		if($show_files)
			echo $f.'<br>';
	}*/

	echo '<p>' . str_replace('{N}', count($del_files), translate('LBL_REMOVE_FILES_N')) . '</p>';

	foreach($del_files as $f) {
		if($show_files)
			echo $f.'<br>';
		if($do_delete)
			@unlink($f);
	}
	
	if($do_delete)
		echo '<p>' . translate('LBL_REMOVE_DONE') . '</p>';
}
else {
?>
<div id="task-result-div"></div>

<div id="task-form-div">
	<form id="task-form" action="?" method="POST" autocomplete="off" onsubmit="run_task(); return false;">
	<input type="hidden" name="module" value="Administration">
	<input type="hidden" name="action" value="CleanUploads">
	<input type="hidden" name="perform" value="1">
	<p class="dataLabel"><?php echo translate('LBL_CLEAN_UPLOADS_TITLE'); ?></p>
	<p class="dataLabel"><label><input type="checkbox" class="checkbox" name="show_files" value="1"> <?php echo translate('LBL_PRINT_FILES'); ?></label></p>
	<p class="dataLabel"><label><input type="checkbox" class="checkbox" name="do_delete" value="1"> <?php echo translate('LBL_REMOVE_FILES'); ?></label></p>
	<p class="dataLabel"><label><input type="checkbox" class="checkbox" name="del_notes" value="1"> <?php echo translate('LBL_REMOVE_NOTES'); ?></label></p>
	<p class="dataLabel"><button type="submit" class="input-button input-outer"><div class="input-icon icon-accept left"></div><span class="input-label"><?php echo translate('LBL_RUN_REPAIR'); ?></span></button>
		&nbsp;<button type="button" class="input-button input-outer" onclick="SUGAR.util.loadUrl('index.php?module=Administration&action=Maintain');"><div class="input-icon icon-return left"></div><span class="input-label"><?php echo translate('LBL_RETURN'); ?></span></button>
	</p>
	</form>
</div>

<div id="task-loading-div" style="display: none"><?php echo get_image($image_path . 'sqsWait.gif', 'border="0" alt=""');  ?></div>

<script type="text/javascript">
	function run_task() {
		var form = $('task-form');
		form.appendChild(createElement2('input', {type: 'hidden', name: 'sugar_body_only', value: 1}));
		toggle_form(true);
		sendAndRetrieve(form, 'task-result-div', undefined, toggle_form);
	}
	function toggle_form(hide) {
		toggleDisplay('task-form-div', ! hide);
		toggleDisplay('task-loading-div', !! hide);
		if(! hide) $('task-result-div').appendChild(createElement2('hr'));
	}
</script>
<?php
}

if(empty($_REQUEST['sugar_body_only']))
	echo '</div>';

?>
