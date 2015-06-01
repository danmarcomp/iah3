<?php
require_once('include/layout/forms/EditableForm.php');

class DiagnosticViewer {

    /**
     * @var EditableForm
     */
    private $form;

    /**
     * @var DBManager
     */
    private $db;

    /**
     * @var array
     */
    private $mod_strings;

    /**
     * @var array
     */
    private $app_strings;

    public function __construct() {
        global $mod_strings, $app_strings;
        $this->mod_strings = $mod_strings;
        $this->app_strings = $app_strings;

        $this->form = new EditableForm('editview', 'Diagnostic', 'Diagnostic');
        $this->initDb();
    }

    public function render() {
        $result = $this->renderOptionsPage();
        $this->form->exportIncludes();
        echo $this->getTitle() . $result;
    }

    /**
     * @param string $file
     */
    public function downloadResult($file) {
        $path = getcwd()."/cache/diagnostic/".$file.".zip";

        if (! empty($path) && file_exists($path)) {
            $filesize = filesize($path);

            header('Content-type: application/zip');
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: private", false);
            header("Content-Disposition: attachment; filename=" .$file. ".zip");
            header("Content-Transfer-Encoding: binary");

            if(! AppConfig::get_server_compression())
                header("Content-Length: $filesize");

            readfile($path);
        } else {
            sugar_die($this->mod_strings['LBL_NO_FILENAME']);
        }
    }

    /**
     * @param string $file
     * @param string $guid
     */
    public function deleteResult($file, $guid) {
        echo "\n<p>\n";
        echo $this->getTitle();
        echo "\n</p>\n";

        if (empty($file) || empty($guid)) {
            echo $this->mod_strings['LBL_NO_FILENAME'] . "<br /><br />";
        } else {
            //Making sure someone doesn't pass a variable name as a false reference
            //to delete a file
            if (strcmp(substr($file, 0, 10), "diagnostic") != 0)
                sugar_die($this->mod_strings['LBL_DELETE_NON_DIAGNOSTIC']);

            if (file_exists("cache/diagnostic/" .$guid. "/" .$file. ".zip")) {
                unlink("cache/diagnostic/" .$guid. "/" .$file. ".zip");
                rmdir("cache/diagnostic/" . $guid);
                echo $this->mod_strings['LBL_DIAGNOSTIC_DELETED'] . "<br/><br/>";
            } else {
                echo sprintf($this->mod_strings['LBL_ZIP_NOT_EXISTS'], $file);
            }
        }

        print "<a href=\"index.php?module=Administration&action=index\">" .$this->mod_strings['LBL_RETURN_HOME']. "</a><br />";
    }

