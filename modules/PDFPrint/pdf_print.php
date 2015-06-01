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
require_once 'include/pdf/PDFManager.php';



require_once("include/dompdf/dompdf_config.inc.php");

/**
 * For testing use:
	echo $result; exit;	
 */
$er = error_reporting(0); //to avoid strict errors being caused most likely by dompdf 
ini_set('display_errors', 0);

// ob_start()'ed in index.php
$result = ob_get_contents();
ob_end_clean();
$result = explode("<!-- crmprint -->", $result);
$result = $result[0];


//clean out slashes
$result = stripslashes($result);


function cmp_longest($a, $b) {
	$i = strlen($a); $j = strlen($b);
	if($i == $j) {
		$i = $b;
		$j = $a;
	}
	return $i < $j ? 1 : ($j > $i ? -1 : 0);
}

function currency_replace_map() {
	require_once('modules/Currencies/Currency.php');
	$cur = new Currency();
	$lst = $cur->get_full_list();
	$repl = array();
	if($lst) {
		foreach($lst as $cur)
			$repl[$cur->symbol] = $cur->iso4217.' ';
	}
	$repl[$cur->getDefaultCurrencySymbol()] = $cur->getDefaultISO4217();
	uksort($repl, 'cmp_longest');
	return array('from' => array_keys($repl), 'to' => array_values($repl));
}
	

global $currentModule;
global $action;

