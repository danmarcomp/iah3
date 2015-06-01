<?php
require_once('include/layout/forms/EditableForm.php');
require_once('include/utils.php');
require_once('include/utils/file_utils.php');
require_once('include/utils/zip_utils.php');

class DiagnosticTool {

    /**
     * @var DBManager
     */
    private $db;

    /**
     * @var array
     */
    private $mod_strings;

    /**
     * @var string
     */
    private $theme_path;

    /**
     * @var string
     */
    private $current_datetime;

    /**
     * GUID used for directory path
     *
     * @var string
     */
    private $sod_guid;

    /**
     * @var string
     */
    private $current_dir;

    /**
     * @var string
     */
    private $cache_dir;

    /**
     * @var string
     */
    private $cache_dir_guid;

    /**
     * @var array
     */
    private $run_options = array();

    /**
     * @param array $params
     */
    public function __construct($params) {
        $this->initDb();
        $this->initOptions($params);
        $this->initCacheDir();

        global $mod_strings;
        $this->mod_strings = $mod_strings;
        $this->theme_path = "themes/" . AppConfig::theme()."/";
    }

    /**
     * Run System Diagnostic
     *
     * @return void
     */
    public function run() {
        $this->prepareRun();

        if (sizeof($this->run_options) > 0) {
            foreach ($this->run_options as $option => $value) {

                if ($option == 'mysql') {
                    $do_mysql_info = in_array('mysql_info', $value);
                    $do_mysql_dumps = in_array('mysql_dumps', $value);
                    $do_mysql_schema = in_array('mysql_schema', $value);

                    echo $this->mod_strings['LBL_DIAGNOSTIC_GETTING'] .
                    ($do_mysql_info ? "... " . $this->mod_strings['LBL_DIAGNOSTIC_GETMYSQLINFO'] : " ") .
                    ($do_mysql_dumps ? "... " . $this->mod_strings['LBL_DIAGNOSTIC_GETMYSQLTD'] : " ") .
                    ($do_mysql_schema ? "... " . $this->mod_strings['LBL_DIAGNOSTIC_GETMYSQLTS'] : "...") .
                    "<br />";

                    $this->executeMySql($do_mysql_dumps, $do_mysql_schema, $do_mysql_info);

                    echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                } else {
                    $this->execute($option);
                }

            }
        }

        $this->finishRun();
    }

    private function initOptions($params) {
        $options = array('configphp', 'custom_dir', 'phpinfo', 'beanlistbeanfiles',
            'iahlog', 'vardefs', 'mysql_dumps', 'mysql_schema', 'mysql_info'
        );

        for ($i = 0; $i < sizeof($options); $i++) {
            $name = $options[$i];
            if (! empty($params[$name])) {

                if (strpos($name, 'mysql') !== false) {
                    $this->run_options['mysql'][] = $name;
                } else {
                    $this->run_options[$name] = 1;
                }
            }
        }
    }

    private function initDb() {
        global $db;

        if(empty($db))
            $db &= PearDatabase::getInstance();

        $this->db = $db;
    }

    private function initCacheDir() {
        $this->sod_guid = create_guid();
        $this->current_datetime = date("Ymd-His");
        $this->current_dir = getcwd();

        //Creates the diagnostic directory in the cache directory
        $this->cache_dir = create_cache_directory("diagnostic/");
        $this->cache_dir = create_cache_directory("diagnostic/" . $this->sod_guid);
        $this->cache_dir_guid = $this->cache_dir;
        $this->cache_dir = create_cache_directory("diagnostic/" .$this->sod_guid. "/diagnostic" .$this->current_datetime. "/");
    }

    private function prepareRun() {
        //Display Diagnostic icon
        echo get_module_title("Diagnostic", $this->mod_strings['LBL_DIAGNOSTIC_TITLE'], true);
        echo "<br />";
        echo $this->mod_strings['LBL_DIAGNOSTIC_EXECUTING'];
        echo "<br /><br />";
    }

    function finishRun(){
        chdir($this->cache_dir);

        zip_dir('.', $this->current_dir .'/'. $this->cache_dir_guid ."/diagnostic". $this->current_datetime .".zip");

        //END ZIP ALL FILES AND EXTRACT IN CACHE ROOT
        chdir($this->current_dir);
        rmdir_recursive($this->cache_dir);

        print "<a href=\"cache/diagnostic/" .$this->sod_guid. "/diagnostic" .$this->current_datetime. ".zip\">" .$this->mod_strings['LBL_DIAGNOSTIC_DOWNLOADLINK']. "</a><br />";
        print "<a href=\"index.php?module=Administration&action=DiagnosticDelete&file=diagnostic" .$this->current_datetime. "&guid=" .$this->sod_guid. "\">" .$this->mod_strings['LBL_DIAGNOSTIC_DELETELINK']. "</a><br />";
    }

