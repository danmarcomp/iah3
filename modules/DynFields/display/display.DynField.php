<?php return; /* no output */ ?>

list
	default_order_by: name
	no_mass_export: true
	no_mass_print: true
	no_mass_panel: true
	massupdate_handlers
		--
			name: HandleMassDelete
			class: DynField
			file: modules/DynFields/DynField.php
filters
	custom_module

