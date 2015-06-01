<?php return; /* no output */ ?>

list
	default_order_by: name
fields
    set_dripfeed_delay
        vname: LBL_DREEPFEED_DELAY
        vname_module: EmailMarketing
        type: varchar
        widget: RunIntervalInput
        qty_name: dripfeed_delay
widgets
    DelayInput
        type: column
		path: modules/EmailMarketing/widgets/DelayInput.php
	EmailTemplateEditCreate
        type: column
		path: modules/EmailMarketing/widgets/EmailTemplateEditCreate.php