    private function execute($name) {
        switch ($name) {
            case 'configphp':
                echo $this->mod_strings['LBL_DIAGNOSTIC_GETCONFPHP'] . "<br />";
                $this->executeConfigPhp();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            case 'custom_dir':
                echo $this->mod_strings['LBL_DIAGNOSTIC_GETCUSTDIR'] . "<br />";
                $this->executeCustomDir();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            case 'phpinfo':
                echo $this->mod_strings['LBL_DIAGNOSTIC_GETPHPINFO'] . "<br />";
                $this->executePhpInfo();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            case 'beanlistbeanfiles':
                echo $this->mod_strings['LBL_DIAGNOSTIC_GETBEANFILES'] . "<br />";
                $this->executeBeanListBeanFiles();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            case 'iahlog':
                echo $this->mod_strings['LBL_DIAGNOSTIC_GETSUGARLOG'] . "<br />";
                $this->executeIahLog();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            case 'vardefs':
                echo $this->mod_strings['LBL_DIAGNOSTIC_VARDEFS'] . "<br />";
                $this->executeVarDefs();
                echo $this->mod_strings['LBL_DIAGNOSTIC_DONE'] . "<br /><br />";
                break;
            default:
                break;
        }
    }

    private function executeConfigPhp() {
        // hide password in copied config file
        $pass_key = 'database.primary.password';
        $p = AppConfig::setting($pass_key);

        AppConfig::set_local($pass_key, '********');
        AppConfig::save_local(null, false, $this->cache_dir . "config.php");
        AppConfig::set_local($pass_key, $p);
    }

    private function executeCustomDir() {
        zip_dir('custom', $this->cache_dir . "custom_directory.zip");
    }

    private function executePhpInfo() {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        $handle = fopen($this->cache_dir . "phpinfo.html", "w");

        if (fwrite($handle, $phpinfo) === FALSE) {
            echo "Cannot write to file " .$this->cache_dir. "phpinfo.html<br />";
        }

        fclose($handle);
    }

    private function executeBeanListBeanFiles() {
        global $beanList, $beanFiles;

        ob_start();
        echo $this->mod_strings['LBL_DIAGNOSTIC_BEANLIST_DESC'];
        echo "<br />";
        echo "<span style='color: #008000;'>";
        echo $this->mod_strings['LBL_DIAGNOSTIC_BEANLIST_GREEN'];
        echo "</span>";
        echo "<br />";
        echo "<span style='color: orange;'>";
        echo $this->mod_strings['LBL_DIAGNOSTIC_BEANLIST_ORANGE'];
        echo "</span>";
        echo "<br />";
        echo "<span style='color: #ff0000;'>";
        echo $this->mod_strings['LBL_DIAGNOSTIC_BEANLIST_RED'];
        echo "</span>";
        echo "<br /><br>";

        foreach ($beanList as $beanz) {
            if( !isset($beanFiles[$beanz])) {
                echo "<font color=orange>NO! --- " .$beanz. " is not an index in \$beanFiles</font><br />";
            } else {
                if(file_exists($beanFiles[$beanz])) {
                    echo "<font color=green>YES --- " .$beanz. " file \"" .$beanFiles[$beanz]. "\" exists</font><br />";
                } else {
                    echo "<font color=red>NO! --- " .$beanz. " file \"" .$beanFiles[$beanz]. "\" does NOT exist</font><br />";
                }
            }
        }

        $content = ob_get_contents();
        ob_end_clean();

        $handle = fopen($this->cache_dir . "beanFiles.html", "w");
        if (fwrite($handle, $content) === FALSE){
            echo "Cannot write to file " .$this->cache_dir. "beanFiles.html<br />";
        }

        fclose($handle);
    }

    private function executeIahLog() {
        if (! is_file('infoathand.log')) {
            echo "Log file infoathand.log not found.<br />";
        } elseif (! is_readable('infoathand.log')) {
            echo "Log file infoathand.log is not readable.<br />";
        } elseif (! copy('infoathand.log', $this->cache_dir . '/infoathand.log')) {
            echo "Couldn't copy infoathand.log to cacheDir.<br />";
        }
    }

    private function executeVarDefs() { }