if($action == "DetailView" || $action == "EditView") {

	global $app_strings;
	global $app_list_strings;
	global $current_language;
	
	$pattern = "/";
	$pattern .= "(<[\/]?span[\s]*sugar=(.*?)>)"; //clean out slot tags: <span sugar='slot1'> </span sugar='slot'> or span sugar='sugar...
	$pattern .= "|(<[\/]?img(.*?)[\/]?>)"; //clean out img tags 
	$pattern .= "|(<tr((.*\\n*)?)<td((.*\\n*)?)<table((.*\\n*)?)<tr((.*\\n*)?)<td(.*?)<a(.*?)>(".$app_strings['LNK_VIEW_CHANGE_LOG']."|".$app_strings['LNK_HELP']."|".$app_strings['LNK_PDF'].")<\/a>((.*\\n*)?)<\/td>((.*\\n*)?)<\/tr>((.*\\n*)?)<\/table>((.*\\n*)?)<\/td>((.*\\n*)?)<\/tr>)"; //remove View Change Log nested table
	$pattern .= "|(<a(.*?)>(".$app_strings['LNK_VIEW_CHANGE_LOG']."|".$app_strings['LNK_HELP']."|".$app_strings['LNK_PDF'].")<\/a>)";  //remove View Change Log, PDF and Help links...
	$pattern .= "|(<script[\s\S]*?<\/script>)";  //remove all script
	$pattern .= "|(&nbsp;)"; //remove all &nbsp;
	$pattern .= "|(<[\/]?form(.*?)[\/]?>)"; //there are cases where a form may overlap a table or td tag incorrectly. take care of this by removing the form tags
	$pattern .= "|(<tr(.*?)!(td)>\s*<\/tr>)"; //handle rows with no cells (e.g. <tr></tr>)
	$pattern .= "|(<[\/]?img(.*?)[\/]?>)"; 
	$pattern .= "/i";
	$result = preg_replace($pattern, "", $result);
	

	
	//clean out h1-h6 tags...dompdf doesn't like it
	$pattern = "/<h[1-6]>(.*?)<\/h[1-6]>/i";
	$result = preg_replace($pattern, "<strong>$1</strong>", $result);
	
	//make all links just text...some links have tags incorrectly left behind so allow for that too
	$pattern = "/<(a|TAG(.*?))(.*?)>((.|\\n)*?)<\/(a|TAG(.*?))>/i";
	$result = preg_replace($pattern, '$4', $result);
	
	// handle checkboxes
	$pattern = "/<input\s*type=('|\")checkbox(.*?)checked(.*?)[\/]?>/i";
	$result = preg_replace($pattern, ' [X]', $result);
	$pattern = "/<input\s*type=('|\")checkbox(.*?)>/i";
	$result = preg_replace($pattern, ' [ ]', $result);
	
	//take care of misuse of colspan...wrong number of cols to span
	if($currentModule == "HR" || $currentModule == "Project" || $currentModule == "Reports") {
		//colspan="5"
		$pattern = "/colspan=\"5\"/i";
		$result = preg_replace($pattern, "", $result);
	}

	//replace euro symbol
	//$result = preg_replace( "/\p{Sk}/m", "", $result );
	//$result = htmlspecialchars($result);
	$replace = currency_replace_map();
	$result = str_replace($replace['from'], $replace['to'], $result);


/**
	//add company:
	$header_text = "";
	global $locale;
	require_once 'modules/CompanyAddress/CompanyAddress.php';

	$settings = CompanyAddress::getAddressArray(null, false);
	$settings['account_name'] = preg_replace('/\r\n|\n/', ' ', $settings['name']);
	$tpl = $locale->getLocaleAddressTemplate('pdf', $settings['address_format']);
	$addr = $locale->getLocaleFormattedAddress($settings, '', 'pdf', $tpl);
	$header_text = preg_replace('/\r\n|\n/', ' - ', $addr);
	$phones = array();
	if (!empty($settings['phone'])) {
		$phones[] = $app_strings['LBL_PDF_PHONE'] . ': ' . $settings['phone'];
	}
	if (!empty($settings['fax'])) {
		$phones[] = $app_strings['LBL_PDF_FAX'] . ': ' . $settings['fax'];
	}
	if (!empty($phones)) {
		$header_text .= "\n" . implode(" - ", $phones);
	}
	
	if(isset($header_text)) {
		$result = "<p><table><tr><td>".$header_text."</td></tr></table></p>".$result;
	}
*/

	$result = preg_replace('/<select.*?select>/is', '', $result);
	//wrap with margins
	$result = "<div style=\"margin: 3em 2em 1em 2em;\">".$result."</div>";
	//echo $result; exit;

	$dompdf = new DOMPDF();			
	$dompdf->load_html($result);
	//$dompdf->load_html_file("pdf_test.html"); //for testing load an html file and remove html until you find the offending code
	$dompdf->set_paper("letter", "portrait");

	$dompdf->render();
	//$dompdf->stream("dompdf_out.pdf");
	
	$pdf_str = $dompdf->output();
} else {

	global $app_strings;
	global $app_list_strings;
	global $current_language;

/**
Notes from dompdf forum:

The problem is that the table is actually split in two and laid out again on the second page. 
If you want to ensure that column widths are constant you will have to assign widths to table cells. 
If you have a table header (i.e. a <thead> element), you should just be able to assign widths to 
the cells in the header, since the header will be repeated across all pages. 
If you don't have a table header, you will have to assign widths to table cells on each page 
(kind of a pain, I know, sorry). 
*/

	//super replace
	
	//<td width='100%'><IMG height='1' width='1' src='include/images/blank.gif' alt=''></td>
	$pattern = array();
	$pattern[] = "(<td width='100%'><IMG(.*?)><\/td>)"; //remove title blank img and cell 
	$pattern[] = "(<a(.*?)>(.*?)(".$app_strings['LBL_CLEARALL'].")(.*?)<\/a>)"; //clean out Clear All link
	$pattern[] = "(<h(.*?)>(".$app_strings['LBL_MASS_UPDATE'].")<\\/h(.*?)>)"; //remove Mass Update table
	$pattern[] = "(<[\/]?span[\s]*sugar=(.*?)>)"; //clean out span slot tags: <span sugar='slot1'> </span sugar='slot'> or span sugar='sugar...
	$pattern[] = "(<[\/]?slot(.*?)>)"; //clean out slot tags
	$pattern[] = "(<[\/]?img(.*?)[\/]?>)"; //clean out img tags 
	$pattern[] = "(<script[\s\S]*?<\/script>)"; //remove all script
	$pattern[] = "(<a(.*?)>(.*?)(".$app_strings['LBL_EXPORT'].")(.*?)<input(.*?)\/>)"; //remove export/merge links
	$pattern[] = "(<input((.*\\n*)?)checkbox((.*\\n*)?)mass((.*\\n*)?)[\/]?>)"; //handle checkboxes...translate checked to yes
	$pattern[] = "(&nbsp;)"; //remove all &nbsp;
	$pattern[] = "(<[\/]?form(.*?)[\/]?>)"; //there are cases where a form may overlap a table or td tag incorrectly. take care of this by removing the form tags
	$pattern[] = "(<tr(.*?)!(td)>\s*<\/tr>)"; //handle rows with no cells (e.g. <tr></tr>)
	$pattern[] = "(".$app_strings['LNK_HELP']."|".$app_strings['LNK_PDF'].")"; //remove View Change Log, PDF and Help links...
	$pattern[] = "((onmouseover=\"(.*)?\")|(onmouseout=\"(.*)?\")|(onmousedown=\"(.*)?\"))"; //remove javascript in tr tags
	$pattern[] = "(<tr>[^<]+[.]*<td[^>]+[.]*colspan=\"20\"[.]*[^<]+<\/tr>)"; //remove empty rows

	foreach ($pattern as $i=> $pat) {
		$result = preg_replace('/' . $pat . '/i', "", $result);
	}

	if (
		$currentModule == 'Calls' || $currentModule == 'Meetings'
		|| $currentModule == 'Tasks' || $currentModule == 'Activities'
	) {
		$lang = return_module_language($current_language, $currentModule);
		$result = preg_replace('/<div[^>]+nowrap[^>]+>\s*' . preg_quote($lang['LBL_LIST_CLOSE']) .  '\s*<\/div>/', '   ', $result);
	}

	$pos = strpos($result, 'send_mass_update');
	if ($pos !== false) {
		while ($result[$pos] != "\r" && $result[$pos] != "\n") {
			$pos--;
		}
		$result = substr($result, 0, $pos);
	}

	$pattern = '/<table[^\r\n]*name=\'Delete\'[^\r\n]*<\/table>/Uis';
	$result = preg_replace($pattern, "", $result);

	//clean out h1-h6 tags...dompdf doesn't like it
	//replace with <strong>???\
	$pattern = "/<h[1-6]>(.*?)<\/h[1-6]>/i";
	$result = preg_replace($pattern, '<strong>$1</strong>', $result);

	//make column headers bold
	$pattern = "/<a(.*?)listViewThLinkS1(.*?)>((.|\\n)*?)<\/a>/i";
	$result = preg_replace($pattern, '<strong>$3</strong>', $result);

	//make non-link column headers bold (e.g. sortable = false in listviewdefs.php)
	//$pattern = "/<td(.*?)listViewThS1(.*?)>[.]*[^\s+<\/td>]+<\/td>/i";
	$pattern = "/<td(.*?)listViewThS1(.*?)>/i";
	$result = preg_replace($pattern, '<td$1listViewThS1$2 style="font-weight: bold;">', $result);
	//<td$1listViewThLinkS1$2><strong>$3$4</strong></td>
	
	//make all links just text...some links have tags incorrectly left behind so allow for that too
	$pattern = "/<(a|TAG(.*?))(.*?)>((.|\\n)*?)<\/(a|TAG(.*?))>/i";
	$result = preg_replace($pattern, '$4', $result);

/**
	<td class='listViewPaginationTdS1' align='right' nowrap='nowrap' id='listViewPaginationButtons'>						
									Start
															Previous
									<span class='pageNumbers'>(1 - 40 of 121)</span>

									Next
															End</td>
							</td>
*/
	//reformat paging
	$pattern = "/[^>]+[.]*<span class=('|\")pageNumbers('|\")>(.*?)<\/span>[.]*[^<]+/i";
	$result = preg_replace($pattern, '$3', $result);

	//handle checkboxes...translate checked to yes
	//<input type="checkbox" class="checkbox" disabled="disabled" checked />
	//<input type="checkbox" class="checkbox" disabled checked="checked">
	$pattern = "/<input\s*type=('|\")checkbox(.*?)checked(.*?)[\/]?>/i";
	$result = preg_replace($pattern, ' [X]', $result);
	
	$pattern = "/<option(.*?)selected(.*?)>([^<]*)<\/option>/i";
	$result = preg_replace($pattern, ' [ $3 ]', $result);
	$pattern = "/<option(.*?)<\/option>/i";
	$result = preg_replace($pattern, '', $result);
	
	//$pattern = "/<input(.*?)type=['\"]?text['\"\s](.*?)value=['\"](.*?)['\"](.*?)>/i";
	//$result = preg_replace($pattern, ' [ $3 ]', $result);
	//$pattern = "/<input([^>]*?)value=['\"](.*?)['\"](.*?)type=['\"]?text['\"\s](.*?)>/i";
	//preg_match($pattern, $result, $m);
	//$result = preg_replace($pattern, ' [ $2 ]', $result);
	$pattern = '/<ul class="tablist">.*<\/ul>/iUs';
	$result = preg_replace($pattern, '', $result);
	$pattern = '/^.*searchButtons\' class=\'searchButtons\'.*<\/table>/iUs';
	$result = preg_replace($pattern, '', $result);

	$pattern = '/<table.*' . preg_quote(translate('LBL_PC_SEARCH_FORM_TITLE', 'ProductCatalog')) . '.*<\/table>/Uis';
	$result = preg_replace($pattern, '', $result);
	$pattern = '/^.*<input type="hidden" name="mu".*\/>/Uis';
	$result = preg_replace($pattern, '', $result);
	
	$result = str_replace('&mdash;', '-', $result);
	
	$result = preg_replace('/<select.*?select>/is', '', $result);

	//replace euro symbol
	//$result = preg_replace( "/\p{Sk}/m", "", $result );
	//$result = htmlspecialchars($result);

	$pattern = '/<td[^><]+listViewPaginationTdS1.*<\\/td>/Uis';
	$result = preg_replace($pattern, '', $result);

	$replace = currency_replace_map();
	$result = str_replace($replace['from'], $replace['to'], $result);

	$result = "<div style=\"margin: 2em 3em 3em 2em;\">".$result."</div>";

//echo $result; exit;

/**
If you want to Add Header and Footer on every Page, than you have to follow the following workarounds: 
 
1. Add your Script Tags at THE BEGINNING of your html Code 
2. you have to output something BEFORE your header-footer Script 
 
$headerfooter = ' {Header Footer scripts explained somewhere else} '; 
$html = '<div style="border:1px solid #000;padding:5px;font-size:44px;">THIS is some TEST</div>'; 
$insert = "<span></span>"; 
 
$this->dp->load_html($insert . $headerfooter . $html); 
*/
	

	$dompdf = new DOMPDF();			
    //die($result);
	$dompdf->load_html($result);
	//$dompdf->load_html_file("pdf_test.html"); //for testing load an html file and remove html until you find the offending code
	$dompdf->set_paper("letter", "landscape");

	$dompdf->render();

	//$dompdf->stream("dompdf_out.pdf");
	$pdf_str = $dompdf->output();
} // end else if not detail/edit view

$name = $currentModule . '.pdf';
if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE')) {
	header('Content-Type: application/force-download');
} else {
	header('Content-Type: application/octet-stream');
}
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Expires: 0");
header('Content-Length: '.strlen($pdf_str));
header('Content-disposition: attachment; filename="'.$name.'"');
echo $pdf_str;

$er = error_reporting(0); //to avoid strict errors being caused most likely by dompdf 
exit;

?>
