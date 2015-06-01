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

require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');

class JotPadDashlet extends Dashlet {
    var $savedText; // users's saved text
	var $origText; // text without formatting
    var $height = '150'; // height of the pad
    
    var $dashletIcon = 'Notes';

    /**
     * Constructor 
     * 
     * @global string current language
     * @param guid $id id for the current dashlet (assigned from Home module)
     * @param array $def options saved for this dashlet
     */
    function JotPadDashlet($id, $def=null) {
        parent::__construct($id, $def); // call parent constructor
         
        $this->loadLanguage('JotPadDashlet'); // load the language strings here

        if(!empty($def['savedText']))  // load default text is none is defined
			$this->origText = $def['savedText'];
        else
        	$this->origText = $this->getDefaultText();
		$this->savedText = nl2br(htmlspecialchars($this->origText));
            
        if(!empty($def['height'])) // set a default height if none is set
            $this->height = $def['height'];

        $this->isConfigurable = true; // dashlet is configurable
        $this->hasScript = true;  // dashlet has javascript attached to it
                
        // if no custom title, use default
        if(empty($def['title'])) $this->title = $this->dashletStrings['LBL_TITLE'];
        else $this->title = $def['title'];    
    }
    
    function getDefaultText() {
		$ret = translate('LBL_NOTEPAD_TEXT', 'UserPreferences');
		if($ret == 'LBL_NOTEPAD_TEXT') $ret = '';
		return $ret;
    }

    /**
     * Displays the dashlet
     * 
     * @return string html to display dashlet
     */
    function display() {
        $ss = new Sugar_Smarty();
        $ss->assign('savedText', $this->savedText);
        $ss->assign('saving', $this->dashletStrings['LBL_SAVING']);
        $ss->assign('saved', $this->dashletStrings['LBL_SAVED']);
        $ss->assign('id', $this->id);
        $ss->assign('height', $this->height);
          
        $str = $ss->fetch('modules/Home/Dashlets/JotPadDashlet/JotPadDashlet.tpl');     
        return parent::display($this->dashletStrings['LBL_DBLCLICK_HELP']) . $str; // return parent::display for title and such
    }
    
    /**
     * Displays the javascript for the dashlet
     * 
     * @return string javascript to use with this dashlet
     */
    function displayScript() {
        $ss = new Sugar_Smarty();
        $ss->assign('saving', $this->dashletStrings['LBL_SAVING']);
        $ss->assign('saved', $this->dashletStrings['LBL_SAVED']);
        $ss->assign('id', $this->id);
        
        $str = $ss->fetch('modules/Home/Dashlets/JotPadDashlet/JotPadDashletScript.tpl');     
        return $str; // return parent::display for title and such
    }
        
    /**
     * Displays the configuration form for the dashlet
     * 
     * @return string html to display form
     */
    function displayOptions() {
        global $app_strings;
        
        $ss = new Sugar_Smarty();
        $ss->assign('titleLbl', $this->dashletStrings['LBL_CONFIGURE_TITLE']);
        $ss->assign('heightLbl', $this->dashletStrings['LBL_CONFIGURE_HEIGHT']);
        $ss->assign('saveLbl', $app_strings['LBL_SAVE_BUTTON_LABEL']);
        $ss->assign('resetLbl', $app_strings['LBL_RESET_BUTTON_LABEL']);
        $ss->assign('cancelLbl', $app_strings['LBL_CANCEL_BUTTON_LABEL']);
        $ss->assign('title', $this->title);
        $ss->assign('height', $this->height);
        $ss->assign('id', $this->id);

        return parent::displayOptions() . $ss->fetch('modules/Home/Dashlets/JotPadDashlet/JotPadDashletOptions.tpl');
    }  

    /**
     * called to filter out $_REQUEST object when the user submits the configure dropdown
     * 
     * @param array $req $_REQUEST
     * @return array filtered options to save
     */  
    function saveOptions($req) {
        global $timedate, $current_user, $theme;
        $options = array();
        $options['title'] = $_REQUEST['title'];
        if(is_numeric($_REQUEST['height'])) {
            if($_REQUEST['height'] > 0 && $_REQUEST['height'] <= 300) $options['height'] = $_REQUEST['height'];
            elseif($_REQUEST['height'] > 300) $options['height'] = '300';
            else $options['height'] = '100';            
        }
        
//        $options['savedText'] = br2nl($this->savedText);
		$options['savedText'] = $this->origText;
         
        return $options;
    }

    /**
     * Used to save text on textarea blur. Accessed via Home/CallMethodDashlet.php
     * This is an example of how to to call a custom method via ajax
     */    
    function saveText() {
        $json = getJSONobj();
        if(isset($_REQUEST['savedText'])) {
			$optionsArray = $this->loadOptions();
//            _pp($_REQUEST['savedText']);
			$json = getJSONObj();
			$optionsArray['savedText']=$json->decode(from_html($_REQUEST['savedText']));
			$optionsArray['savedText'] = $optionsArray['savedText'];
			$this->storeOptions($optionsArray);

        }
        else {
            $optionsArray['savedText'] = '';
        }
        if(empty($optionsArray['savedText']))
        	$optionsArray['savedText'] = $this->getDefaultText();
        echo 'result = ' . $json->encode(array('id' => $_REQUEST['id'], 
			'savedText' => nl2br(htmlspecialchars($optionsArray['savedText']))));
    }
}

?>
