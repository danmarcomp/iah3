function dropShip() {
    var frm = SUGAR.ui.getForm('DetailForm');
	SUGAR.ui.onInitForm('DetailForm', addDropShip);
}

var drop_ship_address = {};

var drop_ship_fields = [
	'shipping_address_street',
	'shipping_address_city',
	'shipping_address_state',
	'shipping_address_postalcode',
	'shipping_address_country'
];

var drop_ship_account_name = '';
var drop_ship_account_id = '';
var drop_ship_contact_name = '';
var drop_ship_contact_id = '';

function addDropShip() {
    var frm = SUGAR.ui.getForm('DetailForm');
    var acct = SUGAR.ui.getFormInput(frm, 'shipping_account');
    var contact = SUGAR.ui.getFormInput(frm, 'shipping_contact');
    var drop_ship = SUGAR.ui.getFormInput(frm, 'drop_ship');

    if (drop_ship.getValue()) {
    	acct.setDisabled(false);
    	contact.setDisabled(false);
		acct.update(drop_ship_account_id, drop_ship_account_name);
		contact.update(drop_ship_contact_id, drop_ship_contact_name);
		for (var i=0; i < drop_ship_fields.length; i++) {
			var f = drop_ship_fields[i];
			frm[f].disabled = false;
			frm[f].value = drop_ship_address[f] || '';
		}

    } else {
		drop_ship_account_id = acct.getKey();
		drop_ship_account_name = acct.getValue();
		drop_ship_contact_id = contact.getKey();
		drop_ship_contact_name = contact.getValue();
    	acct.setDisabled(true);
    	contact.setDisabled(true);
		acct.clear();
		contact.clear();

		for (var i=0; i < drop_ship_fields.length; i++) {
			var f = drop_ship_fields[i];
			drop_ship_address[f] = frm[f].value;
			frm[f].value = '';
			frm[f].disabled = true;
		}
    }
}