    private function renderOptionsPage() {
        $no_mysql_msg = '';

        if($this->db->dbType == 'oci8')
            $no_mysql_msg = "<tr><td class='dataLabel' style='color: #ff0000;'><slot>{$this->mod_strings['LBL_DIAGNOSTIC_NO_MYSQL']}</slot></td></tr><tr><td>&nbsp;</td></tr>";

        $html = <<<EOQ
            <form name="Diagnostic" id="Diagnostic" method="POST" action="index.php">
            <input type="hidden" name="module" value="Administration" />
            <input type="hidden" name="action" value="DiagnosticRun" />
            <table cellpadding="0" cellspacing="0" border="0" width="100%">
                <tr>
                <td style="padding-bottom: 2px;">
                <input title="{$this->mod_strings['LBL_DIAG_EXECUTE_BUTTON']}" class="input-button" onclick="this.form.action.value='DiagnosticRun';" type="submit" name="button" value="  {$this->mod_strings['LBL_DIAG_EXECUTE_BUTTON']}  " />
                <input title="{$this->mod_strings['LBL_DIAG_CANCEL_BUTTON']}" class="input-button" onclick="this.form.action.value='index'; this.form.module.value='Administration'; " type="submit" name="button" value="  {$this->mod_strings['LBL_DIAG_CANCEL_BUTTON']}  " />
                </td>
                </tr>
            </table>
            <br />
            <div>
                <table id="maintable" width="430" border="0" cellspacing="0" cellpadding="0" class="tabForm">
                <tbody style="cursor: pointer; " id="sectionHeader-advanced" class="sectionClosed"><tr>
                <td align="left" onclick="return toggleSection('advanced')" colspan="4" class="dataLabel">
                <h4 class="dataLabel">{$this->app_strings['LNK_ADVANCED_SEARCH']}<div style="margin-left: 1.5em" class="input-arrow">&nbsp;</div></h4>
                </td>
                </tr></tbody>
                <tbody id="advanced" style="display: none;"><tr><td>
                <table width="100%" border="0" cellspacing="0" cellpadding="1">
                {$no_mysql_msg}
EOQ;

        $options = $this->getRubOptions();

        foreach ($options as $idx => $spec) {
            $label = $spec['label'];
            $check_box = $this->renderCheckBox($spec['name'], $spec['value'], $spec['disabled']);

            $html .= <<<EOQ
                <tr>
                <td class="dataLabel"><slot>{$this->mod_strings[$label]}</slot></td>
                <td class="dataField"><slot>{$check_box}</slot></td>
                </tr>
                <tr>
EOQ;
        }

        $html .= "</table></td></tr></tbody></table></div></form>";

        return $html;
    }

    private function initDb() {
        global $db;

        if(empty($db))
            $db &= PearDatabase::getInstance();

        $this->db = $db;
    }

    /**
     * @return string
     */
    private function getTitle() {
        return get_module_title('Diagnostic', $this->mod_strings['LBL_MODULE_NAME'].": ".$this->mod_strings['LBL_DIAGNOSTIC_TITLE'], true);
    }

    /**
     * @return array
     */
    private function getRubOptions() {
        $mysql_capable = true;

        if($this->db->dbType == 'oci8')
            $mysql_capable = false;

        return array(
            array('name' => 'configphp', 'label' => 'LBL_DIAGNOSTIC_CONFIGPHP', 'value' => true, 'disabled' => false),
            array('name' => 'custom_dir', 'label' => 'LBL_DIAGNOSTIC_CUSTOMDIR', 'value' => true, 'disabled' => false),
            array('name' => 'phpinfo', 'label' => 'LBL_DIAGNOSTIC_PHPINFO', 'value' => true, 'disabled' => false),
            array('name' => 'mysql_dumps', 'label' => 'LBL_DIAGNOSTIC_MYSQLDUMPS', 'value' => $mysql_capable, 'disabled' => ! $mysql_capable),
            array('name' => 'mysql_schema', 'label' => 'LBL_DIAGNOSTIC_MYSQLSCHEMA', 'value' => $mysql_capable, 'disabled' => ! $mysql_capable),
            array('name' => 'mysql_info', 'label' => 'LBL_DIAGNOSTIC_MYSQLINFO', 'value' => $mysql_capable, 'disabled' => ! $mysql_capable),
            array('name' => 'beanlistbeanfiles', 'label' => 'LBL_DIAGNOSTIC_BLBF', 'value' => true, 'disabled' => false),
            array('name' => 'iahlog', 'label' => 'LBL_DIAGNOSTIC_SUGARLOG', 'value' => true, 'disabled' => false),
            //array('name' => 'vardefs', 'label' => 'LBL_DIAGNOSTIC_VARDEFS', 'value' => true, 'disabled' => false),
        );
    }

    /**
     * @param string $name
     * @param bool $value
     * @param bool $disabled
     * @return string
     */
    private function renderCheckBox($name, $value, $disabled = false) {
        $spec = array('name' => $name);

        if ($disabled)
            $spec['disabled'] = true;

        return $this->form->renderCheck($spec, $value);
    }
}
