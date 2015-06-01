<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('XTemplate/xtpl.php');
require_once('data/Tracker.php');
require_once('modules/KBArticles/KBArticle.php');

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $db;

if (isset($_POST['save_tag'])) {
	$tag_id = $_POST['save_tag'];
	$tag_name = $_POST['tag'];
	if (!empty($tag_id)) {
		$res = ListQuery::quick_fetch('kb_tags', $tag_id);
		if ($res) {
			$ru = new RowUpdate($res);
			$ru->request = array(
				'tag' => $tag_name,
			);
			$ru->loadRequest();
			$ru->save();
		}
	} else {
		$ru = new RowUpdate('kb_tags');
		$ru->request = array(
			'tag' => $tag_name,
		);
		$ru->loadRequest();
		$ru->insertRow();
	}
	header('Location: index.php?module=KBArticles&action=ManageTags');
	exit;
}

echo "\n<p>\n";
echo get_module_title('KBTags', $mod_strings['LBL_MANAGE_TAGS'], true);
echo "\n</p>\n";

echo "\n<p>\n";
echo $mod_strings['LBL_CLICK_TO_EDIT'];
echo "\n</p>\n";

$min_size = 10;
$max_size = 30;

$size_diff = $max_size - $min_size;

$tags = array();

$query = "SELECT kb_tags.*, COUNT(kb_articles.id) AS narticles FROM kb_tags LEFT JOIN kb_articles_tags ON kb_articles_tags.tag_id = kb_tags.id AND kb_articles_tags.deleted = 0 LEFT JOIN kb_articles ON kb_articles_tags.article_id = kb_articles.id AND kb_articles.deleted = 0 GROUP BY kb_tags.id ORDER BY kb_tags.tag";

$res = $db->query($query, true);

$sum = 0;

while ($row = $db->fetchByAssoc($res)) {
	$sum += $row['narticles'];
	$tags[] = $row;
}

foreach ($tags as $tag) {
	if (!$sum) {
		$size = $min_size;
	} else {
		$size = $min_size + $tag['narticles'] / $sum * $size_diff;
	}
	printf('<a class="tabDetailViewDFLink" style="text-decoration:none" href="#tag_edit_form" onclick="edit_tag(\'%s\', \'%s\')"><span style="font-size:%2.3fpt">%s</span></a> ', $tag['id'],  $tag['tag'], $size, $tag['tag']);
}

?>
<p>&nbsp;</p>

<form id="tag_edit_form" method="post" action="index.php" name="tag_edit_form">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
	<td align="left" style="padding-bottom: 2px;">
		<input 	class="button"
				type="submit"
				name="button"
				onclick="return check_form('tag_edit_form');"
				value="  <?php echo $app_strings['LBL_SAVE_BUTTON_LABEL']?>  " >

		<input 	class="button"
				type="button"
				name="button"
				value="  <?php echo $mod_strings['LBL_NEW_TAG_BUTTON_LABEL']?>  "
				onclick="edit_tag('', '')"
			 >

	</td>
</tr>
</table>


<input id="save_tag" name="save_tag" type="hidden">
<input name="module" type="hidden" value="KBArticles">
<input name="action" type="hidden" value="ManageTags">

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabForm">
<tr>
<td>
<h3 id="form_title"><?php echo $mod_strings['LBL_NEW_TAG']?></h3>
</td>
</tr>
<tr><td>

	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="20%" class="dataLabel"><?php echo $mod_strings['LBL_TAG']?><span class="required"><?php echo $app_strings['LBL_REQUIRED_SYMBOL']?></span></td>
		<td width="80%" class="dataField"><input name='tag' type="text" tabindex='1' size='30' value="" id="tag" > </td>
</tr>
</table>
</td></tr></table>
</form>

<script type="text/javascript">
addToValidate('tag_edit_form', 'tag', 'alpha', true, '<?php echo $mod_strings['LBL_TAG']?>' );
function edit_tag(id, name)
{
	document.getElementById('tag').value = name;
	document.getElementById('save_tag').value = id;
	if (id == '') {
		document.getElementById('form_title').innerHTML = '<?php echo $mod_strings['LBL_NEW_TAG']?>';
	} else {
		document.getElementById('form_title').innerHTML = '<?php echo $mod_strings['LBL_EDIT_TAG']?> : ' + name;
	}
}
</script>