    private function executeMySql($get_dumps, $get_schema, $get_info) {
        $mysql_info_dir = create_cache_directory("diagnostic/" .$this->sod_guid. "/diagnostic" .$this->current_datetime. "/MySQL/");

        if($get_info) {
            ob_start();
            echo "MySQL Version: " .$this->db->getClientInfo(). "<br />";
            echo "MySQL Host Info: " .$this->db->getHostInfo(). "<br />";
            echo "MySQL Server Info: " .$this->db->getServerInfo(). "<br />";

            echo "<br />MySQL Character Set Settings<br>";
            $res = $this->db->query("show variables like 'character\_set\_%'");
            echo "<table border=\"1\"><tr><th>Variable Name</th><th>Value</th></tr>";

            while ($row = $this->db->fetchByAssoc($res)) {
                printf("<tr><td>%s</td><td>%s</td></tr>", $row['Variable_name'], $row['Value']);
            }

            echo "</table>";

            $content = ob_get_contents();
            ob_end_clean();

            $handle = fopen($mysql_info_dir . "MySQL-General-info.html", "w");
            if (fwrite($handle, $content) === FALSE){
                echo "Cannot write to file " .$mysql_info_dir. "_MySQL-General-info.html<br />";
            }

            fclose($handle);
        }


        if($get_schema) {
            $tables_schema_dir = create_cache_directory("diagnostic/" .$this->sod_guid. "/diagnostic" .$this->current_datetime. "/MySQL/TableSchema/");
            $all_tables = $this->db->getTablesArray();

            ob_start();
            echo "<style type='text/css'>";
            echo file_get_contents($this->theme_path . "style.css");
            echo "</style>";

            foreach ($all_tables as $tablename) {
                echo "<table border=\"0\" cellpadding=\"0\" class=\"tabDetailView\">";

                echo "<tr>MySQL " .$tablename. " Definitions:</tr>".
                    "<tr><td class=\"tabDetailViewDL\"><b>Field</b></td>".
                    "<td class=\"tabDetailViewDL\">Type</td>".
                    "<td class=\"tabDetailViewDL\">Null</td>".
                    "<td class=\"tabDetailViewDL\">Key</td>".
                    "<td class=\"tabDetailViewDL\">Default</td>".
                    "<td class=\"tabDetailViewDL\">Extra</td></tr>";

                $describe = $this->db->query("describe ".$tablename);

                while ($inner_row = $this->db->fetchByRow($describe)) {
                    echo "<tr><td class=\"tabDetailViewDF\"><b>" .$inner_row[0]. "</b></td>";
                    echo "<td class=\"tabDetailViewDF\">" .$inner_row[1]. "</td>";
                    echo "<td class=\"tabDetailViewDF\">" .$inner_row[2]. "</td>";
                    echo "<td class=\"tabDetailViewDF\">" .$inner_row[3]. "</td>";
                    echo "<td class=\"tabDetailViewDF\">" .$inner_row[4]. "</td>";
                    echo "<td class=\"tabDetailViewDF\">" .$inner_row[5]. "</td></tr>";
                }

                echo "</table>";
                echo "<br /><br />";
            }

            $content = ob_get_contents();
            ob_end_clean();

            $handle = fopen($tables_schema_dir . "MySQLTablesSchema.html", "w");

            if (fwrite($handle, $content) === FALSE){
                echo "Cannot write to file " .$tables_schema_dir. "MySQLTablesSchema.html<br />";
            }

            fclose($handle);
        }

        if($get_dumps) {
            $table_dump_dir = create_cache_directory("diagnostic/" .$this->sod_guid. "/diagnostic" .$this->current_datetime. "/MySQL/TableDumps/");
            // array of all tables that we need to pull rows from below
            $get_dumps_from = array(
                'config' => 'config',
                'fields_meta_data' => 'fields_meta_data',
                'upgrade_history' => 'upgrade_history',
                'versions' => 'versions'
            );


            foreach ($get_dumps_from as $table) {
                ob_start();
                //calling function defined above to get the string for dump
                echo $this->getFullTableDump($table);
                $content = ob_get_contents();
                ob_end_clean();
                $handle = fopen($table_dump_dir . $table . ".html", "w");

                if(fwrite($handle, $content) === FALSE){
                    echo "Cannot write to file " .$table_dump_dir.$table. "html<br />";
                }

                fclose($handle);
            }
        }
    }

    /**
     * @param string $table_name
     * @return string
     */
    function getFullTableDump($table_name) {
        $return_string = "<table border=\"1\">";
        $return_string .= "<tr><b><center>Table " .$table_name. "</center></b></tr>";

        //get table field definitions
        $definitions = array();
        $def_result = $this->db->query("describe " . $table_name);

        if (! $def_result) {
            return $this->db->getLastError();
        } else {
            $return_string .= "<tr><td style='font-weight: bold;'>Row Num</td>";
            $def_count = 0;

            while($row = $this->db->fetchByRow($def_result)) {
                $definitions[$def_count] = $row[0];
                $def_count ++;
                $return_string .= "<td><b>".$row[0]."</b></td>";
            }
            $return_string .= "</tr>";
        }

        $td_result = $this->db->query("select * from ".$table_name);

        if(! $td_result) {
            return $this->db->getLastError();
        } else {
            $row_counter = 1;

            while($row = $this->db->fetchByRow($td_result)) {
                $return_string .= "<tr>";
                $return_string .= "<td>".$row_counter."</td>";

                for ($counter = 0; $counter < $def_count; $counter++) {
                    if($counter != 0 && strcmp($row[$counter - 1], "smtppass") == 0) {
                        $return_string .= "<td>********</td>";
                    } else {
                        $return_string .= "<td>".($row[$counter] == "" ? "&nbsp;" : $row[$counter])."</td>";
                    }
                }
                $row_counter ++;
                $return_string .= "</tr>";
            }
        }

        $return_string .= "</table>";

        return $return_string;
    }

}
