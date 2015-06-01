<?php

require_once 'modules/NewStudio/wizards/StudioWizardBase.php';

class StudioWizard extends StudioWizardBase
{
	public function render()
	{
		global $mod_strings;
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . ':' . $mod_strings['LBL_SELECT_ACTION'], false);
		$options = array(
			'LBL_EDIT_CUSTOM' => 'EditCustom',
			'LBL_EDIT_DROPDOWNS' => 'EditDropdowns',
			'LBL_BACKUP_STUDIO' => 'BackupStudio',
		);

		foreach ($options as $label => $wizard)
		{
			echo '<a href="index.php?module=NewStudio&action=index&wizard=' . $wizard . '">';
			echo $mod_strings[$label];
			echo '</a>&nbsp;';
		}
	}
}

