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

action_restricted_for('demo');


global $mod_strings;

$setup_site_log_dir = AppConfig::setting('site.log.dir');
$setup_site_log_file = AppConfig::setting('site.log.file');
$setup_site_url = AppConfig::site_url();
$parsed_url = parse_url($setup_site_url);

$htaccess_file  = ".htaccess";
$site_path      = $parsed_url['path'];
$redirect_str   = "# BEGIN SUGARCRM RESTRICTIONS\n";
$redirect_str   .= "RedirectMatch $site_path/$setup_site_log_dir/$setup_site_log_file.* $setup_site_url/log_file_restricted.html\n";
$redirect_str   .= "RedirectMatch $site_path/$setup_site_log_dir/emailman.log $setup_site_url/log_file_restricted.html\n";
$redirect_str   .= "RedirectMatch $site_path/not_imported_(.*).txt $setup_site_url/log_file_restricted.html\n";
$redirect_str   .= "RedirectMatch $site_path/XTemplate/(.*)/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/data/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/examples/(.*).php $setup_site_url/index.php\n";
$redirect_str	.= "RedirectMatch $site_path/include/([^/\.]+).php $setup_site_url/index.php\n";
$redirect_str	.= "RedirectMatch $site_path/include/([^/\.]+)/([^/\.]+).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/log4php/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/log4php/(.*)/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/metadata/(.*)/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/modules/(.*)/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/soap/(.*).php $setup_site_url/index.php\n";
$redirect_str   .= "RedirectMatch $site_path/emailmandelivery.php $setup_site_url/index.php\n";
$redirect_str   .= "# END SUGARCRM RESTRICTIONS\n";

$redirect_str       = preg_replace( "#/./#", "/", $redirect_str );
$htaccess_failed    = false;
if( file_exists( $htaccess_file ) && (filesize( $htaccess_file ) > 0) ){
    if( is_writable( $htaccess_file ) && ($fh = @ fopen( $htaccess_file, "r+" )) ){
        $props  = fread( $fh, filesize( $htaccess_file ) );

        if( !preg_match("=" . $redirect_str . "=", $props)) {
                $props .= $redirect_str;
        }

        rewind( $fh );
        fwrite( $fh, $props );
        ftruncate( $fh, ftell($fh) );
        fclose( $fh );
    }
    else{
        $htaccess_failed = true;
    }
}
else{
    // create the file
    if( $fh = @ fopen( $htaccess_file, "w") ){
        fputs( $fh, $redirect_str, strlen($redirect_str) );
        fclose( $fh );
    }
    else {
        $htaccess_failed = true;
    }
}
if( $htaccess_failed ){
    echo '<p>' . $mod_strings['LBL_HT_NO_WRITE'] . '<span class=stop>$htaccess_file</span></p>\n';
    echo '<p>' . $mod_strings['LBL_HT_NO_WRITE_2'] . '</p>\n';
    echo "$redirect_str";
}


// cn: bug 9365 - security for filesystem
$uploadDir = getcwd()."/".AppConfig::upload_dir();
$uploadHta = $uploadDir.".htaccess";
$denyAll =<<<eoq
<Directory>
	Order Deny,Allow
	Deny from all
</Directory>
eoq;

if(file_exists($uploadHta) && filesize($uploadHta)) {
	// file exists, parse to make sure it is current
	if(is_writable($uploadHta) && ($fpUploadHta = @fopen($uploadHta, "r+"))) {
		$oldHtaccess = fread($fpUploadHta, filesize($uploadHta));
		// use a different regex boundary b/c .htaccess uses the typicals
		if(!preg_match("=".$denyAll."=", $oldHtaccess)) {
			$oldHtaccess .= $denyAll;
		}
		
		rewind($fpUploadHta);
		fwrite($fpUploadHta, $oldHtaccess);
		ftruncate($fpUploadHta, ftell($fpUploadHta));
		fclose($fpUploadHta);		
	} else {
		$htaccess_failed = true;
	}
} else {
	// no .htaccess yet, create a fill
	if($fpUploadHta = @fopen($uploadHta, "w")) {
		fputs($fpUploadHta, $denyAll, strlen($denyAll));
		fclose($fpUploadHta);
	} else {
		$htaccess_failed = true;
	}
}



/*
include('modules/Versions/ExpectedVersions.php');
require_once('modules/Versions/Version.php');

global $expect_versions;

if (isset($expect_versions['htaccess'])) {
        $version = new Version();
        $version->retrieve_by_string_fields(array('name'=>'htaccess'));

        $version->name = $expect_versions['htaccess']['name'];
        $version->file_version = $expect_versions['htaccess']['file_version'];
        $version->db_version = $expect_versions['htaccess']['db_version'];
        $version->save();
}*/

echo "\n<p>" . $mod_strings['LBL_HT_DONE']. "</p><br />\n";

echo '<button type="button" class="input-button input-outer" onclick="SUGAR.util.loadUrl(\'index.php?module=Administration&action=Maintain\');"><div class="input-icon icon-return left"></div><span class="input-label">'. translate('LBL_RETURN') . '</span></button>';

?>
