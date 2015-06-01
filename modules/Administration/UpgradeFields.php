<?php
if(!defined('sugarEntry') || !sugarEntry)
	die('Not A Valid Entry Point');
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
require_once ('modules/DynamicFields/DynamicField.php');
require_once ('modules/DynamicFields/FieldCases.php');
global $db;

if (!isset ($db)) {
	$db = & PearDatabase :: getInstance();
}
global $dbManager;
if (!isset ($dbManager)) {
	$dbManager = & DBManagerFactory :: getInstance();
}

$result = $db->query('SELECT * FROM fields_meta_data WHERE deleted = 0 ORDER BY custom_module');
$modules = array ();
/*
 * get the real field_meta_data
 */
while ($row = $db->fetchByAssoc($result)) {
	$the_modules = $row['custom_module'];
	if (!isset ($modules[$the_modules])) {
		$modules[$the_modules] = array ();
	}
	$modules[$the_modules][$row['name']] = $row['name'];
}

$simulate = false;
if (!isset ($_REQUEST['run'])) {
	$simulate = true;
	echo "SIMULATION MODE - NO CHANGES WILL BE MADE EXCEPT CLEARING CACHE";
}

foreach ($modules as $the_module => $fields) {
	$class_name = $beanList[$the_module];
	echo "<br><br>Scanning $the_module <br>";

	require_once ($beanFiles[$class_name]);
	$mod = new $class_name ();
	if (!$db->tableExists($mod->table_name."_cstm")) {
		$mod->custom_fields = new DynamicField();
		$mod->custom_fields->setup($mod);
		$mod->custom_fields->createCustomTable();
	}

	if ($db->dbType == 'oci8') {



	} elseif ($db->dbType == 'mssql') {
        $def_query="SELECT col.name as field, col_type.name as type, col.is_nullable, col.precision as data_precision, col.max_length as data_length, col.scale data_scale";
        $def_query.=" FROM sys.columns col";
        $def_query.=" join sys.types col_type on col.user_type_id=col_type.user_type_id ";
        $def_query.=" where col.object_id = (select object_id(sys.schemas.name + '.' + sys.tables.name)"; 
        $def_query.=" from sys.tables  join sys.schemas on sys.schemas.schema_id = sys.tables.schema_id";
        $def_query.=" where sys.tables.name='".$mod->table_name."_cstm')";        
        $result = $db->query($def_query);    
	}
	else {
		$result = $db->query("DESCRIBE $mod->table_name"."_cstm");
	}
    
	while ($row = $db->fetchByAssoc($result)) {
		$col = strtolower(empty ($row['Field']) ? $row['field'] : $row['Field']);
		$the_field = $mod->custom_fields->getField($col);
		$type = strtolower(empty ($row['Type']) ? $row['type'] : $row['Type']);
		if ($db->dbType == 'oci8' or $db->dbType == 'mssql') {
			if ($row['data_precision']!= '' and $row['data_scale']!='') {
				$type.='(' . $row['data_precision'];
				if (!empty($row['data_scale'])) {
					$type.=',' . $row['data_scale'];				
				}		
				$type.=')';
			}	 else {
				if ($row['data_length']!= '' && (strtolower($row['type'])=='varchar' or strtolower($row['type'])=='varchar2')) {
					$type.='(' . $row['data_length'] . ')';
			
				} 
			}	
		}	
		if (!isset ($fields[$col]) && $col != 'id_c') {
			if (!$simulate) {
				$db->query("ALTER TABLE $mod->table_name"."_cstm DROP COLUMN $col");
			}
			unset ($fields[$col]);
			echo "Dropping Column $col from $mod->table_name"."_cstm for module $the_module<br>";
		} else {
			if ($col != 'id_c') {
				//$db_data_type = $dbManager->helper->getColumnType(strtolower($the_field->data_type));
				$db_data_type = strtolower(str_replace(' ' , '', $the_field->get_db_type()));
				
				$type = strtolower(str_replace(' ' , '', $type));
				if (strcmp($db_data_type,$type) != 0) {

					echo "Fixing Column Type for $col changing $type to ".$db_data_type."<br>";
					if (!$simulate) {
						$db->query($the_field->get_db_modify_alter_table($mod->table_name.'_cstm'));
                    }
				}
			}

			unset ($fields[$col]);
		}

	}

	echo sizeof($fields)." field(s) missing from $mod->table_name"."_cstm<br>";
	foreach ($fields as $field) {
		echo "Adding Column $field to $mod->table_name"."_cstm<br>";
		if (!$simulate)
			$mod->custom_fields->add_existing_custom_field($field);
	}

}

DynamicField :: deleteCache();
echo '<br>Done<br>';
if ($simulate) {
	echo '<a href="index.php?module=Administration&action=UpgradeFields&run=true">Execute non-simulation mode</a>';
}
?>
